<?php 

require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$pmHost = getenv('API_HOST');
$pmToken = getenv('API_TOKEN');
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);

$getRequestor = $pmHost.'/requests/113338';
$pm4_requestor_curl = curl_init($getRequestor);
curl_setopt($pm4_requestor_curl, CURLOPT_URL, $getRequestor);
curl_setopt($pm4_requestor_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_requestor_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_requestor_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_requestor_curl, CURLOPT_SSL_VERIFYPEER, false);
$requestor_result = curl_exec($pm4_requestor_curl);
curl_close($pm4_requestor_curl);
$requestor_result_json = json_decode($requestor_result);

// $apiInstance = $api->users();
// $user = $apiInstance->getUserById(549);
// $group_name = $user -> getEmail();

$user_id = $requestor_result_json->user_id;
$getUserInformation = $pmHost.'/users/'.$user_id;
$pm4_user_information_curl = curl_init($getUserInformation);
curl_setopt($pm4_user_information_curl, CURLOPT_URL, $getUserInformation);
curl_setopt($pm4_user_information_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_information_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_information_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_information_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_email_result = curl_exec($pm4_user_information_curl);
curl_close($pm4_user_information_curl);
$user_email_result_json = json_decode($user_email_result);
$user_email = $user_email_result_json->email;



return $requestor_result;