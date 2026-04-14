<?php 
/*  
 *  Get Risk by type and country
 *  By Adriana Centellas 
 */

require_once("/CQP_Generic_Functions.php");

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Expecting $data["CQP_COUNTRY"] to be a string like "Argentina"
$country = $data["CQP_COUNTRY"] ?? '';
$type = $data["CQP_TYPE"] ?? '';

if ($country === '' || $country === null || $type === '' || $type === null) {
    // Early-return an empty array if no country provided
    return [];
}

// Normalize/trim to avoid accidental mismatches like " Argentina "
$country = trim($country);
$country = addslashes($country); // minimal escaping as a guard rail

$type = trim($type);
$type = addslashes($type); // minimal escaping as a guard rail

// Resolve collection id once
$collectionId = getCollectionId('CQP_FORTE_CARGO_RISKS', $apiUrl);

$responseVendors = [];

$sqlVendors = "SELECT c.data->>'$.RISK' as RISK      
                FROM collection_" . $collectionId . " AS c
                WHERE c.data->>'$.COUNTRY' = '". $country . "' 
                AND c.data->>'$.TYPE' like '%".$type."%'";

try {
    // If encodeSql() is a requirement of your backend, keep it.
    // Otherwise you can send $sqlVendors directly.
    $responseVendors = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendors)) ?? [];
} catch (Throwable $e) {
    // Log the exception appropriately in your environment
    // For safety, return an empty array to the caller
    $responseVendors = [];
}

return $responseVendors[0]["RISK"];