<?php 
/***********************************************
 *  IS.03 Pre processing
 *  
 *  By Adriana Centellas
 *
 * Modified by Favio Mollinedo
 * Modified by Ana Castillo
 **********************************************/

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

//Get options for dropdowns

$collectionName = "IN_EXPENSE_ACTIVITY";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get options for dropdowns Manager
$idGropuManagers = 41;
$dataReturn["IN_LIST_GROUP_MANAGERS"] = getUserGroupManagers($idGropuManagers, $apiUrl);

//Get the manager selected by submiter and department
$collectionName = "IN_APPROVER_DEPARTMENT";
$collectionIdDepartment = getCollectionId($collectionName, $apiUrl);
$idInitialUSer = $data['IN_DATA_INITIAL_USER']['ID'];
$dataReturn["IN_SUBMITTER_MANAGER"] = getManagerAssigned($collectionIdDepartment, $apiUrl, $idInitialUSer);

//Get the comment logs by request
$collectionNameLog = "IN_COMMENTS_LOG";
$collectionIdLog = getCollectionId($collectionNameLog, $apiUrl);
$commentLogData = getCommentsLog($collectionIdLog, $apiUrl, $requestId);
$dataReturn["IN_COMMENT_LOG"] = $commentLogData;

//$officeLocationOptions = getExpenseActivityOptions($collectionID, $apiUrl);
$officeLocationNROptions = getNonRecoverableExpenseActivityOptions($collectionID, $apiUrl);
$officeLocationROptions = getRecoverableExpenseActivityOptions($collectionID, $apiUrl);

//$dataReturn["IN_EXPENSE_ACTIVITY_VALUES"] = $officeLocationOptions;
$dataReturn["IN_EXPENSE_ACTIVITY_NON_RECOVERABLE"] = $officeLocationNROptions;
$dataReturn["IN_EXPENSE_ACTIVITY_RECOVERABLE"] = $officeLocationROptions;

//URL of PDF
//$dataReturn["IN_PDF_URL"] = getFileURLforPDF($api, $requestId, $fileId, $baseURL);

