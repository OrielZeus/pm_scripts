<?php 
/*  
 *  By Telmo Chiri
 */
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

// Send IN Notification
if (isset($data["IN_PEH01_PASS"]) && $data["IN_PEH01_PASS"] == "YES") {
   $task = "PE02";
   $emailType = "";
   sendInvoiceNotification($data, $task, $emailType, $api);
}

return [];