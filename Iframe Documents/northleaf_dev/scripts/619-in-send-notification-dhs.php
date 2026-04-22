<?php 
/*  
 *  IN - Send Notification DHS
 *  By Adriana Centellas
 */

// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

$task = "DHS01";
$emailType = "";

sendInvoiceNotification($data, $task, $emailType, $api);

return [];