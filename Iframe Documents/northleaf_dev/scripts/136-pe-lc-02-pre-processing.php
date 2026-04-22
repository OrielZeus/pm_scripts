<?php 
/**********************************
 * PE - LC.02 Pre-Processing
 *
 * by Telmo Chiri
 *********************************/
 // Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

/********************
* Clean Variables
*
* by Cinthia Romero
********************/
$dataReturn = array();
$dataReturn["PE_SAVE_SUBMIT_LC2"] = "";

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
//Get collections IDs
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfoLead = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfoLead["PE_GROUP_LEADER"];
//Get Id Group
$groupName = "PE - Law Clerk";
$groupId = getGroupId($groupName, $apiUrl);
//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_LEAD_LAW_CLERK'] = $userLead;

if ($data['PE_SAVE_SUBMIT_LC2'] != "SAVE") {
    $data = array_merge($data, $dataReturn);
    // Send Notification
    $task = 'node_LL04';
    $emailType = 'TO_LL04';
    sendNotification($data, $task, $emailType, $api);

    $task = 'node_LL04';
    $emailType = 'TO_GROUP_DEAL_TEAM';
    sendNotification($data, $task, $emailType, $api);
}


return $dataReturn;