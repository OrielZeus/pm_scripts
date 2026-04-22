<?php 
/********************************
 * Get Leader for LL tasks
 *
 * by Helen Callisaya
 * Modified by Ana Castillo
 * Modified by Telmo Chiri
 * Modified by Cinthia Romero
 *******************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set initial values
$dataReturn = [];

//Get collections IDs
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfo["PE_GROUP_LEADER"];

//Get Id Group
$groupName = "PE - Legal Counsel Managers";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);

//Get Legal Counsel Managers users
$legalCounselUsers = getGroupUsers($groupId, $apiUrl);

//Return Variables
$dataReturn['PE_LEGAL_COUNSEL_OPTIONS'] = $legalCounselUsers;
$dataReturn['PE_RED_FLAG_LEGAL_LEADER'] = $userLead;

// Send Notification
$task = 'node_LL03';
$emailType = 'RED_FLAG_NOT_COMPLETED';
$data = array_merge($data, $dataReturn);
sendNotification($data, $task, $emailType, $api);
return $dataReturn;