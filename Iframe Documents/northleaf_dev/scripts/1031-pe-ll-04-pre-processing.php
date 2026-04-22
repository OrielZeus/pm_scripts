<?php
/**********************************
 * PE - LL.03 Pre-Processing
 *
 * by Adriana Centellas
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$caseNumber = $data['_request']['case_number'] ?? 0;

// Send Notifications
$task = 'node_new_LL04';
$emailType = '';
sendNotification($data, $task, $emailType, $api);

return [];