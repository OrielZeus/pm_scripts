<?php
ini_set("memory_limit", "2500M");
/*   
 *  Validate if there is a request in progress and completed
 *  by Helen Callisaya
 */

/*******************************Functions*********************************************/
/** Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url)
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
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ),
    ));
    $responseCurl = curl_exec($curl);
    $responseCurl = json_decode($responseCurl);
    curl_close($curl);

    return $responseCurl;
}

/*
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postdata
 * @param (bool ) $contentFile
 * @return (Array) $res
 *
 * by Elmer Orihuela
 */
function apiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}


/**
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
    for ($i = 0; $i < $len; $i++) {
        $dataExistsCompleted[$i]['YQP_QUERY_REQUEST_ID'] = $requestList->data[$i]->id;
        $dataExistsCompleted[$i]['YQP_QUERY_CLIENT'] = $requestList->data[$i]->data->YQP_CLIENT_NAME;
        $dataExistsCompleted[$i]['YQP_QUERY_VESSEL'] = $requestList->data[$i]->data->YQP_INTEREST_ASSURED;
        $dataExistsCompleted[$i]['YQP_QUERY_MOTORS'] = $requestList->data[$i]->data->YQP_MOTORS;
        $dataExistsCompleted[$i]['YQP_UPDATE'] = $requestList->data[$i]->updated_at;
    }
    return  [getGroupList($dataExistsCompleted), $dataExistsCompleted];
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
                $groupDataExistsComplete[$j]['YQP_QUERY_MOTORS'] == $dataExistsCompleted[$i]['YQP_QUERY_MOTORS']
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
 * Validate the client name is null or blank
 *
 * @param (Array) $requestList 
 * @param (integer) $len 
 * @return (Array) $newRequestActiveEmpty 
 *
 * by Helen Callisaya
 */
function validateClientNameEmpty($requestList, $len)
{
    $newRequestActiveEmpty = array();
    for ($i = 0; $i < $len; $i++) {
        $aux = trim($requestList->data[$i]->data->YQP_CLIENT_NAME);
        //validates if the client name field has a length equal to 0 (null or blank)
        if (strlen($aux) == 0) {
            array_push($newRequestActiveEmpty, $requestList->data[$i]);
        }
    }
    return $newRequestActiveEmpty;
}
/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $url
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res
 *
 * by Elmer Orihuela 
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        //$res = json_decode($res, true);
         if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}
