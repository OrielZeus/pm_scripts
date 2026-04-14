<?php
/*
* CQP - Watcher - Generate / Adjust N Periods Table using historical ROWS and AS_IF.
* It builds the requested number of period rows based on inception date.
* If historical information exists, it appends AS_IF as the last row and preserves
* the newest values by copying them from bottom to top into the newly generated structure.
* If there is no valid historical information, it returns the base generated rows.
*
* @param none
* @return (array) $baseRows Updated periods rows array
*
* by Adriana Centellas
*
*/
require_once("/CQP_Generic_Functions.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Get data from form
$inceptionDate   = $data["CQP_INCEPTION_DATE"] ?? null;
$nPeriodsRaw     = $data["CQP_NUMBER_OF_PERIODS"] ?? null;
$nPeriods        = (int) $nPeriodsRaw;
$insuredCode     = $data["CQP_INSURED_CODE"] ?? null;

$dt = new DateTime($inceptionDate);
$inceptionDateYear = $dt->format("Y");


// Basic validation
if (empty($inceptionDate) || $nPeriods <= 0) {
    return [];
}

// Always generate the requested base structure first
$baseRows = generatePeriodsRows($nPeriods, $inceptionDate);

// Get historical rows from latest bound request
$historicalRows = getHistoricalPeriodsRows($insuredCode, $inceptionDateYear, $apiUrl);

// If there is no valid historical data, keep the current logic and return base rows
if (!is_array($historicalRows) || count($historicalRows) === 0) {
    return $baseRows;
}

// Merge historical values into the new structure keeping newest rows
return mergeHistoricalRowsIntoBaseRows($baseRows, $historicalRows);

/*
* Get historical ROWS_INFO and AS_IF from the latest BOUND/QUOTE TAKEN request.
* AS_IF is expected to be an array with one row, and that row will be appended
* as the last row of ROWS_INFO when present.
*
* @param (string) $insuredCode //1-10
* @param (string|int) $inceptionDateYear
* @return (array) $historicalRows Historical rows including AS_IF as last row or empty array
*
* by Adriana Centellas
*
*/
function getHistoricalPeriodsRows($insuredCode, $inceptionDateYear, $apiUrl)
{
    if (empty($insuredCode) || $inceptionDateYear === null || $inceptionDateYear === '') {
        return [];
    }

    $sql = "
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(data, '$.ROWS')) AS ROWS_INFO,
            JSON_UNQUOTE(JSON_EXTRACT(data, '$.AS_IF')) AS AS_IF
        FROM process_requests
        WHERE JSON_UNQUOTE(JSON_EXTRACT(data, '$.CQP_INSURED_CODE')) = '" . $insuredCode . "'
          AND EXTRACT(YEAR FROM data->>'$.CQP_INCEPTION_DATE') < " . $inceptionDateYear . "
          AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.CQP_CARGO_CURRENT_STATUS')) = 'BOUND/QUOTE TAKEN'
        ORDER BY EXTRACT(YEAR FROM data->>'$.CQP_INCEPTION_DATE') DESC
        LIMIT 1
    ";

    $result = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql)) ?? [];

    if (!is_array($result) || !isset($result[0])) {
        return [];
    }

    $rowInfoRaw = $result[0]["ROWS_INFO"] ?? null;
    $asIfRaw    = $result[0]["AS_IF"] ?? null;

    $rowsInfo = decodeJsonArray($rowInfoRaw);
    $asIfRows = decodeJsonArray($asIfRaw);

    // If AS_IF is null or empty, the requirement says to keep the current logic
    if (count($asIfRows) === 0) {
        return [];
    }

    // Append the first AS_IF row as the last row of historical rows
    $asIfRow = $asIfRows[0] ?? null;
    if (is_array($asIfRow)) {
        $rowsInfo[] = $asIfRow;
    }

    return is_array($rowsInfo) ? $rowsInfo : [];
}

/*
* Decode a JSON string into an array.
* Returns an empty array if the value is null, empty, invalid, or not an array.
*
* @param (string|null) $jsonValue //1-10
* @return (array) $decodedArray Decoded array or empty array
*
* by Adriana Centellas
*
*/
function decodeJsonArray($jsonValue)
{
    if ($jsonValue === null || $jsonValue === '') {
        return [];
    }

    $decodedArray = json_decode($jsonValue, true);

    if (!is_array($decodedArray)) {
        return [];
    }

    return $decodedArray;
}

/*
* Merge historical rows into the generated base rows.
* It keeps the newest rows by copying from the end of the historical array
* into the end of the base array.
*
* @param (array) $baseRows //1-10
* @param (array) $historicalRows
* @return (array) $baseRows Base rows populated with historical values
*
* by Adriana Centellas
*
*/
function mergeHistoricalRowsIntoBaseRows($baseRows, $historicalRows)
{
    if (!is_array($baseRows) || count($baseRows) === 0) {
        return [];
    }

    if (!is_array($historicalRows) || count($historicalRows) === 0) {
        return $baseRows;
    }

    $baseCount       = count($baseRows);
    $historicalCount = count($historicalRows);
    $copyCount       = min($baseCount, $historicalCount);

    for ($i = 0; $i < $copyCount; $i++) {
        $srcIndex = $historicalCount - 1 - $i;
        $dstIndex = $baseCount - 1 - $i;

        if (!isset($historicalRows[$srcIndex]) || !is_array($historicalRows[$srcIndex])) {
            continue;
        }

        $baseRows[$dstIndex] = copyHistoricalValuesToRow($baseRows[$dstIndex], $historicalRows[$srcIndex]);
    }

    return $baseRows;
}

