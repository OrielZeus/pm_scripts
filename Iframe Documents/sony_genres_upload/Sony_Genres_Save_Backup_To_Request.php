<?php
/**
 * Sony Genres — snapshot collection_23 into request data variable `collection_backup`
 *
 * Fetches all collection records (same shape as Get Collection), then PATCHes request data
 * via PUT /requests/{id} with merged `collection_backup` (does not remove other request keys).
 *
 * JSON input:
 * {
 *   "request_id": 90235
 * }
 */

$collectionId = '23';
$defaultRequestId = 90235;
$apiHost = rtrim(getenv('API_HOST') ?: '', '/');
$token = getenv('API_TOKEN');

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$requestId = isset($payload['request_id']) ? (int) $payload['request_id'] : $defaultRequestId;

if ($apiHost === '') {
    return [
        'success' => false,
        'error' => 'API_HOST is not set.',
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

try {
    $all = [];
    $page = 1;
    $perPage = 100;
    $maxPages = 500;
    $totalPages = 1;

    do {
        $url = $apiHost . '/collections/' . $collectionId . '/records'
            . '?page=' . $page . '&per_page=' . $perPage;

        $json = callApiUrlGuzzle($url, 'GET', []);

        if (is_array($json) && isset($json['error_message'])) {
            return [
                'success' => false,
                'error' => $json['error_message'],
            ];
        }

        if (!is_array($json)) {
            return ['success' => false, 'error' => 'Invalid API response'];
        }

        $chunk = $json['data'] ?? [];
        foreach ($chunk as $rec) {
            $all[] = $normalizeRecord(is_array($rec) ? $rec : []);
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

    $backup = [
        'saved_at' => gmdate('c'),
        'collection_id' => (int) $collectionId,
        'record_count' => count($all),
        'records' => $all,
    ];

    $putUrl = $apiHost . '/requests/' . $requestId;
    $putBody = [
        'data' => [
            'collection_backup' => $backup,
        ],
    ];

    $putRes = callApiUrlGuzzlePut($putUrl, $putBody);

    if (is_array($putRes) && isset($putRes['error_message'])) {
        return [
            'success' => false,
            'error' => $putRes['error_message'],
            'backup_prepared' => true,
            'record_count' => count($all),
        ];
    }

    return [
        'success' => true,
        'request_id' => $requestId,
        'record_count' => count($all),
        'message' => 'collection_backup saved on request data (see request JSON in PM).',
    ];
} catch (Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}

function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }
    $acceptType = $contentFile ? 'application/octet-stream' : 'application/json';
    $headers = [
        'Accept' => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken,
    ];
    if ($contentFile === false && strtoupper($requestType) !== 'GET') {
        $headers['Content-Type'] = 'application/json';
    }
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $body = (strtoupper($requestType) === 'GET' || strtoupper($requestType) === 'DELETE')
        ? null
        : json_encode($postdata);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, $body);
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
    } catch (\Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
        ];
    }
    return $res;
}

function callApiUrlGuzzlePut(string $url, array $body): array
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }
    $headers = [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $apiToken,
        'Content-Type' => 'application/json',
    ];
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $request = new \GuzzleHttp\Psr7\Request('PUT', $url, $headers, json_encode($body));
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
        return [
            'error_message' => $e->getMessage(),
        ];
    }
}

function encodeSql($query)
{
    return ['SQL' => base64_encode($query)];
}
