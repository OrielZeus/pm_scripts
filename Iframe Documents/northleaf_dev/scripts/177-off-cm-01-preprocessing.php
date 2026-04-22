<?php 
/*************************************
 * OFF - CM.01 Pre Processing
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
$taskCode = "CM.01";

//Get Task Lead
$userTaskLead = getTaskLeadGroup($collectionsID, $taskCode, $apiUrl, $data["OFF_OFFICE_LOCATION"]["OFFICE_DESCRIPTION"]);

$data['OFF_LEGAL_COMPLIANCE_APPROVER'] = $userTaskLead;
$dataReturn['OFF_LEGAL_COMPLIANCE_APPROVER'] = $userTaskLead;

//Send notification
$emailType = '';
sendNotificationOffboarding($data, $taskCode, $emailType, $api);

return $dataReturn;