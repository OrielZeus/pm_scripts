<?php
// Global variables
$apiHost = getenv("API_HOST");
$apiToken = getenv("API_TOKEN");
$apiSql = '/admin/package-proservice-tools/sql';

/*
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postdata
 * @param (bool ) $contentFile
 * @return (Array) $res
 *
 * by Elmer Orihuela
 */
function apiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
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
        if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/*
 * Encode SQL
 *
 * @param (String) $string
 * @return (Array) $variablePut
 *
 * by Elmer Orihuela
 */
function encodeSql($string)
{
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
}


// Ensure CURRENT_USER is present in the data
if (!isset($data['CURRENT_USER']) || $data['CURRENT_USER'] === '') {
    return [
        'status' => 'error',
        'message' => 'No CURRENT_USER provided.',
        'visibility' => false,
        'data' => []
    ];
}

// Ensure LIMIT is present in the data
if (!isset($data['LIMIT']) || !is_numeric($data['LIMIT']) || $data['LIMIT'] <= 0) {
    return [
        'status' => 'error',
        'message' => 'LIMIT value is required and must be a positive number.',
        'visibility' => false,
        'data' => []
    ];
}
// Initialize WHERE clauses and filters
$optionalWhereClauses = [];
$filtersAdded = false;

// Build optional WHERE clauses based on $data
if ($data['BY_REQUEST'] === "true") {
    $optionalWhereClauses[] = "(C12.data->>'$.FORTE_REQUEST' IS NULL OR C12.data->>'$.FORTE_REQUEST' = '' OR C12.data->>'$.FORTE_REQUEST' = 'null')";
    $filtersAdded = true;
}

if ($data['BY_SOURCE'] === "true") {
    $optionalWhereClauses[] = "(C12.data->>'$.YQP_SOURCE' IS NULL OR C12.data->>'$.YQP_SOURCE' = '' OR C12.data->>'$.YQP_SOURCE' = 'null')";
    $filtersAdded = true;
}

if ($data['BY_ITEM'] === "true") {
    $optionalWhereClauses[] = "(C12.data->>'$.YQP_SLIP_DOCUMENT_NAME' IS NULL OR C12.data->>'$.YQP_SLIP_DOCUMENT_NAME' = '' OR C12.data->>'$.YQP_SLIP_DOCUMENT_NAME' = 'null')";
    $filtersAdded = true;
}

if ($data['BY_LINE_BUSINESS'] === "true") {
    $optionalWhereClauses[] = "(C12.data->>'$.YQP_LINE_BUSINESS' IS NULL OR C12.data->>'$.YQP_LINE_BUSINESS' = '' OR C12.data->>'$.YQP_LINE_BUSINESS' = 'null')";
    $filtersAdded = true;
}

// If no filters were added, return an error response
if (!$filtersAdded) {
    return [
        'status' => 'error',
        'message' => 'No fields to filter. Please select at least one filter.',
        'visibility' => false,
        'data' => []
    ];
}

// Concatenate optional WHERE clauses with OR
$optionalWhereClause = implode(' OR ', $optionalWhereClauses);

// Add mandatory CURRENT_USER filter using AND
$whereClause = "($optionalWhereClause) AND PRT.user_id = '{$data['CURRENT_USER']}'";
// Build the SQL query
$sql = "SELECT DISTINCT ";
$sql .= "C12.data->>'$.FORTE_REQUEST_ORDER' AS Id_Order, ";
$sql .= "C12.data->>'$.FORTE_REQUEST' AS Request, ";
$sql .= "C12.data->>'$.YQP_SLIP_DOCUMENT_NAME' AS Item, ";
$sql .= "C12.data->>'$.YQP_LINE_BUSINESS' AS Line_Of_Business, ";
$sql .= "C12.data->>'$.YQP_CLIENT_NAME' AS Client_Name_Assured, ";
$sql .= "C12.data->>'$.YQP_SOURCE' AS Source, ";
$sql .= "C12.data->>'$.YQP_TYPE' AS Type, ";
$sql .= "C12.id AS Record_Id, ";
$sql .= "PRT.user_id AS Case_Initiator, ";
$sql .= "false AS SELECTED ";
$sql .= "FROM collection_12 AS C12 ";
$sql .= "LEFT JOIN process_request_tokens AS PRT ";
$sql .= "ON C12.data->>'$.FORTE_REQUEST' = PRT.process_request_id ";
$sql .= "WHERE $whereClause";
// Append LIMIT to the SQL query
$sql .= "LIMIT {$data['LIMIT']}";
// Execute the query using the API
$url = $apiHost . $apiSql;
$responseSql = apiGuzzle($url, "POST", encodeSql($sql));
// Check if the response contains data
if (empty($responseSql) || (is_array($responseSql) && count($responseSql) === 0)) {
    return [
        'status' => 'error',
        'message' => 'No records found.',
        'visibility' => false,
        'data' => []
    ];
}
// Return the response
return [
    'status' => 'success',
    'message' => 'Query executed successfully.',
    'visibility' => true,
    'data' => $responseSql
];