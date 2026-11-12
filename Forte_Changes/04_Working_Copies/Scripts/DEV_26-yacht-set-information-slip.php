<?php 
$openLUrl = getenv('OPENL_CONNECTION');
/*******************************
 * Set Slip Information needed
 *
 * by Ana Castillo 
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 *****************************/
// require_once("/FORTE_PHP_Library.php");
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
/* Get subscription year
* by Helen Callisaya */
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
/* Replace Variables in String
 * by Helen Callisaya */
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

//Initialice the return array
$dataSlipInformation = array();

//Set variable as Submit to do required all fields
$dataSlipInformation['YQP_SUBMIT_SAVE'] = "SUBMIT";
//Convert Subjectives to HTML for Slip and fix it if it is corrupted
$cleanedSubjectives = cleanTextAreaRichTextValue($data["YQP_SUBJECTIVES_GUARANTEE"], "YES");
$dataSlipInformation['YQP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["ORIGINAL_VALUE_CLEANED"];
$dataSlipInformation['YQP_SLIP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["SLIP_VALUE_PARSED"];
//Encode Subjectives Email
$dataSlipInformation['YQP_SUBJECTIVES_GUARANTEE_EMAIL'] = htmlentities($dataSlipInformation['YQP_SUBJECTIVES_GUARANTEE']);
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');
if (empty($data["YQP_CLAIM_ADDITIONAL_INFORMATION"])) {
    $dataSlipInformation['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
    $dataSlipInformation['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = "";
} else {
    //Convert Additional Information to HTML for Slip and fix it if it is corrupted
    $cleanedAdditionalInfo = cleanTextAreaRichTextValue($data["YQP_CLAIM_ADDITIONAL_INFORMATION"], "YES");
    $dataSlipInformation['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["ORIGINAL_VALUE_CLEANED"];
    $dataSlipInformation['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["SLIP_VALUE_PARSED"];
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
//Set User Process
$urlUserId = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$responseUserId = callGetCurl($urlUserId);
$dataSlipInformation['YQP_USER_FULLNAME'] = $responseUserId->firstname . ' ' . $responseUserId->lastname;

/*********************** Get Coverage Rate if it is necessary *********************************/
//Set Rate no Losses
$rateNoLosses = 0;
if (count(explode("NOLOSSES", $data["YQP_PRODUCT"])) > 1) {
    //Set necessary variables
    $dataSend = array();
    $dataSend['Country'] = $data["YQP_COUNTRY_BUSINESS"];

    //Set OpenL Url
    $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetAdditionalRateAnnualBasic";

    //Call Curl OpenL
    $aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
    //Set response
    $response = $aResponse["DATA"];
    //Set error
    $err = $aResponse["ERROR"];

    if ($err) {
        //If there is an error, set the error
        $dataReturn['RATE_COVERAGE_RESPONSE_OPENL'] = "cURL Error #:" . $err;
    } else {
        //Get values of the response
        $response = json_decode($response);
        $dataReturn['RATE_COVERAGE_RESPONSE_OPENL'] = $response;
        if ($response != "" && $response != null) {
            $rateNoLosses = $response;
        }
    }
}

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
$dataSlipInformation['YQP_QUOTE_DATE'] = date('d-m-Y');
$dataSlipInformation['YQP_BROKER_DEDUCTION_POSITION'] = $data['YQP_REINSURER_INFORMATION'][0]['YQP_BROKER_DEDUCTION'];
//Get subscription year
$startDate = '01-07-' . (date('Y') - 1);
$endDate = date('30-06-Y');
$dataSlipInformation['YQP_SUBSCRIPTION_YEAR'] = getSubscriptionYear($startDate, $endDate, date('d-m-Y', strtotime($data['YQP_PERIOD_FROM'])));

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


/************************** Set OpenL Parameters ************************************/
//Set Exchange rate to use it with all amounts
if ($data["YQP_CURRENCY"] == "USD") {
    $dataSlipInformation["YQP_EXCHANGE_RATE"] = 1;
    $exchange = 1;
} else {
    $exchange = $data["YQP_EXCHANGE_RATE"];
}
/********************************** Get Slip Sum Insured ********************************************/
$dataSend = array();
$dataSend['slipSumInsured'] = array();
$dataSend['slipSumInsured']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
$dataSend['slipSumInsured']['LimitPI'] = ($data["YQP_LIMIT_PI"] * $exchange);
if ($data["YQP_CONTAMINATION"] == "YES") {
    $dataSend['slipSumInsured']['ContaminationLimit'] = ($data["YQP_CONTAMINATION_LIMIT"] * $exchange);
} else {
    $dataSend['slipSumInsured']['ContaminationLimit'] = "-";
}
if ($data["YQP_DAMAGE"] == "YES") {
    $dataSend['slipSumInsured']['DamageEnviromentLimit'] = ($data["YQP_DAMAGE_LIMIT"] * $exchange);
} else {
    $dataSend['slipSumInsured']['DamageEnviromentLimit'] = "-";
}
if ($data["YQP_OWNERS_UNINSURED_VESSEL"] == "YES") {
    $dataSend['slipSumInsured']['UninsuredOwnersLimit'] = ($data["YQP_OWNERS_UNINSURED_VESSEL_LIMIT"] * $exchange);
} else {
    $dataSend['slipSumInsured']['UninsuredOwnersLimit'] = "-";
}
$maxTender = 0;
$maxSumInsuredTender = 0;
$sumInsuredWar = 0;
if ($data["YQP_TENDERS"] == "YES") {
    $tendersGrid = $data["YQP_TENDERS_INFORMATION"];
    for ($t = 0; $t < count($tendersGrid); $t++) {
        $tG = $t + 1;
        $tenderSumInsured = ($tendersGrid[$t]["YQP_TENDERS_LIMIT"] * $exchange);
        $tenderSameHull = $tendersGrid[$t]["YQP_TENDERS_HULL"];

        //Add the sum to war if the Tender is not in Hull
        if ($tenderSameHull == "NO") {
            $dataSend['slipSumInsured']['TenderSumInsured' . $tG] = $tenderSumInsured;
            $sumInsuredWar = $sumInsuredWar + $tenderSumInsured;
            //Get the max value of Tenders if The Tender is not in Hull
            if ($tenderSumInsured > $maxSumInsuredTender) {
                $maxSumInsuredTender = $tenderSumInsured;
            }
        }
    }
    $maxTender = count($tendersGrid);
}
//Set MaxTenders
$dataSend['slipSumInsured']['MaxTenders'] = $maxTender;
if ($data["YQP_WAR"] == "YES") {
    $dataSend['slipSumInsured']['WarSumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
} else {
    $dataSend['slipSumInsured']['WarSumInsured'] = "-";
}
if ($data["YQP_PERSONAL_EFFECTS"] == "YES") {
    $dataSend['slipSumInsured']['PersonalEffectsSumInsured'] = ($data["YQP_PERSONAL_EFFECTS_LIMIT"] * $exchange);
    $dataSend['slipSumInsured']['PersonalEffectsMaxPerson'] = ($data["YQP_PERSONAL_EFFECTS_MAX"] * $exchange);
} else {
    $dataSend['slipSumInsured']['PersonalEffectsSumInsured'] = "-";
    $dataSend['slipSumInsured']['PersonalEffectsMaxPerson'] = "-";
}
if ($data["YQP_MEDICAL_PAYMENTS"] == "YES") {
    $dataSend['slipSumInsured']['MedicalPaymentsSumInsured'] = ($data["YQP_MEDICAL_PAYMENTS_LIMIT"] * $exchange);
    $dataSend['slipSumInsured']['MedicalPaymentsMaxPerson'] = ($data["YQP_MEDICAL_PAYMENTS_MAX"] * $exchange);
} else {
    $dataSend['slipSumInsured']['MedicalPaymentsSumInsured'] = "-";
    $dataSend['slipSumInsured']['MedicalPaymentsMaxPerson'] = "-";
}
if ($data["YQP_TOWING_ASSISTANCE"] == "YES") {
    $dataSend['slipSumInsured']['TowingSumInsured'] = ($data["YQP_TOWING_ASSISTANCE_LIMIT"] * $exchange);
} else {
    $dataSend['slipSumInsured']['TowingSumInsured'] = "-";
}
if ($data["YQP_WATER_SKIING"] == "YES") {
    $dataSend['slipSumInsured']['WaterSkiingLimit'] = ($data["YQP_WATER_SKIING_LIMIT"] * $exchange);
} else {
    $dataSend['slipSumInsured']['WaterSkiingLimit'] = "-";
}
$dataSend['slipSumInsured']['BaseText'] = $data["YQP_BASE_TEXT"];
$dataSend['slipSumInsured']['Language'] = $data["YQP_LANGUAGE"];

//Set data Send to Open L
$dataSlipInformation["DATA_SLIP_SUMINSURED_OPENL_SEND"] = $dataSend;
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/SlipRulesSumInsured" . $data["YQP_LANGUAGE"];
//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];
//Set variable with options
$aOptions = array();
//Set table of Slip Sum Insured
$htmlSlipSumInsured = "";

if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['YQP_SLIP_SUMINSURED_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataSlipInformation['SLIP_SUMINSURED_RESPONSE_OPENL'] = $response;
    $slipSumInsured = array();
    //Set table width
    $tableStyle = 0;
    if ($response != "" && $response != null) {
        //Set max column
        $maxColumn = 0;
        foreach ($response as $key => $value) {
            //Get Row and Column of the table
            $column = explode("_", $key)[0];
            $column = explode("Column", $column)[1];
            $column = $column * 1;
            if ($column > $maxColumn) {
                $maxColumn = $column;
            }
            $row = explode("_", $key)[1];
            //Save table with
            if ($row == "TableStyle") {
                $tableStyle = $value;
            } else {
                //Add the row if exist
                if (!isset($slipSumInsured[$row])) {
                    $slipSumInsured[$row] = array();
                }
                //Validate if it is the second Column to Do format on fields as currency
                if ($column == 2) {
                    //Analyze value if there is only amounts with breaks
                    $flagAmount = 0;
                    if (is_numeric($value)) {
                        $flagAmount = 1;
                        $value = number_format($value, 2, ".", ",");
                    } else {
                        //Validate that the value is string or number
                        if (!is_object($value)) {
                            //Split string to stract <br> and <b>
                            $amounts = explode("<br/>", $value);
                            if (count($amounts) > 1) {
                                //Set flag to concatenate the amount and <br/>
                                $brAmounts = "";
                                for ($a = 0; $a < count($amounts); $a++) {
                                    //Validate <b> tag
                                    $bAmount = explode("<b>", $amounts[$a]);
                                    $flagBTag = false;
                                    if (count($bAmount) > 1) {
                                        //Delete <b> tag and set flag to add it
                                        $bAmount = explode("</b>", $bAmount[1])[0];
                                        $amounts[$a] = $bAmount;
                                        $flagBTag = true;
                                    }
                                    //Validate if field is amount
                                    if (is_numeric($amounts[$a])) {
                                        //Validate if field has <b> tag
                                        if ($flagBTag) {
                                            $brAmounts .= "<b>" . number_format($amounts[$a], 2, ".", ",") . "</b>";
                                        } else {
                                            $brAmounts .= number_format($amounts[$a], 2, ".", ",");
                                        }
                                    } else {
                                        $brAmounts .= $amounts[$a];
                                    }
                                    //Add <br/> if it is not the last field
                                    if ($a + 1 < count($amounts)) {
                                        $brAmounts .= "<br/>";
                                    }
                                }
                                //Set value with format amounts
                                $value = $brAmounts;
                            }
                        }
                    }
                    //Set column on the array
                    $slipSumInsured[$row][$column] = $value;
                } else {
                    //Set column on the array
                    $slipSumInsured[$row][$column] = $value;
                }
            }
        }
        //Set OpenL array Response
        $dataSlipInformation['YQP_SLIP_SUMINSURED_RESPONSE'] = $slipSumInsured;
        //Set HTML for Slip deductibles
        $stylesRow = array();
        if ($maxColumn > 0) {
            //Start drawing table
            $htmlSlipSumInsured = "<table style='" . $tableStyle . "'>";
            foreach ($slipSumInsured as $key => $value) {
                $flagDrawTable = 0;
                for ($c = 1; $c <= $maxColumn; $c++) {
                    //Get Styles to columns
                    if ($key == "Style") {
                        $stylesRow[$c] = $value[$c];
                    } else {
                        //Validate that the Key is Row if not don't draw it
                        $validKey = explode("Row", $key);
                        if (count($validKey) > 1) {
                            if ($flagDrawTable == 0) {
                                $flagDrawTable = 1;
                                $htmlSlipSumInsured .= "<tr>";
                            }
                            //Set value if it is None
                            $newValue = "";
                            if ($value[$c] != "None") {
                                //Change format number if the value is a number
                                //Separate the string into an array
                                $validateIsNumeric = explode(" ", $value[$c]);                    
                                for ($x = 0; $x < count($validateIsNumeric); $x++) {
                                    //Validate value is numeric
                                    if (is_numeric($validateIsNumeric[$x])) {
                                        $validateIsNumeric[$x] = number_format($validateIsNumeric[$x], 2, '.', ',');                            
                                    }                  
                                }
                                //Convert array to string
                                $newValue = implode(" ", $validateIsNumeric);
                            }
                            //Draw column
                            $htmlSlipSumInsured .= "<td style='" . $stylesRow[$c] . "'>" . $newValue . "</td>";
                        }
                    }
                }
                if ($flagDrawTable == 1) {
                    $htmlSlipSumInsured .= "</tr>";
                }
            }
            $htmlSlipSumInsured .= "</table>";
        }
        //Set HTMl of Slip deductibles
        $dataSlipInformation['YQP_SLIP_SUMINSURED_HTML'] = htmlentities($htmlSlipSumInsured);
    }
}
/*********************** Get Slip Deductibles *********************************/
$dataSend = array();
$dataSend['OtherDeductible'] = array();
$dataSend['OtherDeductible']['Deductible'] = $data["YQP_DEDUCTIBLE"];
$dataSend['OtherDeductible']['Country'] = $data["YQP_COUNTRY_BUSINESS"];
$dataSend['OtherDeductible']['LengthYacht'] = $data["YQP_LENGTH_UNIT"];
$dataSend['OtherDeductible']['VesselAge'] = $data["YQP_AGE"];
$dataSend['OtherDeductible']['Language'] = $data["YQP_LANGUAGE"];
$dataSend['OtherDeductible']['TypeYacht'] = $data["YQP_TYPE_YACHT"];
$dataSend['OtherDeductible']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
$dataSend['OtherDeductible']['War'] = $data["YQP_WAR_DEDUCTIBLE"];
if ($data["YQP_WAR"] == "YES") {
    $dataSend['OtherDeductible']['WarSumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
} else {
    $dataSend['OtherDeductible']['WarSumInsured'] = 0;
}
if ($data["YQP_PERSONAL_EFFECTS"] == "YES") {
    $dataSend['OtherDeductible']['PersonalEffectsSumInsured'] = ($data["YQP_PERSONAL_EFFECTS_LIMIT"] * $exchange);
} else {
    $dataSend['OtherDeductible']['PersonalEffectsSumInsured'] = 0;
}
if ($data["YQP_MEDICAL_PAYMENTS"] == "YES") {
    $dataSend['OtherDeductible']['MedicalPaymentsSumInsured'] = ($data["YQP_MEDICAL_PAYMENTS_LIMIT"] * $exchange);
} else {
    $dataSend['OtherDeductible']['MedicalPaymentsSumInsured'] = 0;
}
$dataSend['OtherDeductible']['LimitPI'] = ($data["YQP_LIMIT_PI"] * $exchange);
$dataSend['OtherDeductible']['TendersMaxSumInsured'] = $maxSumInsuredTender;
//Validate if The Product is with HULL on the options
if (explode($data["YQP_PRODUCT"], "HULL") > 1) {
    //Validate if the Special Area is YES
    if ($data["YQP_SPECIAL_AREA"] == "YES") {
        $dataSend['OtherDeductible']['SpecialDeductibleCode'] = $data["YQP_TYPE_SPECIAL_DEDUCTIBLE"];
        $dataSend['OtherDeductible']['ShowDeductibleCode'] = $data["YQP_SHOW_SPECIAL_DEDUCTIBLE"];
        $dataSend['OtherDeductible']['SpecialArea'] = $data["YQP_SPECIAL_AREA_ZONE"];
        if ($data["YQP_SHOW_SPECIAL_DEDUCTIBLE"] == "PER") {
            $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = $data["YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE"];
        } else {
            $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = "";
        }
    } else {
        $dataSend['OtherDeductible']['SpecialDeductibleCode'] = "";
        $dataSend['OtherDeductible']['ShowDeductibleCode'] = "";
        $dataSend['OtherDeductible']['SpecialArea'] = "";
        $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = "";
    }
} else {
    $dataSend['OtherDeductible']['SpecialDeductibleCode'] = "";
    $dataSend['OtherDeductible']['ShowDeductibleCode'] = "";
    $dataSend['OtherDeductible']['SpecialArea'] = "";
    $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = "";
}
$dataSend['OtherDeductible']['LimitExcessPI'] = $differenceLimitPI;
$dataSend['OtherDeductible']['Contamination'] = $data['YQP_CONTAMINATION'];
$dataSend['OtherDeductible']['DamageEnvironment'] = $data['YQP_DAMAGE'];
$dataSend['OtherDeductible']['OwnersUninsured'] = $data['YQP_OWNERS_UNINSURED_VESSEL'];
if (count(explode("NOLOSSES", $data["YQP_PRODUCT"])) > 1) {
    $dataSend['OtherDeductible']['ProductExcluding'] = "YES";
} else {
    $dataSend['OtherDeductible']['ProductExcluding'] = "NO";
}
$dataSend['OtherDeductible']['WaterSkiing'] = $data['YQP_WATER_SKIING'];
$dataSend['OtherDeductible']['BaseText'] = $data['YQP_BASE_TEXT'];
$dataSend['OtherDeductible']['TextMachinery'] = $data['YQP_MACHINERY'];
$dataSend['OtherDeductible']['ShowDeductible'] = $data['YQP_SHOW_DEDUCTIBLE'];
$dataSend['OtherDeductible']['PIRCValidation'] = $data['YQP_PIRC_VALIDATION'];
$dataSend['OtherDeductible']['PIDeductible'] = $data['YQP_LIMIT_PI_DEDUCTIBLE'] * 1;
//Set data Send to OpenL
$dataSlipInformation["DATA_SLIP_RULES_DEDUCTIBLES_OPENL_SEND"] = $dataSend;
//Set OpenL Url to Rules
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/OtherDeductibles";
//Set another URL only for Multinational Reinsurer
if ($data["YQP_COUNTRY_BUSINESS"] == "Puerto Rico" && $data["YQP_REASSURED_CEDENT"]["LABEL"] == "Multinational Insurance Company (Puerto Rico)-MIC") {
    $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/OtherDeductiblesMultinational";
}
//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];
//Set variable with options
$aOptions = array();
//Set table of Total Prime
$htmlOtherDeductibles = "";
if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['YQP_OTHER_DEDUCTIBLES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataSlipInformation['OTHER_DEDUCTIBLES_RESPONSE_OPENL'] = $response;
    if ($response != "" && $response != null) {
        $htmlOtherDeductibles .= "<table width='70%' border='1'>";
        $htmlOtherDeductibles .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverage</b></td><td style='padding: 5px;'><b>Deductible</b></td></tr>";
        foreach ($response as $key => $value) {
            //Validate Keys to not show
            if ($key != "HullValue") {
                //Slip text in 2 columns if it is necessary
                if (count(explode("SPLIT", $key)) > 1) {
                    $key = explode("_SPLIT_", $value)[0];
                    $value = explode("_SPLIT_", $value)[1];                 
                }

                //Replace & and espace on Key if it is necessary
                $key = str_replace("_AND_", "&", $key);
                $key = str_replace("_", " ", $key);
                
                //Validate if value has not an answer
                $valueAux = explode("null", $value);
                if (count($valueAux) > 1) {
                    $value = "";
                }

                //Validate if Key is not empty
                if ($key != "null") {
                    $htmlOtherDeductibles .= "<tr><td width='50%' style='text-align: left; padding: 5px;'>" . $key . "</td>";
                    //Validate if value is Numeric
                    //Separate the string into an array
                    $validateIsNumeric = explode(" ", $value);                    
                    for ($vn = 0; $vn < count($validateIsNumeric); $vn++) {
                        //Validate value is numeric
                        if (is_numeric($validateIsNumeric[$vn])) {
                            $validateIsNumeric[$vn] = number_format($validateIsNumeric[$vn], 2, '.', ',');                            
                        }                  
                    }
                    //Convert array to string
                    $value = implode(" ", $validateIsNumeric); 

                    $htmlOtherDeductibles .= "<td width='50%' style='text-align:right; padding: 5px;'>" . $value . "</td></tr>";
                }
            }
        }
        //Close Table
        $htmlOtherDeductibles .= "</table>";

        $dataSlipInformation['YQP_OTHER_DEDUCTIBLES_RESPONSE'] = $htmlOtherDeductibles;
        $dataSlipInformation['YQP_OTHER_DEDUCTIBLES_RESPONSE_HTML'] = htmlentities($htmlOtherDeductibles);
    }
}

//Set OpenL Url For Slip
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/SlipRulesDeductibles" . $data["YQP_LANGUAGE"];
//Set another URL only for Multinational Reinsurer
if ($data["YQP_COUNTRY_BUSINESS"] == "Puerto Rico" && $data["YQP_REASSURED_CEDENT"]["LABEL"] == "Multinational Insurance Company (Puerto Rico)-MIC") {
    $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/SlipRulesDeductiblesMultinational";
}

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Slip Deductibles
$htmlSlipDeductibles = "";

if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['YQP_SLIP_DEDUCTIBLES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataSlipInformation['SLIP_DEDUCTIBLES_RESPONSE_OPENL'] = $response;
    $slipDeductibles = array();

    //Set value for slip tenders percentage
    $dataSlipInformation['YQP_SLIP_TENDERS_PERCENTAGE_OPENL_VALUE'] = "1";
    
    //Set table width
    $tableStyle = 0;
    if ($response != "" && $response != null) {
        //Set max column
        $maxColumn = 0;
        foreach ($response as $key => $value) {
            //Get Row and Column of the table
            $column = explode("_", $key)[0];
            $column = explode("Column", $column)[1];
            $column = $column * 1;
            if ($column > $maxColumn) {
                $maxColumn = $column;
            }
            $row = explode("_", $key)[1];

            //Save PI value from table to use on the Slip
            if ($row == "PI") {
                $dataSlipInformation['YQP_SLIP_PI_OPENL_VALUE'] = $value;
            }

            //Save TendersPercentage value from table to use on the Slip
            if ($row == "TendersPercentage") {
                $dataSlipInformation['YQP_SLIP_TENDERS_PERCENTAGE_OPENL_VALUE'] = $value;
            }

            //Save Variables values from openL
            $getVariable = explode("YQP_", $key);
            if (count($getVariable) > 1) {
                $valueVariable = $value;
                if (is_numeric($valueVariable)) {
                    $valueVariable = number_format($valueVariable, 2, ".", ",");
                }
                $dataSlipInformation['YQP_' . $getVariable[1]] = $valueVariable;
            }

            //Save table with
            if ($row == "TableStyle") {
                $tableStyle = $value;
            } else {
                //Add the row if exist
                if (!isset($slipDeductibles[$row])) {
                    $slipDeductibles[$row] = array();
                }

                //Validate if it is the second Column to Do format on fields as currency
                if ($column == 2) {
                    //Analyze value if there is only amounts with breaks
                    $flagAmount = 0;
                    if (is_numeric($value)) {
                        $flagAmount = 1;
                        $value = number_format($value, 2, ".", ",");
                    } else {
                        //Validate that the value is string or number
                        if (!is_object($value)) {
                            //Split string to stract <br> and <b>
                            $amounts = explode("<br/>", $value);
                            if (count($amounts) > 1) {
                                //Set flag to concatenate the amount and <br/>
                                $brAmounts = "";
                                for ($a = 0; $a < count($amounts); $a++) {
                                    //Validate <b> tag
                                    $bAmount = explode("<b>", $amounts[$a]);
                                    $flagBTag = false;
                                    if (count($bAmount) > 1) {
                                        //Delete <b> tag and set flag to add it
                                        $bAmount = explode("</b>", $bAmount[1])[0];
                                        $amounts[$a] = $bAmount;
                                        $flagBTag = true;
                                    }
                                    //Validate if field is amount
                                    if (is_numeric($amounts[$a])) {
                                        //Validate if field has <b> tag
                                        if ($flagBTag) {
                                            $brAmounts .= "<b>" . number_format($amounts[$a], 2, ".", ",") . "</b>";
                                        } else {
                                            $brAmounts .= number_format($amounts[$a], 2, ".", ",");
                                        }
                                    } else {
                                        $brAmounts .= $amounts[$a];
                                    }
                                    //Add <br/> if it is not the last field
                                    if ($a + 1 < count($amounts)) {
                                        $brAmounts .= "<br/>";
                                    }
                                }
                                //Set value with format amounts
                                $value = $brAmounts;
                            }
                        }
                    }
                    //Set column on the array
                    $slipDeductibles[$row][$column] = $value;
                } else {
                    //Set column on the array
                    $slipDeductibles[$row][$column] = $value;
                }
            }
        }
        //Set OpenL array Response
        $dataSlipInformation['YQP_SLIP_DEDUCTIBLES_RESPONSE'] = $slipDeductibles;

        //Set HTML for Slip deductibles
        $stylesRow = array();
        if ($maxColumn > 0) {
            //Start drawing table HTML
            $htmlSlipDeductibles = "<table style='" . $tableStyle . "'>";
            foreach ($slipDeductibles as $key => $value) {
                $flagDrawTable = 0;
                for ($c = 1; $c <= $maxColumn; $c++) {
                    if ($key == "Style") {
                        $stylesRow[$c] = $value[$c];
                    } else {
                        //Validate that the Key is Row if not don't draw it
                        $validKey = explode("Row", $key);
                        if (count($validKey) > 1) {
                            if ($value[$c] != "null") {
                                if ($flagDrawTable == 0) {
                                    $flagDrawTable = 1;
                                    $htmlSlipDeductibles .= "<tr>";
                                }
                                //Set value if it is None
                                $newValue = "";
                                if ($value[$c] != "None") {
                                    $newValue = $value[$c];
                                    //Separate the string into an array
                                    $validateIsNumeric = explode(" ", $value[$c]);                    
                                    for ($x = 0; $x < count($validateIsNumeric); $x++) {
                                        //Search % in string
                                        $pos = strpos($validateIsNumeric[$x], "%");
                                        if ($pos === false) {
                                            if (is_numeric($validateIsNumeric[$x])) {
                                                $validateIsNumeric[$x] = number_format($validateIsNumeric[$x], 2, '.', ',');
                                            }
                                        } else {
                                            $numberPercentage = explode("%", $validateIsNumeric[$x]);
                                            for ($vp = 0; $vp < count($numberPercentage); $vp++) {
                                                if (is_numeric($numberPercentage[$vp])) {
                                                    $numberPercentage[$vp] = number_format($numberPercentage[$vp], 2, '.', ',');                            
                                                }
                                            }
                                            $validateIsNumeric[$x] = implode("%", $numberPercentage);
                                        }                   
                                    }
                                    //Convert array to string
                                    $newValue = implode(" ", $validateIsNumeric);
                                }
                                $htmlSlipDeductibles .= "<td style='" . $stylesRow[$c] . "'>" . $newValue . "</td>";
                            }
                        }
                    }
                }
                if ($flagDrawTable == 1) {
                    $htmlSlipDeductibles .= "</tr>";
                }
            }
            $htmlSlipDeductibles .= "</table>";
        }
        //Set HTMl of Slip deductibles
        $dataSlipInformation['YQP_SLIP_DEDUCTIBLE_HTML'] = htmlentities($htmlSlipDeductibles);
    }
}

/*********************** Get Other Coverages *********************************/
$dataSend = array();
$dataSend['final1'] = array();
$dataSend['final1']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
$dataSend['final1']['FinalRate'] = $data["YQP_FINAL_RATE_VALUE"];
$dataSend['final1']['TypeYacht'] = $data["YQP_TYPE_YACHT"];
$dataSend['final1']['Country'] = $data["YQP_COUNTRY_BUSINESS"];
if ($data["YQP_PERSONAL_EFFECTS"] == "YES") {
    $dataSend['final1']['PersonalEffects'] = $data["YQP_PERSONAL_EFFECTS"];
    $dataSend['final1']['PersonalEffectsSumInsured'] = ($data["YQP_PERSONAL_EFFECTS_LIMIT"] * $exchange);
} else {
    $dataSend['final1']['PersonalEffects'] = "";
    $dataSend['final1']['PersonalEffectsSumInsured'] = 0;
}
if ($data["YQP_MEDICAL_PAYMENTS"] == "YES") {
    $dataSend['final1']['MedicalPayments'] = $data["YQP_MEDICAL_PAYMENTS"];
    $dataSend['final1']['MedicalPaymentsSumInsured'] = ($data["YQP_MEDICAL_PAYMENTS_LIMIT"]*$exchange);
} else {
    $dataSend['final1']['MedicalPayments'] = "";
    $dataSend['final1']['MedicalPaymentsSumInsured'] = 0;
}
$maxTender = 0;
$maxSumInsuredTender = 0;
$sumInsuredWar = 0;
$descriptionTenderTowed = "";
$flagTenderTowed = "NO";
if ($data["YQP_TENDERS"] == "YES") {
    $tendersGrid = $data["YQP_TENDERS_INFORMATION"];
    for ($t = 0; $t < count($tendersGrid); $t++) {
        if ($tendersGrid[$t]['YQP_TENDERS_TOWED'] == "YES") {
            $descriptionTenderTowed = $tendersGrid[$t]['YQP_TENDERS_DESCRIPTION'];
            $flagTenderTowed = "YES";
        }
        $tG = $t + 1;
        $tenderSumInsured = ($tendersGrid[$t]["YQP_TENDERS_LIMIT"] * $exchange);
        $tenderSameHull = $tendersGrid[$t]["YQP_TENDERS_HULL"];
        $dataSend['final1']['TenderPercentage' . $tG] = $tenderSameHull;
        $dataSend['final1']['TenderSumInsured' . $tG] = $tenderSumInsured;

        //Add the sum to war if the Tender is not in Hull
        if ($tenderSameHull == "NO") {
            $sumInsuredWar = $sumInsuredWar + $tenderSumInsured;
            //Get the max value of Tenders if The Tender is not in Hull
            if ($tenderSumInsured > $maxSumInsuredTender) {
                $maxSumInsuredTender = $tenderSumInsured;
            }
        }
    }
    $maxTender = count($tendersGrid);
} else {
    $dataSend['final1']['TenderSumInsured1'] = 0;
    $dataSend['final1']['TenderPercentage1'] = 0;
}
if ($data["YQP_WAR"] == "YES") {
    $dataSend['final1']['TypeCoverage'] = $data["YQP_WAR_TYPE_COVERAGE"];
    $dataSend['final1']['WarSumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
} else {
    $dataSend['final1']['TypeCoverage'] = "";
    $dataSend['final1']['WarSumInsured'] = 0;
}
$dataSend['final1']['LimitPI'] = ($data["YQP_LIMIT_PI"] * $exchange);
//Set Excluded No Losses
if ($rateNoLosses != 0) {
    $dataSend['final1']['ExcludedPartialLosses'] = "YES";
} else {
    $dataSend['final1']['ExcludedPartialLosses'] = "NO";
}
$dataSend['final1']['MaxTenders'] = $maxTender;
$dataSend['final1']['Language'] = $data["YQP_LANGUAGE"];
$dataSend['final1']['BaseText'] = $data["YQP_BASE_TEXT"];
$brokerNecessary = "NO";
$brokerPercentage = 0;
if ($data["YQP_GROSS_BROKER_CHANGE"]) {
    $brokerNecessary = "YES";
    $brokerPercentage = (($data["YQP_BROKER_PERCENTAGE"] * 1) / 100);
}
$dataSend['final1']['BrokerNecessary'] = $brokerNecessary;
$dataSend['final1']['PercentageBroker'] = $brokerPercentage;
$dataSend['final1']['DaysPeriod'] = $data["YQP_DAYS_DIFFERENCE"];

//Set data Send to Open L
$dataSlipInformation["DATA_OTHER_COVERAGES_OPENL_SEND"] = $dataSend;

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/BusinessFinalPremium";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Total Prime
$htmlTotalPrime = "";

//Set total of Prime for Anual Premium and Gross Annual Premium
$totalPrimeAnnual = 0;
$totalPrimeGross = 0;

//Set difference Limit PI
$differenceLimitPI = 0;

if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['YQP_OTHER_COVERAGES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataSlipInformation['OTHER_COVERAGES_RESPONSE_OPENL'] = $response;
    if ($response != "" && $response != null) {
        //Set table with depends on Broker exist
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            $htmlTotalPrime .= "<table width='90%' border='1'>";
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Anual Premium</b></td><td style='padding: 5px;'><b>Gross Annual Premium</b></td></tr>";
        } else {
            $htmlTotalPrime .= "<table width='70%' border='1'>";
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Anual Premium</b></td></tr>";
        }
        //Reorder the response to create the HTMl
        $responseConverted = array();
        foreach ($response as $key => $value) {
            //Set action and openL variables
            $action = explode("_", $key)[0];
            $variableOpenL = explode("_", $key, 2)[1];
            //Not consider options not necessary
            if ($variableOpenL != "_90Percent" && $variableOpenL != "_10Percent" && $variableOpenL != "PrimaTotal" && 
                $variableOpenL != "DifferenceLimitPI" && $variableOpenL != "MaxTenders") {
                //Add the option if not exist
                if (!isset($responseConverted[$variableOpenL])) {
                    $responseConverted[$variableOpenL] = array();
                }
                $responseConverted[$variableOpenL][$action] = $value;
            }

            //Set difference PI to use in next rule
            if ($variableOpenL == "DifferenceLimitPI" && $action == "AnnualPremium") {
                $differenceLimitPI = $value * 1;
            }
        }
        $dataSlipInformation['YQP_OTHER_COVERAGES_RESPONSE_CONVERTED'] = $responseConverted;

        //Form table of HTML
        foreach ($responseConverted as $key => $value) {
            //Replace & and espace in Key if it is necessary
            $key = str_replace("_AND_", "&", $key);
            $key = str_replace("_", " ", $key);
            //Validate if we need to draw or not
            $validateDraw = true;

            //If it is a tender validate that the tender was selected on the request
            if (count(explode("Tender ", $key)) > 1) {
                if ($maxTender < (explode("Tender ", $key)[1] * 1)) {
                    $validateDraw = false;
                }
            }

            if ($validateDraw) {
                //Set total value to sum Broker or normal
                if ($data["YQP_GROSS_BROKER_CHANGE"]) {
                    $totalPrimeGross = $totalPrimeGross + ($value["GrossAnnualPremium"] * 1);
                    $totalPrimeAnnual = $totalPrimeAnnual + ($value["AnnualPremium"] * 1);
                    //Change format number if the value is a number for Gross Annual Premium that cannot exist
                    //Separate the string into an array
                    $validateIsNumeric = explode(" ", $value["GrossAnnualPremium"]);                    
                    for ($vn = 0; $vn < count($validateIsNumeric); $vn++) {
                        //Validate value is numeric
                        if (is_numeric($validateIsNumeric[$vn])) {
                            $validateIsNumeric[$vn] = number_format($validateIsNumeric[$vn], 2, '.', ',');                            
                        }                  
                    }
                    //Convert array to string
                    $value["GrossAnnualPremium"] = implode(" ", $validateIsNumeric);
                } else {
                    $totalPrimeAnnual = $totalPrimeAnnual + ($value["AnnualPremium"] * 1);
                }

                //Change format number if the value is a number for Annual Premium that always exist
                //Separate the string into an array
                $validateIsNumeric = explode(" ", $value["AnnualPremium"]);                    
                for ($vn = 0; $vn < count($validateIsNumeric); $vn++) {
                    //Validate value is numeric
                    if (is_numeric($validateIsNumeric[$vn])) {
                        $validateIsNumeric[$vn] = number_format($validateIsNumeric[$vn], 2, '.', ',');                            
                    }                  
                }
                //Convert array to string
                $value["AnnualPremium"] = implode(" ", $validateIsNumeric);


                //Set html if exist Broker as a new column or not
                if ($data["YQP_GROSS_BROKER_CHANGE"]) {
                    //Set html with Broker
                    $htmlTotalPrime .= "<tr><td width='30%' style='text-align: left; padding: 5px;'>" . $key . "</td>";
                    $htmlTotalPrime .= "<td width='35%' style='text-align:right; padding: 5px;'>" . $value["AnnualPremium"] . "</td>";
                    $htmlTotalPrime .= "<td width='35%' style='text-align:right; padding: 5px;'>" . $value["GrossAnnualPremium"] . "</td></tr>";
                } else {
                    //Set html without Broker
                    $htmlTotalPrime .= "<tr><td width='50%' style='text-align: left; padding: 5px;'>" . $key . "</td>";
                    $htmlTotalPrime .= "<td width='50%' style='text-align:right; padding: 5px;'>" . $value["AnnualPremium"] . "</td></tr>";
                }
            }
        }
        //Add total Premium value ass Annual Total
        $dataSlipInformation['YQP_TOTAL_PREMIUM_SLIP'] = $totalPrimeAnnual;

        //Change format number if the total prime is a number for Annual
        if (is_numeric($totalPrimeAnnual)) {
            $totalPrimeAnnual = number_format($totalPrimeAnnual, 2, ".", ",");
        }

        //Add final Rate to the table
        //Set total Prime if Broker exist or not
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            //Add total Premium value as with Broker
            $dataSlipInformation['YQP_TOTAL_PREMIUM_SLIP'] = $totalPrimeGross;
            //Change format number if the total prime is a number
            if (is_numeric($totalPrimeGross)) {
                $totalPrimeGross = number_format($totalPrimeGross, 2, ".", ",");
            }
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'>";
            $htmlTotalPrime .= "<td width='30%' style='text-align: left; padding: 5px;'><b>PRIMA TOTAL 100.00%</b></td>";
            $htmlTotalPrime .= "<td width='35%' style='text-align: right; padding: 5px;'><b>" . $totalPrimeAnnual . "</b></td>";
            $htmlTotalPrime .= "<td width='35%' style='text-align: right; padding: 5px;'><b>" . $totalPrimeGross . "</b></td></tr>";
        } else {
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td width='50%' style='text-align: left; padding: 5px;'><b>PRIMA TOTAL 100.00%</b></td>";
            $htmlTotalPrime .= "<td width='50%' style='text-align: right; padding: 5px;'><b>" . $totalPrimeAnnual . "</b></td></tr>";
        }

        //Close Table
        $htmlTotalPrime .= "</table>";
        
        //Add html with other coverages table
        $dataSlipInformation['YQP_OTHER_COVERAGES_RESPONSE'] = $htmlTotalPrime;

        //Set Html of Slip Final Premium
        $dataSlipInformation['YQP_DOCUMENT_FINAL_PREMIUM_HTML'] = htmlentities($htmlTotalPrime);
    }
}

/*********************** Get Slip Rates *********************************/
$dataSlipInformation["DATA_SLIP_RULES_RATE_SEND"] = $dataSend;

$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/SlipRulesRates" . $data["YQP_LANGUAGE"];

$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
$response = $aResponse["DATA"];
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Slip Rates
$htmlSlipRates = "";

if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['YQP_SLIP_RATES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataSlipInformation['SLIP_RATES_RESPONSE_OPENL'] = $response;
    $slipRates = array();
    //Set table width
    $tableStyle = 0;
    if ($response != "" && $response != null) {
        //Set max column
        $maxColumn = 0;
        foreach ($response as $key => $value) {
            //Get Row and Column of the table
            $column = explode("_", $key)[0];
            $column = explode("Column", $column)[1];
            $column = $column * 1;
            if ($column > $maxColumn) {
                $maxColumn = $column;
            }
            $row = explode("_", $key)[1];

            //Save table with
            if ($row == "TableStyle") {
                $tableStyle = $value;
            } else {
                //Add the row if exist
                if (!isset($slipRates[$row])) {
                    $slipRates[$row] = array();
                }

                //Validate if it is the second Column to Do format on fields as currency
                if ($column == 2) {
                    //Analyze value if there is only amounts with breaks
                    $flagAmount = 0;
                    if (is_numeric($value)) {
                        $flagAmount = 1;
                        $value = number_format($value, 2, ".", ",");
                    } else {
                        //Validate that the value is string or number
                        if (!is_object($value)) {
                            //Split string to stract <br> and <b>
                            $amounts = explode("<br/>", $value);
                            if (count($amounts) > 1) {
                                //Set flag to concatenate the amount and <br/>
                                $brAmounts = "";
                                for ($a = 0; $a < count($amounts); $a++) {
                                    //Validate <b> tag
                                    $bAmount = explode("<b>", $amounts[$a]);
                                    $flagBTag = false;
                                    if (count($bAmount) > 1) {
                                        //Delete <b> tag and set flag to add it
                                        $bAmount = explode("</b>", $bAmount[1])[0];
                                        $amounts[$a] = $bAmount;
                                        $flagBTag = true;
                                    }
                                    //Validate if field is amount
                                    if (is_numeric($amounts[$a])) {
                                        //Validate if field has <b> tag
                                        if ($flagBTag) {
                                            $brAmounts .= "<b>" . number_format($amounts[$a], 2, ".", ",") . "</b>";
                                        } else {
                                            $brAmounts .= number_format($amounts[$a], 2, ".", ",");
                                        }
                                    } else {
                                        $brAmounts .= $amounts[$a];
                                    }
                                    //Add <br/> if it is not the last field
                                    if ($a + 1 < count($amounts)) {
                                        $brAmounts .= "<br/>";
                                    }
                                }
                                //Set value with format amounts
                                $value = $brAmounts;
                            }
                        }
                    }

                    //Set column on the array
                    $slipRates[$row][$column] = $value;
                } else {
                    //Set column on the array
                    $slipRates[$row][$column] = $value;
                }
            }
        }
        //Set OpenL array Response
        $dataSlipInformation['YQP_SLIP_RATES_RESPONSE'] = $slipRates;

        //Set HTML for Slip Rates
        $stylesRow = array();
        if ($maxColumn > 0) {
            //Start drawing table HTML
            $htmlSlipRates = "<table style='" . $tableStyle . "'>";
            foreach ($slipRates as $key => $value) {
                $flagDrawTable = 0;
                for ($c = 1; $c <= $maxColumn; $c++) {
                    if ($key == "Style") {
                        $stylesRow[$c] = $value[$c];
                    } else {
                        //Validate that the Key is Row if not don't draw it
                        $validKey = explode("Row", $key);
                        if (count($validKey) > 1) {
                            
                            if ($flagDrawTable == 0) {
                                $flagDrawTable = 1;
                                $htmlSlipRates .= "<tr>";
                            }
                            //Set value if it is None
                            $newValue = "";
                            if ($value[$c] != "None") {
                                //Change format number if the value is a number
                                //Separate the string into an array
                                $validateIsNumeric = explode(" ", $value[$c]);                    
                                for ($x = 0; $x < count($validateIsNumeric); $x++) {
                                    //Validate value is numeric
                                    if (is_numeric($validateIsNumeric[$x])) {
                                        $validateIsNumeric[$x] = number_format($validateIsNumeric[$x], 2, '.', ',');                            
                                    }                  
                                }
                                //Convert array to string
                                $newValue = implode(" ", $validateIsNumeric);
                            }
                            //Valdiate if the Value needs replace of data
                            if (count(explode("REQUEST_DATA_", $newValue)) > 1) {
                                $replaceDataUid = explode("REQUEST_DATA", $newValue)[1];
                                //Set flag to bold field
                                $bold = 0;
                                if (count(explode("BOLD_", $replaceDataUid)) > 1) {
                                    $bold = 0;
                                    //Delete Bold of UID Request data
                                    $replaceDataUid = explode("BOLD_", $replaceDataUid)[1];
                                }

                                //Verify if the value is on previous data of this script or data of the request
                                if (isset($dataSlipInformation[$replaceDataUid])) {
                                    $newValue = number_format($dataSlipInformation[$replaceDataUid], 2, ".", ",");
                                } else {
                                    $newValue = number_format($data[$replaceDataUid], 2, ".", ",");
                                }
                            }

                            //Cut numbers to 2 decimals
                            //$newValue = preg_replace('/\.(\d{2}).*/', '.$1', $newValue);

                            //Set new td
                            $htmlSlipRates .= "<td style='" . $stylesRow[$c] . "'>" . $newValue . "</td>";
                        }
                    }
                }
                if ($flagDrawTable == 1) {
                    $htmlSlipRates .= "</tr>";
                }
            }
            $htmlSlipRates .= "</table>";
        }
        //Set HTMl of Slip Rates
        $dataSlipInformation['YQP_SLIP_RATES_HTML'] = htmlentities($htmlSlipRates);
    }
}

//*********************** Convert Language Country for Slip *********************************/
$dataSend = array();
$dataSend['Country'] = html_entity_decode($data["YQP_COUNTRY_BUSINESS"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$dataSend['Language'] = html_entity_decode($data["YQP_LANGUAGE"], ENT_QUOTES | ENT_XML1, 'UTF-8');

$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/ConvertCountryLanguage";

$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
$response = $aResponse["DATA"];
$err = $aResponse["ERROR"];

if ($err) {
    //If there is an error, set the error
    $dataSlipInformation['PM_OPEN_COUNTRY_LANGUAGE'] = "cURL Error #:" . $err;
} else {
    //Get Country language of the response
    if ($response != "") {
        $dataSlipInformation['PM_OPEN_COUNTRY_LANGUAGE'] = $response;
    }
}
//Replace special characters Variable YQP_CLIENT_NAME
$clientName = str_replace(array('\\','/',':','*','?','"','<','>','|'), '', $data['YQP_CLIENT_NAME']);
$clientName = mberegi_replace("[\n|\r|\n\r|\t||\x0B]", "", $clientName);
$dataSlipInformation['YQP_CLIENT_NAME_DOCUMENT'] = substr(ltrim(rtrim($clientName)), 0, 150);//ltrim(rtrim($clientName));

/******************************* Set YQP_REINSURER_SIGNATURES to slip *******************************/
$reinsurer = array();
$reinsurer = $data["YQP_REINSURER_INFORMATION"];
//Set YQP_REINSURER_INFORMATION in variable return
$dataSlipInformation["YQP_REINSURER_INFORMATION"] = $data["YQP_REINSURER_INFORMATION"];
$reinsurerSlip = "<table style='width:100%;font-family: Corbel;font-size: 11pt;'>";
$noCode = "";
$noCodeValidate = "YES";
if ($data['YQP_SOURCE'] != 'Other') {
    if ($data['YQP_LANGUAGE'] == 'ES') {
        $reinsurerSlip .= "<tr><td style='width:10%;'></td>";
        $reinsurerSlip .= "<td style='width:25%;' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% por " . $data["YQP_COMPLETE_NAME_SOURCE"] . "</td></tr>";
    } else {
        $reinsurerSlip .= "<tr><td style='width:10%;'></td>";
        $reinsurerSlip .= "<td style='width:25%;' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% by " . $data["YQP_COMPLETE_NAME_SOURCE"] . "</td></tr>";
    }
    $reinsurerSlip .= "<tr><td colspan='3'></td></tr>";
}
for ($r = 0; $r < count($reinsurer); $r++) {
    $dataSend = array();
    //Set percentage and name of reinsurer
    if ($data['YQP_SOURCE'] == 'Other') {
        $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% </td>";
    } else {
        $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'>" . $reinsurer[$r]["YQP_SHARE_PERCENTAGE"] . "% </td>";
    }
    if ($data["YQP_COUNTRY_BUSINESS"] == "Mexico") {
        $dataSend['Reinsurer'] = $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"];

        $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetRegisterMexico";
        $aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
        $response = $aResponse["DATA"];
        $err = $aResponse["ERROR"];    
        //Check if there is a code
        if ($response) {
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
            $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'></td>";
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $response . "</td></tr>";
        } else  {
            $noCode .= "The " . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . " has no Register Code in Mexico<br/>"; 
            $noCodeValidate = "NO";
        }
    } else {
        $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
    }    
    //Add a space
    $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'></td>";
    $reinsurerSlip .= "<td style='width:65%;text-align:left;'></td></tr>";
    //Calculated Forte Order * Share
    $dataSlipInformation["YQP_REINSURER_INFORMATION"][$r]['YQP_FORTE_ORDER_SHARE'] = $reinsurer[$r]["YQP_FORTE_ORDER_GRID"] * ($reinsurer[$r]["YQP_SHARE_PERCENTAGE"] / 100);
}
$reinsurerSlip .= "</table>";
$dataSlipInformation["YQP_REINSURER_SIGNATURES"] = htmlentities($reinsurerSlip);

//Set Status
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $dataSlipInformation['YQP_STATUS'] = 'SLIP GENERATED';
} else {
    $dataSlipInformation['YQP_STATUS'] = 'PENDING';
}
return $dataSlipInformation;