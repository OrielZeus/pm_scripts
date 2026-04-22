<?php 
/**********************************************
 * OFF - Validate that all tasks were completed
 *
 * by Jhon Chacolla
 * modified by Favio Mollinedo
*********************************************/
require_once("/Northleaf_PHP_Library.php");


//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

// Check Open Tasks
$openTasksQuery  = "SELECT id, element_id, user_id, status ";
$openTasksQuery .= "FROM process_request_tokens ";
$openTasksQuery .= "WHERE process_request_id = " . $data['_request']['id'] . " ";
$openTasksQuery .= "AND element_id IN ('HR.02','EA.02','EA.03','IT.01','IT.02','IT.03','CO.01','CO.02','CO.03','CO.04','CM.01','MK.01','OS.01','OS.02')";
$openTasksQuery .= "AND status = 'ACTIVE'";
$openTasks = callApiUrlGuzzle($apiUrl, "POST", encodeSql($openTasksQuery));

return ['OFF_TASKS_COMPLETED' => empty($openTasks) ? 'YES' : 'NO'];