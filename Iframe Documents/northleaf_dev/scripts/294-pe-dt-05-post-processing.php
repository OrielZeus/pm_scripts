<?php 
/**********************************************
 * DT.05 - Post processing
 *
 * by Adriana Centellas
*********************************************/
require_once("/Northleaf_PHP_Library.php");

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
//Set initial values
$requestId = $data["_request"]["id"];

//Query to verify if DT.02 is open completed
$queryCheckDT07 = "select id, element_id, status
                        from process_request_tokens where process_request_id = " .$requestId."
                        and element_id in ('node_DT07')
                        order by completed_at desc";
$openResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckDT07));

if ($openResponse[0]["status"] == "CLOSED"){
    $emailType = "";
    $task = "Complete_to_LL02";
    sendNotification($data, $task, $emailType, $api);
}
return [];