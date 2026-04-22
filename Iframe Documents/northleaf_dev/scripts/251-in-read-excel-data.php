<?php 
/* Extract data from Excel File
 *  
 * by Favio Mollinedo
*/

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

//Get global variables
$collectionId = $data["COLLECTION_ID"];
$templateID = $data["IN_TEMPLATE_ID"];
$pmheaders = [
    'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
    'Accept'        => 'application/json',
];
$apiHost = getenv('API_HOST');
$client = new GuzzleHttp\Client(['verify' => false]);

/*
 * Validate format Date
 *
 * @return (Array) bolean
 *
 * by Daniel Aguilar
 */
function validateFormat($date, $format) {
    // Attempt to create a DateTime object from the given date string and format
    $d = DateTime::createFromFormat($format, $date);

    // Check if the creation was successful and if the formatted date string matches the original
    // This second check ensures that the date is not only parsable but also valid (e.g., no 'February 30th')
    return $d && $d->format($format) == $date;
}


/*
 * Get template information
 *
 * @return (Array) $data
 *
 * by Favio Mollinedo
 */
function getTemplateData()
{
    global $templateID, $collectionId, $apiHost, $pmheaders, $client;
    try {
        $res = $client->request("GET",  $apiHost . "/collections/$collectionId/records/$templateID", [
            "headers" => $pmheaders,
            'http_errors' => false
        ]);
        $response = json_decode($res->getBody(), true);
        return $response["data"];
    } catch (Exception $e) {
        return [];
    }
}

/*
 * Check if a cell is a date
 *
 * @param (Array) $worksheet
 * @param (Array) $column
 * @param (Array) $row
 * @return (Boolean) true or false
 *
 * by Favio Mollinedo
 */
function isDateCell($worksheet, $column, $row)
{
    $cell = $worksheet->getCell("{$column}{$row}");
    $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();

    // Format dates
    $dateFormats = [
        'm/d/yy', 'm/d/yyyy', 'd-m-yyyy', 'yyyy-mm-dd', 'dd/mm/yyyy', 
        'dd-mmm-yyyy', 'dd-mmm', 'mmm-yy', 'mm/dd/yy', 'mm/dd/yyyy', 'M d Y'
    ];

    // Compare formats
    foreach ($dateFormats as $dateFormat) {
        if (stripos($formatCode ?? '', $dateFormat  ?? '') !== false) {
            return true;
        }
    }

    return Date::isDateTime($cell);
}

/*
 * Check if the row has data in the position of the primary key
 *
 * @param (Array) $row
 * @param (Int) $position
 * @return (Boolean) true or false
 *
 * by Favio Mollinedo
 */
function hasDataAtPosition($row, $position) {
    $values = array_values($row);
    if (isset($values[$position]) && !is_null($values[$position])) {
        return true;
    }
    return false;
}

/*
 * Read Excel To Array
 *
 * @param (String) $filePath
 * @param (Int) $rowIndexDataAlone
 * @param (Int) $rowEndDataAlone
 * @param (Array) $columns
 * @param (Int) $rowIndexTable
 * @param (Int) $rowEndTable
 * @param (Array) $columnsTable
 * @param (String) $singleDataPosition
 * @param (Boolean) $staticConfig
 * @return (Array) $sheetData
 *
 * by Favio Mollinedo
 * extracted from readExcelToArray by Elmer Orihuela
 */
