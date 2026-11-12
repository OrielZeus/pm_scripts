<?php
/*******************************  
 * Clean Hidden Variables
 *
 * by Helen Callisaya
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
//Hidden variable when YQP_PRODUCT variable is null or PI_RC
if ($data['YQP_PRODUCT'] == null || $data['YQP_PRODUCT'] == 'PI_RC') {
    $dataRequest['YQP_SUM_INSURED_VESSEL'] = 0;
}
//Search PI in YQP_PRODUCT
$searchPI = strpos($data['YQP_PRODUCT'], "PI");
if ($searchPI === false) {
    $dataRequest['YQP_LIMIT_PI'] = 0;
    $data['YQP_LIMIT_PI'] = 0;
    $dataRequest['YQP_LIMIT_PI_DEDUCTIBLE'] = 0;
    $data['YQP_LIMIT_PI_DEDUCTIBLE'] = 0;
}
//Hidden variable when YQP_PRODUCT_HULL_VALIDATE variable is NO
if ($data['YQP_PRODUCT_HULL_VALIDATE'] != 'YES') {
    $dataRequest['YQP_WAR'] = "NO";
    $dataRequest['YQP_WAR_DEDUCTIBLE'] = 'NO';
    $dataRequest['YQP_WAR_TYPE_COVERAGE'] = '';
    $dataRequest['YQP_SPECIAL_AREA'] = 'NO';  
    $dataRequest['YQP_SPECIAL_AREA_ZONE'] = '';
    $dataRequest['YQP_TYPE_SPECIAL_DEDUCTIBLE'] = '';
    $dataRequest['YQP_SHOW_SPECIAL_DEDUCTIBLE'] = '';
    $dataRequest['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'] = '';
    $dataRequest['YQP_SHOW_DEDUCTIBLE'] = '';
}
//Validate YQP_PERSONAL_EFFECTS_VALIDATION
if ($data['YQP_PERSONAL_EFFECTS_VALIDATION'] != 'YES') {
    $dataRequest['YQP_PERSONAL_EFFECTS'] = 'NO';
    $dataRequest['YQP_PERSONAL_EFFECTS_LIMIT'] = '';
    $dataRequest['YQP_PERSONAL_EFFECTS_MAX'] = '';      
}

//Hidden variable when YQP_TENDERS_SHOW variable is NO
if ($data['YQP_TENDERS_SHOW'] != 'YES') {
    $dataRequest['YQP_TENDERS'] = 'NO';
}

//Validate YQP_PI_VALIDATION
if ($data['YQP_PI_VALIDATION'] != 'YES') {
    $dataRequest['YQP_MEDICAL_PAYMENTS'] = 'NO';
    $dataRequest['YQP_MEDICAL_PAYMENTS_LIMIT'] = '';
    $dataRequest['YQP_MEDICAL_PAYMENTS_MAX'] = '';
}

//Hidden variable when YQP_CURRENCY variable is equal to "USD"
if ($data['YQP_CURRENCY'] == 'USD') {
    $dataRequest['YQP_EXCHANGE_RATE'] = '1';
}

//Hidden variable when YQP_TYPE_VESSEL variable is different from YES
if ($data['YQP_TYPE_VESSEL'] == null || $data['YQP_TYPE_VESSEL'] == '') {
    $dataRequest['YQP_FUEL'] = '';
}

//Hidden variable when YQP_FUEL variable is different from YES
if ($data['YQP_FUEL'] == null || $data['YQP_FUEL'] == '') {
    $dataRequest['YQP_PROPULSION'] = '';
}

//Hidden variable when YQP_AGE variable is less than or equal to 15 
if ($data['YQP_AGE'] <= 15) {
    $dataRequest['YQP_MACHINERY_MAX_VALUE'] = '';
}

//Hidden variable when YQP_LOCATION_MOORING_PORT variable is equal to "Other"
if ($data['YQP_LOCATION_MOORING_PORT'] == 'Other') {
    $dataRequest['YQP_MOORING_PORT'] = '';
}

//Hidden variable when YQP_LOCATION_MOORING_PORT variable is different from Other
if ($data['YQP_LOCATION_MOORING_PORT'] != 'Other') {
    $dataRequest['YQP_SPECIFY_PORT'] = '';
}

//Hidden variable when YQP_LOSS_PAYEE variable is different from YES
if ($data['YQP_LOSS_PAYEE'] != "YES") {
    $dataRequest['YQP_LOSS_PAYEE_NAME'] = '';
}

//Hidden variable when YQP_CLAIMS variable is different from YES
if ($data['YQP_CLAIMS'] != "YES") {
    $dataRequest['YQP_CLAIMS_TEXT'] = '';
}

//Hidden variable when YQP_WAR variable is different from YES
if ($data['YQP_WAR'] != "YES") {
    $dataRequest['YQP_WAR_DEDUCTIBLE'] = 'NO';
    $dataRequest['YQP_WAR_TYPE_COVERAGE'] = '';
}

//Hidden variable when YQP_PERSONAL_EFFECTS_VALIDATION variable is different from YES
if ($data['YQP_PERSONAL_EFFECTS'] != "YES") {
    $dataRequest['YQP_PERSONAL_EFFECTS_LIMIT'] = '';
    $dataRequest['YQP_PERSONAL_EFFECTS_MAX'] = '';
}

//Hidden variable when YQP_MEDICAL_PAYMENTS variable is different from YES
if ($data['YQP_MEDICAL_PAYMENTS'] != "YES") {
    $dataRequest['YQP_MEDICAL_PAYMENTS_LIMIT'] = '';
    $dataRequest['YQP_MEDICAL_PAYMENTS_MAX'] =  array("value" => "","content" => "");

}

//Hidden variable when YQP_TENDERS variable is different from YES
if ($data['YQP_TENDERS'] != "YES") {
    $dataRequest['YQP_TENDERS_INFORMATION'] = '';
}

//Hidden variable when YQP_SPECIAL_AREA variable is different from YES
if ($data['YQP_SPECIAL_AREA'] != "YES") {
    $dataRequest['YQP_SPECIAL_AREA_ZONE'] = '';
    $dataRequest['YQP_TYPE_SPECIAL_DEDUCTIBLE'] = '';
    $dataRequest['YQP_SHOW_SPECIAL_DEDUCTIBLE'] = '';
    $dataRequest['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'] = '';
}

//Hidden variable when YQP_APPROVER_COMMENTS_VISIBLE variable is different from YES
if ($data['YQP_APPROVER_COMMENTS_VISIBLE'] != "YES") {
    $dataRequest['YQP_APPROVER_COMMENTS_CHANGE'] = '';
}

//Hidden variable when YQP_NAVIGATION_LIMITS variable is different from YES
if ($data['YQP_NAVIGATION_LIMITS'] != "Other") {
    $dataRequest['YQP_NAVIGATION_LIMITS_OTHER'] = '';
}

//Hidden variable when YQP_GROSS_BROKER_CHANGE variable is different from true
if ($data['YQP_GROSS_BROKER_CHANGE'] != true) {
    $dataRequest['YQP_BROKER_TOTAL_PREMIUM'] = '';
    $dataRequest['YQP_BROKER_PERCENTAGE'] = 0;
    $dataRequest['YQP_EXTRA_PAYMENT_REINSURER'] = '';
}

//Hidden variable when YQP_CONTAMINATION variable is different from YES
if ($data['YQP_CONTAMINATION'] != "YES") {
    $dataRequest['YQP_CONTAMINATION_LIMIT'] = '';
}

//Hidden variable when YQP_DAMAGE variable is different from YES
if ($data['YQP_DAMAGE'] != "YES") {
    $dataRequest['YQP_DAMAGE_LIMIT'] = '';
}

//Hidden variable when YQP_OWNERS_UNINSURED_VESSEL variable is different from YES
if ($data['YQP_OWNERS_UNINSURED_VESSEL'] != "YES") {
    $dataRequest['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'] = '';
}

//Hidden variable when YQP_TOWING_ASSISTANCE variable is different from YES
if ($data['YQP_TOWING_ASSISTANCE'] != "YES") {
    $dataRequest['YQP_TOWING_ASSISTANCE_LIMIT'] = '';
}

//Hidden variable when YQP_SOURCE variable is different from Other
if ($data['YQP_SOURCE'] != "Other") {
    $dataRequest['YQP_SOURCE_OTHER'] = '';
}

//Hidden variable when YQP_WATER_SKIING variable is different from YES
if ($data['YQP_WATER_SKIING'] != "YES") {
    $dataRequest['YQP_WATER_SKIING_LIMIT'] = '';
}

//Copy the value of the edit variable
$dataRequest['YQP_CLIENT_NAME_DISABLE'] = $data['YQP_CLIENT_NAME'];
$dataRequest['YQP_INTEREST_ASSURED_DISABLE'] = $data['YQP_INTEREST_ASSURED'];
//Copy the UMR and contract only if it exists
if (isset($data['YQP_PIVOT_TABLE_NUMBER'])) {
    $dataRequest['YQP_PIVOT_TABLE_NUMBER_DISABLE'] = $data['YQP_PIVOT_TABLE_NUMBER'];
    $dataRequest['YQP_UMR_CONTRACT_NUMBER_DISABLE'] = $data['YQP_UMR_CONTRACT_NUMBER'];
}

//If there is a bug we need to clean to error 
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => ''
];
$dataRequest['FORTE_ERRORS'] = $requestError;
$dataRequest['YQP_AGREEMENT_CREATED_ERROR'] = '';
$dataRequest['YQP_OBTAIN_WORKFLOWS_ERROR'] = '';
$dataRequest['YQP_AGREEMENT_RESPONSE_ERROR'] = '';
$dataRequest['YQP_AGREEMENT_RESPONSE_ACTION'] = '';

//Validate exists Period From
if($data['YQP_PERIOD_FROM']) {
    //Get Month and year
    $month = (int) date("m", strtotime($data['YQP_PERIOD_FROM']));
    $year = (int) date("Y", strtotime($data['YQP_PERIOD_FROM'])); 
    //Search Period
    $url = getenv('API_HOST') . '/collections/' . getenv('FORTE_PERIOD_COLLECTION') . '/records' . '?include=data&pmql=(data.FORTE_PERIOD_MONTH.value="' . $month . '")';
    $response = callGetCurl($url, "GET", "");
    //Set Trimester and Year
    $dataRequest['YQP_TRIMESTER'] = $response['data'][0]['data']['FORTE_PERIOD_NUMBER'];
    if ($response['data'][0]['data']['FORTE_REST_YEAR'] == true) {
        $dataRequest['YQP_YEAR_PERIOD'] = $year - 1;
    } else {
        $dataRequest['YQP_YEAR_PERIOD'] = $year;
    }
}
if ($data['END_ENDORSEMENT_EMAIL_DESCRIPTION']) {
    $dataRequest['END_ENDORSEMENT_EMAIL_DESCRIPTION'] = htmlentities($data['END_ENDORSEMENT_EMAIL_DESCRIPTION']);
}

//Set Month Capture
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $date = new DateTime($data['_request']['created_at']);
    $dataRequest['YQP_CATCH_MONTH_REPORT'] = strtoupper($date->format('M-y'));
}
//Validate the value empty YQP_REINSURER_INFORMATION
$reinsurersGrid = [];
if ($data['YQP_REINSURER_INFORMATION']) {
    $reinsurersGrid = $data['YQP_REINSURER_INFORMATION'];
    for ($r = 0; $r < count($reinsurersGrid); $r++) {
        if (empty($reinsurersGrid[$r]['YQP_TAX_ON_GROSS']) || $reinsurersGrid[$r]['YQP_TAX_ON_GROSS'] == null || $reinsurersGrid[$r]['YQP_TAX_ON_GROSS'] == "0") {
            $reinsurersGrid[$r]['YQP_TAX_ON_GROSS'] = 0;
        }
    }
}
$dataRequest['YQP_REINSURER_INFORMATION'] = $reinsurersGrid;

//Validate machinery
if ($data['PM_OPEN_OPTION_MACHINERY_VALIDATE'] != "YES") {
    $dataRequest['YQP_MACHINERY'] = '';
}
//Clear Reinsurer Comment
$dataRequest['YQP_APPROVE_REINSURER'] = '';
$dataRequest['YQP_COMMENTS_REINSURER'] = '';

//Clear Error Approve
$dataRequest['YQP_ERROR_APPROVE'] = '';

$dataRequest['YQP_CREATE_DATE'] = date('Y-m-d',strtotime($data['_request']['created_at']));

//Save to Gestion Solicitudes Collection
$searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
$searchRequest = callGetCurl($searchRequestUrl, "GET", "");
$dataSave = array();
$dataSave['FORTE_REQUEST'] = $data['_request']['id'];
$dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
$dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
$dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
//Validate Process
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    //Name Process
    $dataRequest['FORTE_TITLE_PROCESS'] = "Yacht Quotation Process";
    $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
} else {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    //Name Process
    $dataRequest['FORTE_TITLE_PROCESS'] = "Yacht Endorsement Process";
    $dataSave['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
}  
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
$dataSave['YQP_REINSURER_INFORMATION'] = $reinsurersGrid;
//

//Validate if the request exists
if (count($searchRequest["data"]) == 0) {
    $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records';
    $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
} else {
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
    $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
}

return $dataRequest;