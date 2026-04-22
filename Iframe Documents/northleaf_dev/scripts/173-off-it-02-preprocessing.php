<?php 
/*************************************
 * OFF - IT.02 Pre Processing
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
$taskCode = "IT.02";

//Get Task Lead
$userTaskLead = getTaskLeadGroup($collectionsID, $taskCode, $apiUrl, $data["OFF_OFFICE_LOCATION"]["OFFICE_DESCRIPTION"]);

$dataReturn['OFF_IT_TEAM_APPROVER'] = $userTaskLead;
$data['OFF_IT_TEAM_APPROVER'] = $userTaskLead;


//Send notification
$emailType = '';
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);

$dataReturn['OFF_SENT_DETAILS'] = $notificationSent;

return $dataReturn;