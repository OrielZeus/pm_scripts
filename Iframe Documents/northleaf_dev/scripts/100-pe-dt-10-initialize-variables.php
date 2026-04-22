<?php 
/**********************************
 * PE - DT.09 Initialize Variables
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$apiUrl = $apiHost . $apiSql;

//Get Collections IDs
$queryCollections = "SELECT data->>'$.COLLECTION_ID' AS ID,
                            data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                     FROM collection_" . $masterCollectionID . "
                     WHERE data->>'$.COLLECTION_NAME' ='PE_CLOSING_CHECK_LIST'";
$collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollections));
//Check if collection has been correctly configured
if (!empty($collectionsInfo[0]["ID"])) {
    $queryClosing = "SELECT data->>'$.PE_CLOSING_CHECK_TASK' AS PE_CLOSING_CHECK_TASK, 
                                data->>'$.PE_CLOSING_CHECK_TODO' AS PE_CLOSING_CHECK_TODO,
                                data->>'$.PE_CLOSING_CHECK_DEAL_TYPE' AS PE_CLOSING_CHECK_DEAL_TYPE,
                                data->>'$.PE_CLOSING_CHECK_RESPONSIBILITY' AS PE_CLOSING_CHECK_RESPONSIBILITY
                        FROM collection_" . $collectionsInfo[0]["ID"] . "
                        ORDER BY id";
    $closingResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryClosing));
    if(!empty($closingResponse[0]["PE_CLOSING_CHECK_TASK"])) {
        $dataReturn["PE_CLOSING_CHECK_LIST"] = $closingResponse;
    }
}

if ($data['PE_SAVE_SUBMIT_DT10'] == "SUBMIT") {
    if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes"){
        // Send Notification
        $task = 'node_DT12';
        $emailType = 'TO_LL08';
        sendNotification($data, $task, $emailType, $api);
    } 
    elseif ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "No"){
        // Send Notification
        $task = 'node_DT12';
        $emailType = 'DT05NO';
        sendNotification($data, $task, $emailType, $api);
    }
}

$dataReturn["PE_SAVE_SUBMIT_DT10"] = '';

return $dataReturn;