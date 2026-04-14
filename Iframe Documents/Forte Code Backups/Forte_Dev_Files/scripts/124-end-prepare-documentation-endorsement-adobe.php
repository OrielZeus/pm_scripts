<?php
/*
 * Prepare Documents to Generate Forte Documentation
 * by Ana Castillo
 * modified by Nestor Orihuela
 * modified by Helen Callisaya
 * modified by Ronald Nina
 * modified by Cinthia Romero
 */
//set initial message
$data['YQP_ERROR_SLIP'] = "";
//Set variable generated Slip
$data["END_DOCUMENT_GENERATED_TYPE"] = "BOUND_ADOBE";
//GET API TOKEN
$apiToken = getenv('API_TOKEN');
//GET HOST/api/1.0
$apiHost = getenv('API_HOST');
//get env Collection_ID
$collectionID = getenv('UTP_TEMPLATES_COLLECTION');
//Global enviroment Templates Variables ID
$templatesVariablesID = getenv('UTP_TEMPLATES_VARIABLES_COLLECTION');
//Clean Variables Quote
$data['END_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = "";
$data['END_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = "";
//Get URL Connection of OpenL
$openLUrl = getenv('OPENL_CONNECTION');
//Set process ID
$processID = $data['_request']['process_id'];
//Set first, second or third document to get type of documents
$typeDocument = 'FORTE_TYPE_THIRD_DOCUMENT';
$typeConditionDocument = 'FORTE_TYPE_THIRD_CONDITION';
//Set Collection of Type of Documents in Templates process
$collectionTypeTemplate = getenv('FORTE_TEMPLATES_TYPE_COLLECTION');

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
/*
 * Call Processmaker Api Files
 *
 * @param (string) $idFile
 * @param (string) $nameFile
 * @return (string) $filePath
 *
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
    //Create file in temporary folder
    $tempFolder = trim(sys_get_temp_dir() . PHP_EOL);
    $filePath = $tempFolder . DIRECTORY_SEPARATOR . $nameFile;
    $file = fopen($filePath, 'w');
    fwrite($file, $response);
    fclose($file);
    return $filePath;
}
/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
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
 *
 * @param (array) $responseCollection
 * @param (string) $orderNameVariable
 * @return (Array) $responseCollection
 *
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
 *
 * @param (string) $string
 * @param (string) $dataRequest
 * @return (string) $string
 *
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
 *
 * @param (string) $rData
 * @return (string) $rData
 *
 * by Elmer Orihuela
 */
function convertVariablesToData($rData)
{
    $rData = str_replace(".", "']['", $rData);
    return $rData;
}
/*
 * Change Date Format with correct language
 *
 * @param (string) $date
 * @param (string) $language
 * @return (string) $date
 *
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
 *
 * @param (array) $dataValidation
 * @param (array) $dataRequest
 * @param (integer) $rowValidation
 * @param (string) $nameVarText
 * @param (string) $nameVariable
 * @param (string) $nameText
 * @return (string) $valueVariableText
 *
 * by Helen Callisaya
 * modify by Elmer Orihuela
 */
function getValueVariableText($dataValidation, $dataRequest, $rowValidation, $nameVarText, $nameVariable, $nameText)
{
    //Validates if the value to compare is variable or text
    if ($dataValidation[$rowValidation][$nameVarText] == "VARIABLE") {
        //Get Variable Name to validate
        $variableCondition1 = $dataValidation[$rowValidation][$nameVariable];
        $variableCondition1 = convertVariablesToData($variableCondition1);
        eval("\$value = \$dataRequest['" . $variableCondition1 . "'] ?? null;");
        //return value evaluation
        if (is_null($value)) {
            $valueVariableText = 'null';
        } elseif ($value === '') {
            //When the variable does not exist we set it to 0
            $valueVariableText = '';
        } else {
            $valueVariableText = $value;
        }
    } else {
        //return name text
        $valueTemp = stripslashes(str_replace('""', '', $dataValidation[$rowValidation][$nameText]));
        $valueVariableText = $valueTemp;
    }
    return $valueVariableText;
}
/*
 * Evaluates if it meets all the conditions and returns true or false
 *
 * @param (array) $dataEvaluate
 * @param (array) $dataRequest
 * @param (integer) $conditionVarText
 * @param (string) $conditionVariable
 * @param (string) $conditionText
 * @param (string) $conditionSign
 * @param (string) $conditionOperator
 * @return (boolean) $resultCondition;
 *
 * by Helen Callisaya
 */
function evaluateCondition($dataEvaluate, $dataRequest, $conditionVarText, $conditionVariable, $conditionText, $conditionSign, $conditionOperator)
{
    $conditions = "";
    for ($j = 0; $j < count($dataEvaluate); $j++) {
        //Get condition operator
        $signCondition = $dataEvaluate[$j][$conditionSign];
        $operator = "";
        //Get value of variable or text of conditions
        $valueVariableText1 = getValueVariableText($dataEvaluate, $dataRequest, $j, $conditionVarText . '1', $conditionVariable . '1', $conditionText . '1');
        $valueVariableText2 = getValueVariableText($dataEvaluate, $dataRequest, $j, $conditionVarText . '2', $conditionVariable . '2', $conditionText . '2');

        //Is not the last value of the loop
        if (($j + 1) < count($dataEvaluate)) {
            $operator = $dataEvaluate[$j][$conditionOperator] . ' ';
        }
        //Concatenate conditions
        $conditions .= "'" . $valueVariableText1 . "' " . $signCondition . " '" . $valueVariableText2 . "' " . $operator;
    }
    //evaluate conditions;
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
 *
 * @param (string) $variable
 * @param (array) $requestData
 * @return (string) $bodyBlock;
 *
 * by Nestor Orihuela
 */
function htmlToXml($variable, $requestData)
{
    $string = convertVariablesToData($variable);
    eval("\$value = \$requestData['" . $string . "'];");
    if (!empty($value)) {
        $html = html_entity_decode($value);
        //initiate new temporal document
        $phpWordHandle = new \PhpOffice\PhpWord\PhpWord();
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $section = $phpWordHandle->addSection();
        //insert html on document section
        \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);
        $objWriter =  \PhpOffice\PhpWord\IOFactory::createWriter($phpWordHandle);
        $fullXml = $objWriter->getWriterPart('Document')->write();
        // Clean Space and Lines RN
        $dataLines = explode("\n", $fullXml);
        $newDataXML = "";
        foreach ($dataLines as $dataLine) {
            $newDataXML .= trim($dataLine);
        }
        // Get body Block XML
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
 *
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
*
* @param (array) $arrayCondition
* @param (array) $data
*
* @return boolean
*
* Ronald Nina - ronal.nina@processmaker.com
*/
function validateConditionTemplate($arrayCondition, $data)
{
    $dataReturn = false;
    $stringCondition = '';
    try {
        foreach ($arrayCondition as $condition) {
            if ($condition['UTP_VALIDATION_EVALUATE'] != "IN") {
                //Validate condition  < <= == > =>
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
                // Evaluate IN sentences
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
 *
 * @param (string) $sequenceCountVariable
 * @param (int) $startSequence
 * @param (string) $sequence
 * @param (bool) $sequenceSpace
 * @param (string) $typeNumber
 * @param (array) $arrayVariable
 *
 * @return (array) $arrayVariable
 *
 * Ronald Nina - ronal.nina@processmaker.com
 * modified by Nestor Orihuela
 */
function addSequence(
    $sequenceCountVariable = 1,
    $startSequence = null,
    $sequence = null,
    $sequenceSpace = null,
    $typeNumber = null,
    $arrayVariable
) {
    if (!empty($startSequence)) {
        $sequenceCountVariable = $startSequence;
    }
    $symbol = '#';
    if (!empty($sequence)) {
        $symbol = $sequence;
    }
    //concat space with symbol
    if (!empty($sequenceSpace) && $sequenceSpace == true) {
        $symbol .= " ";
    } else {
        $symbol .= "    ";
    }

    $tempArrayVariable = [];
    foreach ($arrayVariable as $valueVariable) {
        if (!empty($typeNumber) && $typeNumber == 'roman') {
            //roman Number with sequence count Variable
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
 *
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
 *
 * @return (string) $dataReturn
 *
 * by Ronald Nina - ronal.nina@processmaker.com
 * modify by Elmer Orihuela
 */
function concatArrayWithValues(
    $arrayVariable,
    $useSequence,
    $typeSequence,
    $sequence,
    $sequenceSpace,
    $startSequence,
    $concatDelimiter,
    $concatDelimiterSpace,
    $concatvarLoop,
    $concatFirstDifferent,
    $concatFirstRowDelimiter,
    $concatFirstRowSpace
) {
    // Evaluated Exceptions
    $arrayExceptions = array_column($concatvarLoop, 'UTP_CONCATVAR_EXCEPTION_VALUE');
    if (is_array($arrayVariable)){
        $arrayVariable = array_diff($arrayVariable, $arrayExceptions);
    }
    if (!is_array($arrayVariable)) {
        $arrayVariable = [];
    }
    $dataReturn = '';
    if (!empty($useSequence) && $useSequence == true) {
        // sequences
        switch ($typeSequence) {
            case 'DOTS': {
                    $tempArrayVariable = [];
                    $symbolDot = "&#8226;     ";
                    //concat with dot symbol 
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
                    //concat with dash symbol
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
        // without sequence
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
//-------------------------------- Get Document Types ----------------------------------
try {
    //Set Pmql to get all types active and for the first time document generated
    $urlPmqlType = urlencode('(data.FORTE_TYPE_PROCESS = "' . $processID . '" and data.FORTE_TYPE_STATUS = "ACTIVE" and data.' . $typeDocument . ' = "true")');
    $documentTypeUrl = $apiHost . '/collections/' . $collectionTypeTemplate . '/records?include=data&pmql=' . $urlPmqlType;
    $documentType = callGetCurl($documentTypeUrl);
} catch (Exception $e) {
    $data['YQP_ERROR_SLIP'] = "ERROR";
    $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
    //Forte Errors Screen
    $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
    $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
    $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";
    return $data;
}
if (count($documentType['data']) > 0) {
    //Initate new array 
    $documentTypeArray = [];
    for ($i = 0; $i < count($documentType['data']); $i++) {
        //Verify Document Type is into array documentTypeArray and insert new documentType found
        if (in_array($documentType['data'][$i]['data']['FORTE_TYPE_ID'], $documentTypeArray, TRUE) == false) {
            //Validate if the type of document should be generated
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
        $urlPmqlTemplates = urlencode('(data.UTP_TE_PROCESS_ID = "' . $processID . '" and data.UTP_TE_TYPE = "' . $documentGenerate . '" and data.UTP_TE_LANGUAGE = "' . $data['YQP_LANGUAGE'] . '")');
        $urlSlipTemplateCollection = $apiHost . '/collections/' . $collectionID . '/records' . '?include=data&pmql=' . $urlPmqlTemplates;
        $templateCollection = callGetCurl($urlSlipTemplateCollection);
    } catch (Exception $e) {
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";            
        return $data;
    }
    if (count($templateCollection['data']) > 0) {
        $templateCollection = orderResponseCollection($templateCollection, 'UTP_TE_ORDER');
    } 
    //-------------------------------- Get Document Template Variables ----------------------------------
    try {
        $urlPmqlVariables = urlencode('(data.UTP_PROCESS_ID = "' . $processID . '" and data.UTP_TYPE = "' . $documentGenerate . '" and data.UTP_LANGUAGE = "' . $data['YQP_LANGUAGE'] . '")');
        $urlTemplateVariablesCollection = $apiHost . '/collections/' . $templatesVariablesID . '/records' . '?include=data&pmql=' . $urlPmqlVariables;
        $templateVariablesCollection = callGetCurl($urlTemplateVariablesCollection);
    } catch (Exception $e) {
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = $e->getMessage();
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_91";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";
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
            $data["END_" . $documentGenerate . "_DOCUMENT_NAME"] = $templateNameGenerated;
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
                                    $dataSend = array();
                                    // Call OpenL url with data
                                    if ($useWithOpenL) {
                                        if (!empty($useWithOpenLUrlCurl)) {
                                            $dataSend['Reinsurer'] = $valueVariableLoopConvert;
                                            $aResponse = callCurlOpenL($useWithOpenLUrlCurl, $dataSend);
                                            $response = $aResponse["DATA"];
                                            //Set error
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
                                        "YQP_FORTE_ORDER_SHARE_SIGN" => $data["YQP_REINSURER_INFORMATION"][$l]["YQP_FORTE_ORDER_SHARE"]
                                    ];
                                }
                            }
                           
                        }  
                        // End - Loop                      
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
                    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";
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
            copy($outFile, $outFile . "_copy");
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
                    copy($outFile . "_copy", $outFile);
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
                                            $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";
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
                                //set empty Value
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
                $nameDocument = $data["END_" . $documentGenerate . "_DOCUMENT_NAME"];
                if ($documentGenerate == "SIGN") {
                    $nameDocument = $nameDocument . "_" . $signKeyRow;
                } 
                $dm->merge($inFile, "/tmp/" . $nameDocument . ".docx");
                //Save document merged with name Document
                $processRequestIdForte = $data['_request']['id'];
                $outFile = "/tmp/" . $nameDocument . ".docx";
                $dataName = 'END_DOWNLOAD_' . $documentGenerate . '_DOCUMENT';
                if ($documentGenerate == "SIGN") {
                    if ($signKeyRow == 1) {
                        $nameDocumentSign = $data["END_" . $documentGenerate . "_DOCUMENT_NAME"];
                        $dm->merge($inFile, "/tmp/" . $nameDocumentSign . ".docx");
                        $outFileSign = "/tmp/" . $nameDocumentSign . ".docx";  
                        $dataNameSign = 'END_DOWNLOAD_' . $documentGenerate . '_DOCUMENT';
                        $newFile = $apiInstance->createRequestFile($processRequestIdForte, $dataNameSign, $outFileSign);        
                    }
                    $dataName = 'END_DOWNLOAD_' . $documentGenerate . '_DOCUMENT' . $signKeyRow;
                }
                $newFile = $apiInstance->createRequestFile($processRequestIdForte, $dataName, $outFile);
                //--------------------------------- Create Download Files Button -----------------------------------------
                $slipFiles = $apiHost . '/requests/' . $data['_request']['id'] . '/files';
                $responseRequestFiles = callGetCurl($slipFiles);
                //foreach File on Request generate an URL download and URL_NAME_BUTTON
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
                //Forte Errors Screen
                $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
                $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
                $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
                $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_122";
                $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";
            }
        }

    } else {
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = "There are no templates for this data";
        //Forte Errors Screen
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_122";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "END - Generate Endorsement";
    }
}
return $data;