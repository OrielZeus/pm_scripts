<?php 
/*********************************
 * PE - Save IC 02 Approver Response
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 ********************************/
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');

//Get IC Approvers collection ID
$queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                      FROM collection_" . $masterCollectionID . "
                      WHERE data->>'$.COLLECTION_NAME' IN ('PE_IC_02_APPROVER_RESPONSE')";
$collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
if (empty($collectionInfo["error_message"])) {
    //Set collection url
    $insertResponseUrl = $apiHost . "/collections/" . $collectionInfo[0]["ID"] . "/records";
    //Set array of values to insert in collection
    $insertValues = [
        'data' => [
            "IAR_REQUEST_ID" => $data['_request']['id'] ?? '',
            "IAR_CASE_NUMBER" => $data["PE_PARENT_CASE_NUMBER"] ?? '',
            "IAR_APPROVER_EMAIL" => $data["PE_IC_APPROVER_EMAIL"] ?? '',
            "IAR_APPROVER_FULL_NAME" => $data["PE_IC_APPROVER_NAME"] ?? '',
            "IAR_APPROVER_COMMENTS" => $data["PE_IC_COMMENTS"] ?? ''
        ]
    ];
    callApiUrlGuzzle($insertResponseUrl, "POST", $insertValues);
}
$dataReturn = array();
if ($data['PE_IC_APPROVER_TYPE'] == 'PRIMARY') {
    $dataReturn['PE_CONFIRMATION_DATE_IC2_PRIMARY'] = date('m/d/Y');
}
if ($data['PE_IC_APPROVER_TYPE'] == 'SECONDARY') {
    $dataReturn['PE_CONFIRMATION_DATE_IC2_SECONDARY'] = date('m/d/Y');
}

return $dataReturn;