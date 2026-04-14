<?php
/**
 * DLSU — Development vs production alignment (users, groups, optional collections).
 *
 * Layout of this file (read top to bottom):
 *   1) Configuration — request id, user id, environments, tokens (from $data and/or getenv).
 *   2) Main logic — orchestration only; helpers are at the bottom.
 *   3) Functions — all reusable helpers with English PHPDoc comments.
 *
 * Deploying this script:
 *   - Paste into a ProcessMaker script task on the DEVELOPMENT server.
 *   - Set environment variables OR pass the same keys inside request $data (production: api_host_prod / production_api_host / DLSU_PRODUCTION_API_HOST / PM_PROD_API_HOST, etc.).
 *   - For shared helpers: either keep everything in this single file (recommended) or copy the function block
 *     into a PM Script Library / include if your instance supports it; then redeploy when helpers change.
 *
 * Backups / purify:
 *   - When save_backup_in_return is true and action is apply, the response includes backup_before_apply with
 *     dev snapshots (users + group rows) taken BEFORE mutations. Store that object on the request (e.g. data.dlsu_backup)
 *     so you can audit or manually restore. To “purify”, remove dlsu_backup from request data when no longer needed.
 */

/*
|--------------------------------------------------------------------------
| PCR — Process change record (update when you edit this script)
|--------------------------------------------------------------------------
| 1. Who / when:  _________________________________
| 2. What changed: _________________________________
| 3. Risk / test: _________________________________
| 4. Rollback:     Restore previous script version; replay backup_before_apply if needed.
|--------------------------------------------------------------------------
*/

/**
 * DLSU tenant defaults (lowest priority: used only when $data and getenv() do not supply a value).
 * UI entry point: https://dlsu.cloud.processmaker.net/requests — API calls use /api/1.0 base below.
 * Rotate tokens in ProcessMaker if this file is ever exposed; prefer env vars in production.
 */
const DLSU_HARDCODE_DEV_API_HOST = 'https://dlsu.cloud.processmaker.net/api/1.0';
const DLSU_HARDCODE_PROD_API_HOST = 'https://dlsu.cloud.processmaker.net/api/1.0';
const DLSU_HARDCODE_PROD_API_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxMCIsImp0aSI6IjI4ODc1MGNiOTA5YjNmYzg3MTMxZThmY2M4MjlkNzllZjIwMGQxMjNiODg2NmQ5YjMzMjQxOWM2NWY2YTJhMGFkMTM0YzQzMTcwZmU2ZWY4IiwiaWF0IjoxNzc2MTI4MjAwLjU2NzgyOCwibmJmIjoxNzc2MTI4MjAwLjU2NzgzLCJleHAiOjE4MDc2NjQyMDAuNTYzMDM2LCJzdWIiOiI5MTEiLCJzY29wZXMiOltdfQ.G6GbwtKrrBM0lGI1rVj2Scus3cdmUoVw-G4iCHNBK37RYX4bmido_tt6XybtCcHmkSIhfNAozwpDaXoQgrtuOIy_K0nFPWjndfYzB5k5pokEe5hmUKcLRWtquQm3OkctxYuNEp5Ca2FoQFgiTRjba6x7ZxHisZmtsgavKNo3ygmIUKFs2qFqilgFSf1b8t63bxv2gdKFB9d63ZS9Rjs3QP9zm06O6NAmFF5oHFLd72V30HpTufwnc-zJQI0apy7pYs-9KyqDOcCypKxFc1FHBTmmcSuVrycrKWtjRtg9zztgXZehi0a8dBuKzMuZrsdgouvXqWxq2-Ft3G2SWPmr8nK93Dz0Ex-eLKRkOfSlc6pBQc0u3aDUk4bdD9BDpp8XXr93QDm1dT4vWudSk6GnSCrCnFBC34AUzsYTzbr0HkbeeXY7oxcegkCkS0nsNXneOkO4-z4kIRUL069eNOjbVz-qjQIsVv-N9PgNKGDtbs1Q6QQCWfqlry1Wl_WSh2rNQYATm37auy5qKFQTrz6h7m574lz1v1GPvdT3H-JwnOKzaoCt6nHA54HEi1PEyELN7IjVF1AqnAPzvY8QEqWhvR3HfoYRO10PYy5uzbUUt3mGxlNGTzlGUxOBd0-cQ8At--J1vvF3dmpnywaYxHjW9nqsLAbAK0Hb7tOwT2a8rcc';

/**
 * Resolve development API base URL (must include /api/1.0). Request / env first, then DLSU_HARDCODE_DEV_API_HOST.
 */
function dlsu_resolve_dev_host(array $data): string
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

/**
 * Resolve development bearer token (script executor). No hardcoded secret — PM usually injects API_TOKEN.
 */
function dlsu_resolve_dev_token(array $data): string
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

/**
 * Resolve production API base URL (must include /api/1.0). First non-empty wins.
 * Configurable via $data keys: api_host_prod, production_api_host, production_host, PM_PROD_API_HOST;
 * $data['_env'] keys: same; getenv: DLSU_PRODUCTION_API_HOST, PM_PROD_API_HOST, DLSU_PROD_API_HOST.
 */
function dlsu_resolve_prod_host(array $data): string
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

/**
 * Resolve production bearer token. First non-empty wins.
 * Configurable via $data: api_token_prod, production_api_token, production_token, pm_prod_api_token, PM_PROD_API_TOKEN;
 * $data['_env']: same; getenv: DLSU_PRODUCTION_API_TOKEN, PM_PROD_API_TOKEN, DLSU_PROD_API_TOKEN.
 */
function dlsu_resolve_prod_token(array $data): string
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

// -----------------------------------------------------------------------------
// SECTION 1 — CONFIGURATION (request + environment)
// All values can be supplied via $data (request) or getenv(). Request wins over env.
// -----------------------------------------------------------------------------

