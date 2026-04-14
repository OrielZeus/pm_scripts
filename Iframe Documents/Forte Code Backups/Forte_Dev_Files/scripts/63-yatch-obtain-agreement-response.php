<?php 
/***********************************
* YATCH - Obtain Agreement Response
*
* by Cinthia Romero
***********************************/
date_default_timezone_set("America/New_York");
/**
 * Call Curl
 *
 * @param string $endpointUrl
 * @param string $method
 * @param array $postFields
 * @param string $bearer
 * @param string $contentType
 * @param string $userEmail
 * @return array $reponseCurl
 *
 * by Cinthia Romero
 */
function callCurl($endpointUrl, $method, $postFields, $bearer, $contentType, $userEmail)
{
    $curl = curl_init();
    $httpHeaders = array(
        "Authorization: Bearer " . $bearer,
        "Content-Type: " . $contentType,
        "cache-control: no-cache"
    );
    if ($userEmail != '') {
        $httpHeaders[] = 'x-api-user: email:' . $userEmail;
    }
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpointUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => $httpHeaders
    ));
    $reponseCurl = curl_exec($curl);
    $errorCurl = curl_error($curl);
    curl_close($curl);
    return $reponseCurl;
}

/**
 * Get Base Uri
 * 
 * @param string $adobeSignToken
 * @param string $userEmail
 * @return array $baseUriResponse
 * 
 * by Cinthia Romero
 */
function getBaseUri($adobeSignToken, $userEmail)
{
    $url = 'https://api.na3.adobesign.com/api/rest/v6/baseUris';
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    $curlResponse = json_decode($curlResponse, true);
    if (!empty($curlResponse["apiAccessPoint"])) {
        $baseUriResponse = array(
            "success" => true,
            "errorMessage" => "",
            "baseUri" => $curlResponse["apiAccessPoint"]
        );
    } else {
        $baseUriResponse = array(
            "success" => false,
            "errorMessage" => $curlResponse["message"],
            "baseUri" => ""
        );
    }
    return $baseUriResponse;
}

/**
 * Check Agreement Status
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param string $agreementId
 * @param string $userEmail
 * @return string $agreementStatusResponse
 *
 * by Cinthia Romero
 */
function checkAgreementStatus($adobeBaseUri, $adobeSignToken, $agreementId, $userEmail)
{
    $url = $adobeBaseUri . 'api/rest/v6/agreements/' . $agreementId; 
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    $curlResponse = json_decode($curlResponse, true);
    if (!empty($curlResponse["id"])) {
        $agreementStatusResponse = array(
            "success" => true,
            "errorMessage" => "",
            "agreementStatus" => $curlResponse["status"]
        );
    } else {
        $agreementStatusResponse = array(
            "success" => false,
            "errorMessage" => $curlResponse["message"],
            "agreementStatus" => ""
        );
    }
    return $agreementStatusResponse;
}

/**
 * Get Declined Information
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param string $agreementId
 * @param string $userEmail
 * @return array $declinedInformation
 *
 * by Cinthia Romero
 */
function getDeclinedInformation($adobeBaseUri, $adobeSignToken, $agreementId, $userEmail)
{
    $url = $adobeBaseUri . 'api/rest/v6/agreements/' . $agreementId . '/events'; 
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    $curlResponse = json_decode($curlResponse, true);
    if (!empty($curlResponse["events"])) {
        $declinedInformation = array(
            "success" => true,
            "errorMessage" => "",
        );
        $cancelledAction = "";
        $cancelledUser = "";
        $cancelledEmail = "";
        $cancelledComment = "";
        $cancelledDate = "";
        foreach ($curlResponse["events"] as $event) {

            if ($event["type"] == 'REJECTED') {
                $cancelledAction = "Declined";
                $cancelledUser = $event["actingUserName"];
                $cancelledEmail = $event["actingUserEmail"];
                $cancelledComment = $event["comment"];
                $cancelledDate = date("Y-m-d H:i", strtotime($event["date"]));
            }
            if ($event["type"] == 'RECALLED') {
                $cancelledAction = "Cancelled";
                $cancelledUser = $event["actingUserName"];
                $cancelledEmail = $event["actingUserEmail"];
                $cancelledComment = $event["comment"];
                $cancelledDate = date("Y-m-d H:i", strtotime($event["date"]));
            }
            if ($event["type"] == 'AUTO_CANCELLED_CONVERSION_PROBLEM') {
                $cancelledAction = $event["type"];
                $cancelledDate = date("Y-m-d H:i", strtotime($event["date"]));
            }
        }
        $declinedInformation["declinedInformation"] = array(
            "ACTION" => $cancelledAction,
            "USER" => $cancelledUser,
            "EMAIL" => $cancelledEmail,
            "COMMENT" => $cancelledComment,
            "DATE" => $cancelledDate
        );
    } else {
        $declinedInformation = array(
            "success" => false,
            "errorMessage" => $curlResponse["message"],
            "declinedInformation" => array()
        );
    }
    return $declinedInformation;
}

/**
 * Get Agreement Documents
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param string $agreementId
 * @param string $userEmail
 * @return $documentsInformation
 *
 * by Cinthia Romero
 */
