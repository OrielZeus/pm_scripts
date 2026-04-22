<?php 
/*  
 *  this script send mail, and store data into collection and revert data stored into IS.03 task
 *  By Daniel Aguilar
 */

require_once("/Northleaf_PHP_Library.php");

$datetime = new DateTime();
// Set the timezone to America/New_York (which covers EST/EDT)
$timezone = new DateTimeZone(getenv('IN_TIME_ZONE'));
$datetime->setTimezone($timezone);
// Format and display the date and time
$currentDate = $datetime->format('Y-m-d');


// --- Get global environment variables for API connection ---
$apiHost = getenv('API_HOST');   // Host for API
$apiSql  = getenv('API_SQL');    // SQL endpoint
$apiUrl  = $apiHost . $apiSql;   // Full API URL

$dataReturn = []; // Initialize array to hold results

// --- Send notification email ---
$task      = 'RETURN_IS03';      // Task identifier
$emailType = '';                 // Email type (empty here)
sendInvoiceNotification($data, $task, $emailType, $api);
// --- End send mail ---

// --- Retrieve collection IDs ---
$sql = "SELECT id,name
        FROM collections
        WHERE name in ('IN_RETURN_CASE_LOG','IN_COMMENTS_LOG')";
$res       = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
$arrCollId = array_column($res, 'id', 'name'); // Map collection names to IDs

// --- Save comments into collection ---
$userInfo   = ($data['IN_USER_RETURNED_TO_IS03'] ?? []); // Get user info if available
$dataRecord = [
    "IN_RC_DATE"          => $currentDate,       // Current date/time
    "IN_RC_USER"          => ($userInfo['fullname'] ?? ''), // User full name
    "IN_RC_USER_ID"       => $userInfo['id'] ?? '',     // User ID
    "IN_RC_REQUEST_ID"    => $data['_request']['id'],   // Request ID
    "IN_RC_CASE_NUMBER"   => $data['_request']['case_number'], // Case number
    "IN_RC_COMMENT_SAVED" => ($data['CO_comments_return'] ?? '') // Saved comment
];
$getResponse = postRecordToCollection($dataRecord, $arrCollId['IN_RETURN_CASE_LOG']);

$role = ($data['newStatus'] == "corporateH") ? "Corporate Finance Reviewer" : "Corporate Finance Approver";
$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => ($userInfo['fullname'] ?? ''), // User full name
    "IN_CL_USER_ID" => $userInfo['id'] ?? '',     // User ID
    "IN_CL_ROLE" => $role,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => 'Returned',
    "IN_CL_COMMENT_SAVED" => ($data['CO_comments_return'] ?? ''),
    "IN_CL_DATE" => $currentDate,       // Current date/time
    "IN_SUBMIT" => null
];
$getResponse    = postRecordToCollection($dataRecord, $arrCollId['IN_COMMENTS_LOG']);


// --- End Save comments ---

// --- Handle expense table updates if case is RETURN ---
$requestId = $data['_request']['id'];
if ($data['RETURN_CASE'] == "RETURN") {
    if (!empty($data['IN_DATA_GRID'])) {
        // Delete existing expenses for this request
        $query    = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
        $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

        // Decode and parse new expense data
        $indexTa     = 0;
        $dataGrid    = html_entity_decode($data['IN_DATA_GRID']); // Decode HTML entities
        $textBase    = base64_decode($dataGrid);                  // Decode base64
        $newText     = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings()); // Ensure UTF-8
        $expenseData = json_decode($newText, true);               // Parse JSON

        $created = [];
        foreach ($expenseData as $expense) {
            $indexTa++;
            // Escape description to avoid SQL issues
            $expense['IN_EXPENSE_DESCRIPTION']   = addslashes($expense['IN_EXPENSE_DESCRIPTION']);
            // Add row index values
            $expense['IN_EXPENSE_ROW_NUMBER']    = $indexTa;
            $expense['IN_EXPENSE_TEAM_ROW_INDEX'] = $indexTa;
            $created[] = $expense;
        }

        // Insert expenses in batches of 30
        $created = array_chunk($created, 30);
        foreach ($created as $batch) {
            $createExpences = preparateExpense($batch, $requestId);
            $response       = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
        }
    }
}

