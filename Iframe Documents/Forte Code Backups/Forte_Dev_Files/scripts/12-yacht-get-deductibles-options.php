<?php 
/*  
 * Get Deductible options with OpenL connection
 * by Ana Castillo
 */
//Set variable of return
$dataDropdowns = array();
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');
//Set data to send
$dataSend = array();
$dataSend['Country'] = $data["YQP_COUNTRY_BUSINESS"];
$dataSend['TypeYacht'] = $data["YQP_TYPE_YACHT"];

/*********************** Get values Deductible *********************************/
//Curl init
$curl = curl_init();
//Curl to the End point
curl_setopt_array($curl, array(
    CURLOPT_URL => $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetDeductibles",
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
//Set error
$err = curl_error($curl);
curl_close($curl);

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataDropdowns['PM_OPEN_DEDUCTIBLES'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_DEDUCTIBLES'] = $aOptions;
    }
}

//Return all Values
return $dataDropdowns['PM_OPEN_DEDUCTIBLES'];