<?php
/**
 * DLSU — list ProcessMaker media files attached to a request (uses dev API_HOST / API_TOKEN).
 * Register as PSTools slug e.g. dlsu-request-files.
 * Input: { "request_id": 123 }
 */

if (!isset($data) || !is_array($data)) {
    $data = [];
}

$requestId = (int) ($data['request_id'] ?? $data['requestId'] ?? 0);
if ($requestId <= 0) {
    return [
        'success' => false,
        'error' => 'request_id is required and must be a positive integer.',
        'error_code' => 'E_REQUEST_ID',
        'data' => [],
    ];
}

$apiHost = rtrim((string) getenv('API_HOST'), '/');
$token = (string) getenv('API_TOKEN');
if ($apiHost === '' || $token === '') {
    return [
        'success' => false,
        'error' => 'API_HOST and API_TOKEN must be set on the script executor (development).',
        'error_code' => 'E_ENV',
        'data' => [],
    ];
}

$url = $apiHost . '/requests/' . $requestId . '/files?per_page=100';

try {
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 45]);
    $res = $client->request('GET', $url, [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ],
    ]);
    $code = $res->getStatusCode();
    $json = json_decode($res->getBody()->getContents(), true);
    if ($code === 404) {
        return [
            'success' => false,
            'error' => 'Request not found or you have no access (HTTP 404).',
            'error_code' => 'E_NOT_FOUND',
            'request_id' => $requestId,
            'data' => [],
        ];
    }
    if (!is_array($json)) {
        return ['success' => false, 'error' => 'Invalid API response', 'data' => []];
    }
    $rows = $json['data'] ?? [];
    $out = [];
    foreach ($rows as $f) {
        if (!is_array($f)) {
            continue;
        }
        $out[] = [
            'id' => $f['id'] ?? null,
            'name' => $f['name'] ?? $f['file_name'] ?? '',
            'mime_type' => $f['mime_type'] ?? '',
            'size' => $f['size'] ?? null,
            'created_at' => $f['created_at'] ?? '',
            'updated_at' => $f['updated_at'] ?? '',
            'location' => 'request:' . $requestId . ' / media',
        ];
    }
    return [
        'success' => true,
        'request_id' => $requestId,
        'file_count' => count($out),
        'data' => $out,
    ];
} catch (\Throwable $e) {
    $msg = $e->getMessage();
    $notFound = stripos($msg, '404') !== false;
    return [
        'success' => false,
        'error' => $notFound ? 'Request not found or inaccessible.' : $msg,
        'error_code' => $notFound ? 'E_NOT_FOUND' : 'E_HTTP',
        'request_id' => $requestId,
        'data' => [],
    ];
}
