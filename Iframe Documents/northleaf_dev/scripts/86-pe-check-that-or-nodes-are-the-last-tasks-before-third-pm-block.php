<?php 
/********************************************************************
 * PE - Check that OR nodes are the last tasks before Third PM Block
 *
 * by Telmo Chiri
 * modified by Cinthia Romero
 * modified by Helen Callisaya
********************************************************************/
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
//Set initial values
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
$task = 'node_OR02';
$emailType = '';
$data = array_merge($data, $dataReturn);
sendNotification($data, $task, $emailType, $api);

$dataReturn['PE_OR1_OPEN_THREAD_SELF_SERVICE'] = null;
$dataReturn['PE_OR1_OPEN_THREAD_USER'] = null;
$dataReturn['PE_OR2_OPEN_THREAD_SELF_SERVICE'] = null;
$dataReturn['PE_OR2_OPEN_THREAD_USER'] = null;

return $dataReturn;