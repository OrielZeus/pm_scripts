<?php
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
        $res = json_decode($res, true);
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}
// Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
//TRUNCATE TABLE collection_21;
//TRUNCATE TABLE collection_23;
//TRUNCATE TABLE collection_19;
//TRUNCATE TABLE collection_16;
//TRUNCATE TABLE collection_24;

for ($i = 2; $i <= 627; $i++) {
        $record = $i;
        $updateData['data'] = $record['data'];
        //$updateData['data']['serverStatusActive'] = true;
        //$updateData['data']['documentRequired'] = true;
        //https://etl.dev.cloud.processmaker.net/api/1.0/collections/24/records/1
        $updateRecordUrl = $apiHost . '/requests/' . $record;
        //$getUpdateResponse = apiGuzzle($updateRecordUrl, 'DELETE',[]);
        $response[] = $getUpdateResponse;
}

return $response;