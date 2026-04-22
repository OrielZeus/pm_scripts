<?php 
/**********************************
 * PE - OR.03 Pre-Processing
 *
 * by Telmo Chiri
 * modified by Helen Callisaya
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set initial values  01-08-2024 HC
$dataReturn = array();

//Get collections IDs  31-07-2024 HC
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfo["PE_GROUP_LEADER"];

//Get Id Group
$groupName = "PE Cash Management";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_LEAD_OPERATIONS'] = $userLead;

// Send Notification
$task = 'node_OR04';
$emailType = 'TO_OR04';
$data = array_merge($data, $dataReturn);
sendNotification($data, $task, $emailType, $api);

$task = 'node_OR04';
$emailType = 'TO_GROUP_DEAL_TEAM';
sendNotification($data, $task, $emailType, $api);

return $dataReturn;