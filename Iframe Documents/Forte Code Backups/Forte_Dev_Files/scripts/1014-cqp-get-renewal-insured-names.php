<?php 
/*****************************************
* Create List on unique insured
*
* by Diego Tapia
*****************************************/

// Set initial Variables
$response = [
    "data" => []
];

// Get list of vendors available
$apiInstanceCollections = $api->collections();
$collections = json_decode(json_encode($apiInstanceCollections->getCollections(null,"ID", "desc", "1000")->getData()));
$collectionOriginal = getCollectionId("CQP_FORTE_CARGO_ORIGINAL_REQUESTS");
$quey = "";

if ($data["type"] == "BOUND") {
    $quey = "data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN' AND";
}

$sQCollectionsId = "SELECT 
    SUBSTRING_INDEX(
        GROUP_CONCAT(CQP_INSURED_NAME ORDER BY created_at DESC),
        ',',
        1
    ) as CQP_INSURED_NAME,
    CQP_INSURED_CODE
FROM (
    SELECT 
        created_at,
        data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME,
        data->>'$.CQP_INSURED_CODE' as CQP_INSURED_CODE
    FROM collection_" . $collectionOriginal  . "
    WHERE data->>'$.CQP_INSURED_CODE' IS NOT NULL AND data->>'$.CQP_INSURED_CODE' != ''  AND data->>'$.CQP_INSURED_CODE' != 'null'
    UNION ALL
    SELECT 
        created_at,
        data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME,
        data->>'$.CQP_INSURED_CODE' as CQP_INSURED_CODE
    FROM process_requests
    WHERE " . $quey . " data->>'$.CQP_INSURED_CODE' IS NOT NULL AND data->>'$.CQP_INSURED_CODE' != ''  AND data->>'$.CQP_INSURED_CODE' != 'null'
) AS combined
GROUP BY CQP_INSURED_CODE
ORDER BY CQP_INSURED_NAME";

$response["data"] = getSqlData("POST", $sQCollectionsId);
return $response;


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