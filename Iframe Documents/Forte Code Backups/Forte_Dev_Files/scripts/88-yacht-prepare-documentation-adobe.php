<?php
/****************************************************
 * Prepare Documents to Generate Forte Documentation
 *
 * by Ana Castillo
 * modified by Nestor Orihuela
 * modified by Helen Callisaya
 * modified by Ronald Nina
 * modified by Cinthia Romero
 *****************************************************/
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

$data['YQP_RISK_ATTACHING_MONTH'] = date('n');
$data['YQP_ERROR_SLIP'] = "";
$data["YQP_SLIP_GENERATED_TYPE"] = "CONFIRMATION_ADOBE";
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$collectionID = getenv('UTP_TEMPLATES_COLLECTION');
$templatesVariablesID = getenv('UTP_TEMPLATES_VARIABLES_COLLECTION');
$periodFrom = new DateTime($data['YQP_PERIOD_FROM']);
$periodTo  = new DateTime($data['YQP_PERIOD_TO']);
$dateDifference = $periodFrom->diff($periodTo);
$monthDiference = $dateDifference->m + ($dateDifference->y * 12);
$data['YQP_VALIDATE_PERIOD_MONTH'] = $monthDiference;
$data['YQP_TERM'] = $monthDiference;
$openLUrl = getenv('OPENL_CONNECTION');
$processID = $data['_request']['process_id'];
$typeDocument = 'FORTE_TYPE_THIRD_DOCUMENT';
$typeConditionDocument = 'FORTE_TYPE_THIRD_CONDITION';
$collectionTypeTemplate = getenv('FORTE_TEMPLATES_TYPE_COLLECTION');
if (!empty($data['YQP_UPLOAD_CONFIRMATION_SUBMIT'])) {
    $data['YQP_COMMENTS_APPROVE_CONFIRMATION_SLIP'] = "";
    $data['YQP_FLOW_QUOTE_SLIP'] = "";
    $data['YQP_UPLOAD_CONFIRMATION_SLIP'] = "";
    $data['YQP_UPLOAD_CONFIRMATION_SLIP_ADOBE'] = "";    
    $data['YQP_APPROVE_CONFIRMATION_UPLOAD_SLIP'] = "";  
}
$data['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = "";
$data['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = "";
/*
* Function that calls the OpenL
* @param (String) $url
* @param (Object) $dataSend
* @return (Array) $aDataResponse
* by Ana Castillo
*/
function callCurlOpenL($url, $dataSend)
{
    $curl = curl_init();
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
    $responseCurl = curl_exec($curl);
    $errorCurl = curl_error($curl);
    curl_close($curl);
    $aDataResponse = array();
    $aDataResponse["ERROR"] = $errorCurl;
    $aDataResponse["DATA"] = $responseCurl;
    return $aDataResponse;
}
/******************************* Set YQP_REINSURER_SIGNATURES to slip *******************************/
$reinsurer = array();
$reinsurer = $data["YQP_REINSURER_INFORMATION"];
//Set YQP_REINSURER_INFORMATION in variable return
$reinsurerSlip = "<table style='width:100%;font-family: Corbel;font-size: 11pt;'>";
$noCode = "";
$noCodeValidate = "YES";
if ($data['YQP_SIGN_FORTE_LOGOS'] != 'Empty') {
    if ($data['YQP_LANGUAGE'] == 'ES') {
        $reinsurerSlip .= "<tr><td style='width:10%;'></td>";
        $reinsurerSlip .= "<td style='width:25%; text-align:center' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% por " . $data['YQP_SIGN_FORTE_LOGOS'] . "</td></tr>";
    } else {
        $reinsurerSlip .= "<tr><td style='width:10%;'></td>";
        $reinsurerSlip .= "<td style='width:25%; text-align:center' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% by " . $data['YQP_SIGN_FORTE_LOGOS'] . "</td></tr>";
    }
    $reinsurerSlip .= "<tr><td colspan='3'></td></tr>";
}
$reinsurersTableEstarSeguros = "<table style='width:100%;font-family: Corbel;font-size: 11pt;'>";
for ($r = 0; $r < count($reinsurer); $r++) {
    $forteOrderShare = $reinsurer[$r]["YQP_FORTE_ORDER_GRID"] * ($reinsurer[$r]["YQP_SHARE_PERCENTAGE"] / 100);
    $data["YQP_REINSURER_INFORMATION"][$r]['YQP_FORTE_ORDER_SHARE'] = $forteOrderShare;
    if ($r != 0) {
        //Add a space among reinsurers
        $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'></td>";
        $reinsurerSlip .= "<td style='width:65%;text-align:left;'></td></tr>";
        $reinsurersTableEstarSeguros .= "<tr><td></td></tr>";
    }
    $dataSend = array();
    $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'>" . $forteOrderShare . "% </td>";
    //Only for Estar Seguros Venezuela
    if ($data['YQP_LANGUAGE'] == 'ES') {
        $reinsurersTableEstarSeguros .= "<tr><td style='text-align:center;'>" . $forteOrderShare . "% del 100% por " . strtoupper($reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"]) . "</td></tr>";
    } else {
        $reinsurersTableEstarSeguros .= "<tr><td style='text-align:center;'>" . $forteOrderShare . "% of 100% by " . strtoupper($reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"]) . "</td></tr>";
    }
    //
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
            $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='2'></td>";
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $response . "</td></tr>";
        } else  {
            $noCode .= "The " . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . " has no Register Code in Mexico<br/>"; 
            $noCodeValidate = "NO";
        }
    } else {
        $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
    }   
}
$reinsurerSlip .= "</table>";
$reinsurersTableEstarSeguros .= "</table>";
/*********************** Set Slip Reinsurer Panama *********************************/
$data["YQP_REINSURER_SIGNATURES"] = htmlentities($reinsurerSlip);
$data["YQP_SLIP_REINSURER_PANAMA"] = $data["YQP_REASSURED_CEDENT"]["LABEL"];
$data["YQP_SLIP_COUNTRY_PANAMA"] = $data["YQP_COUNTRY_BUSINESS"];
$data["YQP_REINSURER_SIGNATURES_ESTAR_SEGUROS"] = htmlentities($reinsurersTableEstarSeguros);
/*
 * Call Processmaker Api Files
 * @param (string) $idFile
 * @param (string) $nameFile
 * @return (string) $filePath
 * by Helen Callisaya
 */
function callGetCurlFiles($idFile, $nameFile)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => getenv('API_HOST') . '/files/' . $idFile . '/contents',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'accept: application/octet-stream',
            'Authorization: Bearer ' . getenv('API_TOKEN')
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $tempFolder = trim(sys_get_temp_dir() . PHP_EOL);
    $filePath = $tempFolder . DIRECTORY_SEPARATOR . $nameFile;
    $file = fopen($filePath, 'w');
    fwrite($file, $response);
    fclose($file);
    return $filePath;
}
/* 
 * Call Processmaker API
 * @param (string) $url
 * @return (Array) $responseCurl
 * by Helen Callisaya
 * modify Ronald Nina
 */
function callGetCurl($url)
{
    try {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . getenv('API_TOKEN'),
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $responseCurl = curl_exec($curl);
        if ($responseCurl === false) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }
        $responseCurl = json_decode($responseCurl, true);
        curl_close($curl);
        return $responseCurl;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}
/*
 * Order Response Collection data by orderName
 * @param (array) $responseCollection
 * @param (string) $orderNameVariable
 * @return (Array) $responseCollection
 * by Nestor Orihuela
 */
function orderResponseCollection($responseCollection, $orderNameVariable)
{
    $countVariableCollection = count($responseCollection['data']);
    for ($i = 0; $i < $countVariableCollection; $i++) {
        for ($j = 0; $j < $countVariableCollection - 1; $j++) {
            if ($responseCollection['data'][$j]['data'][$orderNameVariable] > $responseCollection['data'][$j + 1]['data'][$orderNameVariable]) {
                $temporal = $responseCollection['data'][$j];
                $responseCollection['data'][$j] = $responseCollection['data'][$j + 1];
                $responseCollection['data'][$j + 1] = $temporal;
            }
        }
    }
    return $responseCollection;
}
/*
 * Replace Variables in String
 * @param (string) $string
 * @param (string) $dataRequest
 * @return (string) $string
 * by Helen Callisaya
 * modified by Nestor Orihuela
 */
function replaceVariablesString($string, $dataRequest)
{
    $validateExistVariable = strpos($string, '}');
    if ($validateExistVariable) {
        //Get variables in an array
        $stringExplode = explode('}', $string);
        for ($m = 0; $m < count($stringExplode); $m++) {
            $variable = '${' . substr($stringExplode[$m], strpos($stringExplode[$m], '{') + 1) . '}';
            $valueVariable = $dataRequest[substr($stringExplode[$m], strpos($stringExplode[$m], '{') + 1)];
            //Replace in the text with the value of the variable
            $string = str_replace($variable, $valueVariable, $string);
        }
    }
    return $string;
}
/*
 * Replace Template variable to request Data format variable
 * @param (string) $rData
 * @return (string) $rData
 * by Elmer Orihuela
 */
function convertVariablesToData($rData)
{
    $rData = str_replace(".", "']['", $rData);
    return $rData;
}
/*
 * Change Date Format with correct language
 * @param (string) $date
 * @param (string) $language
 * @return (string) $date
 * by Elmer Orihuela
 */
function changeDateFormat($date, $language)
{
    switch ($language) {
        case "ES":
            $date = str_replace('January', 'enero', $date);
            $date = str_replace('February', 'febrero', $date);
            $date = str_replace('March', 'marzo', $date);
            $date = str_replace('April', 'abril', $date);
            $date = str_replace('May', 'mayo', $date);
            $date = str_replace('June', 'junio', $date);
            $date = str_replace('July', 'julio', $date);
            $date = str_replace('August', 'agosto', $date);
            $date = str_replace('September', 'septiembre', $date);
            $date = str_replace('October', 'octubre', $date);
            $date = str_replace('November', 'noviembre', $date);
            $date = str_replace('December', 'diciembre', $date);
            $date = str_replace('Monday', 'Lunes', $date);
            $date = str_replace('Tuesday', 'Martes', $date);
            $date = str_replace('Wednesday', 'Miercoles', $date);
            $date = str_replace('Thursday', 'Jueves', $date);
            $date = str_replace('Friday', 'Viernes', $date);
            $date = str_replace('Saturday', 'Sabado', $date);
            $date = str_replace('Sunday', 'Domingo', $date);
            $date = str_replace('st ', ' de ', $date);
            $date = str_replace('nd ', ' de ', $date);
            $date = str_replace('rd ', ' de ', $date);
            $date = str_replace('th ', ' de ', $date);
            $date = str_replace('of ', 'de ', $date);
            break;
        case "PT":
            $date = str_replace('January', 'Janeiro', $date);
            $date = str_replace('February', 'Fevereiro', $date);
            $date = str_replace('March', 'Março', $date);
            $date = str_replace('April', 'Abril', $date);
            $date = str_replace('May', 'Posso', $date);
            $date = str_replace('June', 'Junho', $date);
            $date = str_replace('July', 'Julho', $date);
            $date = str_replace('August', 'Agosto', $date);
            $date = str_replace('September', 'Setembro', $date);
            $date = str_replace('October', 'Outubro', $date);
            $date = str_replace('November', 'Novembro', $date);
            $date = str_replace('December', 'Dezembro', $date);
            $date = str_replace('Monday', 'Segunda-feira', $date);
            $date = str_replace('Tuesday', 'Terça-feira', $date);
            $date = str_replace('Wednesday', 'Quarta-feira', $date);
            $date = str_replace('Thursday', 'Quinta-feira', $date);
            $date = str_replace('Friday', 'Sexta-feira', $date);
            $date = str_replace('Saturday', 'Sábado', $date);
            $date = str_replace('Sunday', 'Domingo', $date);
            $date = str_replace('st ', ' de ', $date);
            $date = str_replace('nd ', ' de ', $date);
            $date = str_replace('rd ', ' de ', $date);
            $date = str_replace('th ', ' de ', $date);
            $date = str_replace('of ', 'de ', $date);
            break;
    }
    return $date;
}
/*
 * Get value of variable or text of conditions
 * @param (array) $dataValidation
 * @param (array) $dataRequest
 * @param (integer) $rowValidation
 * @param (string) $nameVarText
 * @param (string) $nameVariable
 * @param (string) $nameText
 * @return (string) $valueVariableText
 * by Helen Callisaya
 * modify by Elmer Orihuela
 */
function getValueVariableText($dataValidation,$dataRequest,$rowValidation,$nameVarText,$nameVariable,$nameText) {
    if ($dataValidation[$rowValidation][$nameVarText] == "VARIABLE") {
        $variableCondition1 = $dataValidation[$rowValidation][$nameVariable];
        $variableCondition1 = convertVariablesToData($variableCondition1);
        eval("\$value = \$dataRequest['" . $variableCondition1 . "'] ?? null;");
        if (is_null($value)) {
            $valueVariableText = 'null';
        } elseif ($value === '') {
            $valueVariableText = '';
        } else {
            $valueVariableText = $value;
        }
    } else {
        $valueTemp = stripslashes(str_replace('""', '', $dataValidation[$rowValidation][$nameText]));
        $valueVariableText = $valueTemp;
    }
    return $valueVariableText;
}
/*
 * Evaluates if it meets all the conditions and returns true or false
 * @param (array) $dataEvaluate
 * @param (array) $dataRequest
 * @param (integer) $conditionVarText
 * @param (string) $conditionVariable
 * @param (string) $conditionText
 * @param (string) $conditionSign
 * @param (string) $conditionOperator
 * @return (boolean) $resultCondition;
 * by Helen Callisaya
 */
function evaluateCondition($dataEvaluate,$dataRequest,$conditionVarText,$conditionVariable,$conditionText,$conditionSign,$conditionOperator)
{
    $conditions = "";
    for ($j = 0; $j < count($dataEvaluate); $j++) {
        $signCondition = $dataEvaluate[$j][$conditionSign];
        $operator = "";
        $valueVariableText1 = getValueVariableText($dataEvaluate, $dataRequest, $j, $conditionVarText . '1', $conditionVariable . '1', $conditionText . '1');
        $valueVariableText2 = getValueVariableText($dataEvaluate, $dataRequest, $j, $conditionVarText . '2', $conditionVariable . '2', $conditionText . '2');
        if (($j + 1) < count($dataEvaluate)) {
            $operator = $dataEvaluate[$j][$conditionOperator] . ' ';
        }
        $conditions .= "'" . $valueVariableText1 . "' " . $signCondition . " '" . $valueVariableText2 . "' " . $operator;
    }
    $evaluate = "\$resultCondition = $conditions;";
    try {
        $evaluate = @eval($evaluate);
        return $resultCondition;
    } catch (ParseError $e) {
        return false;
    }    
}
/*
 * Convert HTML to XML
 * @param (string) $variable
 * @param (array) $requestData
 * @return (string) $bodyBlock;
 * by Nestor Orihuela
 */
function htmlToXml($variable, $requestData)
{
    $string = convertVariablesToData($variable);
    eval("\$value = \$requestData['" . $string . "'];");
    if (!empty($value)) {
        $html = html_entity_decode($value);
        $phpWordHandle = new \PhpOffice\PhpWord\PhpWord();
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $section = $phpWordHandle->addSection();
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);
        $objWriter =  \PhpOffice\PhpWord\IOFactory::createWriter($phpWordHandle);
        $fullXml = $objWriter->getWriterPart('Document')->write();
        $dataLines = explode("\n", $fullXml);
        $newDataXML = "";
        foreach ($dataLines as $dataLine) {
            $newDataXML .= trim($dataLine);
        }
        if (preg_match('%(?i)(?<=<w:body>)[\s|\S]*?(?=</w:body>)%', $newDataXML, $regs)) {
            $bodyBlock = $regs[0];
        } else {
            $bodyBlock = '';
        }
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);
        return $bodyBlock;
    } else {
        return "";
    }
}
/*
 * Convert number to Roman Repesentation number
 * @param int $number
 * @return string
 * By https://stackoverflow.com/users/168960/alex
 */
