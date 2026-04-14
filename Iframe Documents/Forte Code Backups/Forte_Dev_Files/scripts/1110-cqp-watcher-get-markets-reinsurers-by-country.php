<?php
/*
* CQP - Watcher - Get Distribution Markets by country
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
$reinsurerCollectionId = getCollectionId('CQP_FORTE_CARGO_REINSURER', $apiUrl);

// User inputs
$countrySelected = $data["CQP_COUNTRY"];

// SQL Query
$sQDistribution = "SELECT
    data->>'$.CQP_MAX_CAP' AS CQP_MAX_CAP,
    data->>'$.CQP_REQUIRED' AS CQP_REQUIRED,
    data->>'$.CQP_REINSURER' AS CQP_REINSURER,
    data->>'$.CQP_REINSURER_NAME' AS CQP_REINSURER_NAME
FROM collection_" . $reinsurerCollectionId . "
WHERE UPPER(data->>'$.CQP_STATUS') = 'ACTIVE'
AND JSON_CONTAINS(
        data,
        '\"" . $countrySelected . "\"',
        '$.CQP_COUNTRIES'
    );";

//Get response
try {
    $responseDistribution = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQDistribution)) ?? [];
} catch (Throwable $e) {
    $responseDistribution = [];
}

// If the query returned results
if (is_array($responseDistribution) && count($responseDistribution) > 0) {
  foreach ($responseDistribution as $item) {
    //Format data as needed on screen
        $CQP_MARKETS[] = [
        "taken" => true,
        "CQP_REINSURER" => $item['CQP_REINSURER'],
        "CQP_MAXIMUN_CAP" => (float) $item['CQP_MAX_CAP'],
        "CQP_REINSURER_FULLNAME" => $item['CQP_REINSURER_NAME'],
        "CQP_USD" => 0,
        "CQP_FORTE_SHARE" => 0,
        "CQP_REQUIRED" => $item["CQP_REQUIRED"],
        "EDIT_DIST" => ""
        ];
  }
}

return $CQP_MARKETS;