<?php

/**
 * CS Dashboard - Unassigned Backend
 *
 * By: Andres Garcia
 */

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql  = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';

/* Builds a safe SQL endpoint from the current environment base URL. */
$siteBase    = preg_replace('~/api/1\.0/?$~', '', $apiHost);
$sqlEndpoint = $siteBase . $apiSql;

if (!isset($data) || !is_array($data)) $data = [];

$mode = trim((string)($data['mode'] ?? ''));

$draw   = (int)($data['draw'] ?? 1);
$start  = max(0, (int)($data['start'] ?? 0));
$length = max(1, min(5000, (int)($data['length'] ?? 25)));

$caseNum   = trim((string)($data['case_number'] ?? ''));
$title     = trim((string)($data['case_title']  ?? ''));
$taskName  = trim((string)($data['task_name']   ?? ''));
$status    = trim((string)($data['case_status'] ?? ''));
$processId = trim((string)($data['process_id']  ?? ''));
$dateFrom  = trim((string)($data['date_from']   ?? ''));
$dateTo    = trim((string)($data['date_to']     ?? ''));

$currentUserId = (int)($data['currentUserId'] ?? ($data['_request']['user_id'] ?? 0));

function escLike($v)
{
    return str_replace(["\\", "'", "%", "_"], ["\\\\", "\\'", "\\%", "\\_"], (string)$v);
}
function escEq($v)
{
    return str_replace("'", "\\'", (string)$v);
}

