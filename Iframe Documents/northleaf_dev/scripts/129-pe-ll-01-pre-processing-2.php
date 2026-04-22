<?php 
/**********************************
 * PE - LL.01 Pre-Processing
 *
 * by Telmo Chiri
 * modified by Cinthia Romero
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

//Send Notification
$task = 'node_LL03';
$emailType = 'RED_FLAG_NOT_COMPLETED';
sendNotification($data, $task, $emailType, $api);

return [];