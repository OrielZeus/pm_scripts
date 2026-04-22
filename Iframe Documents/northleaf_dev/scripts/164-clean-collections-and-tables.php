<?php 
/**********************************************
 * Clean Collections and Tables
 *
 * by Telmo Chiri
*********************************************/
require_once("/Northleaf_PHP_Library.php");

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$dataReturn = array();

// Tables names to clean
$tablesToClean = [
    "collection_16",     // BULK_REASSIGNMENT_HISTORY
    "collection_19",     // CANCEL_CASE_HISTORY
    "collection_24",      // HISTORY_FILES_BY_CASE
    "SEND_NOTIFICATION_LOG",
    "PMB_DAILY_DIGEST_LOGS",
    //"SIGNATURE_CONFIGURATION"   //ONLY ONCE
];

foreach($tablesToClean as $table) {
    $query = "TRUNCATE TABLE " . $table;
    $status = 'FAIL';
    $result = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    if (count($result) == 0) {
        $status = 'DONE';
    }
    $dataReturn[$table] = $status;
}
return $dataReturn;