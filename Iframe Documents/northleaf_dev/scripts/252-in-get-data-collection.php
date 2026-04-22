<?php 
/**********************************
 * IN - Get Data Collection 
 *
 * by Favio Mollinedo
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;
$templateId = $data['TEMPLATE_ID'];

//Get collection ID
$collectionName = "Excel PMBlock Configuration";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get Collection Data
$commentLogData = getTemplateData($collectionID, $apiUrl, $templateId);

$dataReturn["TEMPLATE_DATA"] = $commentLogData;
$dataReturn["COLLECTION_ID"] = $collectionID;

return $dataReturn;

/**
 * Get data collection by its template ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $templateId - The ID of the template.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getTemplateData($ID, $apiUrl, $templateId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT PMB.data->>'$.TEMPLATE_NAME' AS TEMPLATE_NAME,
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
                        WHERE PMB.id = " . $templateId . " 
                        ORDER BY id ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}