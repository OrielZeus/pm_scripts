<?php 
/*****************************************
* YATCH - Send documents to Adobe Sign
*
* by Cinthia Romero
* modified by Helen Callisaya
*****************************************/
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
    $reponseCurl = json_decode($reponseCurl, true);
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
 * Get Workflow Definition
 * 
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param int $workflowUid
 * @param string $userEmail
 * @return array $curlResponse
 * 
 * by Cinthia Romero
 */
function getWorkflowDefinition($adobeBaseUri, $adobeSignToken, $workflowUid, $userEmail) {
    $url = $adobeBaseUri . 'api/rest/v6/workflows/' . $workflowUid;
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    return $curlResponse;
}

/**
 * Create Transient Document
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param string $documentName
 * @param string $documentPath
 * @param string $userEmail
 * @return array $successResponse
 *
 * by Cinthia Romero
 */
function createTransientDocument($adobeBaseUri, $adobeSignToken, $documentName, $documentPath, $userEmail)
{
    $url = $adobeBaseUri . 'api/rest/v6/transientDocuments';
    $mimeType = mime_content_type($documentPath);
    $file = file_get_contents($documentPath);
    $postFields = array('File-Name' => $documentName, 'File' => $file, 'Mime-Type' => $mimeType);
    $curlResponse = callCurl($url, 'POST', $postFields, $adobeSignToken, 'multipart/form-data', $userEmail);
    if (!empty($curlResponse["transientDocumentId"])) {
        $successResponse = array(
            "success" => true,
            "errorMessage" => "",
            "transientDocumentID" => $curlResponse['transientDocumentId']
        );
    } else {
        $successResponse = array(
            "success" => false,
            "errorMessage" => $curlResponse['message'],
            "transientDocumentID" => ""
        );
    }
    return $successResponse;
}

/**
 * Create Agreement
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param array $requestDocuments,
 * @param array $requestData
 * @param string $serverHost
 * @param string $serverToken
 * @param object $apiInstance
 * @param string $userEmail
 * @return array $creationResponse
 *
 * by Cinthia Romero
 */
