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

$host_link = getenv('HTTP_HOST');
$request_link = getenv('REQUEST_URI');
$pmToken = getenv('API_TOKEN');
$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
// $api_folder = '/api/1.0/users';

$pm4_users = $pmHost.'/collections/9/records?pmql=(data.MNL_DATE%20>%20NOW%20-2%20DAY)';
$pm4_user_curl = curl_init($pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_URL, $pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_result = curl_exec($pm4_user_curl);
curl_close($pm4_user_curl);
$user_result_json = json_decode($user_result)->data;
$payload_data = [];
$payload_count = count($user_result_json);

for($x = 0; $x < $payload_count; $x++){
    $id = $user_result_json[$x]->data->id;
    $INVOICE_NUMBER = $user_result_json[$x]->data->INVOICE_NUMBER;
    $INVOICE_AMOUNT = $user_result_json[$x]->data->INVOICE_AMOUNT;
    $INVOICE_DATE = $user_result_json[$x]->data->INVOICE_DATE;
    $MNL_DATE =  $user_result_json[$x]->data->MNL_DATE;
    $SUPPLIER_ID =  $user_result_json[$x]->data->SUPPLIER_ID;
    $SUPPLIER =  $user_result_json[$x]->data->SUPPLIER;
    $record = array("ID"=> $id, "INVOICE_NUMBER" => $INVOICE_NUMBER, "INVOICE_DATE" => $INVOICE_DATE,
                    "MNL_DATE" => $MNL_DATE, "SUPPLIER_ID" => $SUPPLIER_ID,
                    "SUPPLIER" => $SUPPLIER, "INVOICE_AMOUNT" => $INVOICE_AMOUNT);
    array_push($payload_data, $record);
}

return $payload_data;