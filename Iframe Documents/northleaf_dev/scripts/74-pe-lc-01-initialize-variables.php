<?php 
/**********************************
 * PE - LC.01 Initialize Variables
 *
 * by Cinthia Romero
 * Modify by Helen Callisaya
 * modified by Adriana Centellas
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();
$amlMessage = "";

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$apiUrl = $apiHost . $apiSql;

//Get Collections IDs
$queryCollections = "SELECT data->>'$.COLLECTION_ID' AS ID,
                            data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                     FROM collection_" . $masterCollectionID . "
                     WHERE data->>'$.COLLECTION_NAME' IN ('PE_AML_MESSAGE')";
$collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollections));

//Check if collection has been correctly configured 
if (!empty($collectionsInfo[0]["ID"])) {
    $queryAMLMessage = "SELECT data->>'$.AML_MESSAGE_HTML' AS MESSAGE
                        FROM collection_" . $collectionsInfo[0]["ID"] . "
                        WHERE data->>'$.AML_MESSAGE_STATUS' = 'Active'
                        LIMIT 1";
    $amlMessageResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryAMLMessage));
    if(!empty($amlMessageResponse[0]["MESSAGE"])) {
        $amlMessage = $amlMessageResponse[0]["MESSAGE"];
    }
}

$dataReturn["PE_AML_MESSAGE_HTML"] = $amlMessage;
$dataReturn["PE_SAVE_SUBMIT_LC1"] = "";

//Get collections IDs - HC 31-07-2024
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfoLead = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfoLead["PE_GROUP_LEADER"];

//Get Id Group
$groupName = "PE - Law Clerk";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_LEAD_LAW_CLERK'] = $userLead;

if ($data['PE_SAVE_SUBMIT_LC1'] != "SAVE") {
    $data = array_merge($data, $dataReturn);
    // Send Notification
    $task = 'node_LC02';
    //Review if it went through LL.02 to avoid sending the same notification twice
    if (!isset($data["PE_AML_LEGAL_REVIEW_COMPLETE"])) {
        sendNotification($data, $task, $emailType, $api);
    }
}

return $dataReturn;