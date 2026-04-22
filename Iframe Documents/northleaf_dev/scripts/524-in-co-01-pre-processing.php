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

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$baseURL = getenv('ENVIRONMENT_BASE_URL');
$requestId = $data["_request"]["id"];
$fileId = $data["IN_UPLOAD_PDF"];

$dataPE = [];
$dataPC = [];
$dataIN = [];
$dataCO = [];


//$sumCo    = $data['IN_SUMMARY_TOTAL_GRID']['CORP']['IN_EXPENSE_TOTAL'] 
$sumOther = $data['IN_SUMMARY_TOTAL_GRID']['INFRA']['IN_EXPENSE_TOTAL'] + $data['IN_SUMMARY_TOTAL_GRID']['PC']['IN_EXPENSE_TOTAL'] + $data['IN_SUMMARY_TOTAL_GRID']['PE']['IN_EXPENSE_TOTAL']; 

$query  = "SELECT * 
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'
            ORDER BY cast(substring_index(IN_EXPENSE_ROW_NUMBER,'.',1) as unsigned),
            cast(substring_index(substring_index(IN_EXPENSE_ROW_NUMBER,'.',2),'.',-1) as unsigned)";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
foreach($response as $row){
    $glCode = $data['vendorInformation'][0]['EXPENSE_VENDOR_COMPANYCODE'];
    $glCode .= empty($row['IN_EXPENSE_ACCOUNT_ID']) ? '' :  '-' . $row['IN_EXPENSE_ACCOUNT_ID'];
    $expNR = '';
    if(!empty($row['IN_EXPENSE_NR_ID'])){
        if($row['IN_EXPENSE_NR_ID'] == 'Recoverable'){
            $expNR = 2;
        }
        if($row['IN_EXPENSE_NR_ID'] == 'Non-Recoverable'){
            $expNR = 1;
        }
        if($row['IN_EXPENSE_NR_ID'] == 'Unallocated'){
            $expNR = 0;
        }
    }
    $glCode .= empty($expNR) ? '' :  '-' . $expNR;
    $glCode .= empty($row['IN_EXPENSE_MANDATE_ID']) ? '-0000' :  '-' . $row['IN_EXPENSE_MANDATE_ID'];
    $glCode .= empty($row['IN_EXPENSE_OFFICE_ID']) ? '' :  '-' . $row['IN_EXPENSE_OFFICE_ID'];
    $glCode .= empty($row['IN_EXPENSE_DEPARTMENT_ID']) ? '' : '-' . $row['IN_EXPENSE_DEPARTMENT_ID'];
    $glCode .= empty($row['IN_EXPENSE_ACTIVITY_ID']) ? '-000' : '-' .$row['IN_EXPENSE_ACTIVITY_ID'];
    $glCode .= empty($row['IN_EXPENSE_CORP_PROJ_ID']) ? '-00000' : '-' . $row['IN_EXPENSE_CORP_PROJ_ID'];
    $row['IN_EXPENSE_GL_CODE'] = $glCode;
    if($sumOther == 0)
        $sumOther = 1;
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PE'){
        $row['IN_EXPENSE_PERCENTAGE_TOTAL'] = round($row['IN_EXPENSE_TOTAL'] * 100 / $sumOther,2);
        $dataPE[] = $row; 
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'PC'){
        $row['IN_EXPENSE_PERCENTAGE_TOTAL'] = round($row['IN_EXPENSE_TOTAL'] * 100 / $sumOther,2);
        $dataPC[] = $row; 
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'INFRA'){
        $row['IN_EXPENSE_PERCENTAGE_TOTAL'] = round($row['IN_EXPENSE_TOTAL'] * 100 / $sumOther,2);
        $dataIN[] = $row; 
    }
    if($row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'CORP'){
        //if($data['IN_SUMMARY_TOTAL_GRID']['PE']['IN_EXPENSE_TOTAL'] > 0 OR $data['IN_SUMMARY_TOTAL_GRID']['PC']['IN_EXPENSE_TOTAL'] > 0 OR $data['IN_SUMMARY_TOTAL_GRID']['INFRA']['IN_EXPENSE_TOTAL'] > 0){
        if($data['IN_IS_DISCREPANCY'] != true){
            $row['IN_EXPENSE_PERCENTAGE_TOTAL'] = round($row['IN_EXPENSE_TOTAL'] * 100 / $data['IN_SUMMARY_TOTAL_GRID']['CORP']['IN_EXPENSE_TOTAL'],2);
        }
        $dataCO[] = $row; 
    }
}
sleep(2);
$query  = "DELETE 
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