function getAgreementDocuments($adobeBaseUri, $adobeSignToken, $agreementId, $userEmail)
{
    $url = $adobeBaseUri . 'api/rest/v6/agreements/' . $agreementId . '/documents';
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    $curlResponse = json_decode($curlResponse, true);
    if (!empty($curlResponse["documents"])) {
        $documentsInformation = array(
            "success" => true,
            "errorMessage" => "",
            "documents" => $curlResponse["documents"]
        );
    } else {
        $documentsInformation = array(
            "success" => false,
            "errorMessage" => $curlResponse["message"],
            "documents" => array()
        );
    }
    return $documentsInformation;
}

/**
 * Download Document And Associate It To The Case
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param string $agreementId
 * @param int $requestId
 * @param string $pmToken
 * @param object $apiClass
 * @param string $userEmail
 * @return $documentsInformation
 *
 * by Cinthia Romero
 */
function downloadDocumentAssociateItToCase($adobeBaseUri, $adobeSignToken, $agreementId, $requestId, $pmToken, $apiClass, $userEmail)
{
    //Get agreement documents
    $agreementDocuments = getAgreementDocuments($adobeBaseUri, $adobeSignToken, $agreementId, $userEmail);
    if ($agreementDocuments["success"]) {
        $agreementDocumentsList = $agreementDocuments["documents"];
        foreach ($agreementDocumentsList as $key=>$agreementDocument) {
            //Download document
            $url = $adobeBaseUri . 'api/rest/v6/agreements/' . $agreementId . '/documents/' . $agreementDocument["id"];
            $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/pdf', $userEmail);
            if (strpos($curlResponse, "message") !== false) {
                $curlResponseDecoded = json_decode($curlResponse, true);
                $documentsInformation = array(
                    "success" => false,
                    "errorMessage" => $curlResponseDecoded["message"]
                );
                break;
            } else {
                //Create a temporal document
                $tempFilePath = "/tmp/" . $agreementDocument["label"] . ".pdf";
                file_put_contents($tempFilePath, $curlResponse);
            
                //Associate the temporal file to the current request
                $apiClass->createRequestFile($requestId, "YQP_ADOBE_SIGNED_DOCUMENT" . $key, $tempFilePath);
        
                $documentsInformation = array(
                    "success" => true,
                    "errorMessage" => ""
                );
            }
        }
    } else {
        $documentsInformation = $agreementDocuments;
    }
    return $documentsInformation;
}

//Server Token
$serverHost = getenv('API_HOST');
$serverToken = getenv('API_TOKEN');
//Get Integrator Key
$adobeSignToken = getenv('ADOBE_TOKEN');
//Get current user email
$currentUserEmail = $data['YQP_ADOBE_USER_EMAIL'];