function numberToRomanRepresentation($number)
{
    $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
    $returnValue = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if ($number >= $int) {
                $number -= $int;
                $returnValue .= $roman;
                break;
            }
        }
    }
    return $returnValue;
}
/*
* Validate multiple conditions for use Template and return true or false dependence of the conditions
* Verify variables exists in $data
* @param (array) $arrayCondition
* @param (array) $data
* @return boolean
* Ronald Nina - ronal.nina@processmaker.com
*/
function validateConditionTemplate($arrayCondition, $data)
{
    $dataReturn = false;
    $stringCondition = '';
    try {
        foreach ($arrayCondition as $condition) {
            if ($condition['UTP_VALIDATION_EVALUATE'] != "IN") {
                if (!empty($condition['UTP_VALIDATION_VARIABLE1'])) {
                    $variableTemp1 = "\$data['" . convertVariablesToData($condition['UTP_VALIDATION_VARIABLE1']) . "']";
                    eval("\$dataVariable1 = $variableTemp1; ");
                    if (!is_null($dataVariable1)) {
                        $stringCondition .= $variableTemp1;
                    } else {
                        throw new ParseError("Error Validation Variable 1");
                    }
                } elseif (!empty($condition['UTP_VALIDATION_TEXT1'])) {
                    $stringCondition .= "'" . stripslashes(str_replace('""', '', $condition['UTP_VALIDATION_TEXT1'])) . "'";
                }
                if (!empty($condition['UTP_VALIDATION_EVALUATE'])) {
                    $stringCondition .= " " . $condition['UTP_VALIDATION_EVALUATE'] . " ";
                }
                if (!empty($condition['UTP_VALIDATION_VARIABLE2'])) {
                    $variableTemp2 = "\$data['" . convertVariablesToData($condition['UTP_VALIDATION_VARIABLE2']) . "']";
                    eval("\$dataVariable2 = $variableTemp2; ");
                    if (!is_null($dataVariable2)) {
                        $stringCondition .= $variableTemp2;
                    } else {
                        throw new ParseError("Error Validation Variable2");
                    }
                } elseif (!empty($condition['UTP_VALIDATION_TEXT2'])) {
                    $stringCondition .= "'" . stripslashes(str_replace('""', '', $condition['UTP_VALIDATION_TEXT2'])) . "'";
                }
            } else {
                if (!empty($condition['UTP_VALIDATION_VARIABLE2'])) {
                    $variableTemp2 = "\$data['" . convertVariablesToData($condition['UTP_VALIDATION_VARIABLE2']) . "']";
                    eval("\$dataVariable2 = $variableTemp2; ");
                    if (!is_null($dataVariable2) && !is_array($dataVariable2)) {
                        $existsVariable2 = true;
                    } else {
                        throw new ParseError("Error Validation Variable2");
                    }
                    $variableTemp1 = "\$data['" . convertVariablesToData($condition['UTP_VALIDATION_VARIABLE1']) . "']";
                    eval("\$dataVariable1 = $variableTemp1; ");
                    if (!is_null($dataVariable1) && is_array($dataVariable1)) {
                        $existsVariable1 = true;
                    } else {
                        throw new ParseError("Error Validation Variable1");
                    }
                    if ($existsVariable1 && $existsVariable2) {
                        eval("\$dataVariable2 = $variableTemp2; ");
                        $stringCondition .= "(array_filter(" . $variableTemp1 . ", function (\$value) {
                                                    return strpos(\$value, \"" . $dataVariable2 . "\") !== false;
                                                }) ? true : false)";
                    }
                } elseif (!empty($condition['UTP_VALIDATION_TEXT2'])) {
                    $variableTemp1 = "\$data['" . convertVariablesToData($condition['UTP_VALIDATION_VARIABLE1']) . "']";
                    eval("\$dataVariable1 = $variableTemp1; ");
                    if (!is_null($dataVariable1) && is_array($dataVariable1)) {
                        $existsVariable1 = true;
                    } else {
                        throw new ParseError("Error Validation Variable1");
                    }
                    $variable2Validated = stripslashes(str_replace('""', '', $condition['UTP_VALIDATION_TEXT2']));
                    $stringCondition .= "(array_filter(" . $variableTemp1 . ", function (\$value) {
                                                return strpos(\$value, \"" . $variable2Validated . "\") !== false;
                                            }) ? true : false)";
                }
            }
            if (!empty($condition['UTP_VALIDATION_FINAL_EVALUATE'])) {
                $stringCondition .= " " . $condition['UTP_VALIDATION_FINAL_EVALUATE'] . " ";
            }
        }
        eval(" \$dataReturn = $stringCondition;");
    } catch (ParseError $e) {
        //return $e->getMessage();
    }
    return $dataReturn;
}
/*
 * Evaluate Sequence for concat and concat variables
 * Verify variables exists in $data
 * @param (string) $sequenceCountVariable
 * @param (int) $startSequence
 * @param (string) $sequence
 * @param (bool) $sequenceSpace
 * @param (string) $typeNumber
 * @param (array) $arrayVariable
 * @return (array) $arrayVariable
 * Ronald Nina - ronal.nina@processmaker.com
 * modified by Nestor Orihuela
 */
