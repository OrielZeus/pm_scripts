<?php 
/**********************************
 * OFF - CF.01 Pre Processing
 *
 * by Adriana Centellas
 *********************************/

// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();


//Task Code
$taskCode = "CF.01";

//Send notification
$emailType = '';
sendNotificationOffboarding($data, $taskCode, $emailType, $api);

return $dataReturn;