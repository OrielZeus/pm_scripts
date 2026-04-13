<?php

/**
 * CS Dashboard - My Inbox Backend (DataTables server-side)
 *
 * Notes:
 * - Returns process list, KPI counts, request summary, and paginated table data
 * - Uses two separate queries for init mode to avoid UNION issues
 * - Uses scalar subqueries for active token data to keep the query stable
 * By: Andres Garcia 
 */

$apiHost     = rtrim((string)getenv('API_HOST'), '/');
$apiSql      = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$sqlEndpoint = $apiHost . $apiSql;

if (!isset($data) || !is_array($data)) $data = [];

$mode = trim((string)($data['mode'] ?? ''));

$draw   = (int)($data['draw'] ?? 1);
$start  = max(0, (int)($data['start'] ?? 0));
$length = max(1, min(5000, (int)($data['length'] ?? 25)));

$caseNum   = trim((string)($data['case_number'] ?? ''));
$title     = trim((string)($data['case_title']  ?? ''));
$task      = trim((string)($data['task']        ?? ''));
$status    = trim((string)($data['case_status'] ?? ''));
$processId = trim((string)($data['process_id']  ?? ''));
$dateFrom  = trim((string)($data['date_from']   ?? ''));
$dateTo    = trim((string)($data['date_to']     ?? ''));

$currentUserId   = (int)($data['currentUserId'] ?? ($data['_request']['user_id'] ?? 0));
$draftStatusList = "'DRAFT','Draft','draft','DRAFTED','Drafted','drafted'";

function escLike($v)
{
    return str_replace(["\\", "'", "%", "_"], ["\\\\", "\\'", "\\%", "\\_"], (string)$v);
}
function escEq($v)
{
    return str_replace("'", "\\'", (string)$v);
}

/**
 * Sends raw SQL to the ProService Tools SQL endpoint.
 * Returns a normalized array so the calling code stays simple.
 */
function callSql($endpoint, $sql)
{
    static $token = null;
    static $client = null;

    if ($token === null) {
        $token = getenv('API_TOKEN');
    }
    if ($client === null) {
        $client = new \GuzzleHttp\Client([
            'verify'  => false,
            'timeout' => 25,
        ]);
    }

    $headers = [
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ];

    try {
        $res = $client->request('POST', $endpoint, [
            'headers' => $headers,
            'body'    => json_encode(['SQL' => base64_encode($sql)]),
        ]);
        $json = json_decode($res->getBody()->getContents(), true);
        if (is_array($json) && isset($json['output']) && is_array($json['output'])) return $json['output'];
        if (is_array($json) && isset($json['data'])   && is_array($json['data']))   return $json['data'];
        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return [];
    }
}

$scopeJoin  = "JOIN processes P ON P.id = PR.process_id";
$scopeWhere = "UPPER(COALESCE(P.status,'')) = 'ACTIVE'
  AND (
    PR.user_id = {$currentUserId}
    OR EXISTS (
      SELECT 1
      FROM process_request_tokens T2
      WHERE T2.process_request_id = PR.id
        AND T2.status = 'ACTIVE'
        AND T2.user_id = {$currentUserId}
    )
  )";

/* ==========================================================
 * 1. INIT MODE
 * Loads the process dropdown and KPI counts in one HTTP call.
 * ========================================================== */
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
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ({$draftStatusList})     THEN 1 ELSE 0 END) AS draft_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    {$scopeJoin}
    WHERE {$scopeWhere}
  ";

    $procRows = callSql($sqlEndpoint, $procSql);
    $kpiRows  = callSql($sqlEndpoint, $kpiSql);

    $processes = [];
    foreach ($procRows as $p) {
        $id = $p['process_id'] ?? null;
        $name = $p['process_name'] ?? '';
        if (!$id || $name === '') continue;
        $processes[] = ['process_id' => (int)$id, 'process_name' => (string)$name];
    }

    $r = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];
    return [
        'processes' => $processes,
        'kpis' => [
            'all'       => (int)($r['all_count'] ?? 0),
            'pending'   => (int)($r['pending_count'] ?? 0),
            'draft'     => (int)($r['draft_count'] ?? 0),
            'cancelled' => (int)($r['cancelled_count'] ?? 0),
        ],
    ];
}

