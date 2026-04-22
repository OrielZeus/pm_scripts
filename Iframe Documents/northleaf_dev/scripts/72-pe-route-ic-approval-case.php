<?php 
/******************************
 * PE - Route IC Approval Case
 *
 * by Cinthia Romero
 *****************************/
//Call to generic functions
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set Initial Values
$requestID = $data["PE_CURRENT_REQUEST_ID"];
$addCommentsFlag = $data["PE_ADD_COMMENTS_FLAG"];
if ($addCommentsFlag == 0) {
    //Get current task id
    $queryTaskId = "SELECT id
                    FROM process_request_tokens 
                    WHERE process_request_id = " . $requestID . "
                        AND element_type = 'task'
                        AND status = 'ACTIVE'";
    $taskIdResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryTaskId));
    if (!empty($taskIdResponse[0]["id"])) {
        $taskId = $taskIdResponse[0]["id"];
        //Get Task definition
        $task = $apiInstance->getTasksById($taskId);
        $taskDefinitionAttributes = new \ProcessMaker\Client\Model\ProcessRequestTokenEditable();
        $taskDefinitionAttributes->setStatus('COMPLETED');
        $taskDefinitionAttributes->setData(['addToRequestData' => 'a value']);
        $result = $apiInstance->updateTask($taskId, $taskDefinitionAttributes);
    }
}
return true;