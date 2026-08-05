<?php 
/***************************************************************  
 * Get dropdowns options with OpenL connection and Quote Number
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 **************************************************************/

/** 
 * Call Processmaker API
 *
 * @param (string) $url 
 * @return (Array) $responseCurl 
 *
 * by Helen Callisaya
 */
function callGetCurl($url, $method, $json_data)
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ));
    $responseCurl = curl_exec($curl);
    $responseCurl = json_decode($responseCurl, true);
    curl_close($curl);
    
    return $responseCurl;
}
//Set variable of return
$dataDropdowns = array();

//Validate if data is copied from a request (CLONE)
if ($data['YQP_IN_PROGRESS_CONTINUE'] == "CLONE" && empty($data['YQP_CLONED_SUCCESS'])) {
    $requestClone = "";
    $loopRequestClone = $data['YQP_REQUEST_LIST_TO_CLONE'] ?? [];
    foreach ($loopRequestClone as $iClone) {
        if ($iClone['RES_YQP_SELECT_CLONE'] == true) {
            $requestClone = $iClone['RES_REQUEST_ID'];
            break;
        }
    }
    $keysToClean = [
        "YQP_ADOBE_WORFLOW_LIST",
        "YQP_ADOBE_AGREEMENT_ID",
        "YQP_ADOBE_DECLINED_DATE",
        "YQP_ADOBE_USER_DECLINED",
        "YQP_ADOBE_WORKFLOW_LIST",
        "YQP_ADOBE_DOCUMENTS_OPTIONS",
        "YQP_ADOBE_WORKFLOW_DOCUMENTS_UPLOAD",
        "YQP_ADOBE_EMAIL_DECLINED",
        "YQP_MONTH_SENT_ADOBE_REPORT",
        "YQP_ADOBE_WORKFLOW_DOCUMENTS",
        "YQP_ADOBE_DOCUMENTS_GENERATED",
        "YQP_ADOBE_DOCUMENTS_REQUIRED",
        "YQP_ADOBE_AGREEMENT_DOCUMENTS",
        "YQP_SIGN_ADOBE"
    ];

    if (!empty($requestClone)) {
        $urlApi = getenv('API_HOST') . '/requests/' . $requestClone . '?include=data';
        $dataRequestJson = callGetCurl($urlApi, "GET", "");

        // Asegurar que 'data' existe antes de limpiar
        if (isset($dataRequestJson['data']) && is_array($dataRequestJson['data'])) {
            foreach ($keysToClean as $key) {
                if (isset($dataRequestJson['data'][$key])) {
                    // Si es un array, asignar []
                    if (is_array($dataRequestJson['data'][$key])) {
                        $dataRequestJson['data'][$key] = [];
                    } else {
                        // Si es string u otro tipo, asignar ""
                        $dataRequestJson['data'][$key] = "";
                    }
                }
            }
        }

        $dataDropdowns = $dataRequestJson['data'];
        $dataDropdowns['YQP_CLONED_SUCCESS'] = true;
    }
}