if (!isset($data) || !is_array($data)) {
    $data = [];
}

/**
 * Request id for logging / attachments / audit; defaults to 1 when not in a request context.
 */
function dlsu_resolve_request_id(array $data): int
{
    $r = $data['request_id'] ?? $data['requestId'] ?? $data['_request']['id'] ?? getenv('DLSU_REQUEST_ID');
    if ($r === null || $r === '') {
        return 1;
    }
    $n = (int) $r;
    return $n >= 1 ? $n : 1;
}

$dlsuCfg = [
    'request_id' => dlsu_resolve_request_id($data),
    'user_id' => $data['user_id'] ?? $data['_request']['user_id'] ?? getenv('DLSU_USER_ID') ?: null,

    'environment_dev_label'  => trim((string) ($data['environment_dev_label'] ?? getenv('DLSU_ENV_DEV_LABEL') ?: 'development')),
    'environment_prod_label' => trim((string) ($data['environment_prod_label'] ?? getenv('DLSU_ENV_PROD_LABEL') ?: 'production')),

    'api_host_dev'  => dlsu_resolve_dev_host($data),
    'api_token_dev' => dlsu_resolve_dev_token($data),
    'api_sql_path'  => trim((string) ($data['api_sql_path'] ?? getenv('API_SQL') ?: '/admin/package-proservice-tools/sql')),

    'api_host_prod'  => dlsu_resolve_prod_host($data),
    'api_token_prod' => dlsu_resolve_prod_token($data),

    'new_user_password_for_apply' => (string) ($data['new_user_password_for_apply'] ?? $data['DLSU_NEW_USER_PASSWORD'] ?? getenv('DLSU_NEW_USER_PASSWORD') ?: ''),

    'collection_id_prod' => trim((string) ($data['collection_id_prod'] ?? $data['DLSU_COLLECTION_ID_PROD'] ?? getenv('DLSU_COLLECTION_ID_PROD') ?: '')),
    'collection_id_dev'  => trim((string) ($data['collection_id_dev'] ?? $data['DLSU_COLLECTION_ID_DEV'] ?? getenv('DLSU_COLLECTION_ID_DEV') ?: '')),
    'collection_id_prod_2' => trim((string) ($data['collection_id_prod_2'] ?? $data['DLSU_COLLECTION_ID_PROD_2'] ?? getenv('DLSU_COLLECTION_ID_PROD_2') ?: '')),
    'collection_id_dev_2'  => trim((string) ($data['collection_id_dev_2'] ?? $data['DLSU_COLLECTION_ID_DEV_2'] ?? getenv('DLSU_COLLECTION_ID_DEV_2') ?: '')),
    'collection_match_key' => trim((string) ($data['collection_match_key'] ?? getenv('DLSU_COLLECTION_MATCH_KEY') ?: 'email')),
    'audit_collection_id' => trim((string) ($data['audit_collection_id'] ?? $data['DLSU_AUDIT_COLLECTION_ID'] ?? getenv('DLSU_AUDIT_COLLECTION_ID') ?: '')),

    'include_admins' => (static function () use ($data) {
        if (array_key_exists('include_admins', $data)) {
            return filter_var($data['include_admins'], FILTER_VALIDATE_BOOLEAN);
        }
        return getenv('DLSU_INCLUDE_ADMINS') === '1' || getenv('DLSU_INCLUDE_ADMINS') === 'true';
    })(),

    'action' => strtolower(trim((string) ($data['action'] ?? 'report'))),
    'scope'  => strtolower(trim((string) ($data['scope'] ?? 'all'))),

    'save_backup_in_return' => filter_var($data['save_backup_in_return'] ?? true, FILTER_VALIDATE_BOOLEAN),
];

$action = $dlsuCfg['action'];
$scope  = $dlsuCfg['scope'];
$apply  = ($action === 'apply');

$devHost = $dlsuCfg['api_host_dev'];
$devTok  = $dlsuCfg['api_token_dev'];
$sqlPath = $dlsuCfg['api_sql_path'];
$devSql  = $devHost . $sqlPath;

$prodHost = $dlsuCfg['api_host_prod'];
$prodTok  = $dlsuCfg['api_token_prod'];
$prodSql  = $prodHost . $sqlPath;

$memberTypeSql = 'ProcessMaker\\\\Models\\\\User';

$newUserPassword = $dlsuCfg['new_user_password_for_apply'];
$includeAdmins   = $dlsuCfg['include_admins'];

$colProd = $dlsuCfg['collection_id_prod'];
$colDev  = $dlsuCfg['collection_id_dev'];
$colProd2 = $dlsuCfg['collection_id_prod_2'];
$colDev2 = $dlsuCfg['collection_id_dev_2'];
$colKey  = $dlsuCfg['collection_match_key'];
$auditCollectionId = $dlsuCfg['audit_collection_id'];

if ($devHost === '' || $devTok === '') {
    return dlsu_error_response($dlsuCfg, 'API host/token for development are required (api_host_dev / api_token_dev or API_HOST / API_TOKEN).');
}
if ($prodHost === '' || $prodTok === '') {
    return dlsu_error_response(
        $dlsuCfg,
        'Production API host and token are required. Set via request data (api_host_prod, production_api_host, api_token_prod, production_api_token, …) or environment (DLSU_PRODUCTION_API_HOST, DLSU_PRODUCTION_API_TOKEN, PM_PROD_API_HOST, PM_PROD_API_TOKEN).'
    );
}

$canUseSdkApi = isset($api);

if ($scope === 'collections') {
    $hasPair1 = $colProd !== '' && $colDev !== '';
    $hasPair2 = $colProd2 !== '' && $colDev2 !== '';
    if (!$hasPair1 && !$hasPair2) {
        return dlsu_error_response($dlsuCfg, 'For scope=collections set at least one pair: collection_id_prod + collection_id_dev and/or collection_id_prod_2 + collection_id_dev_2.');
    }
}

