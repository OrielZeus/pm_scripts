<?php 
/**********************************
 * Apply Allocation and Send Notification - Consolidated
 *
 *********************************/
$time_start = microtime(true); //////////////////time execution
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$teamID = $config['teamID']; //'PE, INFRA, PC';

// Update Sub Totals

$dataGrid    = html_entity_decode($data['IN_DATA_GRID_SUB_'.$teamID]);
$textBase    = base64_decode($dataGrid);
$newText     = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
$expenseData = json_decode($newText,true);



$sqlDeleteTeam = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '".$data['_request']['id']."' AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamID . "'";
$resDeleteTeam = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlDeleteTeam));

/*foreach ($expenseData as $key => $expense){
    $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
    $created[] = $expense;
}*/
$created = array_chunk($expenseData, 30);
foreach($created as $batch){
    $createExpences = preparateExpense($batch, $data['_request']['id']);
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
}

$expenseByRequest = getAllExpenseByRequest($apiUrl, $data['_request']['id'],$teamID);

foreach($expenseByRequest as $row) {
    if(isset($row['IN_EXPENSE_MANDATE_ID']) AND $row['IN_EXPENSE_MANDATE_ID'] == '') {
        if(isset($row['IN_EXPENSE_PROJECT_DEAL_ID']) AND $row['IN_EXPENSE_PROJECT_DEAL_ID'] != '') {
            $dealAllocationData = gatDataFromExpenseAllocation('DEAL_ID',$row['IN_EXPENSE_PROJECT_DEAL_ID']);
            if(sizeof($dealAllocationData) > 0){
                $dealAllocationData = array_chunk($dealAllocationData, 50);
                foreach($dealAllocationData as $batch){
                    $createRows = insertAllocation($row, $batch, $data['_request']['id'], 'DEAL_ALLOCATION');
                    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createRows));
                }
                $queryAllocation = "DELETE FROM EXPENSE_TABLE 
                    WHERE 
                        IN_EXPENSE_CASE_ID = '".$data['_request']['id']."' 
                        AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamID . "'
                        AND IN_EXPENSE_ROW_NUMBER = '" . $row['IN_EXPENSE_ROW_NUMBER'] . "'";
                $allocationData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryAllocation));
            } else {
                $sqlUpdate = "UPDATE EXPENSE_TABLE
                        SET IN_EXPENSE_PERCENTAGE_TOTAL = 100
                        WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
                $allocationUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdate));
            }

        } else {
            if(isset($row['IN_EXPENSE_FUND_MANAGER_ID']) AND $row['IN_EXPENSE_FUND_MANAGER_ID'] != ''){
                $fundManagerAllocationData = gatDataFromExpenseAllocation('FUND_MANAGER_ID',$row['IN_EXPENSE_FUND_MANAGER_ID']);
                if(sizeof($fundManagerAllocationData)  > 0){
                    $fundManagerAllocationData = array_chunk($fundManagerAllocationData, 50);
                    foreach($fundManagerAllocationData as $batch){
                        $createRows = insertAllocation($row,$batch,$data['_request']['id'],'FUND_MANAGER_ALLOCATION');
                        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createRows));
                    }
                    $queryAllocation = "DELETE FROM EXPENSE_TABLE 
                    WHERE 
                        IN_EXPENSE_CASE_ID = '".$data['_request']['id']."' 
                        AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamID . "'
                        AND IN_EXPENSE_ROW_NUMBER = '" . $row['IN_EXPENSE_ROW_NUMBER'] . "'";
                    $allocationData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryAllocation));
                }
                else{
                    $sqlUpdate = "UPDATE EXPENSE_TABLE
                        SET IN_EXPENSE_PERCENTAGE_TOTAL = 100
                        WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
                    $allocationUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdate));
                }
            }
            else{
                $sqlUpdate = "UPDATE EXPENSE_TABLE
                        SET IN_EXPENSE_PERCENTAGE_TOTAL = 100
                        WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
                $allocationUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdate));
            }
        }
    } else {
        $sqlUpdate = "UPDATE EXPENSE_TABLE
                        SET IN_EXPENSE_PERCENTAGE_TOTAL = 100
                        WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
        $allocationUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdate));
    }
}

