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
$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);

// 
$member_id = 927;
// $values['member_id'] = $member_id;
$payload = json_encode(['data'=>$values]);


$payload = json_encode(['data'=>$values]);
// insert the new user to the User Profile Collection

$pm4_groups_members = $pmHost.'/group_members/?per_page=999999';
$pm4_group_member_curl = curl_init($pm4_groups_members);
curl_setopt($pm4_group_member_curl, CURLOPT_URL, $pm4_groups_members);
curl_setopt($pm4_group_member_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_group_member_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_group_member_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_group_member_curl, CURLOPT_SSL_VERIFYPEER, false);
$group_member_result = curl_exec($pm4_group_member_curl);
curl_close($pm4_group_member_curl);
$group_member_result_json = json_decode($group_member_result);
$group_member_list = $group_member_result_json -> data;
$group_member_list_count = count($group_member_list);
$new_array = [];

for($x = 0; $x < $group_member_list_count; $x++){
    $group_name = $group_member_list[$x] -> name;
    $user_id = $group_member_list[$x] -> member_id;
    if($group_name == "RFP Requestors"){
        // get user information
        $apiInstance = $api->users();
        $user = $apiInstance->getUserById($user_id);
        $user_record = array("Name"=> $user['fullname'] , "Email"=> $user['email'], "Department" => $user['title']);
        array_push($new_array, $user_record);
    }
}

return  $new_array;