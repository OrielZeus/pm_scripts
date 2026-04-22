<?php 
/**********************************
 * PE - LL.02 Post Processing
 *
 * by Adriana Centellas
 *********************************/
 
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");
//Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;


if ($data["PE_AML_LEGAL_REVIEW_COMPLETE"] == "Yes") {
    //Send notification to LC.01
    $taskA = 'LL03_to_LC01';
    $emailType = 'YES';
    sendNotification($data, $taskA, $emailType, $api);

}
if ($data["PE_AML_LEGAL_REVIEW_COMPLETE"] == "No") {
    //Send notification to LC.01
    $taskA = 'LL03_to_LC01';
    $emailType = 'NO';
    sendNotification($data, $taskA, $emailType, $api);
}