//$dataToSend = (isset($data['ALL_DATA_GRID_TO_BACK'])) ? $data['ALL_DATA_GRID_TO_BACK'] : [];
//$dataToSend[$teamID] = (isset($data['ALL_DATA_GRID_TO_BACK']) AND isset($data['ALL_DATA_GRID_TO_BACK'][$teamID])) ? $data['ALL_DATA_GRID_TO_BACK'][$teamID] : base64_encode( json_encode( $expenseByRequest ) );
$rollback_data = isset($data['ROLLBACK_DATA_'.$teamID]) ? $data['ROLLBACK_DATA_'.$teamID] : base64_encode( json_encode( $expenseByRequest ) );

// Send Invoice Notifications and assign user for next task
if ($config["nextTask"] == "IOT02") {
    //Declare task title
    $dataReturn["INFRA_TITLE"] = "Infrastructure Analyst Review";
    //Assign user
    if (!isset($data["IN_USER_SUB_INFRA"]) || empty($data["IN_USER_SUB_INFRA"])) {
        $dataReturn["IN_USER_SUB_INFRA"] = getLastUserAssigned($apiUrl, $data['_request']['id'], 'node_855');
    }
    //Send notification
    if (isset($data["IN_IOTH01_PASS"]) && $data["IN_IOTH01_PASS"] == "YES") {
        $task = "IOT02";
        $emailType = "";
        sendInvoiceNotification($data, $task, $emailType, $api);
    }
}
if ($config["nextTask"] == "PE02") {
    //Declare task title
    $dataReturn["PE_TITLE"] = "Private Equity Analyst Review";
    //Assign user
    if (!isset($data["IN_USER_SUB_PE"]) || empty($data["IN_USER_SUB_PE"])) {
        $dataReturn["IN_USER_SUB_PE"] = getLastUserAssigned($apiUrl, $data['_request']['id'], 'node_1346');
    }
    //Send notification
    if (isset($data["IN_PEH01_PASS"]) && $data["IN_PEH01_PASS"] == "YES") {
        $task = "PE02";
        $emailType = "";
        sendInvoiceNotification($data, $task, $emailType, $api);
    }
}
if ($config["nextTask"] == "PC02") {
    //Declare task title
    $dataReturn["PC_TITLE"] = "Private Credit Analyst Review";
    //Assign user
    if (!isset($data["IN_USER_SUB_PC"]) || empty($data["IN_USER_SUB_PC"])) {
        $dataReturn["IN_USER_SUB_PC"] = getLastUserAssigned($apiUrl, $data['_request']['id'], 'node_1716');
    }
    //Send notification
    if (isset($data["IN_PCH01_PASS"]) && $data["IN_PCH01_PASS"] == "YES") {
        $task = "PC02";
        $emailType = "FROM_PCH01";
        sendInvoiceNotification($data, $task, $emailType, $api);
    }
}

$time_end = microtime(true); //////////////////time execution
$execution_time = ($time_end - $time_start); //////////////////time execution

$dataReturn["readyScreen"] = null;
$dataReturn["dataTimeExec_Allocated_".$teamID] = $execution_time;
$dataReturn["ROLLBACK_DATA_".$teamID] = $rollback_data;


return $dataReturn;

