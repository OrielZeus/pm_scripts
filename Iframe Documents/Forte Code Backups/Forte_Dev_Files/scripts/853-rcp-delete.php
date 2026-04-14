<?php 

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
// Decodear la variable que viene en base64
$clientProductList = json_decode(base64_decode(html_entity_decode($data['CRCP_REQUEST_FINDED_ENCODED'])), true) ?? [];

// Filtrar solo los elementos con "SELECTED: true"
$filteredData = array_filter($clientProductList, function ($item) {
    return $item['SELECTED'] === true;
});


foreach ($filteredData as $record) {
    // Endpoint y método para borrar según Record_Id
    $deleteRecordUrl = $apiHost . "/collections/12/records/" . $record['Record_Id'];
    $responseRecord = apiGuzzle($deleteRecordUrl, 'DELETE');
    
    // Endpoint y método para borrar según Request
    $deleteRequestUrl = $apiHost . "/requests/" . $record['Request'];
    $responseRequest = apiGuzzle($deleteRequestUrl, 'DELETE');
}
return generateRandomString(16);
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
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

$url = $apiHost. '/collections/62/records';