/*
* Copy only business values from a historical row into a base row.
* It does not overwrite period numbering or FROM/TO dates generated for the current request.
*
* @param (array) $baseRow //1-10
* @param (array) $historicalRow
* @return (array) $baseRow Updated row with historical values
*
* by Adriana Centellas
*
*/
function copyHistoricalValuesToRow($baseRow, $historicalRow)
{
    $fieldsToCopy = [
        'CQP_GWP_USD',
        'CQP_BROKERAGE_COMISSION',
        'CQP_TAX',
        'CQP_UNDERWRITING_EXPENSES',
        'CQP_CLAIM_USD',
        'CQP_NWP',
        'CQP_LOSS_RADIO',
        'CQP_COMBINED_RADIO',
        'CQP_FORTE_SHARE',
        'form_html_viewer'
    ];

    foreach ($fieldsToCopy as $field) {
        $baseRow[$field] = $historicalRow[$field] ?? null;
    }

    return $baseRow;
}

/*
* Parse a date string into a DateTimeImmutable in UTC.
* It supports both 'Y-m-d' and 'd-M-Y' formats.
*
* @param (string) $dateValue //1-10
* @return (DateTimeImmutable) $dateUTC Parsed date in UTC
*
* by Adriana Centellas
*
*/
function parseDateUTC($dateValue)
{
    $timezone = new DateTimeZone('UTC');

    $formats = [
        'Y-m-d',
        'd-M-Y'
    ];

    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $dateValue, $timezone);
        if ($date instanceof DateTimeImmutable) {
            return $date;
        }
    }

    return new DateTimeImmutable($dateValue . ' 00:00:00', $timezone);
}

/*
* Add a number of years to a given DateTimeImmutable in UTC.
*
* @param (DateTimeImmutable) $date //1-10
* @param (int) $years
* @return (DateTimeImmutable) $newDate Date plus given years
*
* by Adriana Centellas
*/
function addYearsUTC(DateTimeImmutable $date, $years)
{
    return $date->modify((int) $years . ' year');
}

/*
* Format a DateTimeInterface into 'dd-MMM-yyyy' string.
*
* @param (DateTimeInterface) $date //1-10
* @return (string) $displayDate Date string in dd-MMM-yyyy format
*
* by Adriana Centellas
*/
function toDisplayDate(DateTimeInterface $date)
{
    return $date->format('d-M-Y');
}

/*
* Generate period rows based on number of periods and inception date.
* It builds the array, chains FROM/TO dates backwards, reverses the final order,
* and renumbers periods after reversing.
*
* @param (int) $numPeriods //1-10
* @param (string) $inceptionDate
* @return (array) $reversed Reversed array with generated period rows
*
* by Adriana Centellas
*
*/
function generatePeriodsRows($numPeriods, $inceptionDate)
{
    $numPeriods = (int) $numPeriods;
    if ($numPeriods <= 0) {
        return [];
    }

    $arr = [];

    for ($idx = 0; $idx < $numPeriods; $idx++) {
        $arr[$idx] = buildEmptyPeriodRow($idx + 1);
    }

    $toDate   = parseDateUTC($inceptionDate);
    $fromDate = addYearsUTC($toDate, -1);

    $arr[0]['CQP_TO']   = toDisplayDate($toDate);
    $arr[0]['CQP_FROM'] = toDisplayDate($fromDate);

    for ($i = 1; $i < $numPeriods; $i++) {
        $previousFrom = $arr[$i - 1]['CQP_FROM'];
        $toDate       = parseDateUTC($previousFrom);
        $fromDate     = addYearsUTC($toDate, -1);

        $arr[$i]['CQP_TO']   = toDisplayDate($toDate);
        $arr[$i]['CQP_FROM'] = toDisplayDate($fromDate);
    }

    $reversed = array_reverse($arr);
    $total    = count($reversed);

    foreach ($reversed as $index => &$item) {
        $item['period'] = $total - $index;
    }
    unset($item);

    return $reversed;
}

/*
* Build an empty period row structure with all expected keys.
*
* @param (int) $periodNumber //1-10
* @return (array) $row Empty period row structure
*
* by Adriana Centellas
*
*/
function buildEmptyPeriodRow($periodNumber)
{
    return [
        'period'                    => $periodNumber,
        'form_html_viewer'          => null,
        'CQP_FROM'                  => null,
        'CQP_TO'                    => null,
        'CQP_GWP_USD'               => 0,
        'CQP_BROKERAGE_COMISSION'   => 0,
        'CQP_TAX'                   => 0,
        'CQP_UNDERWRITING_EXPENSES' => 0,
        'CQP_CLAIM_USD'             => 0,
        'CQP_NWP'                   => 0,
        'CQP_LOSS_RADIO'            => 0,
        'CQP_COMBINED_RADIO'        => 0,
        'CQP_FORTE_SHARE'           => 0
    ];
}