function addSequence($sequenceCountVariable = 1,$startSequence = null,$sequence = null,$sequenceSpace = null,$typeNumber = null,$arrayVariable) {
    if (!empty($startSequence)) {
        $sequenceCountVariable = $startSequence;
    }
    $symbol = '#';
    if (!empty($sequence)) {
        $symbol = $sequence;
    }
    if (!empty($sequenceSpace) && $sequenceSpace == true) {
        $symbol .= " ";
    } else {
        $symbol .= "    ";
    }
    $tempArrayVariable = [];
    foreach ($arrayVariable as $valueVariable) {
        if (!empty($typeNumber) && $typeNumber == 'roman') {
            $tempArrayVariable[] = str_replace("#", numberToRomanRepresentation($sequenceCountVariable), $symbol) . $valueVariable;
        } else {
            $tempArrayVariable[] = str_replace("#", $sequenceCountVariable, $symbol) . $valueVariable;
        }
        $sequenceCountVariable++;
    }
    $arrayVariable = $tempArrayVariable;
    return $arrayVariable;
}

/*
 * Concat Array with type sequence
 * @param (array) $arrayVariable
 * @param (bool $useSequence
 * @param (string) $typeSequence
 * @param (string) $sequence
 * @param (bool) $sequenceSpace
 * @param (string) $startSequence
 * @param (string) $concatDelimiter
 * @param (bool) $concatDelimiterSpace
 * @param (array) $concatvarLoop
 * @param (bool) $concatFirstDifferent
 * @param (string) $concatFirstRowDelimiter
 * @param (bool) $concatFirstRowSpace
 * @return (string) $dataReturn
 * by Ronald Nina - ronal.nina@processmaker.com
 * modify by Elmer Orihuela
 */