/*****************************************************************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

//Collection Task
$collectionTask = "28";
//-----------------------------------------------------
//Get required variables
$clientName = $data['YQP_CLIENT_NAME'];
$vesselName = $data['YQP_INTEREST_ASSURED'];
$requestId = $data['_request']['id'];

//Set variable of return
$allValues = array();
//set default value YQP_EXIST_PROGRESS
$allValues['YQP_EXIST_PROGRESS'] = 'NO';
$allValues['YQP_CLONE_VISIBLE'] = "NO";

//The client name is empty 
if (strlen(trim($clientName)) > 0) {
    //The Vessel Name is empty 
    if (strlen(trim($vesselName)) > 0) {
        //Get data request in Progress (Active) the client name and vessel name 
        $urlApi = getenv('API_HOST') . '/requests?include=data&order_by=updated_at&order_direction=desc&pmql=' . urlencode('(request = "Yacht Quotation Process" AND status = "active" AND data.YQP_CLIENT_NAME = "' . $clientName . '" AND data.YQP_INTEREST_ASSURED = "' . $vesselName . '" and id!=' . $requestId . ')');
        
        $countRequestActive = $dataRequestActive->meta->count;
        //Validate if there are requests in progress
        if ($countRequestActive > 0) {
            $allValues['YQP_EXIST_PROGRESS'] = 'YES';
            $allValues['YQP_PROGRESS_MESSAGE'] = 'The Client Name <strong>' . $dataRequestActive->data[0]->data->YQP_CLIENT_NAME . '</strong> and Vessel Name <strong>' . $dataRequestActive->data[0]->data->YQP_INTEREST_ASSURED . '</strong> has the request <strong>' . $dataRequestActive->data[0]->id . '</strong> in progress.';
        } 

        //Get data requests completed
        $urlApiCompleted = getenv('API_HOST') . '/requests?include=data&order_by=updated_at&order_direction=desc&pmql=' . urlencode('(request = "Yacht Quotation Process" AND status = "completed" AND data.YQP_CLIENT_NAME = "' . $clientName . '" AND data.YQP_INTEREST_ASSURED = "' . $vesselName . '" AND data.YQP_STATUS = "BOUND")');
        $dataRequestCompleted = callGetCurl($urlApiCompleted);
        $countRequestCompleted = $dataRequestCompleted->meta->count;

        //validate if there are requests completed
        if ($countRequestCompleted > 0) {
            //group by data
            list($dataRequestGrouped, $allRequestNoGroup) = getDataCompletedRequest($dataRequestCompleted, $countRequestCompleted);
            $allValues['YQP_DATA_REQUEST'] = $dataRequestGrouped;
            $allValues['YQP_ALL_DATA_REQUEST'] = $allRequestNoGroup;
            $allValues['YQP_EXIST_COMPLETED'] = count($dataRequestGrouped);

            //if there is only one completed request
            if (count($dataRequestGrouped) == 1) {
                $allValues['YQP_ID_SELECTION'] = $dataRequestGrouped[0]['YQP_QUERY_REQUEST_ID']; //ESTE OBTIENE EL ID DEL UNICO REQUEST Q ENCUENTRE
            }
        } else {
            $allValues['YQP_EXIST_COMPLETED'] = 0;
            $allValues['YQP_DATA_EXISTS'] = 'NO';
            $allValues['YQP_DATA_REQUEST']  = array();
        }

        //------------Search Request in progress for Clone-------------------
        $listTask = [];
        $sqlTaskOpType = "SELECT T.data->>'$.FORTE_OTT_TASK' as FORTE_OTT_TASK 
                          FROM collection_" . $collectionTask . " AS T 
                          WHERE T.data->>'$.FORTE_OTT_PROCESS' = 6 
                              AND T.data->>'$.FORTE_OTT_OPERATION_TYPE' = 'CLONE' 
                              AND T.data->>'$.FORTE_OTT_STATUS' = 'ACTIVE'";
        $resTaskOpType = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlTaskOpType));

        foreach ($resTaskOpType as $item) {
            $listTask[] = '"' . $item['FORTE_OTT_TASK'] . '"';
        }
        $taskIn = implode(",", $listTask);

        $sqlSearchRequest = "
            SELECT PR.id AS RES_REQUEST_ID,
                PR.data->>'$.YQP_CLIENT_NAME' AS RES_YQP_CLIENT_NAME,
                PR.data->>'$.YQP_INTEREST_ASSURED' AS RES_YQP_INTEREST_ASSURED,
                -- Obtener el nombre de la última tarea y verificar si está activa
                CASE
                    WHEN PR.status = 'active' AND PRT.status = 'active' THEN PRT.element_name
                    WHEN PR.status = 'completed' AND PRT.status != 'active' THEN 'Completed'
                    WHEN PR.status = 'active' AND (PRT.element_name IS NULL OR PRT.element_name = '') THEN 'No Element Found'
                    ELSE 'No Element'
                END AS RESP_YQP_TASK_NAME,
                PR.data->>'$.YQP_STATUS' AS YQP_STATUS,
                JSON_OBJECT(
                    'YQP_QUOTE_NUMBER', PR.data->>'$.YQP_QUOTE_NUMBER',
                    'YQP_CLIENT_NAME', PR.data->>'$.YQP_CLIENT_NAME',
                    'YQP_INTEREST_ASSURED', PR.data->>'$.YQP_INTEREST_ASSURED',
                    'YQP_COUNTRY_BUSINESS', PR.data->>'$.YQP_COUNTRY_BUSINESS',
                    'YQP_LANGUAGE', PR.data->>'$.YQP_LANGUAGE',
                    'YQP_PRODUCT', PR.data->>'$.YQP_PRODUCT',
                    'YQP_SUM_INSURED_VESSEL', PR.data->>'$.YQP_SUM_INSURED_VESSEL',
                    'YQP_CURRENCY', PR.data->>'$.YQP_CURRENCY',
                    'YQP_EXCHANGE_RATE', PR.data->>'$.YQP_EXCHANGE_RATE',
                    'YQP_TYPE_YACHT', PR.data->>'$.YQP_TYPE_YACHT',
                    'YQP_TYPE_VESSEL', PR.data->>'$.YQP_TYPE_VESSEL',
                    'YQP_FUEL', PR.data->>'$.YQP_FUEL',
                    'YQP_PROPULSION', PR.data->>'$.YQP_PROPULSION',
                    'YQP_DEDUCTIBLE', PR.data->>'$.YQP_DEDUCTIBLE',
                    'YQP_LENGTH', PR.data->>'$.YQP_LENGTH',
                    'YQP_YEAR', PR.data->>'$.YQP_YEAR',
                    'YQP_LOCATION_MOORING_PORT', PR.data->>'$.YQP_LOCATION_MOORING_PORT',
                    'YQP_MOORING_PORT', PR.data->>'$.YQP_MOORING_PORT',
                    'YQP_SPECIFY_PORT', PR.data->>'$.YQP_SPECIFY_PORT',
                    'YQP_USE', PR.data->>'$.YQP_USE',
                    'YQP_LIMIT_PI', PR.data->>'$.YQP_LIMIT_PI',
                    'YQP_LIMIT_PI_DEDUCTIBLE', PR.data->>'$.YQP_LIMIT_PI_DEDUCTIBLE',
                    'YQP_OWNER_EXPERIENCE', PR.data->>'$.YQP_OWNER_EXPERIENCE'
                ) AS summaryData
            FROM process_requests AS PR
            LEFT JOIN process_request_tokens AS PRT 
                ON PRT.process_request_id = PR.id
            WHERE PR.process_id = " . $data['_request']['process_id'] . "
            AND PR.id != " . $requestId . "
            AND LOWER(TRIM(CAST(PR.data->>'$.YQP_CLIENT_NAME' AS CHAR))) LIKE LOWER(TRIM('%". $clientName ."%'))
            AND LOWER(TRIM(CAST(PR.data->>'$.YQP_INTEREST_ASSURED' AS CHAR))) LIKE LOWER(TRIM('%". $vesselName ."%'))
            AND PRT.created_at = (
                SELECT MAX(created_at)
                FROM process_request_tokens
                WHERE process_request_id = PR.id
            )
        ";
        $resSearchRequest = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlSearchRequest));
        if (count($resSearchRequest) > 0) {
            $allValues['YQP_CLONE_VISIBLE'] = "YES";
            foreach ($resSearchRequest as &$row) {
                $row['summaryData'] = isset($row['summaryData']) ? json_decode($row['summaryData'], true) : [];
            }
            $allValues['YQP_REQUEST_LIST_TO_CLONE'] = $resSearchRequest;
        }
        //-------------------------------------------------------------------
    } else {

        //get data request in Progress (Active) the client name 
        $urlApi = getenv('API_HOST') . '/requests?include=data&order_by=updated_at&order_direction=desc&pmql=' . urlencode('(request = "Yacht Quotation Process" AND status = "active" AND data.YQP_CLIENT_NAME = "' . $clientName . '" and id!=' . $requestId . ')');
        $dataRequestActive = callGetCurl($urlApi);
        $countRequestActive = $dataRequestActive->meta->count;

        //validate if there are requests in progress
        if ($countRequestActive > 0) {
            $allValues['YQP_EXIST_PROGRESS'] = 'YES';
            $allValues['YQP_PROGRESS_MESSAGE'] = 'The Client Name <strong>' . $dataRequestActive->data[0]->data->YQP_CLIENT_NAME . '</strong> has the request <strong>' . $dataRequestActive->data[0]->id . '</strong> in progress.';
        }

        //get data requests completed
        $urlApiCompleted = getenv('API_HOST') . '/requests?include=data&order_by=updated_at&order_direction=desc&pmql=' . urlencode('(request = "Yacht Quotation Process" AND status = "completed" AND data.YQP_CLIENT_NAME = "' . $clientName . '" AND data.YQP_STATUS = "BOUND")');
        $dataRequestCompleted = callGetCurl($urlApiCompleted);
        $countRequestCompleted = $dataRequestCompleted->meta->count;

        //validate if there are requests completed
        if ($countRequestCompleted > 0) {
            //group by data
            list($dataRequestGrouped, $allRequestNoGroup) = getDataCompletedRequest($dataRequestCompleted, $countRequestCompleted);
            $allValues['YQP_DATA_REQUEST'] = $dataRequestGrouped;
            $allValues['YQP_ALL_DATA_REQUEST'] = $allRequestNoGroup;
            $allValues['YQP_EXIST_COMPLETED'] = 2;
        } else {
            $allValues['YQP_EXIST_COMPLETED'] = 0;
            $allValues['YQP_DATA_EXISTS'] = 'NO';
            $allValues['YQP_DATA_REQUEST']  = array();
        }
    }
} else {
    //get data request in Progress (Active) the vessel name 
    $urlApi = getenv('API_HOST') . '/requests?include=data&order_by=updated_at&order_direction=desc&pmql=' . urlencode('(request = "Yacht Quotation Process" AND status = "active" AND data.YQP_INTEREST_ASSURED = "' . $vesselName . '" and id!=' . $requestId . ')');
    $dataRequestActive = callGetCurl($urlApi);
    $countRequestActive = $dataRequestActive->meta->count;

    //validate if there are requests in progress
    if ($countRequestActive > 0) {
        //get data where the client is null or blank
        $dataValidateClientNameEmpty = validateClientNameEmpty($dataRequestActive, $countRequestActive);

        //there is data in progress
        if (count($dataValidateClientNameEmpty) > 0) {
            $allValues['YQP_EXIST_PROGRESS'] = 'YES';
            $allValues['YQP_PROGRESS_MESSAGE'] = 'The vessel name <strong>' . $dataValidateClientNameEmpty[0]->data->YQP_INTEREST_ASSURED . '</strong> has the request <strong>' . $dataValidateClientNameEmpty[0]->id . '</strong> in progress.';
        } 
    } 

    //get data requests completed from all clients and status BOUND
    $urlApiCompleted = getenv('API_HOST') . '/requests?include=data&order_by=updated_at&order_direction=desc&pmql=' . urlencode('(request = "Yacht Quotation Process" AND status = "completed" AND data.YQP_INTEREST_ASSURED = "' . $vesselName . '" AND data.YQP_STATUS = "BOUND")');
    $dataRequestCompleted = callGetCurl($urlApiCompleted);
    $countRequestCompleted = $dataRequestCompleted->meta->count;

    //validate if there are requests completed
    if ($countRequestCompleted > 0) {
        list($dataRequestGrouped, $allRequestNoGroup) = getDataCompletedRequest($dataRequestCompleted, $countRequestCompleted);
        $allValues['YQP_DATA_REQUEST'] = $dataRequestGrouped;
        $allValues['YQP_ALL_DATA_REQUEST'] = $allRequestNoGroup;
        $allValues['YQP_EXIST_COMPLETED'] = 2;
        $existsGroup = $allValues['YQP_DATA_REQUEST'];
    } else {
        $allValues['YQP_EXIST_COMPLETED'] = 0;
        $allValues['YQP_DATA_EXISTS'] = 'NO';
        $allValues['YQP_DATA_REQUEST']  = array();
    }
}
//get User Initials
$urlApi = getenv('API_HOST') . '/users/' . $data['_request']['user_id'];
$dataUser = callGetCurl($urlApi);

$allValues['YQP_REQUEST_COMPLETE'] = 'SUBMIT';
$allValues['YQP_USER_INITIALS'] = $dataUser->address;
$allValues['YQP_SUBSCRIPTION_YEAR'] = date('Y');
$allValues['YQP_QUOTE_DATE'] = date('d-m-Y');
$allValues['YQP_TYPE'] = "NEW";
//Set variable Status as PENDING
$allValues['YQP_STATUS'] = "PENDING";
//Set situation in OPEN
$allValues['YQP_SITUATION'] = "OPENED";
//Set Month Capture
$date = new DateTime($data['_request']['created_at']);
$allValues['YQP_CATCH_MONTH_REPORT'] = strtoupper($date->format('M-y'));

return $allValues;