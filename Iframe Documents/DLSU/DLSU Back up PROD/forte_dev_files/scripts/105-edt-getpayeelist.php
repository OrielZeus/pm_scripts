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

$user_input = strtoupper($data['form_input_1']);
// $user_input = "C%26AMP;K%251989%25HOTPOT";
// $user_input = "DIAR&#39;S ASSISTANCE INC";
$user_input =  str_replace(" ","%25",$user_input); 
$user_input =  str_replace("%26AMP;","%26",$user_input); //	&amp;
$user_input =  str_replace("&AMP;","%26",$user_input); //	&amp;
$user_input =  str_replace("&#39;","%27",$user_input); //	apostrophe

$user_input =  str_replace("&","%26",$user_input); //	&amp;

$pm4_users = $pmHost.'/collections/23/records?pmql=(upper(data.NAME)%20LIKE%20%22%25'.$user_input.'%25%22)';
$pm4_user_curl = curl_init($pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_URL, $pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_result = curl_exec($pm4_user_curl);
curl_close($pm4_user_curl);
$user_result_json = json_decode($user_result);
$array_list = [];
$array_collection = $user_result_json->data;
if($array_collection != null){
   for($x=0; $x<count($array_collection); $x++){
      array_push($array_list, $array_collection[$x]->data);
   }
}

return $array_list;