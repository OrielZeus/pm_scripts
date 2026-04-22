<?php 
// $requests = "https://dlsu.cloud.processmaker.net/requests";
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

// $apiInstance = $api->requests();
//$user = $apiInstance->getUserById($user_id);


$host_link = getenv('HTTP_HOST');
$request_link = getenv('REQUEST_URI');
$pmToken = getenv('API_TOKEN');
$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
// $api_folder = '/api/1.0/users';

$url = "https://dlsu.cloud.processmaker.net/requests/?per_page=99999'";
$pm4_users = $pmHost.'/requests?per_page=99999';
$pm4_user_curl = curl_init($pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_URL, $pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_result = curl_exec($pm4_user_curl);
curl_close($pm4_user_curl);
$user_result_json = json_decode($user_result)->data;
$user_ids = [];
return $user_result_json;
foreach($user_result_json as $json){
    $user_id = $json->user_id;
    $not_found=true;
    foreach($user_ids as $consolidated_record){
        if($consolidated_record['user_id']==$user_id){
            $consolidated_record['counter']+=1;
            $not_found = false;
        }
    }
    if($not_found){
        $inserted_value = ["user_id"=>$user_id, "counter"=>1];
        array_push($user_ids, $inserted_value);
    }
}
return $user_ids;


function topUsers($requests) {
    $userCounts = [];
    foreach ($requests as $request) {
        $date = new DateTime($request["date"]);
        if ($date > new DateTime('2023-01-01') && $request['status']=="Completed") {
            $user = $request["user"];
            if (!isset($userCounts[$user])) {
                $userCounts[$user] = 0;
            }
            $userCounts[$user]++;
        }
    }
    arsort($userCounts);
    return array_slice($userCounts, 0, 5, true);
}

$topUsers = topUsers($requests);
return [
    "topUsers" => $topUsers,
];


/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use getenv("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/docker-executor-php/tree/master/docs/sdk 
 */

 return [];