/* ==========================================================
 * 2. SUPPORT MODES
 * Small endpoints used by the frontend for dropdowns, summary,
 * and KPI refresh without loading the full table.
 * ========================================================== */
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

/* ==========================================================
 * 3. FILTERS
 * Applies the active filters before KPI or table queries run.
 * ========================================================== */
$filterWhere = $scopeWhere;

if ($caseNum !== '') {
    $s = escLike($caseNum);
    $filterWhere .= " AND (CAST(PR.case_number AS CHAR) LIKE '%{$s}%' OR CAST(PR.id AS CHAR) LIKE '%{$s}%')";
}
if ($title !== '') {
    $s = escLike($title);
    $filterWhere .= " AND COALESCE(PR.data->>'$.caseTitle',PR.data->>'$.title',PR.data->>'$.requestTitle',PR.data->>'$.Title',CONCAT('Case #',PR.case_number)) LIKE '%{$s}%'";
}
if ($task !== '') {
    $s = escLike($task);
    $filterWhere .= " AND EXISTS (
    SELECT 1
    FROM process_request_tokens prt_task
    WHERE prt_task.process_request_id = PR.id
      AND prt_task.status = 'ACTIVE'
      AND COALESCE(prt_task.element_name,'') LIKE '%{$s}%'
  )";
}
if ($processId !== '') {
    $pid = (int)$processId;
    if ($pid > 0) $filterWhere .= " AND PR.process_id = {$pid}";
}
if ($dateFrom !== '') $filterWhere .= " AND PR.created_at >= '" . escEq($dateFrom) . "'";
if ($dateTo   !== '') $filterWhere .= " AND PR.created_at < DATE_ADD('" . escEq($dateTo) . "', INTERVAL 1 DAY)";
if ($status   !== '') {
    $st = strtoupper($status);
    if ($st === 'PENDING')                         $filterWhere .= " AND PR.status IN ('ACTIVE','In Progress')";
    elseif ($st === 'CANCELLED' || $st === 'CANCELED') $filterWhere .= " AND PR.status IN ('CANCELED','CANCELLED')";
    elseif ($st === 'DRAFT')                           $filterWhere .= " AND PR.status IN ({$draftStatusList})";
    elseif ($st === 'RETURNED')                        $filterWhere .= " AND PR.status IN ('RETURNED')";
}

if ($mode === 'kpi_counts') {
    $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ({$draftStatusList})     THEN 1 ELSE 0 END) AS draft_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    {$scopeJoin}
    WHERE {$filterWhere}
  ";
    $r = callSql($sqlEndpoint, $kpiSql);
    $r = isset($r[0]) ? $r[0] : [];
    return ['kpis' => [
        'all'       => (int)($r['all_count'] ?? 0),
        'pending'   => (int)($r['pending_count'] ?? 0),
        'draft'     => (int)($r['draft_count'] ?? 0),
        'cancelled' => (int)($r['cancelled_count'] ?? 0),
    ]];
}

/* ==========================================================
 * 4. SORTING
 * Keeps sorting limited to the supported DataTables columns.
 * ========================================================== */
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
    'status_text'  => 'PR.status',
    'created_at'   => 'PR.created_at',
    'current_task' => 'PR.created_at',
    'assigned_to'  => 'PR.created_at'
];
$innerOb = $innerOrder[$orderField] ?? 'PR.created_at';

/* ==========================================================
 * 5. MAIN QUERY
 * Returns total count, filtered count, and current page data
 * in one SQL response for DataTables.
 * ========================================================== */
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

