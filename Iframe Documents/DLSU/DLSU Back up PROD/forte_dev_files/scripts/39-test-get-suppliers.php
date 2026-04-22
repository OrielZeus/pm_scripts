<?php 
/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use get_env("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/sdk-php#documentation-for-api-endpoints 
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

try {
    ////---- API GroupUsers setting
    $hostUrl = $_SERVER['HOST_URL'];
    $apiToken = getenv('API_TOKEN');
    $apiHost = getenv('API_HOST');
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);

    ////---- getting all suppliers
    $campusRequest = new Request('GET', $apiHost . '/collections/11/records?per_page=2', $headers);
    $campusResponse = $client->send($campusRequest);
    $suppliers = json_decode($campusResponse->getBody()->getContents(), true);

    return [
        'suppliers' => $suppliers['data']
    ];
} catch (Exception $error) {
    echo 'There was an error while running trigger: ' . $error;
}