function readExcelToArray($filePath, $rowIndexDataAlone, $rowEndDataAlone, $columns, $rowIndexTable, $rowEndTable, $columnsTable, $singleDataPosition, $staticConfig, $requiredItemColumn, $tabName,$rowIndexInvoiceNumber,$rowIndexInvoiceDate)
{
    try {
        $spreadsheet = IOFactory::load($filePath);
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        die('Error loading file: ' . $e->getMessage());
    }

    $sheetData = [];
    $currencyData = "";
    $sheetName = $tabName;
    $worksheet = $spreadsheet->getSheetByName($sheetName);

    if ($worksheet !== null) {
        if(count($columns) > 0 && $staticConfig){
            $firstRows = [];
            $firstHeader = [];
            //Extract static information before items table
            foreach ($worksheet->getRowIterator($rowIndexDataAlone, $rowEndDataAlone) as $rowIndex => $row) {
                $rowIndex = $row->getRowIndex();
                if (!empty($rowEndDataAlone) && $rowIndex > $rowEndDataAlone) {
                    break;
                }
                
                $cellValues = [];
                foreach ($columns as $keyStatic => $column) {
                    /*if($keyStatic == 9)
                        return $worksheet->getCell("{$column}{$rowIndex}")->getCalculatedValue();*/
                    $cellValue = $worksheet->getCell("{$column}{$rowIndex}")->getValue();
                    if(is_object($worksheet->getCell("{$column}{$rowIndex}")) || $worksheet->getCell("{$column}{$rowIndex}")->isFormula()){
                        $cellValue = $worksheet->getCell("{$column}{$rowIndex}")->getCalculatedValue();
                    }
                    if($rowIndexInvoiceDate =="{$column}{$rowIndex}"){// add this condition to validate the invoice date By Daniel Aguilar
                        if(empty($cellValue)){
                            $cellValue = '';
                        }else{
                            if(validateFormat($cellValue,'U')){
                                $cellValue = ($cellValue-25569)*86400;
                                $cellValue =  date("Y-m-d",$cellValue);
                            }
                            if(!validateFormat($cellValue,'Y-m-d')){
                                $cellValue = date('Y-m-d', strtotime($cellValue));
                            }
                        }
                        if(!validateFormat($cellValue,'Y-m-d') || $cellValue == '1970-01-01'){
                            $cellValue = '';
                        }
                    }
                    
                    //$cellValues["{$column}{$rowIndex}"] = (isDateCell($worksheet, $column, $rowIndex)) ? DateTime::createFromFormat('Y-m-d', '1899-12-30')->modify("+$cellValue days")->format("Y-m-d") : $cellValue;
                    $cellValues["{$column}{$rowIndex}"] = $cellValue;
                }
                $firstRows[] = $cellValues;
            }
            $sheetData["staticInformation"] = $firstRows;
        }

        //Extract items table information
        $keyPosition = 0;
        $count = 0;
        
        $swCurrencyData = true;
        $rows = [];
        $header = [];
        foreach ($worksheet->getRowIterator($rowIndexTable, $rowEndTable) as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowIndex = $row->getRowIndex();
            if (!empty($rowEndTable) && $rowIndex > $rowEndTable) {
                break;
            }
            
            $rowData = [];
            $swEmptyData = false;
            if(count($columnsTable) == 0){
                foreach ($cellIterator as $index => $cell) {
                    $formatCode = $cell->getStyle()->getNumberFormat()->getFormatCode();
                    // Verify the coin
                    if (strpos($formatCode, '$') !== false) {
                        $currencyData =  "USD";
                        $swCurrencyData = false;
                    } elseif (strpos($formatCode, '€') !== false) {
                        $currencyData =  "EUR";
                        $swCurrencyData = false;
                    } elseif (strpos($formatCode, '£') !== false) {
                        $currencyData =  "GBP";
                        $swCurrencyData = false;
                    } else {
                        if($swCurrencyData){
                            $currencyData =  "";
                        }
                    }

                    $cellValue = is_object($cell) || $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
                    //if(!empty($cellValue))
                        $rowData[] = $cellValue;
                }
            }else{
                
                foreach ($columnsTable as $index => $column) {
                    $formatCode = $worksheet->getCell("{$column}{$rowIndex}")->getStyle()->getNumberFormat()->getFormatCode();
                    // Verify the coin
                    if (strpos($formatCode, '$') !== false) {
                        $currencyData =  "USD";
                        $swCurrencyData = false;
                    } elseif (strpos($formatCode, '€') !== false) {
                        $currencyData =  "EUR";
                        $swCurrencyData = false;
                    } elseif (strpos($formatCode, '£') !== false) {
                        $currencyData =  "GBP";
                        $swCurrencyData = false;
                    } else {
                        if($swCurrencyData){
                            $currencyData =  "";
                        }
                    }
                    $cellValue = $worksheet->getCell("{$column}{$rowIndex}");
                    $rowData[] = is_object($cellValue) || $worksheet->getCell("{$column}{$rowIndex}")->isFormula() ? $worksheet->getCell("{$column}{$rowIndex}")->getCalculatedValue() : $cellValue->getValue();
                }
            }
            if($rowIndex != $rowIndexTable && !hasDataAtPosition($rowData, $keyPosition)){
                array_pop($rowData);
                $swEmptyData = true;
            }
            
            if ($rowIndex == $rowIndexTable) {
                // Process header row to replace spaces with underscores
                //return $rowData;
                foreach ($rowData as $key => &$value) {
                    //get the key header position
                    if(stripos($value ?? '', $requiredItemColumn[0]["fileHeaderName"] ?? '') !== false){
                        $keyPosition = $key;
                    }
                    // Check for null headers and assign them a name
                    /*if(empty($value)){
                        $value = "Empty_Header_".$key;
                    }*/
                    //if(!empty($value))
                        $header[$key] = str_replace(' ', '_', $value ?? '');
                }
            } else {
                // Combine header with row data if they have data
                if(!$swEmptyData){
                    $rows[] = array_combine($header, $rowData);
                }
            }
        }

        $sheetData[$sheetName] = $rows;
    }
    return [$sheetData, $currencyData];
}

