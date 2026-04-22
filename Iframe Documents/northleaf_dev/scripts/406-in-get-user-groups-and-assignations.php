<?php 
/*******************************************
 * IN - Get user groups and assignations
 * by Adriana Centellas
 * Modified by Ana Castillo
 *******************************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$groupInfra = getGroupId("IN_Infra Ops", $apiUrl);
$groupPE = getGroupId("IN_PE Ops", $apiUrl);
$groupPC = getGroupId("IN_PC Ops", $apiUrl);
$groupCorp = getGroupId("IN_Corporate Finance", $apiUrl);

//Set up initial assignment as Self Service - Change to NO by Ana
$infraAssigned = "NO";
$peAssigned = "NO";
$pcAssigned = "NO";
$corpAssigned = "NO";

$user = $data["IN_IS03_CURRENT_USER"]["id"];

$userGroupsInfo = getGroupsbyUser($user, $apiUrl, $groupInfra, $groupPE, $groupPC, $groupCorp);
//Review if the user has a group
foreach ($userGroupsInfo as $group) {
    if ($group["group_id"] == $groupInfra) {
        $IN_USER_SUB_INFRA = $user;
        $infraAssigned = "YES";
    }
    if ($group["group_id"] == $groupPE) {
        $IN_USER_SUB_PE = $user;
        $peAssigned = "YES";
    }
    if ($group["group_id"] == $groupPC) {
        $IN_USER_SUB_PC = $user;
        $pcAssigned = "YES";
    }
    if ($group["group_id"] == $groupCorp) {
        $IN_USER_SUB_CORP = $user;
        $corpAssigned = "YES";
    }
}

$requestId = $data["_request"]["id"];
$dataSumCO = $dataSumIN = $dataSumPE = $dataSumPC = [
    "IN_EXPENSE_PRETAX_AMOUNT" => 0,
    "IN_EXPENSE_HST" => 0,
    "IN_EXPENSE_TOTAL" => 0
];
$query  = "SELECT IN_EXPENSE_PRETAX_AMOUNT,IN_EXPENSE_HST,IN_EXPENSE_TOTAL,IN_EXPENSE_TEAM_ROUTING_ID
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));


foreach($response as $row){
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PC'){
        $dataSumPC['IN_EXPENSE_PRETAX_AMOUNT'] += str_replace(',', '', $row['IN_EXPENSE_PRETAX_AMOUNT']) * 1;
        $dataSumPC['IN_EXPENSE_HST'] += str_replace(',', '', $row['IN_EXPENSE_HST']) * 1;
        $dataSumPC['IN_EXPENSE_TOTAL'] += str_replace(',', '', $row['IN_EXPENSE_TOTAL']) * 1;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PE'){
        $dataSumPE['IN_EXPENSE_PRETAX_AMOUNT'] += str_replace(',', '', $row['IN_EXPENSE_PRETAX_AMOUNT']) * 1;
        $dataSumPE['IN_EXPENSE_HST'] += str_replace(',', '', $row['IN_EXPENSE_HST']) * 1;
        $dataSumPE['IN_EXPENSE_TOTAL'] += str_replace(',', '', $row['IN_EXPENSE_TOTAL']) * 1;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'INFRA'){
        $dataSumIN['IN_EXPENSE_PRETAX_AMOUNT'] += str_replace(',', '', $row['IN_EXPENSE_PRETAX_AMOUNT']) * 1;
        $dataSumIN['IN_EXPENSE_HST'] += str_replace(',', '', $row['IN_EXPENSE_HST']) * 1;
        $dataSumIN['IN_EXPENSE_TOTAL'] += str_replace(',', '', $row['IN_EXPENSE_TOTAL']) * 1;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'CORP'){
        $dataSumCO['IN_EXPENSE_PRETAX_AMOUNT'] += $row['IN_EXPENSE_PRETAX_AMOUNT'];
        $dataSumCO['IN_EXPENSE_HST'] += $row['IN_EXPENSE_HST'];
        $dataSumCO['IN_EXPENSE_TOTAL'] += $row['IN_EXPENSE_TOTAL'];
    }
}

$query  = "SELECT IN_EXPENSE_ROW_ID,IN_EXPENSE_TOTAL,IN_EXPENSE_TEAM_ROUTING_ID
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
foreach($response as $row){
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PE'){
        $pecTotal = $dataSumPE['IN_EXPENSE_TOTAL'];
        $newPer = $row['IN_EXPENSE_TOTAL'] * 100 /  $pecTotal;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PC'){
        $pcTotal = $dataSumPC['IN_EXPENSE_TOTAL'];
        $newPer = $row['IN_EXPENSE_TOTAL'] * 100 /  $pcTotal;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'INFRA'){
        $infraTotal = $dataSumIN['IN_EXPENSE_TOTAL'];
        $newPer = $row['IN_EXPENSE_TOTAL'] * 100 /  $infraTotal;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'CORP'){
        $corpTotal = $dataSumCO['IN_EXPENSE_TOTAL'];
        $newPer = $row['IN_EXPENSE_TOTAL'] * 100 /  $corpTotal;
    }
    $query  = "UPDATE EXPENSE_TABLE SET IN_EXPENSE_PERCENTAGE_TOTAL = '".round($newPer,2)."'
                WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
}


	

return [
    "IN_SUMMARY_TOTAL_GRID" => [
        "PC" => $dataSumPC,
        "PE" => $dataSumPE,
        "INFRA" => $dataSumIN,
        "CORP" => $dataSumCO
    ],
    "IN_USER_SUB_INFRA" => $IN_USER_SUB_INFRA,
    "IN_INFRA_ASSIGNED" => $infraAssigned,
    "IN_USER_SUB_PE" => $IN_USER_SUB_PE,
    "IN_PE_ASSIGNED" => $peAssigned,
    "IN_USER_SUB_PC" => $IN_USER_SUB_PC,
    "IN_PC_ASSIGNED" => $pcAssigned,
    "IN_USER_SUB_CORP" => $IN_USER_SUB_CORP,
    "IN_CORP_ASSIGNED" => $corpAssigned,
    "IN_SAVE_SUBMIT" => null,
    "IN_EDIT_SUBMIT" => null,
    "IN_SUBMITTER_MANAGER_ID" => isset($data["IN_SUBMITTER_MANAGER"]["ID"]) ? $data["IN_SUBMITTER_MANAGER"]["ID"] : '',
    "IN_CASE_TITLE" => $data["IN_INVOICE_VENDOR_LABEL"] . ' - ' . $data["IN_INVOICE_NUMBER"] . ' - ' . $data["IN_INVOICE_DATE"]
];


/**
 * Get Groups per Users
 *
 * @param (String) $groupId
 * @param (String) $apiUrl
 * @return (Array) $groupUsersResponse
 *
 * by Adriana Centellas
 */
function getGroupsbyUser($userID, $apiUrl, $groupInfra, $groupPE, $groupPC, $groupCorp)
{
    //Select Group id and name
    $queryGroupUsers = "select group_id 
                        from group_members gm 
                        where member_id = ".$userID." 
                        and group_id in ( ".$groupInfra.", 
                        ".$groupPE.", 
                        ".$groupPC.", 
                        ".$groupCorp.")";
    $groupUsersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupUsers)) ?? [];
    return $groupUsersResponse;
}