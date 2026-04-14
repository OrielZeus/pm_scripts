<?php 

/*****************************************
* get data for request list for renewal
*
* by Diego Tapia
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// Set default Variables
$responseHistorical = [];
$hasHistorical = "NO";

// Get the request of the insurer
if(isset($data["CQP_INSURED_RENEWAL"]["CQP_INSURED_CODE"])) {
    // Get list of vendors available
    $apiInstanceCollections = $api->collections();
    $collections = json_decode(json_encode($apiInstanceCollections->getCollections(null,"ID", "desc", "1000")->getData()));
    $collectionOriginal = getCollectionId("CQP_FORTE_CARGO_ORIGINAL_REQUESTS");

    $sQCollectionsId = "
        SELECT 
            id as CQP_REQUEST_ID,
            data->>'$.CQP_PIVOT_TABLE_NUMBER' as CQP_PIVOT_NUMBER,
            data->>'$.CQP_TYPE' as CQP_PRODUCT_TYPE,
            data->>'$.CQP_UNDERWRITING_YEAR' as CQP_UNDERWRITER_YEAR,
            data->>'$.CQP_INTEREST' as CQP_INTEREST,
            data->>'$.CQP_REINSURANCE_BROKER' as CQP_BROKER,
            data->>'$.CQP_COUNTRY' as CQP_COUNTRY,
            '' as CQP_PERIOD,
            'BOUND/QUOTE TAKEN' as CQP_STATUS,
            'collection_" . $collectionOriginal  . "' as origin
        FROM collection_" . $collectionOriginal  . "
        WHERE data->>'$.CQP_INSURED_CODE' = '" . $data["CQP_INSURED_RENEWAL"]["CQP_INSURED_CODE"]  . "'
        UNION ALL
        SELECT 
            id as CQP_REQUEST_ID,
            data->>'$.CQP_PIVOT_TABLE_NUMBER' as CQP_PIVOT_NUMBER,
            data->>'$.CQP_TYPE' as CQP_PRODUCT_TYPE,
            data->>'$.CQP_UNDERWRITING_YEAR' as CQP_UNDERWRITER_YEAR,
            data->>'$.CQP_INTEREST' as CQP_INTEREST,
            data->>'$.CQP_REINSURANCE_BROKER' as CQP_BROKER,
            data->>'$.CQP_COUNTRY' as CQP_COUNTRY,
            data->>'$.CQP_NUMBER_OF_PERIODS' as CQP_PERIOD,
            data->>'$.CQP_CARGO_CURRENT_STATUS' as CQP_STATUS,
            'requests' as origin
        FROM process_requests
        WHERE  data->>'$.CQP_INSURED_CODE' = '" . $data["CQP_INSURED_RENEWAL"]["CQP_INSURED_CODE"]  . "'
            AND data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN'";

    $responseHistorical = getSqlData("POST", $sQCollectionsId);
    
    foreach($responseHistorical as &$request) {
        $request["CQP_COUNTRY"] = json_decode($request["CQP_COUNTRY"]);
        $request["CQP_BROKER"] = json_decode($request["CQP_BROKER"]);
        $hasHistorical = "YES";
    }
}


return ["HISTORICAL_FOUND" => $hasHistorical, "CQP_CLIENT_HISTORY" => $responseHistorical];

/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res 
 *
 * by Diego Tapia
 */
function getSqlData ($requestType, $postdata = [], bool $contentFile = false) {
    $headers = [
        "Accept" => $acceptType,
        "Authorization" => "Bearer " . getenv("API_TOKEN"),
        "Content-Type" => $contentFile ? "'application/octet-stream'" : "application/json"

    ];
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, getenv('API_HOST') . getenv('API_SQL'), $headers, json_encode(["SQL" => base64_encode($postdata)]));
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

/*
* Get collection ID using the name
*
* @param string $name
* @return int $collectionId
*
* by Diego Tapia
*/
function getCollectionId ($name) {
    global $collections;
    $collection = $collections[array_search($name, array_column($collections, "name"))];

    if ($collection == null || $collection === false) {
        return false;
    } else {
        return $collection->id;
    }
}