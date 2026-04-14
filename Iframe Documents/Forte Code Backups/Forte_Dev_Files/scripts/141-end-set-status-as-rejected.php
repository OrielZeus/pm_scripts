<?php 
/*******************************  
 * Set status as Declined
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 ******************************/

/*
* Calls the processmaker api
*
* @param (string) $url 
* @param (string) $method 
* @param (string) $json_data 
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

if ($data['YQP_STATUS'] == "BOUND") {
    $dataProducing = array();
    $dataProducing['YQP_STATUS'] = "REJECTED";
    $producingRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
    $producingRequest = callGetCurl($producingRequestUrl, "GET", "");
    if (count($producingRequest["data"]) != 0) {
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records/' . $producingRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataProducing));
    } 
}

//Set variable of return
$dataEndCase = array();
//Set value as Rejected
$dataEndCase['YQP_STATUS'] = "REJECTED";
//Set situation in CLOSED
$dataEndCase['YQP_SITUATION'] = "CLOSED";

//Save to Gestion Solicitudes Collection
$searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
$searchRequest = callGetCurl($searchRequestUrl, "GET", "");
$dataSave = array();
$dataSave['FORTE_REQUEST'] = $data['_request']['id'];
$dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
$dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
$dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
$dataSave['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
$dataSave['YQP_STATUS'] = $dataEndCase['YQP_STATUS'];
$dataSave['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
$dataSave['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
$dataSave['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
$dataSave['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
$dataSave['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
$dataSave['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
$dataSave['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
$dataSave['YQP_SITUATION'] = $dataEndCase['YQP_SITUATION'];
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

//Validate Process
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
} else {
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
    $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
}
//Validate if the request exists
if (count($searchRequest["data"]) == 0) {
    $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records';
    $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
} else {
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
    $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
}

//Update status in collection DataOriginal
if ($data['_request']['process_id'] != getenv('FORTE_ID_YACHT')) {
    $typeEndorsement = $data['END_TYPE_ENDORSEMENT'];
    $idRequestOld = $data['END_REQUEST_ENDORSEMENT_OLD'];
    //First we get the Original data that saved the current request
    $getDataOriginalFromRequest = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?pmql=' . urlencode('(data.FORTE_OD_REQUEST = "' . $data['_request']['id'] . '")');
    $responseDataOriginalFromRequest = callGetCurl($getDataOriginalFromRequest, "GET", "");
    //Id collection current Request
    $idCollectionCurrentRequest = $responseDataOriginalFromRequest["data"][0]["id"];        
    //Update Request in collection Data original  
    $dataOriginalSave = array();
    $dataOriginalSave['FORTE_OD_REQUEST'] = $data['_request']['id'];
    $dataOriginalSave['FORTE_OD_PROCESS'] = $data['_request']['process_id'];
    $dataOriginalSave['FORTE_OD_OLD_REQUEST'] = $idRequestOld;
    $dataOriginalSave['FORTE_OD_DATA'] = $responseDataOriginalFromRequest['data'][0]['data']['FORTE_OD_DATA'];
    $dataOriginalSave['FORTE_OD_TYPE_ENDORSEMENT'] = $typeEndorsement;
    $dataOriginalSave['FORTE_OD_PARENT'] = $data['END_ID_SELECTION'];
    $dataOriginalSave['FORTE_OD_CANCEL'] = "REJECTED";  
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records/' . $idCollectionCurrentRequest;
    $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataOriginalSave));    
}

//Return all Values
return $dataEndCase;