function concatArrayWithValues($arrayVariable,$useSequence,$typeSequence,$sequence,$sequenceSpace,$startSequence,$concatDelimiter,$concatDelimiterSpace,$concatvarLoop,$concatFirstDifferent,$concatFirstRowDelimiter,$concatFirstRowSpace) {
    $arrayExceptions = array_column($concatvarLoop, 'UTP_CONCATVAR_EXCEPTION_VALUE');
    if (is_array($arrayVariable)){
        $arrayVariable = array_diff($arrayVariable, $arrayExceptions);
    }
    if (!is_array($arrayVariable)) {
        $arrayVariable = [];
    }
    $dataReturn = '';
    if (!empty($useSequence) && $useSequence == true) {
        switch ($typeSequence) {
            case 'DOTS': {
                    $tempArrayVariable = [];
                    $symbolDot = "&#8226;     ";
                    foreach ($arrayVariable as $k => $valueVariable) {
                        if (strpos($valueVariable, $symbolDot) !== false) {
                            $tempArrayVariable[] = $valueVariable;
                        } else {
                            $tempArrayVariable[] = $symbolDot . $valueVariable;
                        }
                    }
                    $arrayVariable = $tempArrayVariable;
                }
                break;
            case 'DASH': {
                    $tempArrayVariable = [];
                    $symbolDash = "-     ";
                    foreach ($arrayVariable as $valueVariable) {
                        if (strpos($valueVariable, $symbolDash) !== false) {
                            $tempArrayVariable[] = $valueVariable;
                        } else {
                            $tempArrayVariable[] = $symbolDash . $valueVariable;
                        }
                    }
                    $arrayVariable = $tempArrayVariable;
                }
                break;
            case 'NUMBERS': {
                    $arrayVariable = addSequence(1, (int)$startSequence, $sequence, $sequenceSpace, null, $arrayVariable);
                }
                break;
            case 'ROMAN_NUMBERS': {
                    $arrayVariable = addSequence(1, (int)$startSequence, $sequence, $sequenceSpace, 'roman', $arrayVariable);
                }
                break;
            case 'CAP_LETTERS': {
                    $arrayVariable = addSequence('A', mb_strtoupper($startSequence), $sequence, $sequenceSpace, null, $arrayVariable);
                }
                break;
            case 'LOW_LETTERS': {
                    $arrayVariable = addSequence('a', mb_strtolower($startSequence), $sequence, $sequenceSpace, null, $arrayVariable);
                }
                break;
        }
        if (!empty($concatDelimiterSpace) && $concatDelimiterSpace == true) {
            $concatDelimiter .= ' ';
        }
        $dataReturn = implode($concatDelimiter, $arrayVariable);
    } else {
        if (!empty($concatDelimiterSpace) && $concatDelimiterSpace == true) {
            $concatDelimiter .= ' ';
        }
        $dataReturn = implode($concatDelimiter, $arrayVariable);
    }
    if (!empty($concatFirstRowDelimiter) && $concatFirstRowDelimiter === true) {
        $dataReturn = '' . $dataReturn;
    }
    if (!empty($concatFirstDifferent) && $concatFirstDifferent === true) {
        if (!empty($concatFirstRowSpace) && $concatFirstRowSpace === true) {
            $concatFirstRowDelimiter = $concatFirstRowDelimiter . " ";
        }
        $dataReturn = $concatFirstRowDelimiter . $dataReturn;
    }
    return $dataReturn;
}
/*
* Change the Format of the variable with the Template Variable Collection
* Only created the function for use two times 
* By Ronald Nina
*/
function changeFormatTemplateVariable(&$data,$templateVariableCollection) {
    $varCollection = $templateVariableCollection['data']['UTP_VARIABLE_NAME'];
    switch ($templateVariableCollection['data']['UTP_CODE']) {
        case "SIGN_RECORD_LIST":
            $variableArrayComplete = $templateVariableCollection['data']['UTP_VARIABLE_RECORD_LIST'];
            $variableArrayExplode = explode(".", $variableArrayComplete);
            $variableArray = $variableArrayExplode[0];
            for ($numberRowSign = 0; $numberRowSign <= count($data[$variableArray]) - 1; $numberRowSign++) {
                $variableArrayChangeRow = str_replace("#", $numberRowSign, $variableArrayComplete);
                $variableSignConvert = convertVariablesToData($variableArrayChangeRow);
                eval("\$valueSignVariable = \$data['" . $variableSignConvert . "'];");
                $dataSign[$numberRowSign][$templateVariableCollection['data']['UTP_VARIABLE_NAME']] = $valueSignVariable;
            }
            break;
        case "CHANGE_CURRENCY":
            $valueCurrency = "";
            $dataInfo = convertVariablesToData($templateVariableCollection['data']['UTP_VARIABLE_CURRENCY']);
            eval("\$valueCurrency = \$data['" . $dataInfo . "'];");
            if (is_numeric($valueCurrency)) {
                $valueCurrency = number_format($valueCurrency, 2, ".", ",");
            }
            $data[$varCollection] = $valueCurrency;
            break;
        case "CURRENT_DATE":
            $data[$varCollection] = date($templateVariableCollection['data']['UTP_FORMAT_DATE']);
            $data[$varCollection] = changeDateFormat($data[$varCollection], $data['YQP_LANGUAGE']);
            break;
        case "CHANGE_DATE":
            $dataInfo = convertVariablesToData($templateVariableCollection['data']['UTP_VARIABLE_DATE']);
            eval("\$anCollection = \$data['" . $dataInfo . "'];");
            $anCollection = new DateTime($anCollection);
            $data[$varCollection] = $anCollection->format($templateVariableCollection['data']['UTP_FORMAT_DATE']);
            $data[$varCollection] = changeDateFormat($data[$varCollection], $data['YQP_LANGUAGE']);
            break;
        case "MATH":
            if ($templateVariableCollection['data']['UTP_MATH_FUNCTION']) {
                $mathOperation = "";
                foreach ($templateVariableCollection['data']['UTP_MATH_FUNCTION'] as $varCol) {
                    if (isset($varCol['UTP_MAF_OPERATION']) && !empty($varCol['UTP_MAF_OPERATION'])) {
                        if (!empty($varCol['UTP_MAF_VARIABLE'])) {
                            $dataInfo = convertVariablesToData($varCol['UTP_MAF_VARIABLE']);
                            eval("\$result = \$data['" . $dataInfo . "'];");
                            $mathOperation .= $varCol['UTP_MAF_OPERATION'] . $result;
                        } elseif (!empty($varCol['UTP_MAF_NUMBER'])) {
                            $mathOperation .= $varCol['UTP_MAF_OPERATION'] . $varCol['UTP_MAF_NUMBER'];
                        }
                    } else {
                        $data[$varCollection] = "";
                    }
                }
                eval("\$math = " . $mathOperation . ";");
                $data[$varCollection] = $math;
            }
            break;
        case "HTML":
            $data[$varCollection] = htmlToXml($templateVariableCollection['data']['UTP_TABLE_VARIABLE_NAME'], $data);
            break;
        case "CONVERT_STRING":
            $dataInfo = convertVariablesToData($templateVariableCollection['data']['UTP_STRING_VARIABLE']);
            eval("\$result = \$data['" . $dataInfo . "'];");
            switch ($templateVariableCollection['data']['UTP_STRING_VARIABLE_SELECT']) {
                case "UPPER":
                    $result = mb_strtoupper($result);
                    break;
                case "LOWER":
                    $result = mb_strtolower($result);
                    break;
                case "SENTENCE":
                    $result = ucfirst($result);
                    break;
            }
            $data[$varCollection] = htmlspecialchars($result);
            break;
        case "CONCAT_VARIABLE":
            $dataInfo = convertVariablesToData($templateVariableCollection['data']['UTP_CONCATVAR_VARIABLE']);
            $arrayVariable = "\$data['" . $dataInfo . "']";
            eval("\$arrayVariable = $arrayVariable;");
            $data[$varCollection] = concatArrayWithValues(
                $arrayVariable,
                $templateVariableCollection['data']['UTP_USE_SEQUENCE'],
                $templateVariableCollection['data']['UTP_TYPE_SEQUENCE'],
                $templateVariableCollection['data']['UTP_SEQUENCE'],
                $templateVariableCollection['data']['UTP_SEQUENCE_SPACE'],
                $templateVariableCollection['data']['UTP_START_SEQUENCE'],
                $templateVariableCollection['data']['UTP_CONCAT_DELIMITER'],
                $templateVariableCollection['data']['UTP_CONCAT_DELIMITER_SPACE'],
                $templateVariableCollection['data']['UTP_CONCATVAR_LOOP'],
                $templateVariableCollection['data']['UTP_CONCAT_FIRST_DIFFERENT'],
                $templateVariableCollection['data']['UTP_CONCAT_FIRST_ROW_DELIMITER'],
                $templateVariableCollection['data']['UTP_CONCAT_FIRST_ROW_SPACE']
            );
            break;
        case "CONCAT":
            $arrayValues = [];
            foreach ($templateVariableCollection['data']['UTP_CONCAT_LOOP'] as $loop) {
                $concatVariableInArray = false;
                if (!empty($loop['UTP_CONCAT_VALIDATION'])) {
                    $concatVariableInArray = evaluateCondition($loop['UTP_CONCAT_VALIDATION'], $data, 'UTP_COR_VARTEXT', 'UTP_COR_VARIABLE', 'UTP_COR_TEXT', 'UTP_COR_EVALUATE', 'UTP_COR_FINAL_EVALUATE');
                } else {
                    $concatVariableInArray = true;
                }
                if ($concatVariableInArray) {
                    if (!empty($loop['UTP_CONCAT_VARIABLE'])) {
                        try {
                            $variableTemp1 = "\$data['" . convertVariablesToData($loop['UTP_CONCAT_VARIABLE']) . "']";
                            eval("\$dataVariable1 = ($variableTemp1) ?? null; ");
                            if (!is_null($dataVariable1) && !empty($dataVariable1)) {
                                $arrayValues[] = $dataVariable1;
                            }
                        } catch (ParseError $e) {
                        }
                    } else {
                        $dataText = stripslashes(str_replace('""', '', $loop['UTP_CONCAT_TEXT']));
                        if (!empty($dataText)) {
                            $arrayValues[] = $dataText;
                        }
                    }
                }
            }
            $tempArray = concatArrayWithValues(
                $arrayValues,
                $templateVariableCollection['data']['UTP_USE_SEQUENCE'],
                $templateVariableCollection['data']['UTP_TYPE_SEQUENCE'],
                $templateVariableCollection['data']['UTP_SEQUENCE'],
                $templateVariableCollection['data']['UTP_SEQUENCE_SPACE'],
                $templateVariableCollection['data']['UTP_START_SEQUENCE'],
                $templateVariableCollection['data']['UTP_CONCAT_DELIMITER'],
                $templateVariableCollection['data']['UTP_CONCAT_DELIMITER_SPACE'],
                $templateVariableCollection['data']['UTP_CONCATVAR_LOOP'],
                $templateVariableCollection['data']['UTP_CONCAT_FIRST_DIFFERENT'],
                $templateVariableCollection['data']['UTP_CONCAT_FIRST_ROW_DELIMITER'],
                $templateVariableCollection['data']['UTP_CONCAT_FIRST_ROW_SPACE']
            );
            $data[$varCollection] = $tempArray;
            break;
        case "SLIP_LOGO":
                $arrayImagesTrue = [];
                foreach ($templateVariableCollection['data']['UTP_LOGO'] as $slipLogo) {
                    $resultCondition = false;
                    if (!empty($slipLogo['UTP_LOGO_VALIDATION'])) {
                        $resultCondition = evaluateCondition(
                            $slipLogo['UTP_LOGO_VALIDATION'],
                            $data,
                            'UTP_COR_VARTEXT',
                            'UTP_COR_VARIABLE',
                            'UTP_COR_TEXT',
                            'UTP_COR_EVALUATE',
                            'UTP_COR_FINAL_EVALUATE'
                        );
                    } else {
                        $resultCondition = true;
                    }
                    if ($resultCondition == true && !empty($slipLogo['UTP_LOGO_UPLOAD'])) {
                        $logoId = $slipLogo['UTP_LOGO_UPLOAD']['id'];
                        $logoName = $slipLogo['UTP_LOGO_UPLOAD']['name'];
                        $fileLogo = callGetCurlFiles($logoId, $logoName);
                        $arrayImagesTrue[] = [
                            'type' => 'image',
                            'path' => $fileLogo
                        ];
                    }
                }
                if (count($arrayImagesTrue) > 0) {
                    $data[$varCollection] = end($arrayImagesTrue);
                } else {
                    $data[$varCollection] = "";
                }
            break;
    }
}

