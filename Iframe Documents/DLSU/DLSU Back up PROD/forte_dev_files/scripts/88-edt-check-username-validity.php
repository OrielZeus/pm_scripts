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


#### ------------------------- Input Fields -------------------------------------- ####
$inputted_username = $data['username'];
// $inputted_username = "elizabeth.tan";
$transaction_type = $data['transaction_type'];
// $transaction_type = "Create";
#### ------------------------------------------------------------------------------ ####
// $department_collection_number = "80";
$department_collection_number = "148";
$user_profile_collection_number = "121";
$username_used = "false";
$user_id = 0;
#### ------------------------------------------------------------------------------ ####

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
$pm4_status = "";

for($x = 0; $x < $array_count ; $x++){
    $pm4_username =  $array[$x] -> username;
    if($pm4_username == $inputted_username){
        $pm4_status =  $array[$x] -> status;
        $username_used = "true";
        if($pm4_status == "ACTIVE"){
            $user_id =  $array[$x] -> id;
            $user_info =  $array[$x];
            $x = $array_count;
        }
    }
}

##############################################################################################################
#### ------------------------------------- Group of the User -------------------------------------------- ####

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
$group_output = [];
$originial_group_member_list = [];

$original_group_member_ids = [];
if($transaction_type != "Create"){
    for($x = 0; $x < $group_member_list_count;  $x++){
        $member_id_from_list = $group_member_list[$x] -> member_id;
        if($user_id == $member_id_from_list){
            array_push($originial_group_member_list, $group_member_list[$x]);
            $group_number = $group_member_list[$x] -> group_id;
            $pm4_groups = $pmHost.'/groups/'.$group_number;

            $pm4_group_curl = curl_init($pm4_groups);
            curl_setopt($pm4_group_curl, CURLOPT_URL, $pm4_groups);
            curl_setopt($pm4_group_curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($pm4_group_curl, CURLOPT_HTTPHEADER, $pmHeaders);
            curl_setopt($pm4_group_curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($pm4_group_curl, CURLOPT_SSL_VERIFYPEER, false);
            $group_result = curl_exec($pm4_group_curl);
            curl_close($pm4_group_curl);
            $group_result_json = json_decode($group_result);
            $group_info = $group_result_json;

            array_push($group_output, $group_info);

        }
    }
}
// $group_output = "";
// foreach(array_keys($group_list) as $key){
//     $group_output =  $group_list[$key];
// }

##############################################################################################################
#### ------------------------------------- College and Department --------------------------------------- ####
// $user_id = 549;
$pm4_profile_collection = $pmHost.'/collections/'.$user_profile_collection_number.'/records?include=data&pmql=(data.user_id='.$user_id.')';
$pm4_profile_curl = curl_init($pm4_profile_collection);
curl_setopt($pm4_profile_curl, CURLOPT_URL, $pm4_profile_collection);
curl_setopt($pm4_profile_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_profile_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_profile_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_profile_curl, CURLOPT_SSL_VERIFYPEER, false);
$pm4_user_profile = curl_exec($pm4_profile_curl);
curl_close($pm4_profile_curl);
$pm4_user_profile_json = json_decode($pm4_user_profile);

/*
PM4_user_info
*/
if($transaction_type != "Create"){
    $record_id =  $pm4_user_profile_json -> data[0] -> id ;

    if($record_id != null){
        $pm4_id = $pm4_user_profile_json -> data[0] -> data -> user_id;
        $employee_id = $pm4_user_profile_json -> data[0] -> data ->employee_id;
        $email = $pm4_user_profile_json -> data[0] -> data ->employee_email;
        $firstname = $pm4_user_profile_json -> data[0] -> data ->employee_first_name; 
        $lastname = $pm4_user_profile_json -> data[0] -> data ->employee_last_name;
        $college =  $pm4_user_profile_json -> data[0] -> data ->college;
        $college_id = $pm4_user_profile_json -> data[0] -> data ->college -> id;
        $college_name =  $pm4_user_profile_json -> data[0] -> data ->college -> name;
        $department =  $pm4_user_profile_json -> data[0] -> data ->department;
        $domain = $pm4_user_profile_json -> data[0] -> data ->domain;
        $campus =  $pm4_user_profile_json -> data[0] -> data ->campus;
        $position = $pm4_user_profile_json -> data[0] -> data ->position;
        /*
        if(is_array($domain)){
            $domain_checking = implode($domain);
            $with_slc_domain = json_encode(str_contains($domain_checking, 'SLC'));
        }
        */
        $group = $pm4_user_profile_json -> data[0] -> data ->group;
    }
    else{
        $pm4_id = $user_info -> id;
        $employee_id = $user_info -> phone;
        $email = $user_info -> email;
        $firstname = $user_info -> firstname; 
        $lastname = $user_info -> lastname;
        // $college =  $pm4_user_profile_json -> data[0] -> data ->college;
        // $college_id = $pm4_user_profile_json -> data[0] -> data ->college -> id;
        // $college_name =  $pm4_user_profile_json -> data[0] -> data ->college -> name;
        // $department =  $pm4_user_profile_json -> data[0] -> data ->department;
        // $domain = $pm4_user_profile_json -> data[0] -> data ->domain;
        // $group = $pm4_user_profile_json -> data[0] -> data ->group;
    }
}

/*
user_profile_collection
*/
#### ---------------------------------------------------------------------------------------------------- ####
// $college_name = "";
$pm4_departments = $pmHost.'/collections/'.$department_collection_number.'/records?include=data&pmql=(data.college = "'.$college_name.'")';
$pm4_department_curl = curl_init($pm4_departments);
curl_setopt($pm4_department_curl, CURLOPT_URL, $pm4_departments);
curl_setopt($pm4_department_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_department_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_department_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_department_curl, CURLOPT_SSL_VERIFYPEER, false);
$department_result = curl_exec($pm4_department_curl);
curl_close($pm4_department_curl);
$department_result_json = json_decode($department_result);
$department_result_json_array =  ($department_result_json -> data);
if($department_result_json_array != null){
    $department_result_json_array_count = count($department_result_json_array);
    $all_departments = [];
    for( $i = 0; $i < $department_result_json_array_count; $i++){
        $retrieved_department =  $department_result_json_array[$i] -> data;
        array_push($all_departments, $retrieved_department);
    }
}

return [
    // "user_profile_collection" => $pm4_user_profile_json,
    "user_status" => $pm4_status,
    "username" => $inputted_username,
    "user_profile_collection" => $user_profile_collection_number,
    "group_list" => $group_output,
    "username_used" => $username_used,
    "user_id" => $user_id,
    "record_id" => $record_id, 
    "employee_id" => $employee_id, 
    "email" => $email,
    "first_name" => $firstname,
    "last_name" => $lastname,
    "domain" => $domain,
    "with_slc_domain" => $with_slc_domain,
    "college" => $college, // collection
    "college_number" => $college_id, // collection
    "college_copy" => $college, // collection
    "department" => $department, // collection
    "group" => $group, //collection
    "originial_group_member_list" => $originial_group_member_list,
    "college_name" => $college_name,
    "department_list" => $all_departments,
    "PM4_user_info" => $user_info,
    "campus" => $campus,
    "position" => $position
];