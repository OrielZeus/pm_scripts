<?php

/**
 * CS Dashboard - Participated SQL Backend
 * By: Andres Garcia 
 */

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql  = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$sqlEndpoint = $apiHost . $apiSql;

if (!isset($data) || !is_array($data)) $data = [];

$mode = trim((string)($data['mode'] ?? ''));

$draw   = (int)($data['draw'] ?? 1);
$start  = max(0, (int)($data['start'] ?? 0));
$length = max(1, min(5000, (int)($data['length'] ?? 25)));

$caseNum   = trim((string)($data['case_number'] ?? ''));
$title     = trim((string)($data['case_title'] ?? ''));
$taskName  = trim((string)($data['task_name'] ?? ''));
$status    = trim((string)($data['case_status'] ?? ''));
$processId = trim((string)($data['process_id'] ?? ''));
$dateFrom  = trim((string)($data['date_from'] ?? ''));
$dateTo    = trim((string)($data['date_to'] ?? ''));

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
    static $token = null, $client = null;
    if ($token === null) $token = getenv("API_TOKEN");
    if ($client === null) {
        $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 25]);
    }
    try {
        $res = $client->request('POST', $endpoint, [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode(["SQL" => base64_encode($sql)])
        ]);
        $json = json_decode($res->getBody()->getContents(), true);
        if (is_array($json) && isset($json['output']) && is_array($json['output'])) return $json['output'];
        if (is_array($json) && isset($json['data'])   && is_array($json['data']))   return $json['data'];
        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/* Calls the ProcessMaker API directly for request summary data. */
function callApiGet($url)
{
    static $token = null, $client = null;
    if ($token === null) $token = getenv("API_TOKEN");
    if ($client === null) {
        $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 25]);
    }
    try {
        $res = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);
        $json = json_decode($res->getBody()->getContents(), true);
        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/* Builds a label map from the summary screen config when available. */
function buildLabelMapFromScreenConfig($screenConfig)
{
    $map = [];
    $walk = function ($node) use (&$map, &$walk) {
        if ($node === null) return;
        if (is_array($node)) {
            foreach ($node as $k => $v) {
                if ($k === 'config' && is_array($v) && isset($v['name'])) {
                    $name = (string)$v['name'];
                    $label = isset($v['label']) && $v['label'] !== '' ? (string)$v['label'] : $name;
                    $map[$name] = $label;
                }
                $walk($v);
            }
        }
    };
    $walk($screenConfig);
    return $map;
}

/* =================== PARTICIPATED SCOPE =================== */
$scopeWhere = "UPPER(COALESCE(P.status, '')) = 'ACTIVE'
  AND EXISTS (
    SELECT 1
    FROM process_request_tokens PT
    WHERE PT.process_request_id = PR.id
      AND PT.user_id = {$currentUserId}
  )";

/* =================== FILTER BUILD =================== */
/* Base filters without the status dropdown filter. */
$baseFilters = $scopeWhere;

if ($caseNum !== '') {
    $s = escLike($caseNum);
    $baseFilters .= " AND (CAST(PR.case_number AS CHAR) LIKE '%{$s}%' OR CAST(PR.id AS CHAR) LIKE '%{$s}%')";
}
if ($title !== '') {
    $s = escLike($title);
    $baseFilters .= " AND COALESCE(
    PR.data->>'$._request.case_title',
    PR.data->>'$.case_title',
    PR.data->>'$._request.caseTitle',
    PR.data->>'$.caseTitle',
    PR.data->>'$._request.case_title_formatted',
    PR.data->>'$.case_title_formatted',
    PR.data->>'$.title',
    PR.data->>'$.requestTitle',
    PR.data->>'$.Title',
    CONCAT('Case #', PR.case_number)
  ) LIKE '%{$s}%'";
}
if ($taskName !== '') {
    $s = escLike($taskName);
    $baseFilters .= " AND EXISTS (
    SELECT 1
    FROM process_request_tokens PTT
    WHERE PTT.process_request_id = PR.id
      AND PTT.status = 'ACTIVE'
      AND COALESCE(PTT.element_name, '') LIKE '%{$s}%'
  )";
}
if ($processId !== '') {
    $pid = (int)$processId;
    if ($pid > 0) $baseFilters .= " AND PR.process_id = {$pid}";
}
if ($dateFrom !== '') $baseFilters .= " AND PR.created_at >= '" . escEq($dateFrom) . "'";
if ($dateTo   !== '') $baseFilters .= " AND PR.created_at < DATE_ADD('" . escEq($dateTo) . "', INTERVAL 1 DAY)";