//Set Totals needed for IS.03 - Ana Castillo
//Convert values to number
$invoicePreTaxFormat = $data["IN_INVOICE_PRE_TAX"]; //Data from IS.02 screen
$invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
$invoiceTotalFormat = $data["IN_INVOICE_TOTAL"]; //From script and IS.02
$invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
$invoiceTaxTotalFormat = $data["IN_INVOICE_TAX_TOTAL"]; //From script and IS.02
$invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);
// Round amounts for IS.03 - Jhon Chacolla
$dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] = (empty($invoiceTotal) || empty($invoiceTotal)) ? 0 : (($invoicePreTax * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = (empty($invoiceTaxTotal) || empty($invoiceTotal)) ? 0 :  (($invoiceTaxTotal * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TOTAL_PERCENTAGE"] = ($dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] +  $dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"]);

$dataReturn["IN_REQUEST_ID"] = $data["_request"]["id"];

// Excel Data Records - by Favio Mollinedo
$excelData = [];
$invoiceExcelArray = !empty($data["IN_INVOICE_ARRAY"]) ? $data["IN_INVOICE_ARRAY"] : [];
foreach($invoiceExcelArray as $index => $excelData){
    //Convert values to number
    $invoicePreTaxFormat = $excelData["IN_INVOICE_PRE_TAX"];
    $invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
    $invoiceTotalFormat = $excelData["IN_EXCEL_INVOICE_TOTAL"];
    $invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
    $invoiceTaxTotalFormat = $excelData["IN_EXCEL_INVOICE_TAX_TOTAL"];
    $invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);
    // Round amounts for IS.03 - Jhon Chacolla
    $excelData["IN_INVOICE_PRE_TAX_PERCENTAGE"] = (empty($invoiceTotal) || empty($invoiceTotal)) ? 0 : (($invoicePreTax * 100) / $invoiceTotal);
    $excelData["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = (empty($invoiceTaxTotal) || empty($invoiceTotal)) ? 0 :  (($invoiceTaxTotal * 100) / $invoiceTotal);
    $excelData["IN_INVOICE_TOTAL_PERCENTAGE"] = ($excelData["IN_INVOICE_PRE_TAX_PERCENTAGE"] +  $excelData["IN_INVOICE_TAX_TOTAL_PERCENTAGE"]);
    return $excelData;
    $expenseDataArray = [
        "IN_EXPENSE_CASE_ID" => $data["_request"]["id"],
        "IN_EXPENSE_ROW_ID" => generateUuidV4(),
        "IN_EXPENSE_DESCRIPTION" => empty($data['IN_INVOICE_TRANS_COMMENTS']) ? '' : $data['IN_INVOICE_TRANS_COMMENTS'],
        "IN_EXPENSE_ROW_NUMBER" => $index+1,
        "IN_EXPENSE_TEAM_ROW_INDEX" => $index+1,
        "IN_EXPENSE_PRETAX_AMOUNT" => $invoiceTotal - $invoiceTaxTotal,
        "IN_EXPENSE_HST" => $invoiceTaxTotalFormat,
        "IN_EXPENSE_TOTAL" => $invoiceTotalFormat,
        "IN_EXPENSE_PERCENTAGE" => 100,
        "IN_EXPENSE_PERCENTAGE_TOTAL" => "100"
    ];
    if($index == 0)
        return $expenseDataArray;
    //$createExpences = createExpense($apiUrl, $expenseDataArray, $requestId);
}
return $excelData;
// Add a new record is expence table is empty costume table - Jhon Chacolla
//deleteRow($apiUrl, $requestId);
$totalRecords = checkRecordsByRequestId($apiUrl, $requestId);
return $totalRecords;
if ($totalRecords[0]['TOTAL_ROWS'] <= 0) {
    $expenseData = [
        "IN_EXPENSE_CASE_ID" => $data["_request"]["id"],
        "IN_EXPENSE_ROW_ID" => generateUuidV4(),
        "IN_EXPENSE_DESCRIPTION" => empty($data['IN_INVOICE_TRANS_COMMENTS']) ? '' : $data['IN_INVOICE_TRANS_COMMENTS'],
        "IN_EXPENSE_ROW_NUMBER" => 1,
        "IN_EXPENSE_TEAM_ROW_INDEX" => 1,
        "IN_EXPENSE_PRETAX_AMOUNT" => $invoicePreTaxFormat,
        "IN_EXPENSE_HST" => $invoiceTaxTotalFormat,
        "IN_EXPENSE_TOTAL" => $invoiceTotalFormat,
        "IN_EXPENSE_PERCENTAGE" => 100,
        "IN_EXPENSE_PERCENTAGE_TOTAL" => "100"
    ];
    $createExpences = createExpense($apiUrl, $expenseData, $requestId);
}

//Clear variables
$dataReturn["IN_SAVE_SUBMIT"] = null;
$dataReturn["fakeSaveCloseButton"] = null;
$dataReturn["saveButtonFake"] = null;
$dataReturn["submitButtonFake"] = null;
$dataReturn["validateForm"] = null;
$dataReturn["saveForm"] = null;
$dataReturn["saveFormClose"] = null;
$dataReturn["validation"] = null;

$dataReturn['IN_CASE_TITLE'] = $data['IN_INVOICE_VENDOR_LABEL'] . ' - ' . $data['IN_INVOICE_NUMBER'] . ' - ' . $data['IN_INVOICE_DATE'];

return $dataReturn;

 
/**
 * Retrieve activity options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Adriana Centellas
 */
function getExpenseActivityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.ACTIVITY_LABEL' AS LABEL
                        FROM collection_" . $ID;

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve non recoverable activity options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * adapted copy from getExpenseActivityOptions
 */
function getNonRecoverableExpenseActivityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.ACTIVITY_LABEL' AS LABEL
                        FROM collection_" . $ID . " AS EXPENSE
                        WHERE EXPENSE.data->>'$.ACTIVITY_TYPE' IN ('Non-recoverable','Both')
                        ORDER BY LABEL ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve recoverable activity options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * adapted copy from getExpenseActivityOptions
 */
function getRecoverableExpenseActivityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.ACTIVITY_LABEL' AS LABEL
                        FROM collection_" . $ID . " AS EXPENSE
                        WHERE EXPENSE.data->>'$.ACTIVITY_TYPE' IN ('Recoverable','Both')
                        ORDER BY LABEL ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Generate the full URL for a PDF file within a request.
 *
 * @param object $api - The API instance to interact with the files.
 * @param int $requestId - The ID of the request that contains the file.
 * @param int $fileId - The ID of the file to retrieve.
 * @param string $instanceURL - The base URL of the instance.
 * @return string - The full URL to access the PDF file.
 *
 * by Adriana Centellas
 */
function getFileURLforPDF($api, $requestId, $fileId, $instanceURL) 
{
    // Access the files API instance
    $apiInstance = $api->files();

    // Retrieve the file details using the file ID
    $file = $apiInstance->getFileById($fileId);

    // Extract the file name from the file object
    $filename = $file->getFileName();

    // Construct and return the full URL for the file
    return $instanceURL . "requests/" . $requestId . "/files/" . $filename;
}

/**
 * Get the managers assigned to a specific user group from the collection.
 *
 * @param int $groupId - The ID of the Managers Group.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of records from the Managers group with ID, FULLNAME, and EMAIL.
 *
 * by Manuel Monroy 
 */
function getUserGroupManagers($groupId, $apiUrl)
{
    // Check if the provided group ID is not empty
    if (!empty($groupId)) {
        // Define the SQL query to fetch user details (ID, email, and full name) from the active members of the specified group
        $queryGroupUsers = "SELECT U.id AS ID, U.email AS EMAIL, 
                                CONCAT(U.firstname, ' ', U.lastname) AS FULL_NAME
                            FROM users AS U
                            INNER JOIN group_members AS G
                                ON G.member_id = U.id
                            WHERE G.group_id = " . $groupId . " AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
                            ORDER BY U.lastname ASC";  // Order by user's last name
        
        // Execute the query using the API to fetch the group members
        $groupUsersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupUsers));
    }

    // Return the response containing the group members (managers)
    return $groupUsersResponse;
}