// -----------------------------------------------------------------------------
// SECTION 2 — MAIN LOGIC
// -----------------------------------------------------------------------------

if ($scope === 'collections') {
    $hasPair1 = $colProd !== '' && $colDev !== '';
    $hasPair2 = $colProd2 !== '' && $colDev2 !== '';
    $collectionReport = null;
    $collectionReport2 = null;
    if ($hasPair1) {
        $collectionReport = dlsu_compare_collections(
            $prodHost,
            $prodTok,
            $devHost,
            $devTok,
            $colProd,
            $colDev,
            $colKey,
            $apply
        );
    }
    if ($hasPair2) {
        $collectionReport2 = dlsu_compare_collections(
            $prodHost,
            $prodTok,
            $devHost,
            $devTok,
            $colProd2,
            $colDev2,
            $colKey,
            $apply
        );
    }
    $err = null;
    if ($collectionReport !== null && isset($collectionReport['error'])) {
        $err = (string) $collectionReport['error'];
    }
    if ($err === null && $collectionReport2 !== null && isset($collectionReport2['error'])) {
        $err = (string) $collectionReport2['error'];
    }
    $rowsProd = $collectionReport !== null ? ($collectionReport['rows_prod_full'] ?? []) : [];
    $rowsDev = $collectionReport !== null ? ($collectionReport['rows_dev_full'] ?? []) : [];
    $collBackup = null;
    if ($apply && $dlsuCfg['save_backup_in_return']) {
        $snap = [];
        if ($collectionReport !== null) {
            $snap['pair_1_dev_rows'] = $collectionReport['rows_dev_full'] ?? [];
        }
        if ($collectionReport2 !== null) {
            $snap['pair_2_dev_rows'] = $collectionReport2['rows_dev_full'] ?? [];
        }
        $collBackup = [
            'saved_at' => gmdate('c'),
            'request_id' => $dlsuCfg['request_id'],
            'user_id' => $dlsuCfg['user_id'],
            'environment_dev' => $dlsuCfg['environment_dev_label'],
            'snapshot_dev_collection_by_pair' => $snap,
            'configuration_snapshot' => [
                'collection_id_prod' => $colProd,
                'collection_id_dev' => $colDev,
                'collection_id_prod_2' => $colProd2,
                'collection_id_dev_2' => $colDev2,
                'audit_collection_id' => $auditCollectionId,
            ],
        ];
    }
    $pr1 = $collectionReport !== null ? (int) ($collectionReport['prod_row_count'] ?? 0) : 0;
    $dr1 = $collectionReport !== null ? (int) ($collectionReport['dev_row_count'] ?? 0) : 0;
    $m1 = $collectionReport !== null ? (int) ($collectionReport['missing_on_dev'] ?? 0) : 0;
    if (!$hasPair1) {
        $pr1 = $dr1 = $m1 = 0;
    }
    $pr2 = $collectionReport2 !== null ? (int) ($collectionReport2['prod_row_count'] ?? 0) : 0;
    $dr2 = $collectionReport2 !== null ? (int) ($collectionReport2['dev_row_count'] ?? 0) : 0;
    $m2 = $collectionReport2 !== null ? (int) ($collectionReport2['missing_on_dev'] ?? 0) : 0;
    if (!$hasPair2) {
        $pr2 = $dr2 = $m2 = 0;
    }
    $payloadColl = [
        'scope' => 'collections',
        'scope_note' => 'Collections-only run: user/group SQL was skipped.',
        'rows_return_users_from_production' => [],
        'rows_return_users_from_development' => [],
        'rows_return_groups_prod_membership' => [],
        'rows_return_groups_dev_membership' => [],
        'rows_return_groups_dev_catalog' => [],
        'rows_return_collection_prod_records' => $rowsProd,
        'rows_return_collection_dev_records' => $rowsDev,
        'rows_return_collection_prod_records_pair_2' => $collectionReport2 !== null ? ($collectionReport2['rows_prod_full'] ?? []) : [],
        'rows_return_collection_dev_records_pair_2' => $collectionReport2 !== null ? ($collectionReport2['rows_dev_full'] ?? []) : [],
        'summary_counts' => [
            'prod_users' => 0,
            'dev_users' => 0,
            'missing_users_on_dev' => 0,
            'group_membership_diffs' => 0,
            'groups_missing_on_dev_catalog' => 0,
            'prod_collection_rows' => $pr1,
            'dev_collection_rows' => $dr1,
            'collection_missing_on_dev' => $m1,
            'prod_collection_rows_pair_2' => $pr2,
            'dev_collection_rows_pair_2' => $dr2,
            'collection_missing_on_dev_pair_2' => $m2,
        ],
        'users_missing_on_dev' => [],
        'group_membership_diffs' => [],
        'groups_missing_on_dev_catalog' => [],
        'new_users_created' => [],
        'group_membership_updates_applied' => [],
        'new_groups_created' => [],
        'collections' => $collectionReport,
        'collections_pair_2' => $collectionReport2,
        'backup_before_apply' => $collBackup,
        'purify_note' => 'Merge backup_before_apply into request data as dlsu_backup if you want it stored on the case; remove that key later to purify.',
    ];
    $payloadColl = dlsu_payload_with_audit_row($dlsuCfg, $devHost, $devTok, $payloadColl, $action, $scope, $err === null);
    return dlsu_success_response(
        $dlsuCfg,
        $payloadColl,
        $apply ? [
            'collections_posted_pair_1' => $collectionReport !== null ? ($collectionReport['posted_in_apply'] ?? []) : [],
            'collections_posted_pair_2' => $collectionReport2 !== null ? ($collectionReport2['posted_in_apply'] ?? []) : [],
        ] : null,
        $err
    );
}