function createAgreement($adobeBaseUri, $adobeSignToken, $requestDocuments, $requestData, $serverHost, $serverToken, $apiInstance, $userEmail)
{
    //Get Workflow characteristics
    $workflowUid = $requestData["YQP_ADOBE_WORKFLOW_SELECTED"];
    $workflowCharacteristics = getWorkflowDefinition($adobeBaseUri, $adobeSignToken, $workflowUid, $userEmail);
    $creationResponse = array(
        "success" => false,
        "errorMessage" => "",
        "agreementID" => ""
    );
    if (!empty($workflowCharacteristics["name"])) {
        //Form fileInfos array
        $documentsList = array();
        $documentError = "";
        foreach ($requestDocuments as $key=>$requestFile) {
            //Get document path
            $file = $apiInstance->getRequestFilesById($requestData['_request']['id'], $requestFile['YQP_PM_DOCUMENT_ID']);
            $documentPath = $file->getPathname();

            //Create document in Adobe Sign
            $adobeDocumentId = createTransientDocument($adobeBaseUri, $adobeSignToken, $requestFile['YQP_PM_DOCUMENT_NAME'], $documentPath, $userEmail);
            if ($adobeDocumentId["success"]) {
                $documentsList[] = array (
                    "transientDocumentId" => $adobeDocumentId["transientDocumentID"],
                    "label" => $requestFile['YQP_ADOBE_DOCUMENT_LABEL']
                );
            } else {
                $documentError = $adobeDocumentId["errorMessage"] . " FILE: " . $requestFile['YQP_PM_DOCUMENT_NAME'];
                break;
            }
        }

        //Form recipients array
        $recipientsList = array();
        foreach ($workflowCharacteristics["recipientsListInfo"] as $key=>$recipient) {
            $recipientsList[] = array(
                "memberInfos" => array (
                    0 => array (
                        "email" => $recipient["defaultValue"]
                    )
                ),
                "role" => $recipient["role"],
                "label" => $recipient["label"]
            );
        }

        //Form ccs array
        $ccsList = array();
        if (!empty($workflowCharacteristics["ccsListInfo"])) {
            foreach ($workflowCharacteristics["ccsListInfo"] as $key=>$cc) {
                if (!empty($cc["defaultValues"][0])) {
                    $ccsList[] = array(
                        "email" => $cc["defaultValues"][0],
                        "label" => $cc["label"]
                    );
                }
            }
        }

        //Define agreement due date
        $agreementDueDate = "";
        $agreementDueDateToShow = "";
        if (!empty($workflowCharacteristics["expirationInfo"])) {
            //Obtain the days to wait
            $daysToWait = $workflowCharacteristics["expirationInfo"]["defaultValue"];
            if ($daysToWait > 0) {
                $currentDate = date("Y-m-d");
                $agreementDueDateToShow = date("Y-m-d",strtotime($currentDate."+ " . $daysToWait . " days"));
                //Change format date to adobe format
                $agreementDueDate = $agreementDueDateToShow . "T00:00:00Z";
            } 
        }

        //If there was not any error create the agreement
        if (count($documentsList) > 0 && count($recipientsList) > 0 && $documentError == "") {
            //Reduce the length of client name to 150 characters in order to avoid issues with the subject length
            $clientName = substr(trim($requestData["YQP_CLIENT_NAME"]), 0, 150);
            //Reduce the length of vessel name to 50 characters in order to avoid issues with the subject length
            $vesselName = substr(trim($requestData["YQP_INTEREST_ASSURED"]), 0, 50);
            //Reduce the length of agreement default name and pivot number to 55 characters in order to avoid issues with the subject length
            $agreementSubjectFirstPart = $workflowCharacteristics["agreementNameInfo"]["defaultValue"] . " " . $requestData["YQP_PIVOT_TABLE_NUMBER"];
            $agreementSubjectFirstPart = substr(trim($agreementSubjectFirstPart), 0, 55);
            $agreementSubject = $agreementSubjectFirstPart . " - " . $clientName . " - " . $vesselName;
            //Get Yacht process UID
            $yachtProcessUid = getenv('FORTE_ID_YACHT');
            //Check if endorsement number should be added based on process uid
            if ($requestData['_request']['process_id'] != $yachtProcessUid) {
                $agreementSubject .= " - " . $requestData["END_NUMBER_ENDORSEMENT"];
            }
            $url = $adobeBaseUri . 'api/rest/v6/agreements';
            $postFields = array(
                'fileInfos' => $documentsList, 
                'name' => $agreementSubject,
                'participantSetsInfo' => $recipientsList,
                'signatureType'=> 'ESIGN', 
                'state'=> 'IN_PROCESS',
                'workflowId' => $workflowUid);
            if (count($ccsList) > 0) {
                $postFields["ccs"] = $ccsList;
            }
            if ($agreementDueDate != "") {
                $postFields["expirationTime"] = $agreementDueDate;
            }
            $postFields = json_encode($postFields);
            $curlResponse = callCurl($url, 'POST', $postFields, $adobeSignToken, 'application/json', $userEmail);
            if (!empty($curlResponse["code"])) {
                $creationResponse = array(
                    "success" => false,
                    "errorMessage" => $curlResponse["message"],
                    "agreementID" => "",
                    "expirationDate" => ""
                );
            } else {
                $creationResponse = array(
                    "success" => true,
                    "errorMessage" => "",
                    "agreementID" => $curlResponse["id"],
                    "expirationDate" => $agreementDueDateToShow
                );
            }
        } else {
            if ($documentError == "") {
                $documentError = "There was an error trying to get the files or recipients, please contact your system administrator.";
            }
            $creationResponse = array(
                "success" => false,
                "errorMessage" => $documentError,
                "agreementID" => "",
                "expirationDate" => ""
            );
        }
    } else {
        $creationResponse = array(
            "success" => false,
            "errorMessage" => "Workflow configuration could not be obtained, please contact your system administrator.",
            "agreementID" => "",
            "expirationDate" => ""
        );
    }
    
    return $creationResponse;
}

