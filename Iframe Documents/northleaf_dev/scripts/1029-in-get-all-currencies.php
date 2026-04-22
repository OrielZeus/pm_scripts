<?php 
/**********************************
 * Get all vendors
 *
 *********************************/

require_once("/Northleaf_PHP_Library.php");

//global varaibles
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;


//Get  options Data Source
$sql = "SELECT c.data->>'$.CURRENCY_ID' as ID,
               c.data->>'$.CURRENCY_LABEL' as LABEL      
               FROM collection_" . getCollectionId('IN_CURRENCY', $apiUrl) . " AS c
               WHERE c.data->>'$.CURRENCY_STATUS' = 'Active'
               ORDER BY c.data->>'$.CURRENCY_ORDER' ASC";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));


return $response;