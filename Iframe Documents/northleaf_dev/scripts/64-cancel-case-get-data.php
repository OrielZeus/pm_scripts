<?php 
/**********************************
*  Cancel Case Get Data DataTable Detail
*
*  by Telmo Chiri
**********************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiSql = getenv('API_SQL');
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$currentUser = $data["CURRENT_USER"];

$dataReturn = [];

//Get all Users Info
$aUsers = array();
$queryGetUsers = "SELECT U.id AS ID, 
                        CONCAT(U.firstname, ' ', U.lastname) as FULL_NAME, 
                        GROUP_CONCAT(GM.group_id SEPARATOR ',') AS `GROUPS`
                FROM users AS U
                INNER JOIN group_members AS GM ON GM.member_id = U.id
                GROUP BY ID, FULL_NAME";
$usersInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetUsers));
if (empty($usersInfo["error_message"])) {
    $aUsers = $usersInfo;
}

$filterProcess = '';

//Check if user is Admin
$userIsAdmin = "NO";
$queryUserIsAdmin = "SELECT is_administrator
                        FROM users
                        WHERE id = '" . $currentUser . "'";
$userIsAdminResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUserIsAdmin));
if (!empty($userIsAdminResponse[0]["is_administrator"]) && $userIsAdminResponse[0]["is_administrator"] == 1) {
    $userIsAdmin = "YES";
    $filterProcess = '';
}
if ($userIsAdmin == "NO") {
    $aProcessAccess = array();
    $key = array_search($currentUser, array_column($aUsers, 'ID'));
    $currentUserAllInfo = $aUsers[$key];

    //Get Collections IDs
    $queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN ('CANCEL_CASE_SETTINGS')";
    $collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
    if (empty($collectionInfo["error_message"])) {
        $aCurrentUserGroups = explode(",",$currentUserAllInfo['GROUPS']);
        //Get Cancel Case Settings
        $queryCancelCaseSettings = "SELECT data->>'$.PROCESS.id' AS processId,
                                            data->>'$.userIds' AS userIds,
                                            data->>'$.groupIds' AS groupIds
                                    FROM collection_" . $collectionInfo[0]['ID'] . "";
        $cancelCaseSettingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCancelCaseSettings));
        foreach($cancelCaseSettingsResponse as $setting) {
            $aUserSettings = json_decode($setting['userIds']);
            if (in_array($currentUser, $aUserSettings)) {
                $aProcessAccess[] = $setting['processId'];
                goto next;
            }
            $aGroupSettings = json_decode($setting['groupIds']);
            foreach($aCurrentUserGroups as $currentGroups) {
                if (in_array($currentGroups, $aGroupSettings)) {
                    $aProcessAccess[] = $setting['processId'];
                    goto next;
                }
            }
            next:
        }
        //$aProcessAccess[] = 50;
        $aProcessAccess = array_unique($aProcessAccess);
        if(count($aProcessAccess) > 0) {
            $filterProcess = "FIND_IN_SET('" . implode("',  PROCESS_IDS)>0 OR FIND_IN_SET('", $aProcessAccess) . "', PROCESS_IDS)>0";
        }
    }
}

if ($userIsAdmin == "NO") {
    $filterUser = "FIND_IN_SET('" . $currentUser . "', USER_IDS)>0";
}
// Get All Conditions
$conditions = [];
if (!empty($filterProcess)) {
    $conditions[] = $filterProcess;
}
if (!empty($filterUser)) {
    $conditions[] = $filterUser;
}

// Set filter
$allFilters = "";
if (!empty($conditions)) {
    $allFilters = "HAVING " . implode(' AND ', $conditions);
}
// Get DataTable Parameters
$orderBy = $data["columns"][$data["order"][0]['column']]['data'];
$orderType = $data["order"][0]['dir'];
$pageSize = $data['length'] ?? 10;
$pageNumber = $data["draw"];
$start = $data['start'] ?? 0;
$filter = "";
if ($data['FILTER_SEARCH'] != "") {
    $filter = " AND ( CASE_NUMBER LIKE '%" . $data['FILTER_SEARCH'] . "%' 
	OR CASE_TITLE LIKE '%" . $data['FILTER_SEARCH'] . "%') ";
}

$sqlReport = "SELECT PR.case_number AS CASE_NUMBER, 
                    PR.case_title AS CASE_TITLE, 
                    GROUP_CONCAT(PR.id SEPARATOR '|') AS REQUEST_ID,
                    GROUP_CONCAT(PR.process_id) AS PROCESS_IDS,
                    GROUP_CONCAT(IF(PRT.element_type = 'task', PRT.element_name, NULL) SEPARATOR '<br>') AS TASK_TITLE,
                    GROUP_CONCAT(IF(PRT.element_type = 'task', IF(PRT.user_id IS NULL, 0, PRT.user_id), NULL)) AS USER_IDS
                FROM process_requests AS PR
                INNER JOIN process_request_tokens AS PRT ON PRT.process_request_id = PR.id
                WHERE PR.case_number != '' 
                    AND PR.STATUS = 'ACTIVE' 
                    AND PRT.STATUS = 'ACTIVE'
                    $filter 
                    AND PRT.element_type IN ('task', 'callActivity') 
                    -- AND PRT.element_name NOT LIKE 'IC.0%'
                GROUP BY CASE_NUMBER, CASE_TITLE
                $allFilters
                ";

$sqlReportTotalWithoutLimit = $sqlReport;
$position = strripos($sqlReportTotalWithoutLimit, "FROM process_requests");
$totalSql = "SELECT COUNT(DISTINCT PR.case_number) as total, 
                    GROUP_CONCAT(PR.process_id) AS PROCESS_IDS,
                    GROUP_CONCAT(IF(PRT.element_type = 'task', IF(PRT.user_id IS NULL, 0, PRT.user_id), NULL)) AS USER_IDS " . 
substr($sqlReportTotalWithoutLimit, $position);
//Total without Limits
$responseReportTotal = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($totalSql));

//Total without Limits
if (!empty($orderBy)) {
    $sqlReport .= " ORDER BY " . $orderBy . " " . strtoupper($orderType);
}
$sqlReport .= " LIMIT " . $pageSize . " OFFSET " . $start;
$responseReport = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($sqlReport));

//Post Processing get Full Names of Users
foreach($responseReport as &$record) {
    $userFullNames = array();
    $userTaskList = explode(",", $record['USER_IDS']);
    foreach($userTaskList as $userTask) {
        if ($userTask != 0) {
            $key = array_search($userTask, array_column($aUsers, 'ID'));
            $userFullNames[] = $aUsers[$key]['FULL_NAME'];
        } else {
            $userFullNames[] = 'Self Service';
        }
    }
    $record['USER_FULL_NAME'] = implode("<br>", $userFullNames);
}

$aDataReturn = [
    "draw" => $data['draw'],
    "recordsTotal" => count($responseReportTotal) ?? 0,
    "recordsFiltered" => count($responseReportTotal) ?? 0,
    "data" => $responseReport
];
return $aDataReturn;