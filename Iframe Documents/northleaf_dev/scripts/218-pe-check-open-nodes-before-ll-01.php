<?php
/**********************************************
 * PE - Check Closed Nodes Before LL.01
 *
 * by Favio Mollinedo
 * modified by Adriana Centellas
 * modified by Ana Castillo
 *********************************************/
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
//Set initial values
$dataReturnLL = array();
$requestId = $data["_request"]["id"];
$allNodesAccepted = "No";



//Query to verify if already LL.01
$queryCheckAlreadyExists = "SELECT COUNT(element_id) AS LL01_ELEMENT 
                          FROM process_request_tokens 
                          WHERE process_request_id = " . $requestId . "
                              AND element_id in ('node_2301', 'node_LL06')";
$openLL01 = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckAlreadyExists));

if (empty($openLL01["error_message"]) && $openLL01[0]["LL01_ELEMENT"] <= 0) {

    //Query to verify if all the tasks are closed
    $queryCheckOpenThreads = "SELECT COUNT(element_id) AS TOTAL_OPEN_TASKS 
                          FROM process_request_tokens 
                          WHERE process_request_id = " . $requestId . "
                              AND element_type = 'task'
                              AND element_id in ('node_516', 'node_DT07')
                              AND status != 'CLOSED'";
    $openThreadsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckOpenThreads));
    if (empty($openThreadsResponse["error_message"]) && $openThreadsResponse[0]["TOTAL_OPEN_TASKS"] == 0) {

        $allNodesAccepted = "Yes";

        //Query to verify if DT.05 was completed before DT.02
        $queryCheckDT04 = "SELECT 
    CASE 
        WHEN (SELECT element_id 
              FROM process_request_tokens 
              WHERE process_request_id = " . $requestId . "
              AND element_id IN ('node_516', 'node_DT07') 
              ORDER BY completed_at ASC 
              LIMIT 1) = 'node_516' 
        THEN TRUE 
        ELSE FALSE 
    END AS result;";

        $openResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckDT04));

        if ($openResponse[0]["result"] == 1) {

            //Send Notification
            $task = "DT04_to_LL02";
            $emailType = "";
            sendNotification($data, $task, $emailType, $api);
        }
    }

} else {
    $allNodesAccepted = "No";
}
$dataReturnLL["PE_NODES_DT04_DT07_ANSWERED"] = $allNodesAccepted;
return $dataReturnLL;