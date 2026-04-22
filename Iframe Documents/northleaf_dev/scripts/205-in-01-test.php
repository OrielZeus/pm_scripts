<?php 
/**********************************
 * IN - IS.03 Post Processing 
 *
 * by 
 *********************************/
require_once("/Northleaf_PHP_Library.php");
/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by 
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

function insertIntoTable($row,$allocation,$indexRow,$requestId,$type){
    global $apiUrl;
    $dealSql = "IN_EXPENSE_PROJECT_DEAL_ID, IN_EXPENSE_PROJECT_DEAL_LABEL, ";
    $fundSql = "IN_EXPENSE_FUND_MANAGER_ID, IN_EXPENSE_FUND_MANAGER_LABEL, ";
    $mandSql =  "'" . $allocation['MANDATE_ID'] . "' AS IN_EXPENSE_MANDATE_ID, '" . $allocation['MANDATE_NAME'] . "' AS IN_EXPENSE_MANDATE_LABEL, ";
    if($type == 'DEAL_ALLOCATION'){
        $fundSql =  "'" . $allocation['FUND_MANAGER_ID'] . "' AS IN_EXPENSE_FUND_MANAGER_ID, '" . $allocation['FUND_MANAGER'] . "' AS IN_EXPENSE_FUND_MANAGER_LABEL, ";
    }
    else{
        $dealSql =  "'" . $allocation['DEAL_ID'] . "' AS IN_EXPENSE_PROJECT_DEAL_ID, '" . $allocation['DEAL'] . "' AS IN_EXPENSE_PROJECT_DEAL_LABEL, ";
    }
    $newIdex                        = $row['IN_EXPENSE_ROW_NUMBER'] . "." . $indexRow;
    $newIN_EXPENSE_PRETAX_AMOUNT    = $row['IN_EXPENSE_PRETAX_AMOUNT'] * $allocation[$type];
    $newIN_EXPENSE_HST              = $row['IN_EXPENSE_HST'] * $allocation[$type];
    $newIN_EXPENSE_TOTAL            = $row['IN_EXPENSE_TOTAL'] * $allocation[$type];
    $newIN_EXPENSE_PERCENTAGE_TOTAL = $allocation[$type] * 100;//($allocation[$type] * 100) * $row['IN_EXPENSE_PERCENTAGE_TOTAL'] / 100;
    $query = "INSERT INTO EXPENSE_TABLE (
                IN_EXPENSE_CASE_ID, 
                IN_EXPENSE_ROW_ID, 
                IN_EXPENSE_ROW_NUMBER, 
                IN_EXPENSE_TEAM_ROW_INDEX, 
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
                IN_EXPENSE_VALIDATION ) 
                SELECT " . $requestId . " AS IN_EXPENSE_CASE_ID, 
                '" . $row['IN_EXPENSE_ROW_ID'].$newIdex ."' AS IN_EXPENSE_ROW_ID, 
                " . $newIdex ." AS IN_EXPENSE_ROW_NUMBER, 
                IN_EXPENSE_TEAM_ROW_INDEX, 
                IN_EXPENSE_DESCRIPTION, 
                IN_EXPENSE_ACCOUNT_ID, 
                IN_EXPENSE_ACCOUNT_LABEL, 
                IN_EXPENSE_CORP_PROJ_ID, 
                IN_EXPENSE_CORP_PROJ_LABEL, 
                " . $newIN_EXPENSE_PRETAX_AMOUNT . " AS IN_EXPENSE_PRETAX_AMOUNT, 
                " . $newIN_EXPENSE_HST . " AS IN_EXPENSE_HST, 
                " . $newIN_EXPENSE_TOTAL . " AS IN_EXPENSE_TOTAL, 
                IN_EXPENSE_PERCENTAGE, 
                " . $newIN_EXPENSE_PERCENTAGE_TOTAL. " AS IN_EXPENSE_PERCENTAGE_TOTAL, 
                IN_EXPENSE_NR_ID, 
                IN_EXPENSE_NR_LABEL, 
                IN_EXPENSE_TEAM_ROUTING_ID, 
                IN_EXPENSE_TEAM_ROUTING_LABEL, " . $dealSql . $fundSql . $mandSql ." IN_EXPENSE_ACTIVITY_ID, 
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
                IN_EXPENSE_VALIDATION
                FROM EXPENSE_TABLE
                WHERE IN_EXPENSE_CASE_ID = '" . $requestId .  "'
                AND IN_EXPENSE_ROW_NUMBER = '" . $row['IN_EXPENSE_ROW_NUMBER'] . "'";
    return callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
}


//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

