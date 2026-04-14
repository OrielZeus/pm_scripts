<?php
/*****************************************************  
 * Populate forte order of old cases
 *
 * by Cinthia Romero
 ****************************************************/
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
$urlApiYacht = getenv('API_HOST') . '/requests?type=in_progress&order_by=updated_at&order_direction=desc&per_page=500&pmql=';
$pmqlSearchYacht = '(process_id = "6")';
$urlApiSearchYacht = $urlApiYacht . urlencode($pmqlSearchYacht);
$responseSearchYacht = callGetCurl($urlApiSearchYacht, "GET", "");
$casesUpdated = array();
for ($i = 0; $i <= count($responseSearchYacht['data']); $i++) {
    if ($responseSearchYacht['data'][$i]['id'] == 1240) {
        //Get request data
        $requestId = $responseSearchYacht['data'][$i]['id'];
        $url = getenv('API_HOST') . '/requests/' . $requestId . '?include=data'; 
        $requestsCompleteInfo = callGetCurl($url, "GET", $request);
        $yachtRequestData = $requestsCompleteInfo["data"];
        //Get and update data Gestion Solicitudes
        $searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $requestId . '")';
        $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
        if (count($searchRequest["data"]) > 0) {
            $dataSendGestion = $searchRequest['data'][0]['data'];
            //Saving forte order to report
            $dataSendGestion['YQP_FORTE_ORDER'] = empty($yachtRequestData['YQP_FORTE_ORDER']) ? "" : $yachtRequestData['YQP_FORTE_ORDER'];
            //
            //return $dataSendGestion;
            $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
            $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSendGestion));
            $casesUpdated[] = $requestId;
        }
   }
}
$returnArray = array();
$returnArray["totalCases"] = count($responseSearchYacht['data']);
$returnArray["totalCasesUpdated"] = count($casesUpdated);
$returnArray["casesUpdated"] = $casesUpdated;
return $returnArray;