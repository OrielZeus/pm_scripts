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
    //Create file in temporary folder
    $tempFolder = trim(sys_get_temp_dir() . PHP_EOL);
    $filePath = $tempFolder . DIRECTORY_SEPARATOR . $nameFile;
    $file = fopen($filePath, 'w');
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
//Create Slip Adobe
$pathSlipQuoteOfficialAdobe = callGetCurlFiles($idFileQuoteAdobe, $dataClientInfo['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_ADOBE_NAME_BUTTON']);
$dataNameAdobe = 'YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_ADOBE';
$newSlipQuoteAdobe = $apiInstance->createRequestFile($processRequestIdForte, $dataNameAdobe, $pathSlipQuoteOfficialAdobe);

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