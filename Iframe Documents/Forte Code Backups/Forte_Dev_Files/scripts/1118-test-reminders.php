<?php 


// get Adobe Client
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

if (!createAdobeclient()) return $response;
$reminderJson = "";
$listPeople = [];

$responseParticipants = callAdobeClient("GET", "agreements/CBJCHBCAABAANGFxYzahir2PgY7Ri8HBTCod5KpPEMPj/members");

foreach($responseParticipants->participantSets as $participant) {
    if ($participant->status == "WAITING_FOR_MY_SIGNATURE") {
        $listPeople[] = $participant->id;
    }
}

$reminderJson = [
    "note" => "string",
    "firstReminderDelay" => 1,
    "recipientParticipantIds" => $listPeople,
  "startReminderCounterFrom"=> "AGREEMENT_AVAILABILITY",
    "frequency" => "DAILY_UNTIL_SIGNED",
    "status" => "ACTIVE"
];
$responseUpdateExpiration = callAdobeClient("POST", "agreements/CBJCHBCAABAANGFxYzahir2PgY7Ri8HBTCod5KpPEMPj/reminders", $reminderJson);

return $responseUpdateExpiration;


//$responseParticipants = callAdobeClient("GET", "agreements/CBJCHBCAABAANGFxYzahir2PgY7Ri8HBTCod5KpPEMPj/signingUrls");

$responseParticipants = callAdobeClient("GET", "agreements/CBJCHBCAABAANGFxYzahir2PgY7Ri8HBTCod5KpPEMPj");
$responseParticipants->expirationTime = "2026-05-03T20:23:55Z";
$responseUpdateExpiration = callAdobeClient("PUT", "agreements/CBJCHBCAABAANGFxYzahir2PgY7Ri8HBTCod5KpPEMPj", $responseParticipants);
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
    } catch (\Exception $e) {
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
        print_r($e);die();
        setResponse("Issue in API " . $url . ", please contact your system administrator");
        return false;
    }

}