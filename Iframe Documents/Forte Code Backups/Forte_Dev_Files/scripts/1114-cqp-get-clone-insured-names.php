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
$sQCollectionsId = "
    SELECT 
        id as CQP_REQUEST_ID,
        case_number as CQP_CASE_NUMBER,
        data->>'$.CQP_INSURED_CODE' as CQP_INSURED_CODE,
        data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME
    FROM process_requests
    WHERE data->>'$.CQP_INSURED_CODE' IS NOT NULL AND data->>'$.CQP_INSURED_CODE' != ''  AND data->>'$.CQP_INSURED_CODE' != 'null' 
    AND name = 'Cargo Quotation Process' AND data->>'$.CQP_BROKER_STATUS' = 'OPEN'";

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