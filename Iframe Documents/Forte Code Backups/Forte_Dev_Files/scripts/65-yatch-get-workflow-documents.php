<?php 
/*********************************
* YATCH - Get Workflow Documents
*
* by Cinthia Romero
* modified by Helen Callisaya
*********************************/
/**
 * Call Curl
 * 
 * @param string $endpointUrl
 * @param string $method
 * @param array $postFields
 * @param string $bearer
 * @param string $contentType
 * @param string $userEmail
 * @return array $reponseCurl
 *
 * by Cinthia Romero
 */ 
function callCurl($endpointUrl, $method, $postFields, $bearer, $contentType, $userEmail)
{
    $curl = curl_init();
    $httpHeaders = array(
        "Authorization: Bearer " . $bearer,
        "Content-Type: " . $contentType,
        "cache-control: no-cache"
    );
    if ($userEmail != '') {
        $httpHeaders[] = 'x-api-user: email:' . $userEmail;
    }
    curl_setopt_array($curl, array(
        CURLOPT_URL => $endpointUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => $httpHeaders
    ));
    $reponseCurl = curl_exec($curl);
    $errorCurl = curl_error($curl);
    $reponseCurl = json_decode($reponseCurl, true);
    curl_close($curl);
    return $reponseCurl;
}

/**
 * Get Base Uri
 * 
 * @param string $adobeSignToken
 * @param string $userEmail
 * @return array $baseUriResponse
 * 
 * by Cinthia Romero
 */
function getBaseUri($adobeSignToken, $userEmail)
{
    $url = 'https://api.na3.adobesign.com/api/rest/v6/baseUris';
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    if (!empty($curlResponse["apiAccessPoint"])) {
        $baseUriResponse = array(
            "success" => true,
            "errorMessage" => "",
            "baseUri" => $curlResponse["apiAccessPoint"]
        );
    } else {
        $baseUriResponse = array(
            "success" => false,
            "errorMessage" => $curlResponse["message"],
            "baseUri" => ""
        );
    }
    return $baseUriResponse;
}

/**
 * Get Workflow Definition
 * 
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param int $workflowUid
 * @param string $userEmail
 * @return array $curlResponse
 * 
 * by Cinthia Romero
 */
function getWorkflowDefinition($adobeBaseUri, $adobeSignToken, $workflowUid, $userEmail) {
    $url = $adobeBaseUri . 'api/rest/v6/workflows/' . $workflowUid;
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    return $curlResponse;
}

/**
 * Search the array for keywords 
 *
 * @param (string) $string
 * @param (array) $aFilter
 * @return (string) $search
 *
 * by Helen Callisaya
 */
