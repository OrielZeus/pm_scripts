<?php 
/*****************************************
* Recover signed agremeent in adobe sign
*
* by Diego Tapia
* Modified by Natalia Mendez
*****************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
require_once("/CQP_Generic_Functions.php");
$apiInstanceCollections = $api->collections();


//Initialize variables
$responseAction = "";
$agreementId = $data["CQP_AGREEMENT_ID"];
$fileList = $data["CQP_FILE_LIST"];
$response = [
    "CQP_ADOBE_SUCCESS" => true,
    "CQP_ADOBE_MESSAGE" => "",
    "CQP_ADOBE_USER_DECLINED" => "USER_EMPTY",
    "CQP_STATUS" =>  empty($data["CQP_STATUS"]) ? "" : $data["CQP_STATUS"]
];


// Clear error variable to avoid previous error to be shown afterwards
$response["resErrorHandling"] = "";
$response["FORTE_ERROR"] = ['data' => ["FORTE_ERROR_LOG" => ""]];
$response["FORTE_ERROR_MESSAGE"] = "";

// get Adobe Client
if (!createAdobeclient($api->users()->getUserById($data["CQP_ADOBE_TASK_USER_ID"])['email'] )) return $response;

$agreementStatusResponse = callAdobeClient("GET", 'agreements/' . $agreementId);

if ($agreementStatusResponse !== false) {
    $agreementStatus = $agreementStatusResponse->status;

    if ($agreementStatus == 'SIGNED' || $agreementStatus != 'CANCELLED' || $agreementStatus != 'EXPIRED') {
        $response["CQP_ADOBE_COMPLETE"] = "YES";
        if ($agreementStatus == 'SIGNED') {
            $pmCaseStatus = "BOUND";

            //Get agreement documents
            $agreementDocuments = callAdobeClient("GET", 'agreements/' . $agreementId . '/documents');
            if ($agreementDocuments !== false) {
                foreach ($agreementDocuments->documents as $key => $agreementDocument) { 
                    $index = array_search($agreementDocument->label, array_column($fileList, "CQP_ADOBE_DOCUMENT_LABEL"));
                    $downloadResponse = downloadDocumentAssociateItToCase($agreementDocument, $agreementId, $api->requestFiles(), $data["_request"]["id"], $index);
                    $fileList[$index]["CQP_SIGN_FILE"] = $downloadResponse;
                }
                $response["CQP_FILE_LIST"] = $fileList;
            }
        }

        // Get information of the cancellation
        if ($agreementStatus == 'CANCELLED') {
            $eventsAdobe = callAdobeClient("GET", 'agreements/' . $agreementId . '/events');
            if ($eventsAdobe !== false) {
                $cancelledAction = "";
                $cancelledUser = "";
                $cancelledEmail = "";
                $cancelledComment = "";
                $cancelledDate = "";
                foreach ($eventsAdobe->events as $event) {
                    if ($event->type == 'REJECTED') {
                        $cancelledAction = "Declined";
                        $cancelledUser = $event->actingUserName;
                        $cancelledEmail = $event->actingUserEmail;
                        $cancelledComment = $event->comment;
                    }
                    if ($event->type == 'RECALLED') {
                        $cancelledAction = "Cancelled";
                        $cancelledUser = $event->actingUserName;
                        $cancelledEmail = $event->actingUserEmail;
                        $cancelledComment = $event->comment;
                    }
                    if ($event->type == 'AUTO_CANCELLED_CONVERSION_PROBLEM') {
                        $cancelledAction = $event->type;
                    }
                }
                if ($cancelledAction == "AUTO_CANCELLED_CONVERSION_PROBLEM") {
                    setResponse("An internal error occurred in Adobe Sign and the documents could not be delivered, please contact your system administrator");
                } else {
                    $responseAction = "COMPLETED";
                    $userAction = $cancelledUser == "" ? "USER_EMPTY" : $cancelledUser;
                    $response["CQP_ADOBE_USER_DECLINED"] = $userAction;
                    $response["CQP_ADOBE_EMAIL_DECLINED"] = $cancelledEmail;
                    $response["CQP_ADOBE_DECLINED_COMMENT"] = $cancelledComment;
                    $response["CQP_ADOBE_DECLINED_DATE"] = date("Y-m-d H:i", strtotime($event->date));
                    $pmCaseStatus = "DECLINED";
                }
            }
        }

        // Get information of the Expired
        if ($agreementStatus == 'EXPIRED') {
            $pmCaseStatus = "DECLINED";
        }
    }
    $response["CQP_STATUS"] = $pmCaseStatus;
   

    // Update row in collection
    $collectionInsured = getCollectionId('CQP_FORTE_CARGO_INSURED', getEnv("API_HOST") . getEnv("API_SQL"));
    $record = new \ProcessMaker\Client\Model\RecordsEditable();

    $record->setData([
        'CQP_BROKER_STATUS' => $pmCaseStatus
    ]);

    $result = $apiInstanceCollections->patchRecord($collectionInsured, $data["CQP_COLLECTION_REQUEST_ID"], $record);
} 
$response["CQP_GENERATE_EMAIL"] = "";
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


/**
 * Download Document And Associate It To The Case
 *
 * @param OBject $agreementDocument
 * @param string $agreementId
 * @param object $apiClass
 * @param int $requestId
 * @param int $indexArray
 * @return $FileId
 *
 * by DIego Tapia
 */
function downloadDocumentAssociateItToCase($agreementDocument, $agreementId, $apiClass, $requestId, $indexArray) {
    global $adobeClient;

    if (!is_dir("/tmp/" . $agreementId)) {
        mkdir("/tmp/" . $agreementId, 0777, true);
    }
    $path = tempnam("/tmp/" . $agreementId, 'PDF-');
    rename($path, "/tmp/" . $agreementId. "/" . $agreementDocument->label . ".pdf");
    //Download document
    try {
        $response = $adobeClient->request("GET", '/api/rest/v6/agreements/' . $agreementId . '/documents/' . $agreementDocument->id . $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/pdf',
            ],
            'sink' => "/tmp/" . $agreementId. "/" . $agreementDocument->label . ".pdf",
        ]);
        
        $file = $apiClass->createRequestFile($requestId, "CQP_FILE_LIST." . $indexArray . ".CQP_SIGN_FILE", "/tmp/" . $agreementId. "/" . $agreementDocument->label . ".pdf");
        
        return $file->getFileUploadId();
    } catch(\Exception $e) {
        setResponse("Issue in API " . $url . ", please contact your system administrator");
        return false;
    }
}