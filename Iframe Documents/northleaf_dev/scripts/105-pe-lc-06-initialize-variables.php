<?php 
/**********************************
 * PE - LC.04 Initialize Variables
 *
 * by Telmo Chiri
 * modified by Helen Callisaya
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
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
$groupName = "PE - Law Clerk";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_LEAD_LAW_CLERK'] = $userLead;

// get List of Uploaded Files
$historyFiles = getListUploadedFiles($data["_request"]["case_number"]);
foreach($historyFiles as &$file) {
    // If user is Admin, change the name
    if ($file['USER_ID'] == '1') { $file['USER_NAME'] = '-'; }
}
$dataReturn["PE_HISTORY_FILE"] = $historyFiles;

// Send Notification
$task = 'node_LC06';
$emailType = '';
$data = array_merge($data, $dataReturn);
sendNotification($data, $task, $emailType, $api);

return $dataReturn;