/* Status filter only applies to the table and specific KPI refresh calls. */
$statusFilter = '';
if ($status !== '') {
    $st = strtoupper($status);
    if ($st === 'PENDING') {
        $statusFilter = " AND PR.status IN ('ACTIVE','In Progress')";
    } elseif ($st === 'COMPLETED') {
        $statusFilter = " AND PR.status IN ('COMPLETED')";
    } elseif ($st === 'CANCELLED' || $st === 'CANCELED') {
        $statusFilter = " AND PR.status IN ('CANCELED','CANCELLED')";
    }
}

/* Full filters used by the table. */
$fullFilters = $baseFilters . $statusFilter;

/* =========================================================
 * 1) PROCESS LIST
 * ========================================================= */
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

/* =========================================================
 * 2) INIT
 * Global KPI counts only, without status filters.
 * ========================================================= */
if ($mode === 'init') {
    $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ('COMPLETED') THEN 1 ELSE 0 END) AS completed_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE {$scopeWhere}
  ";
    $kpiRows = callSql($sqlEndpoint, $kpiSql);
    $r = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];
    return [
        'kpis' => [
            'all'       => (int)($r['all_count'] ?? 0),
            'pending'   => (int)($r['pending_count'] ?? 0),
            'completed' => (int)($r['completed_count'] ?? 0),
            'cancelled' => (int)($r['cancelled_count'] ?? 0),
        ]
    ];
}

/* =========================================================
 * 3) BOOTSTRAP
 * KPIs ignore the status filter, table respects it.
 * ========================================================= */