/* Sends SQL to the ProService Tools endpoint and normalizes the response. */
function callSql($endpoint, $sql)
{
    static $token = null;
    static $client = null;

    if ($token === null) $token = getenv('API_TOKEN');

    if ($client === null) {
        $client = new \GuzzleHttp\Client([
            'verify'  => false,
            'timeout' => 25,
        ]);
    }

    try {
        $res = $client->request('POST', $endpoint, [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode(['SQL' => base64_encode($sql)]),
        ]);

        $json = json_decode($res->getBody()->getContents(), true);

        if (is_array($json) && isset($json['output']) && is_array($json['output'])) return $json['output'];
        if (is_array($json) && isset($json['data'])   && is_array($json['data']))   return $json['data'];

        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/* Gets all group IDs for the current user to validate self-service access. */
function getUserGroupIds($sqlEndpoint, $currentUserId)
{
    if ($currentUserId <= 0) return [];

    $sql = "
    SELECT DISTINCT CAST(group_id AS CHAR) AS group_id
    FROM group_members
    WHERE member_id = {$currentUserId}
    ORDER BY group_id
  ";

    $rows = callSql($sqlEndpoint, $sql);
    $ids = [];

    foreach ($rows as $r) {
        $gid = trim((string)($r['group_id'] ?? ''));
        if ($gid !== '') $ids[] = $gid;
    }

    return array_values(array_unique($ids));
}

/* Builds the JSON eligibility condition used for self-service token access. */
function buildEligibilitySql($tokenAlias, $currentUserId, $groupIds)
{
    $parts = [];

    $parts[] = "JSON_CONTAINS(JSON_EXTRACT(COALESCE({$tokenAlias}.self_service_groups, JSON_OBJECT()), '$.users'), CAST({$currentUserId} AS JSON))";
    $parts[] = "JSON_CONTAINS(JSON_EXTRACT(COALESCE({$tokenAlias}.self_service_groups, JSON_OBJECT()), '$.users'), JSON_QUOTE('" . escEq((string)$currentUserId) . "'))";

    $parts[] = "JSON_CONTAINS(COALESCE({$tokenAlias}.self_service_groups, JSON_ARRAY()), CAST({$currentUserId} AS JSON))";
    $parts[] = "JSON_CONTAINS(COALESCE({$tokenAlias}.self_service_groups, JSON_ARRAY()), JSON_QUOTE('" . escEq((string)$currentUserId) . "'))";

    foreach ($groupIds as $gid) {
        $gidInt = (int)$gid;
        $gidEsc = escEq((string)$gid);

        $parts[] = "JSON_CONTAINS(JSON_EXTRACT(COALESCE({$tokenAlias}.self_service_groups, JSON_OBJECT()), '$.groups'), CAST({$gidInt} AS JSON))";
        $parts[] = "JSON_CONTAINS(JSON_EXTRACT(COALESCE({$tokenAlias}.self_service_groups, JSON_OBJECT()), '$.groups'), JSON_QUOTE('{$gidEsc}'))";

        $parts[] = "JSON_CONTAINS(COALESCE({$tokenAlias}.self_service_groups, JSON_ARRAY()), CAST({$gidInt} AS JSON))";
        $parts[] = "JSON_CONTAINS(COALESCE({$tokenAlias}.self_service_groups, JSON_ARRAY()), JSON_QUOTE('{$gidEsc}'))";
    }

    $parts[] = "(
    JSON_LENGTH(COALESCE(JSON_EXTRACT({$tokenAlias}.self_service_groups, '$.users'), JSON_ARRAY())) = 0
    AND JSON_LENGTH(COALESCE(JSON_EXTRACT({$tokenAlias}.self_service_groups, '$.groups'), JSON_ARRAY())) = 0
  )";

    return '(' . implode(' OR ', $parts) . ')';
}

$userGroupIds   = getUserGroupIds($sqlEndpoint, $currentUserId);
$eligibilitySql = buildEligibilitySql('PRT', $currentUserId, $userGroupIds);

$scopeJoin  = "JOIN processes P ON P.id = PR.process_id";
$scopeWhere = "UPPER(COALESCE(P.status,'')) = 'ACTIVE'
  AND UPPER(COALESCE(PR.status,'')) IN ('ACTIVE','IN PROGRESS','IN_PROGRESS')
  AND EXISTS (
    SELECT 1
    FROM process_request_tokens PRT
    WHERE PRT.process_request_id = PR.id
      AND PRT.status = 'ACTIVE'
      AND COALESCE(PRT.user_id,0) = 0
      AND COALESCE(PRT.is_self_service,0) = 1
      AND {$eligibilitySql}
  )";

/* 1) INIT */
if ($mode === 'init') {
    $procSql = "
    SELECT id AS process_id, name AS process_name
    FROM processes
    WHERE status = 'ACTIVE'
      AND deleted_at IS NULL
      AND (is_template = 0 OR is_template IS NULL)
    ORDER BY name ASC
  ";

    $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      COUNT(*) AS pending_count,
      0 AS completed_count,
      0 AS cancelled_count
    FROM process_requests PR
    {$scopeJoin}
    WHERE {$scopeWhere}
  ";

    $procRows = callSql($sqlEndpoint, $procSql);
    $kpiRows  = callSql($sqlEndpoint, $kpiSql);

    $processes = [];
    foreach ($procRows as $p) {
        $id   = $p['process_id'] ?? null;
        $name = $p['process_name'] ?? '';
        if (!$id || $name === '') continue;
        $processes[] = [
            'process_id'   => (int)$id,
            'process_name' => (string)$name,
        ];
    }

    $r = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];

    return [
        'processes' => $processes,
        'kpis' => [
            'all'       => (int)($r['all_count'] ?? 0),
            'pending'   => (int)($r['pending_count'] ?? 0),
            'completed' => (int)($r['completed_count'] ?? 0),
            'cancelled' => (int)($r['cancelled_count'] ?? 0),
        ],
    ];
}

/* 2) PROCESS LIST */
if ($mode === 'process_list') {
    $rows = callSql($sqlEndpoint, "
    SELECT id AS process_id, name AS process_name
    FROM processes
    WHERE status='ACTIVE'
      AND deleted_at IS NULL
      AND (is_template=0 OR is_template IS NULL)
    ORDER BY name ASC
  ");

    $out = [];
    foreach ($rows as $p) {
        $id = (int)($p['process_id'] ?? 0);
        $name = (string)($p['process_name'] ?? '');
        if ($id > 0 && $name !== '') {
            $out[] = ['process_id' => $id, 'process_name' => $name];
        }
    }

    return ['data' => $out];
}

/* 3) REQUEST SUMMARY */
if ($mode === 'request_summary') {
    $rid = (int)($data['request_id'] ?? 0);
    if ($rid <= 0) return ['ok' => false, 'error' => 'Invalid ID'];

    $rows = callSql($sqlEndpoint, "SELECT data FROM process_requests WHERE id={$rid}");
    if (empty($rows)) return ['ok' => false, 'error' => 'Not found'];

    $prData = json_decode($rows[0]['data'] ?? '{}', true);
    if (!is_array($prData)) $prData = [];

    $summary = [];
    foreach ($prData as $k => $v) {
        if ($k === '_request' || $k === '_env') continue;
        $summary[] = ['key' => $k, 'value' => $v];
    }

    return ['ok' => true, 'summary' => $summary, 'labelMap' => []];
}

/* 4) FILTERS */
$filterWhere = $scopeWhere;

if ($caseNum !== '') {
    $s = escLike($caseNum);
    $filterWhere .= " AND (CAST(PR.case_number AS CHAR) LIKE '%{$s}%' OR CAST(PR.id AS CHAR) LIKE '%{$s}%')";
}

if ($title !== '') {
    $s = escLike($title);
    $filterWhere .= " AND COALESCE(
    NULLIF(PR.case_title,''),
    NULLIF(PR.case_title_formatted,''),
    PR.data->>'$.caseTitle',
    PR.data->>'$.title',
    PR.data->>'$.requestTitle',
    PR.data->>'$.Title',
    CONCAT('Case #', PR.case_number)
  ) LIKE '%{$s}%'";
}

