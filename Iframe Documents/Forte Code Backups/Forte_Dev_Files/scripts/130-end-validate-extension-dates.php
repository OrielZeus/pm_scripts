<?php 
/*  
 * Validate dates for Extension
 * by Ana Castillo
 */
//Set variable of return
$dataReturn = array();

//Get Dates
$periodFrom = strtotime($data['YQP_PERIOD_FROM']);
$periodTo = strtotime($data['YQP_PERIOD_TO']);
//Start count
$months = 0;
while (strtotime('+1 MONTH', $periodFrom) <= $periodTo) {
    $months++;
    $periodFrom = strtotime('+1 MONTH', $periodFrom);
}
//Validate Extension dates no more than 18 months
$messageError = '';
if ($months >= 18) {
    $messageError = "The months are exceed. You cannot create another Endorsement for Extension.";
}

//Validate current date no more than 15 days from Period To
$currentDate = new DateTime('00:00');
$periodTo  = new DateTime($data['YQP_PERIOD_TO']);
$periodTo = $periodTo->add(new DateInterval('P15D'));
//Validate if period To is more than current date with 15 days more
if ($currentDate > $periodTo) {
    $dateDifference = $periodTo->diff($currentDate);
    $daysDifference = $dateDifference->days;
    $dataReturn['END_MORE_THAN_15_DAYS'] = $daysDifference;
    if ($daysDifference > 15) {
        $messageError = "Period To is more than 15 days. You cannot run a Endorsement of Extension.";
    }
}

//Set Extension months
$dataReturn['END_MONTHS_DIFFERENCE'] = $months . ' months, ' . ($periodTo - $periodFrom) / (60 * 60 * 24) . ' days';

//Set messaage error if it is needed
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
if ($messageError != '') {
    $requestError['FORTE_ERROR_LOG'] = "Extension Error";
    $requestError['FORTE_ERROR_BODY'] = $messageError;
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "Extension Error";
}
$dataReturn['END_EXTENSION_ERROR'] = $messageError;
$dataReturn['FORTE_ERRORS'] = $requestError;

//Set flag to show new period date on approver
$dataReturn['END_FLAG_SHOW_NEW_DATE'] = '';
if ($messageError == '') {
    $dataReturn['END_FLAG_SHOW_NEW_DATE'] = 'YES';
}

return $dataReturn;