//Server Token
$serverHost = getenv('API_HOST');
$serverToken = getenv('API_TOKEN');
//Get Integrator Key
$adobeSignToken = getenv('ADOBE_TOKEN');
//Get current user email
$urlEmail = $serverHost . '/users/' . $data['YQP_USER_ID'];
$curlResponse = callCurl($urlEmail, 'GET', array(), $serverToken, 'application/json', "");
$currentUserEmail = $curlResponse['email'];

//Initialize variables
$agreementCreationError = "";
$agreementID = "";
$expirationDate = "";
$requestDocumentsArray = array();
$agreementCreationResponse = "";
$responseArray = array();
if ($adobeSignToken) {
    //Check Base Uri
    $baseUriResponse = getBaseUri($adobeSignToken, $currentUserEmail);
    if ($baseUriResponse["success"]) {
        $adobeBaseUri = $baseUriResponse["baseUri"];

        //Get All Documents For Current Request
        $url = $serverHost . '/requests/' . $data['_request']['id'] . '/files';
        $curlResponse = callCurl($url, 'GET', array(), $serverToken, 'application/json', "");

        //Get Generated Documents Type (PM Relation)
        $documentTypeRelation = $data['YQP_ADOBE_WORKFLOW_DOCUMENTS']['YQP_ADOBE_DOCUMENTS_GENERATED'];

        if (!empty($curlResponse['data']) && !empty($documentTypeRelation)) {
            foreach ($curlResponse['data'] as $requestFile) {
                foreach ($documentTypeRelation as $typeDocument) {
                    //Compares the variable of file with the variable configured
                    if ($requestFile['custom_properties']['data_name'] == $typeDocument['VARIABLE']) {
                        $requestDocumentsArray[] = array(
                            "YQP_ADOBE_DOCUMENT_LABEL" => $typeDocument['LABEL'],
                            "YQP_PM_DOCUMENT_ID" => $requestFile['id'],
                            "YQP_PM_DOCUMENT_NAME" => $requestFile["file_name"],
                            "YQP_DOCUMENT_GENERATED" => true
                        );
                    }    
                }
                foreach ($data['YQP_ADOBE_WORKFLOW_LIST'] as $documentsWorkflow) {
                    if ($requestFile['id'] == $documentsWorkflow['YQP_ADOBE_WORKFLOW_DOCUMENTS_UPLOAD']) {
                        $requestDocumentsArray[] = array(
                            "YQP_ADOBE_DOCUMENT_LABEL" => $documentsWorkflow['YQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL'],
                            "YQP_PM_DOCUMENT_ID" => $documentsWorkflow['YQP_ADOBE_WORKFLOW_DOCUMENTS_UPLOAD'],
                            "YQP_PM_DOCUMENT_NAME" => $requestFile["file_name"],
                            "YQP_DOCUMENT_GENERATED" => false
                        );
                    }
                }
            }
        }

        //Create Agreement
        if (count($requestDocumentsArray) > 0) {
            $apiInstance = $api->requestFiles();
            $agreementCreationResponse = createAgreement($adobeBaseUri, $adobeSignToken, $requestDocumentsArray, $data, $serverHost, $serverToken, $apiInstance, $currentUserEmail);
            if ($agreementCreationResponse["success"]) {
                $agreementID = $agreementCreationResponse["agreementID"];
                $expirationDate = $agreementCreationResponse["expirationDate"];
            } else {
                $agreementCreationError = $agreementCreationResponse["errorMessage"];
            }
        } else {
            $agreementCreationError = "Request documents could not be obtained, please contact your system administrator.";
        }
    } else {
        $agreementCreationError = $baseUriResponse["errorMessage"];
    }
} else {
    $agreementCreationError = "The adobe token was not configured, please contact your system administrator.";   
}
//Month Sent adobe sign
$responseArray['YQP_MONTH_SENT_ADOBE_REPORT'] = date('F', mktime(0, 0, 0, date('m'), 28));
$responseArray['YQP_ADOBE_USER_EMAIL'] = $currentUserEmail;
$responseArray['YQP_AGREEMENT_CREATED_ERROR'] = $agreementCreationError;
$responseArray['YQP_ADOBE_AGREEMENT_ID'] = $agreementID;
$responseArray['YQP_ADOBE_EXPIRATION_DATE'] = $expirationDate;
$responseArray["YQP_ADOBE_AGREEMENT_DOCUMENTS"] = $requestDocumentsArray;
//Forte Errors Screen
if ($agreementCreationError != "") {
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $agreementCreationError;
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_BODY'] = "Error Adobe Sign";
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_47";
    $responseArray['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YATCH - Send documents to Adobe Sign"; 
}

//Save to Gestion Solicitudes Collection
$searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
$searchRequest = callCurl($searchRequestUrl, "GET", array(), $serverToken, 'application/json', "");
$dataSave = array();
$dataSave['FORTE_REQUEST'] = $data['_request']['id'];
$dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
$dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
$dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];