if ($mode === 'bootstrap') {
    $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ('COMPLETED') THEN 1 ELSE 0 END) AS completed_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE {$baseFilters}
  ";
    $kpiRows = callSql($sqlEndpoint, $kpiSql);
    $k = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];

    $totalSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$scopeWhere}";
    $filteredSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$fullFilters}";
    $totalRows = callSql($sqlEndpoint, $totalSql);
    $filteredRows = callSql($sqlEndpoint, $filteredSql);
    $total = (int)($totalRows[0]['total'] ?? 0);
    $filtered = (int)($filteredRows[0]['total'] ?? 0);

    $orderDir = 'DESC';
    $orderField = 'created_at';
    $allowedOrder = [
        'case_number'   => 'PR.case_number',
        'case_title'    => 'case_title',
        'process_name'  => 'process_name',
        'current_task'  => 'current_task',
        'assigned_to'   => 'assigned_to',
        'status_text'   => 'status_text',
        'created_at'    => 'PR.created_at',
        'priority_text' => 'priority_text',
    ];
    $ob = $allowedOrder[$orderField] ?? 'PR.created_at';

    $dataSql = "
    WITH ACTIVE_TOKEN AS (
      SELECT prt.process_request_id AS request_id, MAX(prt.id) AS token_id
      FROM process_request_tokens prt
      WHERE prt.status = 'ACTIVE'
      GROUP BY prt.process_request_id
    )
    SELECT
      PR.id AS request_id,
      PR.case_number AS case_number,
      P.id AS process_id,
      P.name AS process_name,
      COALESCE(
        PR.data->>'$._request.case_title',
        PR.data->>'$.case_title',
        PR.data->>'$._request.caseTitle',
        PR.data->>'$.caseTitle',
        PR.data->>'$._request.case_title_formatted',
        PR.data->>'$.case_title_formatted',
        PR.data->>'$.title',
        PR.data->>'$.requestTitle',
        PR.data->>'$.Title',
        CONCAT('Case #', PR.case_number)
      ) AS case_title,
      CT.id AS current_token_id,
      CT.element_name AS current_task,
      CONCAT(COALESCE(U.firstname,''), ' ', COALESCE(U.lastname,'')) AS assigned_to,
      CASE
        WHEN PR.status IN ('ACTIVE','In Progress') THEN 'Pending'
        WHEN PR.status IN ('COMPLETED') THEN 'Completed'
        WHEN PR.status IN ('CANCELED','CANCELLED') THEN 'Cancelled'
        WHEN PR.status IN ('RETURNED') THEN 'Returned'
        ELSE PR.status
      END AS status_text,
      PR.created_at AS created_at,
      COALESCE(
        PR.data->>'$.priority',
        PR.data->>'$.Priority',
        PR.data->>'$.casePriority',
        'Normal'
      ) AS priority_text
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    LEFT JOIN ACTIVE_TOKEN AT ON AT.request_id = PR.id
    LEFT JOIN process_request_tokens CT ON CT.id = AT.token_id
    LEFT JOIN users U ON U.id = CT.user_id
    WHERE {$fullFilters}
    ORDER BY {$ob} DESC
    LIMIT 0, 25
  ";

    $dataRows = callSql($sqlEndpoint, $dataSql);
    $uiBase = preg_replace('~/api/1\.0/?$~', '', $apiHost);

    foreach ($dataRows as &$r) {
        $rid = $r['request_id'] ?? '';
        $tokenId = $r['current_token_id'] ?? '';
        $r['open_url'] = $uiBase . '/requests/' . rawurlencode((string)$rid);
        $r['preview_url'] = '';
        if (!empty($tokenId) && ($r['status_text'] ?? '') === 'Pending') {
            $r['preview_url'] = $uiBase . '/tasks/' . rawurlencode((string)$tokenId) . '/edit/preview?alwaysAllowEditing=1&disableInterstitial=1';
        }
    }
    unset($r);

    return [
        'kpis' => [
            'all'       => (int)($k['all_count'] ?? 0),
            'pending'   => (int)($k['pending_count'] ?? 0),
            'completed' => (int)($k['completed_count'] ?? 0),
            'cancelled' => (int)($k['cancelled_count'] ?? 0),
        ],
        'table' => [
            'draw'            => 1,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $dataRows,
        ]
    ];
}

/* =========================================================
 * 4) REQUEST SUMMARY
 * ========================================================= */