$userSql = "
SELECT id, username, email, firstname, lastname, status,
       COALESCE(is_administrator, 0) AS is_administrator
FROM users
WHERE deleted_at IS NULL
ORDER BY id
";

$prodUsersRaw = dlsu_call_sql($prodSql, $prodTok, $userSql);
if (isset($prodUsersRaw['__error__'])) {
    return dlsu_error_response($dlsuCfg, 'Production SQL failed: ' . ($prodUsersRaw['__error__'] ?? ''));
}
if (!is_array($prodUsersRaw) || isset($prodUsersRaw['error_message'])) {
    return dlsu_error_response($dlsuCfg, 'Unexpected production SQL response', $prodUsersRaw);
}

$devUsersRaw = dlsu_call_sql($devSql, $devTok, $userSql);
if (isset($devUsersRaw['__error__'])) {
    return dlsu_error_response($dlsuCfg, 'Development SQL failed: ' . ($devUsersRaw['__error__'] ?? ''));
}

if (!$includeAdmins) {
    $prodUsersRaw = array_values(array_filter($prodUsersRaw, static function ($r) {
        return empty($r['is_administrator']);
    }));
}

$rowsReturnUsersFromProduction = array_values($prodUsersRaw);
$rowsReturnUsersFromDevelopment = array_values($devUsersRaw);

$devByUser  = dlsu_index_by_lower_username($devUsersRaw);
$prodByUser = dlsu_index_by_lower_username($prodUsersRaw);

$missingOnDev = [];
foreach ($prodByUser as $lu => $p) {
    if (!isset($devByUser[$lu])) {
        $missingOnDev[] = $p;
    }
}

$groupSql = "
SELECT u.username, g.name AS group_name
FROM users u
INNER JOIN group_members gm
  ON gm.member_id = u.id AND gm.member_type = '{$memberTypeSql}'
INNER JOIN groups g ON g.id = gm.group_id
WHERE u.deleted_at IS NULL
";

$prodGm = dlsu_call_sql($prodSql, $prodTok, $groupSql);
$devGm  = dlsu_call_sql($devSql, $devTok, $groupSql);

$rowsReturnGroupsProdMembership = is_array($prodGm) ? array_values($prodGm) : [];
$rowsReturnGroupsDevMembership  = is_array($devGm) ? array_values($devGm) : [];

$prodGroupsByUser = dlsu_group_names_by_username($rowsReturnGroupsProdMembership);
$devGroupsByUser  = dlsu_group_names_by_username($rowsReturnGroupsDevMembership);

$devGroupRows = dlsu_call_sql($devSql, $devTok, 'SELECT id, name, status FROM groups');
$rowsReturnGroupsDevCatalog = is_array($devGroupRows) ? array_values($devGroupRows) : [];

$devGroupIdByName = [];
foreach ($rowsReturnGroupsDevCatalog as $g) {
    $n = trim((string) ($g['name'] ?? ''));
    if ($n !== '' && isset($g['id'])) {
        $devGroupIdByName[strtolower($n)] = (int) $g['id'];
    }
}

$prodGroupNames = [];
foreach ($rowsReturnGroupsProdMembership as $row) {
    $n = trim((string) ($row['group_name'] ?? ''));
    if ($n !== '') {
        $prodGroupNames[strtolower($n)] = $n;
    }
}
$groupsMissingOnDevCatalog = [];
foreach (array_keys($prodGroupNames) as $ln) {
    if (!isset($devGroupIdByName[$ln])) {
        $groupsMissingOnDevCatalog[] = ['group_name' => $prodGroupNames[$ln], 'note' => 'Exists in prod membership but no group with this name on dev; create the group on dev or rename to align.'];
    }
}

$groupMismatches = [];
foreach ($prodByUser as $lu => $p) {
    if (!isset($devByUser[$lu])) {
        continue;
    }
    $want = $prodGroupsByUser[$lu] ?? [];
    $have = $devGroupsByUser[$lu] ?? [];
    if ($want != $have) {
        $groupMismatches[] = [
            'username'    => $p['username'],
            'prod_groups' => $want,
            'dev_groups'  => $have,
        ];
    }
}

$collectionReport = null;
$rowsCollectionProd = [];
$rowsCollectionDev = [];
$collectionReport2 = null;
$rowsCollectionProd2 = [];
$rowsCollectionDev2 = [];
if ($colProd !== '' && $colDev !== '' && ($scope === 'all' || $scope === 'collections')) {
    $collectionReport = dlsu_compare_collections(
        $prodHost,
        $prodTok,
        $devHost,
        $devTok,
        $colProd,
        $colDev,
        $colKey,
        $apply
    );
    $rowsCollectionProd = $collectionReport['rows_prod_full'] ?? [];
    $rowsCollectionDev = $collectionReport['rows_dev_full'] ?? [];
}
if ($colProd2 !== '' && $colDev2 !== '' && ($scope === 'all' || $scope === 'collections')) {
    $collectionReport2 = dlsu_compare_collections(
        $prodHost,
        $prodTok,
        $devHost,
        $devTok,
        $colProd2,
        $colDev2,
        $colKey,
        $apply
    );
    $rowsCollectionProd2 = $collectionReport2['rows_prod_full'] ?? [];
    $rowsCollectionDev2 = $collectionReport2['rows_dev_full'] ?? [];
}

if (($scope === 'all' || $scope === 'collections') && $colProd !== '' && $colDev !== '' && $collectionReport !== null && isset($collectionReport['error'])) {
    return dlsu_error_response($dlsuCfg, 'Collection pair 1: ' . (string) $collectionReport['error']);
}
if (($scope === 'all' || $scope === 'collections') && $colProd2 !== '' && $colDev2 !== '' && $collectionReport2 !== null && isset($collectionReport2['error'])) {
    return dlsu_error_response($dlsuCfg, 'Collection pair 2: ' . (string) $collectionReport2['error']);
}

