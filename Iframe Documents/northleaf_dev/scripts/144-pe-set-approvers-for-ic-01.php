<?php

/**********************************
 * PE - Send email to IC Approvers
 *
 * by Cinthia Romero
 * modified by Elmer Orihuela
 * modified by Favio Mollinedo
 * modified by Adriana Centellas
 * modified by Diego Tapia
 * cleaned by Telmo Chiri
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");
//Import Swift_SmtpTransport classes into the global namespace
require_once 'vendor/autoload.php';

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$smtpServerUrl = getenv('NORTHLEAF_SMTP_ADDRESS');
$smtpUser = getenv('NORTHLEAF_SMTP_USER');
$smtpPassword = getenv('NORTHLEAF_SMTP_PASSWORD');
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
$abePMBlockId = getenv('PE_ABE_PM_BLOCK_ID');
$abePMStartNode = getenv('PE_ABE_PM_BLOCK_START_NODE');

//Initialize Variables
$currentProcess = $data["_request"]["process_id"];
$requestId = $data['_request']['id'];
$mandatesGrid = $data["PE_MANDATES"];
$mandatesQuery = "";
$emailsStatus = array();
$responseData = array();

//Default value for Deal team senior 2 - Diego Tapia
$responseData["PE_DEAL_TEAM_SENIOR_2_LABEL"] = null;

//Check if current case belongs to PM Block
if (!empty($data["_parent"])) {
    $currentProcess = $data["_parent"]["process_id"];
}
unset($data["PE_IC_APPROVER_NAME"]);
unset($data["PE_IC_APPROVER_EMAIL"]);
unset($data["PE_IC_APPROVER_ID"]);


    //Get Collections IDs
$queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN ('PE_MANDATE_IC_APPROVERS')";
$collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
if (empty($collectionInfo["error_message"])) {
    //Get Approvers List
    foreach ($mandatesGrid as $key => $mandate) {
        if ($key == 0) {
            $mandatesQuery = "'" . $mandate["PE_MANDATE_NAME"] . "'";
        } else {
            $mandatesQuery .= ", '" . $mandate["PE_MANDATE_NAME"] . "'";
        }
    }
    //Get Approvers Information NV
    $getApproversInformation = "SELECT DISTINCT ";
    $getApproversInformation .= "CONCAT(U.firstname, ' ', U.lastname) AS APPROVER_FULL_NAME, ";
    $getApproversInformation .= "U.email AS APPROVER_EMAIL, ";
    $getApproversInformation .= "U.id AS APPROVER_ID ";
    $getApproversInformation .= "FROM users AS U ";
    $getApproversInformation .= "JOIN ( ";
    $getApproversInformation .= "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(j.user_id, '$')) AS user_id ";
    $getApproversInformation .= "FROM collection_" . $collectionInfo[0]["ID"] . ", ";
    $getApproversInformation .= "JSON_TABLE( ";
    $getApproversInformation .= "data->>'$.MIA_APPROVER_USERS_IDS', ";
    $getApproversInformation .= "'$[*]' COLUMNS (user_id VARCHAR(255) PATH '$') ";
    $getApproversInformation .= ") AS j ";
    $getApproversInformation .= "WHERE data->>'$.MIA_MANDATE_NAME' IN (" . $mandatesQuery . ") ";
    $getApproversInformation .= "AND data->>'$.MIA_APPROVER_STATUS' = 'ACTIVE' ";
    $getApproversInformation .= ") AS subquery ON FIND_IN_SET(U.id, subquery.user_id) > 0 ";

    $approversInfoResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getApproversInformation));

    if (empty($approversInfoResponse["error_message"])) {
        //Get user's names
        $blackHatUser = empty($data["PE_BLACK_HAT_REVIEW"]) ? "" : $data["PE_BLACK_HAT_REVIEW"];
        $senior2User = empty($data["PE_DEAL_TEAM_SENIOR_2"]) ? "" : $data["PE_DEAL_TEAM_SENIOR_2"];
        $usersInformation = "SELECT id AS USER_ID,
                                    CONCAT(firstname, ' ', lastname) AS USER_FULL_NAME
                                FROM users
                                WHERE id IN (" . $data["PE_DEAL_TEAM_SENIOR"] . ", " . $data["PE_DEAL_TEAM_JUNIOR"];
        if ($blackHatUser != "") {
            $usersInformation .= ", " . $blackHatUser;
        }
        if ($senior2User != "") {
            $usersInformation .= ", " . $senior2User;
        }
        $usersInformation .= ")";
        $usersInformationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($usersInformation));
        if (empty($usersInformationResponse["error_message"])) {
            foreach ($usersInformationResponse as $user) {
                if ($user["USER_ID"] == $data["PE_DEAL_TEAM_SENIOR"]) {
                    $data["PE_DEAL_TEAM_SENIOR_LABEL"] = $user["USER_FULL_NAME"];
                    $responseData["PE_DEAL_TEAM_SENIOR_LABEL"] = $user["USER_FULL_NAME"];
                }
                if ($user["USER_ID"] == $data["PE_DEAL_TEAM_SENIOR_2"]) {
                    $data["PE_DEAL_TEAM_SENIOR_2_LABEL"] = $user["USER_FULL_NAME"];
                    $responseData["PE_DEAL_TEAM_SENIOR_2_LABEL"] = $user["USER_FULL_NAME"];
                }
                if ($user["USER_ID"] == $data["PE_DEAL_TEAM_JUNIOR"]) {
                    $data["PE_DEAL_TEAM_JUNIOR_LABEL"] = $user["USER_FULL_NAME"];
                    $responseData["PE_DEAL_TEAM_JUNIOR_LABEL"] = $user["USER_FULL_NAME"];
                }
                if ($blackHatUser != "" && $user["USER_ID"] == $blackHatUser) {
                    $data["PE_BLACK_HAT_REVIEW_LABEL"] = $user["USER_FULL_NAME"];
                    $responseData["PE_BLACK_HAT_REVIEW_LABEL"] = $user["USER_FULL_NAME"];
                }
            }
        }

        // Check DD Rec
        $uploadDDRec = "Yes";
        if ($data["PE_UPLOAD_DD_REC_NA"] === false) {
            $uploadDDRec = "No";
        }
        $data["PE_UPLOAD_DD_REC_NA_LABEL"] = $uploadDDRec;

        // Check Beat Up
        $uploadBeatUp = "Yes";
        if ($data["PE_UPLOAD_BEAT_UP_NA"] === false) {
            $uploadBeatUp = "No";
        }
        $data["PE_UPLOAD_BEAT_UP_NA_LABEL"] = $uploadBeatUp;

        // Check Black Hat
        $uploadBlackHat = "Yes";
        if ($data["PE_UPLOAD_BLACK_HAT_NA"] === false) {
            $uploadBlackHat = "No";
        }
        $data["PE_UPLOAD_BLACK_HAT_NA_LABEL"] = $uploadBlackHat;


        $aApprovers = [];
        $aApproversReviewed = [];
        $collectionNameICA = "PE_IC_APPROVER_RESPONSE";
        $collectionIdICA = getCollectionId($collectionNameICA, $apiUrl);
        foreach ($approversInfoResponse as $key => $approver) {
            if (count(getICResponseUserEmailData($collectionIdICA, $apiUrl, $data["_request"]["case_number"], $approver["APPROVER_EMAIL"])) == 0) {
                $aApproversReviewed[] = [
                    "PE_PARENT_PROCESS_ID" => $currentProcess,
                    "PE_PARENT_CASE_NUMBER" => $data["_request"]["case_number"],
                    "PE_PARENT_REQUEST_ID" => $data["_request"]["id"],
                    "PE_IC_APPROVER_NAME" => $approver["APPROVER_FULL_NAME"],
                    "PE_IC_APPROVER_EMAIL" => $approver["APPROVER_EMAIL"],
                    "PE_IC_APPROVER_ID" => $approver["APPROVER_ID"],
                    "PE_IC_APPROVER_SIGNATURE" => getSignature($approver["APPROVER_ID"], $apiUrl)
                ];
            }
            $aApprovers[$key] = [
                "PE_PARENT_PROCESS_ID" => $currentProcess,
                "PE_PARENT_CASE_NUMBER" => $data["_request"]["case_number"],
                "PE_PARENT_REQUEST_ID" => $data["_request"]["id"],
                "PE_IC_APPROVER_NAME" => $approver["APPROVER_FULL_NAME"],
                "PE_IC_APPROVER_EMAIL" => $approver["APPROVER_EMAIL"],
                "PE_IC_APPROVER_ID" => $approver["APPROVER_ID"],
                "PE_IC_APPROVER_SIGNATURE" => getSignature($approver["APPROVER_ID"], $apiUrl)
            ];
        }
    }
}

//$responseData = $data;
$responseData["PE_IC_01_APPROVERS"] = $aApprovers;
$responseData["PE_IC_01_APPROVERS_REVIEWED"] = $aApproversReviewed;
$responseData["PE_ALL_APPROVERS_ANSWERED"] = 'NO';
$responseData["PE_EMAILS_SENT"] = $emailsStatus;
return $responseData;

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

/*
*   Get the signature from the user ID
*   By Adriana Centellas
*/
function getSignature($userID, $apiUrl)
{
    $userSignature = "SELECT JSON_UNQUOTE(JSON_EXTRACT(meta, '$.signature')) AS signature from users where id = " . $userID;

    // Execute the query via API call
    $userSignatureResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($userSignature));

    return $userSignatureResponse[0]["signature"];
}