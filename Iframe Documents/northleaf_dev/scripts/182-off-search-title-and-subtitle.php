<?php 
/*****************************************************
* OFF - Search Title and Subtitle
*
* By Adriana Centellas
*****************************************************/
require_once("/Northleaf_PHP_Library.php");

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Declare current task id variable
$taskId = $data["TASK_ID"];

//Get current task element id
$apiInstance = $api->tasks();
$task = $apiInstance->getTasksById($taskId);
$taskCode = $task->getElementId();
$taskTitle = $task->getElementName();

$dataReturn["OFF_TASK_TITLE"] = $taskTitle;
$dataReturn["OFF_TASK_SUBTITLE"] = $taskTitle;

return $dataReturn;