<?php 
/*********************************
 * PE - Verify approvers response
 *
 * by Cinthia Romero
 ********************************/
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');

//Set initial values
$emailsSent = $data["PE_EMAILS_SENT"];
$caseNumber = $data["_request"]["case_number"];
$dataReturn = array();
$allApproversAnswered = "NO";

//Get all abe requests to check
$requestsToCheck = array();
foreach ($emailsSent as $key=>$email) {
    if ($email["STATUS"] == "OK") {
        $requestsToCheck[] = $key;
    }
}
$requestsToCheckQuery = implode(",",  $requestsToCheck);
$totalABERequestsSuccess = count($requestsToCheck);
if ($totalABERequestsSuccess > 0) {
    //Get IC Approvers Response collection ID
    $queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                          FROM collection_" . $masterCollectionID . "
                          WHERE data->>'$.COLLECTION_NAME' IN ('PE_IC_APPROVER_RESPONSE')";
    $collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
    if (empty($collectionInfo["error_message"])) {
        //Obtain total responses for current case
        $checkApproversResponse = "SELECT COUNT(data->>'$.IAR_REQUEST_ID') AS TOTAL_RESPONSES, MAX(created_at) AS LAST_CREATED_AT
                                    FROM collection_" . $collectionInfo[0]["ID"] . "
                                   WHERE 
                                   -- data->>'$.IAR_REQUEST_ID' IN (" . $requestsToCheckQuery. ")
                                      -- AND 
                                      data->>'$.IAR_CASE_NUMBER' = " . $caseNumber;
        $approversResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($checkApproversResponse));
        if (!empty($approversResponse[0]["TOTAL_RESPONSES"])) {
            $totalSavedResponses = $approversResponse[0]["TOTAL_RESPONSES"];
            if ($totalABERequestsSuccess == $totalSavedResponses) {
                $allApproversAnswered = "YES";
                $lastCreatedAtDate = $approversResponse[0]["LAST_CREATED_AT"];
            }
        }
    }
}
$dataReturn["PE_ALL_APPROVERS_ANSWERED"] = $allApproversAnswered;
$dataReturn["PE_DATE_APPROVAL"] = $lastCreatedAtDate;
return $dataReturn;