<?php 

/*****************************************
* Get data for request list for Broker selection screen
*
* by Diego Tapia
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// Set default Variables
$responseRequests = [];
$apiHost = getenv('API_HOST');
$headers = [
    "Content-Type" => "application/json",
    "Accept" => "application/json",
    "Authorization" => "Bearer " . getenv('API_TOKEN')
];

$client = new Client([
    'verify' => false,
    'defaults' => ['verify' => false]
]);    

// Get the request of the insurer

$response = $client->request('GET', $apiHost .'/requests?pmql=(data.CQP_SEARCH_CODE = "' . $data["CQP_SEARCH_CODE"] .'" and status = "ACTIVE" and data.CQP_BROKER_STATUS = "OPEN")&page=1&per_page=10000&include=data&order_by=id&order_direction=DESC&advanced_filter=[{"subject":{"type":"Field","value":"name"},"operator":"=","value":"Cargo Quotation Process"}]', [
    'headers' => $headers
]);

$requests = json_decode($response->getBody()->getContents(), true);

foreach($requests["data"] as $request) {
    $tempRow = [
        "CQP_REQUEST_ID" => $request["id"],
        "CQP_CASE_ID" => $request["case_number"],
        "CQP_PRODUCT_TYPE" => $request["data"]["CQP_TYPE"],
        "CQP_UNDERWRITER_YEAR" => $request["data"]["CQP_UNDERWRITING_YEAR"],
        "CQP_INTEREST" => $request["data"]["CQP_INTEREST"],
        "CQP_BROKER" => $request["data"]["CQP_REINSURANCE_BROKER"],
        "CQP_PERIOD" => $request["data"]["CQP_NUMBER_OF_PERIODS"],
        "CQP_CARGO_CURRENT_STATUS" => $request["data"]["CQP_CARGO_CURRENT_STATUS"],
        "CQP_COUNTRY" => $request["data"]["CQP_COUNTRY"]["COUNTRY"],
        "CQP_PIVOT_NUMBER" => $request["data"]["CQP_PIVOT_NUMBER"]
    ];
    $responseRequests[] = $tempRow;
    $brokerFOund = "YES";
}


return ["REQUEST_FOUND" => $brokerFOund, "CQP_BROKER_REQUESTS" => $responseRequests];