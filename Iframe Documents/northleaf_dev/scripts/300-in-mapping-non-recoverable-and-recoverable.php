<?php 

/**********************************
 * IN - Mapping Non-Recoverable and Recoverable
 *
 * by Favio Mollinedo
 * modified by Adriana Centellas
 *********************************/
require_once("/Northleaf_PHP_Library.php");

sleep(3);

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;
$requestId = $data['_request']['id'];

//Get collection ID
$collectionName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get Collection Data
$commentLogData = getCommentsLog($collectionID, $apiUrl, $requestId);


//use GuzzleHttp\Client;
//use GuzzleHttp\Psr7\Request;

$groupInfra = getGroupId("IN_Infra Ops", $apiUrl);
$groupPE    = getGroupId("IN_PE Ops", $apiUrl);
$groupPC    = getGroupId("IN_PC Ops", $apiUrl);
$groupCorp  = getGroupId("IN_Corporate Finance", $apiUrl);

//Set up initial assignment as Self Service - Change to NO by Ana
$infraAssigned = "NO";
$peAssigned    = "NO";
$pcAssigned    = "NO";
$corpAssigned  = "NO";

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

//$requestId = $data["_request"]["id"];
$dataSumCO = $dataSumIN = $dataSumPE = $dataSumPC = [
    "IN_EXPENSE_PRETAX_AMOUNT" => 0,
    "IN_EXPENSE_HST" => 0,
    "IN_EXPENSE_TOTAL" => 0
];
$query  = "SELECT IN_EXPENSE_ROW_ID,
                REPLACE(IN_EXPENSE_PRETAX_AMOUNT, ',', '') AS IN_EXPENSE_PRETAX_AMOUNT,
                REPLACE(IN_EXPENSE_HST, ',', '') AS IN_EXPENSE_HST,
                REPLACE(IN_EXPENSE_TOTAL, ',', '') AS IN_EXPENSE_TOTAL,
                IN_EXPENSE_TEAM_ROUTING_ID,IN_EXPENSE_PERCENTAGE_TOTAL
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
$dataCO = array_filter($response, function ($var) {
    return ($var['IN_EXPENSE_TEAM_ROUTING_ID'] == 'CORP');
});
$totalCO = array_sum(array_column($dataCO, 'IN_EXPENSE_TOTAL'));

