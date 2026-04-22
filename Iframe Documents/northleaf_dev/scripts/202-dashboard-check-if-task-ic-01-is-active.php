<?php 
/************************
 *  Check if task IC.01 is active by Case Number
 *  by Telmo Chiri
 ************************/
require_once("/Northleaf_PHP_Library.php");
//Validation
if (!$data['caseNumber']) {
    return [
        "status" => false,
        "message" => 'Case not selected.'
    ];
}
//Read Data
$caseNumber = $data['caseNumber'];

// Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$idIC01Process = getenv('PE_IC01_APRROVAL_PROCESS_ID');

$dataReturn = array();

// Get All IC01 Active Requests
$query = "SELECT PR.case_number as case_number, 
                PR.id, 
                PR.process_id, 
                PR.status, 
                PR.name
        FROM process_requests AS PR
        WHERE PR.process_id = " . $idIC01Process . " AND PR.case_number = '".$caseNumber."' AND PR.status = 'ACTIVE'
        ORDER BY id DESC";
$responseRequests = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

$dataReturn = [
        "status" => "OK",
        "IC_active_task" => "NO"
    ];

if (!empty($responseRequests[0]["id"])) {
    $dataReturn = [
        "status" => "OK",
        "IC_active_task" => "YES"
    ];
}

return $dataReturn;