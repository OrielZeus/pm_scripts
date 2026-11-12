<?php
/**
 * Collections List Source IDs
 *
 * Lists collections from a source ProcessMaker API.
 * Input JSON ($data) example:
 * {
 *   "source_api_host": "https://source.example/api/1.0",
 *   "source_api_token": "token",
 *   "per_page": 100
 * }
 *
 * Fallback env vars:
 * - SOURCE_API_HOST
 * - SOURCE_API_TOKEN
 */

$payload = [];
if (!empty($data) && is_string($data)) {
    $payload = json_decode($data, true) ?: [];
} elseif (is_array($data)) {
    $payload = $data;
}

$sourceHost = rtrim((string)($payload['source_api_host'] ?? getenv('SOURCE_API_HOST') ?? getenv('API_HOST') ?? ''), '/');
$sourceToken = (string)($payload['source_api_token'] ?? getenv('SOURCE_API_TOKEN') ?? getenv('API_TOKEN') ?? '');
$perPage = min(1000, max(1, (int)($payload['per_page'] ?? 100)));
$maxPages = min(200, max(1, (int)($payload['max_pages'] ?? 200)));

if ($sourceHost === '') {
    return [
        'success' => false,
        'error' => 'Missing source_api_host (or SOURCE_API_HOST/API_HOST).',
        'data' => [],
    ];
}
if ($sourceToken === '') {
    return [
        'success' => false,
        'error' => 'Missing source_api_token (or SOURCE_API_TOKEN/API_TOKEN).',
        'data' => [],
    ];
}

try {
    $all = [];
    $page = 1;
    $lastPage = 1;

    do {
        $url = $sourceHost . '/collections?page=' . $page . '&per_page=' . $perPage;
        $res = pmApiCall($url, 'GET', null, $sourceToken);
        if (!$res['ok']) {
            return [
                'success' => false,
                'error' => 'Failed to fetch source collections',
                'detail' => $res,
                'data' => [],
            ];
        }

        $json = $res['json'];
        if (!is_array($json)) {
            return [
                'success' => false,
                'error' => 'Invalid source response payload',
                'data' => [],
            ];
        }

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

    return [
        'success' => true,
        'count' => count($all),
        'data' => $all,
    ];
} catch (Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
    ];
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
