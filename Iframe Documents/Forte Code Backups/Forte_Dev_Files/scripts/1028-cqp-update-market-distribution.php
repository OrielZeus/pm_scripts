<?php 
/*  
 *  Watcher to update Distributions
 *  By Adriana Centellas
 */

require_once("/CQP_Generic_Functions.php");

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Resolve collection id once
$reinsuredCollectionId = getCollectionId('CQP_FORTE_CARGO_REINSURER', $apiUrl);
$distributionCollectionId = getCollectionId('CQP_FORTE_CARGO_MARKETS_DISTRIBUTION', $apiUrl);

// SQL query to retrieve all active reinsurers
$sQReinsurer = "
        SELECT
  r.data->>'$.REINSURER'              AS REINSURER,
  dist.distribution                   AS CQP_DISTRIBUTION
FROM collection_" . $reinsuredCollectionId . " r 
JOIN collection_" . $distributionCollectionId . " dm 
  ON dm.data->>'$.CQP_N_MARKETS' = '4'
CROSS JOIN JSON_TABLE(
  dm.data,
  '$.CQP_GENERAL_DISTRIBUTION[*]'
  COLUMNS (
    market        VARCHAR(200) PATH '$.CQP_MARKET',
    distribution  INT          PATH '$.CQP_DISTRIBUTION'
  )
) AS dist
  ON dist.market = r.data->>'$.REINSURER'
WHERE COALESCE(r.data->>'$.STATUS','') = 'ACTIVE'
    ";

try {

  $responseReinsurer = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQReinsurer)) ?? [];

} catch (Throwable $e) {

  $responseReinsurer = [];

}

// Initialize the result array
$CQP_MARKETS = [];

// If the query returned results
if (is_array($responseReinsurer) && count($responseReinsurer) > 0) {
  foreach ($responseReinsurer as $item) {
    // Add one object per REINSURER in the same structure
    $CQP_MARKETS[] = [
      "CQP_REINSURER" => $item['REINSURER'],
      "form_html_viewer" => null,
      "CQP_MAXIMUN_CAP" => 0,
      "CQP_REINSURANCE_DISTRIBUTION" => $item['DISTRIBUTION'],
      "CQP_USD" => 0,
      "CQP_FORTE_SHARE" => 0
    ];
  }

return $CQP_MARKETS;