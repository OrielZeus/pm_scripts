<?php 
/*   
 *  Clear pre-loaded data when a new search is performed
 *  by Helen Callisaya
 */

/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url, $method, $json_data)
{
    try {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . getenv('API_TOKEN'),
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $responseCurl = curl_exec($curl);
        if ($responseCurl === false) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }
        $responseCurl = json_decode($responseCurl, true);
        curl_close($curl);
        return $responseCurl;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}

//Set variable of return
$dataClean = array();
//Restore Data Filter
if ($data['END_ID_SELECTION']) {
    $request = $data['_request']['id'];
    $urlCollection = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?pmql=';
    $getDataOriginal = $urlCollection . urlencode('(data.FORTE_OD_REQUEST="' . $request . '")');
    $getDataOriginalResponse = callGetCurl($getDataOriginal, "GET", "");
    //Exist Data
    if (count($getDataOriginalResponse['data']) > 0) {
        $dataClean['YQP_CLIENT_NAME'] = $getDataOriginalResponse['data'][0]['data']['FORTE_OD_DATA']['YQP_CLIENT_NAME'];
        $dataClean['YQP_INTEREST_ASSURED'] = $getDataOriginalResponse['data'][0]['data']['FORTE_OD_DATA']['YQP_INTEREST_ASSURED'];
        $dataClean['YQP_PIVOT_TABLE_NUMBER'] = $getDataOriginalResponse['data'][0]['data']['FORTE_OD_DATA']['YQP_PIVOT_TABLE_NUMBER'];
        $dataClean['YQP_UMR_CONTRACT_NUMBER'] = $getDataOriginalResponse['data'][0]['data']['FORTE_OD_DATA']['YQP_UMR_CONTRACT_NUMBER'];        
    }
}
//We go through the variables and clean them
foreach ($data as $key => $value) {
    if ($key != "_request" && 
        $key != "_user" && 
        $key != "FORTE_ERRORS" &&
        $key != "YQP_USER_ID" &&
        $key != "YQP_REQUESTOR_NAME" &&
        $key != "YQP_USER_FULLNAME" &&
        $key != "YQP_CREATE_DATE" &&
        $key != "YQP_CLIENT_NAME" &&
        $key != "YQP_INTEREST_ASSURED" &&
        $key != "YQP_PIVOT_TABLE_NUMBER" &&
        $key != "YQP_UMR_CONTRACT_NUMBER" &&
        $key != "FORTE_TITLE_PROCESS") {
            $dataClean[$key] = "";
    }        
}

$dataClean['YQP_STATUS'] = 'PENDING';
return $dataClean;