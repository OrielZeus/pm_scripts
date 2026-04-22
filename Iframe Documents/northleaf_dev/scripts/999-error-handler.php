<?php 
/*  
 *  Error Handler
 *  By Adriana Centellas
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$requestId = $data["_request"]["id"];
$caseNumber = $data["_request"]["case_number"];

$errors = $data["_request"]['errors'];

$errorKey = array_key_first($errors);

$errorLogcollectionId = getCollectionId("ERROR_HANDLING_COLLECTION", $apiUrl);

$nodeId = $errors[$errorKey]["element_id"] . "-" . $errors[$errorKey]["element_name"];

$infoPreviousAttempts = "select max(data->>'$.ATTEMPT') AS ATTEMPT from 
collection_" . $errorLogcollectionId . " 
where data->>'$.REQUEST_ID' = " . $requestId . " 
and data->>'$.NODE_ID' = '" . $nodeId . "'";

$existentResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($infoPreviousAttempts))[0]["ATTEMPT"];

$existentAttempt = intval($existentResponse);

if (!empty($existentAttempt))
{
    $attempt = $existentAttempt + 1;
} else {
    $attempt = 1;
}

$errorsLoop = array_values($errors);

foreach ($errorsLoop as &$error) {
    $error['created_at_dt'] = new DateTime($error['created_at']);
}
unset($error);

usort($errorsLoop, function ($a, $b) {
    return $b['created_at_dt'] <=> $a['created_at_dt'];
});

//Record on collection
$arrayNote = [];
$arrayNote['REQUEST_ID'] = $data["_request"]['id'];
$arrayNote['CASE_NUMBER'] = $data["_request"]['case_number'];
$arrayNote['ERRORS'] = $errorsLoop;
$arrayNote['STATUS'] = $data["_request"]['status'];
$arrayNote['ATTEMPT'] = $attempt;
$arrayNote['NODE_ID'] = $nodeId;
$url = $apiHost . "/collections/" . $errorLogcollectionId . "/records";
$createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);

return ['ERROR_INFO' => $arrayNote, 'ATTEMPT' => $attempt, 'ERRORS_LOOP' => $errorsLoop];