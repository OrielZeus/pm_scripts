<?php 
/**********************************
 * PE - LC.03 Initialize Variables
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$dataReturn["PE_SAVE_SUBMIT_LC3"] = "";

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

return $dataReturn;