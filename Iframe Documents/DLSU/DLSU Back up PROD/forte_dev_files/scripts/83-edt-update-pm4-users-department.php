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
$api_folder = '/api/1.0/users';

$pm4_users = $pmHost.'/users/?per_page=99999';
$pm4_user_curl = curl_init($pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_URL, $pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_result = curl_exec($pm4_user_curl);
curl_close($pm4_user_curl);
$user_result_json = json_decode($user_result);
$array = $user_result_json -> data;
$array_count = count($array);
$pm4_users = [];

for($x = 0; $x < $array_count ; $x++){
    $user_id =  $array[$x] -> id;
    $username =  $array[$x] -> username;
    $email =  $array[$x] -> email;
    $fullname =  $array[$x] -> fullname;
    $status =  $array[$x] -> status;
    $department = $array[$x] -> title;
    $created_at =  $array[$x] -> created_at;
    $loggedin_at =  $array[$x] -> loggedin_at;

    $pm4_user = array("user_id"=> $user_id, 
                        "username"=> $username, 
                        "email"=> $email, 
                        "fullname"=> $fullname, 
                        "status"=> $status, 
                        "department"=> $department, 
                        "created_at"=> $created_at, 
                        "last_login"=> $loggedin_at);
    array_push($pm4_users, $pm4_user);
    /*
    if(strpos(strtolower($department), "vice chancellor for laguna")){
        $new_department = "OFFICE OF THE VICE PRESIDENT FOR LAGUNA CAMPUS";

        $apiInstance = $api->users();
        $user = $apiInstance->getUserById($user_id);
        $user->setTitle($new_department);
        $apiInstance->updateUser($user_id, $user);
        
        $pm4_user = array("user_id"=> $user_id, "username"=> $username, "email"=> $email, "fullname"=> $fullname, "status"=> $status, "department"=> $department);
        array_push($pm4_users, $pm4_user);
    } 
    if(strpos(strtolower($department), "office of the academics director - laguna campus")){
        $new_department = "OFFICE OF THE ASSOCIATE DEAN - LAGUNA CAMPUS";

        $apiInstance = $api->users();
        $user = $apiInstance->getUserById($user_id);
        $user->setTitle($new_department);
        $apiInstance->updateUser($user_id, $user);
        
        $pm4_user = array("user_id"=> $user_id, "username"=> $username, "email"=> $email, "fullname"=> $fullname, "status"=> $status, "department"=> $department);
        array_push($pm4_users, $pm4_user);
    }    
    if(strpos(strtolower($department), "associate principal for grade school")){
        $new_department = "INTEGRATED SCHOOL (IS) OFFICE OF THE VICE PRINCIPAL FOR GRADE SCHOOL";

        $apiInstance = $api->users();
        $user = $apiInstance->getUserById($user_id);
        $user->setTitle($new_department);
        $apiInstance->updateUser($user_id, $user);
        
        $pm4_user = array("user_id"=> $user_id, "username"=> $username, "email"=> $email, "fullname"=> $fullname, "status"=> $status, "department"=> $department);
        array_push($pm4_users, $pm4_user);
    }   
    */
}

return $pm4_users;