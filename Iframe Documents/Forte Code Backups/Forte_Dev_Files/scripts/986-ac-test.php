<?php 
/*  
 *  Test Guzzle
 *  by Adriana Centellas
 */
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");

$notificationLogcollectionId = 35;

$url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";

$records = callApiUrlGuzzle($url, "GET")["data"];

$result = []; // will collect all rows

foreach ($records as $item) {
    // Defensive checks in case fields are missing
    $email   = $item['data']['EMAIL_ADDRESS']  ?? null;

    // Append (do not overwrite fixed keys)
    $result[] = [
        'CQP_CC_EMAIL'  => $email
    ];
}

return ["CQP_CC_LIST" => $result];

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