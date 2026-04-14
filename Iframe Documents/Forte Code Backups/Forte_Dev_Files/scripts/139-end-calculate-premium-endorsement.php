<?php 
/*  
 * Calculate Premium Endorsement
 * by Helen Callisaya
 */
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

$dataReturn = array();
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
$dataReturn['FORTE_ERRORS'] = $requestError;

//Get Old Premium
$totalPremiumOld = $data['END_TOTAL_PREMIUM_ORIGINAL'];
if ($data["END_GROSS_BROKER_CHANGE_ORIGINAL"]) {
    $totalPremiumOld = $data["END_BROKER_TOTAL_PREMIUM_ORIGINAL"];
}
//Get correct Total Premium New
$totalPremiumNew = $data["YQP_TOTAL_PREMIUM"];
if ($data["YQP_GROSS_BROKER_CHANGE"]) {
    $totalPremiumNew = $data["YQP_BROKER_TOTAL_PREMIUM"];
}
//Validate Endorsement 
if (round($totalPremiumNew, 2) > round($totalPremiumOld, 2)) {
    $statusPremium = "INCREASE";
} else {
    if (round($totalPremiumNew, 2) < round($totalPremiumOld, 2)) {
        $statusPremium = "DECREASE";
    } else {
        $statusPremium = "EQUAL";
    }
}
//Compare YQP_OTHER_DEDUCTIBLES_RESPONSE OLD and YQP_OTHER_DEDUCTIBLES_RESPONSE NEW
$dataReturn['END_OTHER_DEDUCTIBLES_CHANGE'] = "NO";
if ($data['END_OTHER_DEDUCTIBLES_RESPONSE_ORIGINAL'] != $data['YQP_OTHER_DEDUCTIBLES_RESPONSE']) {
    $dataReturn['END_OTHER_DEDUCTIBLES_CHANGE'] = "YES";
}

//----------------------------------------------------
$dataHistory = $data['END_CALCULATE_HISTORY'];

$periodFrom = $dataHistory['FORTE_END_PERIOD_FROM'];
$periodTo = $dataHistory['FORTE_END_PERIOD_TO'];
$premiumOriginalOld = $dataHistory['FORTE_END_ORIGINAL_PREMIUM'];//Original Premium
$premiumNewOld = $dataHistory['FORTE_END_NEW_PREMIUM']; //Prima que se arrastra
//$cumulutivePremium= $dataHistory['FORTE_END_CUMULUTIVE'];
$totalDays = $dataHistory['FORTE_END_DAYS_DIFERENCE'];//Dias del rango de fechas
$typeEndorsementOld = $dataHistory['FORTE_END_TYPE_ENDORSEMENT'];
$calculateType = $dataHistory['FORTE_END_CALCULATE_TYPE'];
//------------------------------Calculate Premium-------------------------------------------
$validity = $data['END_VALIDITY_ENDORSEMENT'];
$validityEndorsement  = new DateTime($validity);
$periodFromDate = new DateTime($periodFrom);
$periodToDate = new DateTime($periodTo);

//Get Days Original Coverage - Dias Transcurridos
$periodToDate = new DateTime($periodTo);
$differenceValidity = $validityEndorsement->diff($periodFromDate);
$daysCoverage = $differenceValidity->days;
//Get Premium Coverage - Prima Transcurrida
$premiumCoverage = ($premiumOriginalOld / $totalDays) * $daysCoverage;

//Get Days Coverage Accrue - Dias Devengar
$differenceValidity2 = $validityEndorsement->diff($periodToDate);
$daysCoverageAccrue = $differenceValidity2->days;//157
//Get Earned Premium - Prima devengar
$earnedPremium = ($totalPremiumNew / $totalDays) * $daysCoverageAccrue;

//Get Risk Annual Premium - Prima anual del riesgo (Prima Transcurrida + Prima Devengada)
$riskAnnualPremium = $premiumCoverage + $earnedPremium;
//Get accrued premium - prima acumulada (solo sirve para ver que valor restar a la prima anual del riesgo actual)
if ($calculateType == "FIRST") {
    $accruedPremium = $premiumOriginalOld;
} else {
    if ($typeEndorsementOld != "Coverage Extension") {
        $accruedPremium = $premiumOriginalOld;
    } else {
        $accruedPremium = $premiumNewOld;
    }
}
//$accruedPremium = $riskAnnualPremium + $cumulutivePremium;
//Get premium endorsement - Prima de endoso
$premiumEndorsement = $riskAnnualPremium - $accruedPremium;

