<?php
/**********************************
 * IN - Get Invoice Data
 *
 * by Favio Mollinedo
 *********************************/
require_once("/Northleaf_PHP_Library.php");
$arrayResponse = isset($data["invoiceTemplateData"]) ? $data["invoiceTemplateData"] : [];

//Get global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

/*
 * Get required columns
 *
 * @param (Array) $fileMatching
 * @param (Array) $invoiceTemplateDataArray
 * @return (Array) $result
 *
 * by Favio Mollinedo
 */
function extractRequiredColumns($fileMatching, $invoiceTemplateDataArray) {
    if(is_array($fileMatching)){
        $requiredHeaders = array_filter($fileMatching, function($col) {
            return $col['colSelected'] === true;
        });
    }
    $result = [];

    if(is_array($invoiceTemplateDataArray)){
        foreach ($invoiceTemplateDataArray as $row) {
            $filteredRow = [];
            foreach ($requiredHeaders as $header) {
                $key = $header['fileHeaderName'];
                $keyColName = $header['colName'];
                $filteredRow[$keyColName] = $row[$key] ?? null;
                //$filteredRow[$key] = $row[$key] ?? null;
            }
            $result[] = $filteredRow;
        }
    }

    return $result;
}

// Get key names
$keyArray = [];
if (isset($data['invoiceTemplateData'])) {
    $keys = array_keys($data['invoiceTemplateData']);
    foreach ($keys as $key) {
        $keyArray[] = $key;
    }
}
//IN_INVOICE_CURRENCY
$tempFileMatching = (isset($data["templateData"]["fileMatching"]) and is_array($data["templateData"]["fileMatching"])) ? $data["templateData"]["fileMatching"] : [];
$fileMatching = array_map(function ($item) {
    $item['fileHeaderName'] = str_replace(' ', '_', $item['fileHeaderName']);
    return $item;
}, $tempFileMatching);
// Extract the data
$vendorRequiredFieldsArray = [];
$dataInVendor = null;
$dataInNumber = null;
$dataInDate = null;
$dataInComment = null;
$tableName = $data["templateData"]["TAB_NAME"] != $data["TAB_NAME"] ? $data["TAB_NAME"] : $data["templateData"]["TAB_NAME"];
if($data["templateData"]["STATIC_INFORMATION"]){
    foreach($data["invoiceTemplateData"]["staticInformation"] as $key => $info){
        if(!empty($info[$data["templateData"]["VENDOR_POSITION"]])){
            $dataInVendor = $info[$data["templateData"]["VENDOR_POSITION"]];
        }
        if(!empty($info[$data["templateData"]["INVOICE_NUMBER_POSITION"]])){
            $dataInNumber = $info[$data["templateData"]["INVOICE_NUMBER_POSITION"]];
        }
        if(!empty($info[$data["templateData"]["DATE_POSITION"]])){
            $dataInDate = $info[$data["templateData"]["DATE_POSITION"]];
        }
        if(!empty($info[$data["templateData"]["COMMENT_POSITION"]])){
            $dataInComment = $info[$data["templateData"]["COMMENT_POSITION"]];
        }
    }
    //$tableName = end($keyArray);
    $vendorRequiredFieldsArray = extractRequiredColumns($fileMatching, $data["invoiceTemplateData"][$tableName]);
    //return $fileMatching;
    $taxSum = 0;
    $totalSum = 0;
    // Iterate and sum elements
    foreach ($vendorRequiredFieldsArray as $item) {
        $taxKeys = array_filter(array_keys($item), function($key) {
            return stripos($key, 'HST') !== false;
        });

        foreach ($taxKeys as $key) {
            if (!is_null($item[$key])) {
                $taxSum += $item[$key];
            }
        }
        //return $taxSum;
        $totalKeys = array_filter(array_keys($item), function($key) {
            return stripos($key, 'TOTAL') !== false;
        });
        foreach ($totalKeys as $key) {
            if (!is_null($item[$key])) {
                $totalSum += $item[$key];
            }
        }
    }
}else{
    //$tableName = end($keyArray);
    $vendorRequiredFieldsArray = extractRequiredColumns($fileMatching, $data["invoiceTemplateData"][$tableName]);

    $taxSum = 0;
    $totalSum = 0;
    // Iterate and sum elements
    foreach ($vendorRequiredFieldsArray as $item) {
        $taxKeys = array_filter(array_keys($item), function($key) {
            return stripos($key, 'HST') !== false;
            //return stripos($key, 'TAX') !== false;
        });

        foreach ($taxKeys as $key) {
            if (!is_null($item[$key])) {
                $taxSum += $item[$key];
            }
        }

        $totalKeys = array_filter(array_keys($item), function($key) {
            return stripos($key, 'TOTAL') !== false;
        });
        foreach ($totalKeys as $key) {
            if (!is_null($item[$key])) {
                $totalSum += $item[$key];
            }
        }
    }
}

$dataReturn = [];
$dataReturn["IN_INVOICE_VENDOR"] = $dataInVendor;
$dataReturn["IN_INVOICE_DATE"] = $dataInDate;
$dataReturn["IN_INVOICE_NUMBER"] = $dataInNumber;
$dataReturn["IN_INVOICE_TRANS_COMMENTS"] = $dataInComment;
//$dataReturn["IN_INVOICE_CURRENCY"] = $data["EXCEL_CURRENCY"];
$dataReturn["IN_INVOICE_TAX_TOTAL"] = $taxSum;
$dataReturn["IN_INVOICE_TOTAL"] = $totalSum;
$dataReturn["IN_INVOICE_ARRAY"] = $vendorRequiredFieldsArray;
$dataReturn["IN_INVOICE_ROW_NUMBER"] = count($vendorRequiredFieldsArray);
$dataReturn["invoiceTemplateData"] = [];

//Get Collections IDs
$collectionsToSearch = array('IN_VENDORS', 'IN_CURRENCY','IN_EXPENSE_CORP_ENTITY');
$collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl, 'IN_MASTER_COLLECTION_ID');
// Retrieve vendor information
$sqlVendors = "SELECT c.data->>'$.VENDOR_SYSTEM_ID_ACTG' as ID, 
                      CONCAT(c.data->>'$.VENDOR_LABEL', '|', c.data->>'$.EXPENSE_VENDOR_CURRENCY', '|', c.data->>'$.EXPENSE_VENDOR_NAME_CITY') as LABEL
                FROM collection_" . $collectionsArray['IN_VENDORS'] . " AS c
                WHERE c.data->>'$.VENDOR_STATUS' = 'Active'
                ORDER BY LABEL ASC";
$responseVendors = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendors));
$dataReturn['PM_VENDOR_SOURCE'] = $responseVendors;
// Retrieve currency information
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
$dataReturn['IN_IS_DISCREPANCY'] = 'true';

return $dataReturn;