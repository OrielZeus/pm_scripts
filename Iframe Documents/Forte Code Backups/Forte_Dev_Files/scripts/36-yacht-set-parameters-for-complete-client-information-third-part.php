<?php 
/************************************************************* 
 * Set Parameters for complete client information third part
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 ************************************************************/
//require_once("/FORTE_PHP_Library.php");
/**
 * Function that convert html > ul > li to a PHP array
 * @param string $ul
 * @return array $output
 *
 * by Ronald Nina
 */
function ulToArray($ul) {
    $output = [];

    try {
        if (is_string($ul)) {
            $dom = new DOMDocument();
            // Suprime warnings por HTML mal formado
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $ul);
            libxml_clear_errors();

            $ulElements = $dom->getElementsByTagName('ul');
            if ($ulElements->length === 0) {
                throw new Exception("No UL found");
            }

            return domUlToArray($ulElements->item(0));
        }
    } catch (Exception $e) {
        return ['Exception: ' . $e->getMessage()];
    }

    return $output;
}

function domUlToArray(DOMElement $ul) {
    $result = [];

    foreach ($ul->childNodes as $li) {
        if ($li->nodeName === 'li') {
            $text = '';
            $children = [];

            foreach ($li->childNodes as $child) {
                if ($child->nodeName === 'ul') {
                    $children = domUlToArray($child);
                } elseif ($child->nodeType === XML_TEXT_NODE) {
                    $text .= trim($child->textContent);
                }
            }

            $result[] = !empty($children) ? [$text, $children] : $text;
        }
    }

    return $result;
}

/**
 * Function to get the type of document
 * @param (string) $html
 * @return (string) $response
 * Created By Elmer Orihuela
 */
function fixHtmlListStructure($htmlInput)
{
    // 1. Decodifica las entidades HTML (ej. &aacute;, &ntilde;, etc.)
    $html = html_entity_decode($htmlInput, ENT_QUOTES, 'UTF-8');

    // 2. Elimina caracteres problemáticos como &nbsp;
    $html = str_replace('&nbsp;', '', $html);

    // 3. Cuenta las etiquetas <ul> y <li> abiertas y cerradas
    $openedUl = substr_count($html, '<ul>');
    $closedUl  = substr_count($html, '</ul>');
    $openedLi = substr_count($html, '<li>');
    $closedLi  = substr_count($html, '</li>');

    // 4. Añade etiquetas faltantes si es necesario
    if ($openedUl > $closedUl) {
        $html .= str_repeat('</ul>', $openedUl - $closedUl);
    }
    if ($openedLi > $closedLi) {
        $html .= str_repeat('</li>', $openedLi - $closedLi);
    }

    // 5. Corrige problemas de anidamiento de listas
    $html = preg_replace('/(<ul>)\s*<\/li>/', '$1', $html);
    $html = preg_replace('/<\/ul>\s*(?!<\/li>)/', '</ul></li>', $html);

    return $html;
}

/**
 * Clean textarea rich text value
 *
 * @param string $valueToClean
 * @param string $parseValueForSlip
 * @return array $cleanedValues
 *
 * by Cinthia Romero
 */
function cleanTextAreaRichTextValue($valueToClean, $parseValueForSlip)
{
    // Corrige la estructura de la lista HTML.
    $valueToClean = fixHtmlListStructure($valueToClean);
    $cleanedValues = [
        "ORIGINAL_VALUE_CLEANED" => "",
        "SLIP_VALUE_PARSED" => ""
    ];

    // Valida si existe una lista (<li>) en el contenido.
    if (strpos($valueToClean, "<li>") !== false) {
        // Conserva solo las etiquetas <ul> y <li>.
        $valueToClean = strip_tags($valueToClean, '<ul><li>');
        // Extrae el contenido entre el primer <ul> y el último </ul>.
        $start = strpos($valueToClean, "<ul>");
        $end = strrpos($valueToClean, "</ul>");
        if ($start !== false && $end !== false) {
            $valueToClean = substr($valueToClean, $start, $end - $start + 5);
        }
        $cleanedValues["ORIGINAL_VALUE_CLEANED"] = $valueToClean;
        if ($parseValueForSlip == "YES") {
            // Decodifica dos veces por seguridad en caracteres especiales.
            $decodedValue = html_entity_decode(html_entity_decode($valueToClean));
            $cleanedValues["SLIP_VALUE_PARSED"] = ulToArray($decodedValue);
        }
    } else {
        // Si no hay lista, transforma el texto plano en una lista HTML.
        $lines = explode("\n", $valueToClean);
        $html = "<ul>";
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $html .= "\n<li>" . $line . "</li>";
            }
        }
        $html .= "\n</ul>";
        $cleanedValues["ORIGINAL_VALUE_CLEANED"] = $html;
        if ($parseValueForSlip == "YES") {
            $decodedValue = html_entity_decode(html_entity_decode($html));
            $cleanedValues["SLIP_VALUE_PARSED"] = ulToArray($decodedValue);
        }
    }
    return $cleanedValues;
}
//Initialice the return array
$dataClientThird = array();

