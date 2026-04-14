<?php 
/*   
 * Capture and save errors
 * by Helen Callisaya
 */
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
//Catch errors
$position = sizeof($data['_request']['errors']) - 1;
$error = $data['_request']['errors'][$position];
$requestError['FORTE_ERROR_LOG'] = $error['message'];
$requestError['FORTE_ERROR_BODY'] = $error['body'];
$requestError['FORTE_ERROR_DATE'] = $error['created_at'];
$requestError['FORTE_ERROR_ELEMENT_ID'] = $error['element_id'];
$requestError['FORTE_ERROR_ELEMENT_NAME'] = $error['element_name'];

//Get Id Collection
$collectionID = getenv('FORTE_ERROR_COLLECTION');
$token = getenv('API_TOKEN');

//Set values Array data_string to CURL POST
$data['FORTE_ERROR'] = array(
    "data" => $requestError
);
//Encode Array 
$dataString = json_encode($data['FORTE_ERROR']);
//curl init
$curl = curl_init();
//Curl to the End point
curl_setopt_array($curl, array(
    CURLOPT_URL => getenv('API_HOST') . '/collections/' . $collectionID . '/records',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $dataString,
    CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $token,
        "Content-Type: application/json",
        "cache-control: no-cache"
    ),
));

$response = curl_exec($curl);
curl_close($curl);

$requestData['FORTE_ERRORS'] = $requestError;

//return error 
return $requestData;