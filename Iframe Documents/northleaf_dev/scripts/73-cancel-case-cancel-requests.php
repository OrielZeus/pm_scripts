<?php 
/*
*  Cancel Case
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
$apiUrl = $apiHost . $apiSql;
$idCancelCaseHistoryCollection = getenv('CANCEL_CASE_HISTORY_ID');
$urlCreateCancelCasesHistoryCollection = $apiHost."/collections/$idCancelCaseHistoryCollection/records";

if (!$data['dataCase']['CASE_NUMBER']) {
    return [
        "status" => false,
        "message" => 'Case Number Empty.'
    ];
}
// Get All Active Requests
$query = "SELECT PR.data->>'$._request.case_number' as case_number, 
	            PR.id, PR.process_id, PR.status, PR.name
        FROM process_requests AS PR
        WHERE PR.case_number = '".$data['dataCase']['CASE_NUMBER']."'
        ORDER BY id DESC";
$responseRequests = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
if (!empty($responseRequests[0]["id"])) {
    $countCancel = 0;
    // Loop requests
    foreach ($responseRequests as $request) {
        $requestID = $request['id'];
        //Update request
        $requesInfo = $api->processRequests()->getProcessRequestById($requestID);
        $requesInfo['status'] = "CANCELED";
        if(empty($requesInfo['data'])){
            $requesInfo['data'] = "CANCELED";
        }
        $requesInfo['name'] .=  "CANCELED";
        $api->processRequests()->updateProcessRequest($requestID, $requesInfo);
        // Save in Collection
        $dataCancelCasesHistory = [
            'CCH_CASE_NUMBER' => $data['dataCase']['CASE_NUMBER'],
            'CCH_REQUEST_ID' => $requestID,
            'CCH_USER_ID' => $data['currentUserId'],
            'CCH_REASON' => $data['cancelCaseReason'],
            'CCH_DATE' => date('Y-m-d- H:i:s')
        ];
        
        $responseGuzzle = callApiUrlGuzzle($urlCreateCancelCasesHistoryCollection, "POST", $dataCancelCasesHistory);
        $countCancel++;
    }
    return [
        "status" => (count($responseRequests) == $countCancel),
    ];
}
return [];