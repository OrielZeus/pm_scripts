<?php 
/******************************** 
 * Bulk Excel Data - Insert data to collection
 *
 * by Favio Mollinedo
 *******************************/

use PhpOffice\PhpSpreadsheet\IOFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by Favio Mollinedo
 */

function postRecord($record, $collectionID){
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

/*
 * read Excel To Array
 *
 * @param (String) $filePath
 * @return (Array) $sheetData
 *
 * by Elmer Orihuela
 */
function readExcelToArray($filePath)
{
    try {
        $spreadsheet = IOFactory::load($filePath);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        die('Error loading file: ' . $e->getMessage());
    }

    $sheetData = [];

    foreach ($spreadsheet->getSheetNames() as $sheetIndex => $sheetName) {
        $worksheet = $spreadsheet->getSheet($sheetIndex);
        $rows = [];
        $header = [];

        foreach ($worksheet->getRowIterator() as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            if ($rowIndex == 1) {
                // Process header row to replace spaces with underscores
                foreach ($rowData as $key => $value) {
                    $header[$key] = str_replace(' ', '_', $value);
                }
            } else {
                // Combine header with row data
                $rows[] = array_combine($header, $rowData);
            }
        }

        $sheetData[$sheetName] = $rows;
    }

    return $sheetData;
}

//Get Global Variables
$apiHost = getenv("API_HOST");
$apiToken = getenv("API_TOKEN");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;
$filePath = '/tmp/file_v1.1.xlsx';

$pathPdfOriginal = getenv("HOST_URL") . '/public-files/InvoiceVendorsPM3.xlsx';
$xlsSheet = 'Sheet1';

if (!file_exists($filePath)) {
    $fileContents = file_get_contents($pathPdfOriginal);
    if ($fileContents === false) {
        die('Error downloading file from URL: ' . $pathPdfOriginal);
    }
    file_put_contents($filePath, $fileContents);
}

//Get collections IDs
$collectionName = "IN_VENDORS";
$collectionID = getCollectionId($collectionName, $apiUrl);
$collectionID = 52;

$dataArray = readExcelToArray($filePath);
$dataRecord = [];
foreach ($dataArray[$xlsSheet] as $key => $item) {
    if (!empty($item['VE_NAME'])) {
        $dataRecord = [
            "VENDOR_SYSTEM_ID_ACTG" => $item['VE_UID'],
            "VENDOR_SYSTEM_ID_DB" => $item['VE_VEID'],
            "VENDOR_STATUS" => ($item['VE_STATUS'] == 'ACTIVE' ? 'Active' : 'Inactive'),
            "VENDOR_LABEL" => $item['VE_NAME'],
            "IN_SUBMIT" => null,
        ];
        $getResponse = postRecord($dataRecord, $collectionID);
    }
}
return $dataRecord;