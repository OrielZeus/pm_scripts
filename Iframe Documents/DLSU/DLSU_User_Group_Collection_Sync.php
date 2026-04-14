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
    return '';
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
    return '';
}

// -----------------------------------------------------------------------------
// SECTION 1 — CONFIGURATION (request + environment)
// All values can be supplied via $data (request) or getenv(). Request wins over env.
// -----------------------------------------------------------------------------

if (!isset($data) || !is_array($data)) {
    $data = [];
}

$dlsuCfg = [
    'request_id' => $data['request_id'] ?? $data['_request']['id'] ?? getenv('DLSU_REQUEST_ID') ?: null,
    'user_id' => $data['user_id'] ?? $data['_request']['user_id'] ?? getenv('DLSU_USER_ID') ?: null,

    'environment_dev_label'  => trim((string) ($data['environment_dev_label'] ?? getenv('DLSU_ENV_DEV_LABEL') ?: 'development')),
    'environment_prod_label' => trim((string) ($data['environment_prod_label'] ?? getenv('DLSU_ENV_PROD_LABEL') ?: 'production')),

    'api_host_dev'  => rtrim(trim((string) ($data['api_host_dev'] ?? $data['API_HOST'] ?? getenv('API_HOST') ?: '')), '/'),
    'api_token_dev' => (string) ($data['api_token_dev'] ?? $data['api_token'] ?? getenv('API_TOKEN') ?: ''),
    'api_sql_path'  => trim((string) ($data['api_sql_path'] ?? getenv('API_SQL') ?: '/admin/package-proservice-tools/sql')),

    'api_host_prod'  => dlsu_resolve_prod_host($data),
    'api_token_prod' => dlsu_resolve_prod_token($data),

    'new_user_password_for_apply' => (string) ($data['new_user_password_for_apply'] ?? $data['DLSU_NEW_USER_PASSWORD'] ?? getenv('DLSU_NEW_USER_PASSWORD') ?: ''),

    'collection_id_prod' => trim((string) ($data['collection_id_prod'] ?? $data['DLSU_COLLECTION_ID_PROD'] ?? getenv('DLSU_COLLECTION_ID_PROD') ?: '')),
    'collection_id_dev'  => trim((string) ($data['collection_id_dev'] ?? $data['DLSU_COLLECTION_ID_DEV'] ?? getenv('DLSU_COLLECTION_ID_DEV') ?: '')),
    'collection_match_key' => trim((string) ($data['collection_match_key'] ?? getenv('DLSU_COLLECTION_MATCH_KEY') ?: 'email')),

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
$colKey  = $dlsuCfg['collection_match_key'];

if ($devHost === '' || $devTok === '') {
    return dlsu_error_response($dlsuCfg, 'API host/token for development are required (api_host_dev / api_token_dev or API_HOST / API_TOKEN).');
}
if ($prodHost === '' || $prodTok === '') {
    return dlsu_error_response(
        $dlsuCfg,
        'Production API host and token are required. Set via request data (api_host_prod, production_api_host, api_token_prod, production_api_token, …) or environment (DLSU_PRODUCTION_API_HOST, DLSU_PRODUCTION_API_TOKEN, PM_PROD_API_HOST, PM_PROD_API_TOKEN).'
    );
}

$needsApi = $apply && ($scope === 'all' || $scope === 'users' || $scope === 'groups');
if ($needsApi && !isset($api)) {
    return dlsu_error_response($dlsuCfg, 'Apply mode for users/groups requires $api (ProcessMaker script with SDK on dev).');
}

if ($scope === 'collections' && ($colProd === '' || $colDev === '')) {
    return dlsu_error_response($dlsuCfg, 'For scope=collections set collection_id_prod and collection_id_dev (or env DLSU_COLLECTION_*).');
}

// -----------------------------------------------------------------------------
// SECTION 2 — MAIN LOGIC
// -----------------------------------------------------------------------------

if ($scope === 'collections') {
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
    $collBackup = null;
    if ($apply && $dlsuCfg['save_backup_in_return']) {
        $collBackup = [
            'saved_at' => gmdate('c'),
            'request_id' => $dlsuCfg['request_id'],
            'user_id' => $dlsuCfg['user_id'],
            'environment_dev' => $dlsuCfg['environment_dev_label'],
            'snapshot_dev_collection_rows' => $collectionReport['rows_dev_full'] ?? [],
        ];
    }
    return dlsu_success_response($dlsuCfg, [
        'scope' => 'collections',
        'scope_note' => 'Collections-only run: user/group SQL was skipped. Row arrays below are empty except collection tables.',
        'rows_return_users_from_production' => [],
        'rows_return_users_from_development' => [],
        'rows_return_groups_prod_membership' => [],
        'rows_return_groups_dev_membership' => [],
        'rows_return_groups_dev_catalog' => [],
        'rows_return_collection_prod_records' => $collectionReport['rows_prod_full'] ?? [],
        'rows_return_collection_dev_records' => $collectionReport['rows_dev_full'] ?? [],
        'summary_counts' => [
            'prod_users' => 0,
            'dev_users' => 0,
            'missing_users_on_dev' => 0,
            'group_membership_diffs' => 0,
            'groups_missing_on_dev_catalog' => 0,
            'prod_collection_rows' => (int) ($collectionReport['prod_row_count'] ?? 0),
            'dev_collection_rows' => (int) ($collectionReport['dev_row_count'] ?? 0),
            'collection_missing_on_dev' => (int) ($collectionReport['missing_on_dev'] ?? 0),
        ],
        'users_missing_on_dev' => [],
        'group_membership_diffs' => [],
        'groups_missing_on_dev_catalog' => [],
        'new_users_created' => [],
        'group_membership_updates_applied' => [],
        'new_groups_created' => [],
        'collections' => $collectionReport,
        'backup_before_apply' => $collBackup,
        'purify_note' => 'Merge backup_before_apply into request data as dlsu_backup if you want it stored on the case; remove that key later to purify.',
    ], $apply ? ['collections_posted' => $collectionReport['posted_in_apply'] ?? []] : null, isset($collectionReport['error']) ? (string) $collectionReport['error'] : null);
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
    'summary_counts' => [
        'prod_users' => count($prodByUser),
        'dev_users' => count($devByUser),
        'missing_users_on_dev' => count($missingOnDev),
        'group_membership_diffs' => count($groupMismatches),
        'groups_missing_on_dev_catalog' => count($groupsMissingOnDevCatalog),
        'prod_collection_rows' => $collectionReport ? (int) ($collectionReport['prod_row_count'] ?? 0) : 0,
        'dev_collection_rows' => $collectionReport ? (int) ($collectionReport['dev_row_count'] ?? 0) : 0,
        'collection_missing_on_dev' => $collectionReport ? (int) ($collectionReport['missing_on_dev'] ?? 0) : 0,
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
    'backup_before_apply' => $backupBeforeApply,
    'purify_note' => 'To clean request data: delete dlsu_backup, old script outputs, and any temporary tokens from stored JSON when audit is complete.',
];

if (!$apply || $action === 'report') {
    return dlsu_success_response($dlsuCfg, $resultPayload, null, null);
}

$applied = ['users_created' => [], 'group_updates' => [], 'errors' => [], 'new_groups_created' => []];

if ($scope === 'all' || $scope === 'users') {
    if ($missingOnDev !== [] && $newUserPassword === '') {
        $applied['errors'][] = 'new_user_password_for_apply (or DLSU_NEW_USER_PASSWORD) is required when creating missing users.';
    } elseif ($missingOnDev !== []) {
        foreach ($missingOnDev as $p) {
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
    }
}

$resultPayload['new_users_created'] = $applied['users_created'];
$resultPayload['group_membership_updates_applied'] = $applied['group_updates'];
$resultPayload['new_groups_created'] = $applied['new_groups_created'];
$resultPayload['applied_errors'] = $applied['errors'];

return dlsu_success_response(
    $dlsuCfg,
    $resultPayload,
    $applied,
    empty($applied['errors']) ? null : implode('; ', $applied['errors'])
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