if ($taskName !== '') {
    $s = escLike($taskName);
    $filterWhere .= " AND EXISTS (
    SELECT 1
    FROM process_request_tokens prt_task
    WHERE prt_task.process_request_id = PR.id
      AND prt_task.status = 'ACTIVE'
      AND COALESCE(prt_task.user_id,0) = 0
      AND COALESCE(prt_task.is_self_service,0) = 1
      AND COALESCE(prt_task.element_name,'') LIKE '%{$s}%'
      AND " . buildEligibilitySql('prt_task', $currentUserId, $userGroupIds) . "
  )";
}

if ($processId !== '') {
    $pid = (int)$processId;
    if ($pid > 0) $filterWhere .= " AND PR.process_id = {$pid}";
}

if ($dateFrom !== '') $filterWhere .= " AND PR.created_at >= '" . escEq($dateFrom) . "'";
if ($dateTo   !== '') $filterWhere .= " AND PR.created_at < DATE_ADD('" . escEq($dateTo) . "', INTERVAL 1 DAY)";

if ($status !== '') {
    $st = strtoupper($status);
    if ($st === 'COMPLETED' || $st === 'CANCELLED' || $st === 'CANCELED') {
        $filterWhere .= " AND 1 = 0";
    }
}

/* 5) KPI COUNTS */
if ($mode === 'kpi_counts') {
    $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      COUNT(*) AS pending_count,
      0 AS completed_count,
      0 AS cancelled_count
    FROM process_requests PR
    {$scopeJoin}
    WHERE {$filterWhere}
  ";

    $r = callSql($sqlEndpoint, $kpiSql);
    $r = isset($r[0]) ? $r[0] : [];

    return ['kpis' => [
        'all'       => (int)($r['all_count'] ?? 0),
        'pending'   => (int)($r['pending_count'] ?? 0),
        'completed' => (int)($r['completed_count'] ?? 0),
        'cancelled' => (int)($r['cancelled_count'] ?? 0),
    ]];
}

