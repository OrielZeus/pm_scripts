<?php 
/*****************************************
* Get Workflows Adobe Sign
*
* by Diego Tapia
*****************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set initial variables
$adobeClient = null;
$WORD_TO_SEARCH = "PM";
$defaultDocuments = [];
$uploadDocuments = [];

$response = [
    "CQP_RECOMMENDED_WORKFLOWS" => [],
    "CQP_ADOBE_SUCCESS" => true,
    "CQP_ADOBE_MESSAGE" => "",
    "CQP_SUBMIT_ADOBE_SET" => null
];

// Get markets configuration to match workflow
$apiInstanceCollections = $api->collections();
$collections = json_decode(json_encode($apiInstanceCollections->getCollections(null,"ID", "desc", "1000")->getData()));
$marketsCollection = $collections[array_search("CQP_FORTE_CARGO_REINSURER", array_column($collections, "name")) ];
$markets = array_column(json_decode(json_encode($apiInstanceCollections->getRecords($marketsCollection->id, null)->getData())), "data");
$adobeNamesPresent = [];
$adobeNamesNotPresent = [];

foreach($data["CQP_MARKETS"] as $marketReinsurer) {
    $indexMarketValues = array_search($marketReinsurer["CQP_REINSURER"], array_column($markets, "CQP_REINSURER"));

    if ($indexMarketValues !== false) {
        if ($marketReinsurer["taken"]) {
            $adobeNamesPresent[] = $markets[$indexMarketValues]->CQP_WORKFLOW_LABEL;
        } else {
            $adobeNamesNotPresent[] = $markets[$indexMarketValues]->CQP_WORKFLOW_LABEL;
        }
    }
}

// get Adobe Client
if (!createAdobeclient()) return $response;

//Get list of workflow
$workflowListResponse = callAdobeClient("GET", "workflows");

if ($workflowListResponse == false) return $response;

if (isset($workflowListResponse->userWorkflowList)) {
    $workflowList = [];
    
    foreach($workflowListResponse->userWorkflowList as $workflow) {
        if (strpos($workflow->displayName, $WORD_TO_SEARCH) !== false) {
            $workflowList[] = $workflow;
            $currentWorkflow = true;
            
            foreach($adobeNamesPresent as $name) {
                if (strpos($workflow->displayName, $name) === false) {
                    $currentWorkflow = false;
                }
            }
            
            foreach($adobeNamesNotPresent as $name) {
                if (strpos($workflow->displayName, $name) !== false) {
                    $currentWorkflow = false;
                }
            }

            if ($currentWorkflow) {
                $response["CQP_RECOMMENDED_WORKFLOWS"][] = [
                    "id" => $workflow->id,
                    "name" => $workflow->displayName
                ];
            }
        }
    }

    if (count($workflowList) > 0) {
        $response["CQP_ADOBE_WORFLOW_LIST"] = $workflowList;
    } else {
        setResponse("There is not any workflow configured for current process, please contact your system administrator");
    }
} else {
    setResponse("There is not any workflow configured, please contact your system administrator");
}

return $response;

/**
 * Creates the client to call the Adobe APIs
 * 
 * @return boolean $response
 *
 * by Diego Tapia
 */ 
function createAdobeclient() {
    global $adobeClient;

    try {
        $adobeClientGet = new Client(
            [
                'base_uri' => getenv("ADOBE_URL"),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer "  . getenv("CQP_INTEGRATION_KEY_ADOBE")
                ],
            ]
        );
        $urls = $adobeClientGet->request('GET', '/api/rest/v6/baseUris');
        $response = json_decode($urls->getBody()->getContents());

        $adobeClient = new Client(
            [
                'base_uri' => $response->apiAccessPoint,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer "  . getenv("CQP_INTEGRATION_KEY_ADOBE")
                ],
            ]
        );
        return true;
    } catch(\Exception $e) {
        setResponse("Issue in Adobe Client creation, please contact your system administrator");
        return false;
    }

}


/**
 * Call Adobe API
 * 
 * @param string $method
 * @param string $url
 * @param array $data
 * @return array $response
 *
 * by Diego Tapia
 */ 
function callAdobeClient($method, $url, $data = null) {
    global $adobeClient;

    try {
        if ($method == "GET") {
            $response = $adobeClient->request($method, "/api/rest/v6/" . $url);
        } 
        if ($method == "POST" || $method == "PUT") {
            $response = $adobeClient->request($method, "/api/rest/v6/" . $url, [
                "json" => $data
            ]);
        } 
        $response = json_decode($response->getBody()->getContents());
        return $response;
    } catch(\Exception $e) {
        setResponse("Issue in API " . $url . ", please contact your system administrator");
        return false;
    }

}


/**
 * Set script response in case of error
 * 
 * @param string $message
 *
 * by Diego Tapia
 */ 
function setResponse($message) {
    global $response;
    $response["CQP_ADOBE_SUCCESS"] = false;
    $response["CQP_ADOBE_MESSAGE"] = $message;
}