<?php 
/*****************************************
* YATCH - Get Workflows Adobe Sign
*
* by Cinthia Romero
* modified by Helen Callisaya
*****************************************/
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
 * Get Adobe Workflows
 *
 * @param string $adobeBaseUri
 * @param string $adobeSignToken
 * @param string $userEmail
 * @return array $curlResponse
 *
 * by Cinthia Romero
 */
function getAdobeWorkflows($adobeBaseUri, $adobeSignToken, $userEmail)
{
    $url = $adobeBaseUri . 'api/rest/v6/workflows';
    $curlResponse = callCurl($url, 'GET', array(), $adobeSignToken, 'application/json', $userEmail);
    return $curlResponse;
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
//Get Yacht process UID
$yachtProcessUid = getenv('FORTE_ID_YACHT');
//Define word to search into workflows names
$wordToSearch = "Slip";
if ($data['_request']['process_id'] != $yachtProcessUid) {
    $wordToSearch = "Endorsement";
}
$agreementCreationResponse = "";
$responseArray = array();
if ($adobeSignToken) {
    //Check Base Uri
    $baseUriResponse = getBaseUri($adobeSignToken, $currentUserEmail);
    if ($baseUriResponse["success"]) {
        $adobeBaseUri = $baseUriResponse["baseUri"];

        //Get Workflows List
        $workflowsListResponse = getAdobeWorkflows($adobeBaseUri, $adobeSignToken, $currentUserEmail);
        $workflowList= array();
        if (count($workflowsListResponse) > 0 && !empty($workflowsListResponse["userWorkflowList"])) {
            foreach ($workflowsListResponse["userWorkflowList"] as $workflow) {
                if (strpos($workflow["displayName"], "Yacht") !== false && strpos($workflow["displayName"], $wordToSearch) !== false) {
                    $workflowList[] = $workflow;
                }
            }
            if (count($workflowList) > 0) {
                $responseArray['YQP_OBTAIN_WORKFLOWS_ERROR'] = "";
                $responseArray['YQP_ADOBE_WORFLOW_LIST'] = $workflowList;
            } else {
                $responseArray['YQP_OBTAIN_WORKFLOWS_ERROR'] = "There is not any workflow configured for current process, please contact your system administrator";
                $responseArray['YQP_ADOBE_WORFLOW_LIST'] = $workflowList;
            }
        } else {
            if (empty($workflowsListResponse["userWorkflowList"])) {
                $responseArray['YQP_OBTAIN_WORKFLOWS_ERROR'] = "Your email account does not belong to any workflow, please contact your system administrator"; 
            } else {
                $responseArray['YQP_OBTAIN_WORKFLOWS_ERROR'] = "There is not any workflow configured, please contact your system administrator";
            }
            $responseArray['YQP_ADOBE_WORFLOW_LIST'] = array();
        }
    } else {
        $responseArray['YQP_OBTAIN_WORKFLOWS_ERROR'] = $baseUriResponse["errorMessage"];
        $responseArray['YQP_ADOBE_WORFLOW_LIST'] = array();
    }
} else {
    $responseArray['YQP_OBTAIN_WORKFLOWS_ERROR'] = "The adobe token was not configured, please contact your system administrator.";
    $responseArray['YQP_ADOBE_WORFLOW_LIST'] = array();
}
//Set variable as Submit to do required all fields
$responseArray['YQP_SUBMIT_SAVE'] = "SUBMIT";
//Valid existence of approval Sr. Underwriter
if ($data['_request']['process_id'] == $yachtProcessUid) {
    $approveSrUnderwriter = empty($data['YQP_APPROVE_QUOTE_UPLOAD_SLIP']) ? "NO" : $data['YQP_APPROVE_QUOTE_UPLOAD_SLIP'];       
    if ($approveSrUnderwriter == "YES") {
        $responseArray['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $data['YQP_UPLOAD_QUOTE_SLIP']; 
        $slipFiles = getenv('API_HOST') . '/files/' . $data['YQP_UPLOAD_QUOTE_SLIP'];
        $responseRequestFiles = callCurl($slipFiles, "GET", '', $serverToken, 'application/json', "");
        $fileName = $responseRequestFiles['file_name'];
        $responseArray['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = $fileName;
    } else {
        $responseArray['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL'] = $data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL'];
        //$responseArray['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_NAME'] =
        $responseArray['YQP_DOWNLOAD_REVIEW_SLIP_DOCUMENT_URL_NAME_BUTTON'] = $data['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_NAME_BUTTON'];
    }
} else {
    $approveSrUnderwriter = empty($data['END_APPROVE_DRAFT_UPLOAD_ENDORSEMENT']) ? "NO" : $data['END_APPROVE_DRAFT_UPLOAD_ENDORSEMENT'];       
    if ($approveSrUnderwriter == "YES") {
        $responseArray['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL'] = $_SERVER["HOST_URL"] . '/request/' . $data['_request']['id'] . '/files/' . $data['END_UPLOAD_DRAFT_ENDORSEMENT']; 
        $slipFiles = getenv('API_HOST') . '/files/' . $data['END_UPLOAD_DRAFT_ENDORSEMENT'];
        $responseRequestFiles = callCurl($slipFiles, "GET", '', $serverToken, 'application/json', "");
        $fileName = $responseRequestFiles['file_name'];
        $responseArray['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_NAME_BUTTON'] = $fileName;
    } else {
        $responseArray['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL'] = $data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_URL'];
        $responseArray['END_DOWNLOAD_REVIEW_ENDORSEMENT_DOCUMENT_URL_NAME_BUTTON'] = $data['END_DOWNLOAD_ENDORSEMENT_DOCUMENT_URL_NAME_BUTTON'];
    }    
}
//Decode Email description
if ($data['END_ENDORSEMENT_EMAIL_DESCRIPTION']) {
    $responseArray['END_ENDORSEMENT_EMAIL_DESCRIPTION'] = html_entity_decode($data['END_ENDORSEMENT_EMAIL_DESCRIPTION']);
}
//Save to Producing Files Collection
$searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
$searchRequest = callCurl($searchRequestUrl, "GET", array(), $serverToken, 'application/json', "");
$dataSave = array();
$dataSave['FORTE_REQUEST'] = $data['_request']['id'];
$dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
$dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
$dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];

$dataSave['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
$dataSave['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
$dataSave['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
$dataSave['YQP_CATCH_MONTH_REPORT'] = $data['YQP_CATCH_MONTH_REPORT'];
$dataSave['YQP_PIVOT_TABLE_NUMBER'] = $data['YQP_PIVOT_TABLE_NUMBER'];
$dataSave['YQP_SLIP_DOCUMENT_NAME'] = $data['YQP_SLIP_DOCUMENT_NAME'];
$dataSave['YQP_TYPE'] = $data['YQP_TYPE'];
$dataSave['YQP_REASSURED_CEDENT_LABEL'] = $data['YQP_REASSURED_CEDENT']['LABEL'];
$dataSave['YQP_REINSURANCE_BROKER_LABEL'] = $data['YQP_REINSURANCE_BROKER']['LABEL'];
$dataSave['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
$dataSave['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
$dataSave['YQP_TRIMESTER'] = $data['YQP_TRIMESTER'];
$dataSave['YQP_YEAR_PERIOD'] = $data['YQP_YEAR_PERIOD'];
$dataSave['YQP_CURRENCY'] = $data['YQP_CURRENCY'];
$dataSave['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
$dataSave['DATA_WAR_SUM_INSURED_TENDER'] = $data['DATA_WAR_SUM_INSURED_TENDER'];
$dataSave['DATA_WAR_SUM_INSURED_NET'] = $data['DATA_WAR_SUM_INSURED'];
$dataSave['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
$dataSave['DATA_WAR_SUM_INSURED'] = $data['DATA_WAR_SUM_INSURED'];
$dataSave['YQP_PERSONAL_EFFECTS_LIMIT'] = $data['YQP_PERSONAL_EFFECTS_LIMIT'];
$dataSave['YQP_MEDICAL_PAYMENTS_LIMIT'] = $data['YQP_MEDICAL_PAYMENTS_LIMIT'];
$dataSave['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'] = $data['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'];
$dataSave['YQP_TOTAL_PREMIUM_FINAL'] = $data['YQP_TOTAL_PREMIUM_FINAL'];
$dataSave['YQP_DEDUCTIBLE'] = $data['YQP_DEDUCTIBLE'];
$dataSave['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'] = $data['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'];
$dataSave['YQP_WAR_DEDUCTIBLE_SLIP'] = $data['YQP_WAR_DEDUCTIBLE_SLIP'];
$dataSave['YQP_PI_DEDUCTIBLE_SLIP'] = $data['YQP_PI_DEDUCTIBLE_SLIP'];
$dataSave['YQP_NUMBER_PAYMENTS'] = $data['YQP_NUMBER_PAYMENTS'];
$dataSave['YQP_BROKER_PERCENTAGE'] = $data['YQP_BROKER_PERCENTAGE'];
$dataSave['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'] = $data['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'];
$dataSave['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] = $data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'];
$dataSave['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
$dataSave['YQP_TYPE_CODE_REPORT'] = $data['YQP_TYPE_CODE_REPORT'];
$dataSave['YQP_VESSEL_MARK_MODEL'] = $data['YQP_VESSEL_MARK_MODEL'];
$dataSave['YQP_LENGTH_UNIT_REPORT'] = $data['YQP_LENGTH_UNIT_REPORT'];
$dataSave['YQP_YEAR'] = $data['YQP_YEAR'];
$dataSave['YQP_NAVIGATION_LIMITS'] = $data['YQP_NAVIGATION_LIMITS'];
$dataSave['YQP_USE'] = $data['YQP_USE'];
$dataSave['YQP_LOSS_PAYEE'] = $data['YQP_LOSS_PAYEE'];
$dataSave['YQP_HULL_MATERIAL'] = $data['YQP_HULL_MATERIAL'];
$dataSave['YQP_FLAG'] = $data['YQP_FLAG'];
$dataSave['YQP_MOORING_PORT_REPORT'] = $data['YQP_MOORING_PORT_REPORT'];
$dataSave['YQP_CLUB_MARINA'] = $data['YQP_CLUB_MARINA'];
$dataSave['YQP_COMMENTS'] = $data['YQP_COMMENTS'];
$dataSave['YQP_USER_USERNAME'] = $data['YQP_USER_USERNAME'];
$dataSave['YQP_RISK_ATTACHING_MONTH'] = $data['YQP_RISK_ATTACHING_MONTH'];
$dataSave['YQP_TERM'] = $data['YQP_TERM'];
$dataSave['YQP_TYPE_YACHT'] = $data['YQP_TYPE_YACHT'];
$dataSave['YQP_RANGE_SI_HULL'] = $data['YQP_RANGE_SI_HULL'];
$dataSave['YQP_RANGE_YEAR'] = $data['YQP_RANGE_YEAR'];
$dataSave['YQP_STATUS'] = $data['YQP_STATUS'];
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

$dataSave['YQP_REINSURER_INFORMATION'] = $reinsurerGridData;

//Set Reinsurers List Table
$reinsurerListTable = "<table style='width:100%; border-collapse:collapse; border-color:#bababa; text-align:center;' border='1'>";
$reinsurerListTable .= "<tr style='font-weight: bold;'>";
$reinsurerListTable .= "<td style='width:10%; color:#555916'></td>";
$reinsurerListTable .= "<td style='width:70%; color:#555916'>Reinsurer Name</td>";
$reinsurerListTable .= "<td style='width:20%; color:#555916'>Share %</td>";
$reinsurerListTable .= "</tr>";
for ($r = 0; $r < count($reinsurerGridData); $r++) {
    $reinsurerOrder = $r + 1;
    $reinsurerListTable .= "<tr>";
    $reinsurerListTable .= "<td>" . $reinsurerOrder . "</td>";
    $reinsurerListTable .= "<td>" . $reinsurerGridData[$r]["YQP_REINSURER_NAME"]["LABEL"] . "</td>";
    $reinsurerListTable .= "<td>" . $reinsurerGridData[$r]["YQP_SHARE_PERCENTAGE"] . "</td>";
    $reinsurerListTable .= "</tr>";
}
$reinsurerListTable .= "</table>";
$responseArray["YQP_REINSURERS_LIST_TABLE"] = $reinsurerListTable;

//-----------------Calculate Rate Cession and Hull Cession--------------------
if ($data['YQP_PRODUCT'] != "PI_RC") {
    //(Total Hull Sum Insured * Forte Order) Add HC
    $dataSave['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = $data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100);
    //(Total Premium * FORTE ORDER) / (Total Hull Sum Insured * Forte Order) Add HC
    $dataSave['YQP_RATE_CESSION_REPORT'] = ($data['YQP_BROKER_TOTAL_PREMIUM_REPORT'] * ($data['YQP_FORTE_ORDER'] / 100)) / ($data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100));
} else {
    $dataSave['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = 0;
    $dataSave['YQP_RATE_CESSION_REPORT'] = 0;
}
//---------------------------------------------------------------------------
//Validate Process
if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
} else {
    $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
    $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
    $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
    $dataSave['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
} 

//Validate if the request exists
if (count($searchRequest["data"]) == 0) {
    $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records';
    $insertRequest = callCurl($insertRequestUrl, "POST", json_encode($dataSave), $serverToken, 'application/json', "");
} else {
    $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records/' . $searchRequest["data"][0]["id"];
    $updateRequest = callCurl($updateRequestUrl, "PUT", json_encode($dataSave), $serverToken, 'application/json', "");
}

return $responseArray;