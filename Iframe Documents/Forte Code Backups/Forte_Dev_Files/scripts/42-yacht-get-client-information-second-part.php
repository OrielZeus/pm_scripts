<?php 
/**********************************************  
 * Get dropdowns options with OpenL connection
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 *********************************************/
//Set variable of return
$dataClient = array();

//Set variable as Submit to do required all fields
$dataClient['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Set variable Status as Estimation
$dataRequest['YQP_STATUS'] = "ESTIMATION";

//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');

/*
* Function that calls the OpenL
*
* @param (String) $url
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
        CURLOPT_POSTFIELDS => $dataSend,
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json"
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
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
/*
* Calls the processmaker api
*
* @param (string) $url 
* @return (Array) $responseCurl 
*
* by Helen Callisaya
*/
function callGetCurl($url)
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
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
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
/*
* Get Value Variable or Text
*
* @param (array) $dataRequest
* @param (string) $conditionVarText
* @param (string) $conditionVariable
* @param (string) $conditionText
* @return (string) $valueVariableText
*
* by Helen Callisaya
*/
function getValueVariableText($dataRequest, $conditionVarText, $conditionVariable, $conditionText)
{
    //Validates if the condition applies to variable or text
    if ($conditionVarText == "VARIABLE") {
        //Get the value of the variable to evaluate registered in the request example: $date['YQP_SUM_INSURED_VESSEL']
        if ($dataRequest[$conditionVariable]) {
            $valueVariableText = $dataRequest[$conditionVariable];
        } else {
            //When the variable does not exist we set it to 0
            $valueVariableText = 0;
        }
    } else {
        $valueVariableText = $conditionText;
    }
    return $valueVariableText;
}
/*
* Get Name Range
*
* @param (string) $listRange
* @param (array) $dataRequest
* @return (string) $clasification
*
* by Helen Callisaya
*/
function rangeValues($typeRange, $dataRequest)
{
    $url = getenv('API_HOST') . '/collections/' . getenv('FORTE_RANGE_COLLECTION') . '/records?pmql=(data.FORTE_RANGE_TYPE="' . $typeRange . '")';
    $getConditionRange = callGetCurl($url);
    $clasification = "";
    foreach ($getConditionRange['data'] as $collection) {
        $conditionList = $collection['data']['FORTE_RANGE_CONDITION'];
        $conditions = "";
        for ($r = 0; $r < count($conditionList); $r++) {
            $operator = "";
            $valueVariableText1 = getValueVariableText($dataRequest, $conditionList[$r]['FORTE_RANGE_VARTEXT1'], $conditionList[$r]['FORTE_RANGE_VARIABLE1'], $conditionList[$r]['FORTE_RANGE_TEXT1']);
            $valueVariableText2 = getValueVariableText($dataRequest, $conditionList[$r]['FORTE_RANGE_VARTEXT2'], $conditionList[$r]['FORTE_RANGE_VARIABLE2'], $conditionList[$r]['FORTE_RANGE_TEXT2']);
            //Get condition operator
            if (($r + 1) < count($conditionList)) {
                $operator = $conditionList[$r]['FORTE_RANGE_OPERATOR'] . ' ';
            }
            $conditions .= "'" . $valueVariableText1 . "' " . $conditionList[$r]['FORTE_RANGE_SIGN'] . " '" . $valueVariableText2 . "'" . $operator;
        }
        $evaluate = "\$resultCondition = $conditions;";
        $evaluate = @eval($evaluate);
        //Meets all conditions
        if ($resultCondition == true) {
            $clasification = $collection['data']['FORTE_RANGE_NAME'];
            break;
        }
    }
    return $clasification;
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
/*********************** Get values War Coverage *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetWarCoverages";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, '');
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_WAR_COVERAGE'] = "cURL Error #:" . $err;
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
        $dataClient['PM_OPEN_WAR_COVERAGE'] = $aOptions;
    }
}
/*********************** Get values Special area for Special deductible application *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetSpecialAreas";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, '');
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_SPECIAL_AREA'] = "cURL Error #:" . $err;
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
        $dataClient['PM_OPEN_SPECIAL_AREA'] = $aOptions;
    }
}

/*********************** Get values Types of Special Deductibles *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetTypeSpecialDeductible";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, '');
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_TYPE_SPECIAL_DEDUCTIBLE'] = "cURL Error #:" . $err;
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
        $dataClient['PM_OPEN_TYPE_SPECIAL_DEDUCTIBLE'] = $aOptions;
    }
}

/*********************** Get values Show of Special Deductibles *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetShowSpecialDeductible";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, '');
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_SHOW_SPECIAL_DEDUCTIBLE'] = "cURL Error #:" . $err;
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
        $dataClient['PM_OPEN_SHOW_SPECIAL_DEDUCTIBLE'] = $aOptions;
    }
}

/*********************** Get values Personal Effects Max each person *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetPersonalEffectsMaxEachPerson";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, '');
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_PERSONAL_MAX_PERSON'] = "cURL Error #:" . $err;
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
        $dataClient['PM_OPEN_PERSONAL_MAX_PERSON'] = $aOptions;
    }
}

/*********************** Get values Medical Payments Max each person *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetMedicalMaxEachPerson";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, '');
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_MEDICAL_MAX_PERSON'] = "cURL Error #:" . $err;
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
        $dataClient['PM_OPEN_MEDICAL_MAX_PERSON'] = $aOptions;
    }
}
/*********************** Get values Machineries *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetOptionsTextMachinery";
$dataSend['Country'] = $data["YQP_COUNTRY_BUSINESS"];

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, json_encode($dataSend));
//Set response
$response = $aResponse["DATA"];

//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataClient['PM_OPEN_OPTION_MACHINERY'] = "cURL Error #:" . $err;
    //Valid if it has values
    $dataClient['PM_OPEN_OPTION_MACHINERY_VALIDATE'] = "NO";
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        //Initialize the increment
        $counter = 0;
        for ($r = 0; $r < count($response); $r++) {
            if ($response[$r] != "") {
                $aOptions[$counter] = array();
                $aOptions[$counter]['ID'] = $response[$r];
                $aOptions[$counter]['LABEL'] = $response[$r];
                $counter++;
            }
        }
        $dataClient['PM_OPEN_OPTION_MACHINERY'] = $aOptions;
        //Valid if it has values
        $dataClient['PM_OPEN_OPTION_MACHINERY_VALIDATE'] = "YES";
    } else {
        //Valid if it does not have values
        $dataClient['PM_OPEN_OPTION_MACHINERY_VALIDATE'] = "NO";
    }
}
//Set needed validations to show or hide fields
//If product has on the option HULL
$productHull = "NO";
if (count(explode("HULL", $data["YQP_PRODUCT"])) > 1) {
    $productHull = "YES";
}
$dataClient["YQP_PRODUCT_HULL_VALIDATE"] = $productHull;

//Validate if the sum insured field is hidden
if ($data["YQP_PRODUCT_HULL_VALIDATE"] == "NO") {
    $dataClient["YQP_SUM_INSURED_VESSEL"] = 0;
}

//If product is HULL or HULL_PI
$productHullPI = "NO";
if ($data["YQP_PRODUCT"] == "HULL_PI" || 
    $data["YQP_PRODUCT"] == "HULL") {
    $productHullPI = "YES";
}
$dataClient["YQP_PERSONAL_EFFECTS_VALIDATION"] = $productHullPI;
//If product has on the option PI
$productPI = "NO";
if (count(explode("PI", $data["YQP_PRODUCT"])) > 1) {
    $productPI = "YES";
}
$dataClient["YQP_PI_VALIDATION"] = $productPI;
//If product is HULL or HULL_PI or HULL_PI_NO_LOSSES
$productHullPINoLosses = "NO";
if ($data["YQP_PRODUCT"] == "HULL_PI" || 
    $data["YQP_PRODUCT"] == "HULL" || 
    $data["YQP_PRODUCT"] == "HULL_PI_NOLOSSES") {
    $productHullPINoLosses = "YES";
}
$dataClient["YQP_TENDERS_SHOW"] = $productHullPINoLosses;

//If product is P&I/RC
$productPIRC = "NO";
if ($data["YQP_PRODUCT"] == "PI_RC") {
    $productPIRC = "YES";
}
$dataClient["YQP_PIRC_VALIDATION"] = $productPIRC;

//If there is a bug we need to clean the error
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => ''
];
$dataClient['FORTE_ERRORS'] = $requestError;
//Set Type Vessel Report
$dataClient['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL'] . ' ' . $data['YQP_FUEL'] . ' ' . $data['YQP_PROPULSION'];
$dataClient['YQP_TYPE_CODE_REPORT'] = 'Y' . $data['YQP_TYPE_YACHT'];
//Set Mooring Location
if ($data['YQP_LOCATION_MOORING_PORT'] == "Other") {
    $dataClient['YQP_MOORING_PORT_REPORT'] = $data['YQP_SPECIFY_PORT'];
} else {
    $dataClient['YQP_MOORING_PORT_REPORT'] = $data['YQP_MOORING_PORT'];
}
/******************************* Set YQP_USE_SLIP *******************************/
$dataSend = array();
$dataSend['Use'] = html_entity_decode($data["YQP_USE"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['Language'] = html_entity_decode($data["YQP_LANGUAGE"], ENT_QUOTES | ENT_XML1, 'UTF-8');
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/ConvertUseLanguage";
//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, json_encode($dataSend));
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];  
if ($err) {
    //If there is an error, set the error
    $dataClient['YQP_USE_SLIP'] = "cURL Error #:" . $err;
} else {
    $dataClient['YQP_USE_SLIP'] = $response;
}

//Calculate Hull Year
$dataClient['YQP_RANGE_YEAR'] = rangeValues("RANGE_YEAR", $data);

//Return all Values
return $dataClient;