/*
 * Get the manager assigned to the submitter from Collection.
 *
 * @param int $ID - The ID of the collection.
 * @param int $userSubmitterId - The ID of the submitter (initial user).
 * @param string $apiUrl - The API URL used to make the request.
 * @return object - Returns the manager object from the SUBMITTER_DEPARTMENT collection assigned to the user.
 *
 * by Manuel Monroy 
*/
function getManagerAssigned($ID, $apiUrl, $userSubmitterId)
{
    // Initialize the SQL query string
    $queryCollectionDeal = '';
    
    // Select fields from the JSON in the collection, extracting the manager details
    $queryCollectionDeal .= 'SELECT ';
    $queryCollectionDeal .= 'CAST(JSON_UNQUOTE(ID.data->"$.SUBMITTER_MANAGER.id") AS UNSIGNED) AS ID, '; // Convert the manager's ID to an integer
    $queryCollectionDeal .= 'JSON_UNQUOTE(ID.data->"$.SUBMITTER_MANAGER.email") AS EMAIL, '; // Extract manager's email
    $queryCollectionDeal .= 'JSON_UNQUOTE(ID.data->"$.SUBMITTER_MANAGER.fullname") AS FULL_NAME '; // Extract manager's full name
    
    // From the specific collection based on the given ID
    $queryCollectionDeal .= 'FROM collection_' . $ID . ' AS ID ';
    
    // Apply the filter where the submitter's ID in the department matches the provided userSubmitterId
    $queryCollectionDeal .= 'WHERE JSON_UNQUOTE(ID.data->"$.SUBMITTER_DEPARTMENT.USER_SUBMITTER.id") = "' . $userSubmitterId . '"';
    $queryCollectionDeal .= ' AND JSON_UNQUOTE(ID.data->"$.SUBMITTER_DEFAULT") = "true"';

    // Make the API request and get the collection records
    // If no results are found, return an empty array
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionDeal)) ?? [];

    // Return the first record (manager details) if available, or an empty array if no records exist
    return !empty($collectionRecords) && is_array($collectionRecords) ? $collectionRecords[0] : [];
}

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
                        ORDER BY IN_COMMENT_DATE ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Insert a new record into custome EXPENCE_TABLE.
 *
 * @param string $apiUrl - The API URL for making the request.
 * @param array $expense - The data to save.
 * @param int $requestId - The ID of the request.
 * @return array - An empty array.
 *
 * by Jhon Chacolla
 * 
 */
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
    $query .= 'IN_EXPENSE_GL_CODE) ';
    $query .= 'VALUES (';
    $query .= "'" . $requestId . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ROW_ID'] . "', ";
    $query .= $expense['IN_EXPENSE_ROW_NUMBER'] . ", ";
    $query .= $expense['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
    $query .= "'" . $expense['IN_EXPENSE_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_HST'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_GL_CODE'] . "'";
    $query .= ');';
    //return $query;
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}
/**
 * Get total of rows by request ID from EXPENCE_TABLE.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - Total records.
 * by Jhon Chacolla
 * 
 */
function checkRecordsByRequestId($apiUrl, $requestId){
    $query  = "";
    $query .= "SELECT COUNT(*) AS TOTAL_ROWS ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "';";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    
    return $response;
}

/**
 * Generates a standart UUID with 32 characters
 * @return string - A UUID string
 * by Jhon Chacolla
 */
function generateUuidV4() {
    $data = random_bytes(16);
    
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); 
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); 
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function deleteRow($apiUrl, $requestId){
    $query  = "";
    $query .= "DELETE FROM EXPENSE_TABLE ";
    $query .= "WHERE EXPENSE_TABLE.IN_EXPENSE_CASE_ID = '" . $requestId ."'";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}