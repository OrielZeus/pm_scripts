<?php
/*  
 * Order and Save Templates using API 
 * by Nestor Orihuela
 * modified by Ana Castillo
 * modified by Helen Callisaya
 */
//URL host
$urlHost = $_SERVER["HOST_URL"];
//Set API token
$apiToken = getenv('API_TOKEN');
//HOST/api/1.0
$apiHost = getenv('API_HOST');
//Set Collection Templates ID
$collectionID = getenv('UTP_TEMPLATES_COLLECTION');

//Set parameters to validate data
$processID = $data["UTP_PROCESS"]["ID"];
$processName = $data["UTP_PROCESS"]["NAME"];
$language = $data["UTP_LANGUAGE"];
$type = $data["UTP_TYPE"];
$baseText = $data["UTP_BASE_TEXT"];

/*
* Execute Curl on Collection
*
* @param (String) $collectionID
* @param (String) $apiHost
* @param (String) $apiToken
* @param (String) $pmql
* @param (String) $curlType
* @param (Json) $dataPost
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function executeCurlCollection($collectionID, $apiHost, $apiToken, $pmql, $curlType, $dataPost)
{
    //Curl init
    $curl = curl_init();
    //Curl to the End point
    switch ($curlType) {
        case 'GET':
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
        break;
        case 'POST':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . '/collections/' . $collectionID . '/records',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dataPost,
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: text/plain'
                ),
            ));
        break;
        case 'DELETE':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . '/collections/' . $collectionID . '/records/' . $pmql,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $apiToken,
                    'Content-Type: text/plain'
                ),
            ));
        break;
        case 'FILES':
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiHost . '/files/' . $pmql,
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
        break;
    }
    //Set response
    $response = curl_exec($curl);
    curl_close($curl);
    //Response Json decode
    $response = json_decode($response, true);

    if ($curlType != "FILES") {
        $response = $response["data"];
    }

    //Return data
    return $response;
}

//Validate if exist or not previuos data for this process, language and type
$pmqlTemplate = '?pmql=' . urlencode('(data.UTP_TE_PROCESS_ID = ' . $processID . ' AND data.UTP_TE_LANGUAGE = "' . $language . '" AND data.UTP_TE_TYPE = "' . $type . '")');
//If Type is Slip add Base Text
if ($type == "SLIP") {
    $pmqlTemplate = '?pmql=' . urlencode('(data.UTP_TE_PROCESS_ID = ' . $processID . ' AND data.UTP_TE_LANGUAGE = "' . $language . '" AND data.UTP_TE_TYPE = "' . $type . '" AND data.UTP_TE_BASE_TEXT = "' . $baseText . '")');
}
$existTemplate = executeCurlCollection($collectionID, $apiHost, $apiToken, $pmqlTemplate, "GET", array());
//Delete rows of the Collection
if (count($existTemplate) > 0) {
    for ($d = 0; $d < count($existTemplate); $d++) {
        //Get row id of the Collection
        $id = $existTemplate[$d]['id'];
        //Delete record of the collection
        $deleteRow = executeCurlCollection($collectionID, $apiHost, $apiToken, $id, "DELETE", array());
    }
}

//Set information to files uploaded on the current request and SAVE data on the Collection
for ($fs = 0; $fs < count($data['UTP_UPLOAD_DOCUMENTS']); $fs++) {
    //Get file information
    $responseFile = executeCurlCollection($collectionID, $apiHost, $apiToken, $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT'], "FILES", array());
    //Validate if file was uploaded on the current request
    if ($responseFile['id'] == $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT'] && $responseFile['model_id'] == $data['_request']['id']) {
        //Update data UTP_UPLOAD_DOCUMENTS
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_LANGUAGE'] = $data['UTP_LANGUAGE'];
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_TYPE'] = $data['UTP_TYPE'];
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_PROCESS_ID'] = $processID;
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_PROCESS_NAME'] = $processName;
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT_NAME_FORMAT'] = $data['UTP_DOCUMENT_NAME_FORMAT'];
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT_NAME'] = $responseFile['file_name'];
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_REQUEST_ID'] = $data['_request']['id'];
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DATA_NAME'] = $responseFile['custom_properties']['data_name'];
        $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_URL'] = $urlHost . '/request/' . $data['_request']['id'] . '/files/' . $responseFile['id'] ;
    }

    //Change Order to integer
    $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_ORDER'] = $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_ORDER'] * 1;

    //Delete Validations if Loop is empty
    $validations = $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_VALIDATION'];
    if ($validations != "") {
        if (count($validations) < 1) {
            $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_VALIDATION'] = "";
        } else {
            $flagEmpty = 0;
            //Validate that all fields on the validations are empty
            for ($v = 0; $v < count($validations); $v++) {
                foreach ($validations[$v] as $keyValidation => $valueValidation) {
                    if ($valueValidation != null && $valueValidation != "") {
                        $flagEmpty = 1;
                        break;
                    }
                }
                if ($flagEmpty == 1) {
                    break;
                }
            }
            //Clean Validations if all rows has no data
            if ($flagEmpty == 0) {
                $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_VALIDATION'] = "";
            }
        }
    }
    

    //Set values Array to save it on Collection Templates
    $saveDocuments = array(
        "data" => array(
            "UTP_TE_ORDER" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_ORDER'],
            "UTP_TE_TYPE" => $data['UTP_TYPE'],
            "UTP_TE_BASE_TEXT" => $data['UTP_BASE_TEXT'],
            "UTP_TE_LANGUAGE" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_LANGUAGE'],
            "UTP_TE_PROCESS_ID" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_PROCESS_ID'],
            "UTP_TE_DESCRIPTION" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DESCRIPTION'],
            "UTP_TE_REPEAT_DOCUMENT" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_REPEAT_DOCUMENT'],
            "UTP_TE_VARIABLE_TO_LOOP" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_VARIABLE_TO_LOOP'],
            "UTP_TE_USE_OPENL" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_USE_OPENL'],
            "UTP_TE_OPENL_URL" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_OPENL_URL'],
            "UTP_TE_VARIABLE_TO_SAVE_REPONSE" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_VARIABLE_TO_SAVE_REPONSE'],
            "UTP_TE_DOCUMENT_ID" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT'],
            "UTP_TE_PROCESS_NAME" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_PROCESS_NAME'],
            "UTP_TE_DOCUMENT_NAME_FORMAT" => $data['UTP_DOCUMENT_NAME_FORMAT'],
            "UTP_TE_DOCUMENT_NAME" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT_NAME'],
            "UTP_TE_REQUEST_ID" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_REQUEST_ID'],
            "UTP_TE_DATA_NAME" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DATA_NAME'],
            "UTP_TE_URL" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_URL'],
            "UTP_TE_VALIDATION" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_VALIDATION'],
            "UTP_TE_DOCUMENT" => array(
                "id" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT'],
                "name" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['UTP_DOCUMENT_NAME']
            ),
            //The row_id of the recordlist is added to store it in the collection
            "row_id" => $data['UTP_UPLOAD_DOCUMENTS'][$fs]['row_id']
        )
    );
    //Encode Array 
    $saveDocuments = json_encode($saveDocuments);

    //Save data
    $responseSave = executeCurlCollection($collectionID, $apiHost, $apiToken, "", "POST", $saveDocuments);
}
//Status Complete
$data['UTP_STATUS'] = "Completed";
//Set End date
$data['UTP_END_DATE'] = date("m/d/Y H:i:s");
return $data;