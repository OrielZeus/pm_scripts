<?php 
/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use getenv("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/sdk-php#documentation-for-api-endpoints 
 */

require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


// API Token
$pmHost = getenv('API_HOST');
$host_link = getenv('HTTP_HOST');
$request_link = getenv('REQUEST_URI');
// $pmToken = getenv('API_TOKEN');
$pmToken = 'N0WPby4gMnalY4ms3ecVYLNRYs-4lUv6RViuVDvbbCg';
$userId = $data['_request']['user_id'];



$actual_link = "http://$host_link$request_link";

$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
$api_folder = '/api/1.0';
// $collection_path = '/collections/98/records/'; //--> change the collection number
$collection_path =  "/users";
// $api_collection_path = "http://103.231.241.190:8188/cog/update?restletMethod=POST";
// $api_collection_path = "http://332894b4bbf9.ngrok.io";
// $api_collection_path = "http://7282b837f6ff.ngrok.io"; 3.90.23.200
// $api_collection_path = "https://dlsu-dev.processmaker.net/api/1.0/users";
// $api_collection_path = "http://3cb67bb94a0b.ngrok.io";
// $api_collection_path = "http://103.231.241.190:8190/grade/get?restletMethod=POST";
// $api_collection_path = "http://987f0be9616f.ngrok.io";
// $api_collection_path = "http://103.231.241.190:6011/v1/external/camu/coursestatus";
$api_collection_path = "http://103.231.241.190:6011/v1/external/camu/coursestatus";

$payload = '';

/*
 http://103.231.241.190:8188/cog/update?restletMethod=POST

 "studentid": "214",
   "academicyear": "2019-2020",
   "semester": "TERM2",
   "coursecode": "14ME240",
   "section": "A",
   "facultyid": "A",
   "new_grade": "F",
   "reason": "Testing"
*/

// $values['studedntid'] = "214";
// $values['academicyear'] = "2019-2020";
// $values['semester'] = "TERM2";
$values['CourseCode'] = "14ME240";
// $values['section'] = "A";
// $values['facultyid'] = "A";
// $values['new_grade'] = "F";
// $values['reason'] = "Testing";

$postRequest = array(
    // 'studedntid' => '214',
    // 'academicyear' => '2019-2020',
    // 'semester' => 'TERM2',
    'CourseCode' => '14ME240'
    //,
    // 'section' => 'A',
    // 'facultyid' => 'A',
    // 'new_grade' => 'F',
    // 'reason' => 'Testing'
);

//     "studentid": "11941669",
//    "academicyear": "2019-2020",
//    "semester": "Term 1",
//    "coursecode": "ENVIPO1",
//    "section": "A01",
//    "facultyid": "A"


$query_sent = http_build_query($postRequest, '', '&');

$payload = json_encode([$values]);
$decoded_payload = json_decode($payload);
$elementCount  =  strlen($payload);
$payload = str_replace(array( '[', ']' ), '', $payload);
// // remove the brackets part
$pmHeaders = array(
    "Accept:application/json;",
    'Content-Type:application/json;',                                                                                
    'Content-Length: ' . strlen($payload)
    // ,"Authorization: Bearer ". $pmToken
);
// this one working too
$ddd = new Request(
 "post",
 "$api_collection_path",                
    [
 "Accept" => "application/json, text/plain, */*",
# "Authorization" => "Bearer $pmToken",
 "Content-Type" => "application/json; charset=utf-8",
 "Body" => $payload
    ],
    $payload
);
$request = $client->send($ddd);
$response_code = $request ->getStatusCode();
$response_status = $request->getReasonPhrase();
// $reponse_bosy = $request -> headers();
$reponse_bosy =  $request->getBody()->read(1024);
// // $reply = $client -> receive($ddd);
// $get_rply = $client -> get_headers($api_collection_path);

// // posting a query
// $sched_curl = curl_init($api_collection_path);
// $one = curl_setopt($sched_curl, CURLOPT_URL, $api_collection_path);
// curl_setopt($sched_curl,CURLOPT_POST, 1); // count($payload));
// curl_setopt($sched_curl,CURLOPT_POSTFIELDS, $query_sent);
// // curl_setopt($sched_url, CURLOPT_POSTFIELDS, $payload);
// $two = curl_setopt($sched_curl, CURLOPT_RETURNTRANSFER, true);
// $three = curl_setopt($sched_curl, CURLOPT_HTTPHEADER, $pmHeaders);
// $four = curl_setopt($sched_curl, CURLOPT_SSL_VERIFYHOST, false);
// $five= curl_setopt($sched_curl, CURLOPT_SSL_VERIFYPEER, false);
// $reposnse_bodt = curl_exec($sched_curl);
// $response_data = urldecode(curl_exec($sched_curl));
// curl_close($sched_curl);

 return [
    //  "actual_link" => $actual_link,
    //  "api_collection_path" => $api_collection_path,
    //  "response_received" => $response_data,
    //  "schedule_result" => $schedule_result,
    //  "payload" => $payload,
     "ddd" => $ddd,
     
    "query_sent" => $query_sent,
      "request" => $response_bosy['studentid'],
      "response_code" => $response_code,
      "response_status" => $response_status,
      "response_body" => $reponse_bosy,
     
    "headers" => $pmHeaders,
    "body" => $reposnse_bodt
 ];