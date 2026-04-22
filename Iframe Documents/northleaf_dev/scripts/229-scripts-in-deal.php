<?php 
/**********************************
 * Scripts IN_DEAL
 *
 * by Manuel Monroy
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Manuel Monroy
 */ 
function callApiUrlGuzzle($url, $method, $sendData)
{
    global $apiToken, $apiHost;
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request($method, $url, $headers, json_encode($sendData));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $executionResponse = json_decode($res, true);
    return $executionResponse;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Manuel Monroy
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

/*
 * Get Deal list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @param (String) $assetId (PE, PC, INFRA, CORP)
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
function getDealList($collectionId, $apiUrl, $assetId) 
{
    $query = '';
    $query .= 'SELECT ID.data->>"$.DEAL_LABEL" AS LABEL, ';
    $query .= 'ID.data->>"$.DEAL_SYSTEM_ID_DB" AS ID, ';
    $query .= 'ID.data->>"$.DEAL_ASSETCLASS.ASSET_ID" AS ASSET_LABEL, ';
    $query .= 'ID.data->>"$.DEAL_CURRENCY.CURRENCY_LABEL" AS CURRENCY_LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ID ';
    $query .= 'WHERE JSON_UNQUOTE(ID.data->"$.DEAL_ASSETCLASS.ASSET_ID") = "' . $assetId . '" ';
    $query .= 'AND JSON_UNQUOTE(ID.data->"$.DEAL_STATUS") = "Active"';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @param (String) $assetId (PE, PC, INFRA, CORP)
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
function getDealList2($collectionId, $apiUrl, $assetId) 
{
    $query = '';
    $query .= 'SELECT ID.data->>"$.DEAL_LABEL" AS LABEL, ';
    $query .= 'ID.data->>"$.DEAL_SYSTEM_ID_DB" AS ID, ';
    $query .= 'ID.data->>"$.DEAL_ASSETCLASS.ASSET_ID" AS ASSET_LABEL, ';
    $query .= 'ID.data->>"$.DEAL_CURRENCY.CURRENCY_LABEL" AS CURRENCY_LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ID ';
    $query .= 'WHERE JSON_UNQUOTE(ID.data->"$.DEAL_ASSETCLASS.ASSET_ID") = "' . $assetId . '" ';
    $query .= 'AND JSON_UNQUOTE(ID.data->"$.DEAL_STATUS") = "Active"';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
function getExpenseCorpProject($collectionId, $apiUrl) 
{
    $query = '';
    $query .= 'SELECT CAST(ID.data->>"$.NL_COMPANY_SYSTEM_ID_ACTG" AS UNSIGNED) AS ID, ';
    $query .= 'ID.data->>"$.EXPENSE_CORPORATE_LABEL" AS LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ID ';
    $query .= 'WHERE JSON_UNQUOTE(ID.data->"$.NL_CORPPROJ_STATUS") = "Active" ';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}


//Set Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$collectionId = 68;
$assetId = "PE";

//return getDealList($collectionId, $apiUrl, $assetId);
return getExpenseCorpProject($collectionId, $apiUrl);