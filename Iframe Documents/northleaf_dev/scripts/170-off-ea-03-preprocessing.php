<?php 
/*************************************
 * OFF - EA.03 Pre Processing
 *
 * by Adriana Centellas
 ***********************************/

require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get collections IDs
$collectionName ="OFF_TASK_LEADER";
$collectionsID = getCollectionId($collectionName, $apiUrl);

//Task Code
$taskCode = "EA.03";

//Get Task Lead
$userTaskLead = getTaskLeadGroup($collectionsID, $taskCode, $apiUrl, $data["OFF_OFFICE_LOCATION"]["OFFICE_DESCRIPTION"]);

//Get User Leader
$userLead = getUserLeadGroup($collectionsID, $groupId, $apiUrl);
$dataReturn['OFF_EXECUTIVE_ASSISTANT_APPROVER'] = $userTaskLead;
$data['OFF_EXECUTIVE_ASSISTANT_APPROVER'] = $userTaskLead;


//Send notification
$emailType = '';
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);

$dataReturn['OFF_SENT_DETAILS'] = $notificationSent;

return $dataReturn;