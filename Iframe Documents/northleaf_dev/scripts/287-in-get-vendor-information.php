<?php
/**********************************
 * OFF - Get vendor information from collection
 *
 * by Adriana Centellas
 *********************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Define collection name
$collectionName = 'IN_VENDORS';
//Get collection ID
$collectionID = getCollectionId($collectionName, $apiUrl);
//Get vendor Id from DATA
$vendorID = $data["IN_INVOICE_VENDOR"];
//Get vendor information
$vendorInfo = getVendorInformationById($collectionID, $apiUrl, $vendorID);

$vendorLoc  = 'OTHER';
if(isset($vendorInfo[0]) AND isset($vendorInfo[0]['EXPENSE_VENDOR_COUNTRY']) AND ($vendorInfo[0]['EXPENSE_VENDOR_COUNTRY'] == 'UNITED KINGDOM' OR $vendorInfo[0]['EXPENSE_VENDOR_COUNTRY'] == 'UK')){
    $vendorLoc  = 'UK';
}
if(isset($vendorInfo[0]) AND isset($vendorInfo[0]['EXPENSE_VENDOR_PROVINCE_STATE'])){
    if($vendorInfo[0]['EXPENSE_VENDOR_PROVINCE_STATE'] == 'ONTARIO' OR $vendorInfo[0]['EXPENSE_VENDOR_PROVINCE_STATE'] == 'ON'){ 
        $vendorLoc  = 'ON';
    }
    if($vendorInfo[0]['EXPENSE_VENDOR_PROVINCE_STATE'] == 'BRITISH COLUMBIA'){ 
        $vendorLoc  = 'BC';
    }
}
if(isset($vendorInfo[0]) AND isset($vendorInfo[0]['VENDOR_LABEL']) AND $vendorInfo[0]['VENDOR_LABEL'] == 'NEW VENDOR'){
    $vendorLoc  = 'OTHER';
}
$vendorInfo['companyData']    = $vendorInfo[0]['EXPENSE_VENDOR_COMPANYNAME'];

if(isset($data['IN_INVOICE_CURRENCY'])){
    //$venCurr = (isset($vendorInfo[0]) AND isset($vendorInfo[0]['EXPENSE_VENDOR_CURRENCY'])) ? $vendorInfo[0]['EXPENSE_VENDOR_CURRENCY'] : '';
    $fxReq = 'No';
    if($data['IN_INVOICE_CURRENCY'] != 'CAD' && $data['IN_INVOICE_CURRENCY'] != 'USD' AND $vendorInfo[0]['EXPENSE_VENDOR_COMPANYCODE'] == '010'){
        $fxReq = 'Yes';
    }
    //$fxReq   = ($venCurr == $data['IN_INVOICE_CURRENCY']) ? 'No' : 'Yes';
    $vendorInfo['IN_FX_REQUIRED'] = $fxReq;
}
if(isset($data['COPY_VENDOR_DATA'])){
    $dataLoc = explode("-", $data['COPY_VENDOR_DATA']);
    if($dataLoc[0] == $vendorInfo[0]['VENDOR_SYSTEM_ID_ACTG']){
        $vendorLoc = $dataLoc[1];
        if($dataLoc[2] != ''){
            $vendorInfo['IN_FX_REQUIRED'] = $dataLoc[2];
        }
    }
    
}
//Validate the null in EXPENSE_VENDOR_ADDRESS
if (isset($vendorInfo[0]) && 
    isset($vendorInfo[0]['EXPENSE_VENDOR_ADDRESS']) && 
    ($vendorInfo[0]['EXPENSE_VENDOR_ADDRESS'] == null || $vendorInfo[0]['EXPENSE_VENDOR_ADDRESS'] === 'null')) {
    $vendorInfo[0]['EXPENSE_VENDOR_ADDRESS'] = '';
}


$vendorInfo['vendorLocation'] = $vendorLoc;

return $vendorInfo;

/**
 * Fetch vendor information by its ID from a specific collection.
 *
 * @param int $collectionId The ID of the collection to query.
 * @param string $apiUrl The URL of the API to fetch data.
 * @param int $vendorId The ID of the vendor to retrieve information for.
 * @return array An array containing the vendor information.
 *
 * by Adriana Centellas
 */
function getVendorInformationById($collectionId, $apiUrl, $vendorId)
{
    // SQL query to fetch vendor details based on the provided vendor ID
    $sqlUserLeader = "
        SELECT 
            data->>'$.VENDOR_LABEL' AS VENDOR_LABEL,
            data->>'$.VENDOR_STATUS' AS VENDOR_STATUS,
            data->>'$.VENDOR_SYSTEM_ID_DB' AS VENDOR_SYSTEM_ID_DB,
            data->>'$.VENDOR_SYSTEM_ID_ACTG' AS VENDOR_SYSTEM_ID_ACTG,
            data->>'$.EXPENSE_VENDOR_ADDRESS' AS EXPENSE_VENDOR_ADDRESS,
            data->>'$.EXPENSE_VENDOR_COUNTRY' AS EXPENSE_VENDOR_COUNTRY,
            data->>'$.EXPENSE_VENDOR_CURRENCY' AS EXPENSE_VENDOR_CURRENCY,
            data->>'$.EXPENSE_VENDOR_COMPANYCODE' AS EXPENSE_VENDOR_COMPANYCODE,
            data->>'$.EXPENSE_VENDOR_COMPANYNAME' AS EXPENSE_VENDOR_COMPANYNAME,
            data->>'$.EXPENSE_VENDOR_PROVINCE_STATE' AS EXPENSE_VENDOR_PROVINCE_STATE
        FROM collection_" . $collectionId . "
        WHERE data->>'$.VENDOR_SYSTEM_ID_ACTG' = '" . $vendorId . "';";

    // Call the API to execute the query and fetch results
    $rQUserLeader = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUserLeader)) ?? [];

    // Return the vendor information
    return $rQUserLeader;
}