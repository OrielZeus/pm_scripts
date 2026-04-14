<?php 
/*  
 * Set Client Information to send via email
 * by Helen Callisaya
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
$approveSrUnderwriter = empty($data['END_APPROVE_CONFIRMATION_UPLOAD_ENDORSEMENT']) ? "NO" : $data['END_APPROVE_CONFIRMATION_UPLOAD_ENDORSEMENT'];
if ($approveSrUnderwriter == "YES") {
    //Slip Manual Client
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $data['END_UPLOAD_CONFIRMATION_ENDORSEMENT']; 
    $slipFiles = getenv('API_HOST') . '/files/' . $data['END_UPLOAD_CONFIRMATION_ENDORSEMENT'];
    $responseRequestFiles = callGetCurl($slipFiles);
    $fileName = $responseRequestFiles['file_name'];
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_NAME_BUTTON'] = $fileName;
    $idFileQuote = $data['END_UPLOAD_CONFIRMATION_ENDORSEMENT'];

    //Slip Manual Adobe
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_ADOBE'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $data['END_UPLOAD_CONFIRMATION_ENDORSEMENT_ADOBE']; 
    $slipFiles = getenv('API_HOST') . '/files/' . $data['END_UPLOAD_CONFIRMATION_ENDORSEMENT_ADOBE'];
    $responseRequestFiles = callGetCurl($slipFiles);
    $fileName = $responseRequestFiles['file_name'];
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_ADOBE_NAME_BUTTON'] = $fileName;
    $idFileQuoteAdobe = $data['END_UPLOAD_CONFIRMATION_ENDORSEMENT_ADOBE'];    
} else {
    //Slip Generate Client
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL'] = $data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_CLIENT_URL'];
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_NAME_BUTTON'] = $data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_CLIENT_URL_NAME_BUTTON'];
    $pos = strrpos($data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_CLIENT_URL'], "/");
    $idFileQuote = rtrim(substr($data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_CLIENT_URL'], $pos + 1));

    //Slip Generate Adobe
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_ADOBE'] = $data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_ADOBE_URL'];
    $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_ADOBE_NAME_BUTTON'] = $data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_ADOBE_URL_NAME_BUTTON'];
    $pos = strrpos($data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_ADOBE_URL'], "/");
    $idFileQuoteAdobe = rtrim(substr($data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_ADOBE_URL'], $pos + 1));    
}

$processRequestIdForte = $data['_request']['id'];
$apiInstance = $api->requestFiles();
//Create Slip Client
$pathSlipQuoteOfficial = callGetCurlFiles($idFileQuote, $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_NAME_BUTTON']);
$dataName = 'END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT';
$newSlipQuote = $apiInstance->createRequestFile($processRequestIdForte, $dataName, $pathSlipQuoteOfficial);
//Create Slip Adobe
$pathSlipQuoteOfficialAdobe = callGetCurlFiles($idFileQuoteAdobe, $dataClientInfo['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_ADOBE_NAME_BUTTON']);
$dataNameAdobe = 'END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_ADOBE';
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
            if ($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name'] != null || $dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name'] != "") {
                //Get content document
                $fileAttach = callGetCurlFiles($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['id'], $dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name']);
                //Attach document
                $message->attach(Swift_Attachment::fromPath($fileAttach)->setFilename($dataAttach[$i]['FORTE_EMAIL_ATTACH_UPLOAD']['name']));
            }
            //Add document name to the list of additional documents
            $nameAttachments .= "<li>" . $dataAttach[$i]['FORTE_EMAIL_ATTACH_NAME'] . "</li>";
        }
    }
    //--------------------------------------------Request attachments entered by the user------------------------------------
    //Validate request attachment array is null
    if ($data['END_CONFIRMATION_DOCUMENTS'] == null) {
        $data['END_CONFIRMATION_DOCUMENTS'] = array();
    }
    for ($i = 0; $i < count($data['END_CONFIRMATION_DOCUMENTS']); $i++) {
        if( $data['END_CONFIRMATION_DOCUMENTS'][$i]['END_CONFIRMATION_UPLOAD_DOCUMENT'] != null) {
            //Get Document Name
            $urlDocument = getenv('API_HOST') . '/files/' . $data['END_CONFIRMATION_DOCUMENTS'][$i]['END_CONFIRMATION_UPLOAD_DOCUMENT'];
            $responseDocument = callGetCurl($urlDocument);
            //Get Document Content
            $fileAttach = callGetCurlFiles($data['END_CONFIRMATION_DOCUMENTS'][$i]['END_CONFIRMATION_UPLOAD_DOCUMENT'], $responseDocument["file_name"]);
            //Attach document
            $message->attach(Swift_Attachment::fromPath($fileAttach)->setFilename($responseDocument["file_name"]));
            //Add document name to the list of additional documents
            $nameAttachments .= "<li>" . $data['END_CONFIRMATION_DOCUMENTS'][$i]['END_CONFIRMATION_ATTACHMENT_NAME'] . "</li>";
        }
    }
    //---------------------------Replacement of values on email variables--------------------------------------
    //Attach additional documents for cotization slip
    $data['YQP_ADDITIONAL_DOCUMENTS'] = "<ul>" . $nameAttachments . "</ul>";
    $data['YQP_SUBJECTIVES_GUARANTEE_EMAIL'] = html_entity_decode($data['YQP_SUBJECTIVES_GUARANTEE_EMAIL']);
    $data['YQP_LOGO_IMAGE'] = $logoSign;
    //$data['END_ENDORSEMENT_EMAIL_DESCRIPTION'] = htmlentities($data['END_ENDORSEMENT_EMAIL_DESCRIPTION']);
    $data['END_ENDORSEMENT_EMAIL_DESCRIPTION'] = html_entity_decode($data['END_ENDORSEMENT_EMAIL_DESCRIPTION']);
    //Replace variables in Email body
    $emailBody = replaceVariablesString($emailBody, $data);
    //---------------------------------Send Email--------------------------------------------------------------
    // Set a "subject"
    $message->setSubject($subject);
    // Set the "From address"
    $message->setFrom([$accountFrom => $nameFrom]);
    // Set the "To address"
    $message->addTo($data['END_ENDORSEMENT_EMAIL'], $data['END_ENDORSEMENT_EMAIL_NAME']);
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
    if ($data['END_ENDORSEMENT_CONFIRMATION_EMAIL_CC'] == null) {
        $data['END_ENDORSEMENT_CONFIRMATION_EMAIL_CC'] = array();
    }
    for ($z = 0; $z < count($data['END_ENDORSEMENT_CONFIRMATION_EMAIL_CC']); $z++) {
        if ($data['END_ENDORSEMENT_CONFIRMATION_EMAIL_CC'][$z]['END_EMAIL_CC'] != null || $data['END_ENDORSEMENT_CONFIRMATION_EMAIL_CC'][$z]['END_EMAIL_CC'] != '') {
            $message->addCc($data['END_ENDORSEMENT_CONFIRMATION_EMAIL_CC'][$z]['END_EMAIL_CC']);
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
        //Set error in screen
        $requestError = array();
        $requestError['FORTE_ERROR_LOG'] = "Error Send Email";
        $requestError['FORTE_ERROR_BODY'] = "Error..." . $mail->ErrorInfo;
        $requestError['FORTE_ERROR_DATE'] = date('m-d-Y');
        $requestError['FORTE_ERROR_ELEMENT_ID'] = "node_128";
        $requestError['FORTE_ERROR_ELEMENT_NAME'] = "END - Send Email Quote and Endorsement";
        //Save Forte Errors
        $dataClientInfo['FORTE_ERRORS'] = $requestError;        
    } else {
        $dataClientInfo['YQP_STATUS_SEND_MAIL'] = "OK";
        $dataClientInfo['YQP_STATUS_MESSAGE_SEND_MAIL'] = "Message sent successfully";
    }
} else {
    $dataClientInfo['YQP_STATUS_SEND_MAIL'] = "ERROR";
    $dataClientInfo['YQP_STATUS_MESSAGE_SEND_MAIL'] = "There is no email delivery configured";
    //Set error in screen
    $requestError['FORTE_ERROR_LOG'] = "Error Send Email";
    $requestError['FORTE_ERROR_BODY'] = "There is no email delivery configured";
    $requestError['FORTE_ERROR_DATE'] = date('m-d-Y');
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "node_128";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "END - Send Email Quote and Endorsement";
    //Save Forte Errors
    $dataClientInfo['FORTE_ERRORS'] = $requestError;    
}

//Verify Errors
if ($dataClientInfo['FORTE_ERRORS'] == null) {
    $dataClientInfo["YQP_SUBMIT_SAVE"] = "SUBMIT";
} else {
    $dataClientInfo["YQP_SUBMIT_SAVE"] = "ERROR";
}

//Clean Variables
//$dataClientInfo["YQP_SUBMIT_SAVE"] = "SUBMIT";

//Return all Values
return $dataClientInfo;