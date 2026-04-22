<?php 
/******************************
 * IN - Route DHS.01 task
 * by Adriana Centellas
 *****************************/
//Call to generic functions
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set Initial Values
$requestID = $data["REQUEST_ID"];
$addCommentsFlag = $data["IN_BUTTON_FLAG"];
if ($addCommentsFlag == "1") {
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
        $taskDefinitionAttributes->setData(['IN_SUBMITTER_MANAGER_EDIT_ACTION' => 'Approved']);
        $result = $apiInstance->updateTask($taskId, $taskDefinitionAttributes);
    }
    return true;
}/*
if ($addCommentsFlag == "3") {
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
        $taskDefinitionAttributes->setData(['IN_SUBMITTER_MANAGER_EDIT_ACTION' => 'Rejected']);
        $result = $apiInstance->updateTask($taskId, $taskDefinitionAttributes);
    }
    return true;
}*/
return false;