$backupBeforeApply = null;
if ($apply && $dlsuCfg['save_backup_in_return']) {
    $backupBeforeApply = [
        'saved_at' => gmdate('c'),
        'request_id' => $dlsuCfg['request_id'],
        'user_id' => $dlsuCfg['user_id'],
        'environment_dev' => $dlsuCfg['environment_dev_label'],
        'snapshot_dev_users_rows' => $rowsReturnUsersFromDevelopment,
        'snapshot_dev_group_membership_rows' => $rowsReturnGroupsDevMembership,
        'snapshot_dev_groups_catalog_rows' => $rowsReturnGroupsDevCatalog,
        'snapshot_dev_collection_rows' => $rowsCollectionDev,
        'snapshot_dev_collection_rows_pair_2' => $rowsCollectionDev2,
        'configuration_snapshot' => [
            'collection_id_prod' => $colProd,
            'collection_id_dev' => $colDev,
            'collection_id_prod_2' => $colProd2,
            'collection_id_dev_2' => $colDev2,
            'audit_collection_id' => $auditCollectionId,
            'collection_match_key' => $colKey,
        ],
    ];
}

$resultPayload = [
    'scope' => $scope,
    'scope_note' => 'Full row arrays are always returned for transparency (large JSON). Use scope only to limit which changes apply.',
    'rows_return_users_from_production' => $rowsReturnUsersFromProduction,
    'rows_return_users_from_development' => $rowsReturnUsersFromDevelopment,
    'rows_return_groups_prod_membership' => $rowsReturnGroupsProdMembership,
    'rows_return_groups_dev_membership' => $rowsReturnGroupsDevMembership,
    'rows_return_groups_dev_catalog' => $rowsReturnGroupsDevCatalog,
    'rows_return_collection_prod_records' => $rowsCollectionProd,
    'rows_return_collection_dev_records' => $rowsCollectionDev,
    'rows_return_collection_prod_records_pair_2' => $rowsCollectionProd2,
    'rows_return_collection_dev_records_pair_2' => $rowsCollectionDev2,
    'summary_counts' => [
        'prod_users' => count($prodByUser),
        'dev_users' => count($devByUser),
        'missing_users_on_dev' => count($missingOnDev),
        'group_membership_diffs' => count($groupMismatches),
        'groups_missing_on_dev_catalog' => count($groupsMissingOnDevCatalog),
        'prod_collection_rows' => $collectionReport ? (int) ($collectionReport['prod_row_count'] ?? 0) : 0,
        'dev_collection_rows' => $collectionReport ? (int) ($collectionReport['dev_row_count'] ?? 0) : 0,
        'collection_missing_on_dev' => $collectionReport ? (int) ($collectionReport['missing_on_dev'] ?? 0) : 0,
        'prod_collection_rows_pair_2' => $collectionReport2 ? (int) ($collectionReport2['prod_row_count'] ?? 0) : 0,
        'dev_collection_rows_pair_2' => $collectionReport2 ? (int) ($collectionReport2['dev_row_count'] ?? 0) : 0,
        'collection_missing_on_dev_pair_2' => $collectionReport2 ? (int) ($collectionReport2['missing_on_dev'] ?? 0) : 0,
    ],
    'users_missing_on_dev' => array_map(static function ($u) {
        return [
            'username' => $u['username'] ?? '',
            'email' => $u['email'] ?? '',
            'firstname' => $u['firstname'] ?? '',
            'lastname' => $u['lastname'] ?? '',
            'status' => $u['status'] ?? '',
        ];
    }, $missingOnDev),
    'group_membership_diffs' => $groupMismatches,
    'groups_missing_on_dev_catalog' => $groupsMissingOnDevCatalog,
    'new_users_created' => [],
    'group_membership_updates_applied' => [],
    'new_groups_created' => [],
    'collections' => $collectionReport,
    'collections_pair_2' => $collectionReport2,
    'backup_before_apply' => $backupBeforeApply,
    'purify_note' => 'To clean request data: delete dlsu_backup, old script outputs, and any temporary tokens from stored JSON when audit is complete.',
];

if (!$apply) {
    $resultPayload = dlsu_payload_with_audit_row($dlsuCfg, $devHost, $devTok, $resultPayload, $action, $scope, true);
    return dlsu_success_response($dlsuCfg, $resultPayload, null, null);
}

$applied = ['users_created' => [], 'group_updates' => [], 'errors' => [], 'new_groups_created' => []];

if ($scope === 'all' || $scope === 'users') {
    if ($missingOnDev !== [] && $newUserPassword === '') {
        $applied['errors'][] = 'new_user_password_for_apply (or DLSU_NEW_USER_PASSWORD) is required when creating missing users.';
    } elseif ($missingOnDev !== []) {
        foreach ($missingOnDev as $p) {
            if ($canUseSdkApi) {
                try {
                    $editable = new \ProcessMaker\Client\Model\UsersEditable();
                    $editable->setUsername((string) ($p['username'] ?? ''));
                    $editable->setEmail((string) ($p['email'] ?? ''));
                    $editable->setFirstname((string) ($p['firstname'] ?? ''));
                    $editable->setLastname((string) ($p['lastname'] ?? ''));
                    $editable->setStatus((string) ($p['status'] ?? 'ACTIVE'));
                    $editable->setPassword($newUserPassword);

                    $created = $api->users()->createUser($editable);
                    $applied['users_created'][] = [
                        'username' => $p['username'] ?? '',
                        'new_user_id' => $created->getId(),
                    ];
                    $devByUser[strtolower((string) ($p['username'] ?? ''))] = [
                        'id' => $created->getId(),
                        'username' => $p['username'],
                    ];
                } catch (\Throwable $e) {
                    $applied['errors'][] = 'create user ' . ($p['username'] ?? '?') . ': ' . $e->getMessage();
                }
            } else {
                $rest = dlsu_rest_create_user_pm($devHost, $devTok, $p, $newUserPassword);
                if (isset($rest['error'])) {
                    $applied['errors'][] = 'create user ' . ($p['username'] ?? '?') . ': ' . (string) $rest['error'];
                } else {
                    $newId = (int) ($rest['id'] ?? 0);
                    $applied['users_created'][] = [
                        'username' => $p['username'] ?? '',
                        'new_user_id' => $newId,
                    ];
                    $devByUser[strtolower((string) ($p['username'] ?? ''))] = [
                        'id' => $newId,
                        'username' => $p['username'],
                    ];
                }
            }
        }
    }
}

