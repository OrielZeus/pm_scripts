<?php 

// API Token
$pmHost = getenv('API_HOST');
$pmToken = getenv('API_TOKEN');
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);

$code = $data['conditions'];

$url = $pmHost.'/collections/15/records?include=data&pmql=((lower(data.RUL_CODE)+LIKE+"'.$code.'"))';

//https://jukumari.pm4trial.processmaker.net/api/1.0/collections/10/records?include=data&pmql=((lower(data.CODE) LIKE "111"))
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);
$Test = json_decode($resp);

$managerId = "";
if (!empty($Test->data[0]->data->RUL_USER)) {
    $managerId = $Test->data[0]->data->RUL_USER;
}

/*$managerEmail = $api->users()->getUserById($managerId)['email'];

die(var_dump($managerEmail)); */

$url = $pmHost . '/users/' . $managerId;

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);
$Test = json_decode($resp); 
//die(var_dump($Test->email)); 
$managerEmail = $Test->email;

return [
    'MANAGER_ID' => $managerId,
    'MANAGER_EMAIL' => $Test->email
];