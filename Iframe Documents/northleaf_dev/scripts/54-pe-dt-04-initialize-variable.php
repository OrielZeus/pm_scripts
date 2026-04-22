<?php 
/**********************************
 * PE - DT.04 Initialize Variables
 *
 * by Cinthia Romero
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();
$amlMessage = "";

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$apiUrl = $apiHost . $apiSql;

//Get Collections IDs
$queryCollections = "SELECT data->>'$.COLLECTION_ID' AS ID,
                            data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                     FROM collection_" . $masterCollectionID . "
                     WHERE data->>'$.COLLECTION_NAME' IN ('PE_AML_MESSAGE')";
$collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollections));
//Check if collection has been correctly configured
if (!empty($collectionsInfo[0]["ID"])) {
    $queryAMLMessage = "SELECT data->>'$.AML_MESSAGE_HTML' AS MESSAGE
                        FROM collection_" . $collectionsInfo[0]["ID"] . "
                        WHERE data->>'$.AML_MESSAGE_STATUS' = 'Active'
                        LIMIT 1";
    $amlMessageResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryAMLMessage));
    if(!empty($amlMessageResponse[0]["MESSAGE"])) {
        $amlMessage = $amlMessageResponse[0]["MESSAGE"];
    }
}
$dataReturn["PE_AML_MESSAGE_HTML"] = $amlMessage;
$dataReturn["PE_SAVE_SUBMIT_DT4"] = "";

if ($data['PE_SAVE_SUBMIT_DT4'] == "SUBMIT" && $data['PE_AML_REVIEW_COMPLETE'] == "No") {
    // Send Notification
    $task = 'node_DT06';
    $emailType = '';
    sendNotification($data, $task, $emailType, $api);
}
return $dataReturn;