<?php 
/*************************************************  
 * Set Endorsement Information (Informative Type)
 *
 * by Helen Callisaya
 * modified by Cinthia Romero
 ************************************************/
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
/*
* Get subscription year
* by Helen Callisaya
*/
function getSubscriptionYear($dateStart, $dateEnd, $periodFromDate)
{
    $dateStart = strtotime($dateStart);
    $dateEnd = strtotime($dateEnd);
    $periodFromDate = strtotime($periodFromDate);
    //Check if the current date is on the date range
    if (($periodFromDate >= $dateStart) && ($periodFromDate <= $dateEnd)) {
        return date("Y", $dateStart);
    } else {
        return date("Y", $dateEnd);
    }
}
/* 
 * Replace Variables in String
 * by Helen Callisaya
 */
function replaceVariablesString($string, $dataRequest)
{
    //Separate in array by spaces
    $stringExplode = explode(' ', $string);
    $filterVar = 'YQP';
    //Filter only variables
    $filteredData = array_filter($stringExplode, function($var) use ($filterVar) { return stristr($var, $filterVar); });
    if ($filteredData) {
        foreach ($filteredData as $filter) {
            //Replace variables with data
            $string = str_replace($filter, $dataRequest[$filter], $string);
        }
    } 
    return $string;
}

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

/** Call Processmaker API
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
    $responseCurl = json_decode($responseCurl);
    curl_close($curl);
    
    return $responseCurl;
}

//Initialice the return array
$dataSlipInformation = array();

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
$dataSlipInformation['FORTE_ERRORS'] = $requestError;

//Set variable as Submit to do required all fields
$dataSlipInformation['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Convert Subjectives to HTML for Slip and fix it if it is corrupted
$cleanedSubjectives = cleanTextAreaRichTextValue($data["YQP_SUBJECTIVES_GUARANTEE"], "YES");
$dataSlipInformation['YQP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["ORIGINAL_VALUE_CLEANED"];
$dataSlipInformation['YQP_SLIP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["SLIP_VALUE_PARSED"];
//Encode Subjectives Email
$dataSlipInformation['YQP_SUBJECTIVES_GUARANTEE_EMAIL'] = htmlentities($dataSlipInformation['YQP_SUBJECTIVES_GUARANTEE']);
//Convert Additional Information to HTML for Slip and fix it if it is corrupted
if (empty($data["YQP_CLAIM_ADDITIONAL_INFORMATION"])) {
    $dataSlipInformation['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
    $dataSlipInformation['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = "";
} else {
    $cleanedAdditionalInfo = cleanTextAreaRichTextValue($data["YQP_CLAIM_ADDITIONAL_INFORMATION"], "YES");
    $dataSlipInformation['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["ORIGINAL_VALUE_CLEANED"];
    $dataSlipInformation['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["SLIP_VALUE_PARSED"];
}
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');

//Set User Process
$urlUserId = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$responseUserId = callGetCurl($urlUserId);
$dataSlipInformation['YQP_USER_FULLNAME'] = $responseUserId->firstname . ' ' . $responseUserId->lastname;

/*********************** Get values Cedents and Brokers *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetCedentsAndBrokers";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSent);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['PM_OPEN_CEDENTS_BROKERS'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataSlipInformation['FORTE_ERRORS'] = $requestError;
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
        $dataSlipInformation['PM_OPEN_CEDENTS_BROKERS'] = $aOptions;
    }
}

//Assigning values to variables for email and Slip Document
$urlApi = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$dataUser = callGetCurl($urlApi);
$dataSlipInformation['YQP_USER_INITIALS'] = $dataUser->address;
$dataSlipInformation['YQP_NAME_TO'] = $dataUser->firstname . ' ' . $dataUser->lastname;
$dataSlipInformation['YQP_POSITION_TO'] = $dataUser->title;
$dataSlipInformation['YQP_PHONE'] = $dataUser->phone . ' Ext. ' . $dataUser->meta->FORTE_PHONE_EXTENSION;
$dataSlipInformation['YQP_CURRENT_USER_EMAIL'] =  $dataUser->username;

//*********************** Get values Clauses *********************************/
//Set data to send
$dataSend = array();
$dataSend['Country'] = html_entity_decode($data["YQP_COUNTRY_BUSINESS"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['Language'] = html_entity_decode($data["YQP_LANGUAGE"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['Reinsurer'] = html_entity_decode($data["YQP_REASSURED_CEDENT"]["LABEL"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['BaseText'] = html_entity_decode($data["YQP_BASE_TEXT"], ENT_QUOTES | ENT_XML1, 'UTF-8');

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetClauses";

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
    $dataSlipInformation['PM_OPEN_CLAUSES'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataSlipInformation['FORTE_ERRORS'] = $requestError;    
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        for ($r = 0; $r < count($response); $r++) {
            if ($response[$r] != "") {
                $aOptions[$r] = array();
                //Search for variables, replace with the values and return the string to display
                $aOptions[$r]['LABEL'] = replaceVariablesString($response[$r], $data);
            }
        }
        $dataSlipInformation['PM_OPEN_CLAUSES'] = $aOptions;
    }
}

//*********************** Get values Preliminary Clauses *********************************/
$dataSend = array();
$dataSend['Country'] = html_entity_decode($data["YQP_COUNTRY_BUSINESS"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['Language'] = html_entity_decode($data["YQP_LANGUAGE"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['Reinsurer'] = html_entity_decode($data["YQP_REASSURED_CEDENT"]["LABEL"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['BaseText'] = html_entity_decode($data["YQP_BASE_TEXT"], ENT_QUOTES | ENT_XML1, 'UTF-8');

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetPreliminaryClauses";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();
//Set variable to preliminar options
$preliminarOptions = array();
if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['PM_OPEN_PRELIMINARY_CLAUSES'] = "cURL Error #:" . $err;
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
    $dataSlipInformation['FORTE_ERRORS'] = $requestError;    
} else {
    //Get values of the response
    if ($response != "") {
        //Parse values separated with |
        $response = explode("|", $response);
        //Initialize the increment
        $counter = 0;
        for ($r = 0; $r < count($response); $r+=3) {
            if ($response[$r] != "") {
                $aOptions[$counter] = array();
                //Search for variables, replace with the values and return the string to display
                $preliminarClause = replaceVariablesString($response[$r + 2], $data);
                $aOptions[$counter]['LABEL'] = $preliminarClause;

                //Set value to compare and validate the value of the variable
                $valueValidate = $response[$r + 1];

                //Validate if data exists on the data as YES
                if ($data[$response[$r]] == $valueValidate) {
                    array_push($preliminarOptions, $preliminarClause);
                }
                $counter++;
            }
        }
        $dataSlipInformation['PM_OPEN_PRELIMINARY_CLAUSES'] = $aOptions;
    }
}
$dataSlipInformation['YQP_PRELIMINARY_CLAUSES'] = $preliminarOptions;
//Replace special characters Variable YQP_CLIENT_NAME
$clientName = str_replace(array('\\','/',':','*','?','"','<','>','|'), '', $data['YQP_CLIENT_NAME']);
$clientName = mberegi_replace("[\n|\r|\n\r|\t||\x0B]", "", $clientName);
$dataSlipInformation['YQP_CLIENT_NAME_DOCUMENT'] = substr(ltrim(rtrim($clientName)), 0, 150);//ltrim(rtrim($clientName));

//Set Status
//$dataSlipInformation['YQP_STATUS'] = 'SLIP GENERATED';
return $dataSlipInformation;