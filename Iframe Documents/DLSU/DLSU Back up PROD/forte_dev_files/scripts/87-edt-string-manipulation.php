<?php 
// https://dlsu-clone-qa.processmaker.net/api/1.0/requests//files


require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


### ------------------------------------------- API Token ------------------------------------------------ ###

// $pmHost = "https://dlsu.dev.cloud.processmaker.net/"; 
// $pmHost = getenv('API_HOST');
// $host_link = getenv('HTTP_HOST'); // environment variable not present
// $pmToken = getenv('API_TOKEN');
// $pmHeaders = array(
//    "Accept: application/json",
//    "Authorization: Bearer ". $pmToken,
// );
// $client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
// $collection_record = "/api/1.0/collections/53/records/";


// // $payload = str_replace(array( '[', ']' ), '', $payload);
// $host_link = str_replace("api/1.0", "", $pmHost);
$description = "Honorarium as reviewer for the proposals entitled \" nterpersonal Emotion Regulation.ilipino Translation and Psychometric Validation' of Climate Emotions Measurement Tools\" for T3 AY24-25";
$description = str_replace("  ", " ", (str_replace("\t", ' ', (str_replace("\n", ' ', $description)))));       // Description                  --->    Transaction Type changed to Description
$description = str_replace("\"", "-", $description);

$input_value = "DLSU-1234DLSU!";
$description = preg_replace('/[^0-9]+/', '', $input_value);

return $description;

 return [ 
    "pmHost" => $pmHost,
    "host_link" => $host_link
 ];