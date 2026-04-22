<?php 
/**********************************
 * PE - node_OR4 - Preprocessing
 *
 * by Favio Mollinedo
 * modified by Adriana Centellas
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes"){
// Send Notification
$task = 'node_OR4';
$emailType = 'YES';
sendNotification($data, $task, $emailType, $api);
} 
elseif ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "No"){
// Send Notification
$task = 'node_OR4';
$emailType = 'NO';
sendNotification($data, $task, $emailType, $api);
}

return [];