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
 * Get IN_EXPENSE_FUND_MANAGER list from collection
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
    $query .= 'SELECT ID.data->>"$.FUND_MANAGER_LABEL" AS LABEL, ';
    $query .= 'ID.data->>"$.DEAL_SYSTEM_ID_DB" AS ID, ';
    $query .= 'ID.data->>"$.FUNDMANAGER_ASSETCLASS.ASSET_ID" AS ASSET_LABEL, ';
    $query .= 'ID.data->>"$.FUNDMANAGER_ASSETCLASS.CURRENCY_LABEL" AS CURRENCY_LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ID ';
    $query .= 'WHERE JSON_UNQUOTE(ID.data->"$.FUNDMANAGER_ASSETCLASS.ASSET_ID") = "' . $assetId . '" ';
    $query .= 'AND JSON_UNQUOTE(ID.data->"$.FUND_MANAGER_STATUS") = "Active"';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}


//Set Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$collectionId = 65;
$assetId = "PE";

return getDealList($collectionId, $apiUrl, $assetId);