<?php
/*   
 *  Validate Request based on Filters
 *  by Helen Callisaya
 */

/*******************************Functions*********************************************/
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
* Get the requests list with the necessary fields
*
* @param (Array) $requestList 
* @param (Integer) $len
* @return (Array) $dataExistsCompleted 
*
* by Helen Callisaya
*/
function getDataCompletedRequest($requestList, $len)
{   
    $dataExistsCompleted = [];
    for ($i = 0; $i < $len; $i++) {
        $dataExistsCompleted[$i]['YQP_QUERY_REQUEST_ID'] = $requestList[$i]['id'];
        $dataExistsCompleted[$i]['YQP_QUERY_CLIENT'] = $requestList[$i]['data']['YQP_CLIENT_NAME'];
        $dataExistsCompleted[$i]['YQP_QUERY_VESSEL'] = $requestList[$i]['data']['YQP_INTEREST_ASSURED'];
        $dataExistsCompleted[$i]['YQP_QUERY_PIVOT_NUMBER'] = $requestList[$i]['data']['YQP_PIVOT_TABLE_NUMBER'];
        $dataExistsCompleted[$i]['YQP_QUERY_UMR_CONTRACT_NUMBER'] = $requestList[$i]['data']['YQP_UMR_CONTRACT_NUMBER'];
        //$dataExistsCompleted[$i]['YQP_UPDATE'] = $requestList->data[$i]->updated_at;
    }
    //return getGroupList($dataExistsCompleted);
    if (count($dataExistsCompleted) > 0) {
        return getGroupList($dataExistsCompleted);
    } else {
        return $dataExistsCompleted;
    }
}

/*
* Group the list by Client, Vessel and Motors
*
* @param (Array) $dataExistsCompleted 
* @return (Array) $groupDataExistsComplete 
*
* by Helen Callisaya
*/
function getGroupList($dataExistsCompleted)
{
    $groupDataExistsComplete = array();
    $groupDataExistsComplete[0] = $dataExistsCompleted[0];

    for ($i = 0; $i < count($dataExistsCompleted); $i++) {
        $exist = 0;
        $indexSearch = -1;
        //compare data by position  $dataExisCompleted and $groupDataExistComplete
        for ($j = 0; $j < count($groupDataExistsComplete); $j++) {
            //compare only 3 fields and if there is such data
            if (
                $groupDataExistsComplete[$j]['YQP_QUERY_CLIENT'] == $dataExistsCompleted[$i]['YQP_QUERY_CLIENT'] &&
                $groupDataExistsComplete[$j]['YQP_QUERY_VESSEL'] == $dataExistsCompleted[$i]['YQP_QUERY_VESSEL'] &&
                $groupDataExistsComplete[$j]['YQP_QUERY_PIVOT_NUMBER'] == $dataExistsCompleted[$i]['YQP_QUERY_PIVOT_NUMBER'] &&
                $groupDataExistsComplete[$j]['YQP_QUERY_UMR_CONTRACT_NUMBER'] == $dataExistsCompleted[$i]['YQP_QUERY_UMR_CONTRACT_NUMBER']
            ) {
                $exist = $exist + 1;
            } else {
                $indexSearch = $i;
            }
        }
        //if the row has no match it is added to the new array
        if ($exist > 0) {
            $exist = 1;
        } else {
            array_push($groupDataExistsComplete, $dataExistsCompleted[$indexSearch]);
        }
    }
    return $groupDataExistsComplete;
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

/*************************************************************************************/
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$clientName = strtolower(trim($data['YQP_CLIENT_NAME']));
$vesselName = strtolower(trim($data['YQP_INTEREST_ASSURED']));
$pivotNumber = trim($data['YQP_PIVOT_TABLE_NUMBER']);
$contractNumber = trim($data['YQP_UMR_CONTRACT_NUMBER']);
//Clean message error
$requestError = [
    'FORTE_ERROR_LOG' => '',
    'FORTE_ERROR_DATE' => '',
    'FORTE_ERROR_BODY' => '',
    'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
    'FORTE_ERROR_ELEMENT_ID' => '',
    'FORTE_ERROR_ELEMENT_NAME' => '',
    'FORTE_ERROR_PROCESS_ID' => $data['_request']['process_id'],
    'FORTE_ERROR_PROCESS_NAME' => $data['_request']['name']
];
$dataSearch['FORTE_ERRORS'] = $requestError;

$urlApi = getenv('API_HOST') . '/requests?type=completed&order_by=updated_at&order_direction=desc&per_page=100&include=data&pmql=';
$pmqlSearch = '(request = "Yacht Quotation Process" AND data.YQP_STATUS = "BOUND"';

//The client name is empty 
if (strlen(trim($clientName)) > 0) {
    //The Vessel Name is empty
    if (strlen(trim($vesselName)) > 0) {
        //The Pivot Number is empty
        if (strlen(trim($pivotNumber)) > 0) {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%" AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%" AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '")');
            }
        } else {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%")');
            }
        }
    } else {
        //The Pivot Number is empty
        if (strlen(trim($pivotNumber)) > 0) {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '")');
            }
        } else {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_CLIENT_NAME) LIKE "%' . $clientName . '%")');
            }
        }
    }
} else {
    //The Vessel Name is empty
    if (strlen(trim($vesselName)) > 0) {
        //The Pivot Number is empty
        if (strlen(trim($pivotNumber)) > 0) {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%" AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%" AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '")');
            }
        } else {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND lower(data.YQP_INTEREST_ASSURED) LIKE "%' . $vesselName . '%")');
            }
        }
    } else {
        //The Pivot Number is empty
        if (strlen(trim($pivotNumber)) > 0) {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '" AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            } else {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND data.YQP_PIVOT_TABLE_NUMBER = "' . $pivotNumber . '")');
            }
        } else {
            //The Contract Number is empty
            if (strlen(trim($contractNumber)) > 0) {
                $urlApi = $urlApi . urlencode($pmqlSearch . ' AND data.YQP_UMR_CONTRACT_NUMBER = "' . $contractNumber . '")');
            }
        }
    }
}

//Get Endorsements Type
$dataSend = [];
$openLUrlCurl = getenv('OPENL_CONNECTION') . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetEndorsementTypes";

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
    $dataSearch['PM_OPEN_ENDORSEMENTS_TYPE'] = "cURL Error #:" . $err;
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
        $dataSearch['PM_OPEN_ENDORSEMENTS_TYPE'] = $aOptions;
    }
}

//Valid exists Request
$dataRequestActive = callGetCurl($urlApi);
$searchRequest = getDataCompletedRequest($dataRequestActive['data'], count($dataRequestActive['data']));

if (count($searchRequest) > 0) {
    $dataSearch['YQP_DATA_REQUEST'] = $searchRequest;
    $dataSearch['END_EXISTS_REQUEST'] = "EXISTS";
} else {
    $dataSearch['END_EXISTS_REQUEST'] = "NO_EXISTS";
    //Set error in screen
    $requestError = array();
    $requestError['FORTE_ERROR_LOG'] = "Search";
    $requestError['FORTE_ERROR_BODY'] = "No results found";
    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "Search";
    $dataSearch['FORTE_ERRORS'] = $requestError;   
}

return $dataSearch;