// --- Get list of managers (hardcoded group ID) ---
$idGropuManagers = 41; // HARDCODED group ID
$queryGroupUsers = "SELECT U.id AS ID, U.email AS EMAIL,  
                    CONCAT(U.firstname, ' ', U.lastname) AS FULL_NAME
                    FROM users AS U
                    INNER JOIN group_members AS G ON G.member_id = U.id
                    WHERE G.group_id = " . $idGropuManagers . " 
                      AND U.status = 'ACTIVE' 
                      AND U.deleted_at IS NULL
                    ORDER BY U.firstname ASC"; // Order by first name
$dataReturn["IN_LIST_GROUP_MANAGERS"] = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupUsers));

// --- Calculate invoice percentages ---
$invoicePreTaxFormat = $data["IN_INVOICE_PRE_TAX"];
$invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
$invoiceTotalFormat = $data["IN_INVOICE_TOTAL"];
$invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
$invoiceTaxTotalFormat = $data["IN_INVOICE_TAX_TOTAL"];
$invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);

$dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"]   = (empty($invoiceTotal)) ? 0 : (($invoicePreTax * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = (empty($invoiceTaxTotal) || empty($invoiceTotal)) ? 0 : (($invoiceTaxTotal * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TOTAL_PERCENTAGE"]     = $dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] + $dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"];
$dataReturn["IN_REQUEST_ID"]                   = $data["_request"]["id"];
$dataReturn["showErrorCO"]                     = null;
$dataReturn['IN_COMMENT_COH']                  = null;
$dataReturn['IN_COMMENT_CO']                   = null;


// --- Get user permissions ---
// Retrieves the department IDs associated with a given submitter.
// Author: Adriana Centellas
$currentUserID = $data["IN_CURRENT_USER"]["id"]; // Current user ID

// SQL query: joins submitter department collection with expense department collection
// Extracts the CAN_BYPASS flag for the submitter’s department
$querySubmitterDepartments = "SELECT  
                            JSON_UNQUOTE(JSON_EXTRACT(D.data, '$.CAN_BYPASS')) AS CAN_BYPASS  
                            FROM collection_".$arrCollId['IN_SUBMITTER_DEPARTMENT']." SD  
                            INNER JOIN collection_".$arrCollId['IN_EXPENSE_DEPARTMENT']." D  
                            ON JSON_UNQUOTE(JSON_EXTRACT(SD.data, '$.SUBMITTER_DEPARTMENT.id')) = D.id  
                            WHERE JSON_UNQUOTE(JSON_EXTRACT(SD.data, '$.SUBMITTER.id')) = ".$currentUserID ."  
                            GROUP BY JSON_UNQUOTE(JSON_EXTRACT(D.data, '$.CAN_BYPASS'))";

// Execute query via API
$groupSubmitterDepartments = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySubmitterDepartments));

// Extract CAN_BYPASS value if available
$submitterDepartment = isset($groupSubmitterDepartments[0]) ? $groupSubmitterDepartments[0]->CAN_BYPASS : '';


// --- Get the comment logs by request ---
// SQL query: retrieves comment log entries for the given request ID
$sQCollectionsId = "SELECT LOG.data->>'$.IN_CL_REQUEST_ID' AS REQUEST_ID,
                    LOG.data->>'$.IN_CL_USER' AS IN_COMMENT_USER,
                    LOG.data->>'$.IN_CL_USER_ID' AS IN_COMMENT_USER_ID,
                    LOG.data->>'$.IN_CL_ROLE' AS IN_COMMENT_ROLE,
                    IFNULL(NULLIF(LOG.data->>'$.IN_CL_APPROVAL', 'null'), '') AS IN_COMMENT_APPROVAL,
                    IFNULL(NULLIF(LOG.data->>'$.IN_CL_COMMENT_SAVED', 'null'), '') AS IN_COMMENT_SAVED,
                    LOG.data->>'$.IN_CL_DATE' AS IN_COMMENT_DATE
                    FROM collection_" . $arrCollId['IN_COMMENTS_LOG'] . " AS LOG
                    WHERE LOG.data->>'$.IN_CL_REQUEST_ID' = " . $requestId . "  
                    ORDER BY id";

