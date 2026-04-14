<?php 
/*  
 *  Close requests not taken in quote
 *  By Diego Tapia
 */

require_once("/CQP_Generic_Functions.php");
$apiInstanceCollections = $api->collections();
$apiInstanceRequest = $api->processRequests();

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

// Get request related to the quote
$collectionInsured = getCollectionId('CQP_FORTE_CARGO_INSURED', $apiUrl);
$getRecords = $apiInstanceCollections->getRecords($collectionInsured, 'data.CQP_SEARCH_CODE="' . $data["CQP_SEARCH_CODE"] . '"', 10000)->getData();

foreach ($getRecords as $row) {
    // Update insured collection with the new information
    $record = new \ProcessMaker\Client\Model\RecordsEditable();

    $record->setData([
        'CQP_BROKER_STATUS' => 'CLOSE',
    ]);

    $result = $apiInstanceCollections->patchRecord($collectionInsured, $row["data"]["id"], $record);

    // Update and close the request of not taken quote
    if ($row["data"]["CQP_REQUEST_ID"] != $data["_request"]["id"]) {
        $processRequest = $apiInstanceRequest->getProcessRequestById($row["data"]["CQP_REQUEST_ID"], "data");
        $dataNew = $processRequest->getData();
        $dataNew['CQP_BROKER_STATUS'] = 'CLOSE';
        $dataNew['CQP_CARGO_CURRENT_STATUS'] = 'QUOTED NOT TAKEN';
        $dataNew['CQP_NOT_QUOTE_REASON'] = $data["CQP_NOT_QUOTE_REASON"];
        $dataNew['CQP_NOT_QUOTE_COMMENTS'] = $data["CQP_NOT_QUOTE_COMMENTS"];
        $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
        $processRequestEditable->setData($dataNew);
        $apiInstanceRequest->updateProcessRequest($row["data"]["CQP_REQUEST_ID"], $processRequestEditable);
        $processRequestEditable->setStatus("CANCELED");
        $apiInstanceRequest->updateProcessRequest($row["data"]["CQP_REQUEST_ID"], $processRequestEditable);
    }
}

return ['CQP_BROKER_STATUS' => "CLOSE",
  "CQP_CARGO_CURRENT_STATUS" => "QUOTED NOT TAKEN"];