if ($mode === 'request_summary') {
    $rid = (int)($data['request_id'] ?? 0);
    if ($rid <= 0) return ['ok' => false, 'message' => 'Missing request_id'];

    $url = rtrim($apiHost, '/') . '/requests/' . $rid;
    $resp = callApiGet($url);

    $apiSummary = (isset($resp['summary']) && is_array($resp['summary'])) ? $resp['summary'] : [];
    $apiScreenConfig = $resp['summary_screen'] ?? null;

    $labelMap = [];
    $usedScreenConfig = false;
    if (is_array($apiScreenConfig)) {
        $labelMap = buildLabelMapFromScreenConfig($apiScreenConfig);
        $usedScreenConfig = count($labelMap) > 0;
    }

    if (is_array($apiSummary) && count($apiSummary) > 0) {
        return [
            'ok' => true,
            'request_id' => $rid,
            'usedScreenConfig' => $usedScreenConfig,
            'labelMap' => $labelMap,
            'summary' => $apiSummary,
            'source' => 'api_summary'
        ];
    }

    $sql = "
    SELECT
      PR.id AS request_id,
      PR.case_number,
      PR.status,
      PR.created_at,
      P.name AS process_name,
      PR.data AS data_json
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE PR.id = {$rid}
      AND UPPER(COALESCE(P.status, '')) = 'ACTIVE'
    LIMIT 1
  ";

    $rows = callSql($sqlEndpoint, $sql);
    $row = (is_array($rows) && isset($rows[0])) ? $rows[0] : null;

    if (!$row) {
        return ['ok' => false, 'message' => 'Request not found in SQL', 'request_id' => $rid];
    }

    $rawJson = $row['data_json'] ?? '';
    $decoded = is_array($rawJson) ? $rawJson : (json_decode((string)$rawJson, true) ?: []);

    $pairs = [];
    $seen = [];

    $pushPair = function ($k, $v) use (&$pairs, &$seen) {
        $k = (string)$k;
        if ($k === '' || isset($seen[$k])) return;
        $seen[$k] = true;
        if (is_bool($v)) $val = $v ? 'true' : 'false';
        elseif ($v === null) $val = '';
        elseif (is_scalar($v)) $val = (string)$v;
        else $val = json_encode($v, JSON_PRETTY_PRINT);
        $pairs[] = ['key' => $k, 'value' => $val];
    };

    $walk = function ($node, $prefix, $depth) use (&$walk, $pushPair) {
        if ($depth > 4) return;
        if (is_array($node)) {
            $isAssoc = array_keys($node) !== range(0, count($node) - 1);
            if (!$isAssoc && count($node) > 0 && is_array($node[0])) {
                $pushPair($prefix, json_encode($node, JSON_PRETTY_PRINT));
                return;
            }
            foreach ($node as $k => $v) {
                $key = $isAssoc ? (string)$k : ('[' . $k . ']');
                $path = $prefix === '' ? $key : ($prefix . '.' . $key);
                if (is_array($v)) {
                    $allScalar = true;
                    foreach ($v as $vv) {
                        if (!is_scalar($vv) && $vv !== null && !is_bool($vv)) {
                            $allScalar = false;
                            break;
                        }
                    }
                    if ($allScalar && count($v) <= 30) {
                        $pushPair($path, json_encode($v));
                    } else {
                        $walk($v, $path, $depth + 1);
                    }
                } else {
                    $pushPair($path, $v);
                }
            }
        } else {
            $pushPair($prefix ?: 'value', $node);
        }
    };

    $pushPair('case_title', $decoded['_request']['case_title'] ?? ($decoded['case_title'] ?? ''));
    $pushPair('case_number', $row['case_number'] ?? '');
    $pushPair('process_name', $row['process_name'] ?? '');
    $pushPair('status', $row['status'] ?? '');
    $pushPair('created_at', $row['created_at'] ?? '');

    $walk($decoded, '', 1);

    return [
        'ok' => true,
        'request_id' => $rid,
        'usedScreenConfig' => false,
        'labelMap' => ['case_title' => 'Case Title'],
        'summary' => $pairs,
        'source' => 'sql_data_fallback'
    ];
}

/* =========================================================
 * 5) KPI COUNTS
 * Used after KPI clicks or manual refresh.
 * The status filter is applied here because the frontend
 * already changed the dropdown to the selected status.
 * ========================================================= */
if ($mode === 'kpi_counts') {
    $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ('COMPLETED') THEN 1 ELSE 0 END) AS completed_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE {$fullFilters}
  ";
    $kpiRows = callSql($sqlEndpoint, $kpiSql);
    $r = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];
    return [
        'kpis' => [
            'all'       => (int)($r['all_count'] ?? 0),
            'pending'   => (int)($r['pending_count'] ?? 0),
            'completed' => (int)($r['completed_count'] ?? 0),
            'cancelled' => (int)($r['cancelled_count'] ?? 0),
        ]
    ];
}

/* =========================================================
 * 6) STANDARD TABLE
 * Uses all active filters, including status.
 * ========================================================= */
