<?php
/**
 * Sony Genres — apply Excel-aligned changes to collection_23
 *
 * JSON input:
 * {
 *   "request_id": 90235,
 *   "strategy": "replace|adjust|mix",
 *   "excel_rows": [
 *     { "key": "897", "name": "ASMR", "active": true, "row_id": "", "global_genre": "", "record_id": 12345 }
 *   ],
 *   "confirm_replace": true
 * }
 *
 * - replace: DELETE every collection record, then POST one row per excel_rows (requires confirm_replace === true).
 * - adjust: PATCH records whose value matches Excel key but name/content/row_id/active/global_genre differ; no creates.
 * - mix: POST rows missing in collection; PATCH rows that exist but differ.
 */

$collectionId = '23';
$defaultRequestId = 90235;
$apiHost = rtrim(getenv('API_HOST') ?: '', '/');

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$requestId = isset($payload['request_id']) ? (int) $payload['request_id'] : $defaultRequestId;
$strategy = isset($payload['strategy']) ? strtolower(trim((string) $payload['strategy'])) : '';
$excelRows = isset($payload['excel_rows']) && is_array($payload['excel_rows']) ? $payload['excel_rows'] : [];
$confirmReplace = !empty($payload['confirm_replace']);

if ($apiHost === '') {
    return ['success' => false, 'error' => 'API_HOST is not set.'];
}

if (!in_array($strategy, ['replace', 'adjust', 'mix'], true)) {
    return ['success' => false, 'error' => 'Invalid strategy. Use replace, adjust, or mix.'];
}

if ($strategy === 'replace' && !$confirmReplace) {
    return [
        'success' => false,
        'error' => 'replace requires confirm_replace: true.',
    ];
}

