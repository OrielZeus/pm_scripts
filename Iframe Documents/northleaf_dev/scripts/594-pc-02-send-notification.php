<?php 
/*  
 *  By Telmo Chiri
 */
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

// Send IN Notification
if (isset($data["IN_PCH01_PASS"]) && $data["IN_PCH01_PASS"] == "YES") {
   $task = "PC02";
   $emailType = "FROM_PCH01";
   sendInvoiceNotification($data, $task, $emailType, $api);
}

return [];