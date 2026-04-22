<?php 
/*  
 *  By Jhon Chacolla
 * Modified by Ronald Nina
 */
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$dataReturn = [
   // Clean vars
   'saveButtonFake_subPc' => null,
   'fakeSaveCloseButton_subPc' => null,
   'submitButtonFake_subPc' => null,
   'readyScreen' => null,
   'validateForm_subPc' => null,
   'validation_subPc' => null,
   'saveForm_subPc' => null,
   'saveFormClose_subPc' => null,
   'isCustomeGridValid_subPc' => null
];

// insert data into EXPENSE_TABLE from $data['IN_DATA_GRID_SUB_PC']
if(isset($data['IN_DATA_GRID_SUB_PC'])){
   $requestId = $data['_request']['id'];
   $query     = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROUTING_ID = 'PC'";
   $response  = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

   $dataGrid    = html_entity_decode($data['IN_DATA_GRID_SUB_PC']);
   $textBase    = base64_decode($dataGrid);
   $newText     = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
   $expenseData = json_decode($newText,true);

   $created = array_chunk($expenseData, 30);
   foreach($created as $batch){
      $createExpences = preparateExpense($batch, $requestId);
      $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
   }
}
else{
   $dataReturn['gridEmtpyPC'] = true;
}
// END insert data into EXPENSE_TABLE from $data['IN_DATA_GRID']




$data = array_merge($data, $dataReturn);
// Send IN Notification
$task = "PC01";
$emailType = "";
if ($data["IN_PC_ASSIGNED"] == "YES") {
   $emailType = "USER";
} else if ($data["IN_PC_ASSIGNED"] == "NO") {
   $emailType = "GROUP";
}
if ($data["IN_SAVE_SUBMIT_SUB_PC"] != "SAVE" && $data["IN_SAVE_SUBMIT_SUB_PC"] != "SAVE_AND_CLOSE") {
   sendInvoiceNotification($data, $task, $emailType, $api);
}

$dataReturn["IN_SAVE_SUBMIT_SUB_PC"] = null;

/*$newCommentLog = array_map(function($entry) {
    // Copy all fields
    $newEntry = $entry;
    // Modify the date field (first 10 characters)
    if (isset($entry['IN_COMMENT_DATE'])) {
        $newEntry['IN_COMMENT_DATE'] = substr($entry['IN_COMMENT_DATE'], 0, 10);
    }
    return $newEntry;
}, $data['IN_COMMENT_LOG']);
if (!isset($data['IN_COMMENT_LOG_PC']) OR empty($data['IN_COMMENT_LOG_PC'])){
   $dataReturn['IN_COMMENT_LOG_PC'] = $newCommentLog;
}else{*/
   if(!empty($data['IN_COMMENT_LOG'])){
      $newDataComments = [];
      foreach($data['IN_COMMENT_LOG'] as $comment){
         if($comment['IN_COMMENT_ROLE'] != 'PE Ops Reviewer' AND $comment['IN_COMMENT_ROLE'] != 'PE Ops Approver' AND $comment['IN_COMMENT_ROLE'] != 'INFRA Ops Reviewer' AND $comment['IN_COMMENT_ROLE'] != 'INFRA Ops Approver'){
            $comment['IN_COMMENT_DATE'] = substr($comment['IN_COMMENT_DATE'], 0, 10);
            $newDataComments[] = $comment; 
         }
      }
      $dataReturn['IN_COMMENT_LOG_PC'] = $newDataComments;
   }
//}

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
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) ? null : $expenseRow['IN_EXPENSE_ACCOUNT']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['ID']) ? '00000' : $expenseRow['IN_EXPENSE_CORP_PROJ']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']) ? 'Unallocated' : $expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_HST'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_TOTAL'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PERCENTAGE'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_NR'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_NR'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID']) ? null : $expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL']) ? null : $expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID']) ? null : $expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL']) ? null : $expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_FUND_MANAGER']['ID']) ? null : $expenseRow['IN_EXPENSE_FUND_MANAGER']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL']) ? null : $expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_MANDATE']['ID']) ? null : $expenseRow['IN_EXPENSE_MANDATE']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_MANDATE']['LABEL']) ? null : $expenseRow['IN_EXPENSE_MANDATE']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACTIVITY']['ID']) ? null : $expenseRow['IN_EXPENSE_ACTIVITY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_ACTIVITY']['LABEL']) ? null : $expenseRow['IN_EXPENSE_ACTIVITY']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_ENTITY']['ID']) ? null : $expenseRow['IN_EXPENSE_CORP_ENTITY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL']) ? null : $expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_OFFICE']['ID']) ? null : $expenseRow['IN_EXPENSE_OFFICE']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_OFFICE']['LABEL']) ? null : $expenseRow['IN_EXPENSE_OFFICE']['LABEL'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_DEPARTMENT']['ID']) ? null : $expenseRow['IN_EXPENSE_DEPARTMENT']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL']) ? null : $expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_COMPANY']['ID']) ? null : $expenseRow['IN_EXPENSE_COMPANY']['ID'])  . "', ";
        $query .= "'" . (empty($expenseRow['IN_EXPENSE_COMPANY']['LABEL']) ? null : $expenseRow['IN_EXPENSE_COMPANY']['LABEL'])  . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_GL_CODE'] . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_VALIDATION'] . "'";
        $query .= '),';
    }
    $query = substr($query, 0, -1).';';
    return $query;
}