if (($scope === 'all' || $scope === 'groups') && empty($applied['errors'])) {
    foreach ($prodByUser as $lu => $p) {
        if (!isset($devByUser[$lu])) {
            continue;
        }
        $wantNames = $prodGroupsByUser[$lu] ?? [];
        $ids = [];
        foreach ($wantNames as $gn) {
            $k = strtolower($gn);
            if (isset($devGroupIdByName[$k])) {
                $ids[] = $devGroupIdByName[$k];
            }
        }
        $ids = array_values(array_unique($ids));
        $userId = (int) ($devByUser[$lu]['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        if ($canUseSdkApi) {
            try {
                $uug = new \ProcessMaker\Client\Model\UpdateUserGroups();
                $uug->setGroups($ids);
                $api->users()->updateUserGroups($userId, $uug);
                $applied['group_updates'][] = [
                    'user_id' => $userId,
                    'username' => $p['username'],
                    'group_ids_applied' => $ids,
                ];
            } catch (\Throwable $e) {
                $applied['errors'][] = 'groups ' . ($p['username'] ?? '?') . ': ' . $e->getMessage();
            }
        } else {
            $gerr = dlsu_rest_put_user_groups_pm($devHost, $devTok, $userId, $ids);
            if ($gerr !== null) {
                $applied['errors'][] = 'groups ' . ($p['username'] ?? '?') . ': ' . $gerr;
            } else {
                $applied['group_updates'][] = [
                    'user_id' => $userId,
                    'username' => $p['username'],
                    'group_ids_applied' => $ids,
                ];
            }
        }
    }
}

$resultPayload['new_users_created'] = $applied['users_created'];
$resultPayload['group_membership_updates_applied'] = $applied['group_updates'];
$resultPayload['new_groups_created'] = $applied['new_groups_created'];
$resultPayload['applied_errors'] = $applied['errors'];

$applyOk = $applied['errors'] === [];
$resultPayload = dlsu_payload_with_audit_row($dlsuCfg, $devHost, $devTok, $resultPayload, $action, $scope, $applyOk);

return dlsu_success_response(
    $dlsuCfg,
    $resultPayload,
    $applied,
    $applyOk ? null : implode('; ', $applied['errors'])
);

// =============================================================================
// SECTION 3 — FUNCTIONS
//
// Deployment options (pick one):
//   A) Single file — paste this entire script into one PM script task (simplest; redeploy on change).
//   B) Shared library — if your ProcessMaker version supports script includes, move only this SECTION 3
//      into a library script and require it from the task script; keep PCR notes in sync in one place.
//   C) Document $data / env keys in DLSU/README.md; never commit real tokens to git.
//
// =============================================================================

/**
 * Build a standard error response including config echo (no secrets) and rows placeholders.
 *
 * @param array<string,mixed> $cfg
 * @param array<string,mixed>|null $detail
 * @return array<string,mixed>
 */
function dlsu_error_response(array $cfg, string $message, $detail = null): array
{
    $out = [
        'success' => false,
        'error' => $message,
        'configuration_echo' => dlsu_configuration_echo($cfg),
        'rows_return_users_from_production' => [],
        'rows_return_users_from_development' => [],
        'rows_return_groups_prod_membership' => [],
        'rows_return_groups_dev_membership' => [],
        'rows_return_groups_dev_catalog' => [],
        'rows_return_collection_prod_records' => [],
        'rows_return_collection_dev_records' => [],
    ];
    if ($detail !== null) {
        $out['detail'] = $detail;
    }
    return $out;
}

/**
 * Success wrapper: merges payload, optional applied block, and echoes safe config.
 *
 * @param array<string,mixed> $cfg
 * @param array<string,mixed> $payload
 * @param array<string,mixed>|null $applied
 * @return array<string,mixed>
 */
function dlsu_success_response(array $cfg, array $payload, $applied, $errorHint = null): array
{
    $out = array_merge(
        [
            'success' => $errorHint === null,
            'configuration_echo' => dlsu_configuration_echo($cfg),
        ],
        $payload
    );
    if ($applied !== null) {
        $out['applied'] = $applied;
    }
    if ($errorHint !== null) {
        $out['success'] = false;
        $out['error'] = $errorHint;
    }
    return $out;
}

/**
 * Echo non-secret configuration for auditing (tokens are masked).
 *
 * @param array<string,mixed> $cfg
 * @return array<string,mixed>
 */
function dlsu_configuration_echo(array $cfg): array
{
    return [
        'request_id' => $cfg['request_id'],
        'user_id' => $cfg['user_id'],
        'environment_dev_label' => $cfg['environment_dev_label'],
        'environment_prod_label' => $cfg['environment_prod_label'],
        'api_host_dev' => $cfg['api_host_dev'],
        'api_token_dev_masked' => dlsu_mask_token($cfg['api_token_dev'] ?? ''),
        'api_sql_path' => $cfg['api_sql_path'],
        'api_host_prod' => $cfg['api_host_prod'],
        'api_token_prod_masked' => dlsu_mask_token($cfg['api_token_prod'] ?? ''),
        'production_config_help' => 'Host: api_host_prod | production_api_host | production_host | PM_PROD_API_HOST | _env.* | DLSU_PRODUCTION_API_HOST. Token: api_token_prod | production_api_token | production_token | PM_PROD_API_TOKEN | DLSU_PRODUCTION_API_TOKEN.',
        'collection_id_prod' => $cfg['collection_id_prod'],
        'collection_id_dev' => $cfg['collection_id_dev'],
        'collection_id_prod_2' => $cfg['collection_id_prod_2'] ?? '',
        'collection_id_dev_2' => $cfg['collection_id_dev_2'] ?? '',
        'audit_collection_id' => $cfg['audit_collection_id'] ?? '',
        'collection_match_key' => $cfg['collection_match_key'],
        'include_admins' => $cfg['include_admins'],
        'action' => $cfg['action'],
        'scope' => $cfg['scope'],
        'save_backup_in_return' => $cfg['save_backup_in_return'],
    ];
}

/**
 * Mask a bearer token for logs (keep first/last few characters).
 */
function dlsu_mask_token(string $token): string
{
    $t = trim($token);
    if ($t === '') {
        return '';
    }
    if (strlen($t) <= 12) {
        return '****';
    }
    return substr($t, 0, 4) . '…' . substr($t, -4);
}

/**
 * POST SQL (base64 body) to Pro Service Tools SQL endpoint.
 *
 * @return array<int,array<string,mixed>>|array<string,mixed>
 */
function dlsu_call_sql(string $endpoint, string $token, string $sql): array
{
    static $client = null;
    if ($client === null) {
        $client = new \GuzzleHttp\Client([
            'verify' => false,
            'timeout' => 120,
        ]);
    }
    $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ];
    try {
        $res = $client->request('POST', $endpoint, [
            'headers' => $headers,
            'body' => json_encode(['SQL' => base64_encode($sql)]),
        ]);
        $json = json_decode($res->getBody()->getContents(), true);
        if (is_array($json) && isset($json['output']) && is_array($json['output'])) {
            return $json['output'];
        }
        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }
        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return ['__error__' => $e->getMessage()];
    }
}

