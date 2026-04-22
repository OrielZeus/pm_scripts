<?php 
/*  
 *  By Telmo Chiri
 */

// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

// Send IN Notification
$task = "CO01";
$emailType = "";

if (isset($config["emailType"]) && $config["emailType"] == "FROM_COH01") {
   $emailType = "FROM_COH01";
} else {
   if ($data["IN_CORP_ASSIGNED"] == "YES") {
      $emailType = "USER";
   } else if ($data["IN_CORP_ASSIGNED"] == "NO") {
      $emailType = "GROUP";
   }
}

sendInvoiceNotification($data, $task, $emailType, $api);

return [];