<?php
/**********************************
 * PE - DT.01 Post Processing
 *
 * by Telmo Chiri
 * modified by Favio Mollinedo
 * modified by Ana Castillo
 * modified by Adriana Centellas
 * modified by Diego Tapia
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");
//Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
// Set User of DT.01
$dataReturn["PE_USER_DT01"] = ($data['_user']['id'] ?? '');

//Set Request DT01 ID - Ana
$dataReturn["PE_DT01_REQUEST_ID"] = $data["_request"]["id"];

//Default value for Deal team senior 2 - Diego Tapia
$dataReturn["PE_DEAL_TEAM_SENIOR_2_LABEL"] = null;

//Get user's names
$blackHatUser = empty($data["PE_BLACK_HAT_REVIEW"]) ? "" : $data["PE_BLACK_HAT_REVIEW"];
$usersInformation = "SELECT id AS USER_ID,
                            CONCAT(firstname, ' ', lastname) AS USER_FULL_NAME
                        FROM users
                        WHERE id IN (" . $data["PE_DEAL_TEAM_SENIOR"] . ", " . $data["PE_DEAL_TEAM_JUNIOR"];
if ($blackHatUser != "") {
    $usersInformation .= ", " . $blackHatUser;
}
$usersInformation .= ")";
$usersInformationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($usersInformation));
if (empty($usersInformationResponse["error_message"])) {
    foreach ($usersInformationResponse as $user) {
        if ($user["USER_ID"] == $data["PE_DEAL_TEAM_SENIOR"]) {
            $dataReturn["PE_DEAL_TEAM_SENIOR_LABEL"] = $user["USER_FULL_NAME"];
        }
        if ($user["USER_ID"] == $data["PE_DEAL_TEAM_SENIOR_2"]) {
            $dataReturn["PE_DEAL_TEAM_SENIOR_2_LABEL"] = $user["USER_FULL_NAME"];
        }
        if ($user["USER_ID"] == $data["PE_DEAL_TEAM_JUNIOR"]) {
            $dataReturn["PE_DEAL_TEAM_JUNIOR_LABEL"] = $user["USER_FULL_NAME"];
        }
        if ($blackHatUser != "" && $user["USER_ID"] == $blackHatUser) {
            $dataReturn["PE_BLACK_HAT_REVIEW_LABEL"] = $user["USER_FULL_NAME"];
        }
    }
}
//Get Target Close with format
$date = $data["PE_TARGET_CLOSE_DATE"];
if ($date != null) {
    $date_format = explode('-', $date);
    $dataReturn["format_target_close_date"] = $date_format[1] . '/' . $date_format[2] . '/' . $date_format[0];
}
//Capitalize the first letter.
$dataReturn["PE_BRIEF_DEAL_DESCRIPTION"] = ucfirst($data["PE_BRIEF_DEAL_DESCRIPTION"]);
// Send Notification
$task = 'DEAL_TEAM';
$emailType = 'FROM_DT01';
$data = array_merge($data, $dataReturn);
sendNotification($data, $task, $emailType, $api);

// Collection Data
$collectionAR = "PE_IC_APPROVER_RESPONSE";
$collectionARID = getCollectionId($collectionAR, $apiUrl);

$ic01ReviewedArray = $data["PE_IC_01_APPROVERS_REVIEWED"];
$countApprovals = 0;
if (!empty($ic01ReviewedArray)) {
    foreach ($ic01ReviewedArray as $ic01ReviewedData) {
        if (count(getICResponseUserEmailData($collectionARID, $apiUrl, $data["_request"]["case_number"], $ic01ReviewedData["PE_IC_APPROVER_EMAIL"])) > 0) {
            $countApprovals++;
        }
    }
}

/*$dataReturn["PE_ALL_APPROVERS_ANSWERED"] = !empty($data["PE_IC_01_APPROVERS_REVIEWED"]) 
                                            ? (count($ic01ReviewedArray) == $countApprovals ? "YES" : "NO")
                                            : (!empty($data["PE_ALL_APPROVERS_ANSWERED"]) 
                                                ? $data["PE_ALL_APPROVERS_ANSWERED"] 
                                                : "NO");*/

$dataReturn["PE_ALL_APPROVERS_ANSWERED"] = "NO";
// Change PE_MANDATE_NAME if Mandate is Co Investor
$aPeMandates = $data["PE_MANDATES"];
foreach ($aPeMandates as &$mandate) {
    if ($mandate["PE_MANDATE_CO_INVESTOR"] === "YES") {
        $mandate["PE_MANDATE_NAME"] = $mandate["PE_MANDATE_NAME"] . ": " . $mandate["PE_MANDATE_CO_INVESTOR_NAME"];
    }
}
$dataReturn["PE_MANDATES"] = $aPeMandates;


//In case request is reapproval with no IC get PDF IC id into the data

$shouldRun = empty($data["REAPPROVAL_IC"]) || $data["REAPPROVAL_IC"] === false;

if ($shouldRun) {
    //Get collections IDs
    $collectionNames = array("HISTORY_FILES_BY_CASE", "PE_UPLOAD_FILE_HISTORY_CONTROLLER");
    $collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
    $requestId = $data["_request"]["id"];
    $caseNumber = $data["_request"]["case_number"];
    $targetFilename = "IC_Approval_Document.pdf";

    $idCollectionHistoryFilesByCase = $collectionsInfo["HISTORY_FILES_BY_CASE"] ?? false;

    $requestFiles = $api->requestFiles()->getRequestFiles($requestId)['data'];

    foreach ($requestFiles as $file) {
        if ($file['file_name'] === $targetFilename) {
            $fileId = $file['id'];
            break;
        }
    }
    //Save current ID Info into collection File History to show on Dashboard
    if (isset($fileId)) {
        $insertResponseUrl = $apiHost . "/collections/" . $idCollectionHistoryFilesByCase . "/records";
        $insertValues = [
            'data' => [
                "HFC_CASE_NUMBER" => $caseNumber,
                "HFC_REQUEST_ID" => $requestId,
                "HFC_FILE_ID" => $fileId,
                "HFC_FILE_NAME" => $targetFilename,
                "HFC_USER_ID" => 1,
                "HFC_USER_NAME" => "Admin User",
                "HFC_TASK_ID" => "abeNode",
                "HFC_TASK_NAME" => "IC.01 Approval Capture",
                "HFC_STATUS" => 'ACTIVE'
            ]
        ];
        callApiUrlGuzzle($insertResponseUrl, "POST", $insertValues);
        $dataReturn["PDF_IC_APPROVAL_ID"] = $fileId;
    }
}
// Set active reapproval to NO so that the flow continues normally
$dataReturn["PE_ACTIVE_REAPPROVAL"] = "NO";

return $dataReturn;

/**
 * Get IC Apporval Response data from a specified collection by its ID.
 *
 * @param (int) $ID - The ID of the collection.
 * @param (string) $apiUrl - The API URL for making the request.
 * @param (int) $caseNumber - The case number.
 * @param (string) $approverEmail - The user approver email.
 * @return array - An array of collection records with 'ID' key, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getICResponseUserEmailData($ID, $apiUrl, $caseNumber, $approverEmail)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.IAR_REQUEST_ID' AS IAR_REQUEST_ID
                        FROM collection_" . $ID . " AS IAR
                        WHERE IAR.data->>'$.IAR_CASE_NUMBER' = '" . $caseNumber . "' and 
                        IAR.data->>'$.IAR_APPROVER_EMAIL' = '" . $approverEmail . "'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}