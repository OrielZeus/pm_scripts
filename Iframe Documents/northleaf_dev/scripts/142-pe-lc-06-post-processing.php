<?php 
/**********************************
 * PE - LC.04 Post-Processing
 *
 * by Telmo Chiri
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

// Send Notification
$task = 'FINAL_TASK';
$emailType = '';
sendNotification($data, $task, $emailType, $api);

return [];