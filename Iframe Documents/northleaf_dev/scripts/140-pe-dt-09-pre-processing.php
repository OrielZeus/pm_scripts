<?php 
/**********************************
 * PE - DT.08 Pre-Processing
 *
 * by Telmo Chiri
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

// Send Notification
$task = 'node_DT11';
$emailType = 'TO_LL08';
sendNotification($data, $task, $emailType, $api);

$task = 'node_DT11';
$emailType = 'TO_GROUP_DEAL_TEAM';
sendNotification($data, $task, $emailType, $api);

return [];