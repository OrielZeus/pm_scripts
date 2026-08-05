<?php
/*  
 * Clean Hidden Variables
 * by Helen Callisaya
 */
//**************************************************************************************
//Hidden variable when YQP_PRODUCT variable is null or PI_RC
if ($data['YQP_PRODUCT'] == null || $data['YQP_PRODUCT'] == 'PI_RC') {
    $dataRequest['YQP_SUM_INSURED_VESSEL'] = 0;
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

//If there is a bug we need to clean to error 
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => ''
];
$dataRequest['FORTE_ERRORS'] = $requestError;

//Set array Clauses Selected
$clausesSelected = array();
//vakudate if is null or empty set []
if ($data['YQP_PRELIMINARY_CLAUSES'] == null || $data['YQP_PRELIMINARY_CLAUSES'] == '') {
    $data['YQP_PRELIMINARY_CLAUSES'] = [];
}
if ($data['YQP_CLAUSES_INCLUDED'] == null || $data['YQP_CLAUSES_INCLUDED'] == '') {
    $data['YQP_CLAUSES_INCLUDED'] = [];
}
$clausesSelected = array_merge($data['YQP_PRELIMINARY_CLAUSES'], $data['YQP_CLAUSES_INCLUDED']);
$dataRequest['YQP_CLAUSES_SELECTED'] = $clausesSelected;

//Validate machinery
if ($data['PM_OPEN_OPTION_MACHINERY_VALIDATE'] != "YES") {
    $dataRequest['YQP_MACHINERY'] = '';
}
//Clear Reinsurer Comment
$dataRequest['YQP_APPROVE_REINSURER'] = '';
$dataRequest['YQP_COMMENTS_REINSURER'] = '';

return $dataRequest;