// Execute query and store comment log data
$commentLogData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId));
$dataReturn["IN_COMMENT_LOG"] = $commentLogData;


// --- Clear temporary variables (reset UI state) ---
$dataReturn["IN_SAVE_SUBMIT"]         = null;
$dataReturn["fakeSaveCloseButton"]    = null;
$dataReturn["saveButtonFake"]         = null;
$dataReturn["submitButtonFake"]       = null;
$dataReturn["validateForm"]           = null;
$dataReturn["saveForm"]               = null;
$dataReturn["saveFormClose"]          = null;
$dataReturn["validation"]             = null;
$dataReturn['readyScreen']            = null;
$dataReturn['CO_comments_return']     = null;
$dataReturn['IN_DATA_GRID_SUB_INFRA'] = null;
$dataReturn['IN_DATA_GRID_SUB_PE']    = null;
$dataReturn['IN_DATA_GRID_SUB_PC']    = null;
$dataReturn['RETURN_CASE']           = null;



// --- Clean Action Variables DHS.01 ---
$dataReturn["IN_SUBMITTER_MANAGER_ACTION"] = null;
$dataReturn["IN_COMMENT_SUBMITTER"]        = null;

// --- Clean Action Variables DHS.02 ---
$dataReturn["IN_SUBMITTER_MANAGER_EDIT_ACTION"] = null;
$dataReturn["IN_COMMENT_MANAGER_EDIT"]          = null;

// Calculate invoice discrepancy (10% of total / 100)
$dataReturn['IN_INVOICE_DISCREPANCY'] = empty($data['IN_INVOICE_TOTAL']) ? 0 : round((($data['IN_INVOICE_TOTAL'] * 0.1) / 100), 2);

// Store CAN_BYPASS flag
//$dataReturn['CAN_BYPASS'] = $submitterDepartment;


// --- Flags for UI behavior ---
$dataReturn['IN_FLAG_IS02']         = false; // Disable IS.02 flag
$dataReturn['IN_SHOW_EXCEL_NUMBER'] = false; // Hide Excel row numbers


// --- Build HTML table for additional files ---
$tableForAdditionalFiles = "";
if (isset($data["IN_ADDITIONAL_FILES"]) && is_array($data["IN_ADDITIONAL_FILES"])) {
    $apiHost = getenv('API_HOST');
    $tableForAdditionalFiles = '<table width="100%" class="table">';
    $request = ProcessRequest::where("id", $data['_request']['id'])->first();

    foreach ($data["IN_ADDITIONAL_FILES"] as &$item) {
        // If file URL not set, retrieve from Media table
        if (!isset($item["url"])) {
            $reqIdFile   = Media::where("id", $item['file'])->first();
            $item["url"] = $reqIdFile['original_url'];
        }

        $fileLink = $item["url"];
        $fileInfo = pathinfo($fileLink);

        // Build table row with download link
        $tableForAdditionalFiles .= '<tr><td>';
        $tableForAdditionalFiles .= '<a href="' . $fileLink . '" download="' . getLastSegment($fileLink) . '"><i class="fas fa-file-download"></i> <span style="text-decoration: underline;"> Download</span></a>';
        $tableForAdditionalFiles .= '</td><td>' . getLastSegment($fileLink) . '</td><td>';

        // If file is PDF, add preview button
        if (isset($fileInfo['extension']) && strtoupper($fileInfo['extension']) == 'PDF') {
            $tableForAdditionalFiles .= '<p><a class="btn btn-primary" draggable="false" href="' . $fileLink . '" target="_blank" rel="noopener">Preview file</a></p>';
        }

        $tableForAdditionalFiles .= '</td></tr>';
    }
    unset($item);
    $tableForAdditionalFiles .= "</table>";
}
$dataReturn["IN_ADDITIONAL_FILES_TABLE"] = $tableForAdditionalFiles;


