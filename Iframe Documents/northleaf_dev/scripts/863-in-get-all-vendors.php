<?php 
/**********************************
 * Get all vendors
 *
 * by Daniel Aguilar
 *********************************/

require_once("/Northleaf_PHP_Library.php");

//global varaibles
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;


//Get Vendor options Data Source
$sqlVendors = "SELECT c.data->>'$.VENDOR_SYSTEM_ID_ACTG' as ID,
                CONCAT(c.data->>'$.VENDOR_LABEL', '|', IFNULL(c.data->>'$.EXPENSE_VENDOR_CURRENCY', ''), '|', IFNULL(c.data->>'$.EXPENSE_VENDOR_NAME_CITY', '')) as LABEL      
               FROM collection_" . getCollectionId('IN_VENDORS', $apiUrl) . " AS c
               WHERE c.data->>'$.VENDOR_STATUS' = 'Active'
               ORDER BY LABEL ASC";
$responseVendors = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendors));


return $responseVendors;