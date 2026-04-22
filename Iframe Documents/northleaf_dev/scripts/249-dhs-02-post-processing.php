<?php 
/**********************************
 * IN - DHS.02 Post Processing 
 *
 * by Favio Mollinedo
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$datetime = new DateTime();
// Set the timezone to America/New_York (which covers EST/EDT)
$timezone = new DateTimeZone('America/New_York');
$datetime->setTimezone($timezone);
// Format and display the date and time
$currentDate = $datetime->format('Y-m-d');

//Get collections IDs
$collectionName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get role from task
/*$userRole = isset($data["submittorRoleInfo"]) && $data["submittorRoleInfo"] !== null 
    ? $data["submittorRoleInfo"] 
    : "Submitter";*/

$userRole = "Invoice Approver";

//if($data['IN_SAVE_SUBMIT'] == 'SUBMIT'){
if($data['IN_SAVE_SUBMIT'] != 'SAVE' AND $data['IN_SAVE_SUBMIT'] != 'SAVE_AND_CLOSE'){
    //Save comments into collection
    $dataRecord = [
        "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
        "IN_CL_REQUEST_ID" => $data['_request']['id'],
        "IN_CL_USER" => $data["IN_SUBMITTER_MANAGER"]["FULL_NAME"],
        "IN_CL_USER_ID" => $data["IN_SUBMITTER_MANAGER"]["ID"],
        "IN_CL_ROLE" => $userRole,
        "IN_CL_ROLE_ID" => 2,
        "IN_CL_APPROVAL" => $data["IN_SUBMITTER_MANAGER_EDIT_ACTION"],
        "IN_CL_COMMENT_SAVED" => empty($data['IN_COMMENT_MANAGER_EDIT']) ? "" : $data['IN_COMMENT_MANAGER_EDIT'],
        "IN_CL_DATE" => $currentDate,
        "IN_SUBMIT" => null,
    ];
    $getResponse = !empty($data['_request']['id']) ? postRecordToCollection($dataRecord, $collectionID) : null;
}



// insert data into EXPENSE_TABLE from $data['IN_DATA_GRID']
$requestId = $data['_request']['id'];
$indexTa   = 0;
$query     = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response  = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

$dataGrid    = html_entity_decode($data['IN_DATA_GRID']);
$textBase    = base64_decode($dataGrid);
$newText     = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
$expenseData = json_decode($newText,true);
$created = [];
foreach ($expenseData as $key => $expense){
    $indexTa++;
    $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
    $expense['IN_EXPENSE_ROW_NUMBER'] = $indexTa;
    $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $indexTa;
    $created[] = $expense;
}

$created = array_chunk($created, 30);
foreach($created as $batch){
    $createExpences = preparateExpense($batch, $requestId);
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
}
// END insert data into EXPENSE_TABLE from $data['IN_DATA_GRID']





// Update Sub Totals - Jhon Chacolla
$expenseByRequest = getAllExpenseByRequest($apiUrl, $data['_request']['id']);

$inTotalPreTaxAmountInitSubInfra = 0;
$inTotalHstInitSubInfra = 0;
$inTotalTotalInitSubInfra = 0;
$inTotalPercentageTotalInitSubInfra = 0;

$inTotalPreTaxAmountInitSubPc = 0;
$inTotalHstInitSubPc = 0;
$inTotalTotalInitSubPc = 0;
$inTotalPercentageTotalInitSubPc = 0;

$inTotalPreTaxAmountInitSubPe = 0;
$inTotalHstInitSubPe = 0;
$inTotalTotalInitSubPe = 0;
$inTotalPercentageTotalInitSubPe = 0;

$inTotalPreTaxAmountInitSubCorp = 0;
$inTotalHstInitSubCorp = 0;
$inTotalTotalInitSubCorp = 0;
$inTotalPercentageTotalInitSubCorp = 0;

$totalRecordsInfra = 0;
$totalRecordsPc = 0;
$totalRecordsPe = 0;
$totalRecordsCorp = 0;

