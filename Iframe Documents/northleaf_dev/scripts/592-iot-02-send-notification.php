<?php 
/*  
 *  By Telmo Chiri
 */
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

// Send IN Notification
if (isset($data["IN_IOTH01_PASS"]) && $data["IN_IOTH01_PASS"] == "YES") {
   $task = "IOT02";
   $emailType = "";
   sendInvoiceNotification($data, $task, $emailType, $api);
}

return [];