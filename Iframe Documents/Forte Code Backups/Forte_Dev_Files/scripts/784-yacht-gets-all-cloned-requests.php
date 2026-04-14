<?php 
/*************************************************  
 * Gets all cloned requests
 *
 * by Helen Callisaya
 ************************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$processId = $data['processId'];
$requestId = $data['requestId'];
$clientName = $data['clientName'];
$vesselName = $data['vesselName'];

//Get Id collection Additional Contacts
$collectionNames = ["FORTE_OPERATION_TYPE_TASK"];
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionTask = $collectionsInfo["FORTE_OPERATION_TYPE_TASK"];

$listTask = [];
$sqlTaskOpType = "SELECT T.data->>'$.FORTE_OTT_TASK' as FORTE_OTT_TASK 
                  FROM collection_" . $collectionTask . " AS T 
                  WHERE T.data->>'$.FORTE_OTT_PROCESS' = '" . $processId . "' 
                    AND T.data->>'$.FORTE_OTT_OPERATION_TYPE' = 'CLONE' 
                    AND T.data->>'$.FORTE_OTT_STATUS' = 'ACTIVE'";
$resTaskOpType = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlTaskOpType));

foreach ($resTaskOpType as $item) {
    $listTask[] = '"' . $item['FORTE_OTT_TASK'] . '"';
}
$taskIn = implode(",", $listTask);

$sqlSearchRequest = "SELECT PR.id AS RES_REQUEST_ID, 
                            PR.data->>'$.YQP_CLIENT_NAME' AS RES_YQP_CLIENT_NAME,
                            PR.data->>'$.YQP_INTEREST_ASSURED' AS RES_YQP_INTEREST_ASSURED,
                            PRT.element_name AS RESP_YQP_TASK_NAME
                     FROM process_requests as PR
                     INNER JOIN  process_request_tokens as PRT
                         ON PRT.process_request_id = PR.id
                     WHERE PR.status = 'ACTIVE' 
                         AND PR.process_id = " . $processId . "
                         AND PR.id != " . $requestId . "
                         AND PRT.element_name IN (" . $taskIn . ")
                         AND PRT.status = 'ACTIVE'
                         AND PR.data->>'$.YQP_CLIENT_NAME' = '" . $clientName ."'
                         AND PR.data->>'$.YQP_INTEREST_ASSURED' = '" . $vesselName . "'";
$resSearchRequest = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlSearchRequest)) ?? [];

foreach ($resSearchRequest as &$row) {
    $row['RESP_REQUEST_END'] = true;
}

return $resSearchRequest;

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

/**
 * Get IDs of collections with the Master collection
 *
 * @param (Array) $collectionNames
 * @param (String) $apiUrl
 * @return (Array) $aCollections
 *
 * by Ana Castillo
 */
function getCollectionIdMaster($collectionNames, $apiUrl)
{
    //Set Master Collection ID
    $masterCollectionID = getenv('FORTE_MASTER_COLLECTION_ID');

    //Add semicolon with all fields of the array
    $collectionName = array_map(function($item) {
        return '"' . $item . '"';
    }, $collectionNames);

    //Merge all values of the array with commas
    $collections = implode(", ", $collectionName);

    //Get Collections IDs
    $sQCollectionsId = "SELECT data->>'$.COLLECTION_ID' AS ID,
                               data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN (" . $collections . ")";
    $collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    //Set array to get the name with the ID
    $aCollections = array();
    if (count($collectionsInfo) > 0) {
        foreach ($collectionsInfo as $item) {
            $aCollections[$item['COLLECTION_NAME']] = $item['ID'];
        }
    }

    return $aCollections;
}