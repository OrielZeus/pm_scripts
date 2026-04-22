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

$userId = $data['CURRENT_USER'];
if (empty($userId)) {
    return ["error" => "User id not found"];
}

// Get User information
$urlUsers = $apiHost . "/users/" . $userId;
$getUserInfoById = callApiUrlGuzzle($urlUsers, "GET", []);

if (empty($getUserInfoById)) {
    return ["error" => "User not found"];
}

// Check if the user is an administrator
if ($getUserInfoById["is_administrator"]) {
    return handleAdminUser($sqlUrl, $apiHost, $userId, $orderBy, $orderType, $pageSize, $start);
} else {
    return handleRegularUser($sqlUrl, $signatureGroupId, $userId, $getUserInfoById, $data["draw"]);
}

function handleAdminUser($sqlUrl, $apiHost, $userId, $orderBy, $orderType, $pageSize, $start) {
    // Get list of users in signature group
    $getSignatureUserList = "SELECT id FROM users WHERE deleted_at is null AND username != '_pm4_anon_user'";
    $responseGetSignatureUserList = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureUserList));
    $signatureUserIds = array_column($responseGetSignatureUserList, 'id');
    $signatureUserIdsStr = implode(",", $signatureUserIds);
    
    // List all signature configurations
    $getAllSignatureConfigs = "SELECT * FROM SIGNATURE_CONFIGURATION WHERE USER_ID IN ($signatureUserIdsStr)" . $GLOBALS['filter'];
    if (!empty($orderBy) && !empty($orderType)) {
        $getAllSignatureConfigs .= " ORDER BY $orderBy $orderType";
    }
    $getAllSignatureConfigs .= " LIMIT $start, $pageSize";
    $responseGetAllSignatureConfigs = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getAllSignatureConfigs));

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
    ";/*
    // Execute the query using your existing API call function
    $responseGetSignatureUserCount = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureUserCount));  
    if ($responseGetSignatureUserCount[0]['COUNT'] != $responseGetSignatureCount[0]['COUNT']) {
        // Check and create/update missing signature configurations
        foreach ($responseGetSignatureUserList as $user) {
            updateUserSignatureConfig($sqlUrl, $apiHost, $userId, $user['id']);
        }
    }
    */
    // Count total records
    $countAllSignatureConfigs = "SELECT COUNT(id) AS total FROM SIGNATURE_CONFIGURATION WHERE USER_ID IN ($signatureUserIdsStr)" . $GLOBALS['filter'];
    $responseCountAllSignatureConfigs = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($countAllSignatureConfigs));
    $totalRecords = $responseCountAllSignatureConfigs[0]['total'];

    return [
        "draw" => $GLOBALS['pageNumber'],
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalRecords,
        "data" => $responseGetAllSignatureConfigs,
        "sql" => $getAllSignatureConfigs
    ];
}

function handleRegularUser($sqlUrl, $signatureGroupId, $userId, $getUserInfoById, $draw) {
    // Check if the user is in the signature group and has a signature configuration
    $getSignatureUserList = "SELECT member_id FROM group_members WHERE group_id = $signatureGroupId AND member_id = $userId";
    $responseGetSignatureUserList = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureUserList));

    if (!empty($responseGetSignatureUserList)) {
        $getSignatureConfig = "SELECT * FROM SIGNATURE_CONFIGURATION WHERE USER_ID = $userId" . $filter;
        $responseGetSignatureConfig = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureConfig));
        if (empty($responseGetSignatureConfig)) {
            createUserSignatureConfig($sqlUrl, $userId, $getUserInfoById);
            $responseGetSignatureConfig = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getSignatureConfig));
        }
        return [
            "draw" => $draw,
            "recordsTotal" => count($responseGetSignatureConfig) ?? 0,
            "recordsFiltered" => count($responseGetSignatureConfig) ?? 0,
            "data" => $responseGetSignatureConfig,
            "sql2" => $countAllSignatureConfigs
        ];
    }
    return ["error" => "User not in signature group"];
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

function createUserSignatureConfig($sqlUrl, $userId, $getUserInfoById) {
    $firstName = $getUserInfoById['firstname'];
    $lastName = $getUserInfoById['lastname'];
    $signatureStatus = null;
    $signatureUrl = null;
    $modifiedBy = $userId;
    $status = $getUserInfoById['status'];

    $insertSignatureConfig = "INSERT INTO SIGNATURE_CONFIGURATION (USER_ID, FIRST_NAME, LAST_NAME, SIGNATURE_STATUS, SIGNATURE_URL, MODIFIED_BY, STATUS) 
                              VALUES ($userId, '$firstName', '$lastName', " . ($signatureStatus === null ? "NULL" : "'$signatureStatus'") . ", " . ($signatureUrl === null ? "NULL" : "'$signatureUrl'") . ", $modifiedBy, " . ($status === null ? "NULL" : "'$status'") . ")";
    callApiUrlGuzzle($sqlUrl, "POST", encodeSql($insertSignatureConfig));
}