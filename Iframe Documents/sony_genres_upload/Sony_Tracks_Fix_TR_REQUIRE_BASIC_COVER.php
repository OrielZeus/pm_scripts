<?php
/**
 * Sony Music — tracks collection: set data.TR_REQUIRE_BASIC_COVER from JSON null to the string "null".
 *
 * Uses GET /collections/{id}/records with PMQL (paginated), then for each candidate GET record by id,
 * updates only when the field exists and its value is strictly null; PUT preserves all other data fields.
 *
 * Environment: API_HOST (must include /api/1.0), API_TOKEN.
 *
 * Optional JSON input ($data):
 *   - collection_id      (default "18")
 *   - pmql               (default: data.TR_REQUIRE_BASIC_COVER = "null" — same as your Sony query; override if needed for real JSON null, e.g. try PMQL docs for "is null")
 *   - per_page           (default 100, max 1000)
 *   - order_by           (default "id")
 *   - order_direction    (default "desc")
 *   - dry_run            (default false) — if true, only reports would_update / skipped
 *   - field_name         (default "TR_REQUIRE_BASIC_COVER")
 *   - replacement        (default "null") — literal string stored in the collection
 *   - max_pages          (default 500) — safety cap when paginating
 */

$apiHost = rtrim(getenv('API_HOST') ?: '', '/');

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$collectionId = isset($payload['collection_id']) ? (string) $payload['collection_id'] : '18';
$pmql = isset($payload['pmql']) ? (string) $payload['pmql'] : 'data.TR_REQUIRE_BASIC_COVER = "null"';
$perPage = isset($payload['per_page']) ? min(1000, max(1, (int) $payload['per_page'])) : 100;
$orderBy = isset($payload['order_by']) ? (string) $payload['order_by'] : 'id';
$orderDir = isset($payload['order_direction']) ? (string) $payload['order_direction'] : 'desc';
$dryRun = !empty($payload['dry_run']);
$fieldName = isset($payload['field_name']) ? (string) $payload['field_name'] : 'TR_REQUIRE_BASIC_COVER';
$replacement = array_key_exists('replacement', $payload) ? (string) $payload['replacement'] : 'null';
$maxPages = isset($payload['max_pages']) ? max(1, (int) $payload['max_pages']) : 500;

$isHelperError = static function ($res): bool {
    return is_array($res) && isset($res['error_message']);
};

if ($apiHost === '') {
    return [
        'success' => false,
        'error' => 'API_HOST is not set in the script environment.',
    ];
}

if (!getenv('API_TOKEN')) {
    return [
        'success' => false,
        'error' => 'API_TOKEN is not set in the script environment.',
    ];
}

$updated = [];
$skipped = [];
$errors = [];
$page = 1;
$totalPages = 1;

try {
    do {
        $query = http_build_query([
            'page' => $page,
            'per_page' => $perPage,
            'filter' => '',
            'pmql' => $pmql,
            'order_by' => $orderBy,
            'order_direction' => $orderDir,
        ], '', '&', PHP_QUERY_RFC3986);

        $listUrl = $apiHost . '/collections/' . rawurlencode($collectionId) . '/records?' . $query;
        $listJson = callApiUrlGuzzle($listUrl, 'GET', []);

        if ($isHelperError($listJson)) {
            return [
                'success' => false,
                'error' => $listJson['error_message'] ?? 'List request failed',
                'detail' => $listJson,
            ];
        }

        $rows = $listJson['data'] ?? [];
        if (!is_array($rows)) {
            return [
                'success' => false,
                'error' => 'Invalid list response: missing data array',
            ];
        }

        $meta = $listJson['meta'] ?? [];
        $lastPage = (int) ($meta['last_page'] ?? 0);
        if ($lastPage < 1) {
            $total = (int) ($meta['total'] ?? count($rows));
            $lastPage = $total > 0 ? (int) max(1, ceil($total / max(1, $perPage))) : 1;
        }
        $totalPages = $lastPage;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $recordId = $row['id'] ?? null;
            if ($recordId === null || $recordId === '') {
                continue;
            }

            $oneUrl = $apiHost . '/collections/' . rawurlencode($collectionId) . '/records/' . rawurlencode((string) $recordId);
            $one = callApiUrlGuzzle($oneUrl, 'GET', []);

            if ($isHelperError($one)) {
                $errors[] = [
                    'record_id' => $recordId,
                    'phase' => 'GET',
                    'message' => $one['error_message'] ?? 'GET failed',
                ];
                continue;
            }

            $recordData = $one['data'] ?? null;
            if (!is_array($recordData)) {
                $errors[] = [
                    'record_id' => $recordId,
                    'phase' => 'GET',
                    'message' => 'Record has no data object',
                ];
                continue;
            }

            if (!array_key_exists($fieldName, $recordData) || $recordData[$fieldName] !== null) {
                $skipped[] = [
                    'record_id' => $recordId,
                    'reason' => 'field missing or not JSON null',
                ];
                continue;
            }

            if ($dryRun) {
                $updated[] = [
                    'record_id' => $recordId,
                    'dry_run' => true,
                    'would_set' => $replacement,
                ];
                continue;
            }

            $recordData[$fieldName] = $replacement;
            $put = callApiUrlGuzzle($oneUrl, 'PUT', ['data' => $recordData]);

            if ($isHelperError($put)) {
                $errors[] = [
                    'record_id' => $recordId,
                    'phase' => 'PUT',
                    'message' => $put['error_message'] ?? 'PUT failed',
                ];
                continue;
            }

            $updated[] = [
                'record_id' => $recordId,
                'field' => $fieldName,
                'value' => $replacement,
            ];
        }

        $page++;
    } while ($page <= $totalPages && $page <= $maxPages);

    return [
        'success' => count($errors) === 0,
        'dry_run' => $dryRun,
        'collection_id' => $collectionId,
        'pmql' => $pmql,
        'field' => $fieldName,
        'replacement' => $replacement,
        'updated_count' => count($updated),
        'skipped_count' => count($skipped),
        'error_count' => count($errors),
        'updated' => $updated,
        'skipped_sample' => array_slice($skipped, 0, 50),
        'errors' => $errors,
        'pages_processed' => min($page - 1, $maxPages),
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
    $acceptType = $contentFile ? "'application/octet-stream'" : 'application/json';
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
