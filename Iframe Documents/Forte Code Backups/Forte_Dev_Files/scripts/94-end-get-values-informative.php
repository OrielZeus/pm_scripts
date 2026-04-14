<?php 
/*  
 * Get dropdowns options with OpenL connection and Quote Number
 * by Ana Castillo
 * modified by Helen Callisaya
 */

/*
* Get Open Functions
*
* @param (String) $url
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function callCurlOpenL($url)
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

/* Function that convert html > ul > li to a PHP array
*
* @param string $html
* @return array
*
* Original by https://gist.github.com/molotovbliss/18acc1522d3c23382757df2dbe6f0134
* Modified by Ronald Nina
*/
function ulToArray($ul)
{
    try {
        if (is_string($ul)) {
            // encode ampersand appropiately to avoid parsing warnings
            $ul = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $ul);
            if (@!$ul = simplexml_load_string($ul)) {
                throw new Exception("Syntax error in UL/LI structure");
                return FALSE;
            }
            return ulToArray($ul);
        } else if (is_object($ul)) {
            $output = array();
            foreach ($ul->li as $li) {
                $output[] = (isset($li->ul)) ? ulToArray($li->ul) : (string) $li;
            }
            return $output;
        } else {
            // In case Unknow type
            throw new Exception("Unknow type");
            return FALSE;
        }
    } catch (Exception $e) {
        $output = ['Exception: ' .  $e->getMessage()];
        return $output;
    }
}

//Set variable of return
$dataDropdowns = array();

//Set variable as Submit to do required all fields
$dataDropdowns['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Set variable Status as PENDING
$dataDropdowns['YQP_STATUS'] = "PENDING";

//Get URL Connection openL
$openLUrl = getenv('OPENL_CONNECTION');

//Set parameters to quote number functionality
$requestID = $data['_request']["id"];
$processID = $data['_request']["process_id"];

/*********************** Get values Brokers *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetBrokers";

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
    $dataDropdowns['PM_OPEN_BROKERS'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        //Initialize the increment
        $counter = 0;
        for ($r = 0; $r < count($response); $r+=2) {
            if ($response[$r] != "") {
                $aOptions[$counter] = array();
                $aOptions[$counter]['ID'] = $response[$r];
                $aOptions[$counter]['LABEL'] = $response[$r + 1];
                $counter++;
            }
        }
        $dataDropdowns['PM_OPEN_BROKERS'] = $aOptions;
    }
}

/*********************** Get values Cedents *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetCedents";

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
    $dataDropdowns['PM_OPEN_CEDENTS'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        //Initialize the increment
        $counter = 0;
        for ($r = 0; $r < count($response); $r+=2) {
            if ($response[$r] != "") {
                $aOptions[$counter] = array();
                $aOptions[$counter]['ID'] = $response[$r];
                $aOptions[$counter]['LABEL'] = $response[$r + 1];
                $counter++;
            }
        }
        $dataDropdowns['PM_OPEN_CEDENTS'] = $aOptions;
    }
}

/*********************** Get options deductible P&I *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetPIDeductibles";

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
    $dataDropdowns['PM_OPEN_PI_DEDUCTIBLES'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_PI_DEDUCTIBLES'] = $aOptions;
    }
}

//Convert additional to HTML for Slip and fix it if it is corrupted
if (isset($data["YQP_CLAIM_ADDITIONAL_INFORMATION"]) && $data["YQP_CLAIM_ADDITIONAL_INFORMATION"] != "") {
    $additional = $data["YQP_CLAIM_ADDITIONAL_INFORMATION"];
    //Validate if it is corrupted
    if (count(explode("<li>", $additional)) > 1) {
        //Remove tags other than <ul> and <li>
        $additional = strip_tags($additional, '<ul><li>');
        //Delete everything that is outside the ul 
        $additional = substr($additional, strpos($additional, "<ul>"), strpos($additional, '</ul>') + 5);
        $dataDropdowns['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $additional;
        //Convert to UL HTML to array for Slip
        //Decode twice for security of unknown characters
        $additional = html_entity_decode(html_entity_decode($additional));
        $dataDropdowns['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = ulToArray($additional);
    } else {
        //Fix values of additional
        $newAdditional = explode("\n", $additional);
        $additional = "<ul>";
        for ($s = 0; $s < count($newAdditional); $s++) {
            if ($newAdditional[$s] != "" && str_replace("&nbsp;", "", $newAdditional[$s]) != '') {
                $additional .= "\n<li>" . $newAdditional[$s] . "</li>";
            }
        }
        $additional .= "</ul>";
        $dataDropdowns['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $additional;
        //Clean line break 
        $additional = str_replace("\n", '', $additional);
        //Convert to UL HTML to array for Slip
        //Decode twice for security of unknown characters
        $additional = html_entity_decode(html_entity_decode($additional));
        $dataDropdowns['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = ulToArray($additional);
    }
} else {
    $dataDropdowns['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
    $dataDropdowns['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = "";
}

//Validate If process is yacht Endorsement
$dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "YES";
$dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "YES";
if ($data['_request']['process_id'] == getenv('FORTE_ID_ENDORSEMENT')) {
    switch ($data['END_TYPE_ENDORSEMENT']) {
        case "Change Premium":
            $dataDropdowns['YQP_DATA_EXISTS'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "NO";
            $dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "YES";
            break;
        case "Informative and Change Premium":
            $dataDropdowns['YQP_DATA_EXISTS'] = "NO";
            $dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "YES";
            break;
        case "Informative":
            $dataDropdowns['YQP_DATA_EXISTS'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "NO";
            break;            
    }
}

//If there is a bug we need to clean to error 
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => ''
];
$dataDropdowns['FORTE_ERRORS'] = $requestError;

//Return all Values
return $dataDropdowns;