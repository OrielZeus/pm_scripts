<?php
/**
 * Generic rollback by request ids using ProcessMaker API.
 * Includes local callApiUrlGuzzle() helper and returns structured data.
 */

$apiHost = rtrim((string) getenv('API_HOST'), '/');

$inputRequestIds = $data['request_ids'] ?? [];
$performRollback = filter_var($data['perform_rollback'] ?? true, FILTER_VALIDATE_BOOLEAN);
$includeAllTasks = filter_var($data['all_tasks'] ?? false, FILTER_VALIDATE_BOOLEAN);

$requestIds = normalizeRequestIds($inputRequestIds);

if (empty($requestIds)) {
    return [
        'status' => false,
        'message' => 'No valid request ids were provided.',
        'request_ids' => [],
        'results' => [],
    ];
}

$results = [];
$rolledBackCount = 0;
$eligibleCount = 0;
$errorsCount = 0;

foreach ($requestIds as $requestId) {
    $taskResponse = getActiveTaskByRequestId($apiHost, $requestId, $includeAllTasks);

    if (!empty($taskResponse['error_message'])) {
        $errorsCount++;
        $results[] = [
            'request_id' => $requestId,
            'status' => 'error',
            'message' => 'Failed to fetch active task.',
            'detail' => $taskResponse,
        ];
        continue;
    }

    $task = $taskResponse['data'][0] ?? null;
    if (!is_array($task) || empty($task['id'])) {
        $results[] = [
            'request_id' => $requestId,
            'status' => 'skipped',
            'message' => 'No active task found for request.',
        ];
        continue;
    }

    $taskId = (int) $task['id'];
    $eligibleResponse = callApiUrlGuzzle($apiHost . '/tasks/' . $taskId . '/eligibleRollbackTask', 'GET');

    if (!empty($eligibleResponse['error_message'])) {
        $errorsCount++;
        $results[] = [
            'request_id' => $requestId,
            'task_id' => $taskId,
            'status' => 'error',
            'message' => 'Failed to validate eligible rollback task.',
            'detail' => $eligibleResponse,
        ];
        continue;
    }

    if (!empty($eligibleResponse['message'])) {
        $results[] = [
            'request_id' => $requestId,
            'task_id' => $taskId,
            'status' => 'not_eligible',
            'message' => (string) $eligibleResponse['message'],
        ];
        continue;
    }

    $eligibleCount++;

    if ($performRollback === false) {
        $results[] = [
            'request_id' => $requestId,
            'task_id' => $taskId,
            'status' => 'eligible',
            'message' => 'Eligible for rollback. Dry-run mode enabled, rollback not executed.',
            'eligible_task' => $eligibleResponse,
        ];
        continue;
    }

    $rollbackResponse = callApiUrlGuzzle($apiHost . '/tasks/' . $taskId . '/rollback', 'POST');
    if (!empty($rollbackResponse['error_message'])) {
        $errorsCount++;
        $results[] = [
            'request_id' => $requestId,
            'task_id' => $taskId,
            'status' => 'error',
            'message' => 'Rollback failed.',
            'detail' => $rollbackResponse,
        ];
        continue;
    }

    $rolledBackCount++;
    $results[] = [
        'request_id' => $requestId,
        'task_id' => $taskId,
        'status' => 'rolled_back',
        'message' => 'Rollback executed successfully.',
        'rollback_result' => $rollbackResponse,
    ];
}

return [
    'status' => $errorsCount === 0,
    'message' => 'Rollback processing completed.',
    'perform_rollback' => $performRollback,
    'total_requests' => count($requestIds),
    'eligible_count' => $eligibleCount,
    'rolled_back_count' => $rolledBackCount,
    'errors_count' => $errorsCount,
    'request_ids' => $requestIds,
    'results' => $results,
];

function getActiveTaskByRequestId(string $apiHost, int $requestId, bool $includeAllTasks = false): array
{
    $query = [
        'process_request_id' => $requestId,
        'status' => 'ACTIVE',
        'order_by' => 'id',
        'order_direction' => 'desc',
        'per_page' => 1,
    ];

    if ($includeAllTasks) {
        $query['all_tasks'] = 'true';
    }

    $url = $apiHost . '/tasks?' . http_build_query($query);

    $response = callApiUrlGuzzle($url, 'GET');
    return is_array($response) ? $response : [];
}

function normalizeRequestIds($requestIdsInput): array
{
    if (is_int($requestIdsInput)) {
        return [$requestIdsInput];
    }

    if (is_string($requestIdsInput)) {
        $trimmed = trim($requestIdsInput);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return sanitizeRequestIds($decoded);
        }

        return sanitizeRequestIds(explode(',', $trimmed));
    }

    if (is_array($requestIdsInput)) {
        return sanitizeRequestIds($requestIdsInput);
    }

    return [];
}

function sanitizeRequestIds(array $rawValues): array
{
    $normalized = [];
    foreach ($rawValues as $value) {
        if ($value === null || $value === '') {
            continue;
        }

        $id = (int) trim((string) $value);
        if ($id > 0) {
            $normalized[$id] = $id;
        }
    }

    return array_values($normalized);
}

function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }

    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken,
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
            'error_response' => method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse() : '',
        ];
    }

    return $res;
}
