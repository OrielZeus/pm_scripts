<?php 
/*****************************************
* Create a webhook in adobe configuration
*
*PS. Ask the client to add the scope "webhook_write" and "webhook_read" in the Access Tokens configuration
* by Diego Tapia
*****************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

/**
* Create a new configuration, the first time point to a webhook test environment (EX. https://webhook-test.com/) to get the ID
* get the X_ADOBESIGN_CLIENTID	and configure this in the response of the "CQP_CLIENT_ID_ADOBE" Environment variable
* Then update the url to the "Adobe Webhook Complete Agrements" script webhook
*/
//$url = "https://webhook-test.com/e4eadf38e75abc8bbde49110b319ac4b";
$url = "https://forteunderwriters.dev.cloud.processmaker.net/api/1.0/pstools/script/complete_webhook";

// Create API Adobe client
if (!createAdobeclient()) return $response;

// Check if webhook exist
$exists = false;
$responseVerification = callAdobeClient("GET", "webhooks");

foreach ($responseVerification->userWebhookList as $webhook) {
    if ($webhook->name === 'Webhook to check complete requests') {
        $exists = true;
    }
}

if (!$exists) {
    $postFields = [
        "webhookUrlInfo"=> [
            "url"=> $url //Adjust this link with processmaker webhook once we have the X-AdobeSign-ClientId
        ],
        "webhookSubscriptionEvents"=> [
            "AGREEMENT_WORKFLOW_COMPLETED",
            "AGREEMENT_RECALLED",
            "AGREEMENT_EXPIRED",
            'AGREEMENT_REJECTED'
        ],
        "webhookAgreementEvents"=> [
            "includeDetailedInfo"=> true,
            "includeDocumentsInfo"=> true,
            "includeParticipantsInfo"=> true,
            "includeSignedDocuments"=> true
        ],
        "scope"=> "GROUP",
        "name"=> "Webhook to check complete requests",
        "state"=> "ACTIVE"
    ];

    $responseCreation = callAdobeClient("POST", "webhooks", $postFields);
}

return ["CQP_WEB_WAS_CREATED" => !$exists, "CQP_WEB_CREATED_RESPONSE" => $responseCreation];

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
function callAdobeClient($method, $url, $data = null, $type = "json") {
    global $adobeClient;

        if ($method == "POST" || $method == "PUT") {
            $response = $adobeClient->request($method, '/api/rest/v6/' . $url, [
                $type => $data
            ]);
        } else {
            $response = $adobeClient->request($method, "/api/rest/v6/" . $url);
        }

        $response = json_decode($response->getBody()->getContents());
    try {
        return $response;
    } catch(\Exception $e) {
    return $e;
        setResponse("Issue in API " . $url . ", please contact your system administrator");
        return false;
    }

}