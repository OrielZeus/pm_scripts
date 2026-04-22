<?php

/**********************************
 * Sign Configuration - Get Data
 *
 * by Elmer Orihuela
 * modified by Adriana Centellas
 *********************************/
require_once("/Northleaf_PHP_Library.php");

$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$host = $_SERVER["HOST_URL"];
$sqlUrl = $apiHost . $apiSql;
$signatureGroupId = 19;

// Get DataTable Parameters
$orderBy = $data["columns"][$data["order"][0]['column']]['data'] ?? null;
$orderType = $data["order"][0]['dir'] ?? null;
$pageSize = $data['length'] ?? 10;
$pageNumber = $data["draw"];
$start = $data['start'] ?? 0;
$filter = "";
if ($data['FILTER_SEARCH'] != "") {
    $filter = " AND (FIRST_NAME LIKE '%" . $data['FILTER_SEARCH'] . "%' OR LAST_NAME LIKE '%" . $data['FILTER_SEARCH'] . "%' OR STATUS LIKE '%" . $data['FILTER_SEARCH'] . "%')";
}
 // Get list of users in signature group
    $getSignatureUserList = "SELECT id FROM users WHERE deleted_at is null AND username != '_pm4_anon_user'";
    $responseGetSignatureUserList = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureUserList));
    $signatureUserIds = array_column($responseGetSignatureUserList, 'id');
    $signatureUserIdsStr = implode(",", $signatureUserIds);


// Construct the SQL query to count signatures based on user IDs
    $getSignatureCount = "
        SELECT COUNT(USER_ID) as COUNT
        FROM SIGNATURE_CONFIGURATION 
        WHERE USER_ID IN ($signatureUserIdsStr)
    ";

    // Execute the query using your existing API call function
    $responseGetSignatureCount = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureCount));
    // Construct the SQL query to count the number of users
    $getSignatureUserCount = "
        SELECT COUNT(id) as COUNT
        FROM users 
        WHERE deleted_at IS NULL 
        AND username != '_pm4_anon_user'
    ";
    // Execute the query using your existing API call function
    $responseGetSignatureUserCount = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureUserCount));  
    if ($responseGetSignatureUserCount[0]['COUNT'] != $responseGetSignatureCount[0]['COUNT']) {
        // Check and create/update missing signature configurations
        foreach ($responseGetSignatureUserList as $user) {
            updateUserSignatureConfig($sqlUrl, $apiHost, $userId, $user['id']);
        }
    }

    
function updateUserSignatureConfig($sqlUrl, $apiHost, $userId, $user) {
    // Get User information for each member
    $urlUser = $apiHost . "/users/" . $user;
    $getUserInfo = callApiUrlGuzzle($urlUser, "GET", []);

    // Get or create signature configuration
    $getSignatureConfig = "SELECT * FROM SIGNATURE_CONFIGURATION WHERE USER_ID = $user";
    $responseGetSignatureConfig = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureConfig));

    if (empty($responseGetSignatureConfig)) {
        $firstName = $getUserInfo['firstname'];
        $lastName = $getUserInfo['lastname'];
        $signatureStatus = null;
        $signatureUrl = null;
        $modifiedBy = $userId;
        $status = $getUserInfo['status'];

        $insertSignatureConfig = "INSERT INTO SIGNATURE_CONFIGURATION (USER_ID, FIRST_NAME, LAST_NAME, SIGNATURE_STATUS, SIGNATURE_URL, MODIFIED_BY, STATUS) 
                                  VALUES ($user, '$firstName', '$lastName', " . ($signatureStatus === null ? "NULL" : "'$signatureStatus'") . ", " . ($signatureUrl === null ? "NULL" : "'$signatureUrl'") . ", $modifiedBy, " . ($status === null ? "NULL" : "'$status'") . ")";
        callApiUrlGuzzle($sqlUrl, "POST", encodeSql($insertSignatureConfig));
    } else {
        // Check and update the status if it has changed
        $currentStatus = $responseGetSignatureConfig[0]['STATUS'];
        $newStatus = $getUserInfo['status'];

        if ($currentStatus !== $newStatus) {
            $updateSignatureConfig = "UPDATE SIGNATURE_CONFIGURATION SET STATUS = '$newStatus' WHERE USER_ID = $user";
            callApiUrlGuzzle($sqlUrl, "POST", encodeSql($updateSignatureConfig));
        }
    }
}