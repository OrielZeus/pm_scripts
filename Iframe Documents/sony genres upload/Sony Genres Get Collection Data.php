<?php
/**
 * Sony Genres — collection_23 (genres) records for DataTable / comparison.
 *
 * JSON input: { "page": 1 } optional; default fetches all pages chained until meta.total.
 *
 * API base URL (API_HOST) must already include /api/1.0 — paths are relative to that, e.g. /collections/{id}/records
 */

$collectionId = '23';
$apiHost = rtrim(getenv('API_HOST') ?: '', '/');

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$singlePage = isset($payload['page']) ? max(1, (int) $payload['page']) : null;
$perPage = isset($payload['per_page']) ? min(1000, max(1, (int) $payload['per_page'])) : 100;

if ($apiHost === '') {
    return [
        'success' => false,
        'error' => 'API_HOST is not set in the script environment.',
        'data' => [],
    ];
}

$normalizeRecord = static function (array $rec): array {
    $row = $rec['data'] ?? [];
    return [
        'record_id' => $rec['id'] ?? null,
        'name' => $row['name'] ?? '',
        'value' => $row['value'] ?? '',
        'row_id' => $row['row_id'] ?? '',
        'content' => $row['content'] ?? '',
        'raw' => $row,
    ];
};

$isHelperError = static function ($res): bool {
    return is_array($res) && isset($res['error_message']);
};

try {
    $all = [];
    $page = $singlePage ?? 1;
    $totalPages = 1;

    $maxPages = 500;
    do {
        $url = $apiHost . '/collections/' . $collectionId . '/records'
            . '?page=' . $page . '&per_page=' . $perPage;

        $json = callApiUrlGuzzle($url, 'GET', []);

        if ($isHelperError($json)) {
            return [
                'success' => false,
                'error' => $json['error_message'] ?? 'Request failed',
                'data' => [],
            ];
        }

        if (!is_array($json)) {
            return [
                'success' => false,
                'error' => 'Invalid API response',
                'data' => [],
            ];
        }

        $chunk = $json['data'] ?? [];
        foreach ($chunk as $rec) {
            $all[] = $normalizeRecord(is_array($rec) ? $rec : []);
        }

        $meta = $json['meta'] ?? [];
        $total = (int) ($meta['total'] ?? count($all));
        $lastPage = (int) ($meta['last_page'] ?? 0);
        if ($lastPage < 1 && $total > 0) {
            $lastPage = (int) max(1, ceil($total / max(1, $perPage)));
        }
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        $totalPages = $lastPage;

        if ($singlePage !== null) {
            return [
                'success' => true,
                'data' => $all,
                'meta' => $meta,
                'draw' => (int) ($payload['draw'] ?? 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
            ];
        }

        $page++;
    } while ($page <= $totalPages && $page <= $maxPages);

    return [
        'success' => true,
        'data' => $all,
        'meta' => [
            'total' => count($all),
            'collection_id' => $collectionId,
        ],
    ];
} catch (Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
    ];
}

// --- Embedded helpers (same as function helpers.php — self-contained, no external include) ---

function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        'Accept' => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken,
    ];
    if ($contentFile === false) {
        $headers['Content-Type'] = 'application/json';
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ':' . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? '',
        ];
    }
    return $res;
}

function encodeSql($query)
{
    return ['SQL' => base64_encode($query)];
}
