<?php
/*
* CQP - Watcher - Generate / Adjust N Periods for Summary Claims.
*
* Logic:
* 1. If there is historical summary claims data from previous underwriting year,
*    use that array as the base.
* 2. Append one new current period at the end.
* 3. If the total rows exceed CQP_NUMBER_OF_PERIODS, remove the oldest rows.
* 4. Recalculate the "period" field so the newest row is always period 1.
* 5. If there is no historical data, generate a fresh array with N periods.
*
* @param none
* @return (array) $summaryClaims Updated summary claims array
*
* by Adriana Centellas
*/

require_once("/CQP_Generic_Functions.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Build the summary claims array based on historical data or fresh generation


// Get form data
$inceptionDate = $data["CQP_INCEPTION_DATE"] ?? null;
$insuredCode = $data["CQP_INSURED_CODE"] ?? null;
$nPeriods = isset($data["CQP_NUMBER_OF_PERIODS"]) ? (int) $data["CQP_NUMBER_OF_PERIODS"] : 0;

$dt = new DateTime($inceptionDate);
$inceptionDateYear = $dt->format("Y");


// Validate required values
if (empty($inceptionDate) || empty($insuredCode) || $nPeriods <= 0) {
    return [];
}

// Get historical summary claims from previous  year

$sQHistorical = "SELECT data->>'$.CQP_SUMMARY_CLAIMS' AS CQP_SUMMARY_CLAIMS
    FROM process_requests
    WHERE data->>'$.CQP_INSURED_CODE' = '" . addslashes($insuredCode) . "'
      AND data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN'
	  AND EXTRACT(YEAR FROM data->>'$.CQP_INCEPTION_DATE') < " . $inceptionDateYear . "
    ORDER BY EXTRACT(YEAR FROM data->>'$.CQP_INCEPTION_DATE') DESC limit 1";

$responseHistorical = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQHistorical)) ?? [];

// Case 1: No historical data found
if (
    !is_array($responseHistorical) ||
    count($responseHistorical) === 0 ||
    empty($responseHistorical[0]["CQP_SUMMARY_CLAIMS"])
) {
    return generateSummaryClaims($nPeriods, $inceptionDate);
}

// Decode historical JSON
$historicalClaims = decodeSummaryClaimsJson($responseHistorical[0]["CQP_SUMMARY_CLAIMS"]);

// If historical JSON is invalid, fallback to fresh generation
if (!is_array($historicalClaims) || count($historicalClaims) === 0) {
    return generateSummaryClaims($nPeriods, $inceptionDate);
}

$newRow = buildNextPeriodFromHistory($historicalClaims);
if (empty($newRow["CQP_PERIOD_YEARS"])) {
    // fallback a regenerar desde cero
    return generateSummaryClaims($nPeriods, $inceptionDate);
}
$historicalClaims[] = $newRow;

// CLEAN invalid rows (optional but recommended)
$historicalClaims = array_filter($historicalClaims, function ($row) {
    return !empty($row["CQP_PERIOD_YEARS"]);
});
$historicalClaims = array_values($historicalClaims);

// EXPAND if rows are less than N
$totalRows = count($historicalClaims);
if ($totalRows < $nPeriods) {
    $missing = $nPeriods - $totalRows;

    // Generate older periods by going backwards
    for ($i = 0; $i < $missing; $i++) {
        $firstPeriodYears = $historicalClaims[0]["CQP_PERIOD_YEARS"];
        $prevPeriodYears = getPreviousPeriodYears($firstPeriodYears);

        array_unshift($historicalClaims, [
            "period" => 0,
            "form_html_viewer" => null,
            "CQP_FREQUENCY_STORAGE" => 0,
            "CQP_TOTAL_CLAIMS_STORAGE" => 0,
            "CQP_FREQUENCY_TRANSIT" => 0,
            "CQP_TOTAL_CLAIMS_TRANSIT" => 0,
            "CQP_PERIOD_YEARS" => $prevPeriodYears,
            "CQP_TOTAL_CLAIMS_COMBINED" => 0,
            "CQP_TOTAL_CLAIMS_COMBINED_VARIATION" => 0,
            "CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL" => 0,
        ]);
    }
}

// Recalculate periods on the full array
$historicalClaims = recalculatePeriods($historicalClaims);

// Populate historical combined values on the full array
$historicalClaims = populateHistoricalCombined($historicalClaims);

// Keep only the most recent N rows
if (count($historicalClaims) > $nPeriods) {
    $historicalClaims = array_slice($historicalClaims, -$nPeriods);
}

// Recalculate periods again for visible rows
$historicalClaims = recalculatePeriods($historicalClaims);

// Return final array
return $historicalClaims;

/*
* Generates an array of claims objects with period labels growing in time
* while the period number counts down from N to 1
*
* @param (int) $numPeriods Total number of periods to generate
* @param (string) $inceptionISO Inception date in ISO format
* @return (array) $claimsArray Generated claims array
*
* by Adriana Centellas
*/
function generateSummaryClaims($numPeriods, $inceptionISO)
{
    $inceptionYear = (int) substr($inceptionISO, 0, 4) + 1;
    $claimsArray = [];

    for ($i = 0; $i < $numPeriods; $i++) {
        $toYearFull = $inceptionYear - ($numPeriods - 1) + $i;
        $prevYearFull = $toYearFull - 1;

        $yyPrev = substr((string) $prevYearFull, 2);
        $yyTo = substr((string) $toYearFull, 2);
        $periodYears = $yyPrev . "-" . $yyTo;

        $periodNumber = $numPeriods - $i;

        $claimsArray[] = [
            "period" => $periodNumber,
            "form_html_viewer" => null,
            "CQP_FREQUENCY_STORAGE" => 0,
            "CQP_TOTAL_CLAIMS_STORAGE" => 0,
            "CQP_FREQUENCY_TRANSIT" => 0,
            "CQP_TOTAL_CLAIMS_TRANSIT" => 0,
            "CQP_PERIOD_YEARS" => $periodYears,
            "CQP_TOTAL_CLAIMS_COMBINED" => 0
        ];
    }

    return $claimsArray;
}

