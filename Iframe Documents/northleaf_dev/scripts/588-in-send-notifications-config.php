<?php 
/*  
 * Send Invoice Notification
 * By  Telmo Chiri
 */

// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

// Get parameters
$task = $config["task"] ?? "";
$emailType = $config["emailType"] ?? "";

// Validate data
if ($task !== "") {
   // Send IN Notification
   sendInvoiceNotification($data, $task, $emailType, $api);
}

return [];