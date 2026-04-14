<?php 
/*  
 *  CQP - Watcher - Options - CQP_FORTE_CARGO_STORAGE_RATES
 *  By Adriana Centellas
 */
 
require_once("/CQP_Generic_Functions.php");

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$type = $data["TYPE"];

// Resolve collection id once
$rateLimitsId = getCollectionId('CQP_FORTE_CARGO_RATES_LIMITS', $apiUrl);

$sQlimits = "select 
data->>'$.MIN' as MIN, 
data->>'$.MAX' as MAX
from collection_" . $rateLimitsId . " 
where data->>'$.TYPE' = '" . $type . "' 
order by data->>'$.ORDER'";


try {
  $responseLimitsRate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQlimits)) ?? [];
} catch (Throwable $e) {
  $responseLimitsRate = [];
}

 return $responseLimitsRate;