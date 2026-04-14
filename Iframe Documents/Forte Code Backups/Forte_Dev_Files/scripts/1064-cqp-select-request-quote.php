<?php 
/*  
 *  Close requests not taken in quote
 *  By Diego Tapia
 */
require_once("/CQP_Generic_Functions.php");
$apiInstanceCollections = $api->collections();
$apiInstanceRequest = $api->processRequests();
$apiInstanceTask = $api->tasks();

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Get request related to the quote
$collectionInsured = getCollectionId('CQP_FORTE_CARGO_INSURED', $apiUrl);
$getRecords = $apiInstanceCollections->getRecords($collectionInsured, 'data.CQP_SEARCH_CODE="' . $data["CQP_CODE"] . '" and data.CQP_BROKER_STATUS = "OPEN"', 10000)->getData();

foreach ($getRecords as $row) {
    // Update insured collection with the new information
    $record = new \ProcessMaker\Client\Model\RecordsEditable();
    $rowStatus = $row["data"]["CQP_REQUEST_ID"] != $data["CQP_REQUEST_SELECTED"] ? "CLOSE" : "SELECTED";

    $record->setData([
        'CQP_BROKER_STATUS' => $rowStatus
    ]);

    $result = $apiInstanceCollections->patchRecord($collectionInsured, $row["data"]["id"], $record);

    // Update/close the request that are part of the quote
    $processRequest = $apiInstanceRequest->getProcessRequestById($row["data"]["CQP_REQUEST_ID"], "data");
    $dataNew = $processRequest->getData();
    $dataNew['CQP_BROKER_STATUS'] = $rowStatus;
    $dataNew['CQP_CARGO_CURRENT_STATUS'] = "QUOTED NOT TAKEN";
    $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();

    if ($row["data"]["CQP_REQUEST_ID"] == $data["CQP_REQUEST_SELECTED"]) {
        $dataNew['CQP_FX_RATE'] = $data['CQP_FX_RATE'];
        $dataNew['CQP_SUBMIT_BROKER'] = "TAKEN";
        $dataNew['CQP_CARGO_CURRENT_STATUS'] = "BOUND/QUOTE TAKEN";
    }

    $processRequestEditable->setData($dataNew);
    $apiInstanceRequest->updateProcessRequest($row["data"]["CQP_REQUEST_ID"], $processRequestEditable);
    
    if ($row["data"]["CQP_REQUEST_ID"] != $data["CQP_REQUEST_SELECTED"]) {
        $processRequestEditable->setStatus("CANCELED");
        $apiInstanceRequest->updateProcessRequest($row["data"]["CQP_REQUEST_ID"], $processRequestEditable);
    }
}

// Complete task if the request is on broker task
$result = $apiInstanceTask->getTasks($data["CQP_REQUEST_SELECTED"], null, "Waiting Response from Broker", "id", "desc", "data");

foreach ($result->getData() as $task) {
    if ($task["status"] == "ACTIVE") {
        $process_request_token_editable = new \ProcessMaker\Client\Model\ProcessRequestTokenEditable();
        $process_request_token_editable->setData(['CQP_SUBMIT_BROKER' => 'TAKEN']);
        $process_request_token_editable->setStatus('COMPLETED');
        $result = $apiInstanceTask->updateTask($task["id"], $process_request_token_editable);
    }
}


$attemps = 30;
$sleep = 7;
$counter = 0;
$correctTask = false;
$node = false;

// Get selected quote request
while ($counter < $attemps) {
    $resultNewTask = $apiInstanceTask->getTasks($data["CQP_REQUEST_SELECTED"], null, null, "id", "desc", "data");
    sleep($sleep);

    foreach ($resultNewTask["data"] as $task) {
        if ($task["status"] == "ACTIVE") {
            $node = "/tasks/" . $task["id"] . "/edit";
            $correctTask = true;
            $counter = $attemps;
        }
    }
    
    $counter++;
}

return $node;