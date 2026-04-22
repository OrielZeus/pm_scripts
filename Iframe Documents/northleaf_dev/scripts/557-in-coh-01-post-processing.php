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
$requestId = $data['_request']['id'];


$role     = "";
$comments = "";
if($data['newStatus'] == "corporateH"){
    $role     = "Corporate Finance Approver";
    $comments = empty($data["IN_COMMENT_COH"]) ? "" : $data["IN_COMMENT_COH"];
    $approval = $data["IN_SUBMITTER_COH"] == "Rejected" ? "Rejected" : "Approved";
}

if($data['newStatus'] == "corporate"){
    $approval = "";
    $role     = "Corporate Finance Reviewer";
    $comments = empty($data["IN_COMMENT_CO"]) ? "" : $data["IN_COMMENT_CO"];
}


//Save comments into collection
$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => $data["commentsData"]["userName"],
    "IN_CL_USER_ID" => $data["commentsData"]["userID"],
    "IN_CL_ROLE" => $role,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => $approval,
    "IN_CL_COMMENT_SAVED" => $comments,
    "IN_CL_DATE" => date("m/d/Y H:i:s"),
    "IN_SUBMIT" => null
];
$getResponse    = postRecordToCollection($dataRecord, getCollectionId("IN_COMMENTS_LOG", $apiUrl));
$commentLogData = getCommentsLog(getCollectionId("IN_COMMENTS_LOG", $apiUrl), $apiUrl, $requestId);

$query  = "SELECT * 
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'
            ORDER BY cast(substring_index(IN_EXPENSE_ROW_NUMBER,'.',1) as unsigned),
            cast(substring_index(substring_index(IN_EXPENSE_ROW_NUMBER,'.',2),'.',-1) as unsigned)";
$items = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
$sumtotal = $data['IN_SUMMARY_TOTAL_GRID']['INFRA']['IN_EXPENSE_TOTAL'] + $data['IN_SUMMARY_TOTAL_GRID']['PC']['IN_EXPENSE_TOTAL'] + $data['IN_SUMMARY_TOTAL_GRID']['PE']['IN_EXPENSE_TOTAL'] + $data['IN_SUMMARY_TOTAL_GRID']['CORP']['IN_EXPENSE_TOTAL']; 
if($config['newStatus'] == 'corporate'){
    $sumCo    = $data['IN_SUMMARY_TOTAL_GRID']['CORP']['IN_EXPENSE_TOTAL'];
    $sumtotal = $sumtotal - $sumCo;
}

$query  = "DELETE 
            FROM EXPENSE_TABLE
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

$newIndex = 1;
foreach($items as $row){
    if($config['newStatus'] == 'corporate' AND $row['IN_EXPENSE_TEAM_ROUTING_ID'] == 'CORP'){
        $row['IN_EXPENSE_PERCENTAGE_TOTAL'] = round($row['IN_EXPENSE_TOTAL'] * 100 / $sumCo,2);    
    }
    else{
        $row['IN_EXPENSE_PERCENTAGE_TOTAL'] = round($row['IN_EXPENSE_TOTAL'] * 100 / $sumtotal,2);
    }
    $row['IN_EXPENSE_TEAM_ROW_INDEX'] = $newIndex;
    createExpense($apiUrl, $row, $requestId);
    $newIndex++;
}
return [
    "COPY_TEMP_COMPANY_ID" => $data['vendorInformation'][0]['EXPENSE_VENDOR_COMPANYCODE'],
    "newStatus" => $config['newStatus'],
    "readyScreen" => null,
    "SUBMIT_CO" => null,
    "IN_SUBMITTER_COH" => null,
    "IN_COMMENT_LOG" => $commentLogData
];

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
    $query .= "'" . $expense['IN_EXPENSE_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_HST'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY_ID'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY_LABEL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_GL_CODE'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_VALIDATION'] . "'";
    $query .= ');';
    //return $query;

    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}