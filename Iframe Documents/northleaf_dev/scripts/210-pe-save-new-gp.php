<?php

$client = new GuzzleHttp\Client(['verify' => false]);
$headers = [
    'Authorization' => 'Bearer ' .   getenv('API_TOKEN'),        
    'Accept'        => 'application/json',
];

/**********************************************
 * PE - Save New GP
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 *********************************************/
require_once("/Northleaf_PHP_Library.php");

// Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

// Get Collections IDs
$collectionsToSearch = array('PE_GP');
$collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl);

// Get records
$recordGP = getRecordsGP($collectionsArray["PE_GP"]);

// Create an array to track existing GPs in the recordGP variable
$existingGPs = [];
foreach ($recordGP as $record) {
    $existingGPs[$record['GP']] = true; // Register existing GPs
}

if (!empty($collectionsArray["PE_GP"])) {
    // Primary
    if ($data["PE_DEAL_TYPE"] == "Primary") {
        $newGP = $data["PE_PRIMARY_NEW_GP"] ?? null; // Get the value

        // Validate that the GP is not in recordGP
        if (!empty($newGP) && !isset($existingGPs[$newGP])) {
            saveNewGP($newGP); // Save only if it does not exist
            $existingGPs[$newGP] = true; // Mark it as existing
        }
    }

    // Secondary
    if ($data["PE_DEAL_TYPE"] == "Secondary") {
        // Create an array to track unique PE_SECONDARY_NEW_GP values being processed
        $processedGPs = [];

        if (is_array($data["PE_SECONDARY_FUNDS_LOOP"])) {
            foreach ($data["PE_SECONDARY_FUNDS_LOOP"] as $secondaries) {
                $newGP = $secondaries["PE_SECONDARY_NEW_GP"] ?? null; // Get the value
                
                // Validate that the GP is not in recordGP and has not been processed yet
                if (!empty($newGP) && !isset($existingGPs[$newGP]) && !isset($processedGPs[$newGP])) {
                    saveNewGP($newGP); // Save only if it does not exist

                    // Mark as processed
                    $processedGPs[$newGP] = true;
                    $existingGPs[$newGP] = true; // Update the list of existing GPs
                }
            }
        }
    }

    // Direct
    if ($data["PE_DEAL_TYPE"] == "Direct") {
        $newGP = $data["PE_DIRECT_FUND_NEW_GP"] ?? null; // Get the value

        // Validate that the GP is not in recordGP
        if (!empty($newGP) && !isset($existingGPs[$newGP])) {
            saveNewGP($newGP); // Save only if it does not exist
            $existingGPs[$newGP] = true; // Mark it as existing
        }
    }
}

//Create loop FUND_LEGAL_NAME_LOOP for TX.05
// Initialize the result array
$FUND_LEGAL_NAME_LOOP = [];

// Populate FUND_LEGAL_NAME_LOOP based on PE_DEAL_TYPE
if ($data["PE_DEAL_TYPE"] === "Secondary") {
    foreach ($data["PE_SECONDARY_FUNDS_LOOP"] as $secondaryFund) {
        $FUND_LEGAL_NAME_LOOP[] = [
            "FUND_LEGAL_NAME" => $secondaryFund["PE_SECONDARY_FUND_LEGAL"],
            "TAX_ENTITY_LEGAL_ENTITY" => null,
            "ENTITY" => ""
        ];
    }
} elseif ($data["PE_DEAL_TYPE"] === "Primary") {
    $FUND_LEGAL_NAME_LOOP[] = [
        "FUND_LEGAL_NAME" => $data["PE_PRIMARY_FUND_LEGAL"],
        "TAX_ENTITY_LEGAL_ENTITY" => null,
        "ENTITY" => ""
    ];
} elseif ($data["PE_DEAL_TYPE"] === "Direct") {
    $FUND_LEGAL_NAME_LOOP[] = [
        "FUND_LEGAL_NAME" => $data["PE_DIRECT_FUND_LEGAL"],
        "TAX_ENTITY_LEGAL_ENTITY" => null,
        "ENTITY" => ""
    ];
}

// Send Notification
$task = 'node_new_DT06';
$emailType = 'TO_GROUP_PE-Operations-Preclose-Notification';
sendNotification($data, $task, $emailType, $api);

$DT02_TASK_COMPLETED = isTaskActive("node_DT07", $data['_request']['id']) ? false : true;
return [
    "FUND_LEGAL_NAME_LOOP" => $FUND_LEGAL_NAME_LOOP,
    "DT02_TASK_COMPLETED" => $DT02_TASK_COMPLETED
];


/*****
 * Save New GP in Collection
 *
 * by Telmo Chiri
 ******/
function saveNewGP($newGP) {
    // Set Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;

    // Get Collections IDs
    $collectionsToSearch = array('PE_GP');
    $collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl);

    // Record on collection New GP
    $aNewGP = [];
    $aNewGP['GP'] = $newGP;
    $aNewGP['GP_STATUS'] = 'Active';
    $collectionPEGP = $collectionsArray["PE_GP"];
    $url = $apiHost . "/collections/" . $collectionPEGP . "/records";
    $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $aNewGP]);
}


/**
 * Retrieve grouped records of GP from a specified collection.
 *
 * @param string $collectionId The ID of the collection to query.
 * @return array An array of grouped GP records from the collection.
 *
 * by Adriana Centellas
 */
function getRecordsGP($collectionId)
{
    // Get API host and SQL endpoint from environment variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;

    // Prepare SQL query to fetch unique GP values from the collection
    $sqlUserLeader = "SELECT data->>'$.GP' AS GP 
                      FROM collection_" . $collectionId . " 
                      GROUP BY data->>'$.GP'";

    // Execute the query using the API and decode the response
    $rQUserLeader = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUserLeader)) ?? [];

    // Return the result set
    return $rQUserLeader;
}

function isTaskActive($nodeId, $requestId) {
    $activeTasks = getTasksByRequestId($requestId);
    $activeNodeIds = array_column($activeTasks, "element_id");
    return in_array($nodeId, $activeNodeIds) ? true : false;
}

function getTasksByRequestId($requestId, $status="ACTIVE") {
    global $client, $headers, $apiHost;
    $endpoint = "/tasks?process_request_id=$requestId&status=$status";
    $url = $apiHost.$endpoint;
    $res = $client->request("GET", $url, [
        'headers' => $headers,
    ]);

    $request_data = json_decode($res->getBody(), true);
    return $request_data["data"];  
}