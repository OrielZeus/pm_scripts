<?php

/******************************
 * Set User ID Requestor
 *
 * by Helen Callisaya
 * modified by Cinthia Romero
 * modified by Elmer Orihuela
 *****************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Initial Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$urlSQL = $apiHost . $apiSql;

$userId = $data['_request']['user_id'];
$dataRequest['YQP_USER_ID'] = $userId;
//Underwriter id bby default is 5
$dataRequest['YQP_UNDEWRITER_GROUP_ID'] = getEnv("YQP_UNDEWRITER_GROUP_ID") ?? 5;
$requestId = $data['_request']['id'];
$processId = $data['_request']['process_id'];

//Get Id collection Additional Contacts
$collectionNames = ["FORTE_ID_YACHT", "FORTE_GESTION_SOLICITUDES"];
$collectionsInfo = getCollectionIdMaster($collectionNames, $urlSQL);
$forteIdyacht = $collectionsInfo["FORTE_ID_YACHT"];
$forteGestionSolicitudesId = $collectionsInfo["FORTE_GESTION_SOLICITUDES"];

//Set User Requestor
$sqlUser = "SELECT U.firstname, 
                   U.lastname
            FROM users AS U
            WHERE U.id = " . $userId;
$resSqlUser = apiGuzzle($urlSQL, "POST", encodeSql($sqlUser));

$dataRequest['YQP_REQUESTOR_NAME'] = $resSqlUser[0]['firstname'] . ' ' . $resSqlUser[0]['lastname']; //$responseUserId['firstname'] . ' ' . $responseUserId['lastname'];
$dataRequest['YQP_CREATE_DATE'] = date('Y-m-d', strtotime($data['_request']['created_at']));
$dataRequest['YQP_STATUS'] = "PENDING";

//Save to Gestion Solicitudes Collection
$sqlGestionSolicitudes = "SELECT id AS TOTAL
                          FROM collection_" . $forteGestionSolicitudesId . " AS C
                          WHERE C.data->>'$.FORTE_REQUEST' = '" . $requestId . "'";
$resGestionSolicitudes = apiGuzzle($urlSQL, "POST", encodeSql($sqlGestionSolicitudes));

$dataSave = array();
$dataSave['FORTE_REQUEST'] = $requestId;
$dataSave['FORTE_PROCESS'] = $processId;
$dataSave['FORTE_CREATE_DATE'] = $dataRequest['YQP_CREATE_DATE'];
$dataSave['FORTE_CREATE_USER'] = $dataRequest['YQP_USER_ID'];
$dataSave['YQP_STATUS'] = $dataRequest['YQP_STATUS'];
$dataSave['YQP_FORTE_ORDER'] = empty($data['YQP_FORTE_ORDER']) ? "" : $data['YQP_FORTE_ORDER'];
//Get Name Process
if ($processId == $forteIdyacht) {
    $dataSave['FORTE_REQUEST_PARENT'] = $requestId;
    $dataSave['FORTE_REQUEST_CHILD'] = $requestId;
    $dataSave['FORTE_REQUEST_ORDER'] = $requestId . ".0";
    //Name Process
    $dataRequest['FORTE_TITLE_PROCESS'] = "Yacht Quotation Process";
} else {
    $dataSave['FORTE_REQUEST_CHILD'] = $requestId;
    //Name Process
    $dataRequest['FORTE_TITLE_PROCESS'] = "Yacht Endorsement Process";
}
//Validate if the request exists
if (count($resGestionSolicitudes) == 0) {
    $insertRequestUrl = $apiHost . '/collections/' . $forteGestionSolicitudesId . '/records';
    $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
} else {
    $totalRecords = $resGestionSolicitudes[0]['TOTAL'];
    $updateRequestUrl = $apiHost . '/collections/' . $forteGestionSolicitudesId . '/records/' . $totalRecords; //$searchRequest["data"][0]["id"];
    $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
}

return $dataRequest;

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
    ));
    $responseCurl = curl_exec($curl);
    $responseCurl = json_decode($responseCurl, true);
    curl_close($curl);

    return $responseCurl;
}

/*
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postfiles
 * @return (Array) $res
 *
 * by Elmer Orihuela
 */
function apiGuzzle($url, $requestType, $postfiles, $contentFile = false)
{
    global $apiToken, $apiHost;
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";

    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken
    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request($requestType, $url, $headers, json_encode($postfiles));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    if ($contentFile === false) {
        $res = json_decode($res, true);
    }
    return $res;
}

/*
 * Encode SQL
 *
 * @param (String) $string
 * @return (Array) $variablePut
 *
 * by Elmer Orihuela
 */
function encodeSql($string)
{
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
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
    $collectionName = array_map(function ($item) {
        return '"' . $item . '"';
    }, $collectionNames);

    //Merge all values of the array with commas
    $collections = implode(", ", $collectionName);

    //Get Collections IDs
    $sQCollectionsId = "SELECT data->>'$.COLLECTION_ID' AS ID,
                               data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN (" . $collections . ")";
    $collectionsInfo = apiGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    //Set array to get the name with the ID
    $aCollections = array();
    if (count($collectionsInfo) > 0) {
        foreach ($collectionsInfo as $item) {
            $aCollections[$item['COLLECTION_NAME']] = $item['ID'];
        }
    }

    return $aCollections;
}