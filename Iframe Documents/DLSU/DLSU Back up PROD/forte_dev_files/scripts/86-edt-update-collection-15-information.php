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

# get change of grade schedule
$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
$api_folder = '/api/1.0';
###############################################################
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
###############################################################
$approver_collection = '/collections/15/records'; // --> change the collection number
$approver_colection_path = $pmHost.$approver_collection;
$approver_user_curl = curl_init($approver_colection_path);
curl_setopt($approver_user_curl, CURLOPT_URL, $approver_colection_path);
curl_setopt($approver_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($approver_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($approver_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($approver_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$approver_result = curl_exec($approver_user_curl);
curl_close($approver_user_curl);
$approver_result_json = json_decode($approver_result);
##################################################################
$approver_count = count($approver_result_json -> data);
$user_count = count($user_result_json -> data);
for ($x = 0; $x <= $approver_count; $x++) {
    $record_id = $approver_result_json -> data[$x] -> data -> id;
    $record_user_id = $approver_result_json -> data[$x] -> data -> RUL_USER;

    for ($y = 0; $y <= $user_count; $y++) {
        $user_id = strtoupper($user_result_json -> data[$y] -> id);

        if ($record_user_id != '' && $record_user_id == $user_id)
        {            
            $values['RUL_CODE'] = $approver_result_json -> data[$x] -> data -> RUL_CODE;
            $values['RUL_USER']=  $approver_result_json -> data[$x] -> data -> RUL_USER;
            $values['dept'] = strtoupper($user_result_json -> data[$y] -> title);
            $values['aprName'] = strtoupper($user_result_json -> data[$y] -> fullname);
           
            $payload = json_encode(['data'=>$values]);

            // update collection            
            $ddd = new Request(
            "PUT",
            "/api/1.0/collections/15/records/".$record_id,                
                [
            "Accept" => "application/json, text/plain, */*",
            "Authorization" => "Bearer $pmToken",
            "Content-Type" => "application/json; charset=utf-8",
            "Body" =>   $payload
                ],
                $payload
            );
            $request = $client->send($ddd);
            $y = $user_count +  1;
        }
    }
}


return $payload;