//Clean message error
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
$dataClientThird['FORTE_ERRORS'] = $requestError;

//We set the status value to Pending for the requests that return
$dataClientThird['YQP_STATUS'] = "PENDING";

//Set variable as Submit to do required all fields
$dataClientThird['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Convert Subjectives to HTML for Slip and fix it if it is corrupted
$cleanedSubjectives = cleanTextAreaRichTextValue($data["YQP_SUBJECTIVES_GUARANTEE"], "NO");
$dataClientThird['YQP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["ORIGINAL_VALUE_CLEANED"];
//Convert Additional Information to HTML for Slip and fix it if it is corrupted
if (empty($data["YQP_CLAIM_ADDITIONAL_INFORMATION"])) {
    $dataClientThird['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
} else {
    $cleanedAdditionalInfo = cleanTextAreaRichTextValue($data["YQP_CLAIM_ADDITIONAL_INFORMATION"], "NO");
    $dataClientThird['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["ORIGINAL_VALUE_CLEANED"];
}

//Get URL Connection
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

//Set Month of the Period from in English
$months = array();
$months[1] = "January";
$months[2] = "February";
$months[3] = "March";
$months[4] = "April";
$months[5] = "May";
$months[6] = "June";
$months[7] = "July";
$months[8] = "August";
$months[9] = "September";
$months[10] = "October";
$months[11] = "November";
$months[12] = "December";
$periodFrom = $data["YQP_PERIOD_FROM"];
if ($periodFrom != "" && $periodFrom != null) {
    $monthFrom = explode("-", $periodFrom);
    $monthFrom = $monthFrom[1];
    $monthFrom = $monthFrom * 1;
    $monthFrom = $months[$monthFrom];
    $dataClientThird['YQP_MONTH'] = $monthFrom;
}

/*********************** Get values Subjective *********************************/
if (!isset($data["YQP_SUBJECTIVES_GUARANTEE"]) || $data["YQP_SUBJECTIVES_GUARANTEE"] == "") {
    //Set necessary variables
    $dataSend = array();
    $dataSend['Language'] = $data["YQP_LANGUAGE"];

    //Set data Send OpenL
    $dataClientThird["DATA_SUBJECTIVE_OPENL_SEND"] = $dataSend;

    //Set OpenL Url
    $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetSubjectives";

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
        $dataClientThird['PM_OPEN_SUBJECTIVES'] = "cURL Error #:" . $err;
        //Set error in screen
        $requestError = array();
        $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
        $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
        $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
        $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
        $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
        $dataClientThird['FORTE_ERRORS'] = $requestError;
    } else {
        //Get values of the response
        if ($response != "") {
            //Set subjectives Html
            $subjectivesHtml = "<ul>";
            //Parse values separated with |
            $response = explode("|", $response);
            for ($r = 0; $r < count($response); $r++) {
                if ($response[$r] != "") {
                    $subjectivesHtml .= "\n<li>" . $response[$r] . "</li>";
                }
            }
            $subjectivesHtml .= "</ul>";
            $dataClientThird['YQP_SUBJECTIVES_GUARANTEE'] = $subjectivesHtml;
        }
    }
}

//Set Premium share
//Set parameters to calc
$totalPremium = $data["YQP_TOTAL_PREMIUM_FINAL"];
$forteOrder = $data["YQP_FORTE_ORDER"];
//Get Percentage
$forteOrder = $forteOrder / 100;
//Calc Total Premium Share
$totalPremiumShare = $totalPremium * $forteOrder;
$dataClientThird["YQP_PREMIUM_SHARE"] = $totalPremiumShare;

/******************************* Set YQP_REINSURER_SIGNATURES to slip *******************************/
$reinsurer = array();
$reinsurer = $data["YQP_REINSURER_INFORMATION"];
//Set YQP_REINSURER_INFORMATION in variable return
$dataClientThird["YQP_REINSURER_INFORMATION"] = $data["YQP_REINSURER_INFORMATION"];
$reinsurerSlip = "<table style='width:100%;font-family: Corbel;font-size: 11pt;'>";
$noCode = "";
$noCodeValidate = "YES";
for ($r = 0; $r < count($reinsurer); $r++) {
    $dataSend = array();
    //Set percentage and name of reinsurer
    $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;'>" . $reinsurer[$r]["YQP_SHARE_PERCENTAGE"] . "% </td>";
    if ($data["YQP_COUNTRY_BUSINESS"] == "Mexico") {
        $dataSend['Reinsurer'] = $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"];
        //Set OpenL Url
        $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetRegisterMexico";
        //Call Curl OpenL
        $aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
        //Set response
        $response = $aResponse["DATA"];
        //Set error
        $err = $aResponse["ERROR"];    
        //Check if there is a code
        if ($response) {
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
            $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;'></td>";
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $response . "</td></tr>";
        } else  {
            $noCode .= "The " . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . " has no Register Code in Mexico.<br/>"; 
            $noCodeValidate = "NO";
        }
    } else {
        $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
    }    
    //Add a space
    $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;'></td>";
    $reinsurerSlip .= "<td style='width:65%;text-align:left;'></td></tr>";
    //Calculated Forte Order * Share
    $dataClientThird["YQP_REINSURER_INFORMATION"][$r]['YQP_FORTE_ORDER_SHARE'] = $reinsurer[$r]["YQP_FORTE_ORDER_GRID"] * ($reinsurer[$r]["YQP_SHARE_PERCENTAGE"] / 100);
}
$reinsurerSlip .= "</table>";
$dataClientThird["YQP_REINSURER_SIGNATURES"] = htmlentities($reinsurerSlip);
$dataClientThird["YQP_NO_CODE_MESSAGE"] = $noCode;
//Validate Code Mexico
if ($noCode != "") {
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "Country Reinsurer Code";
    $requestError['FORTE_ERROR_BODY'] = $noCode . " RETURN TO THE PREVIOUS PAGE.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "Country Reinsurer Code";
    $dataClientThird['FORTE_ERRORS'] = $requestError;
}
/*********************** Get values Base Text *********************************/
//Set necessary variables
$dataSend = array();
$dataSend['Country'] = $data["YQP_COUNTRY_BUSINESS"];
$dataSend['Reinsurer'] = $data["YQP_REASSURED_CEDENT"]["LABEL"];
$dataSend['Language'] = $data["YQP_LANGUAGE"];

//Set data Send OpenL
$dataClientThird["DATA_BASE_TEXT_OPENL_SEND"] = $dataSend;

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetBaseTextReinsurer";

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
    $dataClientThird['PM_OPEN_OPTIONS_BASE_TEXT'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataClientThird['FORTE_ERRORS'] = $requestError;    
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
        $dataClientThird['PM_OPEN_OPTIONS_BASE_TEXT'] = $aOptions;
    }
}
//*********************** Get values Source *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetSources";
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
    $dataClientThird['PM_OPEN_OPTIONS_SOURCE'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataClientThird['FORTE_ERRORS'] = $requestError;    
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
        $dataClientThird['PM_OPEN_OPTIONS_SOURCE'] = $aOptions;
    }
}

//Format Submission date
if ($data['YQP_SUBMISSION_DATE']) {
    $dataClientThird['YQP_SUBMISSION_DATE_REPORT'] = date('m/d/Y', strtotime($data['YQP_SUBMISSION_DATE']));
}
if ($data['YQP_SUBMISSION_MONTH']) {
    $dataClientThird['YQP_SUBMISSION_MONTH_REPORT'] = date('F', mktime(0, 0, 0, $data['YQP_SUBMISSION_MONTH'], 28));
}
if ($data['YQP_GROSS_BROKER_CHANGE'] == true) {
    $dataClientThird['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = number_format($data['YQP_BROKER_TOTAL_PREMIUM'], 2, ".", "");
} else {
    $dataClientThird['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = number_format($data['YQP_TOTAL_PREMIUM'], 2, ".", "");
}

//Calculate Range S.I. Hull
$dataClientThird['YQP_RANGE_SI_HULL'] = rangeValues("RANGE_HULL", $data);
//Calculate Hull Year
$dataClientThird['YQP_RANGE_YEAR'] = rangeValues("RANGE_YEAR", $data);

return $dataClientThird;