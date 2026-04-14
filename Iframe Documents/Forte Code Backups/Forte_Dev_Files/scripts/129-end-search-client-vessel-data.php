<?php 
/**************************************************  
 * Search Client Name, vessel name and endorsement
 *
 * by Helen Callisaya
 * modified by Cinthia Romero
 *************************************************/
/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url, $method, $json_data)
{
    try {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . getenv('API_TOKEN'),
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $responseCurl = curl_exec($curl);
        if ($responseCurl === false) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }
        $responseCurl = json_decode($responseCurl, true);
        curl_close($curl);
        return $responseCurl;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

/* 
 * Insert or update data in collection
 *
 * @param (string) $idCollection
 * @param (string) $requestId
 * @param (string) $json_data  
 * @return none
 *
 * by Helen Callisaya
 */
function saveUpdateCollection($idCollection, $requestId, $json_data, $fieldRequest)
{
    $searchRequestUrl = getenv('API_HOST') . '/collections/' . $idCollection . '/records?pmql=(data.' . $fieldRequest . '="' . $requestId . '")';
    $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
    if (count($searchRequest["data"]) == 0) {
        $insertRequestUrl = getenv('API_HOST') . '/collections/' . $idCollection . '/records';
        $insertRequest = callGetCurl($insertRequestUrl, "POST", $json_data);
    } else {
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . $idCollection . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", $json_data);
    }    
}

/*
* Execute Curl on Collection
*
* @param (String) $collectionID
* @param (String) $apiHost
* @param (String) $apiToken
* @param (String) $pmql
* @param (String) $curlType
* @param (String) $dataPost
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function executeCurlCollection($collectionID, $apiHost, $apiToken, $pmql, $curlType, $dataPost)
{
    //Curl init
    $curl = curl_init();
    //Curl to the End point
    switch ($curlType) {
	    case 'GET':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . "/collections/" . $collectionID . "/records" . $pmql,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiToken
                ),
            ));
		break;
	    case 'POST':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . '/collections/' . $collectionID . '/records',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataPost,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: text/plain'
                ),
            ));
		break;
    }
    //Set response
    $response = curl_exec($curl);
    curl_close($curl);
    //Response Json decode
    $response = json_decode($response, true);

    //Return data
    return $response["data"];
}
//Name Process
$dataResult['FORTE_TITLE_PROCESS'] = "Yacht Endorsement Process";
//Set message error if it is needed
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_BODY' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => '',
    'FORTE_ERROR_PROCESS_ID' => $data['_request']['process_id'],
    'FORTE_ERROR_PROCESS_NAME' => $data['_request']['name']
];
$creationErrorMessage = "";

//Search Data Request
$idSearch = $data['END_ID_SELECTION'];
$requestId = $data['_request']['id'];
$processID = $data['_request']["process_id"];
$typeEndorsement = $data['END_TYPE_ENDORSEMENT'];
$idUserFullName = $data['YQP_USER_FULLNAME'];
$idUser = $data['YQP_USER_ID'];
//Validate Yacht in progress
$continueRequest = "YES";
$nameSelection = $data['END_NAME_SELECTION'];
$urlApiYacht = getenv('API_HOST') . '/requests?type=in_progress&order_by=updated_at&order_direction=desc&per_page=100&pmql=';
$pmqlSearchYacht = '(request = "Yacht Quotation Process"';
$urlApiSearchYacht = $urlApiYacht . urlencode($pmqlSearchYacht . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . strtolower($nameSelection['YQP_CLIENT_NAME_SELECT']) . '%" AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . strtolower($nameSelection['YQP_VESSEL_NAME_SELECT']) . '%")');
$responseSearchYacht = callGetCurl($urlApiSearchYacht, "GET", "");
if (count($responseSearchYacht['data']) > 0) {
    if($typeEndorsement != "Coverage Extension") {
        $continueRequest = "NO";
    }
}
if ($continueRequest == "NO") {
    $dataResult['END_CASE_CAN_CONTINUE'] = "ERROR";
    $creationErrorMessage = "The selected yacht has request " . $responseSearchYacht['data'][0]['id'] . " in PROGRESS, so you can only create an Extended Coverage Endorsement. \n El yate seleccionado tiene la solicitud " . $responseSearchYacht['data'][0]['id'] . " en PROGRESO, por lo que solo puede crear un Endoso de Extension de Cobertura."; 
} else {
    $urlRoot = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?order_by=updated_at&order_direction=desc&pmql=';
    $collectionRequestID = getenv('FORTE_QUOTE_NUMBER');
    //Check if exist a Cancellation Endorsement Completed for the YATCH case selected
    $getCancelledEndorsementUrl = $urlRoot . urlencode('(data.FORTE_REQUEST_PARENT = "' . $idSearch . '" AND data.FORTE_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_REQUEST_CHILD != "' . $requestId . '" AND data.END_TYPE_ENDORSEMENT = "Cancelled" AND data.YQP_STATUS = "BOUND")');
    $cancelledEndorsementResponse = callGetCurl($getCancelledEndorsementUrl, "GET", "");
    $cancelledEndorsement = $cancelledEndorsementResponse['data'];
   // return $cancelledEndorsement;
    if (count($cancelledEndorsement) > 0) {
        $dataResult['END_CASE_CAN_CONTINUE'] = "ERROR";
        $creationErrorMessage = "The endorsement for the selected yacht case cannot be created because a CANCELLATION ENDORSEMENT has already been issued.\n No se puede crear el endoso para el caso de Yate seleccionado porque ya se ha CREADO un ENDOSO DE CANCELACION."; 
    } else {
        $requestCanceled = '';
        //----------------Validate if there are requests in progress that are in the collection----------------
        $searchProgressCollection = $urlRoot . urlencode('(data.FORTE_REQUEST_PARENT = "' . $idSearch . '" AND data.FORTE_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_REQUEST_CHILD != "' . $requestId . '")');
        $responseSearchProgressCollection = callGetCurl($searchProgressCollection, "GET", "");
        $progressCollection = $responseSearchProgressCollection['data'];
        $requestInProgress = '';
        $inProgressCount = 0;
        for ($i = 0; $i < count($progressCollection); $i++ ) {
            $searchInProgressUrl = getenv('API_HOST') . '/requests/' . $progressCollection[$i]['data']['FORTE_REQUEST'];
            $searchEndorsement = callGetCurl($searchInProgressUrl, "GET", "");
            if ($searchEndorsement['status'] == "ACTIVE") {
                $requestInProgress = $progressCollection[$i]['data']['FORTE_REQUEST'];
                $inProgressCount = $inProgressCount + 1;
            }
            //Add request Canceled
            if ($searchEndorsement['status'] == "CANCELED") {
                $requestCanceled .= '"' . $progressCollection[$i]['data']['FORTE_REQUEST'] . '",';
            }
        }
        //Number Endorsement
        $numberEndorsement = count($progressCollection);
        //----------------------------------------------------------------------------------------------------
        
        //Validate Request in Progress
        if ($inProgressCount > 0) {
            $dataResult['END_CASE_CAN_CONTINUE'] = "ERROR"; 
            $creationErrorMessage = "The selected Yacht has endorsement number " . $requestInProgress . " in PROGRESS. \n El yate seleccionado tiene el número de endoso " . $requestInProgress . " en PROGRESO."; 
        } else {
            $requestCanceled .= '"' . $requestId . '"';
            $urlHistory = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?order_by=updated_at&order_direction=desc&pmql=';
            //$searchRequestUrl = $urlHistory . urlencode('(data.FORTE_OD_PARENT = "' . $idSearch . '" AND data.FORTE_OD_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_OD_REQUEST != "' . $requestId . '" AND data.FORTE_OD_CANCEL = "NO")');
            $searchRequestUrl = $urlHistory . urlencode('(data.FORTE_OD_PARENT = "' . $idSearch . '" AND data.FORTE_OD_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_OD_REQUEST NOT IN [' . $requestCanceled . '] AND data.FORTE_OD_CANCEL = "NO")');
            $responseSearchRequest = callGetCurl($searchRequestUrl, "GET", "");
            $requestCollection = $responseSearchRequest['data'];
            $dataIdRequest = "";
            $countEndorsement = 0;
            if (count($requestCollection) > 0) {
                for ($i = 0; $i < count($requestCollection); $i++ ) {
                    $countEndorsement = $countEndorsement + 1;
                    if ($countEndorsement == 1) {
                        $dataIdRequest = $requestCollection[0]['data']['FORTE_OD_REQUEST'];
                    }
                }
            } else {
                if ($typeEndorsement == "Cancel Endorsement") {
                    $dataResult['END_CASE_CAN_CONTINUE'] = "ERROR"; 
                    $creationErrorMessage = "There are not endorsements to cancel. \N No existen endosos para cancelar.";
                } else {
                    //When not exist Endorsement
                    $dataIdRequest = $idSearch;
                }
            }
            //-----------*******************************************------------------------
            if ($dataIdRequest != "") {
                $urlApi = getenv('API_HOST') . '/requests/' . $dataIdRequest . '?include=data';
                $dataResponse = callGetCurl($urlApi, "GET", "");
                $requestResponse = $dataResponse['data'];
                //Data obtained from the request
                $dataResult = $requestResponse;
                //Clear Variables
                $typeEndorsementOld = isset($dataResult['END_TYPE_ENDORSEMENT']) ? $dataResult['END_TYPE_ENDORSEMENT'] : "";
                //$validityEndorsementOld = isset($dataResult['END_VALIDITY_ENDORSEMENT']) ? $dataResult['END_VALIDITY_ENDORSEMENT'] : "";
                $validityEndorsementOld = isset($dataResult['END_VALIDITY_ENDORSEMENT']) ? $dataResult['END_VALIDITY_ENDORSEMENT'] : $dataResult['YQP_PERIOD_FROM'];
                $newPeriodToOld = isset($dataResult['END_NEW_PERIOD_TO']) ? $dataResult['END_NEW_PERIOD_TO'] : "";

                //Clear Variable Endorsement
                foreach ($dataResult as $key => $value) {
                    if (substr($key, 0, 3) == "END") {
                        unset($dataResult[$key]);                    
                    }
                }
                unset($dataResult['YQP_USER_ID']);
                unset($dataResult['YQP_REQUESTOR_NAME']);
                unset($dataResult['YQP_CREATE_DATE']);
                unset($dataResult['YQP_STATUS']);
                unset($dataResult['FORTE_TITLE_PROCESS']);
                unset($dataResult['YQP_DATA_REQUEST']);
                unset($dataResult['YQP_ADOBE_WORKFLOW_SELECTED']);
                unset($dataResult['YQP_ADOBE_WORKFLOW_DOCUMENTS']);
                unset($dataResult['YQP_VALIDATE_REQUIRED']);            
                unset($dataResult['PM_OPEN_ENDORSEMENTS_TYPE']);

                //------------------Exists premium of endorsement-------------------------
                //Get Old Premium
                $urlHistoryPremium = getenv('API_HOST') . '/collections/' . getenv('FORTE_ENDORSEMENT_PREMIUM_ID') . '/records?order_by=updated_at&order_direction=desc&pmql=';
                $searchHistoryPremium = $urlHistoryPremium . urlencode('(data.FORTE_EP_REQUEST_PARENT = "' . $idSearch . '" AND data.FORTE_EP_REQUEST != "' . $requestId . '")');
                $responseSearchHistoryPremium = callGetCurl($searchHistoryPremium, "GET", "");
                $dataHistoryCollection = $responseSearchHistoryPremium['data'];
                $dataHistory = array();
                if (count($dataHistoryCollection) > 0) {
                    $dataHistory['FORTE_END_CALCULATE_TYPE'] = "OTHER";
                    $dataHistory['FORTE_END_TYPE_ENDORSEMENT'] = $dataHistoryCollection[0]['data']['FORTE_EP_TYPE_ENDORSEMENT'];
                    $dataHistory['FORTE_END_ORIGINAL_PREMIUM'] = $dataHistoryCollection[0]['data']['FORTE_EP_NEW_ANNUAL_PREMIUM'];
                    $dataHistory['FORTE_END_PERIOD_FROM'] = $dataHistoryCollection[0]['data']['FORTE_EP_PERIOD_FROM'];
                    $dataHistory['FORTE_END_PERIOD_TO'] = $dataHistoryCollection[0]['data']['FORTE_EP_NEW_PERIOD_TO'];
                    $dataHistory['FORTE_END_NEW_PREMIUM'] = $dataHistoryCollection[0]['data']['FORTE_EP_ANNUAL_RISK_PREMIUM'];
                    $dataHistory['FORTE_END_DAYS_DIFERENCE'] = $dataHistoryCollection[0]['data']['FORTE_EP_TOTAL_DAYS'];
                    $dataHistory['FORTE_END_PERIOD_TO_ORIGINAL'] = $dataHistoryCollection[count($dataHistoryCollection) - 1]['data']['FORTE_EP_PERIOD_TO'];
                    $sumCumulutive = 0;
                    for ($y = 0; $y < count($dataHistoryCollection); $y++) {
                        $sumCumulutive = $sumCumulutive + $dataHistoryCollection[$y]['data']['FORTE_EP_VALUE_PREMIUM_ENDORSEMENT'];
                    }
                    $dataHistory['FORTE_END_CUMULUTIVE'] = $sumCumulutive;//Sumatoria Primas
                } else {
                    $totalPremiumOld = $dataResult['YQP_TOTAL_PREMIUM'];
                    if ($dataResult['YQP_GROSS_BROKER_CHANGE']) {
                        $totalPremiumOld = $dataResult['YQP_BROKER_TOTAL_PREMIUM'];
                    }
                    $dataHistory['FORTE_END_CALCULATE_TYPE'] = "FIRST";
                    $dataHistory['FORTE_END_TYPE_ENDORSEMENT'] = "-";
                    $dataHistory['FORTE_END_ORIGINAL_PREMIUM'] = $totalPremiumOld;
                    $dataHistory['FORTE_END_PERIOD_FROM'] = $dataResult["YQP_PERIOD_FROM"];
                    $dataHistory['FORTE_END_PERIOD_TO'] = $dataResult["YQP_PERIOD_TO"];
                    $dataHistory['FORTE_END_PERIOD_TO_ORIGINAL'] = $dataResult["YQP_PERIOD_TO"];
                    $dataHistory['FORTE_END_NEW_PREMIUM'] = $totalPremiumOld;
                    $dataHistory['FORTE_END_DAYS_DIFERENCE'] = $dataResult['YQP_DAYS_DIFFERENCE'];
                    $dataHistory['FORTE_END_CUMULUTIVE'] = 0;
                }
                $dataResult['END_CALCULATE_HISTORY'] = $dataHistory;
                //----------------------------------------------------------------------                   
                //Set variable of return 
                $dataResult['YQP_CLIENT_NAME_DISABLE'] = $dataResult['YQP_CLIENT_NAME'];
                $dataResult['YQP_INTEREST_ASSURED_DISABLE'] = $dataResult['YQP_INTEREST_ASSURED'];
    
                $dataResult['YQP_QUOTE_DOCUMENTS'] = null;
                $dataResult['FORTE_ERRORS'] = null;
                $dataResult['YQP_DOWNLOAD_SLIP_DOCUMENT_URL'] = null;
                $dataResult['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_NAME_BUTTON'] = null;
                $dataResult['YQP_DOWNLOAD_SLIP_DOCUMENT'] = null;
                $dataResult['YQP_OBTAIN_WORKFLOWS_ERROR'] = null;
                $dataResult['YQP_AGREEMENT_CREATED_ERROR'] = null;
                $dataResult['YQP_STATUS_MESSAGE_SEND_MAIL'] = null;
                $dataResult['YQP_STATUS_SEND_MAIL'] = null;
                $dataResult['YQP_REJECTED_REASON'] = null;
                $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT_URL'] = null;
                $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT_URL_NAME_BUTTON'] = null;
                $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT'] = null;
                $dataResult['YQP_DOWNLOAD_QUOTE_DOCUMENT_URL'] = null;
                $dataResult['YQP_DOWNLOAD_QUOTE_DOCUMENT_URL_NAME_BUTTON'] = null;
                $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT'] = null;
                $dataResult['YQP_ERROR_SLIP'] = null;
                $dataResult['YQP_ERROR_MESSAGE_SLIP'] = null;
                $dataResult['YQP_ADOBE_WORKFLOW_LIST'] = null;
                $dataResult['YQP_CONFIRMATION_DOCUMENTS'] = null;
                //Variable to refer to the "Type of endorsement" flow
                $dataResult['END_CASE_CAN_CONTINUE'] = $typeEndorsement;
                $dataResult['END_NUMBER_ENDORSEMENT_TO_CANCEL'] = isset($requestResponse['END_NUMBER_ENDORSEMENT']) ? $requestResponse['END_NUMBER_ENDORSEMENT'] : "";
                $dataResult['END_NUMBER_ENDORSEMENT'] = $numberEndorsement + 1;//$countEndorsement + 1;
                $dataResult['END_PERIOD_FROM_ORIGINAL'] = $dataResult['YQP_PERIOD_FROM'];
                $dataResult['END_PERIOD_TO_ORIGINAL'] = $dataResult['YQP_PERIOD_TO'];
                $dataResult['END_TOTAL_PREMIUM_ORIGINAL'] = $dataResult['YQP_TOTAL_PREMIUM'];
                $dataResult['END_GROSS_BROKER_CHANGE_ORIGINAL'] = $dataResult['YQP_GROSS_BROKER_CHANGE'];
                $dataResult['END_BROKER_TOTAL_PREMIUM_ORIGINAL'] = $dataResult['YQP_BROKER_TOTAL_PREMIUM'];
                $dataResult['END_OTHER_DEDUCTIBLES_RESPONSE_ORIGINAL'] = $dataResult['YQP_OTHER_DEDUCTIBLES_RESPONSE'];
                $dataResult['END_TYPE_ENDORSEMENT_OLD'] = $typeEndorsementOld;
                $dataResult['END_REQUEST_ENDORSEMENT_OLD'] = $dataIdRequest;
                if ($validityEndorsementOld != '') {
                    $dataResult['END_VALIDITY_ENDORSEMENT_OLD'] = $validityEndorsementOld;
                }
                if ($newPeriodToOld != '') {
                    $dataResult['END_NEW_PERIOD_TO_OLD'] = $newPeriodToOld;
                }
                $dataResult['END_VALIDITY_ENDORSEMENT'] = "";
                $dataResult['END_REASON_ENDORSEMENT'] = "";
                $dataResult['END_ADDITIONAL_INFORMATION_ENDORSEMENT'] = "";
                //Get last Validity Endorsement
                $urlValidity = getenv('API_HOST') . '/collections/' . getenv('FORTE_ENDORSEMENT_PREMIUM_ID') . '/records?order_by=updated_at&order_direction=desc&pmql=';
                $pmqlValidity = $urlValidity . urlencode('(data.FORTE_EP_REQUEST_PARENT = "' . $idSearch . '" AND data.FORTE_EP_REQUEST != "' . $data['_request']['id'] . '")');
                $getValidity = callGetCurl($pmqlValidity, "GET", "");
                $requestDataValidity = $getValidity['data'];
                $dataResult['END_LAST_VALIDITY_ENDORSEMENT'] = isset($requestDataValidity[0]['data']['FORTE_EP_VALIDITY_ENDORSEMENT']) ? $requestDataValidity[0]['data']['FORTE_EP_VALIDITY_ENDORSEMENT'] : $dataResult['YQP_PERIOD_FROM'];               
                //Get Number Quote
                /*********************** Set Request Quote Number *********************************/
                //Validate if the request number for this process and request already exist
                $pmqlRequest = "?pmql=" . urlencode("(data.YQP_PROCESS_ID = " . $processID . " AND data.YQP_REQUEST_ID = " . $requestId . ")");
                $validateQuote = executeCurlCollection($collectionRequestID, getenv('API_HOST'), getenv('API_TOKEN'), $pmqlRequest, "GET", array());
                //Validate if response is null
                if ($validateQuote == null || $validateQuote == '') {
                    $validateQuote = array();
                }
                if (count($validateQuote) > 0) {
                    //The Quote number already exist
                    $dataResult['END_QUOTE_NUMBER'] = $validateQuote[0]["data"]["YQP_REQUEST_UNIQUE_ID"];
                } else {
                    //Validate if there is values for this process
                    $pmqlRequest = "?pmql=(data.YQP_PROCESS_ID=" . $processID . ")&order_direction=desc&order_by=id";
                    $validateProcess = executeCurlCollection($collectionRequestID, getenv('API_HOST'), getenv('API_TOKEN'), $pmqlRequest, "GET", array());
                    //Validate if response is null
                    if ($validateProcess == null || $validateProcess == '') {
                        $validateProcess = array();
                    }
                    if (count($validateProcess) > 0) {
                        //Add one number
                        $requestUid = $validateProcess[0]["data"]["YQP_REQUEST_UNIQUE_ID"];
                        $requestUid = ($requestUid * 1) + 1;
                        //Set values to CURL POST
                        $dataPost = array(
                            "data" => array(
                                "YQP_PROCESS_ID" => $processID,
                                "YQP_REQUEST_ID" => $requestId,
                                "YQP_REQUEST_UNIQUE_ID" => $requestUid
                            )
                        );
                        //Encode values to Post 
                        $dataPost = json_encode($dataPost);
                        //Post data
                        $pmqlRequest = "";
                        $validateProcess = executeCurlCollection($collectionRequestID, getenv('API_HOST'), getenv('API_TOKEN'), $pmqlRequest, "POST", $dataPost);
                
                        //Set Quote Number
                        $dataResult['END_QUOTE_NUMBER'] = $requestUid;
                    } else {
                        //Add the first record
                        $requestUid = 1;
                        //Set values to CURL POST
                        $dataPost = array(
                            "data" => array(
                                "YQP_PROCESS_ID" => $processID,
                                "YQP_REQUEST_ID" => $requestId,
                                "YQP_REQUEST_UNIQUE_ID" => $requestUid
                            )
                        );
                        //Encode values to Post 
                        $dataPost = json_encode($dataPost);
                        //Post data
                        $pmqlRequest = "";
                        $validateProcess = executeCurlCollection($collectionRequestID, getenv('API_HOST'), getenv('API_TOKEN'), $pmqlRequest, "POST", $dataPost);
                
                        //Set Quote Number
                        $dataResult['END_QUOTE_NUMBER'] = $requestUid;
                    }
                }
    
    
                //Save Data Original in Collection
                $dataOriginalSave = array();
                $dataOriginalSave['FORTE_OD_REQUEST'] = $data['_request']['id'];
                $dataOriginalSave['FORTE_OD_PROCESS'] = $data['_request']['process_id'];
                $dataOriginalSave['FORTE_OD_PARENT'] = $data['END_ID_SELECTION'];
                $dataOriginalSave['FORTE_OD_DATA'] = $requestResponse;
                $dataOriginalSave['FORTE_OD_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
                $dataOriginalSave['FORTE_OD_CANCEL'] = "NO";
                saveUpdateCollection(getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT'), $requestId, json_encode($dataOriginalSave), 'FORTE_OD_REQUEST');
                //Save (Insert or Update ) in collection Gestion Solicitudes
                $dataSave = array();
                $dataSave['FORTE_REQUEST'] = $data['_request']['id'];
                $dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
                $dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
                $dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
                $dataSave['YQP_CLIENT_NAME'] = $dataResult['YQP_CLIENT_NAME'];
                $dataSave['YQP_STATUS'] = $data['YQP_STATUS'];
                $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
                $dataSave['FORTE_REQUEST_PARENT'] = $idSearch;
                $dataSave['FORTE_REQUEST_ORDER'] = $idSearch . "." . $dataResult['END_NUMBER_ENDORSEMENT'];
                $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];    
                $dataSave['YQP_INTEREST_ASSURED'] = $dataResult['YQP_INTEREST_ASSURED'];
                $dataSave['YQP_PERIOD_FROM_REPORT'] = $dataResult['YQP_PERIOD_FROM_REPORT'];
                $dataSave['YQP_PERIOD_TO_REPORT'] = $dataResult['YQP_PERIOD_TO_REPORT'];
                $dataSave['YQP_TYPE_VESSEL_REPORT'] = $dataResult['YQP_TYPE_VESSEL_REPORT'];
                $dataSave['YQP_SUM_INSURED_VESSEL'] = $dataResult['YQP_SUM_INSURED_VESSEL'];
                $dataSave['YQP_LIMIT_PI'] = $dataResult['YQP_LIMIT_PI'];
                $dataSave['YQP_COUNTRY_BUSINESS'] = $dataResult['YQP_COUNTRY_BUSINESS'];
                $dataSave['YQP_SITUATION'] = $dataResult['YQP_SITUATION'];
                $dataSave['YQP_TYPE'] = $dataResult['YQP_TYPE'];
                $dataSave['YQP_REASSURED_CEDENT_LABEL'] = isset($dataResult['YQP_REASSURED_CEDENT']['LABEL']) ? $dataResult['YQP_REASSURED_CEDENT']['LABEL'] : "";
                $dataSave['YQP_REINSURANCE_BROKER_LABEL'] = isset($dataResult['YQP_REINSURANCE_BROKER']['LABEL']) ? $dataResult['YQP_REINSURANCE_BROKER']['LABEL'] : "";
                $dataSave['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = $dataResult['YQP_BROKER_TOTAL_PREMIUM_REPORT'];
                $dataSave['YQP_LINE_BUSINESS'] = $dataResult['YQP_LINE_BUSINESS'];
                $dataSave['YQP_SOURCE'] = $dataResult['YQP_SOURCE'];
                $dataSave['YQP_SUBMISSION_DATE_REPORT'] = $dataResult['YQP_SUBMISSION_DATE_REPORT'];
                $dataSave['YQP_SUBMISSION_MONTH_REPORT'] = $dataResult['YQP_SUBMISSION_MONTH_REPORT'];
                $dataSave['YQP_COMMENTS'] = $dataResult['YQP_COMMENTS'];
                $dataSave['YQP_RETROCESIONARY'] = $dataResult['YQP_RETROCESIONARY'];
                $dataSave['YQP_TERM'] = $dataResult['YQP_TERM'];
                $dataSave['YQP_SLIP_DOCUMENT_NAME'] = $dataResult['YQP_SLIP_DOCUMENT_NAME'];
                $dataSave['YQP_RISK_ATTACHING_MONTH'] = $dataResult['YQP_RISK_ATTACHING_MONTH'];
                $dataSave['YQP_MONTH_SENT_ADOBE_REPORT'] = $dataResult['YQP_MONTH_SENT_ADOBE_REPORT'];
                $dataSave['YQP_FORTE_ORDER'] = empty($dataResult['YQP_FORTE_ORDER']) ? "" : $dataResult['YQP_FORTE_ORDER'];
                //Saving mooring port and club marina to report (Added by Cinthia Romero 2023-11-03)
                $mooringPortReport = empty($dataResult['YQP_MOORING_PORT']) ? "" : $dataResult['YQP_MOORING_PORT'];
                if (empty($dataResult['YQP_LOCATION_MOORING_PORT']) != true && $dataResult['YQP_LOCATION_MOORING_PORT'] == "Other") {
                    $mooringPortReport = empty($dataResult['YQP_SPECIFY_PORT']) ? "" : $dataResult['YQP_SPECIFY_PORT'];
                }
                $dataSave['YQP_MOORING_PORT_REPORT'] = $mooringPortReport;
                $dataSave['YQP_CLUB_MARINA'] = empty($dataResult['YQP_CLUB_MARINA']) ? "" : $dataResult['YQP_CLUB_MARINA'];
                $dataSave['YQP_REINSURER_INFORMATION'] = isset($dataResult['YQP_REINSURER_INFORMATION']) ? $dataResult['YQP_REINSURER_INFORMATION'] : [];
                //

                saveUpdateCollection(getenv('FORTE_GESTION_SOLICITUDES_ID'), $requestId, json_encode($dataSave), 'FORTE_REQUEST');
            }
        }
    }

}
if ($creationErrorMessage != "") {
    $requestError['FORTE_ERROR_LOG'] = "Endorsement Creation Conflict";
    $requestError['FORTE_ERROR_BODY'] = $creationErrorMessage;
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "Endorsement Creation Conflict"; 
}
$dataResult['FORTE_ERRORS'] = $requestError;
return $dataResult;