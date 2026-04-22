<?php 
/**********************************
 * PE - PA.01 Pre-Processing
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 *********************************/
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
$groupName = "PE - Portfolio Manager";
$groupId = getGroupId($groupName, $apiUrl);
//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);

//Return Variables
$dataReturn['PE_PORTFOLIO_MANAGER_APPROVER'] = $userLead;

//Get Name of Portfolio Manager Approver
$sqlUser = "SELECT CONCAT (U.firstname, ' ', U.lastname) AS USER_NAME
            FROM users AS U
            WHERE U.id = " . $userLead . "
                AND U.status = 'ACTIVE' 
                AND U.deleted_at IS NULL";
$rQUser = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUser));
if (count($rQUser) > 0 ) {
    $dataReturn['PE_PORTFOLIO_MANAGER_APPROVER_NAME'] = $rQUser[0]['USER_NAME'];
}

// Send Notification
$task = 'node_PA01';
$emailType = 'TO_PA01';
sendNotification($data, $task, $emailType, $api);

$task = 'node_PA01';
$emailType = 'TO_GROUP_DEAL_TEAM';
sendNotification($data, $task, $emailType, $api);

return $dataReturn;