//Set User Process
$urlUserId = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$responseUserId = callGetCurl($urlUserId, "GET", ""); 
$dataDropdowns['YQP_USER_FULLNAME'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'];

//validate if there is a comment from the approver
$comments = $data['YQP_APPROVER_COMMENTS'];
if (strlen($comments) > 0) {
    $dataDropdowns['YQP_APPROVER_COMMENTS_CHANGE'] = $data['YQP_APPROVER_COMMENTS'];
    $dataDropdowns['YQP_APPROVER_COMMENTS_VISIBLE'] = "YES";
} else {
    $dataDropdowns['YQP_APPROVER_COMMENTS_CHANGE'] = "";
    $dataDropdowns['YQP_APPROVER_COMMENTS_VISIBLE'] = "NO";
}
//Clean approver comments
$dataDropdowns['YQP_APPROVER_COMMENTS'] = "";

//Set variable as Submit to do required all fields
$dataDropdowns['YQP_SUBMIT_SAVE'] = "SUBMIT";

//Set variable Status as PENDING
$dataDropdowns['YQP_STATUS'] = "PENDING";

//Get URL Connection openL
$openLUrl = getenv('OPENL_CONNECTION');

//Set parameters to quote number functionality
$requestID = $data['_request']["id"];
$processID = $data['_request']["process_id"];
$collectionRequestID = getenv('FORTE_QUOTE_NUMBER');
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');

/*
* Execute Curl on Collection
*
* @param (String) $collectionID
* @param (String) $apiHost
* @param (String) $apiToken
* @param (String) $pmql
* @param (String) $curlType
* @param (String) $dataPost
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function executeCurlCollection($collectionID, $apiHost, $apiToken, $pmql, $curlType, $dataPost)
{
    //Curl init
    $curl = curl_init();
    //Curl to the End point
    switch ($curlType) {
	    case 'GET':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . "/collections/" . $collectionID . "/records" . $pmql,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiToken
                ),
            ));
		break;
	    case 'POST':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . '/collections/' . $collectionID . '/records',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataPost,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: text/plain'
                ),
            ));
		break;
    }
    //Set response
    $response = curl_exec($curl);
    curl_close($curl);
    //Response Json decode
    $response = json_decode($response, true);

    //Return data
    return $response["data"];
}

/*
* Get Open Functions
*
* @param (String) $url
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function callCurlOpenL($url)
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

/*********************** Get values Countries *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetCountries";

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
    $dataDropdowns['PM_OPEN_COUNTRIES'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_COUNTRIES'] = $aOptions;
    }
}

/*********************** Get values Currencies *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetCurrencies";

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
    $dataDropdowns['PM_OPEN_CURRENCIES'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_CURRENCIES'] = $aOptions;
    }
}

/*********************** Get values Units *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetUnits";

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
    $dataDropdowns['PM_OPEN_UNITS'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_UNITS'] = $aOptions;
    }
}

/*********************** Get values Location Moorning Ports *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetLocationMooringPort";

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
    $dataDropdowns['PM_OPEN_LOCATION_MOORING_PORTS'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_LOCATION_MOORING_PORTS'] = $aOptions;
    }
}

/*********************** Get values Vessage Usage *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetVessageUsage";

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
    $dataDropdowns['PM_OPEN_VESSAGE_USAGE'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_VESSAGE_USAGE'] = $aOptions;
    }
}

/*********************** Get values Brokers *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetBrokers";

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
    $dataDropdowns['PM_OPEN_BROKERS'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_BROKERS'] = $aOptions;
    }
}

/*********************** Get values Cedents *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetCedents";

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
    $dataDropdowns['PM_OPEN_CEDENTS'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_CEDENTS'] = $aOptions;
    }
}

/*********************** Get values Reinsurer Name *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetReinsurers";

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
    $dataDropdowns['PM_OPEN_REINSURERS'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_REINSURERS'] = $aOptions;
    }
}

/*********************** Get values Type of Yacht *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetYachtTypes";

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
    $dataDropdowns['PM_OPEN_YACHT_TYPES'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_YACHT_TYPES'] = $aOptions;
    }
}

/*********************** Get options deductible P&I *********************************/
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetPIDeductibles";

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
    $dataDropdowns['PM_OPEN_PI_DEDUCTIBLES'] = "cURL Error #:" . $err;
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
        $dataDropdowns['PM_OPEN_PI_DEDUCTIBLES'] = $aOptions;
    }
}

//If there is a bug we need to clean to error 
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => ''
];
$dataDropdowns['FORTE_ERRORS'] = $requestError;

