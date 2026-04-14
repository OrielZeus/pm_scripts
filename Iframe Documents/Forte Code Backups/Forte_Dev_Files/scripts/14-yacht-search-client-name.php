<?php 
/*   
 *  Search Client Name
 *  by Helen Callisaya
 */
/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url, $method, $json_data)
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
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json_data,
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

if ($data['YQP_REQUEST_COMPLETE'] == "NEW") {
    //Assign NO value to YQP_DATA_EXISTS variable
    $dataResult['YQP_DATA_EXISTS'] = "NO";
} else {
    $allRequest = $data['YQP_ALL_DATA_REQUEST'];
    $newAllRequest = array();

    //Get required variable
    $requestId = $data['YQP_ID_SELECTION'];
    //Get client name and vessel name
    $urlApi = getenv('API_HOST') . '/requests/' . $requestId . '?include=data';
    $dataRequestJson = callGetCurl($urlApi, "GET", "");
    $dataResult = $dataRequestJson['data'];
    $client = $dataResult['YQP_CLIENT_NAME'];
    $vesselName = $dataResult['YQP_INTEREST_ASSURED'];
    $indexNew = 0;
    for ($i = 0; $i < count($allRequest); $i++ ) {
        if ($allRequest[$i]['YQP_QUERY_CLIENT'] == $client && $allRequest[$i]['YQP_QUERY_VESSEL'] == $vesselName) {
            $newAllRequest[$indexNew] = $allRequest[$i];
            $indexNew = $indexNew + 1;
        }       
    }
    //Check if there is a cancellation endorsement
    $urlRoot = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?order_by=updated_at&order_direction=desc&pmql=';    
    $dataIdRequest = "";
    //Check yacht request
    for ($i = 0; $i < count($newAllRequest); $i++ ) {
        //---------------------------------------------------------------------------------
        $idSearch = $newAllRequest[$i]['YQP_QUERY_REQUEST_ID'];
        //Check if exist a Cancellation Endorsement Completed for the YATCH case selected
        $getCancelledEndorsementUrl = $urlRoot . urlencode('(data.FORTE_REQUEST_PARENT = "' . $idSearch . '" AND data.FORTE_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.END_TYPE_ENDORSEMENT = "Cancelled" AND data.YQP_STATUS = "BOUND")');
        $cancelledEndorsementResponse = callGetCurl($getCancelledEndorsementUrl, "GET", "");
        $cancelledEndorsement = $cancelledEndorsementResponse['data'];
        //If there is any cancellation endorsement for the yacht
        if (count($cancelledEndorsement) > 0) {
            $dataResult['YQP_MESSAGE_ENDORSEMENT'] = "This yacht was canceled";
        } else {
            //Gets all the endorsements that the Yacht has
            $searchProgressCollection = $urlRoot . urlencode('(data.FORTE_REQUEST_PARENT = "' . $idSearch . '" AND data.FORTE_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '")');
            $responseSearchProgressCollection = callGetCurl($searchProgressCollection, "GET", "");
            $progressCollection = $responseSearchProgressCollection['data'];
            $requestInProgress = '';
            $inProgressCount = 0;
            $inCanceledCount = 0;
            $requestNotToCount = '';
            //Check request in progress or canceled
            for ($j = 0; $j < count($progressCollection); $j++ ) {
                $searchInProgressUrl = getenv('API_HOST') . '/requests/' . $progressCollection[$j]['data']['FORTE_REQUEST'];
                $searchEndorsement = callGetCurl($searchInProgressUrl, "GET", "");
                if ($searchEndorsement['status'] == "ACTIVE" || $searchEndorsement['status'] == "CANCELED") {
                    $requestNotToCount .= '"' . $progressCollection[$j]['data']['FORTE_REQUEST'] . '",';
                    $inProgressCount = $inProgressCount + 1;
                }
            }
            //-----------------------------------
            $requestNotToCount = trim($requestNotToCount, ",");
            $urlHistory = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?order_by=updated_at&order_direction=desc&pmql=';
            if ($requestNotToCount == "") {
                $searchRequestUrl = $urlHistory . urlencode('(data.FORTE_OD_PARENT = "' . $idSearch . '" AND data.FORTE_OD_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_OD_CANCEL = "NO")');
            } else {
                $searchRequestUrl = $urlHistory . urlencode('(data.FORTE_OD_PARENT = "' . $idSearch . '" AND data.FORTE_OD_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_OD_REQUEST NOT IN [' . $requestNotToCount . '] AND data.FORTE_OD_CANCEL = "NO")');
            }
            $responseSearchRequest = callGetCurl($searchRequestUrl, "GET", "");
            $requestCollection = $responseSearchRequest['data'];
            //Valid if you get the request from the last endorsement or from the yacht
            if (count($requestCollection) > 0) {
                $dataIdRequest = $requestCollection[0]['data']['FORTE_OD_REQUEST'];
            } else {
                $dataIdRequest = $idSearch;
            }
            break;
        }
        //---------------------------------------------------------------------------------
    }    
    //Check if there is any request
    if ($dataIdRequest != "") {
        $urlApi = getenv('API_HOST') . '/requests/' . $dataIdRequest . '?include=data';
        $dataRequestJson = callGetCurl($urlApi, "GET", "");
        $dataResult = $dataRequestJson['data'];
        foreach ($dataResult as $key => $value) {
            if (substr($key, 0, 3) == "END") {
                unset($dataResult[$key]);                    
            }
        }
        unset($dataResult['YQP_ADOBE_WORKFLOW_SELECTED']);
        unset($dataResult['YQP_ADOBE_WORKFLOW_LIST']);
        unset($dataResult['YQP_ADOBE_WORKFLOW_DOCUMENTS']);
        unset($dataResult['YQP_VALIDATE_REQUIRED']);

        //Set variable of return
        $dataResult['YQP_DATA_EXISTS'] = "YES";
        $dataResult['YQP_TYPE'] = "RENEWAL";
        $dataResult['YQP_TYPE_DISABLE'] = "RENEWAL";
        $dataResult['YQP_CLIENT_NAME_DISABLE'] = $dataResult['YQP_CLIENT_NAME'];
        $dataResult['YQP_INTEREST_ASSURED_DISABLE'] = $dataResult['YQP_INTEREST_ASSURED'];
        $dataResult['YQP_SITUATION'] = "OPENED";
        $dataResult['YQP_STATUS'] = "PENDING";
        $dataResult['YQP_SUBSCRIPTION_YEAR'] = date('Y');
        $dataResult['YQP_QUOTE_DATE'] = date('d-m-Y');
        $date = new DateTime($data['_request']['created_at']);
        $dataResult['YQP_CATCH_MONTH_REPORT'] = strtoupper($date->format('M-y'));
        $dataResult['YQP_PIVOT_TABLE_NUMBER_DISABLE'] = $dataResult['YQP_PIVOT_TABLE_NUMBER'];
        $dataResult['YQP_UMR_CONTRACT_NUMBER_DISABLE'] = $dataResult['YQP_UMR_CONTRACT_NUMBER'];
        
        $dataResult['YQP_QUOTE_DOCUMENTS'] = null;
        $dataResult['FORTE_ERRORS'] = null;
        $dataResult['YQP_DOWNLOAD_SLIP_DOCUMENT_URL'] = null;
        $dataResult['YQP_DOWNLOAD_SLIP_DOCUMENT_URL_NAME_BUTTON'] = null;
        $dataResult['YQP_DOWNLOAD_SLIP_DOCUMENT'] = null;
        $dataResult['YQP_OBTAIN_WORKFLOWS_ERROR'] = null;
        $dataResult['YQP_AGREEMENT_CREATED_ERROR'] = null;
        $dataResult['YQP_STATUS_MESSAGE_SEND_MAIL'] = null;
        $dataResult['YQP_STATUS_SEND_MAIL'] = null;
        $dataResult['YQP_REJECTED_REASON'] = null;
        $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT_URL'] = null;
        $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT_URL_NAME_BUTTON'] = null;
        $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT'] = null;
        $dataResult['YQP_DOWNLOAD_QUOTE_DOCUMENT_URL'] = null;
        $dataResult['YQP_DOWNLOAD_QUOTE_DOCUMENT_URL_NAME_BUTTON'] = null;
        $dataResult['YQP_DOWNLOAD_SIGN_DOCUMENT'] = null;
        $dataResult['YQP_ERROR_SLIP'] = null;
        $dataResult['YQP_ERROR_MESSAGE_SLIP'] = null;
        $dataResult['YQP_ADOBE_WORKFLOW_LIST'] = null;
        $dataResult['YQP_CONFIRMATION_DOCUMENTS'] = null;
        $dataResult['YQP_ID_REQUEST_OLD'] = $dataIdRequest;
    } else {
        //New Request
        $dataResult['YQP_DATA_EXISTS'] = "NO";
    }
}
$dataResult['YQP_VALID_REQUEST'] = "YES";
return $dataResult;