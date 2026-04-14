<?php
/*  
 * Get list of processes with API connection
 * by Nestor Orihuela
 * modified by Helen Callisaya
 */

 /** Call Processmaker API
 *
 * @param (string) $url 
 * @return (Array) $responseCurl 
 *
 * by Helen Callisaya
 */
function callGetCurl($url)
{
    $token = getenv('API_TOKEN');
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $token,
            "Content-Type: application/json",
            "cache-control: no-cache"
        ),
    ));
    $responseCurl = curl_exec($curl);
    $responseCurl = json_decode($responseCurl);
    curl_close($curl);

    return $responseCurl;
}

//Get Api Token
$api_token = getenv('API_TOKEN');
//Get Api Host
$api_host = getenv('API_HOST');

/*********************** Get values - Processes *********************************/
//Curl init
$curl = curl_init();
//Curl to the End point
curl_setopt_array($curl, array(
    CURLOPT_URL => $api_host . '/processes?order_by=name&order_direction=asc&per_page=1000000&pmql=(status="ACTIVE")',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $api_token
    ),
));
//Set response
$response = curl_exec($curl);
//Set error
$err = curl_error($curl);
curl_close($curl);

/*********************** Set values on data UTP_PROCESS *********************************/
if ($err) {
    //If there is an error, set the error
    $data['UTP_PROCESSES'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response, true);
    //Initialize the increment auxiliar value
    $aux = 0;
    for ($i = 0; $i < count($response['data']); $i++) {
        //Compare response data id with _request process id 
        if ($response['data'][$i]['id'] != $data['_request']['process_id']) {
            //Set Different processes to return
            $data['UTP_PROCESSES'][$aux]['ID'] = $response['data'][$i]['id'];
            $data['UTP_PROCESSES'][$aux]['NAME'] = $response['data'][$i]['name'];
            //Increase aux 
            $aux = $aux + 1;
        }
    }
}
//Initial Status
$data['UTP_STATUS'] = "In progress";
//Set User Requestor
$urlUserId = getenv('API_HOST') . '/users/' . $data['_request']['user_id'];
$responseUserId = callGetCurl($urlUserId); 
if (!$data['_request']['errors']) {
    $data['UTP_USER_FULLNAME'] = $responseUserId->firstname . ' ' . $responseUserId->lastname;
}
//Return all Values
return $data;