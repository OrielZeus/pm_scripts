<?php
/**************************************************  
 * Search completed requests in Yacht Process and update
 *
 * by Helen Callisaya
 **************************************************/
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
for ($i = 0; $i <= count($responseSearchYacht['data']); $i++) {
    //Get and update data Gestion Solicitudes
    $searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $responseSearchYacht['data'][$i]['id'] . '")';
    $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
    if (count($searchRequest["data"]) > 0) {
        $dataSendGestion = $searchRequest['data'][0]['data'];
        $dataSendGestion['FORTE_REQUEST_PARENT'] = $responseSearchYacht['data'][$i]['id'];
        $dataSendGestion['FORTE_REQUEST_CHILD'] = $responseSearchYacht['data'][$i]['id'];
        $dataSendGestion['FORTE_REQUEST_ORDER'] = $responseSearchYacht['data'][$i]['id'] . ".0";
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSendGestion));
    }
    // Get and update data Producing Files
    $searchRequestUrlProd = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $responseSearchYacht['data'][$i]['id'] . '")';
    $searchRequestProd = callGetCurl($searchRequestUrlProd, "GET", "");
    if (count($searchRequest["data"]) > 0) {
        $dataSendProducing = $searchRequestProd['data'][0]['data'];
        $dataSendProducing['FORTE_REQUEST_PARENT'] = $responseSearchYacht['data'][$i]['id'];
        $dataSendProducing['FORTE_REQUEST_CHILD'] = $responseSearchYacht['data'][$i]['id'];
        $dataSendProducing['FORTE_REQUEST_ORDER'] = $responseSearchYacht['data'][$i]['id'] . ".0";
        $updateRequestUrlProd = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records/' . $searchRequestProd["data"][0]["id"];
        $updateRequestProd = callGetCurl($updateRequestUrlProd, "PUT", json_encode($dataSendProducing));
    }
}
/*$urlApiYacht = getenv('API_HOST') . '/requests?type=in_progress&order_by=updated_at&order_direction=desc&include=data&per_page=500&pmql=';
$pmqlSearchYacht = '(process_id = "30")';
$urlApiSearchYacht = $urlApiYacht . urlencode($pmqlSearchYacht);
$responseSearchYacht = callGetCurl($urlApiSearchYacht, "GET", "");
for ($i = 0; $i <= count($responseSearchYacht['data']); $i++) {

    $searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST = "' . $responseSearchYacht['data'][$i]['id'] . '")';
    $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
    if (count($searchRequest["data"]) > 0) {
        $dataSendGestion = $searchRequest['data'][0]['data'];
        $dataSendGestion['FORTE_REQUEST_ORDER'] = $responseSearchYacht['data'][$i]['data']['END_ID_SELECTION'] . "." . $responseSearchYacht['data'][$i]['data']['END_NUMBER_ENDORSEMENT'];
        if (!isset($dataSendGestion['FORTE_REQUEST_PARENT'])) { 
            $dataSendGestion['FORTE_REQUEST_CHILD'] = $responseSearchYacht['data'][$i]['id'];
            $dataSendGestion['FORTE_REQUEST_PARENT'] = $responseSearchYacht['data'][$i]['data']['END_ID_SELECTION'];
            $dataSendGestion['END_TYPE_ENDORSEMENT'] = $responseSearchYacht['data'][$i]['data']['END_TYPE_ENDORSEMENT'];
        }
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSendGestion));
    }

    $searchRequestUrlProd = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records?pmql=(data.FORTE_REQUEST = "' . $responseSearchYacht['data'][$i]['id'] . '")';
    $searchRequestProd = callGetCurl($searchRequestUrlProd, "GET", "");
    if (count($searchRequest["data"]) > 0) {
        $dataSendProducing = $searchRequestProd['data'][0]['data'];
        $dataSendProducing['FORTE_REQUEST_ORDER'] = $responseSearchYacht['data'][$i]['data']['END_ID_SELECTION'] . "." . $responseSearchYacht['data'][$i]['data']['END_NUMBER_ENDORSEMENT'];
        if (!isset($dataSendProducing['FORTE_REQUEST_PARENT'])) { 
            $dataSendProducing['FORTE_REQUEST_CHILD'] = $responseSearchYacht['data'][$i]['id'];
            $dataSendProducing['FORTE_REQUEST_PARENT'] = $responseSearchYacht['data'][$i]['data']['END_ID_SELECTION'];
            $dataSendProducing['END_TYPE_ENDORSEMENT'] = $responseSearchYacht['data'][$i]['data']['END_TYPE_ENDORSEMENT'];
        }
        $updateRequestUrlProd = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records/' . $searchRequestProd["data"][0]["id"];
        $updateRequestProd = callGetCurl($updateRequestUrlProd, "PUT", json_encode($dataSendProducing));
    }
}*/

return count($responseSearchYacht['data']);