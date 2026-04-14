<?php 
/************************************************************  
 * Set Parameters for complete client information first part
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 ***********************************************************/
//Initialice the return array
$dataClientFirst = array();

//Clean message error
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
$dataClientFirst['FORTE_ERRORS'] = $requestError;

//Validate if the User did Save set as Pending the status
if ($data["YQP_SUBMIT_SAVE"] == "SAVE") {
    $dataClientFirst['YQP_STATUS'] = "PENDING";
}

//Set variable as Submit to do required all fields
$dataClientFirst['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Hidden variable when YQP_GROSS_BROKER_CHANGE variable is different from true
if ($data['YQP_GROSS_BROKER_CHANGE'] != true) {
    $dataClientFirst['YQP_BROKER_TOTAL_PREMIUM'] = '';
    $dataClientFirst['YQP_BROKER_PERCENTAGE'] = 0;
    $dataClientFirst['YQP_EXTRA_PAYMENT_REINSURER'] = '';
}

//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');

/*
* Function that calls the OpenL
*
* @param (String) $url
* @param (Object) $dataSend
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function callCurlOpenL($url, $dataSend)
{
    //Curl init
    $curl = curl_init();
    //Curl to the End point
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($dataSend),
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json"
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ));
    //Set response
    $responseCurl = curl_exec($curl);
    //Set error
    $errorCurl = curl_error($curl);
    curl_close($curl);

    //Set array to response
    $aDataResponse = array();
    $aDataResponse["ERROR"] = $errorCurl;
    $aDataResponse["DATA"] = $responseCurl;

    //Return Response
    return $aDataResponse;
}
/*
* Calls the processmaker api
*
* @param (string) $url 
* @return (Array) $responseCurl 
*
* by Helen Callisaya
*/
function callGetCurl($url, $method, $json_data)
{
    $token = getenv('API_TOKEN');
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $json_data,
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ),
    ));
    $responseCurl = curl_exec($curl);
    $responseCurl = json_decode($responseCurl, true);
    curl_close($curl);
    return $responseCurl;
}
/*********************** Get values Navigation Limits *********************************/
//Set necessary variables
$dataSend = array();
$dataSend['Country'] = $data["YQP_COUNTRY_BUSINESS"];
$dataSend['Language'] = $data["YQP_LANGUAGE"];

//Set data Send OpenL
$dataClientFirst["DATA_NAVIGATION_LIMITS_OPENL_SEND"] = $dataSend;

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetNavigationLimits";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClientFirst['PM_OPEN_NAVIGATION_LIMITS'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataClientFirst['FORTE_ERRORS'] = $requestError;    
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        for ($r = 0; $r < count($response); $r++) {
            if ($response[$r] != "") {
                $aOptions[$r] = array();
                $aOptions[$r]['LABEL'] = $response[$r];
            }
        }
        $dataClientFirst['PM_OPEN_NAVIGATION_LIMITS'] = $aOptions;
    }
}

/*********************** Get values Payments *********************************/
//Set necessary variables
$dataSend = array();
$dataSend['Language'] = $data["YQP_LANGUAGE"];

//Set data Send OpenL
$dataClientFirst["DATA_PAYMENTS_OPENL_SEND"] = $dataSend;

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetPayments";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClientFirst['PM_OPEN_NUMBER_PAYMENTS'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataClientFirst['FORTE_ERRORS'] = $requestError;    
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        for ($r = 0; $r < count($response); $r++) {
            if ($response[$r] != "") {
                $aOptions[$r] = array();
                $aOptions[$r]['LABEL'] = $response[$r];
            }
        }
        $dataClientFirst['PM_OPEN_NUMBER_PAYMENTS'] = $aOptions;
    }
}
//Save to Gestion Solicitudes Collection
$searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
$searchRequest = callGetCurl($searchRequestUrl, "GET", "");
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
$dataSave['YQP_REASSURED_CEDENT_LABEL'] = isset($data['YQP_REASSURED_CEDENT']['LABEL']) ? $data['YQP_REASSURED_CEDENT']['LABEL'] : "";
$dataSave['YQP_REINSURANCE_BROKER_LABEL'] = isset($data['YQP_REINSURANCE_BROKER']['LABEL']) ? $data['YQP_REINSURANCE_BROKER']['LABEL'] : "";
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
$dataSave['YQP_MONTH_SENT_ADOBE_REPORT'] = $data['YQP_MONTH_SENT_ADOBE_REPORT'];
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
    $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
} else {
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
    $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
}
//Return data
return $dataClientFirst;