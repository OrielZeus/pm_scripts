<?php 
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$api_token = getenv('API_TOKEN');
$api_host = getenv('API_HOST');
$apiInstance = $api->collections();
$pmqlScreen = (isset($data["pmql"]))?$data["pmql"]:"";

$headers = [
    "Content-Type" => "application/json",
    "Accept" => "application/json",
    "Authorization" => "Bearer " . $api_token
];
$client = new Client([
    'base_uri' => $api_host,
    'verify' => false,
]);

$page = empty($data['page'])?1:$data['page'];
$savedSearch = empty($data['savedSearch'])? "NOT" : $data['savedSearch'];
if($savedSearch == "NOT" && !ctype_digit ($savedSearch)){
    return "The saved search ID is required";
}

$getData = getSavedSearchData();
$organizeData = formatData($getData);

return ["data" => $organizeData, "meta" => $getData->meta];

function getSavedSearchData(){
    global $data, $api_host, $headers, $client, $pmqlScreen,$savedSearch;
      
    $getRecords = new Request("GET", $api_host . "/saved-searches/".$savedSearch."/results?page=".$page."&per_page=100&include=data&order_by=id&order_direction=asc", $headers);                                                             
    $response = $client->send($getRecords);
    $records = json_decode($response->getBody()->getContents());
    
    return $records;
}

function formatData($oldData){
    $rowValue = [];
    foreach($oldData->data as $key){
        $rowData = json_decode($key->_json, true)["data"];
        $rowValue[] = $rowData;
        
    }
    return $rowValue;
}