<?php

/**********************************
 * Active Tasks - Get Data
 * by Elmer Orihuela
 *********************************/

// Incluye tus helpers si los necesitas (como callApiUrlGuzzle, encodeSql, etc.)
// require_once("/Northleaf_PHP_Library.php");

$apiHost = getenv('API_HOST');
$apiSql  = getenv('API_SQL');
$sqlUrl  = $apiHost . $apiSql;
$currentUser = $data['CURRENT_USER'] ?? null;
$filterSearch = $data['SEARCH'] ?? '';
$orderBy   = $data["columns"][$data["order"][0]['column']]['data'] ?? null;
$orderType = $data["order"][0]['dir'] ?? null;
$pageSize  = $data['length'] ?? 10;
$pageNumber = $data['draw'] ?? 1;
$start     = $data['start'] ?? 0;

// Filtro para búsqueda
$where = "PRT.status = 'ACTIVE' AND PRT.element_type = 'task' and PRT.id is not null";
if ($currentUser) {
    $where .= " AND PRT.user_id = '$currentUser'";
}
if ($filterSearch !== '') {
    $where .= " AND (PR.data->>'$.YQP_CLIENT_NAME' LIKE '%$filterSearch%' OR PR.data->>'$.YQP_QUOTE_NUMBER' LIKE '%$filterSearch%' OR PR.data->>'$.YQP_STATUS' LIKE '%$filterSearch%'  OR PR.id LIKE '%$filterSearch%')";
}

// SQL principal
$sql = "
SELECT 
    PR.id AS id,
    PRT.id AS RESP_YQP_TASK_ID,
    -- data.YQP_QUOTE_NUMBER
    PR.data->>'$.YQP_QUOTE_NUMBER' AS YQP_QUOTE_NUMBER,
    -- data.YQP_CLIENT_NAME
    PR.data->>'$.YQP_CLIENT_NAME' AS YQP_CLIENT_NAME,
    -- data.YQP_INTEREST_ASSURED
    PR.data->>'$.YQP_INTEREST_ASSURED' AS YQP_INTEREST_ASSURED,
    -- data.YQP_STATUS
    PR.data->>'$.YQP_STATUS' AS YQP_STATUS,
    PRT.element_name AS task,
    -- data.YQP_USER_FULLNAME
    PR.data->>'$.YQP_USER_FULLNAME' AS YQP_USER_FULLNAME,
    -- data.UTP_END_DATE
    PR.data->>'$.YQP_REQUESTOR_NAME' AS YQP_REQUESTOR_NAME,    
    -- Última tarea activa
    COALESCE(PRT.element_name, 'No Element') AS RESP_YQP_TASK_NAME,
    -- JSON de summaryData para el modal
    JSON_OBJECT(
        'YQP_QUOTE_NUMBER', PR.data->>'$.YQP_QUOTE_NUMBER',
        'YQP_CLIENT_NAME', PR.data->>'$.YQP_CLIENT_NAME',
        'YQP_STATUS', PR.data->>'$.YQP_STATUS',
        'YQP_INTEREST_ASSURED', PR.data->>'$.YQP_INTEREST_ASSURED',
        'YQP_COUNTRY_BUSINESS', PR.data->>'$.YQP_COUNTRY_BUSINESS',
        'YQP_LANGUAGE', PR.data->>'$.YQP_LANGUAGE',
        'YQP_PRODUCT', PR.data->>'$.YQP_PRODUCT',
        'YQP_SUM_INSURED_VESSEL', PR.data->>'$.YQP_SUM_INSURED_VESSEL',
        'YQP_LIMIT_PI', PR.data->>'$.YQP_LIMIT_PI',
        'YQP_LIMIT_PI_DEDUCTIBLE', PR.data->>'$.YQP_LIMIT_PI_DEDUCTIBLE',
        'YQP_OWNER_EXPERIENCE', PR.data->>'$.YQP_OWNER_EXPERIENCE',
        'YQP_TERM', PR.data->>'$.YQP_TERM',
        'YQP_PERIOD_FROM', PR.data->>'$.YQP_PERIOD_FROM',
        'YQP_PERIOD_TO', PR.data->>'$.YQP_PERIOD_TO',
        'YQP_PIVOT_NUMBER_SLIP', PR.data->>'$.YQP_PIVOT_NUMBER_SLIP',
        'BOUND_STATUS',
            CASE
                WHEN CURDATE() BETWEEN 
                    STR_TO_DATE(PR.data->>'$.YQP_PERIOD_FROM', '%Y-%m-%d')
                AND DATE_SUB(STR_TO_DATE(PR.data->>'$.YQP_PERIOD_TO', '%Y-%m-%d'), INTERVAL 3 MONTH)
                THEN 'Already quoted this year'
                ELSE 'Not quoted this year'
            END,
        'RENEWAL_STATUS',
            CASE
                WHEN CURDATE() BETWEEN 
                    STR_TO_DATE(PR.data->>'$.YQP_PERIOD_FROM', '%Y-%m-%d')
                AND DATE_SUB(STR_TO_DATE(PR.data->>'$.YQP_PERIOD_TO', '%Y-%m-%d'), INTERVAL 3 MONTH)
                THEN 'Within coverage period'
                WHEN CURDATE() BETWEEN 
                    DATE_SUB(STR_TO_DATE(PR.data->>'$.YQP_PERIOD_TO', '%Y-%m-%d'), INTERVAL 3 MONTH)
                AND STR_TO_DATE(PR.data->>'$.YQP_PERIOD_TO', '%Y-%m-%d')
                THEN 'Eligible for renewal'
                ELSE 'Renewable'
            END
    ) AS summaryData
FROM process_requests AS PR
JOIN process_request_tokens AS PRT ON PRT.process_request_id = PR.id 
WHERE $where
";

// Orden y paginación
if (!empty($orderBy) && !empty($orderType)) {
    $sql .= " ORDER BY $orderBy $orderType";
} else {
    $sql .= " ORDER BY PR.id DESC";
}
$sql .= " LIMIT $start, $pageSize";
// Ejecutar query
$rows = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($sql));
foreach ($rows as &$row) {
    if (isset($row['summaryData']) && is_string($row['summaryData'])) {
        $decoded = json_decode($row['summaryData'], true);
        if (is_array($decoded)) {
            $row['summaryData'] = $decoded;
        }
    }
}
unset($row);
// Total de registros para paginación
$countSql = "SELECT COUNT(*) as total FROM process_requests AS PR JOIN process_request_tokens AS PRT ON PRT.process_request_id = PR.id WHERE $where";
$countRows = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($countSql));
$totalRecords = $countRows[0]['total'] ?? 0;

// Estructura de retorno para DataTables
return [
    "draw" => $pageNumber,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
    "data" => $rows
];

/**
 * Helpers (puedes traerlos desde tu librería compartida)
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken
    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
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
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

function encodeSql($query)
{
    return ["SQL" => base64_encode($query)];
}