$dataSave['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
$dataSave['YQP_STATUS'] = $data['YQP_STATUS'];
$dataSave['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
$dataSave['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
$dataSave['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
$dataSave['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
$dataSave['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
$dataSave['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
$dataSave['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
$dataSave['YQP_SITUATION'] = $data['YQP_SITUATION'];
$dataSave['YQP_TYPE'] = $data['YQP_TYPE'];
$dataSave['YQP_REASSURED_CEDENT_LABEL'] = $data['YQP_REASSURED_CEDENT']['LABEL'];
$dataSave['YQP_REINSURANCE_BROKER_LABEL'] = $data['YQP_REINSURANCE_BROKER']['LABEL'];
$dataSave['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = $data['YQP_BROKER_TOTAL_PREMIUM_REPORT'];
$dataSave['YQP_LINE_BUSINESS'] = $data['YQP_LINE_BUSINESS'];
$dataSave['YQP_SOURCE'] = $data['YQP_SOURCE'];
$dataSave['YQP_SUBMISSION_DATE_REPORT'] = $data['YQP_SUBMISSION_DATE_REPORT'];
$dataSave['YQP_SUBMISSION_MONTH_REPORT'] = $data['YQP_SUBMISSION_MONTH_REPORT'];
$dataSave['YQP_COMMENTS'] = $data['YQP_COMMENTS'];
$dataSave['YQP_RETROCESIONARY'] = $data['YQP_RETROCESIONARY'];
$dataSave['YQP_TERM'] = $data['YQP_TERM'];
$dataSave['YQP_SLIP_DOCUMENT_NAME'] = $data['YQP_SLIP_DOCUMENT_NAME'];
$dataSave['YQP_RISK_ATTACHING_MONTH'] = $data['YQP_RISK_ATTACHING_MONTH'];
$dataSave['YQP_MONTH_SENT_ADOBE_REPORT'] = $responseArray['YQP_MONTH_SENT_ADOBE_REPORT'];
$dataSave['YQP_FORTE_ORDER'] = empty($data['YQP_FORTE_ORDER']) ? "" : $data['YQP_FORTE_ORDER'];
//Saving mooring port and club marina to report (Added by Cinthia Romero 2023-11-03)
$mooringPortReport = empty($data['YQP_MOORING_PORT']) ? "" : $data['YQP_MOORING_PORT'];
if (empty($data['YQP_LOCATION_MOORING_PORT']) != true && $data['YQP_LOCATION_MOORING_PORT'] == "Other") {
    $mooringPortReport = empty($data['YQP_SPECIFY_PORT']) ? "" : $data['YQP_SPECIFY_PORT'];
}
$dataSave['YQP_MOORING_PORT_REPORT'] = $mooringPortReport;
$dataSave['YQP_CLUB_MARINA'] = empty($data['YQP_CLUB_MARINA']) ? "" : $data['YQP_CLUB_MARINA'];
$dataSave['YQP_REINSURER_INFORMATION'] = isset($data['YQP_REINSURER_INFORMATION']) ? $data['YQP_REINSURER_INFORMATION'] : [];
//

//Check Process Type
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
} else {
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
    $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    $dataSave['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
}
//Validate if the request exists
if (count($searchRequest["data"]) == 0) {
    $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records';
    $insertRequest = callCurl($insertRequestUrl, "POST", json_encode($dataSave), $serverToken, 'application/json', "");
    return $dataSave;
} else {
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
    $updateRequest = callCurl($updateRequestUrl, "PUT", json_encode($dataSave), $serverToken, 'application/json', "");
}

return $responseArray;