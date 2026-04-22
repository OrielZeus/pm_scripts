<?php 
/********************************
 * TX.01 Address tax requirements and engage outside counsel
 *
 * by Helen Callisaya
 * modified by Telmo Chiri
 *******************************/
require_once("/Northleaf_PHP_Library.php");

$dataReturn = [];
$dataReturn['PE_SAVE_SUBMIT_TX1'] = "";

// Send Notification
$task = 'node_TX04';
$emailType = 'TO_TX04';
sendNotification($data, $task, $emailType, $api);

$task = 'node_TX04';
$emailType = 'TO_GROUP_DEAL_TEAM';
sendNotification($data, $task, $emailType, $api);

return $dataReturn;