/**
 * HTTP JSON helper for collection record GET/POST.
 *
 * @param array<string,mixed>|null $body
 * @return array<string,mixed>
 */
function dlsu_http_json(string $method, string $url, string $token, ?array $body): array
{
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 120]);
    $headers = [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ];
    if ($body !== null) {
        $headers['Content-Type'] = 'application/json';
    }
    try {
        $opts = ['headers' => $headers];
        if ($body !== null) {
            $opts['json'] = $body;
        }
        $res = $client->request($method, $url, $opts);
        $raw = $res->getBody()->getContents();
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['raw' => $raw];
    } catch (\Throwable $e) {
        return ['error_message' => $e->getMessage()];
    }
}

/**
 * Create a user on dev via REST (POST /users) when the PHP SDK $api is not available (e.g. PSTools iframe).
 *
 * @param array<string,mixed> $p Production user row
 * @return array{id?:int,error?:string,detail?:mixed}
 */
function dlsu_rest_create_user_pm(string $devHost, string $devTok, array $p, string $password): array
{
    $url = rtrim($devHost, '/') . '/users';
    $body = [
        'username' => (string) ($p['username'] ?? ''),
        'email' => (string) ($p['email'] ?? ''),
        'firstname' => (string) ($p['firstname'] ?? ''),
        'lastname' => (string) ($p['lastname'] ?? ''),
        'status' => (string) ($p['status'] ?? 'ACTIVE'),
        'password' => $password,
    ];
    $res = dlsu_http_json('POST', $url, $devTok, $body);
    if (isset($res['error_message'])) {
        return ['error' => (string) $res['error_message']];
    }
    $id = $res['id'] ?? null;
    if ($id === null && isset($res['data']) && is_array($res['data'])) {
        $id = $res['data']['id'] ?? null;
    }
    if ($id === null || (int) $id <= 0) {
        return ['error' => 'Unexpected create user response (no id)', 'detail' => $res];
    }

    return ['id' => (int) $id];
}

/**
 * Replace group membership for a user (PUT /users/{id}/groups).
 *
 * @param array<int> $groupIds
 */
function dlsu_rest_put_user_groups_pm(string $devHost, string $devTok, int $userId, array $groupIds): ?string
{
    $url = rtrim($devHost, '/') . '/users/' . $userId . '/groups';
    $res = dlsu_http_json('PUT', $url, $devTok, ['groups' => array_values($groupIds)]);
    if (isset($res['error_message'])) {
        return (string) $res['error_message'];
    }

    return null;
}

/**
 * Append one audit row to a ProcessMaker collection on dev (optional).
 *
 * @param array<string,mixed> $payload Sync response body (mutated: adds audit_collection_log)
 * @return array<string,mixed>
 */
function dlsu_payload_with_audit_row(
    array $cfg,
    string $devHost,
    string $devTok,
    array $payload,
    string $action,
    string $scope,
    bool $runOk
): array {
    $auditId = trim((string) ($cfg['audit_collection_id'] ?? ''));
    if ($auditId === '') {
        $payload['audit_collection_log'] = [
            'skipped' => true,
            'reason' => 'audit_collection_id not set (pass audit_collection_id or DLSU_AUDIT_COLLECTION_ID)',
        ];

        return $payload;
    }

    $recordData = [
        'run_at' => gmdate('c'),
        'request_id' => $cfg['request_id'],
        'user_id' => $cfg['user_id'],
        'action' => $action,
        'scope' => $scope,
        'success' => $runOk ? 'yes' : 'no',
        'environment_dev' => $cfg['environment_dev_label'],
        'environment_prod' => $cfg['environment_prod_label'],
        'summary_json' => json_encode($payload['summary_counts'] ?? [], JSON_UNESCAPED_UNICODE),
        'applied_errors' => isset($payload['applied_errors']) && is_array($payload['applied_errors'])
            ? implode('; ', $payload['applied_errors'])
            : '',
    ];
    $url = rtrim($devHost, '/') . '/collections/' . rawurlencode($auditId) . '/records';
    $res = dlsu_http_json('POST', $url, $devTok, ['data' => $recordData]);
    if (isset($res['error_message'])) {
        $payload['audit_collection_log'] = [
            'ok' => false,
            'error' => $res['error_message'],
        ];
    } else {
        $payload['audit_collection_log'] = [
            'ok' => true,
            'record_id' => $res['id'] ?? ($res['data']['id'] ?? null),
        ];
    }

    return $payload;
}