$orderColIdx = (int)($data['order'][0]['column'] ?? 7);
$orderDir    = strtolower((string)($data['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderField  = (string)($data['columns'][$orderColIdx]['data'] ?? 'created_at');

$allowedOrder = [
    'case_number'   => 'PR.case_number',
    'case_title'    => 'case_title',
    'process_name'  => 'process_name',
    'current_task'  => 'current_task',
    'assigned_to'   => 'assigned_to',
    'status_text'   => 'status_text',
    'created_at'    => 'PR.created_at',
    'priority_text' => 'priority_text',
];
$ob = $allowedOrder[$orderField] ?? 'PR.created_at';

$totalSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$scopeWhere}";
$filteredSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$fullFilters}";
$totalRows = callSql($sqlEndpoint, $totalSql);
$filteredRows = callSql($sqlEndpoint, $filteredSql);
$total = (int)($totalRows[0]['total'] ?? 0);
$filtered = (int)($filteredRows[0]['total'] ?? 0);

$dataSql = "
  WITH ACTIVE_TOKEN AS (
    SELECT prt.process_request_id AS request_id, MAX(prt.id) AS token_id
    FROM process_request_tokens prt
    WHERE prt.status = 'ACTIVE'
    GROUP BY prt.process_request_id
  )
  SELECT
    PR.id AS request_id,
    PR.case_number AS case_number,
    P.id AS process_id,
    P.name AS process_name,
    COALESCE(
      PR.data->>'$._request.case_title',
      PR.data->>'$.case_title',
      PR.data->>'$._request.caseTitle',
      PR.data->>'$.caseTitle',
      PR.data->>'$._request.case_title_formatted',
      PR.data->>'$.case_title_formatted',
      PR.data->>'$.title',
      PR.data->>'$.requestTitle',
      PR.data->>'$.Title',
      CONCAT('Case #', PR.case_number)
    ) AS case_title,
    CT.id AS current_token_id,
    CT.element_name AS current_task,
    CONCAT(COALESCE(U.firstname,''), ' ', COALESCE(U.lastname,'')) AS assigned_to,
    CASE
      WHEN PR.status IN ('ACTIVE','In Progress') THEN 'Pending'
      WHEN PR.status IN ('COMPLETED') THEN 'Completed'
      WHEN PR.status IN ('CANCELED','CANCELLED') THEN 'Cancelled'
      WHEN PR.status IN ('RETURNED') THEN 'Returned'
      ELSE PR.status
    END AS status_text,
    PR.created_at AS created_at,
    COALESCE(
      PR.data->>'$.priority',
      PR.data->>'$.Priority',
      PR.data->>'$.casePriority',
      'Normal'
    ) AS priority_text
  FROM process_requests PR
  JOIN processes P ON P.id = PR.process_id
  LEFT JOIN ACTIVE_TOKEN AT ON AT.request_id = PR.id
  LEFT JOIN process_request_tokens CT ON CT.id = AT.token_id
  LEFT JOIN users U ON U.id = CT.user_id
  WHERE {$fullFilters}
  ORDER BY {$ob} {$orderDir}
  LIMIT {$start}, {$length}
";

$dataRows = callSql($sqlEndpoint, $dataSql);
$uiBase = preg_replace('~/api/1\.0/?$~', '', $apiHost);

foreach ($dataRows as &$r) {
    $rid = $r['request_id'] ?? '';
    $tokenId = $r['current_token_id'] ?? '';
    $r['open_url'] = $uiBase . '/requests/' . rawurlencode((string)$rid);
    $r['preview_url'] = '';
    if (!empty($tokenId) && ($r['status_text'] ?? '') === 'Pending') {
        $r['preview_url'] = $uiBase . '/tasks/' . rawurlencode((string)$tokenId) . '/edit/preview?alwaysAllowEditing=1&disableInterstitial=1';
    }
}
unset($r);

return [
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $filtered,
    "data" => $dataRows
];
