<?php 
/*********************************
 * PE - Save IC Approver Response
 *
 * by Cinthia Romero
 * Modified by Telmo Chiri
 ********************************/
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');

//Get IC Approvers collection ID
$queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                      FROM collection_" . $masterCollectionID . "
                      WHERE data->>'$.COLLECTION_NAME' IN ('PE_IC_APPROVER_RESPONSE')";
$collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
if (empty($collectionInfo["error_message"])) {
    //Set collection url
    $insertResponseUrl = $apiHost . "/collections/" . $collectionInfo[0]["ID"] . "/records";

    $data["PE_IC_COMMENTS"] = ($data["PE_IC_COMMENTS"] === "null" || $data["PE_IC_COMMENTS"] === "") ? null : $data["PE_IC_COMMENTS"];

    //Set array of values to insert in collection
    $insertValues = [
        'data' => [
            "IAR_REQUEST_ID" => $data['_request']['id'],
            "IAR_CASE_NUMBER" => $data["PE_PARENT_CASE_NUMBER"],
            "IAR_APPROVER_EMAIL" => $data["PE_IC_APPROVER_EMAIL"],
            "IAR_APPROVER_FULL_NAME" => $data["PE_IC_APPROVER_NAME"],
            "IAR_APPROVER_COMMENTS" => $data["PE_IC_COMMENTS"]
        ]
    ];
    callApiUrlGuzzle($insertResponseUrl, "POST", $insertValues);
    //tlx
    /*
    // Update Data in Parent Request
    $apiInstance = $api->processRequests();
    $processRequestId = $data['PE_PARENT_REQUEST_ID'];
    $include = 'data';
    $processRequest = $apiInstance->getProcessRequestById($processRequestId, $include);
    $dataParent = $processRequest->getData();

    $dataParent['PE_ALL_APPROVERS_ANSWERED'] = 'YES';

    $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
    $processRequestEditable->setData($dataParent);

    $apiInstance->updateProcessRequest($processRequestId, $processRequestEditable);
    // Restore event in Parent Request
    $eventNode = 'node_wait_approvers_ic_01';
    $updateEventUrl = $apiHost . '/requests/' . $data['PE_PARENT_REQUEST_ID'] . '/events/' . $eventNode;
    callApiUrlGuzzle($updateEventUrl, 'POST');
    */ //tlx
}
return true;