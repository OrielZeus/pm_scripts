<?php 


// get Adobe Client
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// Set initial variables for adobe creation

$recipients = json_decode(urldecode($data["CQP_RECIPIENTS"]), true);;

$response = [
    "CQP_CURRENT_STATUS_RECIPIENTS" => []
];

// Set Adobe Client
if (!createAdobeclient($api->users()->getUserById($data["CQP_ADOBE_TASK_USER_ID"])['email'] )) return $response;

// Get current agreement status
$responseParticipants = callAdobeClient("GET", "agreements/" . $data["CQP_AGREEMENT_ID"] . "/members");


// Set response array
foreach ($recipients as $keyRecipient => $recipient) {
    $status = "";

    switch ($responseParticipants->participantSets[$keyRecipient]->status) {
        case "WAITING_FOR_MY_APPROVAL":
            $status = "Waiting revision";
            break;
        case "WAITING_FOR_OTHERS":
            $status = "Completed";
            break;
        case "WAITING_FOR_MY_SIGNATURE":
            $status = "Waiting signature";
            break;
        case "COMPLETED":
            $status = "Completed";
            break;
        case "NOT_YET_VISIBLE":
            $status = "Mail not sended yet";
            break;
        default:
            $status = $responseParticipants->participantSets[$keyRecipient]->status;
            break;
    }

    $response["CQP_CURRENT_STATUS_RECIPIENTS"][$keyRecipient] = [
        "CQP_LABEL" => $recipient["label"],
        "CQP_MAIL" => $responseParticipants->participantSets[$keyRecipient]->memberInfos[0]->email,
        "CQP_ROLE" => $responseParticipants->participantSets[$keyRecipient]->role,
        "CQP_STATUS" => $status
    ];
}

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