foreach($expenseByRequest as $key => $expense){
    switch ($expense['IN_EXPENSE_TEAM_ROUTING_ID']) {
        case 'INFRA':
            $inTotalPreTaxAmountInitSubInfra += $expense['IN_EXPENSE_PRETAX_AMOUNT'];
            $inTotalHstInitSubInfra += $expense['IN_EXPENSE_HST'];
            $inTotalTotalInitSubInfra += $expense['IN_EXPENSE_TOTAL'];
            $inTotalPercentageTotalInitSubInfra += $expense['IN_EXPENSE_PERCENTAGE_TOTAL'];
            $totalRecordsInfra++;
            break;
        case 'PC':
            $inTotalPreTaxAmountInitSubPc += $expense['IN_EXPENSE_PRETAX_AMOUNT'];
            $inTotalHstInitSubPc += $expense['IN_EXPENSE_HST'];
            $inTotalTotalInitSubPc += $expense['IN_EXPENSE_TOTAL'];
            $inTotalPercentageTotalInitSubPc += $expense['IN_EXPENSE_PERCENTAGE_TOTAL'];
            $totalRecordsPc++;
            break;
        case 'PE':
            $inTotalPreTaxAmountInitSubPe += $expense['IN_EXPENSE_PRETAX_AMOUNT'];
            $inTotalHstInitSubPe += $expense['IN_EXPENSE_HST'];
            $inTotalTotalInitSubPe += $expense['IN_EXPENSE_TOTAL'];
            $inTotalPercentageTotalInitSubPe += $expense['IN_EXPENSE_PERCENTAGE_TOTAL'];
            $totalRecordsPe++;
            break;
        case 'CORP':
            $inTotalPreTaxAmountInitSubCorp += $expense['IN_EXPENSE_PRETAX_AMOUNT'];
            $inTotalHstInitSubCorp += $expense['IN_EXPENSE_HST'];
            $inTotalTotalInitSubCorp += $expense['IN_EXPENSE_TOTAL'];
            $inTotalPercentageTotalInitSubCorp += $expense['IN_EXPENSE_PERCENTAGE_TOTAL'];
            $totalRecordsCorp++;
            break;
    }
}
// INFRA
$inInvoicePreTaxPercentageInitSubInfra = (empty($inTotalPreTaxAmountInitSubInfra) || empty($inTotalTotalInitSubInfra) || empty($inTotalPercentageTotalInitSubInfra)) ? 0 : (($inTotalPreTaxAmountInitSubInfra * $inTotalPercentageTotalInitSubInfra) / $inTotalTotalInitSubInfra);
$inInvoiceTaxTotalPercentageInitSubInfra = (empty($inTotalHstInitSubInfra) || empty($inTotalTotalInitSubInfra) || empty($inTotalPercentageTotalInitSubInfra)) ? 0 :  (($inTotalHstInitSubInfra * $inTotalPercentageTotalInitSubInfra) / $inTotalTotalInitSubInfra);
$inInvoiceTotalPercentageInitSubInfra = ($inInvoicePreTaxPercentageInitSubInfra + $inInvoiceTaxTotalPercentageInitSubInfra);
// PC
$inInvoicePreTaxPercentageInitSubPc = (empty($inTotalTotalInitSubPc) || empty($inTotalTotalInitSubPc) || empty($inTotalPercentageTotalInitSubPc)) ? 0 : (($inTotalPreTaxAmountInitSubPc * $inTotalPercentageTotalInitSubPc) / $inTotalTotalInitSubPc);
$inInvoiceTaxTotalPercentageInitSubPc = (empty($inTotalHstInitSubPc) || empty($inTotalTotalInitSubPc) || empty($inTotalPercentageTotalInitSubPc)) ? 0 :  (($inTotalHstInitSubPc * $inTotalPercentageTotalInitSubPc) / $inTotalTotalInitSubPc);
$inInvoiceTotalPercentageInitSubPc = ($inInvoicePreTaxPercentageInitSubPc + $inInvoiceTaxTotalPercentageInitSubPc);
//PE
$inInvoicePreTaxPercentageInitSubPe = (empty($inTotalTotalInitSubPe) || empty($inTotalTotalInitSubPe) || empty($inTotalPercentageTotalInitSubPe)) ? 0 : (($inTotalPreTaxAmountInitSubPe * $inTotalPercentageTotalInitSubPe) / $inTotalTotalInitSubPe);
$inInvoiceTaxTotalPercentageInitSubPe = (empty($inTotalHstInitSubPe) || empty($inTotalTotalInitSubPe) || empty($inTotalPercentageTotalInitSubPe)) ? 0 :  (($inTotalHstInitSubPe * $inTotalPercentageTotalInitSubPe) / $inTotalTotalInitSubPe);
$inInvoiceTotalPercentageInitSubPe = ($inInvoicePreTaxPercentageInitSubPe + $inInvoiceTaxTotalPercentageInitSubPe);
// Corp
$inInvoicePreTaxPercentageInitSubCorp = (empty($inTotalTotalInitSubCorp) || empty($inTotalTotalInitSubCorp) || empty($inTotalPercentageTotalInitSubCorp)) ? 0 : (($inTotalPreTaxAmountInitSubCorp * $inTotalPercentageTotalInitSubCorp) / $inTotalTotalInitSubCorp);
$inInvoiceTaxTotalPercentageInitSubCorp = (empty($inTotalHstInitSubCorp) || empty($inTotalTotalInitSubCorp) || empty($inTotalPercentageTotalInitSubCorp)) ? 0 :  (($inTotalHstInitSubCorp * $inTotalPercentageTotalInitSubCorp) / $inTotalTotalInitSubCorp);
$inInvoiceTotalPercentageInitSubCorp = ($inInvoicePreTaxPercentageInitSubCorp + $inInvoiceTaxTotalPercentageInitSubCorp);