$dataPE = array_filter($response, function ($var) {
    return ($var['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PE');
});
$totalPE = array_sum(array_column($dataPE, 'IN_EXPENSE_TOTAL'));

$dataIN = array_filter($response, function ($var) {
    return ($var['IN_EXPENSE_TEAM_ROUTING_ID'] == 'INFRA');
});
$totalIN = array_sum(array_column($dataIN, 'IN_EXPENSE_TOTAL'));

$dataPC = array_filter($response, function ($var) {
    return ($var['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PC');
});
$totalPC = array_sum(array_column($dataPC, 'IN_EXPENSE_TOTAL'));

$sumTotal = array_sum([$totalPC,$totalPE,$totalCO,$totalIN]);

/*foreach($response as $row){
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
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));*/

foreach($response as $row){
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PC'){
        $newPer = ($totalPC == 0) ? 0 : $row['IN_EXPENSE_TOTAL'] * 100 /  $totalPC;
        $dataSumPC['IN_EXPENSE_PRETAX_AMOUNT'] += str_replace(',', '', $row['IN_EXPENSE_PRETAX_AMOUNT']) * 1;
        $dataSumPC['IN_EXPENSE_HST'] += str_replace(',', '', $row['IN_EXPENSE_HST']) * 1;
        $dataSumPC['IN_EXPENSE_TOTAL'] += str_replace(',', '', $row['IN_EXPENSE_TOTAL']) * 1;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PE'){
        $newPer = ($totalPE == 0) ? 0 : $row['IN_EXPENSE_TOTAL'] * 100 /  $totalPE;
        $dataSumPE['IN_EXPENSE_PRETAX_AMOUNT'] += str_replace(',', '', $row['IN_EXPENSE_PRETAX_AMOUNT']) * 1;
        $dataSumPE['IN_EXPENSE_HST'] += str_replace(',', '', $row['IN_EXPENSE_HST']) * 1;
        $dataSumPE['IN_EXPENSE_TOTAL'] += str_replace(',', '', $row['IN_EXPENSE_TOTAL']) * 1;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'INFRA'){
        $newPer = ($totalIN == 0) ? 0 : $row['IN_EXPENSE_TOTAL'] * 100 /  $totalIN;
        $dataSumIN['IN_EXPENSE_PRETAX_AMOUNT'] += str_replace(',', '', $row['IN_EXPENSE_PRETAX_AMOUNT']) * 1;
        $dataSumIN['IN_EXPENSE_HST'] += str_replace(',', '', $row['IN_EXPENSE_HST']) * 1;
        $dataSumIN['IN_EXPENSE_TOTAL'] += str_replace(',', '', $row['IN_EXPENSE_TOTAL']) * 1;
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'CORP'){
        $newPer = ($totalCO == 0) ? 0 : $row['IN_EXPENSE_TOTAL'] * 100 /  $totalCO;
        $dataSumCO['IN_EXPENSE_PRETAX_AMOUNT'] += $row['IN_EXPENSE_PRETAX_AMOUNT'];
        $dataSumCO['IN_EXPENSE_HST'] += $row['IN_EXPENSE_HST'];
        $dataSumCO['IN_EXPENSE_TOTAL'] += $row['IN_EXPENSE_TOTAL'];
    }

    /*
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
    }*/
    $newPeT = $row['IN_EXPENSE_TOTAL'] * 100 /  $sumTotal;
    
    /*if($data['IN_IS_DISCREPANCY'] == true){
        $newPer = $row['IN_EXPENSE_PERCENTAGE_TOTAL'];
    }*/
    if($dataSumPC['IN_EXPENSE_TOTAL'] == 0 AND $dataSumPE['IN_EXPENSE_TOTAL'] == 0 AND $dataSumIN['IN_EXPENSE_TOTAL'] == 0){
        $newPer = $row['IN_EXPENSE_PERCENTAGE_TOTAL'];
    }
    $query  = "UPDATE EXPENSE_TABLE SET 
                IN_EXPENSE_PERCENTAGE = '".round($newPeT,2)."',
                IN_EXPENSE_PERCENTAGE_TOTAL = '".round($newPer,2)."'
                WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
}


	

/*return [
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
];*/












return [
    "IN_SAVE_SUBMIT" => null,
    "IN_COMMENT_LOG" => $commentLogData,
    // Clean Action Variables  DHS.01
    "IN_SUBMITTER_MANAGER_ACTION" => null,
    "IN_COMMENT_SUBMITTER" => null,
    // Clean Action Variables  DHS.02
    "IN_SUBMITTER_MANAGER_EDIT_ACTION" => null,
    "IN_COMMENT_MANAGER_EDIT" => null,
    // Clear variables - Jhon Chacolla
    "fakeSaveCloseButton" =>  null,
    "saveButtonFake" =>  null,
    "submitButtonFake" =>  null,
    "validateForm" =>  null,
    "saveForm" =>  null,
    "saveFormClose" =>  null,
    "validation" =>  null,
    
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
    "IN_EDIT_SUBMIT" => null,
    "IN_SUBMITTER_MANAGER_ID" => isset($data["IN_SUBMITTER_MANAGER"]["ID"]) ? $data["IN_SUBMITTER_MANAGER"]["ID"] : ''
    //"IN_CASE_TITLE" => $data["IN_INVOICE_VENDOR_LABEL"] . ' - ' . $data["IN_INVOICE_NUMBER"] . ' - ' . $data["IN_INVOICE_DATE"]
];









/**
 * Get the comment logs from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getCommentsLog($ID, $apiUrl, $requestId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT LOG.data->>'$.IN_CL_REQUEST_ID' AS REQUEST_ID,
                        LOG.data->>'$.IN_CL_USER' AS IN_COMMENT_USER,
                        LOG.data->>'$.IN_CL_USER_ID' AS IN_COMMENT_USER_ID,
                        LOG.data->>'$.IN_CL_ROLE' AS IN_COMMENT_ROLE,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_APPROVAL', 'null'), '') AS IN_COMMENT_APPROVAL,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_COMMENT_SAVED', 'null'), '') AS IN_COMMENT_SAVED,
                        LOG.data->>'$.IN_CL_DATE' AS IN_COMMENT_DATE
                        FROM collection_" . $ID . " AS LOG
                        WHERE LOG.data->>'$.IN_CL_REQUEST_ID' = " . $requestId . " 
                        ORDER BY id";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}


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