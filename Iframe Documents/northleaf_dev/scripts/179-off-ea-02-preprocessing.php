<?php
/******************************** 
 * EA.02 - Preprocessing
 *
 * by Adriana Centellas
 *******************************/
require_once("/Northleaf_PHP_Library.php");
//Send notification
$taskCode = "EA.02";
$emailType = '';
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);

$dataReturn['OFF_SENT_DETAILS'] = $notificationSent;


return $dataReturn;