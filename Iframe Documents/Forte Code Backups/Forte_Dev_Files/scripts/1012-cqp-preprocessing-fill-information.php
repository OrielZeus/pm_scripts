<?php
/*  
 *  CQP - Preprocessing Fill Information
 *  By Adriana Centellas 
 */

require_once("/CQP_Generic_Functions.php");

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Resolve collection id once
$riskCollectionId = getCollectionId('CQP_FORTE_CARGO_RISKS', $apiUrl);
$countryCollectionId = getCollectionId('CQP_FORTE_CARGO_COUNTRIES', $apiUrl);
$limitsCollectionId = getCollectionId('CQP_FORTE_CARGO_STORAGE_RATES', $apiUrl);
$commoditiesCollectionId = getCollectionId('CQP_FORTE_CARGO_COMMODITIES', $apiUrl);

//Get data from request
$insuredCode = $data["CQP_INSURED_CODE"] ?? null;
$countrySelected = $data["CQP_COUNTRY"] ?? null;

//Declare arrays
$responseCountries = [];
$responseHistorical = [];
$responseCommodities = [];

//SQL query to get the country and risks collections and build country options array  
$sQCountries = "select cc.data->>'$.COUNTRY' AS COUNTRY,
cc.data->>'$.COUNTRY_CODE' AS COUNTRY_CODE,
cc.data->>'$.TAX' AS TAX,
r.data->>'$.RISK' AS RISK,
r.data->>'$.TYPE' AS TYPE
from collection_" . $countryCollectionId . " cc
left join collection_" . $riskCollectionId . " r on UPPER(r.data->>'$.COUNTRY') = UPPER(cc.data->>'$.COUNTRY')
where cc.data->>'$.STATUS' = 'ACTIVE'
and r.data->>'$.STATUS' = 'ACTIVE'";

try {
  $responseCountries = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCountries)) ?? [];
} catch (Throwable $e) {
  $responseCountries = [];
}

// Get commodities information and limits for rates
$sQcommodity = "select
c33.data->>'$.COMMODITY' as COMMODITY,
c41.data->>'$.LIMITS' as LIMITS,
c41.data->>'$.TYPE' as TYPE,
c41.data->>'$.NWP_MIN_PREMIUM' as NWP_MIN_PREMIUM 
from collection_" . $commoditiesCollectionId . " c33
inner join collection_" . $limitsCollectionId . " c41
on UPPER(c33.data->>'$.COMMODITY') = UPPER(c41.data->>'$.COMMODITIES_PROFILE')
and UPPER(c41.data->>'$.STATUS') = 'ACTIVE'";

try {
  $responseCommodities = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQcommodity)) ?? [];
} catch (Throwable $e) {
  $responseCommodities = [];
}

return [
  "CQP_COUNTRIES_OPTIONS" => $responseCountries,
  "CQP_COMMODITIES" => decodeCommodityLimits($responseCommodities),
  "CQP_SUBMIT" => "SUBMIT",
  "CQP_HIDE_SAVE" => false,
  "CQP_GENERATE_EMAIL" => ""
];


/*
* Decode the LIMITS field (JSON string) into a PHP array
* for each commodity item in CQP_COMMODITIES.
*
* @param (array) $commoditiesArray //1-10  Array of commodities, each with a LIMITS key as JSON string
*
* by Adriana Centellas
*/
function decodeCommodityLimits($commoditiesArray)
{
    // Loop through each commodity item
    foreach ($commoditiesArray as $index => $item) {
        // Check if LIMITS exists and is a string
        if (isset($item['LIMITS']) && is_string($item['LIMITS'])) {
            // Decode JSON string into PHP associative array
            $decodedLimits = json_decode($item['LIMITS'], true);
            // Only overwrite if the JSON was decoded correctly
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedLimits)) {
                $commoditiesArray[$index]['LIMITS'] = $decodedLimits;
            }
        }
    }

    return $commoditiesArray;
}