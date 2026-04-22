<?php 
/**********************************
 * PE - IR.01 Pre-Processing
 *
 * by Telmo Chiri
 * modify by Helen Callisaya
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

//Set initial values  31-07-2024 HC
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get collections IDs
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfo["PE_GROUP_LEADER"];

//Get Id Group
$groupName = "PE - IR";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_LEAD_IR'] = $userLead;

// Send Notification
$task = 'node_IR01';
$emailType = '';
$data = array_merge($data, $dataReturn);
sendNotification($data, $task, $emailType, $api);

return $dataReturn;