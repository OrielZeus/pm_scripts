<?php
/**
 * DLSU — lightweight configuration check (no heavy SQL).
 * Register as PSTools with slug e.g. dlsu-config-status.
 * POST JSON body merges into $data (same keys as main sync / iframe).
 * Production host and token use the same resolution rules as DLSU_User_Group_Collection_Sync.php.
 */

if (!isset($data) || !is_array($data)) {
    $data = [];
}

/** Must match DLSU_User_Group_Collection_Sync.php */
const DLSU_HARDCODE_DEV_API_HOST = 'https://dlsu.cloud.processmaker.net/api/1.0';
const DLSU_HARDCODE_PROD_API_HOST = 'https://dlsu.cloud.processmaker.net/api/1.0';
const DLSU_HARDCODE_PROD_API_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxMCIsImp0aSI6IjI4ODc1MGNiOTA5YjNmYzg3MTMxZThmY2M4MjlkNzllZjIwMGQxMjNiODg2NmQ5YjMzMjQxOWM2NWY2YTJhMGFkMTM0YzQzMTcwZmU2ZWY4IiwiaWF0IjoxNzc2MTI4MjAwLjU2NzgyOCwibmJmIjoxNzc2MTI4MjAwLjU2NzgzLCJleHAiOjE4MDc2NjQyMDAuNTYzMDM2LCJzdWIiOiI5MTEiLCJzY29wZXMiOltdfQ.G6GbwtKrrBM0lGI1rVj2Scus3cdmUoVw-G4iCHNBK37RYX4bmido_tt6XybtCcHmkSIhfNAozwpDaXoQgrtuOIy_K0nFPWjndfYzB5k5pokEe5hmUKcLRWtquQm3OkctxYuNEp5Ca2FoQFgiTRjba6x7ZxHisZmtsgavKNo3ygmIUKFs2qFqilgFSf1b8t63bxv2gdKFB9d63ZS9Rjs3QP9zm06O6NAmFF5oHFLd72V30HpTufwnc-zJQI0apy7pYs-9KyqDOcCypKxFc1FHBTmmcSuVrycrKWtjRtg9zztgXZehi0a8dBuKzMuZrsdgouvXqWxq2-Ft3G2SWPmr8nK93Dz0Ex-eLKRkOfSlc6pBQc0u3aDUk4bdD9BDpp8XXr93QDm1dT4vWudSk6GnSCrCnFBC34AUzsYTzbr0HkbeeXY7oxcegkCkS0nsNXneOkO4-z4kIRUL069eNOjbVz-qjQIsVv-N9PgNKGDtbs1Q6QQCWfqlry1Wl_WSh2rNQYATm37auy5qKFQTrz6h7m574lz1v1GPvdT3H-JwnOKzaoCt6nHA54HEi1PEyELN7IjVF1AqnAPzvY8QEqWhvR3HfoYRO10PYy5uzbUUt3mGxlNGTzlGUxOBd0-cQ8At--J1vvF3dmpnywaYxHjW9nqsLAbAK0Hb7tOwT2a8rcc';

function dlsu_resolve_dev_host_cfg(array $data): string
{
    $chain = [
        $data['api_host_dev'] ?? null,
        $data['API_HOST'] ?? null,
    ];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['api_host_dev'] ?? null;
        $chain[] = $env['API_HOST'] ?? null;
    }
    foreach (['API_HOST', 'DLSU_DEV_API_HOST'] as $ev) {
        $g = getenv($ev);
        $chain[] = ($g !== false && $g !== '') ? $g : null;
    }
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $s = rtrim(trim((string) $v), '/');
        if ($s !== '') {
            return $s;
        }
    }
    return rtrim(DLSU_HARDCODE_DEV_API_HOST, '/');
}

function dlsu_resolve_dev_token_cfg(array $data): string
{
    $chain = [
        $data['api_token_dev'] ?? null,
        $data['api_token'] ?? null,
    ];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['api_token_dev'] ?? null;
        $chain[] = $env['API_TOKEN'] ?? null;
    }
    foreach (['API_TOKEN', 'DLSU_DEV_API_TOKEN'] as $ev) {
        $g = getenv($ev);
        $chain[] = ($g !== false && $g !== '') ? $g : null;
    }
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        return trim((string) $v);
    }
    return '';
}

function dlsu_cfg_pick(array $data, string $key, $envKey, $default = '')
{
    if (array_key_exists($key, $data) && $data[$key] !== '' && $data[$key] !== null) {
        return $data[$key];
    }
    $e = getenv($envKey);
    return ($e !== false && $e !== '') ? $e : $default;
}