return [
    "RESPONSE_COMMENTS" => $getResponse,
    //"IN_SUBMITTER_MANAGER" => $data['IN_SAVE_SUBMIT'] == 'SUBMIT' ? null : $data['IN_SUBMITTER_MANAGER'],
    //"IN_COMMENT_MANAGER_EDIT" => null,
    //"IN_SAVE_SUBMIT" => null,
    "IN_SUBMITTER_MANAGER_ID" => isset($data["IN_SUBMITTER_MANAGER"]["ID"]) ? $data["IN_SUBMITTER_MANAGER"]["ID"] : '',
    // Clear variables - Jhon Chacolla
    //"IN_SAVE_SUBMIT" =>  null,
    "fakeSaveCloseButton" =>  null,
    "saveButtonFake" =>  null,
    "submitButtonFake" =>  null,
    "validateForm" =>  null,
    "saveForm" =>  null,
    "saveFormClose" =>  null,
    "validation" =>  null,
    "readyScreen" => null,
    // Sub Total by Teams - Jhon Chacolla
    "IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA" => $inTotalPreTaxAmountInitSubInfra,
    "IN_TOTAL_HST_INIT_SUB_INFRA" => $inTotalHstInitSubInfra,
    "IN_TOTAL_TOTAL_INIT_SUB_INFRA" => $inTotalTotalInitSubInfra,
    "IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_INFRA" => $inTotalPercentageTotalInitSubInfra,
    "IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA" => $inInvoicePreTaxPercentageInitSubInfra,
    "IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_INFRA" => $inInvoiceTaxTotalPercentageInitSubInfra,
    "IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA" => $inInvoiceTotalPercentageInitSubInfra,

    "IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_PC" => $inTotalPreTaxAmountInitSubPc,
    "IN_TOTAL_HST_INIT_SUB_PC" => $inTotalHstInitSubPc,
    "IN_TOTAL_TOTAL_INIT_SUB_PC" => $inTotalTotalInitSubPc,
    "IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_PC" => $inTotalPercentageTotalInitSubPc,
    "IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_PC" => $inInvoicePreTaxPercentageInitSubPc,
    "IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_PC" => $inInvoiceTaxTotalPercentageInitSubPc,
    "IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_PC" => $inInvoiceTotalPercentageInitSubPc,

    "IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_PE" => $inTotalPreTaxAmountInitSubPe,
    "IN_TOTAL_HST_INIT_SUB_PE" => $inTotalHstInitSubPe,
    "IN_TOTAL_TOTAL_INIT_SUB_PE" => $inTotalTotalInitSubPe,
    "IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_PE" => $inTotalPercentageTotalInitSubPe,
    "IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_PE" => $inInvoicePreTaxPercentageInitSubPe,
    "IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_PE" => $inInvoiceTaxTotalPercentageInitSubPe,
    "IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_PE" => $inInvoiceTotalPercentageInitSubPe,

    "IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_CORP" => $inTotalPreTaxAmountInitSubCorp,
    "IN_TOTAL_HST_INIT_SUB_CORP" => $inTotalHstInitSubCorp,
    "IN_TOTAL_TOTAL_INIT_SUB_CORP" => $inTotalTotalInitSubCorp,
    "IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_CORP" => $inTotalPercentageTotalInitSubCorp,
    "IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_CORP" => $inInvoicePreTaxPercentageInitSubCorp,
    "IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_CORP" => $inInvoiceTaxTotalPercentageInitSubCorp,
    "IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_CORP" => $inInvoiceTotalPercentageInitSubCorp,

    // Total records foreach team
    "IN_TOTAL_RECORDS_INFRA" => $totalRecordsInfra,
    "IN_TOTAL_RECORDS_PC" => $totalRecordsPc,
    "IN_TOTAL_RECORDS_PE" => $totalRecordsPe,
    "IN_TOTAL_RECORDS_CORP" => $totalRecordsCorp,
];

/* Get all Expense records by request ID from EXPENSE_TABLE
 *
 * @param (String) $apiUrl
 * @param (Int) $requestId
 * @return (Array) $response
 *
 * by Jhon Chacolla
*/
function getAllExpenseByRequest ($apiUrl, $requestId) {
    $query  = "";
    $query .= "SELECT * ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' ";
    $query .= "ORDER BY IN_EXPENSE_ROW_NUMBER ASC ; ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}

/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by Favio Mollinedo
*/
function postRecordToCollection($record, $collectionID){
    try{
        $pmheaders = [
            'Authorization' => 'Bearer ' . getenv('API_TOKEN'),        
            'Accept'        => 'application/json',
        ];
        $apiHost = getenv('API_HOST');
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request("POST", $apiHost ."/collections/$collectionID/records", [
                    "headers" => $pmheaders,
                    "http_errors" => false,
                    "json" => [
                        "data" => $record
                        ]
                    ]);
        if ($res->getStatusCode() == 201){
            $response = json_decode($res->getBody(), true);
            return $response["id"];
        }
        return "Status Code " . $res->getStatusCode() . ".  Unable to Save";
        
    }
    catch(\Exception $e){
        return $e->getMessage();
    }
}

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
        $query .= "'" . $expenseRow['IN_EXPENSE_DESCRIPTION'] . "', ";
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