/*********************** Set Request Quote Number *********************************/
if ($processID == getenv("FORTE_ID_YACHT")) {
    //Validate if the request number for this process and request already exist
    $pmqlRequest = "?pmql=" . urlencode("(data.YQP_PROCESS_ID = " . $processID . " AND data.YQP_REQUEST_ID = " . $requestID . ")");
    $validateQuote = executeCurlCollection($collectionRequestID, $apiHost, $apiToken, $pmqlRequest, "GET", array());
    //Validate if response is null
    if ($validateQuote == null || $validateQuote == '') {
        $validateQuote = array();
    }
    if (count($validateQuote) > 0) {
        //The Quote number already exist
        $dataDropdowns['YQP_QUOTE_NUMBER'] = $validateQuote[0]["data"]["YQP_REQUEST_UNIQUE_ID"];
    } else {
        //Validate if there is values for this process
        //$pmqlRequest = "?pmql=(data.YQP_PROCESS_ID = " . $processID . ")&order_direction=desc&order_by=data.YQP_REQUEST_UNIQUE_ID";
        $pmqlRequest = "?pmql=" . urlencode("(data.YQP_PROCESS_ID = " . $processID . ")");
        $pmqlRequest .= "&order_direction=desc&order_by=id";
        $validateProcess = executeCurlCollection($collectionRequestID, $apiHost, $apiToken, $pmqlRequest, "GET", array());
        //Validate if response is null
        if ($validateProcess == null || $validateProcess == '') {
            $validateProcess = array();
        }
        if (count($validateProcess) > 0) {
            //Add one number
            $requestUid = $validateProcess[0]["data"]["YQP_REQUEST_UNIQUE_ID"];
            $requestUid = ($requestUid * 1) + 1;
            //Set values to CURL POST
            $dataPost = array(
                "data" => array(
                    "YQP_PROCESS_ID" => $processID,
                    "YQP_REQUEST_ID" => $requestID,
                    "YQP_REQUEST_UNIQUE_ID" => $requestUid
                )
            );
            //Encode values to Post 
            $dataPost = json_encode($dataPost);
            //Post data
            $pmqlRequest = "";
            $validateProcess = executeCurlCollection($collectionRequestID, $apiHost, $apiToken, $pmqlRequest, "POST", $dataPost);
    
            //Set Quote Number
            $dataDropdowns['YQP_QUOTE_NUMBER'] = $requestUid;
        } else {
            //Add the first record
            $requestUid = 1;
            //Set values to CURL POST
            $dataPost = array(
                "data" => array(
                    "YQP_PROCESS_ID" => $processID,
                    "YQP_REQUEST_ID" => $requestID,
                    "YQP_REQUEST_UNIQUE_ID" => $requestUid
                )
            );
            //Encode values to Post 
            $dataPost = json_encode($dataPost);
            //Post data
            $pmqlRequest = "";
            $validateProcess = executeCurlCollection($collectionRequestID, $apiHost, $apiToken, $pmqlRequest, "POST", $dataPost);
    
            //Set Quote Number
            $dataDropdowns['YQP_QUOTE_NUMBER'] = $requestUid;
        }
    }
}

//Validate If process is yacht Endorsement
$dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "YES";
$dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "YES";
if ($data['_request']['process_id'] == getenv('FORTE_ID_ENDORSEMENT')) {
    switch ($data['END_TYPE_ENDORSEMENT']) {
        case "Change Premium":
            $dataDropdowns['YQP_DATA_EXISTS'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "NO";
            $dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "YES";
            break;
        case "Informative and Change Premium":
            $dataDropdowns['YQP_DATA_EXISTS'] = "NO";
            $dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "YES";
            break;
        case "Informative":
            $dataDropdowns['YQP_DATA_EXISTS'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_INFORMATIVE'] = "YES";
            $dataDropdowns['END_SHOW_FIELD_PREMIUM'] = "NO";
            break;            
    }
}
//Save to Gestion Solicitudes Collection
$searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
$searchRequest = callGetCurl($searchRequestUrl, "GET", "");
$dataSave = array();
$dataSave['FORTE_REQUEST'] = $data['_request']['id'];
$dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
$dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
$dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
$dataSave['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
$dataSave['YQP_STATUS'] = $data['YQP_STATUS'];
$dataSave['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
$dataSave['YQP_FORTE_ORDER'] = empty($data['YQP_FORTE_ORDER']) ? "" : $data['YQP_FORTE_ORDER'];
//Check Process Type
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
} else {
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
    $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    $dataSave['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
}
//Validate if the request exists
if (count($searchRequest["data"]) == 0) {
    $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records';
    $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
} else {
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
    $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
}
$dataDropdowns['YQP_USER_ID'] = $data['_request']['user_id'];
//Return all Values
return $dataDropdowns;