<?php 
/**********************************
 * PE - LC.02 Initialize Variables
 * LC.02 Review Funding Authorization and obtain signatures
 *
 * by Telmo Chiri
 * modified by Helen Callisaya
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();
$aPrimary = array();
$aSecondary = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');

//Get collections IDs  01-08-2024 HC
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfo["PE_GROUP_LEADER"];

//Get Id Group
$groupName = "PE - Law Clerk";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_LEAD_LAW_CLERK'] = $userLead;

//Get Signing Users
$sql = "SELECT U.id AS user_id, 
            U.status,
            CONCAT (U.firstname, ' ', U.lastname) AS user_fullname, 
            U.meta->>'$.typeOfSigner' AS type_signer,
            IF(U.meta->>'$.signature' != '', 'YES', 'NO') AS signature
        FROM users AS U
        WHERE  U.meta->>'$.typeOfSigner' != '' AND U.meta->>'$.typeOfSigner' IS NOT NULL AND U.meta->>'$.typeOfSigner' != 'null'
            AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
        ORDER BY user_fullname";
$usersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
//Loop Users
foreach($usersResponse as $user) {
    if ($user['type_signer'] == 'Both') {
        array_push($aPrimary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
        array_push($aSecondary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
    }
    if ($user['type_signer'] == 'Primary') {
        array_push($aPrimary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
    }
    if ($user['type_signer'] == 'Secondary') {
        array_push($aSecondary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
    }
}
$dataReturn["PE_PRIMARY_SIGNERS"] = $aPrimary;
$dataReturn["PE_SECONDARY_SIGNERS"] = $aSecondary;
$dataReturn["PE_SAVE_SUBMIT_LC4"] = '';

if ($data['PE_SAVE_SUBMIT_LC4'] != "SAVE") {
    $data = array_merge($data, $dataReturn);
    // Send Notification
    $task = 'node_LC04';
    $emailType = 'TO_LC04';
    sendNotification($data, $task, $emailType, $api);

    $task = 'node_LC04';
    $emailType = 'TO_GROUP_DEAL_TEAM';
    sendNotification($data, $task, $emailType, $api);
}

//Get Collections IDs
$queryCollections = "SELECT data->>'$.COLLECTION_ID' AS ID,
                            data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                     FROM collection_" . $masterCollectionID . "
                     WHERE data->>'$.COLLECTION_NAME' = 'DEFAULT_SIGNERS'";
$collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollections));
$collectionSigners = $collectionsInfo[0]['ID'];
//Get Default Signers
$sqlGetSigners = "SELECT data->>'$.DEFAULT_PRIMARY_SIGNER' AS DEFAULT_PRIMARY_SIGNER,
                            data->>'$.DEFAULT_SECONDARY_SIGNER' AS DEFAULT_SECONDARY_SIGNER
                    FROM collection_" . $collectionSigners . "
                    WHERE id = 1
                    LIMIT 1";
$defaultSigners = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlGetSigners));

$dataReturn['DEFAULT_PRIMARY_SIGNER'] = $defaultSigners[0]['DEFAULT_PRIMARY_SIGNER'] != '' ? json_decode($defaultSigners[0]['DEFAULT_PRIMARY_SIGNER']) : [];
$dataReturn['DEFAULT_SECONDARY_SIGNER'] = $defaultSigners[0]['DEFAULT_SECONDARY_SIGNER'] != '' ? json_decode($defaultSigners[0]['DEFAULT_SECONDARY_SIGNER']) : [];

return $dataReturn;