$newIndex = 1;
foreach($dataPE as $row){
    $row['IN_EXPENSE_TEAM_ROW_INDEX'] = $newIndex;
    createExpense($apiUrl, $row, $requestId);
    $newIndex++;
}
foreach($dataPC as $row){
    $row['IN_EXPENSE_TEAM_ROW_INDEX'] = $newIndex;
    createExpense($apiUrl, $row, $requestId);
    $newIndex++;
}
foreach($dataIN as $row){
    $row['IN_EXPENSE_TEAM_ROW_INDEX'] = $newIndex;
    createExpense($apiUrl, $row, $requestId);
    $newIndex++;
}
foreach($dataCO as $row){
    $row['IN_EXPENSE_TEAM_ROW_INDEX'] = $newIndex;
    $ress = createExpense($apiUrl, $row, $requestId);
    $newIndex++;
}

//$summaryTotal = $data['IN_SUMMARY_TOTAL_GRID'];
//$invoiceTotal = $summaryTotal['PC']['IN_EXPENSE_TOTAL'] + $summaryTotal['PE']['IN_EXPENSE_TOTAL'] + $summaryTotal['INFRA']['IN_EXPENSE_TOTAL'] + $summaryTotal['CORP']['IN_EXPENSE_TOTAL'];

/*$gridCurrency   = $data['IN_INVOICE_CURRENCY'];
$vendorCurrency = (isset($data['newVendorInformation']) AND isset($data['newVendorInformation'][0]) AND isset($data['newVendorInformation'][0]['EXPENSE_VENDOR_CURRENCY'])) ? $data['newVendorInformation'][0]['EXPENSE_VENDOR_CURRENCY'] : '';
$fxReq          = ($vendorCurrency == $gridCurrency) ? 'No' : 'Yes';
*/


$time_start1 = microtime(true); //////////////////time execution
/*  
 *  By Telmo Chiri
 */
// Send IN Notification
$task = "CO01";
$emailType = "";

if (isset($config["emailType"]) && $config["emailType"] == "FROM_COH01") {
   $emailType = "FROM_COH01";
} else {
   if ($data["IN_CORP_ASSIGNED"] == "YES") {
      $emailType = "USER";
   } else if ($data["IN_CORP_ASSIGNED"] == "NO") {
      $emailType = "GROUP";
   }
}

sendInvoiceNotification($data, $task, $emailType, $api);

return [
    //"FX_DATA" => ["IN_FX_REQUIRED" => $fxReq],
    //"IN_FX_REQUIRED" => $fxReq,
    "COPY_TEMP_COMPANY_ID" => $data['vendorInformation'][0]['EXPENSE_VENDOR_COMPANYCODE'],
    "newStatus" => "corporate",
    "corporateFX" => false,
    "readyScreen" => null,
    "CO_TITLE" => "Corporate Analyst Review",
    "SUBMIT_CO" => null
];


function createExpense($apiUrl, $expense, $requestId) {
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
    $query .= 'VALUES (';
    $query .= "'" . $requestId . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ROW_ID'] . "', ";
    $query .= ""  . $expense['IN_EXPENSE_ROW_NUMBER'] . ", ";
    $query .= ""  . $expense['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_DESCRIPTION']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_ACCOUNT_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_CORP_PROJ_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_HST'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_NR_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_TEAM_ROUTING_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_PROJECT_DEAL_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_FUND_MANAGER_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_MANDATE_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_ACTIVITY_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_CORP_ENTITY_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_OFFICE_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_DEPARTMENT_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY_ID'] . "', ";
    $query .= "'" . addslashes($expense['IN_EXPENSE_COMPANY_LABEL']) . "', ";
    $query .= "'" . $expense['IN_EXPENSE_GL_CODE'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_VALIDATION'] . "'";
    $query .= ');';
    //return $query;

    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}