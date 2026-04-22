<?php 
/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use getenv("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/docker-executor-php/tree/master/docs/sdk 
 */

require_once("/Northleaf_PHP_Library.php");

$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$requestId = $data["IN_REQUEST_ID"];
$teamID = $config['teamID']; //'INFRA';
//$dataGrid = $data['ALL_DATA_GRID_TO_BACK'][$teamID];
$dataGrid = $data['ROLLBACK_DATA_'.$teamID];
$expenseData = json_decode(base64_decode($dataGrid),true);
$query = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamID . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

if($expenseData != null){
    $created = array_chunk($expenseData, 30);
    foreach($created as $batch){
        $createExpences = createExpense($apiUrl,$batch, $requestId);
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
    }
}

// Send Invoice Notifications and assign user for next task
if ($teamID == 'INFRA') {
//Assign user
    if (!isset($data["IN_USER_SUB_INFRA"]) || empty($data["IN_USER_SUB_INFRA"])) {
        $assignedINFRA = getLastUserAssigned($apiUrl, $data['_request']['id'], 'node_997');
    } else {
        $assignedINFRA = $data["IN_USER_SUB_INFRA"];
    }
}
if ($teamID == 'PE') {
//Assign user
    if (!isset($data["IN_USER_SUB_PE"]) || empty($data["IN_USER_SUB_PE"])) {
        $assignedPE = getLastUserAssigned($apiUrl, $data['_request']['id'], 'node_1405');
    } else {
        $assignedPE = $data["IN_USER_SUB_PE"];
    }
}
if ($teamID == 'PC') {
//Assign user
    if (!isset($data["IN_USER_SUB_PC"]) || empty($data["IN_USER_SUB_PC"])) {
        $assignedPC = getLastUserAssigned($apiUrl, $data['_request']['id'], 'node_1771');
    } else {
        $assignedPC = $data["IN_USER_SUB_PC"];
    }
}

//$sendData = $data['ALL_DATA_GRID_TO_BACK'];
//$sendData[$teamID] = null;

$dataToReturn = [
    //"ALL_DATA_GRID_TO_BACK" => $sendData,
    "ROLLBACK_DATA_".$teamID => null,
    //"log_ROLLBACK_DATA_".$teamID => $data['ROLLBACK_DATA_'.$teamID],
    "SUBMIT" => null,
    "SAVE_SUBMIT" => null,
    "SAVE_CLOSE_SUBMIT" => null,
    "Allocated" => false,
    "submitButtonFake_subInfra" => null,
    "submitButtonFake_subPc" => null,
    "submitButtonFake_subPe" => null,
    "IN_SAVE_SUBMIT_SUB_INFRA" => null,
    "IN_SAVE_SUBMIT_SUB_PE" => null,
    "IN_SAVE_SUBMIT_SUB_PC" => null,
    "IN_USER_SUB_INFRA" => $assignedINFRA,
    "IN_USER_SUB_PC" => $assignedPC,
    "IN_USER_SUB_PE" => $assignedPE,
    "readyScreen" => null
];

if($teamID == 'INFRA'){
   $dataToReturn['SUBMIT_INFRA'] = null;
   $dataToReturn['SUBMIT_02_INFRA'] = null;
}

if($teamID == 'PC'){
   $dataToReturn['SUBMIT_PC'] = null;
   $dataToReturn['SUBMIT_02_PC'] = null;
}

if($teamID == 'PE'){
   $dataToReturn['SUBMIT_PE'] = null;
   $dataToReturn['SUBMIT_02_PE'] = null;
}

if($teamID == 'CORP'){
    $dataToReturn['SUBMIT_CORP'] = null;
    $dataToReturn['SUBMIT_02_CORP'] = null;
}

if (is_array($config) && !empty($config["IN_IOTH01_PASS"])) {
    $dataReturn["IN_IOTH01_PASS"] = $config["IN_IOTH01_PASS"];
}
if (is_array($config) && !empty($config["IN_PEH01_PASS"])) {
    $dataReturn["IN_PEH01_PASS"] = $config["IN_PEH01_PASS"];
}
if (is_array($config) && !empty($config["IN_PCH01_PASS"])) {
    $dataReturn["IN_PCH01_PASS"] = $config["IN_PCH01_PASS"];
}

return $dataToReturn;

function createExpense($apiUrl, $expenseRow, $requestId) {
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
    foreach($expenseRow as $expense){
        $query .= '(';
        $query .= "'" . $expense['IN_EXPENSE_CASE_ID']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_ROW_ID']. "', ";
        $query .= ""  . $expense['IN_EXPENSE_ROW_NUMBER'] . ", ";
        $query .= ""  . $expense['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_DESCRIPTION']). "', ";
        $query .= "'" . $expense['IN_EXPENSE_ACCOUNT_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_ACCOUNT_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_CORP_PROJ_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_PRETAX_AMOUNT']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_HST']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_TOTAL']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE_TOTAL']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_NR_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_NR_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_TEAM_ROUTING_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_PROJECT_DEAL_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_FUND_MANAGER_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_MANDATE_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_MANDATE_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_ACTIVITY_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_ACTIVITY_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_CORP_ENTITY_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_TRANSACTION_COMMENTS']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_OFFICE_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_OFFICE_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_DEPARTMENT_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_COMPANY_ID']. "', ";
        $query .= "'" . addslashes($expense['IN_EXPENSE_COMPANY_LABEL']) . "', ";
        $query .= "'" . $expense['IN_EXPENSE_GL_CODE']. "', ";
        $query .= "'" . $expense['IN_EXPENSE_VALIDATION'] . "'";
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
                  and element_id in ('" . addslashes($nodeId) . "');";

        $result = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
        if (is_array($result) && isset($result[0]["user_id"])) {
            return $result[0]["user_id"];
        }
        return null;
    } catch (\Throwable $e) {
        return null;
    }
}