if ($orderField === 'current_task' || $orderField === 'assigned_to') {
    $dataSql = "
    SELECT
      'data' AS row_type,
      NULL AS meta_key,
      NULL AS meta_value,
      Q.request_id,
      Q.case_number,
      Q.process_id,
      Q.process_name,
      Q.case_title,
      Q.current_token_id,
      Q.current_task,
      Q.assigned_to,
      Q.status_text,
      Q.created_at
    FROM (
      SELECT
        PR.id AS request_id,
        PR.case_number AS case_number,
        P.id AS process_id,
        P.name AS process_name,
        COALESCE(
          PR.data->>'$.caseTitle',
          PR.data->>'$.title',
          PR.data->>'$.requestTitle',
          PR.data->>'$.Title',
          CONCAT('Case #', PR.case_number)
        ) AS case_title,
        CT.id AS current_token_id,
        CT.element_name AS current_task,
        CASE
          WHEN U.id IS NOT NULL THEN TRIM(CONCAT(COALESCE(U.firstname,''),' ',COALESCE(U.lastname,'')))
          ELSE ''
        END AS assigned_to,
        CASE
          WHEN PR.status IN ({$draftStatusList})      THEN 'Draft'
          WHEN PR.status IN ('ACTIVE','In Progress')  THEN 'Pending'
          WHEN PR.status IN ('COMPLETED')             THEN 'Completed'
          WHEN PR.status IN ('CANCELED','CANCELLED')  THEN 'Cancelled'
          WHEN PR.status IN ('RETURNED')              THEN 'Returned'
          ELSE PR.status
        END AS status_text,
        PR.created_at AS created_at
      FROM process_requests PR
      {$scopeJoin}
      LEFT JOIN (
        SELECT process_request_id, MAX(id) AS token_id
        FROM process_request_tokens
        WHERE status = 'ACTIVE'
        GROUP BY process_request_id
      ) AT ON AT.process_request_id = PR.id
      LEFT JOIN process_request_tokens CT ON CT.id = AT.token_id
      LEFT JOIN users U ON U.id = CT.user_id
      WHERE {$filterWhere}
      ORDER BY {$ob} {$orderDir}
      LIMIT {$start}, {$length}
    ) Q
  ";
} else {
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
        WHERE PRT.process_request_id = B.request_id AND PRT.status = 'ACTIVE' 
        ORDER BY PRT.id DESC LIMIT 1
      ) AS current_token_id,
      (
        SELECT PRT.element_name 
        FROM process_request_tokens PRT 
        WHERE PRT.process_request_id = B.request_id AND PRT.status = 'ACTIVE' 
        ORDER BY PRT.id DESC LIMIT 1
      ) AS current_task,
      (
        SELECT TRIM(CONCAT(COALESCE(U.firstname,''),' ',COALESCE(U.lastname,''))) 
        FROM process_request_tokens PRT 
        JOIN users U ON U.id = PRT.user_id 
        WHERE PRT.process_request_id = B.request_id AND PRT.status = 'ACTIVE' 
        ORDER BY PRT.id DESC LIMIT 1
      ) AS assigned_to,
      B.status_text,
      B.created_at
    FROM (
      SELECT
        PR.id AS request_id,
        PR.case_number AS case_number,
        P.id AS process_id,
        P.name AS process_name,
        COALESCE(
          PR.data->>'$.caseTitle',
          PR.data->>'$.title',
          PR.data->>'$.requestTitle',
          PR.data->>'$.Title',
          CONCAT('Case #', PR.case_number)
        ) AS case_title,
        CASE
          WHEN PR.status IN ({$draftStatusList})      THEN 'Draft'
          WHEN PR.status IN ('ACTIVE','In Progress')  THEN 'Pending'
          WHEN PR.status IN ('COMPLETED')             THEN 'Completed'
          WHEN PR.status IN ('CANCELED','CANCELLED')  THEN 'Cancelled'
          WHEN PR.status IN ('RETURNED')              THEN 'Returned'
          ELSE PR.status
        END AS status_text,
        PR.created_at AS created_at
      FROM process_requests PR
      {$scopeJoin}
      WHERE {$filterWhere}
      ORDER BY {$innerOb} {$orderDir}
      LIMIT {$start}, {$length}
    ) B
  ";
}

$mainSql = $metaSql . "\nUNION ALL\n" . $dataSql;

$rows = callSql($sqlEndpoint, $mainSql);

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

$uiBase = preg_replace('~/api/1\\.0/?$~', '', $apiHost);

/**
 * Builds direct task URLs when an active token exists.
 * Falls back to the request URL when no active token is found.
 */
foreach ($dataRows as &$r) {
    $rid     = $r['request_id']       ?? '';
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