/* Get all Expense records by request ID from EXPENSE_TABLE
 *
 * @param (String) $apiUrl
 * @param (Int) $requestId
 * @return (Array) $response
 *
 * by Jhon Chacolla
*/
function getAllExpenseByRequest ($apiUrl, $requestId,$teamID) {
    $query  = "";
    $query .= "SELECT * ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_TEAM_ROUTING_ID = '".$teamID."'
                AND IN_EXPENSE_CASE_ID = '" . $requestId . "' ";
    $query .= "ORDER BY IN_EXPENSE_ROW_NUMBER ASC ; ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}

function gatDataFromExpenseAllocation($filter,$value){
    global $apiUrl;
    $queryAllocation = "SELECT data->>'$.DEAL_ALLOCATION' as DEAL_ALLOCATION,
                        data->>'$.FUND_MANAGER_ALLOCATION' as FUND_MANAGER_ALLOCATION,
                        data->>'$.MANDATE_ID' as MANDATE_ID,
                        data->>'$.MANDATE_NAME' as MANDATE_NAME,
                        data->>'$.FUND_MANAGER' as FUND_MANAGER,
                        data->>'$.FUND_MANAGER_ID' as FUND_MANAGER_ID,
                        data->>'$.DEAL' as DEAL,
                        data->>'$.DEAL_ID' as DEAL_ID 
                        FROM collection_" . getCollectionId('IN_EXPENSE_ALLOCATION', $apiUrl) . "
                        WHERE data->>'$." . $filter . "' = '" . $value . "'";
    $allocationData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryAllocation));
    if(sizeof($allocationData) > 0)
        $allocationData = array_map("unserialize", array_unique(array_map("serialize", $allocationData)));
    return $allocationData;
}
function insertAllocation($row, $allocationData, $requestId, $allocationKey) {//'FUND_MANAGER_ALLOCATION'
    $query = "INSERT INTO EXPENSE_TABLE (
                IN_EXPENSE_CASE_ID, 
                IN_EXPENSE_ROW_ID, 
                IN_EXPENSE_DESCRIPTION, 
                IN_EXPENSE_ACCOUNT_ID, 
                IN_EXPENSE_ACCOUNT_LABEL, 
                IN_EXPENSE_CORP_PROJ_ID, 
                IN_EXPENSE_CORP_PROJ_LABEL, 
                IN_EXPENSE_PRETAX_AMOUNT, 
                IN_EXPENSE_HST, 
                IN_EXPENSE_TOTAL, 
                IN_EXPENSE_PERCENTAGE, 
                IN_EXPENSE_PERCENTAGE_TOTAL, 
                IN_EXPENSE_NR_ID, 
                IN_EXPENSE_NR_LABEL, 
                IN_EXPENSE_TEAM_ROUTING_ID, 
                IN_EXPENSE_TEAM_ROUTING_LABEL, 
                IN_EXPENSE_PROJECT_DEAL_ID, 
                IN_EXPENSE_PROJECT_DEAL_LABEL, 
                IN_EXPENSE_FUND_MANAGER_ID, 
                IN_EXPENSE_FUND_MANAGER_LABEL, 
                IN_EXPENSE_MANDATE_ID, 
                IN_EXPENSE_MANDATE_LABEL, 
                IN_EXPENSE_ACTIVITY_ID, 
                IN_EXPENSE_ACTIVITY_LABEL, 
                IN_EXPENSE_CORP_ENTITY_ID, 
                IN_EXPENSE_CORP_ENTITY_LABEL, 
                IN_EXPENSE_TRANSACTION_COMMENTS, 
                IN_EXPENSE_OFFICE_ID, 
                IN_EXPENSE_OFFICE_LABEL, 
                IN_EXPENSE_DEPARTMENT_ID, 
                IN_EXPENSE_DEPARTMENT_LABEL, 
                IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION, 
                IN_EXPENSE_INVESTRAN_HST_DESCRIPTION, 
                IN_EXPENSE_COMPANY_ID, 
                IN_EXPENSE_COMPANY_LABEL, 
                IN_EXPENSE_GL_CODE, 
                IN_EXPENSE_ROW_NUMBER, 
                IN_EXPENSE_VALIDATION,
                IN_EXPENSE_TEAM_ROW_INDEX ) VALUES ";
    $indexRow = 1;
    foreach($allocationData as $allocation) {
        $cloneRow = $row;
        $perAllocation = $allocation[$allocationKey] / 100;
        $query .= "(";
        
        $cloneRow['IN_EXPENSE_DESCRIPTION']      = addslashes($cloneRow['IN_EXPENSE_DESCRIPTION']);
        $cloneRow['IN_EXPENSE_ROW_NUMBER']       = $cloneRow['IN_EXPENSE_ROW_NUMBER'] . "." . $indexRow;
        $cloneRow['IN_EXPENSE_PRETAX_AMOUNT']    = $cloneRow['IN_EXPENSE_PRETAX_AMOUNT'] * $perAllocation;
        $cloneRow['IN_EXPENSE_HST']              = $cloneRow['IN_EXPENSE_HST'] * $perAllocation;
        $cloneRow['IN_EXPENSE_TOTAL']            = $cloneRow['IN_EXPENSE_TOTAL'] * $perAllocation;
        $cloneRow['IN_EXPENSE_PERCENTAGE_TOTAL'] = $perAllocation * 100;
        $cloneRow['IN_EXPENSE_ROW_ID']           = $cloneRow['IN_EXPENSE_ROW_ID'] . $cloneRow['IN_EXPENSE_ROW_NUMBER'];
        $cloneRow['IN_EXPENSE_MANDATE_ID']       = $allocation['MANDATE_ID'];
        $cloneRow['IN_EXPENSE_MANDATE_LABEL']    = $allocation['MANDATE_NAME'];
        if($allocationKey == 'DEAL_ALLOCATION'){
            $cloneRow['IN_EXPENSE_FUND_MANAGER_ID']    = $allocation['FUND_MANAGER_ID'];
            $cloneRow['IN_EXPENSE_FUND_MANAGER_LABEL'] = $allocation['FUND_MANAGER'];
        }
        else{
            $cloneRow['IN_EXPENSE_PROJECT_DEAL_ID']    = $allocation['DEAL_ID'];
            $cloneRow['IN_EXPENSE_PROJECT_DEAL_LABEL'] = $allocation['DEAL'];
        }

        $cloneRow['IN_EXPENSE_ACCOUNT_LABEL'] = addslashes($cloneRow['IN_EXPENSE_ACCOUNT_LABEL']);
        $cloneRow['IN_EXPENSE_CORP_PROJ_LABEL'] = addslashes($cloneRow['IN_EXPENSE_CORP_PROJ_LABEL']);
        $cloneRow['IN_EXPENSE_NR_LABEL'] = addslashes($cloneRow['IN_EXPENSE_NR_LABEL']);
        $cloneRow['IN_EXPENSE_TEAM_ROUTING_LABEL'] = addslashes($cloneRow['IN_EXPENSE_TEAM_ROUTING_LABEL']);
        $cloneRow['IN_EXPENSE_PROJECT_DEAL_LABEL'] = addslashes($cloneRow['IN_EXPENSE_PROJECT_DEAL_LABEL']);
        $cloneRow['IN_EXPENSE_FUND_MANAGER_LABEL'] = addslashes($cloneRow['IN_EXPENSE_FUND_MANAGER_LABEL']);
        $cloneRow['IN_EXPENSE_MANDATE_LABEL'] = addslashes($cloneRow['IN_EXPENSE_MANDATE_LABEL']);
        $cloneRow['IN_EXPENSE_ACTIVITY_LABEL'] = addslashes($cloneRow['IN_EXPENSE_ACTIVITY_LABEL']);
        $cloneRow['IN_EXPENSE_CORP_ENTITY_LABEL'] = addslashes($cloneRow['IN_EXPENSE_CORP_ENTITY_LABEL']);
        $cloneRow['IN_EXPENSE_OFFICE_LABEL'] = addslashes($cloneRow['IN_EXPENSE_OFFICE_LABEL']);
        $cloneRow['IN_EXPENSE_DEPARTMENT_LABEL'] = addslashes($cloneRow['IN_EXPENSE_DEPARTMENT_LABEL']);
        $cloneRow['IN_EXPENSE_COMPANY_LABEL'] = addslashes($cloneRow['IN_EXPENSE_COMPANY_LABEL']);
        $query .= "'" . implode("','", $cloneRow) . "'";
        $query .= "),";
        $indexRow++;
        //$daaaaa[] = $cloneRow;
    }
    //return $cloneRow;
    $query = substr($query, 0, -1);
    return $query;
}

