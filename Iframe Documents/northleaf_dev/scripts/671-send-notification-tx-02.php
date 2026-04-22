<?php 
require_once("/Northleaf_PHP_Library.php");

// Send Notification TODO: move to another script
$task = 'node_TX05';
$emailType = 'Tax_Post_Close';
sendNotification($data, $task, $emailType, $api);

return [];