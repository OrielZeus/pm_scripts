<?php
/*
* CQP - Watcher - Get Taxes based on Country
*
* by Adriana Centellas
*/

require_once("/CQP_Generic_Functions.php");

// Global Variables
$apiHost  = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql   = getEnv("API_SQL");
$apiUrl   = $apiHost . $apiSql;

// Resolve collection id
$countryCollectionId = getCollectionId('CQP_FORTE_CARGO_COUNTRIES', $apiUrl);

// User inputs
$countrySelected = $data["CQP_COUNTRY"];


// SQL Query
$sQtax = "select 
data->>'$.TAX' as TAX
from collection_" . $countryCollectionId . "
where data->>'$.COUNTRY' = '" . $countrySelected . "'
and data->>'$.STATUS' = 'ACTIVE'";

try {
    $responseTax = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQtax)) ?? [];
} catch (Throwable $e) {
    $responseTax = [];
}

return (float) $responseTax[0]["TAX"] ?? 0;