<?php 
/*********************************
* Get Workflow Documents
*
* by Diego Tapia
*********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set initial variables
$adobeClient = null;
$currentUserEmail = $api->users()->getUserById($data["CQP_USER_ID"])['email'];
$defaultDocuments = [];
$uploadDocuments = [];

$response = [
    "CQP_ADOBE_SUCCESS" => true,
    "CQP_ADOBE_MESSAGE" => ""
];

// List of Files to add a Signing Page
$filesList = ["Reinsurance Slip"];

// get Adobe Client
if(!createAdobeclient()) return $response;

// Get List of documents
$workflowCharacteristics = callAdobeClient("GET", "workflows/" . $data["CQP_ADOBE_WORKFLOW_SELECTED"]);

if ($workflowCharacteristics === false) return $response;

if (!empty($workflowCharacteristics->fileInfos)) {
    foreach ($workflowCharacteristics->fileInfos as $adobefile) {
        $fileID = null;
        $uploadFile = true;

        if (isset($adobefile->workflowLibraryDocumentSelectorList)) {
            $fileID = $adobefile->workflowLibraryDocumentSelectorList[0]->workflowLibDoc;
            $uploadFile = false;
        } elseif (in_array($adobefile->label, $filesList)) {
            $fileID = "SIGNED-SLIP";
            $uploadFile = false;
        }

        if (!$uploadFile && $adobefile->required) {
            $defaultDocuments[] = [
                "LABEL" => $adobefile->label,
                "REQUIRED" => $adobefile->required,
                "DOC_ID" => $fileID,
                "UPLOAD_FILE" => $uploadFile
            ];
        } else {
            $uploadDocuments[] = [
                "LABEL" => $adobefile->label,
                "REQUIRED" => $adobefile->required,
                "DOC_ID" => $fileID,
                "UPLOAD_FILE" => $uploadFile
            ];
        }
    }

    $response["CQP_DEFAULT_DOCUMENTS"] = $defaultDocuments;
    $response["CQP_UPLOAD_DOCUMENTS"] = $uploadDocuments;
} else {
    $response["CQP_DEFAULT_DOCUMENTS"] = [];
    $response["CQP_UPLOAD_DOCUMENTS"] = [];
}

$response["CQP_RECIPIENTS"] = $workflowCharacteristics->recipientsListInfo;
return $response;
 
/**
 * Creates the client to call the Adobe APIs
 * 
 * @return boolean $response
 *
 * by Diego Tapia
 */ 
function createAdobeclient() {
    global $adobeClient, $currentUserEmail;

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