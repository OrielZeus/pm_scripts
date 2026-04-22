<?php 
/**********************************
 * IN - DHS.01 Pre Processing 
 *
 * by Favio Mollinedo
 * modified by Adriana Centellas
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;
$requestId = $data['_request']['id'];

//Get collection ID
$collectionName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get Collection Data
$commentLogData = getCommentsLog($collectionID, $apiUrl, $requestId);

$dataReturn["IN_COMMENT_LOG"] = $commentLogData;

//Clear variables - Jhon Chacolla
$dataReturn["IN_SAVE_SUBMIT"] = null;
$dataReturn["fakeSaveCloseButton"] = null;
$dataReturn["saveButtonFake"] = null;
$dataReturn["submitButtonFake"] = null;
$dataReturn["validateForm"] = null;
$dataReturn["saveForm"] = null;
$dataReturn["saveFormClose"] = null;
$dataReturn["validation"] = null;
$dataReturn["IN_VENDOR_NAME"] = $data["vendorInformation"]["0"]["VENDOR_LABEL"];
$dataReturn["IN_CASE_NUMBER"] = $data["_request"]["case_number"];


return $dataReturn;

/**
 * Get the comment logs from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getCommentsLog($ID, $apiUrl, $requestId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT LOG.data->>'$.IN_CL_REQUEST_ID' AS REQUEST_ID,
                        LOG.data->>'$.IN_CL_USER' AS IN_COMMENT_USER,
                        LOG.data->>'$.IN_CL_USER_ID' AS IN_COMMENT_USER_ID,
                        LOG.data->>'$.IN_CL_ROLE' AS IN_COMMENT_ROLE,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_APPROVAL', 'null'), '') AS IN_COMMENT_APPROVAL,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_COMMENT_SAVED', 'null'), '') AS IN_COMMENT_SAVED,
                        LOG.data->>'$.IN_CL_DATE' AS IN_COMMENT_DATE
                        FROM collection_" . $ID . " AS LOG
                        WHERE LOG.data->>'$.IN_CL_REQUEST_ID' = " . $requestId . " 
                        ORDER BY IN_COMMENT_DATE ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}