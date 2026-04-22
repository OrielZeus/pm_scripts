<?php 
/**********************************************
 * OFF - Close Threads
 *
 * by Adriana Centellas
*********************************************/

require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

return [];

/**
 * Close Thread
 *
 * @param int $openThread
 * @param object $apiInstance
 * @return none
 *
 * by Cinthia Romero
 */ 
function closeThread($openThread, $apiInstance)
{
    $apiInstance->getTasksById($openThread);
    $taskDefinitionAttributes = new \ProcessMaker\Client\Model\ProcessRequestTokenEditable();
    $taskDefinitionAttributes->setStatus('COMPLETED');
    $taskDefinitionAttributes->setData(['addToRequestData' => 'a value']);
    $apiInstance->updateTask($openThread, $taskDefinitionAttributes);
}
/*
//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
//Set initial values
$dataReturn = array();
//$requestId = $data["_request"]["id"];
$requestId = 1399;

$track =[];
//Query to verify if there is at least one task open
$queryCheckOpenThreads = "SELECT COUNT(element_id) AS TOTAL_OPEN_TASKS  
                          FROM process_request_tokens 
                          WHERE process_request_id = " . $requestId . "
                              AND (status = 'ACTIVE'
							  OR  status = 'FAILING')";

$openThreadsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckOpenThreads));

if ($openThreadsResponse[0]["TOTAL_OPEN_TASKS"] > 0) {
        $queryOpen = "SELECT id,
                               element_id,
                               user_id,
                               status
                        FROM process_request_tokens 
                        WHERE process_request_id = " . $requestId;
        $openTasksResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryOpen));
            foreach ($openTasksResponse as $openThread) {
                    $track[] = $openThread["element_id"];
                    $track[] = $openThread["status"];
                    if ($openThread["status"] == 'ACTIVE' || $openThread["status"] == 'FAILING') {
                        $track[] = "ACTIVE Route";
                        //Close thread
                       closeThread($openThread["id"],  $apiInstance);
                    } else {
                        $track[] = "CLOSED Route";
                    }
        }
    
}
$dataReturn["tracking.".date("Y-m-d H:i:s")] = $track;

return $dataReturn;
*/