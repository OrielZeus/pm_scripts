<?php
/****************************************************
 * Prepare Documents to Generate Forte Documentation
 *
 * by Ana Castillo
 * modified by Nestor Orihuela
 * modified by Helen Callisaya
 * modified by Ronald Nina
 * modified by Cinthia Romero
 ***************************************************/

/***************************
 * FORTE Generic Functions
 *
 * by Cinthia Romero
 **************************/
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
<?php 
/*  
 * Set Client Information to send via email
 * by Helen Callisaya
 * modified by Cinthia Romero
 */

/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url)
{
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
    $responseCurl = json_decode($responseCurl, true);
    curl_close($curl);
    return $responseCurl;
}

/*
* Calls the processmaker api
*
* @param (string) $url
* @param (string) $method
* @param (string) $json_data 
* @return (Array) $responseCurl 
*
* by Helen Callisaya
*/
function callGetCurlCollection($url, $method, $json_data)
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
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $json_data,
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
 * Replace Variables in String
 *
 * @param (string) $string
 * @param (string) $dataRequest
 * @return (string) $string
 *
 * by Helen Callisaya
 */
function replaceVariablesString($string, $dataRequest)
{
    $validateExistVariable = strpos($string, '}');
    if ($validateExistVariable) {
        //Get variables in an array
        $stringExplode = explode('}', $string);
        for ($m = 0; $m < count($stringExplode); $m++) {
            $validateIsVariable = strpos($stringExplode[$m], '${');
            //Valid if variable
            if ($validateIsVariable) {
                $variable = '${' . substr($stringExplode[$m], strpos($stringExplode[$m], '{') + 1) . '}';
                $valueVariable = $dataRequest[substr($stringExplode[$m], strpos($stringExplode[$m], '{') + 1)];
                //Replace in the text with the value of the variable
                $string = str_replace($variable, $valueVariable, $string);
            }
        }
    }
    return $string;
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
 */
function getValueVariableText($dataValidation,
                              $dataRequest,
                              $rowValidation,
                              $nameVarText,
                              $nameVariable,
                              $nameText) 
{
    //Validates if the value to compare is variable or text
    if ($dataValidation[$rowValidation][$nameVarText] == "VARIABLE") {
        //Get Variable Name to validate 
        $variableCondition1 = $dataValidation[$rowValidation][$nameVariable];
        //Get the value of the variable to evaluate registered in the request example: $data['YQP_SUM_INSURED_VESSEL']
        if ($dataRequest[$variableCondition1]) {
            $valueVariableText = $dataRequest[$variableCondition1];
        } else {
            //When the variable does not exist we set it to 0
            $valueVariableText = 0;
        }
    } else {
        $valueVariableText = $dataValidation[$rowValidation][$nameText];
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
function evaluateCondition($dataEvaluate,
                           $dataRequest,
                           $conditionVarText,
                           $conditionVariable,
                           $conditionText,
                           $conditionSign,
                           $conditionOperator) 
{
    $conditions = "";
    for ($j = 0; $j < count($dataEvaluate); $j++) {
        $operator = "";
        //Get value of variable or text of conditions
        $valueVariableText1 = getValueVariableText($dataEvaluate, $dataRequest, $j, $conditionVarText . '1', $conditionVariable . '1', $conditionText . '1');
        $valueVariableText2 = getValueVariableText($dataEvaluate, $dataRequest, $j, $conditionVarText . '2', $conditionVariable . '2', $conditionText . '2');
        //Get condition operator
        $signCondition = $dataEvaluate[$j][$conditionSign];
        //Is not the last value of the loop
        if (($j + 1) < count($dataEvaluate)) {
            $operator = $dataEvaluate[$j][$conditionOperator] . ' ';
        }
        //Concatenate conditions
        $conditions .= "'" . $valueVariableText1 . "' " . $signCondition . " '" . $valueVariableText2 . "' " . $operator;
    }
    $evaluate = "\$resultCondition = $conditions;";
    $evaluate = @eval($evaluate);
    return $resultCondition;
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
    if (empty($idFile) || $nameFile === null || $nameFile === '') {
        return false;
    }
    //Create file in temporary folder
    $tempFolder = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    $filePath = $tempFolder . DIRECTORY_SEPARATOR . $nameFile;
    $file = fopen($filePath, 'w');
    if ($file === false) {
        return false;
    }
    fwrite($file, $response);
    fclose($file);
    return $filePath;
}

//Import Swift_SmtpTransport classes into the global namespace
require_once 'vendor/autoload.php';

// Create the Transport
$transport = (new Swift_SmtpTransport(getenv('FORTE_SMTP_ADDRESS'), 587, 'tls'))
    ->setUsername(getenv('FORTE_SMTP_USER'))
    ->setPassword(getenv('FORTE_SMTP_USER_PASSWORD'));
// Create the Mailer using your created Transport
$mailer = new Swift_Mailer($transport);
// Create a message
$message = new Swift_Message();

//Set subject and body variables
$subject = "";
$emailBody = "";
$accountFrom = "";
$nameFrom = "";
$reply = false;
$data['YQP_EMAIL'] = '';

//Set User Process
$urlUserId = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$responseUserId = callGetCurl($urlUserId);
$dataClientInfo['YQP_USER_FULLNAME'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'];
$dataClientInfo['YQP_USER_INITIALS'] = $responseUserId['address'];
$dataClientInfo['YQP_NAME_TO'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'];
$dataClientInfo['YQP_POSITION_TO'] = $responseUserId['title'];
$dataClientInfo['YQP_PHONE'] = $responseUserId['phone'] . ' Ext. ' . $responseUserId['meta']['FORTE_PHONE_EXTENSION'];
$dataClientInfo['YQP_CURRENT_USER_EMAIL'] = $responseUserId['username'];

$data['YQP_USER_INITIALS'] = $responseUserId['address'];
$data['YQP_NAME_TO'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'];
$data['YQP_POSITION_TO'] = $responseUserId['title'];
$data['YQP_PHONE'] = $responseUserId['phone'] . ' Ext. ' . $responseUserId['meta']['FORTE_PHONE_EXTENSION'];
$data['YQP_CURRENT_USER_EMAIL'] = $responseUserId['username'];

//Valid existence of approval Sr. Underwriter
$approveSrUnderwriter = empty($data['YQP_APPROVE_CONFIRMATION_UPLOAD_SLIP']) ? "NO" : $data['YQP_APPROVE_CONFIRMATION_UPLOAD_SLIP'];

if ($approveSrUnderwriter == "YES") {
    //Slip Manual Client
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $data['YQP_UPLOAD_CONFIRMATION_SLIP']; 
    $slipFiles = getenv('API_HOST') . '/files/' . $data['YQP_UPLOAD_CONFIRMATION_SLIP'];
    $responseRequestFiles = callGetCurl($slipFiles);
    $fileName = $responseRequestFiles['file_name'];
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = $fileName;
    $idFileQuote = $data['YQP_UPLOAD_CONFIRMATION_SLIP'];
    //Slip Manual Adobe
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $data['YQP_UPLOAD_CONFIRMATION_SLIP_ADOBE']; 
    $slipFiles = getenv('API_HOST') . '/files/' . $data['YQP_UPLOAD_CONFIRMATION_SLIP_ADOBE'];
    $responseRequestFiles = callGetCurl($slipFiles);
    $fileName = $responseRequestFiles['file_name'];
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE_NAME_BUTTON'] = $fileName;
    $idFileQuoteAdobe = $data['YQP_UPLOAD_CONFIRMATION_SLIP_ADOBE'];    
} else {
    //Slip Generate Client
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = $data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL'];
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = $data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_NAME_BUTTON'];
    $pos = strrpos($data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL'], "/");
    $idFileQuote = rtrim(substr($data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL'], $pos + 1));
    //Slip Generate Adobe
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE'] = $data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_ADOBE'];
    $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE_NAME_BUTTON'] = $data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_NAME_BUTTON_ADOBE'];
    $pos = strrpos($data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_ADOBE'], "/");
    $idFileQuoteAdobe = rtrim(substr($data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_ADOBE'], $pos + 1));    
}
$processRequestIdForte = $data['_request']['id'];
$apiInstance = $api->requestFiles();
//Create Slip Client
$pathSlipQuoteOfficial = callGetCurlFiles($idFileQuote, $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON']);
$dataName = 'YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT';
$newSlipQuote = $apiInstance->createRequestFile($processRequestIdForte, $dataName, $pathSlipQuoteOfficial);
//Create Slip Adobe only when the Adobe slip document was generated upstream
if (!empty($idFileQuoteAdobe) && !empty($dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE_NAME_BUTTON'])) {
    $pathSlipQuoteOfficialAdobe = callGetCurlFiles($idFileQuoteAdobe, $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE_NAME_BUTTON']);
    if ($pathSlipQuoteOfficialAdobe !== false) {
        $dataNameAdobe = 'YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_ADOBE';
        $newSlipQuoteAdobe = $apiInstance->createRequestFile($processRequestIdForte, $dataNameAdobe, $pathSlipQuoteOfficialAdobe);
    }
}

//Get URL Connection openL
$openLUrl = getenv('OPENL_CONNECTION');

//Get email subject, image and body records
$urlEmailCollection = getenv('API_HOST') . '/collections/' . getenv('FORTE_EMAIL_COLLECTION') . '/records' . '?include=data&pmql=' . urlencode('(data.FORTE_EMAIL_PROCESS = "' . $data['_request']['process_id'] . '" and data.FORTE_EMAIL_TYPE = "CONFIRMATION" and data.FORTE_EMAIL_LANGUAJE = "' . $data['YQP_LANGUAGE'] . '")');
$responseEmailCollection = callGetCurl($urlEmailCollection);

if (count($responseEmailCollection['data']) > 0) {
    //Replace variables in subject;
    $subject = replaceVariablesString($responseEmailCollection['data'][0]['data']['FORTE_EMAIL_SUBJECT'], $data);
    $emailBody = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_MESSAGE'];
    $nameFrom = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_NAME'];
    $accountFrom = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_ACCOUNT'];
    $reply =  $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_REPLY'];
    $dataLogo = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_LOGO'];
    $dataAttach = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_ATTACH'];
    $dataAttachVariable = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_ATTACH_REQUEST'];
    $dataCcEmail = $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_CC'];
    $dataSignDifferent =  isset($responseEmailCollection['data'][0]['data']['FORTE_EMAIL_SIGN_DIFFERENT']) ? $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_SIGN_DIFFERENT'] : "";
    $dataSignUserId = isset($responseEmailCollection['data'][0]['data']['FORTE_EMAIL_USER_SIGN']) ? $responseEmailCollection['data'][0]['data']['FORTE_EMAIL_USER_SIGN'] : "";
    $nameAttachments = "";
    //------------------------------------Sign-------------------------
    if ($dataSignDifferent == true) {
        //Get Country Cedent
        $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetCedentCountry";
        $dataSend = array();
        $dataSend['Cedent'] = $data["YQP_REASSURED_CEDENT"]["LABEL"];
        //Call Curl OpenL
        $aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
        //Set error
        $err = $aResponse["ERROR"];
        if ($err) {
            //If there is an error, set the error
            $responseCedent = "cURL Error #:" . $err;
        } else {
            $responseCedent = $aResponse["DATA"];
        }
        
        //Get Country Broker
        $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetBrokerCountry";
        $dataSend = array();
        $dataSend['Broker'] = $data["YQP_REINSURANCE_BROKER"]["LABEL"];
        //Call Curl OpenL
        $aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
        //Set error
        $err = $aResponse["ERROR"];
        if ($err) {
            //If there is an error, set the error
            $responseBroker = "cURL Error #:" . $err;
        } else {
            $responseBroker = $aResponse["DATA"];
        } 
        //Validate Cedent or Broker is Mexico         
        if ($responseCedent == "MEXICO" || $responseBroker == "MEXICO") {
            if ($data['YQP_USER_ID'] != $dataSignUserId ) {
                //Set User Process
                $urlUserId = getenv('API_HOST') . '/users/' . $dataSignUserId;
                $responseUserId = callGetCurl($urlUserId);
                $data['YQP_NAME_TO'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'] . " / " . $dataClientInfo['YQP_NAME_TO'];
                $data['YQP_POSITION_TO'] = $responseUserId['title'] . " / " . $dataClientInfo['YQP_POSITION_TO'];
            }
        }        
    }    
    //------------------------------------Logo-------------------------
    $existLogo = 0;
    $logoId = "";
    $logoSign = "";
    $logoName = "";
    $logoDomain = "";
    for ($i = 0; $i < count($dataLogo); $i++) {
        //Validate that variable is array
        if ($dataLogo[$i]['FORTE_EMAIL_LOGO_VALIDATION'] == null) {
            $dataLogo[$i]['FORTE_EMAIL_LOGO_VALIDATION'] = array();
        }
        //Get Conditions
        if (count($dataLogo[$i]['FORTE_EMAIL_LOGO_VALIDATION']) > 0) {
            $resultCondition = evaluateCondition($dataLogo[$i]['FORTE_EMAIL_LOGO_VALIDATION'],
                                                 $data,
                                                 'FORTE_EMAIL_LOGO_VARTEXT',
                                                 'FORTE_EMAIL_LOGO_VARIABLE',
                                                 'FORTE_EMAIL_LOGO_TEXT',
                                                 'FORTE_EMAIL_LOGO_CONDITION_SIGN',
                                                 'FORTE_EMAIL_LOGO_OPERATOR');
            //The result is true
            if ($resultCondition == true) {
                $logoId = $dataLogo[$i]['FORTE_EMAIL_LOGO_UPLOAD']['id'];
                $logoName = $dataLogo[$i]['FORTE_EMAIL_LOGO_UPLOAD']['name'];
                $logoDomain = $dataLogo[$i]['FORTE_EMAIL_DOMAIN'];
                break;
            }
        } else {
            //There are no conditions
            $logoId = $dataLogo[$i]['FORTE_EMAIL_LOGO_UPLOAD']['id'];
            $logoName = $dataLogo[$i]['FORTE_EMAIL_LOGO_UPLOAD']['name'];
            $logoDomain = $dataLogo[$i]['FORTE_EMAIL_DOMAIN'];
            break;
        }
    }

    //Has Logo
    if ($logoDomain != "") {
        //Get content logo
        $fileLogo = callGetCurlFiles($logoId, $logoName);
        //Embed logo to email for signature        
        $logoSign = "<img src='" . $message->embed(Swift_Image::fromPath($fileLogo)) . "'  width='110' height='80' />";
        $data['YQP_EMAIL'] = $data['YQP_CURRENT_USER_EMAIL'] . $logoDomain;
    }
    //--------------------------------Get Attachment Variable----------------------------------
    $documentAttachVariable = array();
    for ($i = 0; $i < count($dataAttachVariable); $i++) {
        $conditions = "";
        //Get file the request
        $urlDocumentAttachVariable = getenv('API_HOST') . '/requests/' . $data['_request']['id'] . '/files';
        $responseDocumentAttachVariable = callGetCurl($urlDocumentAttachVariable);
        //Get conditions
        if ($dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_VALIDATION'] == null) {
            $dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_VALIDATION'] = array();
        }
        if (count($dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_VALIDATION']) > 0) {
            $resultCondition = evaluateCondition($dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_VALIDATION'],
                                                 $data,
                                                 'FORTE_EMAIL_ATTACH_REQUEST_VARTEXT',
                                                 'FORTE_EMAIL_ATTACH_REQUEST_VARIABLE',
                                                 'FORTE_EMAIL_ATTACH_REQUEST_TEXT',
                                                 'FORTE_EMAIL_ATTACH_REQUEST_CONDITION_SIGN',
                                                 'FORTE_EMAIL_ATTACH_REQUEST_OPERATOR');
            //The result is true
            if ($resultCondition == true) {
                foreach ($responseDocumentAttachVariable['data'] as $responseDocumentAttachVariables) {
                    //Compares the element name with the screen element
                    if ($responseDocumentAttachVariables['custom_properties']['data_name'] == $dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_VARIABLE']) {
                        //Get content document SLIP
                        $file = callGetCurlFiles($responseDocumentAttachVariables['id'], $responseDocumentAttachVariables['file_name']);
                        //Attach document
                        $message->attach(Swift_Attachment::fromPath($file)->setFilename($responseDocumentAttachVariables['file_name']));
                        //Add document name to the list of additional documents
                        $nameAttachments .= "<li>" . $dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_NAME'] . "</li>";
                    }
                }
            }
        } else {
            foreach ($responseDocumentAttachVariable['data'] as $responseDocumentAttachVariables) {
                //Compares the element name with the screen element SLIP
                if ($responseDocumentAttachVariables['custom_properties']['data_name'] == $dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_VARIABLE']) {
                    //Get content document SLIP
                    $file = callGetCurlFiles($responseDocumentAttachVariables['id'], $responseDocumentAttachVariables['file_name']);
                    //Attach document
                    $message->attach(Swift_Attachment::fromPath($file)->setFilename($responseDocumentAttachVariables['file_name']));
                    //Add document name to the list of additional documents
                    $nameAttachments .= "<li>" . $dataAttachVariable[$i]['FORTE_EMAIL_ATTACH_REQUEST_NAME'] . "</li>";
                }
            }
        }
    }
    //-------------------Get Attachment-------------------------
    $documentAttach = array();
    for ($i = 0; $i < count($dataAttach); $i++) {
        $conditions = "";
        //Get conditions
        if ($dataAttach[$i]['FORTE_EMAIL_ATTACH_VALIDATION'] == null) {
            $dataAttach[$i]['FORTE_EMAIL_ATTACH_VALIDATION'] = array();
        }
        if (count($dataAttach[$i]['FORTE_EMAIL_ATTACH_VALIDATION']) > 0) {
            $resultCondition = evaluateCondition($dataAttach[$i]['FORTE_EMAIL_ATTACH_VALIDATION'],
                                                 $data,
                                                 'FORTE_EMAIL_ATTACH_VALIDATION',
                                                 'FORTE_EMAIL_ATTACH_VARIABLE',
                                                 'FORTE_EMAIL_ATTACH_TEXT',
                                                 'FORTE_EMAIL_ATTACH_CONDITION_SIGN',
                                                 'FORTE_EMAIL_ATTACH_OPERATOR');
            //The result is true
            if ($resultCondition == true) {
                //Get content document
                $fileAttach = callGetCurlFiles($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['id'], $dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name']);
                //Attach document
                $message->attach(Swift_Attachment::fromPath($fileAttach)->setFilename($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name']));
                //Add document name to the list of additional documents
                $nameAttachments .= "<li>" . $dataAttach[$i]['FORTE_EMAIL_ATTACH_NAME'] . "</li>";
            }
        } else {
            //Get content document
            $fileAttach = callGetCurlFiles($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['id'], $dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name']);
            //Attach document
            $message->attach(Swift_Attachment::fromPath($fileAttach)->setFilename($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name']));
            //Add document name to the list of additional documents
            $nameAttachments .= "<li>" . $dataAttach[$i]['FORTE_EMAIL_ATTACH_NAME'] . "</li>";
        }
    }
    //--------------------------------------------Request attachments entered by the user------------------------------------
    //Validate request attachment array is null
    if ($data['YQP_CONFIRMATION_DOCUMENTS'] == null) {
        $data['YQP_CONFIRMATION_DOCUMENTS'] = array();
    }
    for ($i = 0; $i < count($data['YQP_CONFIRMATION_DOCUMENTS']); $i++) {
        if( $data['YQP_CONFIRMATION_DOCUMENTS'][$i]['YQP_CONFIRMATION_UPLOAD_DOCUMENT'] != null) {
            //Get Document Name
            $urlDocument = getenv('API_HOST') . '/files/' . $data['YQP_CONFIRMATION_DOCUMENTS'][$i]['YQP_CONFIRMATION_UPLOAD_DOCUMENT'];
            $responseDocument = callGetCurl($urlDocument);
            //Get Document Content
            $fileAttach = callGetCurlFiles($data['YQP_CONFIRMATION_DOCUMENTS'][$i]['YQP_CONFIRMATION_UPLOAD_DOCUMENT'], $responseDocument["file_name"]);
            //Attach document
            $message->attach(Swift_Attachment::fromPath($fileAttach)->setFilename($responseDocument["file_name"]));
            //Add document name to the list of additional documents
            $nameAttachments .= "<li>" . $data['YQP_CONFIRMATION_DOCUMENTS'][$i]['YQP_CONFIRMATION_ATTACHMENT_NAME'] . "</li>";
        }
    }
    //---------------------------Replacement of values on email variables--------------------------------------
    //Attach additional documents for cotization slip
    $data['YQP_ADDITIONAL_DOCUMENTS'] = "<ul>" . $nameAttachments . "</ul>";
    $data['YQP_SUBJECTIVES_GUARANTEE_EMAIL'] = html_entity_decode($data['YQP_SUBJECTIVES_GUARANTEE_EMAIL']);
    $data['YQP_LOGO_IMAGE'] = $logoSign;
    //Replace variables in Email body
    $emailBody = replaceVariablesString($emailBody, $data);
    //---------------------------------Send Email--------------------------------------------------------------
    // Set a "subject"
    $message->setSubject($subject);
    // Set the "From address"
    $message->setFrom([$accountFrom => $nameFrom]);
    // Set the "To address"
    $message->addTo($data['YQP_SLIP_EMAIL'], $data['YQP_SLIP_EMAIL_NAME']);
    //Add CC Email Collection
    for ($x = 0; $x < count($dataCcEmail); $x++) {
        if ($dataCcEmail[$x]['FORTE_EMAIL_CC_EMAIL'] != null || $dataCcEmail[$x]['FORTE_EMAIL_CC_EMAIL'] != '') {
            if ($dataCcEmail[$x]['FORTE_EMAIL_CC_NAME'] != null || $dataCcEmail[$x]['FORTE_EMAIL_CC_NAME'] != '') {
                $message->addCc($dataCcEmail[$x]['FORTE_EMAIL_CC_EMAIL'], $dataCcEmail[$x]['FORTE_EMAIL_CC_NAME']);
            } else {
                $message->addCc($dataCcEmail[$x]['FORTE_EMAIL_CC_EMAIL']);
            }
        }
    }
    //Add CC from Screen
    if ($data['YQP_SLIP_CONFIRMATION_EMAIL_CC'] == null) {
        $data['YQP_SLIP_CONFIRMATION_EMAIL_CC'] = array();
    }
    for ($z = 0; $z < count($data['YQP_SLIP_CONFIRMATION_EMAIL_CC']); $z++) {
        if ($data['YQP_SLIP_CONFIRMATION_EMAIL_CC'][$z]['YQP_EMAIL_CC'] != null || $data['YQP_SLIP_CONFIRMATION_EMAIL_CC'][$z]['YQP_EMAIL_CC'] != '') {
            $message->addCc($data['YQP_SLIP_CONFIRMATION_EMAIL_CC'][$z]['YQP_EMAIL_CC']);
        }
    }    
    // Set to Replay
    if ($reply == true) {
        if($data['YQP_EMAIL'] != '') {
            $message->setReplyTo($data['YQP_EMAIL']);
        }        
    }
    // Set a "Body"
    $message->addPart($emailBody, 'text/html');
    // Send the message
    if (!$mailer->send($message)) {
        $dataClientInfo['YQP_STATUS_MESSAGE_SEND_MAIL'] = "Error..." . $mail->ErrorInfo;
        $dataClientInfo['YQP_STATUS_SEND_MAIL'] = "ERROR";
    } else {
        $dataClientInfo['YQP_STATUS_SEND_MAIL'] = "OK";
        $dataClientInfo['YQP_STATUS_MESSAGE_SEND_MAIL'] = "Message sent successfully";
    }
} else {
    $dataClientInfo['YQP_STATUS_SEND_MAIL'] = "ERROR";
    $dataClientInfo['YQP_STATUS_MESSAGE_SEND_MAIL'] = "There is no email delivery configured";
}

//Clean Variables
$dataClientInfo["YQP_SUBMIT_SAVE"] = "SUBMIT";
//----------------------
if ($data['YQP_STATUS'] == "BOUND" && $data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    //Save to Producing Files Collection
    $searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
    $searchRequest = callGetCurlCollection($searchRequestUrl, "GET", "");
    $dataProducing = array();
    $dataProducing['FORTE_REQUEST'] = $data['_request']['id'];
    $dataProducing['FORTE_PROCESS'] = $data['_request']['process_id'];
    $dataProducing['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
    $dataProducing['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
    
    //Validate Process
    if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
        $dataProducing['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
        $dataProducing['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        //Name Process
        $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
    } else {
        $dataProducing['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
        $dataProducing['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        $dataProducing['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
        //Name Process
        $dataProducing['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
    }

    $dataProducing['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
    $dataProducing['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
    $dataProducing['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
    $dataProducing['YQP_CATCH_MONTH_REPORT'] = $data['YQP_CATCH_MONTH_REPORT'];
    $dataProducing['YQP_PIVOT_TABLE_NUMBER'] = $data['YQP_PIVOT_TABLE_NUMBER'];
    $dataProducing['YQP_SLIP_DOCUMENT_NAME'] = $data['YQP_SLIP_DOCUMENT_NAME'];
    $dataProducing['YQP_TYPE'] = $data['YQP_TYPE'];
    $dataProducing['YQP_REASSURED_CEDENT_LABEL'] = $data['YQP_REASSURED_CEDENT']['LABEL'];
    $dataProducing['YQP_REINSURANCE_BROKER_LABEL'] = $data['YQP_REINSURANCE_BROKER']['LABEL'];
    $dataProducing['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
    $dataProducing['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
    $dataProducing['YQP_TRIMESTER'] = $data['YQP_TRIMESTER'];
    $dataProducing['YQP_YEAR_PERIOD'] = $data['YQP_YEAR_PERIOD'];
    $dataProducing['YQP_CURRENCY'] = $data['YQP_CURRENCY'];
    $dataProducing['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
    $dataProducing['DATA_WAR_SUM_INSURED_TENDER'] = $data['DATA_WAR_SUM_INSURED_TENDER'];
    $dataProducing['DATA_WAR_SUM_INSURED_NET'] = $data['DATA_WAR_SUM_INSURED'];
    $dataProducing['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
    $dataProducing['DATA_WAR_SUM_INSURED'] = $data['DATA_WAR_SUM_INSURED'];
    $dataProducing['YQP_PERSONAL_EFFECTS_LIMIT'] = $data['YQP_PERSONAL_EFFECTS_LIMIT'];
    $dataProducing['YQP_MEDICAL_PAYMENTS_LIMIT'] = $data['YQP_MEDICAL_PAYMENTS_LIMIT'];
    $dataProducing['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'] = $data['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'];
    $dataProducing['YQP_TOTAL_PREMIUM_FINAL'] = $data['YQP_TOTAL_PREMIUM_FINAL'];
    $dataProducing['YQP_DEDUCTIBLE'] = $data['YQP_DEDUCTIBLE'];
    $dataProducing['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'] = $data['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'];
    $dataProducing['YQP_WAR_DEDUCTIBLE_SLIP'] = $data['YQP_WAR_DEDUCTIBLE_SLIP'];
    $dataProducing['YQP_PI_DEDUCTIBLE_SLIP'] = $data['YQP_PI_DEDUCTIBLE_SLIP'];
    $dataProducing['YQP_NUMBER_PAYMENTS'] = $data['YQP_NUMBER_PAYMENTS'];
    $dataProducing['YQP_BROKER_PERCENTAGE'] = $data['YQP_BROKER_PERCENTAGE'];
    $dataProducing['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'] = $data['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'];
    $dataProducing['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] = $data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'];
    $dataProducing['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
    $dataProducing['YQP_TYPE_CODE_REPORT'] = $data['YQP_TYPE_CODE_REPORT'];
    $dataProducing['YQP_VESSEL_MARK_MODEL'] = $data['YQP_VESSEL_MARK_MODEL'];
    $dataProducing['YQP_LENGTH_UNIT_REPORT'] = $data['YQP_LENGTH_UNIT_REPORT'];
    $dataProducing['YQP_YEAR'] = $data['YQP_YEAR'];
    $dataProducing['YQP_NAVIGATION_LIMITS'] = $data['YQP_NAVIGATION_LIMITS'];
    $dataProducing['YQP_USE'] = $data['YQP_USE'];
    $dataProducing['YQP_LOSS_PAYEE'] = $data['YQP_LOSS_PAYEE'];
    $dataProducing['YQP_HULL_MATERIAL'] = $data['YQP_HULL_MATERIAL'];
    $dataProducing['YQP_FLAG'] = $data['YQP_FLAG'];
    $dataProducing['YQP_MOORING_PORT_REPORT'] = $data['YQP_MOORING_PORT_REPORT'];
    $dataProducing['YQP_CLUB_MARINA'] = $data['YQP_CLUB_MARINA'];
    $dataProducing['YQP_COMMENTS'] = $data['YQP_COMMENTS'];
    $dataProducing['YQP_USER_USERNAME'] = $data['YQP_USER_USERNAME'];
    $dataProducing['YQP_RISK_ATTACHING_MONTH'] = $data['YQP_RISK_ATTACHING_MONTH'];
    $dataProducing['YQP_TERM'] = $data['YQP_TERM'];
    $dataProducing['YQP_TYPE_YACHT'] = $data['YQP_TYPE_YACHT'];
    $dataProducing['YQP_RANGE_SI_HULL'] = $data['YQP_RANGE_SI_HULL'];
    $dataProducing['YQP_RANGE_YEAR'] = $data['YQP_RANGE_YEAR'];
    $dataProducing['YQP_STATUS'] = $data['YQP_STATUS'];
    //Calculate variables YQP_SUM_INSURED_HULL_CESSION_REPORT, YQP_RATE_CESSION_REPORT, YQP_TAXES_USD_100_REPORT used in Producing report. Added by Cinthia Romero 2024-01-08
    $reinsurerGridData = isset($data['YQP_REINSURER_INFORMATION']) ? $data['YQP_REINSURER_INFORMATION'] : [];
    if (count($reinsurerGridData) > 0 && !empty($data['DATA_WAR_SUM_INSURED'])) {
        foreach ($reinsurerGridData as $key=>$reinsurer) {
            /*if (!empty($reinsurer['YQP_FORTE_ORDER_SHARE'])) {
                //YQP_SUM_INSURED_HULL_CESSION_REPORT
                $sumInsuredHullCessionReport = $data['DATA_WAR_SUM_INSURED'] * ($reinsurer['YQP_FORTE_ORDER_SHARE'] / 100);
                $reinsurerGridData[$key]["YQP_SUM_INSURED_HULL_CESSION_REPORT"] = $sumInsuredHullCessionReport;
                //YQP_RATE_CESSION_REPORT
                if (!empty($data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'])) {
                    if ($sumInsuredHullCessionReport > 0) {
                        $reinsurerGridData[$key]['YQP_RATE_CESSION_REPORT'] = round($data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] / $sumInsuredHullCessionReport, 2);
                    } else {
                        $reinsurerGridData[$key]['YQP_RATE_CESSION_REPORT'] = 0;
                    }
                }
            }*/
            //YQP_TAXES_USD_100_REPORT
            $reinsurerGridData[$key]['YQP_TAXES_USD_100_REPORT'] = $reinsurer['YQP_TOTAL_PREMIUM_GRID_TOTAL'] * ($reinsurer['YQP_TAX_ON_GROSS'] / 100);
        }
    }
    $dataProducing['YQP_REINSURER_INFORMATION'] = $reinsurerGridData;
    //-----------------Calculate Rate Cession and Hull Cession--------------------
    if ($data['YQP_PRODUCT'] != "PI_RC") {
        //(Total Hull Sum Insured * Forte Order) Add HC
        $dataProducing['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = $data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100);
        //(Total Premium * FORTE ORDER) / (Total Hull Sum Insured * Forte Order) Add HC
        $dataProducing['YQP_RATE_CESSION_REPORT'] = ($data['YQP_BROKER_TOTAL_PREMIUM_REPORT'] * ($data['YQP_FORTE_ORDER'] / 100)) / ($data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100));
    } else {
        $dataProducing['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = 0;
        $dataProducing['YQP_RATE_CESSION_REPORT'] = 0;
    }
    //---------------------------------------------------------------------------
    //Check Process Type
    if ($data['_request']['process_id'] != getenv('FORTE_ID_YACHT')) {
        $dataProducing['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    }
    //Validate if the request exists
    if (count($searchRequest["data"]) == 0) {
        $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records';
        $insertRequest = callGetCurlCollection($insertRequestUrl, "POST", json_encode($dataProducing));
    } else {
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurlCollection($updateRequestUrl, "PUT", json_encode($dataProducing));
    }
}

//Return all Values
return $dataClientInfo;
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

//Clean variable of signatures
$data["YQP_SIGN_ADOBE_SLIP"] = "";
//Set value as Bound
$data['YQP_STATUS'] = "BOUND";
//Set value as YQP_RISK_ATTACHING_MONTH
$data['YQP_RISK_ATTACHING_MONTH'] = date('n');
//set initial message
$data['YQP_ERROR_SLIP'] = "";
//Set variable generated Slip
$data["YQP_SLIP_GENERATED_TYPE"] = "CONFIRMATION";
//GET API TOKEN
$apiToken = getenv('API_TOKEN');
//GET HOST/api/1.0
$apiHost = getenv('API_HOST');
//get env Collection_ID
$collectionID = getenv('UTP_TEMPLATES_COLLECTION');
//Global enviroment Templates Variables ID
$templatesVariablesID = getenv('UTP_TEMPLATES_VARIABLES_COLLECTION');
//Get month difference between effective date and current date
$periodFrom = new DateTime($data['YQP_PERIOD_FROM']);
$periodTo  = new DateTime($data['YQP_PERIOD_TO']);
$dateDifference = $periodFrom->diff($periodTo);
$monthDiference = $dateDifference->m + ($dateDifference->y * 12);
$data['YQP_VALIDATE_PERIOD_MONTH'] = $monthDiference;
$data['YQP_TERM'] = $monthDiference;
//Get URL Connection of OpenL
$openLUrl = getenv('OPENL_CONNECTION');
//Set process ID
$processID = $data['_request']['process_id'];
//Set first or second document to get type of documents
$typeDocument = 'FORTE_TYPE_SECOND_DOCUMENT';
$typeConditionDocument = 'FORTE_TYPE_SECOND_CONDITION';
//Set Collection of Type of Documents in Templates process
$collectionTypeTemplate = getenv('FORTE_TEMPLATES_TYPE_COLLECTION');

if (!empty($data['YQP_UPLOAD_CONFIRMATION_SUBMIT'])) {
    //Comments Approve
    $data['YQP_COMMENTS_APPROVE_CONFIRMATION_SLIP'] = "";
    //Submit Approve
    $data['YQP_FLOW_QUOTE_SLIP'] = "";
    //Id document Upload
    $data['YQP_UPLOAD_CONFIRMATION_SLIP'] = "";
    // Id document Upload Adobe
    $data['YQP_UPLOAD_CONFIRMATION_SLIP_ADOBE'] = "";
    //Approve Sr. underwriter
    $data['YQP_APPROVE_CONFIRMATION_UPLOAD_SLIP'] = "";  
}
//clean Variables Quote
$data['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = "";
$data['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = "";
$data['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE'] = "";
$data['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE_NAME_BUTTON'] = "";
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
/******************************* Set YQP_REINSURER_SIGNATURES to slip *******************************/
$reinsurer = array();
$reinsurer = $data["YQP_REINSURER_INFORMATION"];
//Set YQP_REINSURER_INFORMATION in variable return
$reinsurerSlip = "<table style='width:100%;font-family: Corbel;font-size: 11pt;'>";
$noCode = "";
$noCodeValidate = "YES";
if ($data['YQP_SOURCE'] != 'Other') {
    $reinsurerSlip .= "<tr><td style='width:10%;'></td>";    
    if ($data['YQP_LANGUAGE'] == 'ES') {
        $reinsurerSlip .= "<td style='width:90%;' colspan='3'>" . $data['YQP_FORTE_ORDER'] . "% por " . $data["YQP_COMPLETE_NAME_SOURCE"] . "</td></tr>";
    } else {
        $reinsurerSlip .= "<td style='width:90%;' colspan='3'>" . $data['YQP_FORTE_ORDER'] . "% by " . $data["YQP_COMPLETE_NAME_SOURCE"] . "</td></tr>";
    }
    $reinsurerSlip .= "<tr><td colspan='4'></td></tr>";
}
//Set necessary variables
$dataSend = array();
$dataSend['Source'] = $data["YQP_SOURCE"];
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetSignatureSource";
//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];
//Set variable with options
if ($err) {
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "OpenL Error";
    $requestError['FORTE_ERROR_BODY'] = "Rules cannot be executed. Please contact your system administrator.";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "OpenL error";
} else {
    //Get values of the response
    if ($response != "") {
        $reinsurerSlip .= "<tr><td style='width:20%;' colspan='2'></td>";
        if ($data['YQP_LANGUAGE'] == 'ES') {        
            $reinsurerSlip .= "<td style='width:80%;' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% por " . $response . "</td></tr>";
        } else {
            $reinsurerSlip .= "<td style='width:80%;' colspan='2'>" . $data['YQP_FORTE_ORDER'] . "% by " . $response . "</td></tr>";
        }
    }
}
$reinsurersTableEstarSeguros = "<table style='width:100%;font-family: Corbel;font-size: 11pt;'>";
$reinsurerListTable = "<table style='width:100%; border-collapse:collapse; border-color:#bababa; text-align:center;' border='1'>";
$reinsurerListTable .= "<tr style='font-weight: bold;'>";
$reinsurerListTable .= "<td style='width:10%; color:#555916'></td>";
$reinsurerListTable .= "<td style='width:70%; color:#555916'>Reinsurer Name</td>";
$reinsurerListTable .= "<td style='width:20%; color:#555916'>Share %</td>";
$reinsurerListTable .= "</tr>";
for ($r = 0; $r < count($reinsurer); $r++) {
    $reinsurerOrder = $r + 1;
    $forteOrderShare = $reinsurer[$r]["YQP_FORTE_ORDER_GRID"] * ($reinsurer[$r]["YQP_SHARE_PERCENTAGE"] / 100);
    $data["YQP_REINSURER_INFORMATION"][$r]['YQP_FORTE_ORDER_SHARE'] = $forteOrderShare;
    $reinsurerListTable .= "<tr>";
    $reinsurerListTable .= "<td>" . $reinsurerOrder . "</td>";
    $reinsurerListTable .= "<td>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td>";
    $reinsurerListTable .= "<td>" . $reinsurer[$r]["YQP_SHARE_PERCENTAGE"] . "</td>";
    $reinsurerListTable .= "</tr>";
    if ($r != 0) {
        $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='3'></td><td style='width:65%;text-align:left;'></td></tr>"; //Add a space among reinsurers
        $reinsurersTableEstarSeguros .= "<tr><td></td></tr>";
    }
    $dataSend = array();           
    $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='3'>" . $forteOrderShare . "% </td>";
    //Only for Estar Seguros Venezuela
    if ($data['YQP_LANGUAGE'] == 'ES') {
        $reinsurersTableEstarSeguros .= "<tr><td style='text-align:center;'>" . $forteOrderShare . "% del 100% por " . strtoupper($reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"]) . "</td></tr>";
    } else {
        $reinsurersTableEstarSeguros .= "<tr><td style='text-align:center;'>" . $forteOrderShare . "% of 100% by " . strtoupper($reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"]) . "</td></tr>";
    }
    //
    if ($data["YQP_COUNTRY_BUSINESS"] == "Mexico") {
        $dataSend['Reinsurer'] = $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"];
        $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetRegisterMexico"; //Set OpenL Url
        $aResponse = callCurlOpenL($openLUrlCurl, $dataSend); //Call Curl OpenL
        $response = $aResponse["DATA"];//Set response
        $err = $aResponse["ERROR"]; //Set error   
        //Check if there is a code
        if ($response) {
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
            $reinsurerSlip .= "<tr><td style='width:35%;text-align:right;' colspan='3'></td>";
            $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $response . "</td></tr>";
        } else  {
            $noCode .= "The " . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . " has no Register Code in Mexico<br/>"; 
            $noCodeValidate = "NO";
        }
    } else {
        $reinsurerSlip .= "<td style='width:65%;text-align:left;'>" . $reinsurer[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td></tr>";
    } 
}
$reinsurerListTable .= "</table>";
$reinsurerSlip .= "</table>";
$reinsurersTableEstarSeguros .= "</table>";
/*********************** Set Slip Reinsurer Panama *********************************/
$data["YQP_REINSURER_SIGNATURES"] = htmlentities($reinsurerSlip);
$data["YQP_SLIP_REINSURER_PANAMA"] = $data["YQP_REASSURED_CEDENT"]["LABEL"];
$data["YQP_SLIP_COUNTRY_PANAMA"] = $data["YQP_COUNTRY_BUSINESS"];
$data["YQP_REINSURERS_LIST_TABLE"] = $reinsurerListTable;
$data["YQP_REINSURER_SIGNATURES_ESTAR_SEGUROS"] = htmlentities($reinsurersTableEstarSeguros);

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
    if (empty($idFile) || $nameFile === null || $nameFile === '') {
        return false;
    }
    //Create file in temporary folder
    $tempFolder = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    $filePath = $tempFolder . DIRECTORY_SEPARATOR . $nameFile;
    $file = fopen($filePath, 'w');
    if ($file === false) {
        return false;
    }
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
function getValueVariableText(
    $dataValidation,
    $dataRequest,
    $rowValidation,
    $nameVarText,
    $nameVariable,
    $nameText
) {
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
function evaluateCondition(
    $dataEvaluate,
    $dataRequest,
    $conditionVarText,
    $conditionVariable,
    $conditionText,
    $conditionSign,
    $conditionOperator
) {
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
*
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
            // Extract values from collection variable
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
                //Change the number format, to two decimals and with a thousand separator the comma
                $valueCurrency = number_format($valueCurrency, 2, ".", ",");
            }
            $data[$varCollection] = $valueCurrency;
            break;

        case "CURRENT_DATE":
            $data[$varCollection] = date($templateVariableCollection['data']['UTP_FORMAT_DATE']);
            //replace english words to document language format generally date format g:ia \o\n l jS F Y'
            $data[$varCollection] = changeDateFormat($data[$varCollection], $data['YQP_LANGUAGE']);
            break;

        case "CHANGE_DATE":
            $dataInfo = convertVariablesToData($templateVariableCollection['data']['UTP_VARIABLE_DATE']);
            eval("\$anCollection = \$data['" . $dataInfo . "'];");
            $anCollection = new DateTime($anCollection);
            $data[$varCollection] = $anCollection->format($templateVariableCollection['data']['UTP_FORMAT_DATE']);
            //replace english words to document language format generally date format g:ia \o\n l jS F Y'
            $data[$varCollection] = changeDateFormat($data[$varCollection], $data['YQP_LANGUAGE']);
            break;
        case "MATH":
            if ($templateVariableCollection['data']['UTP_MATH_FUNCTION']) {
                $mathOperation = "";
                //Concat mathOperation with number a operator
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
            //Obtain XML of a html variable
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
                // In case there are no validation variables
                if (!empty($loop['UTP_CONCAT_VALIDATION'])) {
                    $concatVariableInArray = evaluateCondition($loop['UTP_CONCAT_VALIDATION'], $data, 'UTP_COR_VARTEXT', 'UTP_COR_VARIABLE', 'UTP_COR_TEXT', 'UTP_COR_EVALUATE', 'UTP_COR_FINAL_EVALUATE');
                } else {
                    $concatVariableInArray = true;
                }
                if ($concatVariableInArray) {
                    //Verify First Variable
                    if (!empty($loop['UTP_CONCAT_VARIABLE'])) {
                        try {
                            $variableTemp1 = "\$data['" . convertVariablesToData($loop['UTP_CONCAT_VARIABLE']) . "']";
                            eval("\$dataVariable1 = ($variableTemp1) ?? null; ");
                            // The array value is not added to the array if it is empty
                            if (!is_null($dataVariable1) && !empty($dataVariable1)) {
                                $arrayValues[] = $dataVariable1;
                            }
                        } catch (ParseError $e) {
                        }
                    } else {
                        $dataText = stripslashes(str_replace('""', '', $loop['UTP_CONCAT_TEXT']));
                        // The array value is not added to the array if it is empty
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
                        //Validate array with data
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

                    //The result is true
                    if ($resultCondition == true && !empty($slipLogo['UTP_LOGO_UPLOAD'])) {
                        $logoId = $slipLogo['UTP_LOGO_UPLOAD']['id'];
                        $logoName = $slipLogo['UTP_LOGO_UPLOAD']['name'];
                        $fileLogo = callGetCurlFiles($logoId, $logoName);
                        //$templateProcessor->setImageValue($varCollection, $fileLogo);
                        $arrayImagesTrue[] = [
                            'type' => 'image',
                            'path' => $fileLogo
                        ];
                    }
                }

                // Set Data with last arrayImagesTrue Varaible
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
//Convert Additional Information to HTML for Slip and fix it if it is corrupted
if (empty($data["YQP_CLAIM_ADDITIONAL_INFORMATION"])) {
    $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
    $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = "";
} else {
    $cleanedAdditionalInfo = cleanTextAreaRichTextValue($data["YQP_CLAIM_ADDITIONAL_INFORMATION"], "YES");
    $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["ORIGINAL_VALUE_CLEANED"];
    $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = $cleanedAdditionalInfo["SLIP_VALUE_PARSED"];
}
//Encode subjective email
$data['YQP_SUBJECTIVES_GUARANTEE_EMAIL'] = htmlentities($data['YQP_SUBJECTIVES_GUARANTEE']);
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
    $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Generate Slip";
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
                            $file = $apiInstance->getRequestFilesById($template['data']['UTP_TE_REQUEST_ID'], $template['data']['UTP_TE_DOCUMENT_ID']);
                            if (!is_null($valueVariableToLoop) && !is_null($variableLoopResponse)) {
                                for ($l = 0; $l <= count($valueVariableToLoop) - 1; $l++) {
                                    $variableToLoopCollectionWithReplace= str_replace("#", $l, $variableToLoopCollection);
                                    $variableLoopConvert = convertVariablesToData($variableToLoopCollectionWithReplace);
                                    eval("\$valueVariableLoopConvert = \$data['" . $variableLoopConvert . "'];");
                                    // Create different template files with the loop
                                    $filePathNameTmp = $file->getPathname();
                                    $newFilePathNameTmp = $filePathNameTmp . $l;
                                    copy($filePathNameTmp, $newFilePathNameTmp);
                                    $filePathNameTmp = $newFilePathNameTmp;
                                    $inFile[] = $filePathNameTmp;
                                    $dataVariableTemplateLoop[$filePathNameTmp][$l] = [
                                        "YQP_REINSURER_SHARE_PERCENTAGE_SLIP" => $valueVariableToLoop[$l]["YQP_SHARE_PERCENTAGE"]
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

        //-----------------------------------------Template Processor Set Values ------------------------------
        if (count($inFile) > 0) {
            $keysTemplatesLoop = array_keys($dataVariableTemplateLoop); // Extract the keys of the array of variables which should be repeated
            $l = 0; // Counter for loop  - Loop
            foreach ($inFile as $keyOutFile => $outFile) {
                //Replace the values of the variables which should be repeated
                if (in_array($outFile, $keysTemplatesLoop)) { // Verify if the template which should be duplicated is part of the list of templates
                    if(!is_null($dataVariableTemplateLoop[$outFile][$l])) {
                        $keysLoop = array_keys($dataVariableTemplateLoop[$outFile][$l]);
                        foreach ($keysLoop as $key) {
                            // Add the value for the template in $data
                            $data[$key] = $dataVariableTemplateLoop[$outFile][$l][$key];
                            foreach ($templateVariablesCollection['data'] as $templateVariableCollection) {
                                if ($templateVariableCollection['data']['UTP_TABLE_VARIABLE_NAME'] == $key || $templateVariableCollection['data']['UTP_STRING_VARIABLE'] == $key) {
                                    changeFormatTemplateVariable($data, $templateVariableCollection); // Change Format of the variable from UTP_TEMPLATE_VARIABLES config
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

            $nameDocument = $data["YQP_" . $documentGenerate . "_DOCUMENT_NAME"];
            $dm->merge($inFile, "/tmp/" . $nameDocument . ".docx");
            //Save document merged with name Document
            $processRequestIdForte = $data['_request']['id'];
            $outFile = "/tmp/" . $nameDocument . ".docx";
            $dataName = 'YQP_DOWNLOAD_' . $documentGenerate . '_DOCUMENT_CLIENT';
            $newFile = $apiInstance->createRequestFile($processRequestIdForte, $dataName, $outFile);
            $dataName = 'YQP_DOWNLOAD_' . $documentGenerate . '_DOCUMENT';
            //--------------------------------- Create Download Files Button -----------------------------------------
            $slipFiles = $apiHost . '/requests/' . $data['_request']['id'] . '/files';
            $responseRequestFiles = callGetCurl($slipFiles);
            //foreach File on Request generate an URL download and URL_NAME_BUTTON
            foreach ($responseRequestFiles['data'] as $responseRequestFile) {
                //return $responseRequestFiles['data'];
                $variableName = $responseRequestFile['custom_properties']['data_name'];
                $fileName = $responseRequestFile['file_name'];
                if ($variableName == "YQP_DOWNLOAD_SLIP_DOCUMENT_CLIENT") {
                    $data[$dataName . '_URL'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $responseRequestFile['id'];
                    $data[$dataName . '_URL_NAME_BUTTON'] = $fileName;
                }
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
            $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Prepare Documentation";
        }
    } else {
        $data['YQP_ERROR_SLIP'] = "ERROR";
        $data['YQP_ERROR_MESSAGE_SLIP'] = "There are no templates for this data";
        //Forte Errors Screen
        $data['FORTE_ERRORS']['FORTE_ERROR_LOG'] = $data['YQP_ERROR_MESSAGE_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_BODY'] = $data['YQP_ERROR_SLIP'];
        $data['FORTE_ERRORS']['FORTE_ERROR_DATE'] = date('m-d-Y');
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_ID'] = "node_122";
        $data['FORTE_ERRORS']['FORTE_ERROR_ELEMENT_NAME'] = "YACHT - Prepare Documentation";
    }

}
return $data;