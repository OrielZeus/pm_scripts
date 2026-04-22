<?php 
/*************************************
 * OFF - EA.05 Pre Processing
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
$taskCode = "EA.05";

//Get Task Lead
$userTaskLead = getTaskLeadGroup($collectionsID, $taskCode, $apiUrl);

$dataReturn['OFF_CORPORATE21'] = $userTaskLead;
$data['OFF_CORPORATE21'] = $userTaskLead;

//Send notification
$emailType = '';
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);

$dataReturn['OFF_SENT_DETAILS'] = $notificationSent;

return $dataReturn;