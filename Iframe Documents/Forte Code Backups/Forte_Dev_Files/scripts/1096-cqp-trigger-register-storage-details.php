<?php 
/*
* This script is responsible for triggering a parallel process to register storage details, uses https://github.com/ProcessMaker/docker-executor-php/blob/develop/docs/sdk/Processes.md
* as reference
*
* by Mateo Rada Arias
* Modify by Diego Tapia
* Modify by Cristian
* Modified by Natalia Mendez
*/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
$process_id = getenv('REGISTER_STORAGE_PROCESS_ID'); 
$apiInstance = $api->processes();
$body = new \stdClass; 
$event = 'start';
$requestId = null;

// Set Variables for parallel request creation
$body->CQP_PARENT_REQUEST = $data["_request"]["id"];
$body->CQP_MARKETS = $data["CQP_MARKETS"];
$body->CQP_INSURED_NAME = $data["CQP_INSURED_NAME"];
$body->CQP_INCEPTION_DATE = $data["CQP_INCEPTION_DATE"];
$body->CQP_EXPIRATION_DATE = $data["CQP_EXPIRATION_DATE"];
$body->CQP_COUNTRY = $data["CQP_COUNTRY"];
$body->CQP_STORAGE_EEL = $data["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_EEL"];
$body->CQP_STORAGE_AGG = $data["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_AGG"];
$body->CQP_TOTAL_FORTE_SHARE = $data["CQP_TOTAL_FORTE_SHARE"];
$body->CQP_CITIES = [];
$body->CQP_CITIES[]= ["CQP_ADDRESS" => [], "REQUIRED_IF" => true];
$body->CQP_CITIES[0]["CQP_ADDRESS"][] = ["CQP_MARKETS" => [], "REQUIRED_IF" => true];
$body->CQP_SUBPROCESS_STATUS = "INITIATED";
$body->CQP_TYPE = $data["CQP_TYPE"];

// Create parallel process
if ($data["CQP_TYPE"] == "STP") {
    $processRequest = $apiInstance->triggerStartEvent($process_id, $event, $body);
    $requestId = $processRequest->getId();
}

return [
    'CQP_SUBPROCESS_ID' => $requestId,
    'CQP_STATUS' => null,

    "resErrorHandling"   => "", // Clear error variable to avoid previous error to be shown afterwards
    "FORTE_ERROR"       => ['data' => ["FORTE_ERROR_LOG" => ""]],
    "FORTE_ERROR_MESSAGE" => ""
];