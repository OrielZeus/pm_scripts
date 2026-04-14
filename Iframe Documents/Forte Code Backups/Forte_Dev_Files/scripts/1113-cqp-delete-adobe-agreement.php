<?php 
/*****************************************
* Cancell agremeent in adobe sign
*
* by Diego Tapia
* Modified by Natalia Mendez
*****************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Initialize variables
$agreementId = $data["CQP_AGREEMENT_ID"];
$response = [
    "CQP_ADOBE_SUCCESS" => true,
    "CQP_ADOBE_MESSAGE" => "",
    "CQP_STATUS" => ""
];

// Clear error variable to avoid previous error to be shown afterwards
$response["resErrorHandling"] = "";
$response["FORTE_ERROR"] = ['data' => ["FORTE_ERROR_LOG" => ""]];
$response["FORTE_ERROR_MESSAGE"] = "";


// get Adobe Client
if (!createAdobeclient($api->users()->getUserById($data["CQP_ADOBE_TASK_USER_ID"])['email'] )) return $response;

// Cancell current agreement
$postFields = [
    'state' => 'CANCELLED'
];

$agreementStatusResponse = callAdobeClient("PUT", 'agreements/' . $agreementId . '/state', $postFields);
return $response;


/**
 * Creates the client to call the Adobe APIs
 * 
 * @param string $userEmail
 * @return boolean $response
 *
 * by Diego Tapia
 */ 
function createAdobeclient($userEmail = null) {
    global $adobeClient;

    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => "Bearer "  . getenv("CQP_INTEGRATION_KEY_ADOBE")
    ];

    if ($userEmail != null) {
        $headers["x-api-user"] = 'email:' . $userEmail;
    }
    
    $adobeClientGet = new Client(
        [
            'base_uri' => getenv("ADOBE_URL"),
            'headers' => $headers
        ]
    );

    $urls = $adobeClientGet->request('GET', '/api/rest/v6/baseUris');
    $response = json_decode($urls->getBody()->getContents());
    
    $adobeClient = new Client(
        [
            'base_uri' => $response->apiAccessPoint,
            'headers' => $headers
        ]
    );

    return true;
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
function callAdobeClient($method, $url, $data = null, $type = "json") {
    global $adobeClient;

    try {
        if ($method == "POST" || $method == "PUT") {
            $response = $adobeClient->request($method, '/api/rest/v6/' . $url, [
                $type => $data
            ]);
        } else {
            $response = $adobeClient->request($method, "/api/rest/v6/" . $url);
        }

        $response = json_decode($response->getBody()->getContents());
        return $response;
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), "INVALID_USER") !== false) {
            createAdobeclient();
            return callAdobeClient($method, $url, $data, $type);
        } else {
            throw new Exception($e);
        }
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