function dlsu_resolve_prod_host_cfg(array $data): string
{
    $chain = [
        $data['api_host_prod'] ?? null,
        $data['production_api_host'] ?? null,
        $data['production_host'] ?? null,
        $data['PM_PROD_API_HOST'] ?? null,
    ];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['api_host_prod'] ?? null;
        $chain[] = $env['production_api_host'] ?? null;
        $chain[] = $env['PM_PROD_API_HOST'] ?? null;
        $chain[] = $env['DLSU_PRODUCTION_API_HOST'] ?? null;
    }
    foreach (['DLSU_PRODUCTION_API_HOST', 'PM_PROD_API_HOST', 'DLSU_PROD_API_HOST'] as $ev) {
        $g = getenv($ev);
        $chain[] = ($g !== false && $g !== '') ? $g : null;
    }
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $s = rtrim(trim((string) $v), '/');
        if ($s !== '') {
            return $s;
        }
    }
    return rtrim(DLSU_HARDCODE_PROD_API_HOST, '/');
}

function dlsu_resolve_prod_token_cfg(array $data): string
{
    $chain = [
        $data['api_token_prod'] ?? null,
        $data['production_api_token'] ?? null,
        $data['production_token'] ?? null,
        $data['pm_prod_api_token'] ?? null,
        $data['PM_PROD_API_TOKEN'] ?? null,
    ];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['api_token_prod'] ?? null;
        $chain[] = $env['production_api_token'] ?? null;
        $chain[] = $env['PM_PROD_API_TOKEN'] ?? null;
        $chain[] = $env['DLSU_PRODUCTION_API_TOKEN'] ?? null;
    }
    foreach (['DLSU_PRODUCTION_API_TOKEN', 'PM_PROD_API_TOKEN', 'DLSU_PROD_API_TOKEN'] as $ev) {
        $g = getenv($ev);
        $chain[] = ($g !== false && $g !== '') ? $g : null;
    }
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        return trim((string) $v);
    }
    return trim(DLSU_HARDCODE_PROD_API_TOKEN);
}

$devHost = dlsu_resolve_dev_host_cfg($data);
$devTok = dlsu_resolve_dev_token_cfg($data);
$prodHost = dlsu_resolve_prod_host_cfg($data);
$prodTok = dlsu_resolve_prod_token_cfg($data);
$sqlPath = trim((string) dlsu_cfg_pick($data, 'api_sql_path', 'API_SQL', '/admin/package-proservice-tools/sql'));

$issues = [];
if ($devHost === '') {
    $issues[] = ['code' => 'E_DEV_HOST', 'severity' => 'error', 'message' => 'Development API host is not configured (api_host_dev / API_HOST).'];
}
if ($devTok === '') {
    $issues[] = ['code' => 'E_DEV_TOKEN', 'severity' => 'error', 'message' => 'Development API token is not configured (api_token_dev / API_TOKEN).'];
}
if ($prodHost === '') {
    $issues[] = ['code' => 'E_PROD_HOST', 'severity' => 'error', 'message' => 'Production API host is not configured (api_host_prod, production_api_host, DLSU_PRODUCTION_API_HOST, PM_PROD_API_HOST, …).'];
}
if ($prodTok === '') {
    $issues[] = ['code' => 'E_PROD_TOKEN', 'severity' => 'error', 'message' => 'Production API token is not configured (api_token_prod, production_api_token, DLSU_PRODUCTION_API_TOKEN, PM_PROD_API_TOKEN, …).'];
}

$rid = $data['request_id'] ?? $data['_request']['id'] ?? null;
if ($rid === null || $rid === '' || (int) $rid <= 0) {
    $issues[] = ['code' => 'W_REQUEST_ID', 'severity' => 'warning', 'message' => 'Request ID is missing or invalid — attachment listing and some audits may be unavailable.'];
}

$mask = static function (string $t): string {
    $t = trim($t);
    if ($t === '') {
        return '';
    }
    if (strlen($t) <= 12) {
        return '****';
    }
    return substr($t, 0, 4) . '…' . substr($t, -4);
};

return [
    'success' => true,
    'configuration_ok' => empty(array_filter($issues, static function ($i) {
        return ($i['severity'] ?? '') === 'error';
    })),
    'issues' => $issues,
    'configuration_echo' => [
        'api_host_dev' => $devHost,
        'api_token_dev_masked' => $mask($devTok),
        'api_host_prod' => $prodHost,
        'api_token_prod_masked' => $mask($prodTok),
        'api_sql_path' => $sqlPath,
        'request_id' => $rid,
        'production_config_help' => 'Host: api_host_prod | production_api_host | production_host | PM_PROD_API_HOST | _env.* | DLSU_PRODUCTION_API_HOST. Token: api_token_prod | production_api_token | production_token | PM_PROD_API_TOKEN | DLSU_PRODUCTION_API_TOKEN.',
    ],
    'checked_at' => gmdate('c'),
];
