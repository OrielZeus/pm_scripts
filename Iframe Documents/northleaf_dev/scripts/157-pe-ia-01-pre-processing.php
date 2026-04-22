<?php 
/**********************************
 * PE - IA.01 Pre-Processing
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
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');

//Get collections IDs
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfo["PE_GROUP_LEADER"];

//Get Id Group
$groupName = "PE - Investment Advisors";
$groupId = getGroupId($groupName, $apiUrl);

//Get User Leader
$userLead = getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl);
$dataReturn['PE_INVESTMENT_ADVISOR_APPROVER'] = $userLead;

//Check Signature
$sql = "SELECT U.id AS user_id, 
            U.status,
            CONCAT (U.firstname, ' ', U.lastname) AS user_fullname, 
            U.meta->>'$.typeOfSigner' AS type_signer,
            IF((U.meta->>'$.signature' != '' AND U.meta->>'$.signature' != 'null' AND U.meta->>'$.signature' IS NOT NULL), 
                'YES', 
                'NO'
            ) AS signature
        FROM users AS U
        WHERE U.id = $userLead
            AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
        LIMIT 1";
$usersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
//$dataReturn['PE_INVESTMENT_ADVISOR_APPROVER_SIGNATURE'] = 'NO';
foreach($usersResponse as $user) {
    //$dataReturn['PE_INVESTMENT_ADVISOR_APPROVER_SIGNATURE'] = $user['signature'];
    $dataReturn['PE_INVESTMENT_ADVISOR_APPROVER_NAME'] = $user['user_fullname'];
}
$dataReturn["PE_SAVE_SUBMIT_IA1"] = '';

$data = array_merge($data, $dataReturn);
    // Send Notification
    $task = 'node_IA01';
    $emailType = '';
    sendNotification($data, $task, $emailType, $api);

return $dataReturn;