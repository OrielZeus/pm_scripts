<?php 

/***********************************************
 *  IN - Clean Request to Reupload
 *  
 *  By Jhon Chacolla
 **********************************************/

require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$baseURL = getenv('ENVIRONMENT_BASE_URL');
$requestId = $data["_request"]["id"];
// Remove rows from the EXPENSE_TABLE
deleteRow($apiUrl, $requestId);

return [
    "IN_LIST_GROUP_MANAGERS"=> null,
    "IN_SUBMITTER_MANAGER" => null,
    "IN_COMMENT_LOG" => null,

    "IN_EXPENSE_ACTIVITY_NON_RECOVERABLE" => null,
    "IN_EXPENSE_ACTIVITY_RECOVERABLE" => null,

    // Round amounts for IS.03 - Jhon Chacolla
    "IN_INVOICE_PRE_TAX_PERCENTAGE" => null,
    "IN_INVOICE_TAX_TOTAL_PERCENTAGE" => null,
    "IN_INVOICE_TOTAL_PERCENTAGE" => null,

    //Clear variables
    "IN_SAVE_SUBMIT" => null,
    "fakeSaveCloseButton" => null,
    "saveButtonFake" => null,
    "submitButtonFake" => null,
    "validateForm" => null,
    "saveForm" => null,
    "saveFormClose" => null,
    "validation" => null,
    // Clean Action Variables  DHS.01
    "IN_SUBMITTER_MANAGER_ACTION" => null,
    "IN_COMMENT_SUBMITTER" => null,
    // Clean Action Variables  DHS.02
    "IN_SUBMITTER_MANAGER_EDIT_ACTION" => null,
    "IN_COMMENT_MANAGER_EDIT" => null,
    "IN_RESPONSE_NEW_VENDOR" => null,
    "IN_INVOICE_VENDOR_LABEL" => null,
    "IN_CHECK_REQUIREDS" => null,
    "IN_INVOICE_VENDOR_ADDRESS" => null,

    "IN_INVOICE_DISCREPANCY" => null,

    //"IN_CASE_TITLE" => null,

    // Excel data
    //"IN_UPLOAD_EXCEL" => null,
    "IN_INVOICE_ARRAY" => null,
    "IN_INVOICE_ROW_NUMBER" => 0,

    // PDF data
    //"IN_UPLOAD_PDF" => null,

    // Custome table data
    "IN_DATA_GRID" => null,
    "IN_EXPENSE_DELETED_ROWS" => null,
    "IN_TOTAL_PRE_TAX_AMOUNT" => 0,
    "IN_TOTAL_HST" => 0,
    "IN_TOTAL_PERCENTAGE_TOTAL" => 0,
    "IN_OUTSTANDING_TOTAL" => 0,
    "IN_OUTSTANDING_PERCENTAGE" => 0,
    "IN_CHECK_FIELDS" => null,
    "IN_INVOICE_VENDOR" => null,
    "IN_INVOICE_VENDOR_ADDRESS" => null,
    "IN_INVOICE_VENDOR_ID" => null,
    "IN_INVOICE_DATE" => null,
    "IN_INVOICE_NUMBER" => null,
    "IN_INVOICE_CURRENCY" => null,
    "IN_INVOICE_TRANS_COMMENTS" => null,
    "IN_CASE_REFERENCE" => null,

];

/**
 * Generates a standart UUID with 32 characters
 * @return string - A UUID string
 * by Jhon Chacolla
 */
function deleteRow($apiUrl, $requestId){
    $query  = "";
    $query .= "DELETE FROM EXPENSE_TABLE ";
    $query .= "WHERE EXPENSE_TABLE.IN_EXPENSE_CASE_ID = '" . $requestId . "' ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}