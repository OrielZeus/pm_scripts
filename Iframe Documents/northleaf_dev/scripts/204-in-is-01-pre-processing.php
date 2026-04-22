<?php 
/**********************************
 * IN - IS.01 Pre-processing
 *
 * by Favio Mollinedo
 *********************************/
$time_start = microtime(true); //////////////////time execution
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

//Get collection ID
$collectionName = "Excel PMBlock Configuration";
$collectionID = getCollectionId($collectionName, $apiUrl);
//return $collectionID;
//Get Collection Data
$commentLogData = getTemplateData($collectionID, $apiUrl);

$dataReturn["EXCEL_TEMPLATE_DATA"] = $commentLogData;
$dataReturn["COLLECTION_ID"] = $collectionID;
$dataReturn['IN_EDIT_SUBMIT'] = $data['IN_SAVE_SUBMIT'] ? null : true;
$dataReturn['IN_COMMENT'] = null;
$dataReturn['IN_CASE_TITLE'] = (isset($dataReturn['IN_CASE_TITLE'])) ? $dataReturn['IN_CASE_TITLE'] : '(The case has not yet been assigned a vendor or an invoice)';
$dataReturn['REQUEST_ID'] = $data["_request"]["id"]; // addd by Cristian Azure functionality


$time_end = microtime(true); //////////////////time execution
$execution_time = ($time_end - $time_start); //////////////////time execution
$dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];//////////////////time execution
$dataTimeExec['IN - IS.01 Pre-processing'][] = $execution_time;//////////////////time execution
$dataReturn['dataTimeExec'] = $dataTimeExec; //////////////////time execution


return $dataReturn;

/*
* Get a random answer
*
* @return string 'Yes' or 'No'
*
* by Favio Mollinedo
*/
function getRandomYesNo() {
    return rand(0, 1) === 1 ? 'Yes' : 'No';
}
/**
 * Get data collection by its template ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getTemplateData($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT PMB.data->>'$.TEMPLATE_NAME' AS TEMPLATE_NAME,
                        PMB.id as ID, 
                        PMB.data->>'$.STATIC_INFORMATION' AS STATIC_INFORMATION,
                        IFNULL(NULLIF(PMB.data->>'$.INITIAL_ROW_LETTER', 'null'), '') AS INITIAL_ROW_LETTER,
                        IFNULL(NULLIF(PMB.data->>'$.END_COLUMN_LETTER', 'null'), '') AS END_COLUMN_LETTER,
                        IFNULL(NULLIF(PMB.data->>'$.INITIAL_ROW_NUMBER', 'null'), '') AS INITIAL_ROW_NUMBER,
                        IFNULL(NULLIF(PMB.data->>'$.END_ROW_NUMBER', 'null'), '') AS END_ROW_NUMBER,
                        IFNULL(NULLIF(PMB.data->>'$.VENDOR_POSITION', 'null'), '') AS VENDOR_POSITION,
                        IFNULL(NULLIF(PMB.data->>'$.INVOICE_NUMBER_POSITION', 'null'), '') AS INVOICE_NUMBER_POSITION,
                        IFNULL(NULLIF(PMB.data->>'$.DATE_POSITION', 'null'), '') AS DATE_POSITION,
                        IFNULL(NULLIF(PMB.data->>'$.SINGLE_INVOICE_VALUES', 'null'), '') AS SINGLE_INVOICE_VALUES,
                        IFNULL(NULLIF(PMB.data->>'$.TABLE_INITIAL_ROW_LETTER', 'null'), '') AS TABLE_INITIAL_ROW_LETTER,
                        IFNULL(NULLIF(PMB.data->>'$.TABLE_END_COLUMN_LETTER', 'null'), '') AS TABLE_END_COLUMN_LETTER,
                        IFNULL(NULLIF(PMB.data->>'$.TABLE_INITIAL_ROW_NUMBER', 'null'), '') AS TABLE_INITIAL_ROW_NUMBER,
                        IFNULL(NULLIF(PMB.data->>'$.TABLE_END_ROW_NUMBER', 'null'), '') AS TABLE_END_ROW_NUMBER,
                        IFNULL(NULLIF(PMB.data->>'$.TAB_NAME', 'null'), '') AS TAB_NAME
                        FROM collection_" . $ID . " AS PMB 
                        ORDER BY id ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}