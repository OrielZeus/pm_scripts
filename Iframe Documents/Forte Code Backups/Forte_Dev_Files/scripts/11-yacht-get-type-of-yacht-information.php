<?php 
/*  
 * Get type of yacht with OpenL connection
 * by Ana Castillo
 * modified Helen Callisaya
 */
//Set variable of return
$dataDropdowns = array();
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');

//Set data to send
$dataSend = array();
$dataSend['YQP_TYPE_YACHT'] = html_entity_decode($data["YQP_TYPE_YACHT"], ENT_QUOTES | ENT_XML1, 'UTF-8');

/*********************** Get Type of Yacht Information *********************************/
//Set variable with options
$aOptions = array();
//Curl init
$curl = curl_init();
//Curl to the End point
curl_setopt_array($curl, array(
    CURLOPT_URL => $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetYachtInformation",
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
));

//Set response
$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);
if($status != 200) {
    $dataDropdowns['PM_OPEN_TYPE_YACHT_INFORMATION']['YQP_TYPE_VESSEL'] = "";
    $dataDropdowns['PM_OPEN_TYPE_YACHT_INFORMATION']['YQP_FUEL'] = "";
    $dataDropdowns['PM_OPEN_TYPE_YACHT_INFORMATION']['YQP_PROPULSION'] = "";
    $dataDropdowns['PM_OPEN_TYPE_YACHT_INFORMATION']['ERROR'] = "cURL Error #:" . $err;
} else {
    $dataDropdowns['PM_OPEN_TYPE_YACHT_INFORMATION'] = json_decode($response);
}
curl_close($curl);

//Return all Values
return $dataDropdowns['PM_OPEN_TYPE_YACHT_INFORMATION'];