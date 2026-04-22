<?php 
/**********************************
 * PE - TX.03 Pre-Processing
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 *********************************/
 // Import Generic Functions
 require_once("/Northleaf_PHP_Library.php");

 //Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$caseNumber = $data['_request']['case_number'] ?? 0;

//Get Mandates info updated
$collection = getCollectionId('PE_ALLOCATION_INFO', $apiUrl);
$sqlMandates = "select data->>'$.ALLOCATION_VARIABLE' as ALLOCATION_VARIABLE from collection_" . $collection . " where data->>'$.CASE_NUMBER' = " . $caseNumber . " order by id desc limit 1";
$approversResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlMandates));
$input = json_decode($approversResponse[0]["ALLOCATION_VARIABLE"], true);
// Send Notification
$task = 'node_TX06';
$emailType = '';
sendNotification($data, $task, $emailType, $api);

//Return mandates from collection
return ['PE_MANDATES' => $input];