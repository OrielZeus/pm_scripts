<?php
/*  
 * Get Information about the documents from the collection
 * by Nestor Orihuela
 * modified by Ana Castillo
 * modified by Helen Callisaya
 * modified by Cinthia Romero
 */
//Set parameters to get information

$processID = html_entity_decode($data["UTP_PROCESS_ID"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$language = html_entity_decode($data["UTP_LANGUAGE"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$type = html_entity_decode($data["UTP_TYPE"], ENT_QUOTES | ENT_XML1, 'UTF-8');
$baseText = html_entity_decode($data["UTP_BASE_TEXT"], ENT_QUOTES | ENT_XML1, 'UTF-8');

//Validate that variables needed are not empty
if ($processID != null && $processID != "" && 
    $language != null && $language != "" && 
    $type != null && $type != "") {
    //Set pmql when type is not a Slip
    $pmql = '?pmql=' . urlencode('(data.UTP_TE_PROCESS_ID = ' . $processID . ' AND data.UTP_TE_LANGUAGE = "' . $language . '" AND data.UTP_TE_TYPE = "' . $type . '")');

    //Validate Base text if Type is Slip
    $flagSlip = false;
    if ($type == "SLIP") {
        //Validate Base Text is not null
        if ($baseText != null && $baseText != "") {
            $flagSlip = true;
            //Set pmql when type is Slip
            $pmql = '?pmql=' . urlencode('(data.UTP_TE_PROCESS_ID = ' . $processID . ' AND data.UTP_TE_LANGUAGE = "' . $language . '" AND data.UTP_TE_TYPE = "' . $type . '" AND data.UTP_TE_BASE_TEXT = "' . $baseText . '")');
        }
    } else {
        $flagSlip = true;
    }
    //Continue if Base text is not null or if it is not Slip Type
    if ($flagSlip) {
        //Set api Token and Host
        $apiToken = getenv('API_TOKEN');
        $apiHost = getenv('API_HOST');
        //Get id of the collection for Templates
        $collectionID = getenv('UTP_TEMPLATES_COLLECTION');

        /*********************** Get values - Templates Collection *********************************/
        //Curl init
        $curl = curl_init();
        //Curl to the End point
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiHost . '/collections/' . $collectionID . '/records' . $pmql,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $apiToken
            ),
        ));
        //Set response
        $response = curl_exec($curl);
        //Decode response
        $response = json_decode($response, true);
        //Validate if there is not an error
        if ($response['error'] == "") {
            if (is_array($response['data'])) {
                //Variable for the loop of documents
                $loopCount = 0;
                //Assign response data to UTP_UPLOAD_DOCUMENTS
                for ($i = 0; $i < count($response['data']); $i++) {
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_ORDER'] = $response['data'][$i]['data']['UTP_TE_ORDER'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT'] = $response['data'][$i]['data']['UTP_TE_DOCUMENT_ID'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DESCRIPTION'] = $response['data'][$i]['data']['UTP_TE_DESCRIPTION'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_REPEAT_DOCUMENT'] = $response['data'][$i]['data']['UTP_TE_REPEAT_DOCUMENT'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_VARIABLE_TO_LOOP'] = $response['data'][$i]['data']['UTP_TE_VARIABLE_TO_LOOP'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_USE_OPENL'] = $response['data'][$i]['data']['UTP_TE_USE_OPENL'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_OPENL_URL'] = $response['data'][$i]['data']['UTP_TE_OPENL_URL'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_VARIABLE_TO_SAVE_REPONSE'] = $response['data'][$i]['data']['UTP_TE_VARIABLE_TO_SAVE_REPONSE'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_REQUEST_ID'] = $response['data'][$i]['data']['UTP_TE_REQUEST_ID'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DATA_NAME'] = $response['data'][$i]['data']['UTP_TE_DATA_NAME'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_URL'] = $response['data'][$i]['data']['UTP_TE_URL'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_PROCESS_ID'] = $response['data'][$i]['data']['UTP_TE_PROCESS_ID'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_PROCESS_NAME'] = $response['data'][$i]['data']['UTP_TE_PROCESS_NAME'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_LANGUAGE'] = $response['data'][$i]['data']['UTP_TE_LANGUAGE'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME'] = $response['data'][$i]['data']['UTP_TE_DOCUMENT_NAME'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_VALIDATION'] = $response['data'][$i]['data']['UTP_TE_VALIDATION'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_TYPE'] = $response['data'][$i]['data']['UTP_TE_TYPE'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME_FORMAT'] = $response['data'][$i]['data']['UTP_TE_DOCUMENT_NAME_FORMAT'];
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_BASE_TEXT'] = $response['data'][$i]['data']['UTP_TE_BASE_TEXT'];
                    //Load row_id from collection
                    $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['row_id'] = $response['data'][$i]['data']['row_id'];

                    //Set name button
                    $strLengUploadDocuments = strlen($data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME']);
                    if ($strLengUploadDocuments > 20) {
                        $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME_BUTTON'] = substr($data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME'], 0, 15);
                        $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME_BUTTON'] = $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME_BUTTON'] . '(....).docx';
                    } else {
                        $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME_BUTTON'] = $data['UTP_UPLOAD_DOCUMENTS'][$loopCount]['UTP_DOCUMENT_NAME'];
                    }

                    //Increase Row on the record list
                    $loopCount++;
                }

                //Order Upload Documents with Order value
                if (count($response['data']) > 0) {
                    $keys = array_column($data['UTP_UPLOAD_DOCUMENTS'], 'UTP_ORDER');
                    array_multisort($keys, SORT_ASC, $data['UTP_UPLOAD_DOCUMENTS']);
                }

            }
        }
    }
}

return $data['UTP_UPLOAD_DOCUMENTS'];