function validateDate($date, $format = 'm-d-Y')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && strtolower($d->format($format)) === strtolower($date);
}


$processRequestId = $data['_request']['id'];
$fileId = $data['IN_UPLOAD_EXCEL'];

$file = $api->requestFiles()->getRequestFilesById($processRequestId, $fileId);
$path = $file->getPathname();

$templateData = getTemplateData();
//return $templateData;
$requiredItemColumn = array_values(array_filter($templateData["fileMatching"], function ($column) {
    return $column['required'] === true;
}));
//return $requiredItemColumn;
//Columns and Rows by the client
$rowIndexInvoiceNumber = $templateData["INVOICE_NUMBER_POSITION"] ?? '';
$rowIndexInvoiceDate = $templateData["DATE_POSITION"] ?? '';
$rowIndexDataAlone = $templateData["INITIAL_ROW_NUMBER"] ?? '';
$rowEndDataAlone = $templateData["END_ROW_NUMBER"] ?? '';
$initRowLetter = $templateData["INITIAL_ROW_LETTER"] ?? '';
$endtRowLetter = $templateData["END_COLUMN_LETTER"] ?? '';
$singleDataPosition = $templateData["SINGLE_INVOICE_VALUES"] ?? '';
$staticConfig = $templateData["STATIC_INFORMATION"] ?? '';
$columns = !empty($initRowLetter) && !empty($endtRowLetter) && $staticConfig ? range($initRowLetter, $endtRowLetter) : [];
$rowIndexTable = $templateData["TABLE_INITIAL_ROW_NUMBER"] != $data["TABLE_INITIAL_ROW_NUMBER"] ? $data["TABLE_INITIAL_ROW_NUMBER"] : $templateData["TABLE_INITIAL_ROW_NUMBER"];
$rowEndTable = $templateData["TABLE_END_ROW_NUMBER"] ?? '';
$initRowTable = $templateData["TABLE_INITIAL_ROW_LETTER"] ?? '';
$endtRowTable = $templateData["TABLE_END_COLUMN_LETTER"] ?? '';
$tabName = $templateData["TAB_NAME"] != $data["TAB_NAME"] ? $data["TAB_NAME"] : $templateData["TAB_NAME"];
$columnsTable = !empty($initRowTable) && !empty($endtRowTable) ? range($initRowTable, $endtRowTable) : [];

//return readExcelToArray($path, $rowIndexDataAlone, $rowEndDataAlone, $columns, $rowIndexTable, $rowEndTable, $columnsTable, $singleDataPosition, $staticConfig, $requiredItemColumn, $tabName);
$resultArray = readExcelToArray($path, $rowIndexDataAlone, $rowEndDataAlone, $columns, $rowIndexTable, $rowEndTable, $columnsTable, $singleDataPosition, $staticConfig, $requiredItemColumn, $tabName,$rowIndexInvoiceNumber,$rowIndexInvoiceDate);
/*$resultArray[0][$tabName] = array_map(function ($item) {
    return array_filter($item, function ($value, $key) {
        return $key !== "" || $value !== null;
    }, ARRAY_FILTER_USE_BOTH);
}, (array) $resultArray[0][$tabName] ?? [] );*/
$resultArray[0][$tabName] = array_map(function ($item) {
    return array_filter($item, function ($value, $key) {
        return $key !== "" || $value !== null;
    }, ARRAY_FILTER_USE_BOTH);
}, (array) ($resultArray[0][$tabName] ?? []));

return [
    'invoiceTemplateData' => $resultArray[0],
    'templateData' => $templateData,
    'EXCEL_CURRENCY' => $resultArray[1],
    'ERROR_EXCEL' => count($resultArray[0][$tabName]) == 0 ? true : false,
    'EXISTS_ERROR_EXCEL' => count($resultArray[0][$tabName]) == 0 ? true : false,
    'EXCEL_ERROR_TAB' => count($resultArray[0][$tabName]) == 0 ? "The Excel Tab Name field does not match the tab name of the file. Please check to ensure the selected template matches the template you are using or update the Template Details if you made changes to the uploaded template itself." : ""
    ];