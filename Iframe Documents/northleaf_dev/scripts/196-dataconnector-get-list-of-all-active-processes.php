<?php 
/********************************
 * Get list of active processes
 *
 * By Cinthia Romero
 *******************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Load autoloader
require_once 'vendor/autoload.php';

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Cinthia Romero
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
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$queryGetProcesses = "SELECT P.id, 
                             P.name
                      FROM processes AS P
                      INNER JOIN process_categories AS PC
                          ON PC.id = P.process_category_id
                      WHERE P.status = 'ACTIVE' 
                          AND P.is_template = 0 
	                      AND PC.is_system = 0
                      ORDER BY name ASC";
$processesList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetProcesses));
return $processesList;