<?php
/*  
 *  CQP - Preprocessing Fill First Information
 *  By Adriana Centellas 
 *  Modify  by Diego Tapia
 */

require_once("/CQP_Generic_Functions.php");

//Global Variables
$apiHost = getEnv("API_HOST");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;
$countryCollectionId = getCollectionId('CQP_FORTE_CARGO_COUNTRIES', $apiUrl);
$riskCollectionId = getCollectionId('CQP_FORTE_CARGO_RISKS', $apiUrl);
$responseCountries = [];

//SQL query to get the country and risks collections and build country options array  
$sQCountries = "select cc.data->>'$.COUNTRY' AS COUNTRY,
r.data->>'$.RISK' AS RISK,
r.data->>'$.TYPE' AS TYPE
from collection_" . $countryCollectionId . " cc
inner join collection_" . $riskCollectionId . " r on UPPER(r.data->>'$.COUNTRY') = UPPER(cc.data->>'$.COUNTRY')
where cc.data->>'$.STATUS' = 'ACTIVE'
and r.data->>'$.STATUS' = 'ACTIVE'";

try {
  $responseCountries = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCountries)) ?? [];
} catch (Throwable $e) {
  $responseCountries = [];
}

$underwriter = callApiUrlGuzzle($apiUrl, "POST", encodeSql("select * from users where id =" . $data["_request"]["user_id"]))[0];

return [
  "CQP_COUNTRIES_OPTIONS" => $responseCountries,
  "CQP_STARTER_USER" => isset($data["CQP_CLONE_REQUEST_STARTER"]) ? $data["CQP_CLONE_REQUEST_STARTER"] : $data["_request"]["user_id"],
  "CQP_CLONE_REQUEST_STARTER" => null,
  "CQP_CARGO_CURRENT_STATUS" => "WORKING",
  "CQP_UNDERWRITER_USER" => $underwriter["firstname"] . " " . $underwriter["lastname"]
];