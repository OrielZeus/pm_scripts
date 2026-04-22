<?php 
/*  
 *  this script revert data of Expense data, if an error ocurred into IN - return case to IS03 script
 *  By Daniel Aguilar
 */

// Load custom PHP library used in this project
require_once("/Northleaf_PHP_Library.php");

// Get environment variables used to build the API endpoint
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

// Array that will store the response returned to ProcessMaker
$dataReturn = [];

// Get the current request/case ID from ProcessMaker
$requestId = $data['_request']['id'];


// ------------------------------------------------------
// Decode first Data Grid (enabled rows)
// ------------------------------------------------------

// Decode HTML entities from the grid data
$dataGrid = html_entity_decode($data['IN_DATA_GRID_1_CO']);

// Decode base64 encoded grid content
$textBase = base64_decode($dataGrid);

// Convert encoding to UTF-8
$newText = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());

// Convert JSON text into PHP array
$expenseData = json_decode($newText,true);


// ------------------------------------------------------
// Decode second Data Grid (disabled rows)
// ------------------------------------------------------

$dataGrid = html_entity_decode($data['IN_DATA_GRID_2_CO']);
$textBase = base64_decode($dataGrid);
$newText = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
$expenseDataDisabled = json_decode($newText,true);


// ------------------------------------------------------
// Delete existing expense records for this case
// ------------------------------------------------------

$query = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));


// ------------------------------------------------------
// Rebuild expense list before inserting again
// ------------------------------------------------------

$created = [];
$mainIdex = 0;

// Process disabled expenses first (ordered by team routing)
if(!empty($expenseDataDisabled)){

    // Process team: PE
    foreach ($expenseDataDisabled as $key => $expense){
        if($expense['IN_EXPENSE_TEAM_ROUTING']['ID'] == 'PE'){
            $mainIdex++;

            // Escape special characters in description
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);

            // Assign row numbers
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;

            // Add to array of expenses to be inserted
            $created[] = $expense;
        }
    }

    // Process team: PC
    foreach ($expenseDataDisabled as $key => $expense){
        if($expense['IN_EXPENSE_TEAM_ROUTING']['ID'] == 'PC'){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            $created[] = $expense;
        }
    }

    // Process team: INFRA
    foreach ($expenseDataDisabled as $key => $expense){
        if($expense['IN_EXPENSE_TEAM_ROUTING']['ID'] == 'INFRA'){
            $mainIdex++;
            $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;
            $created[] = $expense;
        }
    }
}


// ------------------------------------------------------
// Process enabled expenses
// ------------------------------------------------------

if(!empty($expenseData)){
    foreach ($expenseData as $key => $expense){

        $mainIdex++;

        // Escape description text
        $expense['IN_EXPENSE_DESCRIPTION'] = addslashes($expense['IN_EXPENSE_DESCRIPTION']);

        // Assign row numbers
        $expense['IN_EXPENSE_ROW_NUMBER'] = $mainIdex;
        $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $mainIdex;

        // Add to final array
        $created[] = $expense;
    }
}


// ------------------------------------------------------
// Insert expenses in batches of 30 rows
// ------------------------------------------------------

$created = array_chunk($created, 30);

foreach($created as $batch){

    // Generate SQL insert query for this batch
    $createExpences = preparateExpense($batch, $requestId);

    // Execute query through API
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
}


// ------------------------------------------------------
// Reset some form fields in ProcessMaker after saving
// ------------------------------------------------------

$dataReturn['readyScreen'] = null;
$dataReturn['showErrorCO'] = true;
$dataReturn['RETURN_CASE'] = null;
$dataReturn['CO_comments_return'] = null;

// Return values to ProcessMaker
return $dataReturn;


/* 
 * Function: preparateExpense
 * Purpose: Build a bulk SQL INSERT statement for expenses
 *
 * @param (Array) $expense   List of expense rows
 * @param (Int) $requestId   Current ProcessMaker case ID
 * @return (String)          SQL INSERT query
 *
 * Author: Daniel Aguilar
 */
function preparateExpense($expense, $requestId) {

    $query  = '';

    // Define table columns
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
    $query .= 'IN_EXPENSE_VALIDATION, ';
    $query .= 'IN_EXPENSE_GL_CODE) ';

    $query .= 'VALUES ';

    // Build VALUES section dynamically
    foreach($expense as $expenseRow){

        $query .= '(';

        // Case ID
        $query .= "'" . $requestId . "', ";

        // Expense row identifiers
        $query .= "'" . $expenseRow['IN_EXPENSE_ROW_ID'] . "', ";
        $query .= $expenseRow['IN_EXPENSE_ROW_NUMBER'] . ", ";
        $query .= $expenseRow['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";

        // Description
        $query .= "'" . ($expenseRow['IN_EXPENSE_DESCRIPTION'] ? addslashes($expenseRow['IN_EXPENSE_DESCRIPTION']) : '') . "', ";

        // Continue mapping all fields from grid row to database columns
        // (Many use null-coalescing operator to avoid undefined errors)

        $query .= "'" . ($expenseRow['IN_EXPENSE_ACCOUNT']['ID'] ?? '')  . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) : '')  . "', ";

        // ... (rest of fields follow the same pattern)

        $query .= "'" . ($expenseRow['IN_EXPENSE_GL_CODE'] ?? '') . "'";

        $query .= '),';
    }

    // Remove last comma and finalize query
    $query = substr($query, 0, -1).';';

    return $query;
}