function preparateExpense( $expense, $requestId) {
    $query  = '';
    $query .= 'INSERT INTO EXPENSE_TABLE (';
    $query .= 'IN_EXPENSE_CASE_ID, ';
    $query .= 'IN_EXPENSE_ROW_ID, ';
    $query .= 'IN_EXPENSE_ROW_NUMBER, ';
    $query .= 'IN_EXPENSE_TEAM_ROW_INDEX, ';
    $query .= 'IN_EXPENSE_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_ACCOUNT_ID, ';
    $query .= 'IN_EXPENSE_ACCOUNT_LABEL, ';
    $query .= 'IN_EXPENSE_CORP_PROJ_ID, ';
    $query .= 'IN_EXPENSE_CORP_PROJ_LABEL, ';
    $query .= 'IN_EXPENSE_PRETAX_AMOUNT, ';
    $query .= 'IN_EXPENSE_HST, ';
    $query .= 'IN_EXPENSE_TOTAL, ';
    $query .= 'IN_EXPENSE_PERCENTAGE, ';
    $query .= 'IN_EXPENSE_PERCENTAGE_TOTAL, ';
    $query .= 'IN_EXPENSE_NR_ID, ';
    $query .= 'IN_EXPENSE_NR_LABEL, ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_ID, ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_LABEL, ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_ID, ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_LABEL, ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_ID, ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_LABEL, ';
    $query .= 'IN_EXPENSE_MANDATE_ID, ';
    $query .= 'IN_EXPENSE_MANDATE_LABEL, ';
    $query .= 'IN_EXPENSE_ACTIVITY_ID, ';
    $query .= 'IN_EXPENSE_ACTIVITY_LABEL, ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_ID, ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_LABEL, ';
    $query .= 'IN_EXPENSE_TRANSACTION_COMMENTS, ';
    $query .= 'IN_EXPENSE_OFFICE_ID, ';
    $query .= 'IN_EXPENSE_OFFICE_LABEL, ';
    $query .= 'IN_EXPENSE_DEPARTMENT_ID, ';
    $query .= 'IN_EXPENSE_DEPARTMENT_LABEL, ';
    $query .= 'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_COMPANY_ID, ';
    $query .= 'IN_EXPENSE_COMPANY_LABEL, ';
    $query .= 'IN_EXPENSE_GL_CODE, ';
    $query .= 'IN_EXPENSE_VALIDATION ';
    $query .= ') ';
    $query .= 'VALUES ';
    foreach($expense as $expenseRow){
        $query .= '(';
        $query .= "'" . $requestId . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_ROW_ID'] . "', ";
        $query .= ""  . $expenseRow['IN_EXPENSE_ROW_NUMBER'] . ", ";
        $query .= ""  . $expenseRow['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
        $query .= "'" . addslashes($expenseRow['IN_EXPENSE_DESCRIPTION']) . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACCOUNT']['ID']) ? null : $expenseRow['IN_EXPENSE_ACCOUNT']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['ID']) ? null : $expenseRow['IN_EXPENSE_CORP_PROJ']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']))  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_HST'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_TOTAL'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PERCENTAGE'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_NR'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_NR'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID']) ? null : $expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID']) ? null : $expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_FUND_MANAGER']['ID']) ? null : $expenseRow['IN_EXPENSE_FUND_MANAGER']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_MANDATE']['ID']) ? null : $expenseRow['IN_EXPENSE_MANDATE']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_MANDATE']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_MANDATE']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACTIVITY']['ID']) ? null : $expenseRow['IN_EXPENSE_ACTIVITY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACTIVITY']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_ACTIVITY']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_ENTITY']['ID']) ? null : $expenseRow['IN_EXPENSE_CORP_ENTITY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL']))  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_OFFICE']['ID']) ? null : $expenseRow['IN_EXPENSE_OFFICE']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_OFFICE']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_OFFICE']['LABEL']))  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_DEPARTMENT']['ID']) ? null : $expenseRow['IN_EXPENSE_DEPARTMENT']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL']))  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_COMPANY']['ID']) ? null : $expenseRow['IN_EXPENSE_COMPANY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_COMPANY']['LABEL']) ? null : addslashes($expenseRow['IN_EXPENSE_COMPANY']['LABEL']))  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_GL_CODE'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_VALIDATION'] . "'";
        $query .= '),';
    }
    $query = substr($query, 0, -1).';';
    return $query;
    //return $query;

    //$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    //return $response;
}
/**
** getLastUserAssigned
** get the user that claimed the task .01
** Adriana Centellas
**/
function getLastUserAssigned ($apiUrl, $requestId, $nodeId) {
    try {
        $query = "select user_id 
                  from process_request_tokens 
                  where process_request_id = " . intval($requestId) . " 
                  and element_id in ('" . addslashes($nodeId) . "') order by completed_at desc limit 1;";

        $result = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
        if (is_array($result) && isset($result[0]["user_id"])) {
            return $result[0]["user_id"];
        }
        return null;
    } catch (\Throwable $e) {
        return null;
    }
}