//-------------------------------------------------------------------------------------
//Convert Subjectives to HTML for Slip and fix it if it is corrupted
$cleanedSubjectives = cleanTextAreaRichTextValue($data["YQP_SUBJECTIVES_GUARANTEE"], "YES");
$data['YQP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["ORIGINAL_VALUE_CLEANED"];
$data['YQP_SLIP_SUBJECTIVES_GUARANTEE'] = $cleanedSubjectives["SLIP_VALUE_PARSED"];
//Encode Subjectives Email
$data['YQP_SUBJECTIVES_GUARANTEE_EMAIL'] = htmlentities($data['YQP_SUBJECTIVES_GUARANTEE']);
//Convert Additional Information to HTML for Slip and fix it if it is corrupted
if (empty($data["YQP_CLAIM_ADDITIONAL_INFORMATION"])) {
    $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
    $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = "";
} else {
    $cleanedAdditionalInfo = cleanTextAreaRichTextValue($data["YQP_CLAIM_ADDITIONAL_INFORMATION"], "YES");
    $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["ORIGINAL_VALUE_CLEANED"];
    $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["SLIP_VALUE_PARSED"];
}
//-------------------------------- Get Document Types ----------------------------------
try {
    $urlPmqlType = urlencode('(data.FORTE_TYPE_PROCESS = "' . $processID . '" and data.FORTE_TYPE_STATUS = "ACTIVE" and data.' . $typeDocument . ' = "true")');
    $documentTypeUrl = $apiHost . '/collections/' . $collectionTypeTemplate . '/records?include=data&pmql=' . $urlPmqlType;
    $documentType = callGetCurl($documentTypeUrl);
} catch (Exception $e) {
    $data['YQP_ERROR_SLIP'] = "ERROR";
    $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
    $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
    $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
    $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Generate Slip";
    return $data;
}
if (count($documentType['data']) > 0) {
    $documentTypeArray = [];
    for ($i = 0; $i < count($documentType['data']); $i++) {
        if (in_array($documentType['data'][$i]['data']['FORTE_TYPE_ID'], $documentTypeArray, TRUE) == false) {
            if (!empty($documentType['data'][$i]['data'][$typeConditionDocument])) {
                $evalValidationType = evaluateCondition($documentType['data'][$i]['data'][$typeConditionDocument], $data, 'FORTE_TYPE_DC_VARTEXT', 'FORTE_TYPE_DC_VARIABLE', 'FORTE_TYPE_DC_TEXT', 'FORTE_TYPE_DC_EVALUATE', 'FORTE_TYPE_DC_FINAL_EVALUATE');
            } else {
                $evalValidationType = true;
            }
            if ($evalValidationType) {
                $documentTypeArray[] = $documentType['data'][$i]['data']['FORTE_TYPE_ID'];
            }
        }
    }
}
//----------------------------------- GENERATE DOCUMENTS -----------------------------------------------
//Generate documents type by type defined in the collection
foreach ($documentTypeArray as $documentGenerate) {
    //-------------------------------- Get Document Templates ----------------------------------  
    try {
        $urlPmqlTemplates = '(data.UTP_TE_PROCESS_ID = "' . $processID . '" and data.UTP_TE_TYPE = "' . $documentGenerate . '" and data.UTP_TE_LANGUAGE = "' . $data['YQP_LANGUAGE'] . '"';
        //Validate if it is slip to add base text on the filter
        if ($documentGenerate == 'SLIP') {
            $urlPmqlTemplates .= ' and data.UTP_TE_BASE_TEXT = "' . $data['YQP_BASE_TEXT'] . '"';
        }
        $urlPmqlTemplates .=  ')';
        $urlPmqlTemplates = urlencode($urlPmqlTemplates);
        $urlSlipTemplateCollection = $apiHost . '/collections/' . $collectionID . '/records' . '?include=data&pmql=' . $urlPmqlTemplates;
        $templateCollection = callGetCurl($urlSlipTemplateCollection);
    } catch (Exception $e) {            
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Generate Slip";            
        return $data;
    }
    if (count($templateCollection['data']) > 0) {
        $templateCollection = orderResponseCollection($templateCollection, 'UTP_TE_ORDER');
    }
    //-------------------------------- Get Document Template Variables ----------------------------------
    try {
        $urlPmqlVariables = '(data.UTP_PROCESS_ID = "' . $processID . '" and data.UTP_TYPE = "' . $documentGenerate . '" and data.UTP_LANGUAGE = "' . $data['YQP_LANGUAGE'] . '")';
        $urlTemplateVariablesCollection = $apiHost . '/collections/' . $templatesVariablesID . '/records' . '?include=data&pmql=' . urlencode($urlPmqlVariables);
        $templateVariablesCollection = callGetCurl($urlTemplateVariablesCollection);
    } catch (Exception $e) {
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Generate Slip";              
        return $data;
    }
    if (count($templateVariablesCollection['data']) > 0) {
        $templateVariablesCollection = orderResponseCollection($templateVariablesCollection, 'UTP_ORDER_EXECUTION');
    }
    if (count($templateCollection['data']) > 0) {
        // Get documents Names and Templates
        $inFile = [];
        $dataVariableTemplateLoop = [];
        foreach ($templateCollection['data'] as $template) {
            //Generate Template Variable and save on $data
            $templateNameGenerated = replaceVariablesString($template['data']['UTP_TE_DOCUMENT_NAME_FORMAT'], $data);
            $data["YQP_" . $documentGenerate . "_DOCUMENT_NAME"] = $templateNameGenerated;
            //Validate Template
            if (empty($template['data']['UTP_TE_VALIDATION'])) {
                $flagValidationTemplate = true;
            } else {
                $flagValidationTemplate = validateConditionTemplate($template['data']['UTP_TE_VALIDATION'], $data);
            }
            //Load temporal template on an array file
            if ($flagValidationTemplate) {
                $apiInstance = $api->requestFiles();
                try {
                    //Added for working with loop templates - Loop
                    if (isset($template['data']['UTP_TE_REPEAT_DOCUMENT']) && $template['data']['UTP_TE_REPEAT_DOCUMENT']) {
                        // Verify if the existing value in the field for the loop
                        if (isset($template['data']['UTP_TE_VARIABLE_TO_LOOP']) && !empty($template['data']['UTP_TE_VARIABLE_TO_LOOP'])) {
                            $variableToLoopCollection = $template['data']['UTP_TE_VARIABLE_TO_LOOP'];
                            $variableToLoop = explode(".#", $variableToLoopCollection);
                            eval("\$valueVariableToLoop = \$data['" . convertVariablesToData($variableToLoop[0]) . "'];");
                            // Extract other variables for condition loop
                            $variableLoopResponse = $template['data']['UTP_TE_VARIABLE_TO_SAVE_REPONSE'] ?? null;
                            $useWithOpenL = $template['data']['UTP_TE_USE_OPENL'] ?? false;
                            $useWithOpenLUrlCurl = $template['data']['UTP_TE_OPENL_URL'];
                            $file = $apiInstance->getRequestFilesById($template['data']['UTP_TE_REQUEST_ID'], $template['data']['UTP_TE_DOCUMENT_ID']);
                            if (!is_null($valueVariableToLoop) && !is_null($variableLoopResponse)) {
                                $adobeSignerTotal = 0; //Total of signer of all reinsurers
                                for ($l = 0; $l <= count($valueVariableToLoop) - 1; $l++) {
                                    $variableToLoopCollectionWithReplace= str_replace("#", $l, $variableToLoopCollection);
                                    $variableLoopConvert = convertVariablesToData($variableToLoopCollectionWithReplace);
                                    eval("\$valueVariableLoopConvert = \$data['" . $variableLoopConvert . "'];");
                                    // Call OpenL url with data
                                    if ($useWithOpenL) {
                                        if (!empty($useWithOpenLUrlCurl)) {
                                            $dataSend['Reinsurer'] = $valueVariableLoopConvert;
                                            $aResponse = callCurlOpenL($useWithOpenLUrlCurl, $dataSend);
                                            $response = $aResponse["DATA"];
                                            $err = $aResponse["ERROR"];  
                                            if ($response) {
                                                //Code added to change adobe signers tags with multiple reinsurers
                                                $signaturesArray = explode(":", $response);
                                                $adobeSignersArray = array();
                                                foreach ($signaturesArray as $signatureAdobe) {
                                                    if (strpos($signatureAdobe, "signer") !== false) {
                                                        if (!in_array($signatureAdobe, $adobeSignersArray)) {
                                                            $adobeSignersArray[] = $signatureAdobe;
                                                        }
                                                    }
                                                }
                                                if ($l != 0) {
                                                    foreach ($adobeSignersArray as $adobeSigner) {
                                                        $adobeSignerOriginalIndex = str_replace("signer", "", $adobeSigner);
                                                        $adobeSignerNewIndex = $adobeSignerOriginalIndex + $adobeSignerTotal;
                                                        $adobeSignerNewIndex = "signer" . $adobeSignerNewIndex;
                                                        $response = str_replace($adobeSigner, $adobeSignerNewIndex, $response);
                                                    }
                                                }
                                                $adobeSignerTotal = $adobeSignerTotal + count($adobeSignersArray);
                                                $valueVariableLoopConvert = $response;
                                            } else {
                                                $valueVariableLoopConvert = "";
                                            }
                                        }
                                    }
                                    // Create different template files with the loop
                                    $filePathNameTmp = $file->getPathname();
                                    $newFilePathNameTmp = $filePathNameTmp . $l;
                                    copy($filePathNameTmp, $newFilePathNameTmp);
                                    $filePathNameTmp = $newFilePathNameTmp;
                                    $inFile[] = $filePathNameTmp;
                                    $dataVariableTemplateLoop[$filePathNameTmp][$l] = [
                                        $variableLoopResponse => $valueVariableLoopConvert,
                                        "YQP_REINSURER_NAME_SIGN" => $data["YQP_REINSURER_INFORMATION"][$l]["YQP_REINSURER_NAME"]["LABEL"],
                                        "YQP_FORTE_ORDER_SHARE_SIGN" => $data["YQP_REINSURER_INFORMATION"][$l]["YQP_FORTE_ORDER_SHARE"],
                                        "YQP_REINSURER_SHARE_PERCENTAGE_SLIP" => $data["YQP_REINSURER_INFORMATION"][$l]["YQP_SHARE_PERCENTAGE"]
                                    ];
                                }
                            }
                           
                        } // End - Loop                      
                    } else {
                        $file = $apiInstance->getRequestFilesById($template['data']['UTP_TE_REQUEST_ID'], $template['data']['UTP_TE_DOCUMENT_ID']);
                        $inFile[] = $file->getPathname();
                    }
                } catch (Exception $e) {
                    $data['YQP_ERROR_SLIP'] = "ERROR";
                    $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
                    $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
                    $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
                    $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
                    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
                    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Generate Slip";                          
                    return $data;
                }                 
            }
        }
        // Get Variables and values from Collection
        $dataSign = [[]];
        foreach ($templateVariablesCollection['data'] as $templateVariableCollection) {
            changeFormatTemplateVariable($data, $templateVariableCollection);
        }
        // Check if it is not type SIGN, set a return to generate the documents
        if ($documentGenerate != "SIGN") {
            $dataSign = [[]]; //Only one each
        }
        // Copy paths in temporal paths 
        foreach ($inFile as $outFile) {
            copy($outFile, $outFile."_copy");
        }
        foreach($dataSign as $signKeyRow => $signRow) {
            $signKeyRow++;
            // Verify and extract variable from the array, for set variables in $data, 
            if ($documentGenerate == "SIGN") {
                foreach ($signRow as $signVariable => $signValue) {
                    $data[$signVariable] = $signValue;
                }
                // Set path original with temporal paths 
                foreach ($inFile as $outFile) {
                    copy($outFile."_copy", $outFile);
                }
            }
            //-----------------------------------------Template Processor Set Values ------------------------------
            if (count($inFile) > 0) {
                $keysTemplatesLoop = array_keys($dataVariableTemplateLoop); // Created array with keys the array for the templates in loop - Loop
                $l = 0; // Counter for loop  - Loop
                foreach ($inFile as $outFile) {
                    // Begin Working for the loop - Loop
                    if (in_array($outFile, $keysTemplatesLoop)) { // Verified if the template is part of Loop Template
                        if(!is_null($dataVariableTemplateLoop[$outFile][$l])) {
                            $keysLoop = array_keys($dataVariableTemplateLoop[$outFile][$l]);
                            foreach ($keysLoop as $key) {
                                // Added the value for the template in $data
                                $data[$key] = $dataVariableTemplateLoop[$outFile][$l][$key];
                                foreach ($templateVariablesCollection['data'] as $templateVariableCollection) {
                                    if ($templateVariableCollection['data']['UTP_TABLE_VARIABLE_NAME'] == $key || $templateVariableCollection['data']['UTP_STRING_VARIABLE'] == $key) {
                                        changeFormatTemplateVariable($data, $templateVariableCollection); // Changed Format the variable from UTP_TEMPLATE_VARIABLES config
                                    }
                                }
                            }
                        }
                        $l++;
                    } else {
                        $l = 0;
                    } // End Working for the loop   - Loop
                    \PhpOffice\PhpWord\Settings::setCompatibility(false);
                    \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(false);
                    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($outFile);
                    $templateVariables = $templateProcessor->getVariables();
                    if (count($templateVariables) > 0) {
                        foreach ($templateVariables as $templateVariable) {
                            //Obtain template value from data Variable
                            $dataVariable = convertVariablesToData($templateVariable);
                            eval("\$value = \$data['" . $dataVariable . "'];");
                            if (!empty($value) || $value === 0) {
                                if (is_array($value)) {
                                    //In case value is an Image insert with correct function
                                    if ($value['type'] == "image") {
                                        if (file_exists($value['path'])) {
                                            $templateProcessor->setImageValue($templateVariable, $value['path']);
                                        } else {
                                            $data['YQP_ERROR_SLIP'] = "ERROR";
                                            $data['YQP_ERROR_MESSAGE_SLIP'] = "Template Variable " . $templateVariable . " not found insided collection for DOCUMENT TYPE  " . $documentGenerate . " document Nro " . $countTest;
                                            $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
                                            $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
                                            $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
                                            $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
                                            $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Generate Slip";                                             
                                        }
                                    }
                                } else {
                                    //set value on templateVariable replacing html special chars
                                    $value = htmlspecialchars_decode($value);
                                    $value = str_replace("&", "&amp;", $value);
                                    $value = str_replace("&amp;#8226;", "&#8226;", $value);
                                    $templateProcessor->setValue($templateVariable, $value);
                                }
                            } else {
                                $templateProcessor->setValue($templateVariable, "");
                            }
                        }
                        //Save Slip file
                        ob_clean();
                        $templateProcessor->saveAs($outFile);
                    }
                }
                //--------------------------------- Merge Documents ------------------------------------------------------
                $dm = new DocxMerge\DocxMerge();
                $nameDocument = $data["YQP_" . $documentGenerate . "_DOCUMENT_NAME"];
                if ($documentGenerate == "SIGN") {
                    $nameDocument = $nameDocument . "_" . $signKeyRow;
                } 
                $dm->merge($inFile, "/tmp/" . $nameDocument . ".docx");
                //Save document merged with name Document
                $processRequestIdForte = $data['_request']['id'];
                $outFile = "/tmp/" . $nameDocument . ".docx";
                $dataName = 'YQP_DOWNLOAD_' . $documentGenerate . '_DOCUMENT';
                if ($documentGenerate == "SIGN") {
                    if ($signKeyRow == 1) {
                        $nameDocumentSign = $data["YQP_" . $documentGenerate . "_DOCUMENT_NAME"];
                        $dm->merge($inFile, "/tmp/" . $nameDocumentSign . ".docx");
                        $outFileSign = "/tmp/" . $nameDocumentSign . ".docx";  
                        $dataNameSign = 'YQP_DOWNLOAD_' . $documentGenerate . '_DOCUMENT';
                        $newFile = $apiInstance->createRequestFile($processRequestIdForte, $dataNameSign, $outFileSign);        
                    }
                    $dataName = 'YQP_DOWNLOAD_' . $documentGenerate . '_DOCUMENT' . $signKeyRow;
                }
                $newFile = $apiInstance->createRequestFile($processRequestIdForte, $dataName, $outFile);
                //--------------------------------- Create Download Files Button -----------------------------------------
                $slipFiles = $apiHost . '/requests/' . $data['_request']['id'] . '/files';
                $responseRequestFiles = callGetCurl($slipFiles);
                foreach ($responseRequestFiles['data'] as $responseRequestFile) {
                    $variableName = $responseRequestFile['custom_properties']['data_name'];
                    $fileName = $responseRequestFile['file_name'];
                    $data[$variableName . '_URL_ADOBE'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $responseRequestFile['id'];
                    $data[$variableName . '_URL_NAME_BUTTON_ADOBE'] = $fileName;
                }
                $data['YQP_ERROR_SLIP'] = "SUCCESS";
            } else {
                $data['YQP_ERROR_SLIP'] = "ERROR";
                $data['YQP_ERROR_MESSAGE_SLIP'] = "There are no templates for this data";
                $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
                $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
                $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
                $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_122";
                $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Prepare Documentation";
            }
        }
    } else {
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = "There are no templates for this data";
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_122";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Prepare Documentation";
    }
}
return $data;