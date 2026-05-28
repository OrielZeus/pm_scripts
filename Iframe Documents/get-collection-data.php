<?php
/**
 * Generic script: SELECT data FROM collection_...
 *
 * Input optional ($data):
 * - collection_id (example: 25)
 * - collection (example: collection_25)
 *
 * Fallback:
 * - COLLECTION_ID env var
 * - default: 3
 */

$payload = [];
$inputData = isset($data) ? $data : null;
if (!empty($inputData) && is_string($inputData)) {
    $payload = json_decode($inputData, true) ?: [];
} elseif (is_array($inputData)) {
    $payload = $inputData;
}

$collectionNameInput = trim((string)($payload['collection'] ?? ''));
$collectionId = (int)($payload['collection_id'] ?? getenv('COLLECTION_ID') ?: 3);
if ($collectionId < 1) {
    $collectionId = 3;
}
$collection = $collectionNameInput !== '' ? $collectionNameInput : ('collection_' . $collectionId);

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql = (string)(getenv('API_SQL') ?: '/admin/package-proservice-tools/sql');
$apiUrl = $apiHost . $apiSql;

if ($apiHost === '' || $apiSql === '') {
    return [];
}

$sql = "SELECT data FROM {$collection}";

$response = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($sql));
if (is_array($response) && isset($response['error_message'])) {
    return [];
}

$rows = [];
if (is_array($response)) {
    if (isset($response['data']) && is_array($response['data'])) {
        $rows = $response['data'];
    } elseif (isset($response[0]) && is_array($response[0])) {
        // Some SQL endpoints return the rows array directly.
        $rows = $response;
    }
}
$objects = [];

foreach ($rows as $row) {
    $rawData = is_array($row) ? ($row['data'] ?? null) : null;

    if (is_string($rawData)) {
        $decoded = json_decode($rawData);
        $objects[] = (json_last_error() === JSON_ERROR_NONE && is_object($decoded))
            ? $decoded
            : (object)['value' => $rawData];
        continue;
    }

    if (is_array($rawData)) {
        $objects[] = (object)$rawData;
        continue;
    }

    if (is_object($rawData)) {
        $objects[] = $rawData;
        continue;
    }

    $objects[] = (object)[];
}

return $objects;

/**
 * Embedded helpers (same logic as function helpers.php)
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken
    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $clientClass = '\\GuzzleHttp\\Client';
    $requestClass = '\\GuzzleHttp\\Psr7\\Request';
    $client = new $clientClass([
        'verify' => false,
    ]);
    $request = new $requestClass($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
    } catch (\Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => method_exists($e, 'getResponse') ? $e->getResponse() : ''
        ];
    }
    return $res;
}

function encodeSql($query)
{
    return ["SQL" => base64_encode($query)];
}
