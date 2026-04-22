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

$teamID    = $config['teamID'];
$requestId = $data['_request']['id'];

//save data grid from IN_DATA_GRID_02_
$requestId   = $data['_request']['id'];
$teamID      = $config['teamID']; //'PE, INFRA, PC';
$dataGrid    = html_entity_decode($data['IN_DATA_GRID_02_' . $teamID]);
$textBase    = base64_decode($dataGrid);
$newText     = mb_convert_encoding($textBase, 'UTF-8', mb_list_encodings());
$expenseData = json_decode($newText,true);



$sqlDeleteTeam = "DELETE FROM EXPENSE_TABLE WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "' AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $teamID . "'";
$resDeleteTeam = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlDeleteTeam));

$created = array_chunk($expenseData, 30);
foreach ($created as $batch) {
    $createExpences = preparateExpense($batch, $requestId);
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($createExpences));
}
//end save data grid


$userRole = $teamID . " Ops Reviewer";
$comments = empty($data['IN_COMMENT_' . $teamID]) ? "" : $data['IN_COMMENT_' . $teamID];

//Save comments into collection
$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => $data["currentUser_" . $teamID]["fullname"],
    "IN_CL_USER_ID" => $data["currentUser_" . $teamID]["id"],
    "IN_CL_ROLE" => $userRole,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => null,
    "IN_CL_COMMENT_SAVED" => $comments,
    "IN_CL_DATE" => $currentDate,
    "IN_SUBMIT" => null,
];

$dataCommentTeam = [
    'REQUEST_ID' => $data['_request']['id'],
    'IN_COMMENT_USER' => $data["currentUser_" . $teamID]["fullname"],
    'IN_COMMENT_USER_ID' => $data["currentUser_" . $teamID]["id"],
    'IN_COMMENT_ROLE' => $userRole,
    'IN_COMMENT_APPROVAL' => null,
    'IN_COMMENT_SAVED' => $comments,
    'IN_COMMENT_DATE' => substr($currentDate, 0, 10)
];

$pmheaders = [
'Authorization' => 'Bearer ' . getenv('API_TOKEN'),        
'Accept'        => 'application/json',
];
$apiHost = getenv('API_HOST');
$client = new GuzzleHttp\Client(['verify' => false]);

$promise = $client->request("POST", $apiHost . "/collections/" . getCollectionId("IN_COMMENTS_LOG", $apiUrl) . "/records", [
    "headers" => $pmheaders,
    "http_errors" => false,
    "json" => [
        "data" => $dataRecord
        ]
    ]);
$getResponse = "Status Code " . $promise->getStatusCode() . " .  Unable to Save";
$tempFlag = $promise->getStatusCode();
if ( $tempFlag == 201) {
    $response = json_decode($promise->getBody(), true);
    $getResponse = $response["id"];
    $commentLogData = getCommentsLog(getCollectionId("IN_COMMENTS_LOG", $apiUrl), $apiUrl, $requestId);
}



//$getResponse    = postRecordToCollection($dataRecord, getCollectionId("IN_COMMENTS_LOG", $apiUrl));
//$commentLogData = getCommentsLog(getCollectionId("IN_COMMENTS_LOG", $apiUrl), $apiUrl, $requestId);

$dataReturn = [];

$IOTtitle = "Infrastructure Lead Review";
$PEtitle = "Private Equity Analyst Review";
$PCtitle = "Private Credit Analyst Review";

if ($teamID == 'INFRA') {
    $commentsInfra = $data['IN_COMMENT_LOG_INFRA'];
    $commentsInfra[] = $dataCommentTeam;
    $dataReturn = [
        "SUBMIT_INFRA" => null,
        "SUBMIT_02_INFRA" => null,
        "SUBMIT_INFRA_H" => null,
        "copySubmitForm_INFRA" => null,
        "Allocated_INFRA" => true,
        "IN_COMMENT_LOG" => $commentLogData,
        "IN_INH_ACTION" => null,
        "IN_INH_Comments" => "",
        "readyScreen" => null,
        "INFRA_TITLE" => $IOTtitle,
        "IN_COMMENT_INFRA" => null,
        "IN_INFRAH_ACTION" => null,
        "IN_COMMENT_LOG_INFRA" => $commentsInfra
    ];
}

if ($teamID == 'PC') {
    $commentsPC = $data['IN_COMMENT_LOG_PC'];
    $commentsPC[] = $dataCommentTeam;
    $dataReturn = [
        "SUBMIT_PC" => null,
        "SUBMIT_02_PC" => null,
        "SUBMIT_PC_H" => null,
        "copySubmitForm_PC" => null,
        "Allocated_PC" => true,
        "IN_COMMENT_LOG" => $commentLogData,
        "readyScreen" => null,
        "PC_TITLE" => $PCtitle,
        "IN_COMMENT_PC" => null,
        "IN_PCH_ACTION" => null,
        "IN_COMMENT_LOG_PC" => $commentsPC
    ];
}

if ($teamID == 'PE') {
    $commentsPE = $data['IN_COMMENT_LOG_PE'];
    $commentsPE[] = $dataCommentTeam;
    $dataReturn = [
        "SUBMIT_PE" => null,
        "SUBMIT_02_PE" => null,
        "SUBMIT_PE_H" => null,
        "copySubmitForm_PE" => null,
        "Allocated_PE" => true,
        "IN_COMMENT_LOG" => $commentLogData,
        "readyScreen" => null,
        "PE_TITLE" => $PEtitle,
        "IN_COMMENT_PE" => null,
        "IN_PEH_ACTION" => null,
        "IN_COMMENT_LOG_PE" => $commentsPE
    ];
}

if ($teamID == 'CORP') {
   $dataReturn = [
      "SUBMIT_CORP" => null,
      "SUBMIT_02_CORP" => null,
      "copySubmitForm_CORP" => null,
      "Allocated_CORP" => true,
      "IN_COMMENT_LOG" => $commentLogData,
      "readyScreen" => null
   ];
}

// ---- Send Notifications ----
// Get parameters
$task = $config["task"] ?? "";
$emailType = $config["emailType"] ?? "";

// Validate data
if ($task !== "") {
   $data = array_merge($data, $dataReturn);
   // Send IN Notification
   sendInvoiceNotification($data, $task, $emailType, $api);
}
// ---- o ----

if (!empty($dataReturn)) {
   return $dataReturn;
}

return [   
   "Allocated" => true
];


/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by 
*/
function postRecordToCollection($record, $collectionID) {
    try {
        $pmheaders = [
            'Authorization' => 'Bearer ' . getenv('API_TOKEN'),        
            'Accept'        => 'application/json',
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
        return "Status Code " . $res->getStatusCode() . " .  Unable to Save";
        
    }
    catch (\Exception $e) {
        return $e->getMessage();
    }
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
                        ORDER BY id";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/* generate a sql sentece with data grid
 *
 * @param (String) $expense
 * @param (Int) $requestId
 * @return (String) $response
 *
 * by Daniel Aguilar
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
    $query .= 'IN_EXPENSE_GL_CODE, ';
    $query .= 'IN_EXPENSE_VALIDATION ';
    $query .= ') ';
    $query .= 'VALUES ';
    foreach ($expense as $expenseRow) {
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
    $query = substr($query, 0, -1) . ';';
    return $query;
}