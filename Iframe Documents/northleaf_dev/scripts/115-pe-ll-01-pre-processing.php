<?php 
/**********************************
 * PE - LL.01 Pre-Processing
 *
 * by Telmo Chiri
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

// Send Notification
$task = 'node_LL01';
$emailType = '';
sendNotification($data, $task, $emailType, $api);

return [];