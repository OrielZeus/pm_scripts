<?php 
$apiHost = getenv("API_HOST");
$apiToken = getenv("API_TOKEN");

$scriptID = 0;    // ID Script


$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $apiHost . '/admin/package-proservice-tools/finder/php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "c": "ZWNobyAiSGVsbG8iOw==",
    "id" : ' . $scriptID . ',
    "data": {
        "name" : "jorge"
    }
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiToken
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
return $response;