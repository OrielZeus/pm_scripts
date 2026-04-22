<?php 
require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


#-------------------------- Environment --------------------------------- #

// API Token
$pmHost = getenv('API_HOST');
$host_link = getenv('HTTP_HOST');
$request_link = getenv('REQUEST_URI');
$pmToken = getenv('API_TOKEN');
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);

$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);

#-------------------------- Initialize --------------------------------- #
$user_id = $data['_request']['user_id'];
$request_name = $data['_request']['name'];
// $user_id = 549;
$user_department_list = [];
$requestId = $data['_request']['id'];
$api_folder = '/api/1.0';
$curl_array = [];
$request_link = str_replace("/api/1.0", "", $pmHost."/requests/".$requestId);
# collection numbers
$user_profile_collection = 43; // User profile collection 
$payee_collection = '/collections/23/records';  // supplier initialization (not used)
#-------------------------- Initialize --------------------------------- #

// $user_id = 549;

    // $approver_collection = '/collections/'.$student_grade_collection.'/records'.'?include=data&pmql=(data.term="'.$term.'" and data.a_y_from="'.$ayfrom.'" and data.facultyId="'.$faculty_id.'" and data.studentId="'.$student_id.'" and data.scCode="'.$course_code.'" and data.section="'.$section.'" )';
    $collection_path = '/collections/'.$user_profile_collection.'/records'.'?include=data&pmql=(data.user_id='.$user_id.')';
    $user_profile_collection = $pmHost.$collection_path;
    // ERROR: "Not Found"
    // https://dlsu.dev.cloud.processmaker.net/api/1.0/collections/$collection_number/records

    $approver_user_curl = curl_init($user_profile_collection);
    curl_setopt($approver_user_curl, CURLOPT_URL, $user_profile_collection);
    curl_setopt($approver_user_curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($approver_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
    curl_setopt($approver_user_curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($approver_user_curl, CURLOPT_SSL_VERIFYPEER, false);
    $approver_result = curl_exec($approver_user_curl);
    curl_close($approver_user_curl);
    $approver_result_json = json_decode($approver_result);
    $approver_result_data = $approver_result_json -> data[0] ;
    $multiple_departments = false;

    if($approver_result_data != null){
        $user_id = $approver_result_data -> data -> user_id;
        $user_fullname = $approver_result_data -> data -> employee_name;
        $user_email = $approver_result_data -> data -> employee_email;
        $user_college = $approver_result_data -> data -> college -> name;
        $user_department = $approver_result_data -> data -> department -> department_name;
        $user_employee_id = $approver_result_data -> data -> employee_id;
        $user_department_code = $approver_result_data -> data -> department -> department_code;
        if(is_array($approver_result_data -> data -> department)){
            $user_department_list = $approver_result_data -> data -> department;
            if(count($user_department_list) > 1){
                $multiple_departments = true;
            }
            $user_department_code = $approver_result_data -> data -> department[0] -> department_code;
            $user_department = $approver_result_data -> data -> department[0] -> department_name;
        }
    }
    else{
        if($user_id != null){   
            $apiInstance = $api->users();
            $user = $apiInstance->getUserById($user_id);
            $user_email = $user -> getEmail();
            $user_fullname = $user -> getFullName();
            $user_college = $user -> getFax();
            $user_department = $user -> getTitle();
            $user_employee_id = $user -> getPhone();
        }
    }


// $approver_collection = '/collections/'.$student_grade_collection.'/records'.'?include=data&pmql=(data.term="'.$term.'" and data.a_y_from="'.$ayfrom.'" and data.facultyId="'.$faculty_id.'" and data.studentId="'.$student_id.'" and data.scCode="'.$course_code.'" and data.section="'.$section.'" )';
$collection_path = '/collections/'.$user_profile_collection.'/records'.'?include=data&pmql=(data.user_id='.$user_id.')';
$user_profile_collection = $pmHost.$collection_path;
// ERROR: "Not Found"
// https://dlsu.dev.cloud.processmaker.net/api/1.0/collections/$collection_number/records

$approver_user_curl = curl_init($user_profile_collection);
curl_setopt($approver_user_curl, CURLOPT_URL, $user_profile_collection);
curl_setopt($approver_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($approver_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($approver_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($approver_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$approver_result = curl_exec($approver_user_curl);
curl_close($approver_user_curl);
$approver_result_json = json_decode($approver_result);
$approver_result_data = $approver_result_json -> data[0] ;

// if($approver_result_data != null){
//    $user_id = $approver_result_data -> data -> user_id;
//    $user_fullname = $approver_result_data -> data -> employee_name;
//    $user_email = $approver_result_data -> data -> employee_email;
//    $user_college = $approver_result_data -> data -> college -> name;
//    $requestor_campus = $approver_result_data -> data -> campus -> code;
//    $user_department = $approver_result_data -> data -> department -> description;
//    $user_employee_id = $approver_result_data -> data -> employee_id;
// }
// else{
//    if($user_id != null){   
//       $apiInstance = $api->users();
//       $user = $apiInstance->getUserById($user_id);
//       $user_email = $user -> getEmail();
//       $user_fullname = $user -> getFullName();
//       $user_college = $user -> getFax();
//       $user_department = $user -> getTitle();
//       $user_employee_id = $user -> getPhone();
//    }
// }

return [
    "multiple_departments" => $multiple_departments,
    "requestor_department_list" => $user_department_list,
   "email" => $email,
   "user_id" => $user_id,
   "requestor_employee_id" => $user_employee_id,
   "requestor_fullname" => strtoupper($user_fullname),
   "requestor_email" => $user_email,
   "requestor_campus" => $requestor_campus,
   "requestor_college" => strtoupper($user_college),
   "requestor_department" => strtoupper($user_department),
    "requestor_department_code" => $user_department_code,
    "requestId" => $requestId,
    "request_link" => $request_link,
    "request_name" => $request_name
];