/* 6) ORDERING */
$orderColIdx = (int)($data['order'][0]['column'] ?? 7);
$orderDir    = strtolower((string)($data['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderField  = (string)($data['columns'][$orderColIdx]['data'] ?? 'created_at');

$allowedOrder = [
    'case_number'  => 'B.case_number',
    'case_title'   => 'B.case_title',
    'process_name' => 'B.process_name',
    'current_task' => 'current_task',
    'assigned_to'  => 'assigned_to',
    'status_text'  => 'B.status_text',
    'created_at'   => 'B.created_at'
];
$ob = $allowedOrder[$orderField] ?? 'B.created_at';

$innerOrder = [
    'case_number'  => 'PR.case_number',
    'case_title'   => 'case_title',
    'process_name' => 'P.name',
    'status_text'  => 'PR.created_at',
    'created_at'   => 'PR.created_at',
    'current_task' => 'PR.created_at',
    'assigned_to'  => 'PR.created_at'
];
$innerOb = $innerOrder[$orderField] ?? 'PR.created_at';

/* 7) MAIN QUERY */
$metaSql = "
  SELECT
    'meta' AS row_type,
    'total' AS meta_key,
    COUNT(1) AS meta_value,
    NULL AS request_id,
    NULL AS case_number,
    NULL AS process_id,
    NULL AS process_name,
    NULL AS case_title,
    NULL AS current_token_id,
    NULL AS current_task,
    NULL AS assigned_to,
    NULL AS status_text,
    NULL AS created_at
  FROM process_requests PR
  {$scopeJoin}
  WHERE {$scopeWhere}

  UNION ALL

  SELECT
    'meta' AS row_type,
    'filtered' AS meta_key,
    COUNT(1) AS meta_value,
    NULL AS request_id,
    NULL AS case_number,
    NULL AS process_id,
    NULL AS process_name,
    NULL AS case_title,
    NULL AS current_token_id,
    NULL AS current_task,
    NULL AS assigned_to,
    NULL AS status_text,
    NULL AS created_at
  FROM process_requests PR
  {$scopeJoin}
  WHERE {$filterWhere}
";

$dataSql = "
  SELECT
    'data' AS row_type,
    NULL AS meta_key,
    NULL AS meta_value,
    B.request_id,
    B.case_number,
    B.process_id,
    B.process_name,
    B.case_title,
    (
      SELECT PRT.id
      FROM process_request_tokens PRT
      WHERE PRT.process_request_id = B.request_id
        AND PRT.status = 'ACTIVE'
        AND COALESCE(PRT.user_id,0) = 0
        AND COALESCE(PRT.is_self_service,0) = 1
        AND {$eligibilitySql}
      ORDER BY PRT.id DESC
      LIMIT 1
    ) AS current_token_id,
    (
      SELECT PRT.element_name
      FROM process_request_tokens PRT
      WHERE PRT.process_request_id = B.request_id
        AND PRT.status = 'ACTIVE'
        AND COALESCE(PRT.user_id,0) = 0
        AND COALESCE(PRT.is_self_service,0) = 1
        AND {$eligibilitySql}
      ORDER BY PRT.id DESC
      LIMIT 1
    ) AS current_task,
    'Unassigned' AS assigned_to,
    'Pending' AS status_text,
    B.created_at
  FROM (
    SELECT
      PR.id AS request_id,
      PR.case_number AS case_number,
      P.id AS process_id,
      P.name AS process_name,
      COALESCE(
        NULLIF(PR.case_title,''),
        NULLIF(PR.case_title_formatted,''),
        PR.data->>'$.caseTitle',
        PR.data->>'$.title',
        PR.data->>'$.requestTitle',
        PR.data->>'$.Title',
        CONCAT('Case #', PR.case_number)
      ) AS case_title,
      PR.created_at AS created_at
    FROM process_requests PR
    {$scopeJoin}
    WHERE {$filterWhere}
    ORDER BY {$innerOb} {$orderDir}
    LIMIT {$start}, {$length}
  ) B
";

$rows = callSql($sqlEndpoint, $metaSql . " UNION ALL " . $dataSql);

$total = 0;
$filtered = 0;
$dataRows = [];

foreach ($rows as $row) {
    $rowType = (string)($row['row_type'] ?? '');
    if ($rowType === 'meta') {
        $metaKey = (string)($row['meta_key'] ?? '');
        $metaVal = (int)($row['meta_value'] ?? 0);
        if ($metaKey === 'total') $total = $metaVal;
        if ($metaKey === 'filtered') $filtered = $metaVal;
        continue;
    }
    if ($rowType === 'data') {
        unset($row['row_type'], $row['meta_key'], $row['meta_value']);
        $dataRows[] = $row;
    }
}

$uiBase = preg_replace('~/api/1\.0/?$~', '', $apiHost);

/* Builds task URLs when there is an eligible active token. */
foreach ($dataRows as &$r) {
    $rid     = $r['request_id'] ?? '';
    $tokenId = $r['current_token_id'] ?? '';

    $r['open_url']    = $uiBase . '/requests/' . rawurlencode((string)$rid);
    $r['preview_url'] = '';

    if (!empty($tokenId)) {
        $r['open_url']    = $uiBase . '/tasks/' . rawurlencode((string)$tokenId) . '/edit';
        $r['preview_url'] = $uiBase . '/tasks/' . rawurlencode((string)$tokenId) . '/edit/preview?alwaysAllowEditing=1&disableInterstitial=1';
    }
}
unset($r);

return [
    'draw'            => $draw,
    'recordsTotal'    => $total,
    'recordsFiltered' => $filtered,
    'data'            => $dataRows,
];
