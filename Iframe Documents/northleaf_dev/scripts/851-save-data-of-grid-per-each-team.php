<?php 

require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$dataReturn = [];
$requestId   = $data['_request']['id'];
$teamID      = $config['teamID']; //'PE, INFRA, PC';
if($teamID =='CORP'){
    $dataGrid            = html_entity_decode($data['IN_DATA_GRID_1_CO']);
    $textBase            = base64_decode($dataGrid);
    $newText             = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
    $expenseData         = json_decode($newText,true);
    $dataGrid            = html_entity_decode($data['IN_DATA_GRID_2_CO']);
    $textBase            = base64_decode($dataGrid);
    $newText             = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
    $expenseDataDisabled = json_decode($newText,true);
    //return $expenseDataDisabled[0]['IN_EXPENSE_TEAM_ROUTING'];
    
    $query = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    $mainIdex = 0;
    foreach ($expenseDataDisabled as $key => $expense){
        if($expense['IN_EXPENSE_TEAM_ROUTING']['ID'] == 'PE'){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            $created[] = $expense;
        }
    }
    foreach ($expenseDataDisabled as $key => $expense){
        if($expense['IN_EXPENSE_TEAM_ROUTING']['ID'] == 'PC'){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            $created[] = $expense;
        }
    }
    foreach ($expenseDataDisabled as $key => $expense){
        if($expense['IN_EXPENSE_TEAM_ROUTING']['ID'] == 'INFRA'){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            $created[] = $expense;
        }
    }
    foreach ($expenseData as $key => $expense){
        $mainIdex++;
        $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
        $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
        $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
        $created[] = $expense;
    }
    $created = array_chunk($created, 30);
    foreach($created as $batch){
        $createExpences = preparateExpense($batch, $requestId);
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
    }
}
else{
    $dataGrid    = html_entity_decode($data['IN_DATA_GRID_02_'.$teamID]);
    $expenseData = json_decode(base64_decode($dataGrid),true);

    $sqlDeleteTeam = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '". $requestId ."' AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamID . "'";
    $resDeleteTeam = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlDeleteTeam));

    $created = array_chunk($expenseData, 30);
    foreach($created as $batch){
        $createExpences = preparateExpense($batch, $requestId);
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
    }
}

if($teamID == "PC"){
    $dataReturn['SUBMIT_02_PC'] = null;
    $dataReturn['SUBMIT_PC_H'] = null;
}
if($teamID == "PE"){
    $dataReturn['SUBMIT_02_PE'] = null;
    $dataReturn['SUBMIT_PE_H'] = null;
}
if($teamID == "INFRA"){
    $dataReturn['SUBMIT_02_INFRA'] = null;
    $dataReturn['SUBMIT_INFRA_H'] = null;
}

if(isset($config['cleanVar']) AND $config['cleanVar'] != ''){
    $dataReturn [$config['cleanVar']] = null;
}
$dataReturn ['readyScreen'] = null;
return $dataReturn;


/* generate a sql sentece with data grid
 *
 * @param (String) $expense
 * @param (Int) $requestId
 * @return (String) $response
 *
 * by Daniel Aguilar
*/
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
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['ID']) ? '00000' : $expenseRow['IN_EXPENSE_CORP_PROJ']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']) ? 'Unallocated' : addslashes($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']))  . "', ";
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
}