/*
* Decode a JSON string and return it as an array
*
* @param (string) $json Summary claims JSON string
* @return (array) $decodedArray Decoded summary claims array or empty array
*
* by Adriana Centellas
*/
function decodeSummaryClaimsJson($json)
{
    $decodedArray = json_decode($json, true);

    if (!is_array($decodedArray)) {
        return [];
    }

    return $decodedArray;
}

/*
* Build the next period row based on the last historical row
*
* @param (array) $historicalClaims Historical claims array
* @return (array) $newClaim New claim row for the next period
*
* by Adriana Centellas
*/
function buildNextPeriodFromHistory($historicalClaims)
{
    $lastIndex = count($historicalClaims) - 1;
    $lastRow = $historicalClaims[$lastIndex] ?? [];

    $lastPeriodYears = $lastRow["CQP_PERIOD_YEARS"] ?? "";
    $nextPeriodYears = getNextPeriodYears($lastPeriodYears);

    $newClaim = [
        "period" => 1,
        "form_html_viewer" => null,
        "CQP_FREQUENCY_STORAGE" => 0,
        "CQP_TOTAL_CLAIMS_STORAGE" => 0,
        "CQP_FREQUENCY_TRANSIT" => 0,
        "CQP_TOTAL_CLAIMS_TRANSIT" => 0,
        "CQP_PERIOD_YEARS" => $nextPeriodYears,
        "CQP_TOTAL_CLAIMS_COMBINED" => 0,
        "CQP_TOTAL_CLAIMS_COMBINED_VARIATION" => 0,
        "CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL" => 0
    ];

    return $newClaim;
}

/*
* Get the next period years label from an existing label
*
* Example:
* 25-26 becomes 26-27
*
* @param (string) $periodYears Current period years label
* @return (string) $nextPeriodYears Next period years label
*
* by Adriana Centellas
*/
function getNextPeriodYears($periodYears)
{
    if (!preg_match('/^(\d{2})-(\d{2})$/', $periodYears, $matches)) {
        return "";
    }

    $fromYear = (int) $matches[1];
    $toYear = (int) $matches[2];

    $nextFromYear = $toYear;
    $nextToYear = ($toYear + 1) % 100;

    $nextPeriodYears =
        str_pad((string) $nextFromYear, 2, "0", STR_PAD_LEFT) .
        "-" .
        str_pad((string) $nextToYear, 2, "0", STR_PAD_LEFT);

    return $nextPeriodYears;
}

/*
* Recalculate the "period" field so the newest row is always period 1
*
* Example for 6 rows:
* first row = period 6
* last row = period 1
*
* @param (array) $claimsArray Claims array
* @return (array) $claimsArray Claims array with recalculated periods
*
* by Adriana Centellas
*/
function recalculatePeriods($claimsArray)
{
    $totalRows = count($claimsArray);

    for ($i = 0; $i < $totalRows; $i++) {
        $claimsArray[$i]["period"] = $totalRows - $i;
    }

    return $claimsArray;
}

/*
* Populate CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL when it is empty.
* Rule:
* - Keep existing historical value if it is different from zero
* - Only populate from previous row if:
*   1. Current historical is empty
*   2. Current combined is greater than zero
*   3. Previous combined is greater than zero
*
* @param (array) $claimsArray Summary claims array
* @return (array) $claimsArray Updated array with historical combined values
*
* by Adriana Centellas
*/
function populateHistoricalCombined($claimsArray)
{
    $totalRows = count($claimsArray);

    for ($i = 0; $i < $totalRows; $i++) {

        if ($i === 0) continue;

        $currentHistorical = $claimsArray[$i]["CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL"] ?? 0;
        $previousCombined  = (float) ($claimsArray[$i - 1]["CQP_TOTAL_CLAIMS_COMBINED"] ?? 0);

        if ($currentHistorical == 0 && $previousCombined > 0) {
            $claimsArray[$i]["CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL"] = $previousCombined;
        }
    }

    // Force historical for newest record (period 1)
    $lastIndex = $totalRows - 1;
    if ($lastIndex > 0) {
        $claimsArray[$lastIndex]["CQP_TOTAL_CLAIMS_COMBINED_HISTORICAL"] =
            $claimsArray[$lastIndex - 1]["CQP_TOTAL_CLAIMS_COMBINED"];
    }

    return $claimsArray;
}

function getPreviousPeriodYears($periodYears)
{
    if (!preg_match('/^(\d{2})-(\d{2})$/', $periodYears, $matches)) {
        return "";
    }

    $from = (int) $matches[1];
    $to = (int) $matches[2];

    $prevTo = $from;
    $prevFrom = ($from - 1 + 100) % 100;

    return str_pad($prevFrom, 2, "0", STR_PAD_LEFT) . "-" .
           str_pad($prevTo, 2, "0", STR_PAD_LEFT);
}