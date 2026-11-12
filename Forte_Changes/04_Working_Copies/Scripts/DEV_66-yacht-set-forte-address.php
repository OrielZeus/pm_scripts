<?php 
/*  
 * Set Forte Address html
 * by Helen Callisaya
  */
/************** Set Forte Address **********************/
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');
//Curl init
$curl = curl_init();
//Curl to the End point
curl_setopt_array($curl, array(
    CURLOPT_URL => $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetSignForteAddress",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache",
        "content-type: application/json"
    ),
));
//Set response
$response = curl_exec($curl);
//Set error
$err = curl_error($curl);
curl_close($curl);

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClientInfo['YQP_FORTE_ADDRESS_HTML'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    if ($response != "") {
        $dataClientInfo['YQP_FORTE_ADDRESS_HTML'] = $response;
    }
}
//Set submit
$dataClientInfo['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Set value as QUOTED
$dataClientInfo['YQP_STATUS'] = "QUOTED";

return $dataClientInfo;