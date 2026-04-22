<?php
$time_start = microtime(true); //////////////////time execution
require_once("/Northleaf_PHP_Library.php");

$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$dataReturn = array();

$requestId = $data["_request"]["id"];
$sql = "DELETE FROM EXPENSE_TABLE
        WHERE IN_EXPENSE_CASE_ID = '".$requestId."'";

$resDelete = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));

$collectionsToSearch = array('IN_VENDORS_PROMPT', 'IN_VENDORS', 'IN_CURRENCY','IN_EXPENSE_CORP_ENTITY');
$collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl, 'IN_MASTER_COLLECTION_ID');

//Get Vendor options Data Source
/*$sqlVendors = "SELECT c.data->>'$.VENDOR_SYSTEM_ID_ACTG' as ID,
                CONCAT(c.data->>'$.VENDOR_LABEL', '|', IFNULL(c.data->>'$.EXPENSE_VENDOR_CURRENCY', ''), '|', IFNULL(c.data->>'$.EXPENSE_VENDOR_NAME_CITY', '')) as LABEL      
               FROM collection_" . $collectionsArray['IN_VENDORS'] . " AS c
               WHERE c.data->>'$.VENDOR_STATUS' = 'Active'
               ORDER BY LABEL ASC";
$responseVendors = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendors));*/ // comment this line because the watcher was addd into screen 
//$dataReturn["IN_INVOICE_VENDOR"] = findFirstMatch($responseVendors, $outputAITask, 50);
//$vendorInv = preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚüÜ\s]/u', '',$data['IN_INVOICE_VENDOR']);
// Deleted vendor invoice name by Ana Castillo requested by the client 2024-01-02
//$dataReturn["IN_INVOICE_VENDOR"] = findBestMatchingVendor($vendorInv, $responseVendors);
$dataReturn["IN_INVOICE_VENDOR_AZURE"] = $vendorInv;
//$dataReturn['PM_VENDOR_SOURCE'] = $responseVendors; // comment this line because the watcher was addd into screen 

//Get Currency data Source
$sqlCurrency = "SELECT c.data->>'$.CURRENCY_ID' as ID,   
                       c.data->>'$.CURRENCY_LABEL' as LABEL
                FROM collection_" . $collectionsArray['IN_CURRENCY'] . " AS c
                WHERE c.data->>'$.CURRENCY_STATUS' = 'Active'";
$responseCurrency = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlCurrency));
$dataReturn['PM_CURRENCY_SOURCE'] = $responseCurrency;

//Get Company data Source
$sqlCurrency = "SELECT c.data->>'$.NL_COMPANY_SYSTEM_ID_ACTG' as ID,   
                       c.data->>'$.EXPENSE_COMPANY_LABEL' as LABEL
                FROM collection_" . $collectionsArray['IN_EXPENSE_CORP_ENTITY'] . " AS c
                WHERE c.data->>'$.EXPENSE_CORPORATE_STATUS' = 'Active'";
$responseCompany = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlCurrency));
$dataReturn['PM_COMPANY_SOURCE'] = $responseCompany;

//Set submit variable as SUBMIT to show required fields
$dataReturn['IN_SAVE_SUBMIT'] = "SUBMIT";
$dataReturn['IN_IS_DISCREPANCY'] = (isset($data['IN_IS_DISCREPANCY']) AND $data['IN_IS_DISCREPANCY'] ) ? 'true' : 'false';



/**********************************
 * IN - IS.02 Pre-processing
 * by Ana Castillo
 *********************************/
//Set return


//Set flag if it is IS.02
$dataReturn["IN_FLAG_IS02"] = true;

//Set variable to know that this is IS.02 and if it is Excel
$dataReturn["IN_SHOW_EXCEL_NUMBER"] = false;
if ($data['CHECK_EXCEL_FLOW']) {
    $dataReturn["IN_SHOW_EXCEL_NUMBER"] = true;
}

//Set Submit to required fields
$dataReturn["IN_SAVE_SUBMIT"] = "SUBMIT";

//Fill a first value for calc in next screen
$dataReturn["just60Characters"] = true;


$time_end = microtime(true); //////////////////time execution
$execution_time = ($time_end - $time_start); //////////////////time execution
$dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];//////////////////time execution
$dataTimeExec['Validate Values Extraction Azure'][] = date('Y-m-d h:i:s');//////////////////time execution
$dataTimeExec['Validate Values Extraction Azure'][] = $execution_time;//////////////////time execution
$dataReturn['dataTimeExec'] = $dataTimeExec; //////////////////time execution

return $dataReturn;


function findBestMatchingVendor($listPage, $vendors) {
    
    if (isset($listPage) && !empty($listPage)) {
        $inputWords = explode(' ', strtolower($listPage));
        $bestMatch = null;
        $maxSequentialMatches = 0;

        foreach ($vendors as $vendor) {
            $vendorWords = explode(' ', strtolower($vendor['LABEL']));
            $sequentialMatches = 0;

            // Compare the same position
            foreach ($inputWords as $index => $word) {
                if (isset($vendorWords[$index]) && $vendorWords[$index] === $word) {
                    $sequentialMatches++;
                } else {
                    break; // Stops the comparison if there are no more matches
                }
            }

            //  Update if there are more matches
            if ($sequentialMatches > $maxSequentialMatches) {
                $maxSequentialMatches = $sequentialMatches;
                $bestMatch = $vendor['ID'];
            }
        }
    }
   
    return $bestMatch;
}