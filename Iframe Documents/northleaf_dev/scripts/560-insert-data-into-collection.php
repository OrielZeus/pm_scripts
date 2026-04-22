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

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$requestId = $data["IN_REQUEST_ID"];

$companyID = (isset($data["COPY_COMPANY_ID"])) ? $data["COPY_COMPANY_ID"] : $data["COPY_TEMP_COMPANY_ID"];

$query .= "SELECT * 
            FROM EXPENSE_TABLE 
            WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'
            ORDER BY IN_EXPENSE_TEAM_ROW_INDEX";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

foreach($response as $row){
    $jsonData = '{"Completed": "'.date('Y-m-d').'",
                    "case_title": "'.$data["_request"]["case_title"].'",
                    "case_number": "'.$data["_request"]["case_number"].'",
                    "FINAL_SUBMITTER" : "'.$data["FINAL_SUBMITTER"].'",
                    "companyData": "'.$data["vendorInformation"]["companyData"].'",
                    "IN_COMMENT_LOG": '.(json_encode($data["IN_COMMENT_LOG"])).',
                    "COPY_COMPANY_ID": "'.$companyID.'",
                    "IN_INVOICE_DATE": "'.$data["IN_INVOICE_DATE"].'",
                    "IN_INVOICE_NUMBER": "'.$data["IN_INVOICE_NUMBER"].'",
                    "IN_INVOICE_VENDOR_LABEL": "'.$data["IN_INVOICE_VENDOR_LABEL"].'",
                    "IN_INVOICE_VENDOR": "'.$data["IN_INVOICE_VENDOR"].'",
                    "IN_INVOICE_VENDOR_ADDRESS": "'.$data["IN_INVOICE_VENDOR_ADDRESS"].'",
                    "IN_INVOICE_TRANS_COMMENTS": "'.$data["IN_INVOICE_TRANS_COMMENTS"].'",
                    "IN_INVOICE_CURRENCY": "'.$data["IN_INVOICE_CURRENCY"].'",
                    "IN_INVOICE_TOTAL": "'.$data["IN_INVOICE_TOTAL"].'",
                    "FX_CURRENCY": "'.$data["IN_FX_CURRENCY"].'",
                    "FX_RATE": "'.$data["IN_FX_RATE"].'",
                    "FX_AMOUNT": "'.$data["IN_FX_TOTAL_AMOUNT"].'",
                    "FINAL_SUBMITTER_DATE": "'.$data["FINAL_SUBMITTER_DATE"].'",
                    "IN_INVOICE_VENDOR_LOCATION": "'.$data["vendorInformation"]["vendorLocation"].'",
                    "IN_EXPENSE_TEAM_ROW_INDEX": "'.$row["IN_EXPENSE_TEAM_ROW_INDEX"].'",
                    "N_EXPENSE_NR_LABEL": "'.$row["N_EXPENSE_NR_LABEL"].'",
                    "IN_EXPENSE_OFFICE_ID": "'.$row["IN_EXPENSE_OFFICE_ID"].'",
                    "IN_EXPENSE_ACCOUNT_ID": "'.$row["IN_EXPENSE_ACCOUNT_ID"].'",
                    "IN_EXPENSE_NR_ID": "'.$row["IN_EXPENSE_NR_ID"].'",
                    "IN_EXPENSE_MANDATE_ID": "'.$row["IN_EXPENSE_MANDATE_ID"].'",
                    "IN_EXPENSE_TOTAL": "'.$row["IN_EXPENSE_TOTAL"].'",
                    "IN_EXPENSE_HST": "'.$row["IN_EXPENSE_HST"].'",
                    "IN_EXPENSE_GL_CODE": "'.$row["IN_EXPENSE_GL_CODE"].'",
                    "IN_EXPENSE_PERCENTAGE": "'.$row["IN_EXPENSE_PERCENTAGE"].'",
                    "IN_EXPENSE_ACTIVITY_ID": "'.$row["IN_EXPENSE_ACTIVITY_ID"].'",
                    "IN_EXPENSE_DESCRIPTION": "'.$row["IN_EXPENSE_DESCRIPTION"].'",
                    "IN_EXPENSE_CORP_PROJ_ID": "'.$row["IN_EXPENSE_CORP_PROJ_ID"].'",
                    "IN_EXPENSE_OFFICE_LABEL": "'.$row["IN_EXPENSE_OFFICE_LABEL"].'",
                    "IN_EXPENSE_ACCOUNT_LABEL": "'.$row["IN_EXPENSE_ACCOUNT_LABEL"].'",
                    "IN_EXPENSE_DEPARTMENT_ID": "'.$row["IN_EXPENSE_DEPARTMENT_ID"].'",
                    "IN_EXPENSE_MANDATE_LABEL": "'.$row["IN_EXPENSE_MANDATE_LABEL"].'",
                    "IN_EXPENSE_PRETAX_AMOUNT": "'.$row["IN_EXPENSE_PRETAX_AMOUNT"].'",
                    "IN_EXPENSE_ACTIVITY_LABEL": "'.$row["IN_EXPENSE_ACTIVITY_LABEL"].'",
                    "IN_EXPENSE_CORP_ENTITY_ID": "'.$row["IN_EXPENSE_CORP_ENTITY_ID"].'",
                    "IN_EXPENSE_CORP_PROJ_LABEL": "'.$row["IN_EXPENSE_CORP_PROJ_LABEL"].'",
                    "IN_EXPENSE_FUND_MANAGER_ID": "'.$row["IN_EXPENSE_FUND_MANAGER_ID"].'",
                    "IN_EXPENSE_PROJECT_DEAL_ID": "'.$row["IN_EXPENSE_PROJECT_DEAL_ID"].'",
                    "IN_EXPENSE_TEAM_ROUTING_ID": "'.$row["IN_EXPENSE_TEAM_ROUTING_ID"].'",
                    "IN_EXPENSE_DEPARTMENT_LABEL": "'.$row["IN_EXPENSE_DEPARTMENT_LABEL"].'",
                    "IN_EXPENSE_PERCENTAGE_TOTAL": "'.$row["IN_EXPENSE_PERCENTAGE_TOTAL"].'",
                    "IN_EXPENSE_CORP_ENTITY_LABEL": "'.$row["IN_EXPENSE_CORP_ENTITY_LABEL"].'",
                    "IN_EXPENSE_FUND_MANAGER_LABEL": "'.$row["IN_EXPENSE_FUND_MANAGER_LABEL"].'",
                    "IN_EXPENSE_PROJECT_DEAL_LABEL": "'.$row["IN_EXPENSE_PROJECT_DEAL_LABEL"].'"}';


    $queryInsert = "INSERT INTO collection_".getCollectionId('IN_SUMMARY_DATA', $apiUrl)."
    (created_by_id,updated_by_id,data) values(170,1,'".$jsonData."')";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryInsert));
}

// Send IN Notification
$task = "FINISH";
$emailType = "";
sendInvoiceNotification($data, $task, $emailType, $api);

return $response;