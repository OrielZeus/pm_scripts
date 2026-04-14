<?php 
/*  
 * Set Parameters for complete client information second part
 * by Ana Castillo
 * modified by Helen Callisaya
 */
//Initialice the return array
$dataClientSecond = array();

//Get URL Connection of OpenL
$openLUrl = getenv('OPENL_CONNECTION');

/*
* Function that calls the OpenL
*
* @param (String) $url
* @param (Object) $dataSend
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function callCurlOpenL($url, $dataSend)
{
    //Curl init
    $curl = curl_init();
    //Curl to the End point
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
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
    $responseCurl = curl_exec($curl);
    //Set error
    $errorCurl = curl_error($curl);
    curl_close($curl);

    //Set array to response
    $aDataResponse = array();
    $aDataResponse["ERROR"] = $errorCurl;
    $aDataResponse["DATA"] = $responseCurl;

    //Return Response
    return $aDataResponse;
}

//Validate if the User did Save set as Pending the status
if ($data["YQP_SUBMIT_SAVE"] == "SAVE") {
    $dataClientSecond['YQP_STATUS'] = "PENDING";
}

//Set variable as Submit to do required all fields
$dataClientSecond['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Set Total Premium Final to use on Reinsurers records list
//If broker payment is required
if ($data["YQP_GROSS_BROKER_CHANGE"]) {
    $totalPremiumFinal = $data["YQP_BROKER_TOTAL_PREMIUM"];
} else {
    $totalPremiumFinal = $data["YQP_TOTAL_PREMIUM"];
}
$dataClientSecond["YQP_TOTAL_PREMIUM_FINAL"] = $totalPremiumFinal;

//Validate if Fields has been changed
//Forte Order
$forteOrderOld = $data["YQP_FORTE_ORDER_OLD"];
if (!isset($forteOrderOld) || $forteOrderOld != "") {
    //If forte Order Old is different to the current value
    if ($forteOrderOld != $data["YQP_FORTE_ORDER"]) {
        //Save Forte Old to save the value
        $dataClientSecond["YQP_FORTE_ORDER_OLD"] = $data["YQP_FORTE_ORDER"];
        //Delete all rows of the reinsurer grid
        $dataClientSecond["YQP_REINSURER_INFORMATION"] = "";
    }
} else {
    //Save Forte Old to save the value
    $dataClientSecond["YQP_FORTE_ORDER_OLD"] = $data["YQP_FORTE_ORDER"];
}
//Total Premium Final change
$totalFinalOld = $data["YQP_TOTAL_PREMIUM_FINAL_OLD"];
if (isset($totalFinalOld) && $totalFinalOld != "") {
    //If Total Premium Final Old is different to the current value
    if (round($totalFinalOld, 2) != round($totalPremiumFinal, 2)) {       
        //Save Forte Old to save the value
        $dataClientSecond["YQP_TOTAL_PREMIUM_FINAL_OLD"] = $totalPremiumFinal;
        //Delete all rows of the reinsurer grid
        $dataClientSecond["YQP_REINSURER_INFORMATION"] = "";
    }
} else {
    //Save Forte Old to save the value
    $dataClientSecond["YQP_TOTAL_PREMIUM_FINAL_OLD"] = $totalPremiumFinal;  
}

$dataClientSecond['YQP_LENGTH_UNIT_REPORT'] = number_format($data['YQP_LENGTH_UNIT'], 2, ".", "");
//Validate Check Broker Payment
if ($data['YQP_GROSS_BROKER_CHANGE'] != true)
{
    $dataClientSecond['YQP_BROKER_PERCENTAGE_DEDUCTION'] = 0;
    $dataClientSecond['YQP_BROKER_TOTAL_PREMIUM'] = '';
    $dataClientSecond['YQP_BROKER_PERCENTAGE'] = 0;
    $dataClientSecond['YQP_EXTRA_PAYMENT_REINSURER'] = '';    
} else {
    $dataClientSecond['YQP_BROKER_PERCENTAGE_DEDUCTION'] = $data['YQP_BROKER_PERCENTAGE'];
}

/*********************** Get all Reinsurers that have Signatures configured *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetReinsurersConfiguredSignatures";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClientSecond['PM_OPEN_REINSURERS_CONFIGURED_SIGN'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        for ($r = 0; $r < count($response); $r++) {
            $aOptions[$response[$r]] = "YES";
        }
        $dataClientSecond['PM_OPEN_REINSURERS_CONFIGURED_SIGN'] = json_encode($aOptions);
    }
}

//Return data
return $dataClientSecond;