// --- Get user’s assigned groups ---
$userId = $data["IN_CURRENT_USER"]["id"];
$getUsersByGroup = "SELECT g.name_ AS group_name
                    FROM users u
                    INNER JOIN group_members gm ON gm.member_id = u.id
                    INNER JOIN dlv_groups g ON g.id_ = gm.group_id
                    WHERE g.name_ IN ('IN_Corporate Finance','IN_Infra Ops','IN_PC Ops','IN_PE Ops')
                      AND u.id = " . $userId;

$usersGroups = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getUsersByGroup));
$dataReturn['userAssignedGroups'] = $usersGroups;

// --- Return final data array ---
return $dataReturn;



/* Call Processmaker API with Guzzle
 *
 * @param (Array) $expense
 * @param (Int) $requestId
 * @return (String) $query
 *
 * by  Daniel aguilar
 */
function preparateExpense($expense, $requestId) {
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
    $query .= 'IN_EXPENSE_VALIDATION, ';
    $query .= 'IN_EXPENSE_GL_CODE) ';
    
    $query .= 'VALUES ';
    
    foreach($expense as $expenseRow){
        $query .= '(';
        $query .= "'" . $requestId . "', ";
        $query .= "'" . $expenseRow['IN_EXPENSE_ROW_ID'] . "', ";
        $query .= $expenseRow['IN_EXPENSE_ROW_NUMBER'] . ", ";
        $query .= $expenseRow['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_DESCRIPTION'] ? addslashes($expenseRow['IN_EXPENSE_DESCRIPTION']) : '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_ACCOUNT']['ID'] ?? '')  . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_ACCOUNT']['LABEL']) : '')  . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_CORP_PROJ']['ID'] ?? '')  . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_CORP_PROJ']['LABEL']) : '')  . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_PRETAX_AMOUNT'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_HST'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_TOTAL'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_PERCENTAGE'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_PERCENTAGE_TOTAL'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_NR'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_NR'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_TEAM_ROUTING']['ID'] ?? '')  . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_TEAM_ROUTING']['LABEL']) : '')  . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_PROJECT_DEAL']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_PROJECT_DEAL']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_FUND_MANAGER']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_FUND_MANAGER']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_MANDATE']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_MANDATE']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_MANDATE']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_ACTIVITY']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_ACTIVITY']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_ACTIVITY']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_CORP_ENTITY']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_CORP_ENTITY']['LABEL']) : '' ). "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_TRANSACTION_COMMENTS']) ? addslashes($expenseRow['IN_EXPENSE_TRANSACTION_COMMENTS']) : '' ). "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_OFFICE']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_OFFICE']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_OFFICE']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_DEPARTMENT']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_DEPARTMENT']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_COMPANY']['ID'] ?? '' ) . "', ";
        $query .= "'" . (isset($expenseRow['IN_EXPENSE_COMPANY']['LABEL']) ? addslashes($expenseRow['IN_EXPENSE_COMPANY']['LABEL']) : '' ) . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_VALIDATION'] ?? '') . "', ";
        $query .= "'" . ($expenseRow['IN_EXPENSE_GL_CODE'] ?? '') . "'";
        $query .= '),';
    }
    $query = substr($query, 0, -1).';';
    return $query;
}


/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by Daniel Aguilar
 */
function postRecordToCollection($record, $collectionID)
{
    try {
        $pmheaders = [
            'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
            'Accept' => 'application/json',
        ];
        $apiHost = getenv('API_HOST');
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request("POST", $apiHost . "/collections/$collectionID/records", [
            "headers" => $pmheaders,
            "http_errors" => false,
            "json" => [
                "data" => $record
            ]
        ]);
        if ($res->getStatusCode() == 201) {
            $response = json_decode($res->getBody(), true);
            return $response["id"];
        }
        return "Status Code " . $res->getStatusCode() . ".  Unable to Save";

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}

/**
 * Retrieves the last segment of a given URL.
 * 
 * @param string $url The URL from which to extract the last segment.
 * @return string The last segment of the URL, or an empty string if the URL is invalid.
 * By Daniel Aguilar
 */
function getLastSegment($url) 
{
    // Ensure the URL is a valid string
    if (!is_string($url) || empty($url)) {
        return ''; // Return an empty string for invalid input
    }

    // Remove trailing slash, if any
    $url = rtrim($url, '/');

    // Get the last segment after the final "/"
    return basename($url);
}