//------Calculated Difference Date Original
$periodFromOriginal = new DateTime($data['YQP_PERIOD_FROM']);
$periodToOriginal = new DateTime($data['YQP_PERIOD_TO']);
$differenceCoverage = $periodFromOriginal->diff($validityEndorsement);
$daysDifferenceCoverage = $differenceCoverage->days;

//Get Premium Endorsement
$dataReturn['END_VALUE_PREMIUM_ENDORSEMENT'] = round($premiumEndorsement, 2);
//Other Variables
$dataReturn['END_STATUS_PREMIUM'] = $statusPremium;
//$dataReturn['END_COLLECTION_OLD_PREMIUM'] = round($premiumNewOld, 2);
$dataReturn['END_COLLECTION_OLD_PREMIUM'] = round($premiumOriginalOld, 2);
$dataReturn['END_COLLECTION_TOTAL_DAYS'] = $totalDays;
$dataReturn['END_COLLECTION_NEW_RECALCULATED_PREMIUM'] = round($totalPremiumNew, 2);
$dataReturn['END_COLLECTION_DAYS_COVERAGE'] = $daysDifferenceCoverage;
$dataReturn['END_COLLECTION_PREMIUM_COVERAGE'] = round($premiumCoverage, 2);
$dataReturn['END_COLLECTION_DAYS_COVERAGE_ACCRUE'] = $daysCoverageAccrue;
$dataReturn['END_COLLECTION_EARNED_PREMIUM'] = round($earnedPremium, 2);
$dataReturn['END_COLLECTION_ACCRUED_PREMIUM'] = round($riskAnnualPremium, 2);

//-------------Otro
/*$dataReturn['diasCoberturaOriginal'] = $originalCoverageDays;
$dataReturn['diasCoberturaSAOriginal'] = $differenceDaysValidity;
$dataReturn['diasCoberturaDevengar'] = $daysCoverageAccrueNew;
$dataReturn['primaDevengadaSAOriginal'] = $earnedPremium;
$dataReturn['primaDevengarNueva'] = $earnedPremiumNew;
$dataReturn['totalPrimaNueva'] = $totalNewPremium;
$dataReturn['primaEndoso'] = round($totalNewPremium - $totalPremiumOld, 2);*/

//Save in collecction
if ($statusPremium != "EQUAL") {
    $requestId = $data['_request']['id'];
    $dataSave = array();
    $dataSave['FORTE_EP_REQUEST'] = $data['_request']['id'];
    $dataSave['FORTE_EP_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    $dataSave['FORTE_EP_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
    $dataSave['FORTE_EP_OLD_REQUEST'] = $data['END_REQUEST_ENDORSEMENT_OLD'];
    $dataSave['FORTE_EP_PERIOD_FROM'] = $periodFrom;
    $dataSave['FORTE_EP_PERIOD_TO'] = $periodTo;
    $dataSave['FORTE_EP_TOTAL_DAYS'] = $totalDays;
    $dataSave['FORTE_EP_ORIGINAL_PREMIUM'] = $premiumOriginalOld;
    $dataSave['FORTE_EP_NEW_PREMIUM'] = $premiumNewOld;
    $dataSave['FORTE_EP_NEW_PERIOD_FROM'] = $validity;
    $dataSave['FORTE_EP_NEW_PERIOD_TO'] = $periodTo;
    $dataSave['FORTE_EP_VALIDITY_ENDORSEMENT'] = $validity;
    $dataSave['FORTE_EP_DATE_EXTENSION'] = '';
    $dataSave['FORTE_EP_DAYS_ORIGINAL_COVERAGE'] = $daysCoverage;
    $dataSave['FORTE_EP_PREMIUM_COVERAGE'] = $premiumCoverage;
    $dataSave['FORTE_EP_DAYS_COVERAGE_ACCRUE'] = $daysCoverageAccrue;
    $dataSave['FORTE_EP_EARNED_PREMIUM'] = $earnedPremium;
    $dataSave['FORTE_EP_ANNUAL_RISK_PREMIUM'] = $riskAnnualPremium;
    $dataSave['FORTE_EP_ACCUMULATED_RISK_PREMIUM'] = $accruedPremium;
    $dataSave['FORTE_EP_EARNED_PREMIUM_NEW'] = '';
    $dataSave['FORTE_EP_VALUE_PREMIUM_ENDORSEMENT'] = round($premiumEndorsement, 2);
    $dataSave['FORTE_EP_NEW_ANNUAL_PREMIUM'] = $totalPremiumNew;
    saveUpdateCollection(getenv('FORTE_ENDORSEMENT_PREMIUM_ID'), $requestId, json_encode($dataSave), 'FORTE_EP_REQUEST');
}
return $dataReturn;