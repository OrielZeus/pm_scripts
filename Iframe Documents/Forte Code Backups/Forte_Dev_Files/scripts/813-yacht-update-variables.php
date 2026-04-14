<?php
/*******************************  
 * Update Variables
 *
 * by Helen Callisaya
 ******************************/
/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url)
{
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
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . getenv('API_TOKEN'),
            "Content-Type: application/json",
            "cache-control: no-cache"
        ),
    ));
    $responseCurl = curl_exec($curl);
    $responseCurl = json_decode($responseCurl, true);
    curl_close($curl);
    return $responseCurl;
}

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
function callGetCurlCollection($url, $method, $json_data)
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

//Define Variables
$apiHost = getenv('API_HOST');
$forteIdYacht = getenv('FORTE_ID_YACHT');
$forteProducingFilesId = getenv('FORTE_PRODUCING_FILES_ID');
$requestId = $data['_request']['id'];
$processId = $data['_request']['process_id'];

$dataClientInfo["YQP_SUBMIT_SAVE"] = "SUBMIT";
$dataProducing = array();
$dataReturn = array();

//----------------------
if ($data['YQP_STATUS'] == "BOUND" && $processId == $forteIdYacht) {
    //Save to Producing Files Collection
    $searchRequestUrl = $apiHost . '/collections/' . $forteProducingFilesId . '/records?pmql=(data.FORTE_REQUEST="' . $requestId . '")';
    $searchRequest = callGetCurlCollection($searchRequestUrl, "GET", "");
    $dataProducing['FORTE_REQUEST'] = $requestId;
    $dataProducing['FORTE_PROCESS'] = $processId;
    $dataProducing['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
    $dataProducing['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
    
    //Validate Process
    if ($processId == $forteIdYacht) {
        $dataProducing['FORTE_REQUEST_PARENT'] = $requestId;
        $dataProducing['FORTE_REQUEST_CHILD'] = $requestId;
        //Name Process
        $dataSave['FORTE_REQUEST_ORDER'] = $requestId . ".0";
    } else {
        $dataProducing['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
        $dataProducing['FORTE_REQUEST_CHILD'] = $requestId;
        $dataProducing['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
        //Name Process
        $dataProducing['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
    }

    $dataProducing['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
    $dataProducing['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
    $dataProducing['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
    $dataProducing['YQP_CATCH_MONTH_REPORT'] = $data['YQP_CATCH_MONTH_REPORT'];
    $dataProducing['YQP_PIVOT_TABLE_NUMBER'] = $data['YQP_PIVOT_TABLE_NUMBER'];
    $dataProducing['YQP_SLIP_DOCUMENT_NAME'] = $data['YQP_SLIP_DOCUMENT_NAME'];
    $dataProducing['YQP_TYPE'] = $data['YQP_TYPE'];
    $dataProducing['YQP_REASSURED_CEDENT_LABEL'] = $data['YQP_REASSURED_CEDENT']['LABEL'];
    $dataProducing['YQP_REINSURANCE_BROKER_LABEL'] = $data['YQP_REINSURANCE_BROKER']['LABEL'];
    $dataProducing['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
    $dataProducing['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
    $dataProducing['YQP_TRIMESTER'] = $data['YQP_TRIMESTER'];
    $dataProducing['YQP_YEAR_PERIOD'] = $data['YQP_YEAR_PERIOD'];
    $dataProducing['YQP_CURRENCY'] = $data['YQP_CURRENCY'];
    $dataProducing['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
    $dataProducing['DATA_WAR_SUM_INSURED_TENDER'] = $data['DATA_WAR_SUM_INSURED_TENDER'];
    $dataProducing['DATA_WAR_SUM_INSURED_NET'] = $data['DATA_WAR_SUM_INSURED'];
    $dataProducing['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
    $dataProducing['DATA_WAR_SUM_INSURED'] = $data['DATA_WAR_SUM_INSURED'];
    $dataProducing['YQP_PERSONAL_EFFECTS_LIMIT'] = $data['YQP_PERSONAL_EFFECTS_LIMIT'];
    $dataProducing['YQP_MEDICAL_PAYMENTS_LIMIT'] = $data['YQP_MEDICAL_PAYMENTS_LIMIT'];
    $dataProducing['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'] = $data['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'];
    $dataProducing['YQP_TOTAL_PREMIUM_FINAL'] = $data['YQP_TOTAL_PREMIUM_FINAL'];
    $dataProducing['YQP_DEDUCTIBLE'] = $data['YQP_DEDUCTIBLE'];
    $dataProducing['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'] = $data['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'];
    $dataProducing['YQP_WAR_DEDUCTIBLE_SLIP'] = $data['YQP_WAR_DEDUCTIBLE_SLIP'];
    $dataProducing['YQP_PI_DEDUCTIBLE_SLIP'] = $data['YQP_PI_DEDUCTIBLE_SLIP'];
    $dataProducing['YQP_NUMBER_PAYMENTS'] = $data['YQP_NUMBER_PAYMENTS'];
    $dataProducing['YQP_BROKER_PERCENTAGE'] = $data['YQP_BROKER_PERCENTAGE'];
    $dataProducing['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'] = $data['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'];
    $dataProducing['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] = $data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'];
    $dataProducing['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
    $dataProducing['YQP_TYPE_CODE_REPORT'] = $data['YQP_TYPE_CODE_REPORT'];
    $dataProducing['YQP_VESSEL_MARK_MODEL'] = $data['YQP_VESSEL_MARK_MODEL'];
    $dataProducing['YQP_LENGTH_UNIT_REPORT'] = $data['YQP_LENGTH_UNIT_REPORT'];
    $dataProducing['YQP_YEAR'] = $data['YQP_YEAR'];
    $dataProducing['YQP_NAVIGATION_LIMITS'] = $data['YQP_NAVIGATION_LIMITS'];
    $dataProducing['YQP_USE'] = $data['YQP_USE'];
    $dataProducing['YQP_LOSS_PAYEE'] = $data['YQP_LOSS_PAYEE'];
    $dataProducing['YQP_HULL_MATERIAL'] = $data['YQP_HULL_MATERIAL'];
    $dataProducing['YQP_FLAG'] = $data['YQP_FLAG'];
    $dataProducing['YQP_MOORING_PORT_REPORT'] = $data['YQP_MOORING_PORT_REPORT'];
    $dataProducing['YQP_CLUB_MARINA'] = $data['YQP_CLUB_MARINA'];
    $dataProducing['YQP_COMMENTS'] = $data['YQP_COMMENTS'];
    $dataProducing['YQP_USER_USERNAME'] = $data['YQP_USER_USERNAME'];
    $dataProducing['YQP_RISK_ATTACHING_MONTH'] = $data['YQP_RISK_ATTACHING_MONTH'];
    $dataProducing['YQP_TERM'] = $data['YQP_TERM'];
    $dataProducing['YQP_TYPE_YACHT'] = $data['YQP_TYPE_YACHT'];
    $dataProducing['YQP_RANGE_SI_HULL'] = $data['YQP_RANGE_SI_HULL'];
    $dataProducing['YQP_RANGE_YEAR'] = $data['YQP_RANGE_YEAR'];
    $dataProducing['YQP_STATUS'] = $data['YQP_STATUS'];
    //Calculate variables YQP_SUM_INSURED_HULL_CESSION_REPORT, YQP_RATE_CESSION_REPORT, YQP_TAXES_USD_100_REPORT used in Producing report. Added by Cinthia Romero 2024-01-08
    $reinsurerGridData = isset($data['YQP_REINSURER_INFORMATION']) ? $data['YQP_REINSURER_INFORMATION'] : [];
    if (count($reinsurerGridData) > 0 && !empty($data['DATA_WAR_SUM_INSURED'])) {
        foreach ($reinsurerGridData as $key=>$reinsurer) {
            //YQP_TAXES_USD_100_REPORT
            $reinsurerGridData[$key]['YQP_TAXES_USD_100_REPORT'] = $reinsurer['YQP_TOTAL_PREMIUM_GRID_TOTAL'] * ($reinsurer['YQP_TAX_ON_GROSS'] / 100);
        }
    }
    $dataProducing['YQP_REINSURER_INFORMATION'] = $reinsurerGridData;
    //-----------------Calculate Rate Cession and Hull Cession--------------------
    if ($data['YQP_PRODUCT'] != "PI_RC") {
        //(Total Hull Sum Insured * Forte Order) Add HC
        $dataProducing['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = $data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100);
        //(Total Premium * FORTE ORDER) / (Total Hull Sum Insured * Forte Order) Add HC
        $dataProducing['YQP_RATE_CESSION_REPORT'] = ($data['YQP_BROKER_TOTAL_PREMIUM_REPORT'] * ($data['YQP_FORTE_ORDER'] / 100)) / ($data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100));
    } else {
        $dataProducing['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = 0;
        $dataProducing['YQP_RATE_CESSION_REPORT'] = 0;
    }
    //---------------------------------------------------------------------------
    //Check Process Type
    if ($processId != $forteIdYacht) {
        $dataProducing['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    }
    //Validate if the request exists
    if (count($searchRequest["data"]) == 0) {
        $insertRequestUrl = $apiHost . '/collections/' . $forteProducingFilesId . '/records';
        $insertRequest = callGetCurlCollection($insertRequestUrl, "POST", json_encode($dataProducing));
    } else {
        $updateRequestUrl = $apiHost . '/collections/' . $forteProducingFilesId . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurlCollection($updateRequestUrl, "PUT", json_encode($dataProducing));
    }
}
return $dataReturn;