//Initialize values
$responseAction = "";
$responseErrorMessage = "";
$agreementStatus = "";
$agreementStatusToShow = "";
$userDeclinedCancelled = "";
$emailDeclinedCancelled = "";
$commentDeclinedCancelled = "";
$dateDeclinedCancelled = "";
$documentsHTML = ""; 
$pmCaseStatus = empty($data["YQP_STATUS"]) ? "" : $data["YQP_STATUS"];
$responseArray = array();
if ($adobeSignToken) {
    //Check Base Uri
    $baseUriResponse = getBaseUri($adobeSignToken, $currentUserEmail);
    if ($baseUriResponse["success"]) {
        $adobeBaseUri = $baseUriResponse["baseUri"];
        if (!empty($data["YQP_ADOBE_AGREEMENT_ID"])) {
            $agreementId = $data["YQP_ADOBE_AGREEMENT_ID"];
            //Get Agreement Status
            $agreementStatusResponse = checkAgreementStatus($adobeBaseUri, $adobeSignToken, $agreementId, $currentUserEmail);
            if ($agreementStatusResponse["success"]) {
                $agreementStatus = $agreementStatusResponse["agreementStatus"];
                if ($agreementStatus == 'SIGNED') {
                    $pmCaseStatus = "BOUND";
                    $userDeclinedCancelled = "USER_EMPTY";
                    $apiInstance = $api->requestFiles();
                    //Download signed document
                    $downloadResponse = downloadDocumentAssociateItToCase($adobeBaseUri, $adobeSignToken, $agreementId, $data['_request']['id'], $serverToken, $apiInstance, $currentUserEmail);
                    if (!$downloadResponse["success"]) {
                        $responseAction = "ERROR";
                        $responseErrorMessage = $downloadResponse["errorMessage"];
                    } else {
                        //Get All Documents For Current Request
                        $url = $serverHost . '/requests/' . $data['_request']['id'] . '/files';
                        $curlResponse = callCurl($url, 'GET', array(), $serverToken, 'application/json', '');
                        $curlResponse = json_decode($curlResponse, true);
                        if (!empty($curlResponse['data'])) {
                            $existDownloadedDocuments = false;
                            //Form Documents Download HTML
                            $documentsHTML = "<table width='100%' style='border-collapse: collapse'>";
                            $documentsHTML .= "<tr>";
                            $documentsHTML .= "<th>Document Name</th>";
                            $documentsHTML .= "<th>Action</th>";
                            $documentsHTML .= "</tr>";
                            foreach ($curlResponse['data'] as $requestFile) {
                                if (strpos($requestFile['custom_properties']['data_name'], "YQP_ADOBE_SIGNED_DOCUMENT") !== false) {
                                    $existDownloadedDocuments = true;
                                    $documentsHTML .= "<tr>";
                                    $documentsHTML .= "<td>" . $requestFile['file_name'] . "</td>";
                                    $documentsHTML .= "<td><a class='btn btn-outline-success' role='button' href=" . $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $requestFile['id'] . ">Download</a></td>";
                                    $documentsHTML .= "</tr>";
                                }    
                            }
                            $documentsHTML .= "</table>";

                            if (!$existDownloadedDocuments) {
                                $documentsHTML = "";
                            }
                            $responseAction = "COMPLETED";
                            $agreementStatusToShow = "Completed";
                        } else {
                            $responseAction = "ERROR";
                            $responseErrorMessage = "Request files could not be obtained, please contact your system administrator";
                        }
                    }
                }
                if ($agreementStatus == 'CANCELLED') {
                    $declinedInformation = getDeclinedInformation($adobeBaseUri, $adobeSignToken, $agreementId, $currentUserEmail);
                    if ($declinedInformation["success"]) {
                        $actionDoneInAdobe = $declinedInformation["declinedInformation"]["ACTION"];
                        if ($actionDoneInAdobe == "AUTO_CANCELLED_CONVERSION_PROBLEM") {
                            $responseAction = "ERROR";
                            $responseErrorMessage = "An internal error occurred in Adobe Sign and the documents could not be delivered, please contact your system administrator";
                        } else {
                            $responseAction = "COMPLETED";
                            $userAction = $declinedInformation["declinedInformation"]["USER"] == "" ? "USER_EMPTY" : $declinedInformation["declinedInformation"]["USER"];
                            $userDeclinedCancelled = $userAction;
                            $emailDeclinedCancelled = $declinedInformation["declinedInformation"]["EMAIL"];
                            $commentDeclinedCancelled = $declinedInformation["declinedInformation"]["COMMENT"];
                            $dateDeclinedCancelled = $declinedInformation["declinedInformation"]["DATE"];
                            $agreementStatusToShow = $actionDoneInAdobe;
                            $pmCaseStatus = "DECLINED";
                        }
                    } else {
                        $responseAction = "ERROR";
                        $responseErrorMessage = $declinedInformation["errorMessage"];
                    }
                }
                if ($agreementStatus == 'EXPIRED') {
                    $responseAction = "COMPLETED";
                    $userDeclinedCancelled = "USER_EMPTY";
                    $commentDeclinedCancelled = "Agreement expired on " . $data["YQP_ADOBE_EXPIRATION_DATE"];
                    $agreementStatusToShow = "Expired";
                    $pmCaseStatus = "DECLINED";
                }
                if ($agreementStatus != 'SIGNED' && $agreementStatus != 'CANCELLED' && $agreementStatus != 'EXPIRED') {
                    $responseAction = "WAIT";
                }
            } else {
                $responseAction = "ERROR";
                $responseErrorMessage = $agreementStatusResponse["errorMessage"];
            }
        } else {
            $responseAction = "ERROR";
            $responseErrorMessage = "Agreement ID could not be empty, please contact your system administrator";
        }
    } else {
        $responseAction = "ERROR";
        $responseErrorMessage = $baseUriResponse["errorMessage"];
    }
} else {
    $responseAction = "ERROR";
    $responseErrorMessage = "The adobe token was not configured, please contact your system administrator.";
}
$responseArray['YQP_AGREEMENT_RESPONSE_REVISION_TIME'] = date("Y-m-d H:i:s");
$responseArray['YQP_AGREEMENT_RESPONSE_ACTION'] = $responseAction;
$responseArray['YQP_AGREEMENT_RESPONSE_ERROR'] = $responseErrorMessage;
$responseArray["YQP_ADOBE_SIGN_AGREEMENT_STATUS"] = $agreementStatus;
$responseArray["YQP_ADOBE_SIGN_AGREEMENT_STATUS_SHOW"] = $agreementStatusToShow;
$responseArray["YQP_ADOBE_USER_DECLINED"] = $userDeclinedCancelled;
$responseArray["YQP_ADOBE_EMAIL_DECLINED"] = $emailDeclinedCancelled;
$responseArray["YQP_ADOBE_DECLINED_COMMENT"] = $commentDeclinedCancelled;
$responseArray["YQP_ADOBE_DECLINED_DATE"] = $dateDeclinedCancelled;
$responseArray['YQP_ADOBE_DOWNLOAD_DOCUMENTS_HTML'] = $documentsHTML;
$responseArray["YQP_STATUS"] = $pmCaseStatus;

//Forte Errors Screen
if ($responseAction == "ERROR") {
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $responseErrorMessage;
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_BODY'] = "Error Response Adobe Sign";
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_263";
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "Obtain Agreement Response"; 
}

return $responseArray;