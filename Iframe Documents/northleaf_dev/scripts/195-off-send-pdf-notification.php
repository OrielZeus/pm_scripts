<?php 
/*  
 * OFF - Send PDF Notification
 *  
 * Script to send the final PDF to the group HR Manager
 *
 * By Adriana Centellas
 */

require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Task Code
$taskCode = "SEND_PDF";

//Send notification
$emailType = '';
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);

$dataReturn['OFF_SENT_DETAILS'] = $notificationSent;

 return $dataReturn;