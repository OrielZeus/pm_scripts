<?php
/**
 * Collections Sync IDs To Local
 *
 * Clones source collection IDs progressively into local:
 * - If local collection with same ID exists -> keep it
 * - If ID does not exist -> create an empty placeholder collection
 *
 * Input JSON ($data) example:
 * {
 *   "source_api_host": "https://source.example/api/1.0",
 *   "source_api_token": "source_token",
 *   "local_api_host": "https://local.example/api/1.0",
 *   "local_api_token": "local_token",
 *   "collection_ids": [1, 2, 23],
 *   "create_if_missing": true,
 *   "dry_run": false
 * }
 *
 * Fallback env vars:
 * - SOURCE_API_HOST / SOURCE_API_TOKEN
 * - API_HOST / API_TOKEN (for local)
 */

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$sourceHost = rtrim((string)($payload['source_api_host'] ?? getenv('SOURCE_API_HOST') ?? ''), '/');
$sourceToken = (string)($payload['source_api_token'] ?? getenv('SOURCE_API_TOKEN') ?? '');
$localHost = rtrim((string)($payload['local_api_host'] ?? getenv('API_HOST') ?? ''), '/');
$localToken = (string)($payload['local_api_token'] ?? getenv('API_TOKEN') ?? '');
$createIfMissing = !isset($payload['create_if_missing']) || (bool)$payload['create_if_missing'];
$dryRun = (bool)($payload['dry_run'] ?? false);

if ($sourceHost === '' || $sourceToken === '') {
    return [
        'success' => false,
        'error' => 'Missing source_api_host/source_api_token or SOURCE_API_HOST/SOURCE_API_TOKEN.',
    ];
}
if ($localHost === '' || $localToken === '') {
    return [
        'success' => false,
        'error' => 'Missing local_api_host/local_api_token or API_HOST/API_TOKEN for local.',
    ];
}

try {
    $requestedIds = normalizeIds($payload['collection_ids'] ?? []);
    $sourceCollections = fetchSourceCollections($sourceHost, $sourceToken, $requestedIds);
    if (!$sourceCollections['success']) {
        return $sourceCollections;
    }

    $rows = [];
    $created = 0;
    $existing = 0;
    $errors = 0;

    foreach ($sourceCollections['data'] as $src) {
        $sourceId = (int)$src['id'];

        // 1) Check if same ID already exists in local
        $localGet = pmApiCall($localHost . '/collections/' . $sourceId, 'GET', null, $localToken);
        if ($localGet['ok']) {
            $existing++;
            $rows[] = [
                'source_id' => $sourceId,
                'status' => 'exists',
                'local_id' => $sourceId,
                'name' => (string)($src['name'] ?? ''),
            ];
            continue;
        }

        if (!$createIfMissing) {
            $rows[] = [
                'source_id' => $sourceId,
                'status' => 'missing',
                'local_id' => null,
                'name' => (string)($src['name'] ?? ''),
            ];
            continue;
        }

        $name = trim((string)($src['name'] ?? ''));
        if ($name === '') {
            $name = 'Collection ID ' . $sourceId;
        }

        $description = trim((string)($src['description'] ?? ''));
        $description = trim($description . ' [auto-cloned placeholder for source id ' . $sourceId . ']');

        $createPayload = [
            'id' => $sourceId, // Try to preserve ID first
            'name' => $name,
            'description' => $description,
            'columns' => [],
        ];

        if ($dryRun) {
            $rows[] = [
                'source_id' => $sourceId,
                'status' => 'dry_run_create',
                'local_id' => null,
                'name' => $name,
                'payload' => $createPayload,
            ];
            continue;
        }

        $create = pmApiCall($localHost . '/collections', 'POST', $createPayload, $localToken);
        if (!$create['ok']) {
            // Fallback: some PM versions do not allow explicit id in create payload
            unset($createPayload['id']);
            $create = pmApiCall($localHost . '/collections', 'POST', $createPayload, $localToken);
        }

        if ($create['ok']) {
            $createdData = $create['json']['data'] ?? [];
            $localId = isset($createdData['id']) ? (int)$createdData['id'] : null;
            $created++;
            $rows[] = [
                'source_id' => $sourceId,
                'status' => 'created',
                'local_id' => $localId,
                'name' => $name,
            ];
        } else {
            $errors++;
            $rows[] = [
                'source_id' => $sourceId,
                'status' => 'error',
                'local_id' => null,
                'name' => $name,
                'detail' => $create,
            ];
        }
    }

    return [
        'success' => true,
        'summary' => [
            'total' => count($sourceCollections['data']),
            'existing' => $existing,
            'created' => $created,
            'errors' => $errors,
            'dry_run' => $dryRun,
        ],
        'data' => $rows,
    ];
} catch (Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

function fetchSourceCollections(string $sourceHost, string $sourceToken, array $requestedIds): array
{
    if (!empty($requestedIds)) {
        $out = [];
        foreach ($requestedIds as $id) {
            $res = pmApiCall($sourceHost . '/collections/' . $id, 'GET', null, $sourceToken);
            if ($res['ok'] && is_array($res['json'])) {
                $data = is_array($res['json']['data'] ?? null) ? $res['json']['data'] : $res['json'];
                $out[] = [
                    'id' => (int)($data['id'] ?? $id),
                    'name' => (string)($data['name'] ?? ''),
                    'description' => (string)($data['description'] ?? ''),
                ];
            }
        }
        return ['success' => true, 'data' => $out];
    }

    $all = [];
    $page = 1;
    $perPage = 100;
    $lastPage = 1;
    $maxPages = 200;
    do {
        $res = pmApiCall($sourceHost . '/collections?page=' . $page . '&per_page=' . $perPage, 'GET', null, $sourceToken);
        if (!$res['ok']) {
            return [
                'success' => false,
                'error' => 'Failed to list source collections',
                'detail' => $res,
            ];
        }

        $json = $res['json'];
        foreach (($json['data'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $all[] = [
                'id' => isset($row['id']) ? (int)$row['id'] : null,
                'name' => (string)($row['name'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
            ];
        }

        $meta = $json['meta'] ?? [];
        $lastPage = (int)($meta['last_page'] ?? $lastPage);
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        $page++;
    } while ($page <= $lastPage && $page <= $maxPages);

    usort($all, static function (array $a, array $b): int {
        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });
    return ['success' => true, 'data' => $all];
}

function normalizeIds($ids): array
{
    if (!is_array($ids)) {
        return [];
    }
    $out = [];
    foreach ($ids as $id) {
        $n = (int)$id;
        if ($n > 0) {
            $out[$n] = $n;
        }
    }
    ksort($out);
    return array_values($out);
}

function pmApiCall(string $url, string $method, $body, string $token): array
{
    $headers = [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ];
    if ($body !== null) {
        $headers['Content-Type'] = 'application/json';
    }

    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 90]);
    $requestBody = $body === null ? null : json_encode($body);
    $request = new \GuzzleHttp\Psr7\Request($method, $url, $headers, $requestBody);

    try {
        $response = $client->send($request, ['http_errors' => false]);
        $status = (int)$response->getStatusCode();
        $raw = (string)$response->getBody();
        $json = json_decode($raw, true);
        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'json' => is_array($json) ? $json : null,
            'raw' => $raw,
        ];
    } catch (\Throwable $e) {
        return [
            'ok' => false,
            'status' => 0,
            'json' => null,
            'raw' => null,
            'error' => $e->getMessage(),
        ];
    }
}
