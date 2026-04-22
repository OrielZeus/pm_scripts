<?php 
require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$boolean_valid = false;
########### MsSql Credentials ###############
### default port : 1433
 $serverName = "103.231.241.188, 1433";
 $uid = "cog_dev";
 $pwd = "!2401 Taft!";
 $dbName = "BPMS";
 $connectionInfo = array("Database" => $dbName ,"UID" => $uid, "PWD" => $pwd);
 $conn = sqlsrv_connect( $serverName, $connectionInfo);

#### Get Current Academic Year ####
// API Token
$pmHost = getenv('API_HOST');
$host_link = getenv('HTTP_HOST');
$request_link = getenv('REQUEST_URI');
$pmToken = getenv('API_TOKEN');
$userId = $data['_request']['user_id'];
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);

$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
$api_folder = '/api/1.0';
$collection_path = '/collections/99/records/'; //--> change the collection number

$query_url = $pmHost.$collection_path.'?include=data&pmql=(data.is_active="Y")';

$curl = curl_init($query_url);
curl_setopt($curl, CURLOPT_URL, $query_url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($curl);
curl_close($curl);
$json_output = json_decode($response);
$academic_year_start = $json_output -> data[0] -> data -> start_date;
## check if COG is within the current academic year

########## Query #################
$term = $data['term'];
$ayfrom = $data['ayearStart'];
$ayto = $data['ayearEnd'];
$faculty_id = $data['facultyId'];
$student_id = $data['studentId'];
$course_code = $data['courseCode'];
$section = $data['section'];
$sy_term = $ayfrom . $term;

$query_stmt = "SELECT * FROM [BPMS].[dbo].[COG_GUMS] where SY_TERM = '".$sy_term."' and FACULTY_NO = '".$faculty_id."' and STUDENT_NO = '".$student_id."' and COURSE_CODE = '".$course_code."' and SECTION = '".$section."';";    
$stmt = sqlsrv_query( $conn, $query_stmt);    

if ($stmt == null){
    $boolean_valid = true;
}
else{
    # Faculty had change of grade for the student but not sure if the same academic year
    $cog_date = $stmt['CONFIRMATION_DATE'] ;
    if ($academic_year_start > $cog_date){
        $boolean_valid = true; // last term pa sya nag COG
    }
}

sqlsrv_close( $conn);

return $boolean_valid;