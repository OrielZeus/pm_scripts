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


$user_input = "2026-03-01";
$supplier = "DEL-CC%25LAUNDRY%25SERVICES";

// %20 = space
// %25 = %
// %22 = "

$invoice_number = "HON";
// $invoice_number = 'RFP-MC-LI-138455';

$pm4_users = $pmHost.'/collections/9/records?pmql=(upper(data.MNL_DATE)%20LIKE%20%22%25'.$user_input.'%%25%22)';
// $pm4_users = $pmHost.'/collections/9/records?pmql=(upper(data.MNL_DATE)%20LIKE%20%22%25'.$user_input.'%%25%22%20and%20data.SUPPLIER%20LIKE%20%22%25'.$supplier.'%%25%22)';
// $pm4_users = $pmHost.'/collections/9/records?pmql=(upper(data.MNL_DATE)%20LIKE%20%22%25'.$user_input.'%%25%22%20and%20data.INVOICE_NUMBER%20LIKE%20%22%25'.$invoice_number.'%%25%22)';

// $pm4_users = $pmHost.'/collections/9/records?pmql=(data.INVOICE_NUMBER%20LIKE%20%22%25'.$invoice_number.'%%25%22)';
// return $pm4_users;

$pm4_user_curl = curl_init($pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_URL, $pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_result = curl_exec($pm4_user_curl);
curl_close($pm4_user_curl);
$user_result_json = json_decode($user_result);

return count($user_result_json->data);

$array_list = [];
$array_collection = $user_result_json->data;
if($array_collection != null){
   for($x=0; $x<count($array_collection); $x++){
      $record = $array_collection[$x]->data;
      $record_id = $record->id;
      $values['LINE'] = $record->LINE;
      $values['TYPE'] = $record->TYPE;
      // $values['MNL_DATE'] = "2025-08-04".substr($record->MNL_DATE, 10);
      $values['MNL_DATE'] = $record->MNL_DATE;
      $values['SUPPLIER'] = $record->SUPPLIER;
      $values['INVOICE_DATE'] = $record->INVOICE_DATE;
      $values['DESCRIPTION'] = $record->DESCRIPTION;
      $values['DESCRIPTION'] .= ";".substr($values['INVOICE_DATE'], 0, 10);
      $values['INVOICE_DATE'] = "2026-03-03".substr($values['INVOICE_DATE'], 10);
      // $values['INVOICE_DATE'] = $record->INVOICE_DATE;
      $values['SUPPLIER_ID'] = $record->SUPPLIER_ID;
      if(isset($record->ACTIVITY_DATE)){
         $values['ACTIVITY_DATE'] = $record->ACTIVITY_DATE;
      }
      else{
         $values['ACTIVITY_DATE'] = null;
      }
      $values['INVOICE_TYPE'] = $record->INVOICE_TYPE;
      $values['BUSINESS_UNIT'] = $record->BUSINESS_UNIT;
      $values['CAMPUS_CHOICE'] = $record->CAMPUS_CHOICE;
      $values['PAYMENT_TERMS'] = $record->PAYMENT_TERMS;
      $values['SUPPLIER_SITE'] = $record->SUPPLIER_SITE;
      $values['INVOICE_AMOUNT'] = $record->INVOICE_AMOUNT;
      $values['INVOICE_NUMBER'] = $record->INVOICE_NUMBER;
      $values['URL_ATTACHMENTS'] = $record->URL_ATTACHMENTS;
      $values['INVOICE_CURRENCY'] = $record->INVOICE_CURRENCY;
      $values['INVOICE_RECEIVED_DATE'] = $record->INVOICE_RECEIVED_DATE;
      $values['ADDITIONAL_INFORMATION'] = $record->ADDITIONAL_INFORMATION;
      $values['LIABILITY_DISTRIBUTION'] = $record->LIABILITY_DISTRIBUTION;
      $values['DISTRIBUTION_COMBINATION'] = $record->DISTRIBUTION_COMBINATION;

      $payload = json_encode(['data'=>$values]);

      array_push($array_list, $values['INVOICE_NUMBER']);

      // update collection record      
      $ddd = new Request(
      "PUT",
      "/api/1.0/collections/9/records/".$record_id,                
            [
      "Accept" => "application/json, text/plain, */*",
      "Authorization" => "Bearer $pmToken",
      "Content-Type" => "application/json; charset=utf-8",
      "Body" =>   $payload
            ],
            $payload
      );
      $request = $client->send($ddd);
   }
}

return $array_list;