try {
    $collection = fetchAllRecords($apiHost, $collectionId);
    $byValue = [];
    $byId = [];
    foreach ($collection as $rec) {
        $rid = isset($rec['id']) ? (int) $rec['id'] : 0;
        if ($rid > 0) {
            $byId[$rid] = $rec;
        }
        $v = normalizeKey($rec['data']['value'] ?? '');
        if ($v !== '') {
            $byValue[$v] = $rec;
        }
    }

    $report = [
        'request_id' => $requestId,
        'strategy' => $strategy,
        'created' => [],
        'updated' => [],
        'deleted' => [],
        'skipped' => [],
        'errors' => [],
    ];

    if ($strategy === 'replace') {
        foreach ($collection as $rec) {
            $rid = $rec['id'] ?? null;
            if ($rid === null) {
                continue;
            }
            $del = apiDelete($apiHost, $collectionId, (string) $rid);
            if (!empty($del['error_message'])) {
                $report['errors'][] = ['op' => 'delete', 'record_id' => $rid, 'error' => $del['error_message']];
            } else {
                $report['deleted'][] = (int) $rid;
            }
        }
        foreach ($excelRows as $er) {
            $plan = normalizeExcelRow($er);
            if ($plan['key'] === '') {
                continue;
            }
            $body = ['data' => buildMergedData(null, $plan)];
            $created = apiPost($apiHost, $collectionId, $body);
            if (!empty($created['error_message'])) {
                $report['errors'][] = ['op' => 'create', 'key' => $plan['key'], 'error' => $created['error_message']];
            } elseif (isset($created['id'])) {
                $report['created'][] = ['id' => $created['id'], 'value' => $plan['key']];
            }
        }

        return ['success' => count($report['errors']) === 0, 'report' => $report];
    }

    foreach ($excelRows as $er) {
        $plan = normalizeExcelRow($er);
        if ($plan['key'] === '') {
            continue;
        }

        $k = $plan['key'];
        $ridExcel = $plan['record_id'] ?? 0;

        $existing = null;
        if ($ridExcel > 0 && isset($byId[$ridExcel])) {
            $existing = $byId[$ridExcel];
        } elseif (isset($byValue[$k])) {
            $existing = $byValue[$k];
        }

        if ($existing === null) {
            if ($strategy === 'adjust') {
                $report['skipped'][] = ['reason' => 'not_in_collection', 'value' => $k];
                continue;
            }
            $body = ['data' => buildMergedData(null, $plan)];
            $created = apiPost($apiHost, $collectionId, $body);
            if (!empty($created['error_message'])) {
                $report['errors'][] = ['op' => 'create', 'key' => $k, 'error' => $created['error_message']];
            } elseif (isset($created['id'])) {
                $report['created'][] = ['id' => $created['id'], 'value' => $k];
                $byValue[$k] = $created;
                $byId[(int) $created['id']] = $created;
            }
            continue;
        }

        $cur = $existing['data'] ?? [];
        $merged = buildMergedData(is_array($cur) ? $cur : [], $plan);
        if (!needsUpdate($cur, $merged)) {
            $report['skipped'][] = ['reason' => 'no_change', 'record_id' => $existing['id'], 'value' => $k];
            continue;
        }

        $rid = (string) ($existing['id'] ?? '');
        if ($rid === '') {
            continue;
        }

        $put = apiPut($apiHost, $collectionId, $rid, ['data' => $merged]);
        if (!empty($put['error_message'])) {
            $report['errors'][] = ['op' => 'update', 'record_id' => $rid, 'error' => $put['error_message']];
        } else {
            $report['updated'][] = ['id' => (int) $rid, 'value' => $k];
        }
    }

    return [
        'success' => count($report['errors']) === 0,
        'report' => $report,
    ];
} catch (Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

function normalizeKey($v): string
{
    return trim((string) $v);
}

function normalizeExcelRow(array $er): array
{
    $key = $er['key'] ?? $er['value'] ?? '';
    $rid = isset($er['record_id']) ? (int) $er['record_id'] : 0;

    return [
        'key' => normalizeKey((string) $key),
        'name' => trim((string) ($er['name'] ?? '')),
        'row_id' => trim((string) ($er['row_id'] ?? '')),
        'global_genre' => trim((string) ($er['global_genre'] ?? '')),
        'active' => $er['active'] ?? true,
        'record_id' => $rid > 0 ? $rid : 0,
    ];
}

function parseActive($active): bool
{
    if (is_bool($active)) {
        return $active;
    }
    $s = strtolower(trim((string) $active));
    return in_array($s, ['true', '1', 'yes', 'si', 'sí'], true);
}

function buildMergedData(?array $existing, array $plan): array
{
    $base = $existing ?? [];
    $name = $plan['name'];
    $out = array_merge($base, [
        'value' => $plan['key'],
        'content' => $name !== '' ? $name : ($base['content'] ?? ''),
        'name' => $name !== '' ? $name : ($base['name'] ?? $base['content'] ?? ''),
    ]);
    $out['is_active'] = parseActive($plan['active']);
    if ($plan['global_genre'] !== '') {
        $out['global_genre'] = $plan['global_genre'];
    }
    if ($plan['row_id'] !== '') {
        $out['row_id'] = $plan['row_id'];
    }
    return $out;
}

function needsUpdate(array $cur, array $merged): bool
{
    $keys = ['value', 'content', 'name', 'row_id', 'global_genre', 'is_active'];
    foreach ($keys as $k) {
        if (!array_key_exists($k, $merged)) {
            continue;
        }
        $a = $cur[$k] ?? null;
        $b = $merged[$k];
        if ($k === 'is_active') {
            $a = parseActive($a ?? false);
            $b = parseActive($b);
        } else {
            $a = is_scalar($a) || $a === null ? (string) $a : json_encode($a);
            $b = is_scalar($b) || $b === null ? (string) $b : json_encode($b);
        }
        if ($a !== $b) {
            return true;
        }
    }
    return false;
}

function fetchAllRecords(string $apiHost, string $collectionId): array
{
    $all = [];
    $page = 1;
    $perPage = 100;
    $maxPages = 500;
    $totalPages = 1;

    do {
        $url = $apiHost . '/collections/' . $collectionId . '/records?page=' . $page . '&per_page=' . $perPage;
        $json = callApiUrlGuzzle($url, 'GET', []);
        if (is_array($json) && isset($json['error_message'])) {
            throw new RuntimeException($json['error_message']);
        }
        if (!is_array($json)) {
            throw new RuntimeException('Invalid records response');
        }
        $chunk = $json['data'] ?? [];
        foreach ($chunk as $rec) {
            if (is_array($rec)) {
                $all[] = $rec;
            }
        }
        $meta = $json['meta'] ?? [];
        $lastPage = (int) ($meta['last_page'] ?? 0);
        $total = (int) ($meta['total'] ?? count($all));
        if ($lastPage < 1 && $total > 0) {
            $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
        }
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        $totalPages = $lastPage;
        $page++;
    } while ($page <= $totalPages && $page <= $maxPages);

    return $all;
}

function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [])
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }
    $headers = [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $apiToken,
    ];
    $method = strtoupper($requestType);
    if ($method !== 'GET' && $method !== 'DELETE') {
        $headers['Content-Type'] = 'application/json';
    }
    $body = ($method === 'GET' || $method === 'DELETE') ? null : json_encode($postdata);
    $client = new \GuzzleHttp\Client(['verify' => false, 'http_errors' => false]);
    $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $body);
    try {
        $res = $client->sendAsync($request)->wait();
        $code = $res->getStatusCode();
        $raw = $res->getBody()->getContents();
        if ($code === 204 || $raw === '') {
            return ['success' => true, 'http_code' => $code];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['raw' => $raw, 'http_code' => $code];
    } catch (\Throwable $e) {
        return ['error_message' => $e->getMessage()];
    }
}

function apiPost(string $apiHost, string $collectionId, array $body): array
{
    $url = $apiHost . '/collections/' . $collectionId . '/records';
    return callApiUrlGuzzle($url, 'POST', $body);
}

function apiPut(string $apiHost, string $collectionId, string $recordId, array $body): array
{
    $url = $apiHost . '/collections/' . $collectionId . '/records/' . $recordId;
    return callApiUrlGuzzle($url, 'PUT', $body);
}

function apiDelete(string $apiHost, string $collectionId, string $recordId): array
{
    $url = $apiHost . '/collections/' . $collectionId . '/records/' . $recordId;
    return callApiUrlGuzzle($url, 'DELETE', []);
}

function encodeSql($query)
{
    return ['SQL' => base64_encode($query)];
}