/*
//Get collections IDs
$collectionName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get role from task
$userRole = isset($data["submittorRoleInfo"]) && $data["submittorRoleInfo"] !== null 
    ? $data["submittorRoleInfo"] 
    : "Submitter";

//Save comments into collection
$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => $data["currentUser"]["fullname"],
    "IN_CL_USER_ID" => $data["currentUser"]["id"],
    "IN_CL_ROLE" => $userRole,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => null,
    "IN_CL_COMMENT_SAVED" => empty($data['IN_COMMENT']) ? "" : $data['IN_COMMENT'],
    "IN_CL_DATE" => date("m/d/Y"),
    "IN_SUBMIT" => null,
];
//$getResponse = postRecordToCollection($dataRecord, $collectionID);
*/

/*'INFRA'
'PC'
'PE'
'CORP'*/
$teamID = $config['teamID'];//'PE';

// Update Sub Totals
$expenseByRequest = getAllExpenseByRequest($apiUrl, $data['_request']['id'],$teamID);
foreach($expenseByRequest as $row){
    if(isset($row['IN_EXPENSE_MANDATE_ID']) AND $row['IN_EXPENSE_MANDATE_ID'] == ''){
        if(isset($row['IN_EXPENSE_PROJECT_DEAL_ID']) AND $row['IN_EXPENSE_PROJECT_DEAL_ID'] != ''){
            $dealAllocationData = gatDataFromExpenseAllocation('DEAL_ID',$row['IN_EXPENSE_PROJECT_DEAL_ID']);
            if(sizeof($dealAllocationData) > 0){
                $indexRow = 1;
                foreach($dealAllocationData as $allocation){
                    insertIntoTable($row,$allocation,$indexRow,$data['_request']['id'],'DEAL_ALLOCATION'); 
                    $indexRow++;
                }
                $queryAllocation = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '".$data['_request']['id']."' AND IN_EXPENSE_ROW_NUMBER = '" . $row['IN_EXPENSE_ROW_NUMBER'] . "'";
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
            if(isset($row['IN_EXPENSE_FUND_MANAGER_ID']) AND $row['IN_EXPENSE_FUND_MANAGER_ID'] != ''){
                $fundManagerAllocationData = gatDataFromExpenseAllocation('FUND_MANAGER_ID',$row['IN_EXPENSE_FUND_MANAGER_ID']);
                if(sizeof($fundManagerAllocationData)  > 0){
                    $indexRowFund = 1;
                    foreach($fundManagerAllocationData as $allocation){
                        insertIntoTable($row,$allocation,$indexRowFund,$data['_request']['id'],'FUND_MANAGER_ALLOCATION');
                        $indexRowFund++;
                    }
                    $queryAllocation = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '".$data['_request']['id']."' AND IN_EXPENSE_ROW_NUMBER = '" . $row['IN_EXPENSE_ROW_NUMBER'] . "'";
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
    }
    else{
        $sqlUpdate = "UPDATE EXPENSE_TABLE
                        SET IN_EXPENSE_PERCENTAGE_TOTAL = 100
                        WHERE IN_EXPENSE_ROW_ID = '" . $row['IN_EXPENSE_ROW_ID'] . "'";
        $allocationUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdate));
    }
}



/*
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
*/
$dataToSend = (isset($data['ALL_DATA_GRID_TO_BACK'])) ? $data['ALL_DATA_GRID_TO_BACK'] : [];
$dataToSend[$teamID] = (isset($data['ALL_DATA_GRID_TO_BACK']) AND isset($data['ALL_DATA_GRID_TO_BACK'][$teamID])) ? $data['ALL_DATA_GRID_TO_BACK'][$teamID] : base64_encode( json_encode( $expenseByRequest ) );
return [
    "ALL_DATA_GRID_TO_BACK" => $dataToSend,
    "Allocated_".$teamID => false
];

return [
    "RESPONSE_COMMENTS" => $getResponse,
    "IN_SUBMITTER_MANAGER_ACTION" => null,
    "IN_COMMENT" => null,
    //"IN_SAVE_SUBMIT" => null,
    "IN_SUBMITTER_MANAGER_ID" => isset($data["IN_SUBMITTER_MANAGER"]["ID"]) ? $data["IN_SUBMITTER_MANAGER"]["ID"] : '',
    // Clear variables - Jhon Chacolla
    "IN_SAVE_SUBMIT" =>  null,
    "fakeSaveCloseButton" =>  null,
    "saveButtonFake" =>  null,
    "submitButtonFake" =>  null,
    "validateForm" =>  null,
    "saveForm" =>  null,
    "saveFormClose" =>  null,
    "validation" =>  null,
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
    "ALL_DATA_GRID_TO_BACK" => base64_encode( json_encode( $expenseByRequest ) )

];

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

return [
    "IN_SAVE_SUBMIT" => null,
    "IN_EDIT_SUBMIT" => null,
    "IN_SUBMITTER_MANAGER_ID" => isset($data["IN_SUBMITTER_MANAGER"]["ID"]) ? $data["IN_SUBMITTER_MANAGER"]["ID"] : ''
];