function searchFilter($string, $aFilter)
{
    $search = 0;
    $nameDocument = '';
    $variableDocument = '';
    for ($i = 0; $i < count($aFilter); $i++) {
        if (strpos(strtoupper($string), strtoupper($aFilter[$i]['FORTE_TYPE_DOCUMENT_KEYWORD'])) !== false) {
            $search = 1;
            $nameDocument = $aFilter[$i]['FORTE_TYPE_DOCUMENT_KEYWORD'];
            $variableDocument = $aFilter[$i]['FORTE_TYPE_DOCUMENT_VARIABLE'];
        }
    }
    return [$search, $nameDocument, $variableDocument];
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

//Server Token
$serverHost = getenv('API_HOST');
$serverToken = getenv('API_TOKEN');
//Get Integrator Key
$adobeSignToken = getenv('ADOBE_TOKEN');
//Get current user email
$urlEmail = $serverHost . '/users/' . $data['YQP_USER_ID'];
$curlResponse = callCurl($urlEmail, 'GET', array(), $serverToken, 'application/json', "");
$currentUserEmail = $curlResponse['email'];

$agreementCreationResponse = "";
$workflowDocuments = array();
if ($adobeSignToken) {
    //Check Base Uri
    $baseUriResponse = getBaseUri($adobeSignToken, $currentUserEmail);
    if ($baseUriResponse["success"]) {
        $adobeBaseUri = $baseUriResponse["baseUri"];
        $workflowUid = $data["YQP_ADOBE_WORKFLOW_SELECTED"];

        //Get Workflows List
        $workflowCharacteristics = getWorkflowDefinition($adobeBaseUri, $adobeSignToken, $workflowUid, $currentUserEmail);
        if (!empty($workflowCharacteristics["fileInfos"])) {
            foreach ($workflowCharacteristics["fileInfos"] as $adobefile) {
                $workflowDocuments[] = array(
                    "LABEL" => $adobefile["label"],
                    "REQUIRED" => $adobefile["required"]
                );
            }
        }
    }
}
//Get keyword records
$urlTypeCollection = getenv('API_HOST') . '/collections/' . getenv('FORTE_TYPE_COLLECTION') . '/records?include=data&pmql=(data.FORTE_TYPE_DOCUMENT_PROCESS="' . $data['YQP_PROCESS_ID'] .'")';
$responseTypeCollection = callCurl($urlTypeCollection, 'GET', array(), getenv('API_TOKEN'), 'application/json', "");
$filterWord = array();

foreach ($responseTypeCollection['data'] as $typeDocument) {
    //$conditionList = ($typeDocument['data']['FORTE_TYPE_DOCUMENT_CONDITIONS'] == null ? $typeDocument['data']['FORTE_TYPE_DOCUMENT_CONDITIONS'] : array());
    if ($typeDocument['data']['FORTE_TYPE_DOCUMENT_CONDITIONS'] && count($typeDocument['data']['FORTE_TYPE_DOCUMENT_CONDITIONS']) > 0) {
        $a = 1;
        $conditionList = $typeDocument['data']['FORTE_TYPE_DOCUMENT_CONDITIONS'];
        for ($i = 0; $i < count($conditionList); $i++) {
            $valueVariableText1 = getValueVariableText($data, $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_VARTEXT1'], $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_VARIABLE1'], $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_TEXT1']);
            $valueVariableText2 = getValueVariableText($data, $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_VARTEXT2'], $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_VARIABLE2'], $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_TEXT2']);
            //Get condition operator
            $operator = "";
            if (($i + 1) < count($conditionList)) {
                $operator = $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_FINAL_EVALUATE'] . ' ';
            }
            $conditions .= "'" . $valueVariableText1 . "' " . $conditionList[$i]['FORTE_TYPE_DOCUMENT_CONDITIONS_EVALUATE'] . " '" . $valueVariableText2 . "' " . $operator;            
        }
        $evaluate = "\$resultCondition = $conditions;";
        $evaluate = @eval($evaluate);
        //Meets all conditions
        if ($resultCondition == true) {
            $filterWord[] = array(
                'FORTE_TYPE_DOCUMENT_KEYWORD' => $typeDocument['data']['FORTE_TYPE_DOCUMENT_KEYWORD'],
                'FORTE_TYPE_DOCUMENT_VARIABLE' => $typeDocument['data']['FORTE_TYPE_DOCUMENT_VARIABLE']
            );
        }
    } else {
        $filterWord[] = array(
            'FORTE_TYPE_DOCUMENT_KEYWORD' => $typeDocument['data']['FORTE_TYPE_DOCUMENT_KEYWORD'],
            'FORTE_TYPE_DOCUMENT_VARIABLE' => $typeDocument['data']['FORTE_TYPE_DOCUMENT_VARIABLE']
        );
    }
}

//Set array of required and options
$aDocumentsRequired = array();
$aDocumentsOptions = array();
$aDocumentsGenerated = array();

foreach ($workflowDocuments as $listDocument) {
    //Look for keywords in the name
    [$exist, $documentAdobe, $documentAdobeVariable] = searchFilter($listDocument['LABEL'], $filterWord);
    if ($exist != 1) {
        $aDocumentsOptions[] = $listDocument;
        //Validate if required
        if ($listDocument['REQUIRED'] == true) {
            $aDocumentsRequired[] = $listDocument;
        }
    } else {
       $aDocumentsGenerated[] = array(
           'LABEL' => $listDocument['LABEL'],
           'TYPE' => $documentAdobe,
           'VARIABLE' => $documentAdobeVariable
       );
    }
}
//Assign value to array to return
$dataListDocument['YQP_ADOBE_DOCUMENTS_REQUIRED'] = $aDocumentsRequired;
$dataListDocument['YQP_ADOBE_DOCUMENTS_OPTIONS'] = $aDocumentsOptions;
$dataListDocument['YQP_ADOBE_DOCUMENTS_GENERATED'] = $aDocumentsGenerated;

return $dataListDocument;