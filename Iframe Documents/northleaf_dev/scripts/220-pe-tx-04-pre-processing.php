<?php 
/**********************************
 * PE - TX.04 - Pre processing
 *
 * by Adriana Centellas
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

if ($data['PE_SAVE_SUBMIT_TX4'] == "SUBMIT") {
    if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes"){
        // Send Notification
        $task = 'node_new_TX05';
        $emailType = 'YES';
        sendNotification($data, $task, $emailType, $api);
    } 
    elseif ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "No"){
        // Send Notification
        $task = 'node_new_TX05';
        $emailType = 'NO';
        sendNotification($data, $task, $emailType, $api);
    }
}

$dataReturn["PE_SAVE_SUBMIT_TX4"] = '';

return $dataReturn;