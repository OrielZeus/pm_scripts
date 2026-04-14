<?php 

/*****************************************
* Derivate request with complete agremeents
*
* by Diego Tapia
*****************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
require_once("/CQP_Generic_Functions.php");
$apiInstanceCollections = $api->collections();

// Register log  of the wbhookcall
$collectionInsured = getCollectionId('CQP_FORTE_CARGO_WEBHOOK_PAYLOAD', getEnv("API_HOST") . getEnv("API_SQL"));
$record = new \ProcessMaker\Client\Model\RecordsEditable();

$record->setData([
    'PAYLOAD' => $data
]);

$apiInstanceCollections->createRecord($collectionInsured, $record);

// Valiate the webhook information
if (!isset($data["agreement"])) {
    // Return validation for webhook creation  
    return ["xAdobeSignClientId" => getenv("CQP_CLIENT_ID_ADOBE")];
} else {
    // Update completed request
    $apiHost = getenv("API_HOST");
    $domain = getenv("HOST_URL");

    //Initialize HTTP client
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        "Authorization" => "Bearer " . getenv("API_TOKEN")
    ];

    $client = new Client([
        "verify" => false,
        "defaults" => ["verify" => false]
    ]);
    
    // Complete task
    $response = $client->request('GET', $apiHost .'/tasks?pmql=(status = "ACTIVE")and(element_id="wait_adobe")and(data.CQP_AGREEMENT_ID = "' . $data["agreement"]["id"] . '")&per_page=1&order_by=id&order_direction=desc', [
        "headers" => $headers
    ]);
    
    $records = json_decode($response->getBody()->getContents());
    
    if (count($records->data) > 0) {
        $apiInstance = $api->tasks();
        $process_request_token_editable = new \ProcessMaker\Client\Model\ProcessRequestTokenEditable();
        $process_request_token_editable->setStatus('COMPLETED');
        $process_request_token_editable->setData(["AUTOMATIC_DERIVATION" => true]);
        $result = $apiInstance->updateTask($records->data[0]->id, $process_request_token_editable);
        return ["derivated" => true];
    }
    
    return ["derivated" => false];
}