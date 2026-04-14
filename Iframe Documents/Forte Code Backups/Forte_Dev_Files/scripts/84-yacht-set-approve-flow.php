<?php
/*  
 * Set User Underwriter
 * by Helen Callisaya
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
    $responseCurl = json_decode($responseCurl, true);
    curl_close($curl);
    
    return $responseCurl;
}
//Set User Process
$urlUserId = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$responseUserId = callGetCurl($urlUserId);

$dataRequest['YQP_USER_FULLNAME'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'];

return $dataRequest;