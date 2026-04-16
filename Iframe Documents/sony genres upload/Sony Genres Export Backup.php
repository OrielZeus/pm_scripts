<?php
/**
 * Sony Genres — backup via collection_23 export
 *
 * 1) POST {API_HOST}/collections/23/export (async job, often HTTP 202)
 * 2) Web UI may produce download URLs like: .../collections/23/download/{hash}
 *
 * Uses callApiUrlGuzzle for the export POST. Binary download for optional request attachment
 * uses Guzzle directly because callApiUrlGuzzle JSON-decodes the body (not suitable for files).
 *
 * JSON input:
 * {
 *   "request_id": 123,
 *   "attach_to_request": true|false,
 *   "filename": "genres_backup_collection_23.xlsx"
 * }
 */

use GuzzleHttp\Client;

$collectionId = '23';
$apiHost = rtrim(getenv('API_HOST') ?: '', '/');
$token = getenv('API_TOKEN');

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$requestId = isset($payload['request_id']) ? (int) $payload['request_id'] : 90235;
$attach = !empty($payload['attach_to_request']);
$filename = $payload['filename'] ?? ('genres_backup_collection_' . $collectionId . '_' . date('Y-m-d_His') . '.xlsx');

if ($apiHost === '') {
    return [
        'success' => false,
        'error' => 'API_HOST is not set.',
    ];
}

$exportUrl = $apiHost . '/collections/' . $collectionId . '/export';

try {
    $json = callApiUrlGuzzle($exportUrl, 'POST', []);

    if (is_array($json) && isset($json['error_message'])) {
        return [
            'success' => false,
            'error' => $json['error_message'],
            'error_detail' => $json['error_detail'] ?? null,
        ];
    }

    // callApiUrlGuzzle does not expose HTTP status; treat presence of expected keys as success
    $downloadHint = null;
    if (is_array($json)) {
        foreach (['download_url', 'url', 'link', 'href'] as $k) {
            if (!empty($json[$k]) && is_string($json[$k])) {
                $downloadHint = $json[$k];
                break;
            }
        }
    }

    $out = [
        'success' => true,
        'export_response' => $json,
        'download_hint' => $downloadHint,
        'manual_download_pattern' => $apiHost . '/collections/' . $collectionId . '/download/{hash_from_export_job}',
        'note' => 'If the API returns no direct URL, use ProcessMaker Collections export UI or the async job docs.',
    ];

    // Optional: GET binary and attach to request (raw Guzzle — not JSON)
    if ($attach && $requestId > 0 && isset($api) && $downloadHint && strpos($downloadHint, 'http') === 0) {
        $client = new Client(['verify' => false, 'http_errors' => false]);
        $get = $client->get($downloadHint, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
        $bin = (string) $get->getBody();
        if ($get->getStatusCode() === 200 && strlen($bin) > 0) {
            $tmp = sys_get_temp_dir() . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
            file_put_contents($tmp, $bin);
            try {
                $apiInstance = $api->requestFiles();
                $newFile = $apiInstance->createRequestFile($requestId, 'genres_backup_' . $requestId . '_' . basename($filename), $tmp);
                unlink($tmp);
                $out['attached_file_id'] = $newFile->getFileUploadId();
                $out['attached'] = true;
            } catch (Throwable $e) {
                $out['attach_error'] = $e->getMessage();
                if (is_file($tmp)) {
                    unlink($tmp);
                }
            }
        }
    }

    return $out;
} catch (Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
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
