<?php 
/*
*  Edit Target Date
*
*  by Telmo Chiri
*/
require_once("/Northleaf_PHP_Library.php");

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiSql = getenv('API_SQL');
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');

if (!$data['caseNumber']) {
    return [
        "status" => false,
        "message" => 'Case not selected.'
    ];
}
//Get all Request ID by Case Number
$sqlGetRequests = "SELECT DISTINCT(PR.id) AS REQUEST_ID, 
                            PR.case_number AS CASE_NUMBER,
                            PR.STATUS
                    FROM process_requests AS PR
                    WHERE PR.case_number = '" . $data['caseNumber'] . "'";
$responseGetRequests = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($sqlGetRequests));
$countEdit = 0;
// Loop requests
foreach($responseGetRequests as $request) {
    //Get Data of Request
    $urlUpdateData = $apiHost . '/requests/' . $request['REQUEST_ID'] . '?include=data';
    $dataRequest = callApiUrlGuzzle($urlUpdateData, "GET", []);
    //UPDATE DATA ON REQUEST
    $dataRequestUpdate['PE_TARGET_CLOSE_DATE'] = $data['newDate'];
    $dataRequestUpdate['TARGET_EDIT_DATE_HISTORY'] = $dataRequest['data']['TARGET_EDIT_DATE_HISTORY'] ?? [];
    $newRecord = [
        'OLD_DATE' => $data['currentDate'],
        'NEW_DATE' => $data['newDate'],
        'MODIFICATION_REASON' => $data['reason'],
        'MODIFICATION_DATE' => date("Y-m-d H:i:s"),
        'MODIFIED_BY' => $data['currentUserId']
    ];
    array_push($dataRequestUpdate['TARGET_EDIT_DATE_HISTORY'], $newRecord);
    
    $dataToUpdate['data'] = $dataRequestUpdate;
    $urlUpdateData = $apiHost . '/requests/' . $request['REQUEST_ID'];
    $resUpdate = callApiUrlGuzzle($urlUpdateData, "PUT", $dataToUpdate);
    // Get Tasks from Request ID 
    $urlTasks = $apiHost . '/tasks?process_request_id='.$request['REQUEST_ID'] . '?include=data';
    $dataTasks = callApiUrlGuzzle($urlTasks, "GET", []);
    //Update Data in Requests of Tasks
    if (is_array($dataTasks['data'])) {
        foreach ($dataTasks['data'] as $task) {
            if ($task['status'] == 'ACTIVE') {
                $urlUpdateTaks = $apiHost . '/tasks/'. $task['id'];
                $dataToUpdate = [];
                $dataToUpdate['data'] = $dataRequestUpdate;
                $resultUpdateTask = callApiUrlGuzzle($urlUpdateTaks, "PUT", $dataToUpdate);
            }
        }
    }
    $countEdit++;
}

return [
    "status" => (count($responseGetRequests) == $countEdit),
    "errorMessage" => count($responseGetRequests) == $countEdit ? "Something went wrong, please contact your system administrator" : ""
];