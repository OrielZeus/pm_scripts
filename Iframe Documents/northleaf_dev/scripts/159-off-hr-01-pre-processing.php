<?php 
/*************************************
 * OFF - HR.01 Pre Processing
 *
 * by Adriana Centellas
 ***********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;


//Get options for dropdowns

$getHiringManagerGroupID = getGroupId("Hiring Manager", $apiUrl);
$hiringManagerUsers = getGroupUsers($getHiringManagerGroupID, $apiUrl);

$getExecutiveAssistantGroupID = getGroupId("Executive Assistant", $apiUrl);
$executiveAssistantUsers = getGroupUsers($getExecutiveAssistantGroupID, $apiUrl);

$collectionName = "OFFICE_LOCATION";
$collectionID = getCollectionId($collectionName, $apiUrl);
$officeLocationOptions = getOfficeLocationOptions($collectionID, $apiUrl);

$dataReturn["OFF_HIRING_MANAGER_OPTIONS"] = $hiringManagerUsers;
$dataReturn["OFF_EXECUTIVE_ASSISTANT_OPTIONS"] = $executiveAssistantUsers;
$dataReturn["OFF_OFFICE_LOCATION_OPTIONS"] = $officeLocationOptions;

$dataReturn["OFF_JOB_TITLE"] = null;
$dataReturn["OFF_OFFICE_LOCATION"] = null;
$dataReturn["OFF_MANAGER"] = null;


$dataReturn["OFF_SAVE_SUBMIT"] = "";

$dataReturn['HOST_URL'] = getenv('HOST_URL');
$dataReturn['REQUEST_ID'] = $data['_request']['id'];

/**
 * Fetch office location options from the collection using its ID
 *
 * @param (String) $ID - The ID of the collection
 * @param (String) $apiUrl - The API URL for the request
 * @return (Array) $collectionRecords - An array of office location records
 *
 * by Adriana Centellas
 */
function getOfficeLocationOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.OFFICE_UID' AS OFFICE_UID,
                        data->>'$.OFFICE_USER' AS OFFICE_USER,
                        data->>'$.OFFICE_ORDER' AS OFFICE_ORDER,
                        data->>'$.OFFICE_STATUS' AS OFFICE_STATUS,
                        data->>'$.OFFICE_CURRENCY' AS OFFICE_CURRENCY,
                        data->>'$.OFFICE_DESCRIPTION' AS OFFICE_DESCRIPTION,
                        data->>'$.OFFICE_SHORT_DESCRIPTION' AS OFFICE_SHORT_DESCRIPTION
                        FROM collection_" . $ID . " where data->>'$.OFFICE_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    return $collectionRecords;
}


return $dataReturn;