/**
 * Index user rows by lowercase username.
 *
 * @param array<int,array<string,mixed>> $rows
 * @return array<string,array<string,mixed>>
 */
function dlsu_index_by_lower_username(array $rows): array
{
    $out = [];
    foreach ($rows as $r) {
        $u = strtolower((string) ($r['username'] ?? ''));
        if ($u !== '') {
            $out[$u] = $r;
        }
    }
    return $out;
}

/**
 * Build username => sorted list of distinct group names from membership rows.
 *
 * @param array<int,array<string,mixed>> $membershipRows
 * @return array<string,array<int,string>>
 */
function dlsu_group_names_by_username(array $membershipRows): array
{
    $map = [];
    foreach ($membershipRows as $row) {
        $user = strtolower((string) ($row['username'] ?? ''));
        $gname = trim((string) ($row['group_name'] ?? ''));
        if ($user === '' || $gname === '') {
            continue;
        }
        if (!isset($map[$user])) {
            $map[$user] = [];
        }
        $map[$user][$gname] = true;
    }
    foreach ($map as $u => $names) {
        $map[$u] = array_keys($names);
        sort($map[$u]);
    }
    return $map;
}

/**
 * Compare prod vs dev collection records; optionally POST missing rows to dev.
 * Returns full prod/dev rows for transparency (response can be large).
 *
 * @return array<string,mixed>
 */
function dlsu_compare_collections(
    string $prodHost,
    string $prodTok,
    string $devHost,
    string $devTok,
    string $collectionIdProd,
    string $collectionIdDev,
    string $matchKey,
    bool $apply
): array {
    $prodRecords = dlsu_fetch_all_collection_records($prodHost, $prodTok, $collectionIdProd);
    $devRecords = dlsu_fetch_all_collection_records($devHost, $devTok, $collectionIdDev);

    if (isset($prodRecords['error'])) {
        return $prodRecords;
    }
    if (isset($devRecords['error'])) {
        return $devRecords;
    }

    $prodRows = $prodRecords['rows'];
    $devRows = $devRecords['rows'];

    $devKeys = [];
    foreach ($devRows as $rec) {
        $data = isset($rec['data']) && is_array($rec['data']) ? $rec['data'] : [];
        $k = dlsu_collection_match_value($data, $matchKey);
        if ($k !== '') {
            $devKeys[strtolower($k)] = true;
        }
    }

    $missing = [];
    foreach ($prodRows as $rec) {
        $data = isset($rec['data']) && is_array($rec['data']) ? $rec['data'] : [];
        $k = dlsu_collection_match_value($data, $matchKey);
        if ($k === '') {
            continue;
        }
        if (!isset($devKeys[strtolower($k)])) {
            $missing[] = ['match' => $k, 'source_record_id' => $rec['id'] ?? null, 'data' => $data];
        }
    }

    $posted = [];
    if ($apply && $missing !== []) {
        foreach ($missing as $m) {
            $url = rtrim($devHost, '/') . '/collections/' . rawurlencode($collectionIdDev) . '/records';
            $res = dlsu_http_json('POST', $url, $devTok, ['data' => $m['data']]);
            $posted[] = isset($res['error_message']) ? ['error' => $res['error_message']] : ['id' => $res['id'] ?? null];
        }
    }

    return [
        'collection_prod' => $collectionIdProd,
        'collection_dev' => $collectionIdDev,
        'match_key' => $matchKey,
        'prod_row_count' => count($prodRows),
        'dev_row_count' => count($devRows),
        'missing_on_dev' => count($missing),
        'rows_missing_full' => $missing,
        'posted_in_apply' => $posted,
        'rows_prod_full' => $prodRows,
        'rows_dev_full' => $devRows,
    ];
}

/**
 * Read match key from collection record data payload.
 */
function dlsu_collection_match_value(array $data, string $matchKey): string
{
    if (isset($data[$matchKey])) {
        return trim((string) $data[$matchKey]);
    }
    return '';
}

/**
 * Paginate through GET /collections/{id}/records until last_page.
 *
 * @return array{rows: array<int, array<string,mixed>>}|array{error: string}
 */
function dlsu_fetch_all_collection_records(string $apiHost, string $token, string $collectionId): array
{
    $rows = [];
    $page = 1;
    $perPage = 100;

    do {
        $url = rtrim($apiHost, '/') . '/collections/' . rawurlencode($collectionId) . '/records'
            . '?page=' . $page . '&per_page=' . $perPage;
        $json = dlsu_http_json('GET', $url, $token, null);
        if (isset($json['error_message'])) {
            return ['error' => $json['error_message']];
        }
        $chunk = $json['data'] ?? [];
        foreach ($chunk as $rec) {
            if (is_array($rec)) {
                $rows[] = $rec;
            }
        }
        $meta = $json['meta'] ?? [];
        $lastPage = (int) ($meta['last_page'] ?? 1);
        if ($lastPage < 1) {
            $lastPage = 1;
        }
        $page++;
    } while ($page <= $lastPage && $page < 600);

    return ['rows' => $rows];
}
