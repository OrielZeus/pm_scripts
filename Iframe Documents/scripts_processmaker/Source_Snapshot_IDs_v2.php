<?php
/**
 * Source Snapshot IDs v2
 *
 * Read-only snapshot from source API.
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
$maxPages = min(500, max(1, (int)($payload['max_pages'] ?? 500)));

$allowed = ['users', 'groups', 'group_members', 'environment_variables', 'collections'];
$entities = normalizeEntities($payload['entities'] ?? $allowed, $allowed);

if ($sourceHost === '' || $sourceToken === '') {
    return ['success' => false, 'error' => 'Missing source_api_host/source_api_token.'];
}

$out = ['success' => true, 'summary' => [], 'errors' => [], 'data' => []];
foreach ($entities as $entity) {
    $res = fetchEntityList($sourceHost, $sourceToken, '/' . $entity, $perPage, $maxPages);
    if (!$res['success']) {
        $out['errors'][$entity] = $res['error'];
        $out['summary'][$entity] = 0;
        continue;
    }
    $rows = [];
    foreach ($res['data'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($entity === 'users') {
            $rows[] = [
                'id' => (int)($row['id'] ?? 0),
                'username' => (string)($row['username'] ?? ''),
                'email' => (string)($row['email'] ?? ''),
                'firstname' => (string)($row['firstname'] ?? ''),
                'lastname' => (string)($row['lastname'] ?? ''),
            ];
        } elseif ($entity === 'groups') {
            $rows[] = [
                'id' => (int)($row['id'] ?? 0),
                'name' => (string)($row['name'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'status' => (string)($row['status'] ?? ''),
            ];
        } elseif ($entity === 'group_members') {
            $rows[] = [
                'id' => (int)($row['id'] ?? 0),
                'group_id' => (int)($row['group_id'] ?? 0),
                'member_id' => (int)($row['member_id'] ?? 0),
                'member_type' => (string)($row['member_type'] ?? ''),
            ];
        } else {
            $rows[] = [
                'id' => (int)($row['id'] ?? 0),
                'name' => (string)($row['name'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
            ];
        }
    }
    usort($rows, static function (array $a, array $b): int {
        return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
    });
    $out['data'][$entity] = $rows;
    $out['summary'][$entity] = count($rows);
}

return $out;

function normalizeEntities($entities, array $allowed): array
{
    if (!is_array($entities)) {
        return $allowed;
    }
    $set = [];
    foreach ($entities as $entity) {
        $key = trim((string)$entity);
        if ($key !== '' && in_array($key, $allowed, true)) {
            $set[$key] = true;
        }
    }
    return empty($set) ? $allowed : array_keys($set);
}

function fetchEntityList(string $host, string $token, string $path, int $perPage, int $maxPages): array
{
    $all = [];
    $page = 1;
    $lastPage = 1;
    do {
        $url = $host . $path . '?page=' . $page . '&per_page=' . $perPage;
        $res = pmApiCall($url, 'GET', null, $token);
        if (!$res['ok']) {
            return ['success' => false, 'error' => ['path' => $path, 'status' => $res['status'] ?? 0, 'raw' => $res['raw'] ?? null]];
        }
        $json = $res['json'];
        if (!is_array($json)) {
            return ['success' => false, 'error' => ['path' => $path, 'message' => 'Invalid response payload']];
        }
        $rows = $json['data'] ?? [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $all[] = $row;
                }
            }
        }
        $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
        $lastPage = (int)($meta['last_page'] ?? $lastPage);
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        $page++;
    } while ($page <= $lastPage && $page <= $maxPages);
    return ['success' => true, 'data' => $all];
}

function pmApiCall(string $url, string $method, $body, string $token): array
{
    $headers = ['Accept' => 'application/json', 'Authorization' => 'Bearer ' . $token];
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
        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'json' => is_array($json) ? $json : null, 'raw' => $raw];
    } catch (\Throwable $e) {
        return ['ok' => false, 'status' => 0, 'json' => null, 'raw' => null, 'error' => $e->getMessage()];
    }
}
