<?php
/**
 * Sony Genres — attach uploaded file (e.g. Excel) to a request
 *
 * Accepts base64 file body (same contract as typical upload PSTools) and POSTs multipart to
 * POST /api/1.0/requests/{request_id}/files
 *
 * JSON input:
 * {
 *   "request_id": 90235,
 *   "filename": "genres.xlsx",
 *   "mimetype": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
 *   "filedata": "<base64>",
 *   "data_name": "sony_genres_source_file"
 * }
 */

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
$filename = $payload['filename'] ?? 'upload.bin';
$mimetype = $payload['mimetype'] ?? 'application/octet-stream';
$filedata = $payload['filedata'] ?? '';
$dataName = $payload['data_name'] ?? 'sony_genres_source_file';

if ($apiHost === '') {
    return [
        'success' => false,
        'error' => 'API_HOST is not set.',
    ];
}

if ($filedata === '') {
    return [
        'success' => false,
        'error' => 'filedata (base64) is required.',
    ];
}

$binary = base64_decode($filedata, true);
if ($binary === false || strlen($binary) < 1) {
    return [
        'success' => false,
        'error' => 'Invalid base64 in filedata.',
    ];
}

$safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($filename));
$tmp = sys_get_temp_dir() . '/sgu_' . uniqid('', true) . '_' . $safeName;

try {
    file_put_contents($tmp, $binary);

    $url = $apiHost . '/requests/' . $requestId . '/files';
    $client = new \GuzzleHttp\Client(['verify' => false, 'http_errors' => false]);

    $res = $client->post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
        'multipart' => [
            [
                'name' => 'file',
                'contents' => fopen($tmp, 'rb'),
                'filename' => $safeName,
                'headers' => [
                    'Content-Type' => $mimetype,
                ],
            ],
            [
                'name' => 'data_name',
                'contents' => $dataName,
            ],
        ],
    ]);

    $code = $res->getStatusCode();
    $body = (string) $res->getBody();
    $json = json_decode($body, true);

    unlink($tmp);

    if ($code >= 200 && $code < 300) {
        return [
            'success' => true,
            'http_code' => $code,
            'response' => is_array($json) ? $json : ['raw' => $body],
            'request_id' => $requestId,
            'filename' => $safeName,
        ];
    }

    return [
        'success' => false,
        'http_code' => $code,
        'error' => is_array($json) ? ($json['message'] ?? $body) : $body,
    ];
} catch (Throwable $e) {
    if (isset($tmp) && is_file($tmp)) {
        @unlink($tmp);
    }
    return [
        'success' => false,
        'error' => $e->getMessage(),
    ];
}
