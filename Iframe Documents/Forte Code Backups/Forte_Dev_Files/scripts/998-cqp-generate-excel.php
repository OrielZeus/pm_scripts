<?php 

/*****************************************
* Generate excel File
*
* by Diego Tapia
*****************************************/
ini_set('memory_limit', '-1');
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

require 'vendor/autoload.php';

// Set Default values
$apiInstance = $api->files();
$excelTemplateId = getenv($data["VAR_TEMPLATE"]);
$requests = getSqlData("POST", $searchQuery);

// Get Report Data
$searchParameters = json_decode(urldecode($data["DOWNLOAD_DATA"]), true);
$formatParameters = empty($data["DOCUMENT_FORMAT"]) ? null : json_decode(urldecode($data["DOCUMENT_FORMAT"]), true);
$searchQuery = str_replace("&#x2F;", "/", $searchParameters["query"]);
$requests = getSqlData("POST", $searchQuery);
$table_data = [];
$convertPercentage = [];

foreach($requests as &$request) {

    if ($request["CQP_MARKETS"] != null && $request["CQP_MARKETS"] != "" && $request["CQP_MARKETS"] != "null" && $formatParameters != null) {
        $markets = json_decode($request["CQP_MARKETS"]);
        
        foreach ($markets as $market) {
            $confColumnSelect = array_search($market->CQP_REINSURER, array_column($formatParameters["collectionConf"], "CQP_REINSURER"));

            if ($confColumnSelect !== false) {
                $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_TOTAL_SHARE"] = $market->CQP_FORTE_SHARE;
                $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_EEL_LIMIT"] = $market->CQP_EEL_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_CAT_LIMIT"] = $market->CQP_CAT_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_STOCK_MAX"] = $market->CAP_STOCK_MAX_EXP_REINSURER_SHARE;
                $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_STOCK_AVERAGE"] = $market->CQP_STOCK_AVERAGE_EXP_SCOR_SHARE;
                
                if ($formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] == "AUSTRAL") {
                    $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_RETENTION"] = $market->CQP_AUSTRAL_RETENTION;
                    $request["CQP_MARKETS_" . $formatParameters["collectionConf"][$confColumnSelect]["CQP_ALIAS"] . "_RETRO_AXA"] = $market->CQP_AXA;
                }
            }

            if ($market->CQP_REINSURER == "FORTE") {
                $request["CQP_MARKETS_FORTE_TOTAL_SHARE"] = $market->CQP_FORTE_SHARE;
                $request["CQP_MARKETS_FORTE_EEL_LIMIT"] = $market->CQP_EEL_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_FORTE_CAT_LIMIT"] = $market->CQP_CAT_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_FORTE_STOCK_MAX"] = $market->CAP_STOCK_MAX_EXP_REINSURER_SHARE;
                $request["CQP_MARKETS_FORTE_STOCK_AVERAGE"] = $market->CQP_STOCK_AVERAGE_EXP_SCOR_SHARE;
            }
        }
    }
    
    $tempRow = [];

    foreach ($searchParameters["columns"] as $column) {
        if ($column["variable"] != "id") {
            if ($request[$column["variable"]] == "null" || $request[$column["variable"]] == null || $request[$column["variable"]] == "") {
                $tempRow[] = "";
            } elseif ($column["format"] == null) {
                $tempRow[] = $column["variable"] == "" ? "" : $request[$column["variable"]];
            } else {
                switch ($column["format"]) {
                    case 'date_month':
                        $tempRow[] = date("m", strtotime($request[$column["variable"]]));
                        break;
                    case 'date_year':
                        $tempRow[] = date("m", strtotime($request[$column["variable"]]));
                        break;
                    case 'quarter_date':
                        $valMonth = (int)date("m", strtotime($request[$column["variable"]]));
                        
                        if ($valMonth <= 3) {
                            $result = "3rd";
                        } elseif ($valMonth <= 6) { 
                            $result = "4th";
                        } elseif ($valMonth <= 9) { 
                            $result = "1st";
                        } else {
                            $result = "2nd";
                        }
                        
                        $tempRow[] = $result;
                        break;
                    case 'fixName':
                        $search = [
                            "AUSTRAL" => "Austral RE",
                            "ECHO RE" =>"Echo RE",
                            "SCOR" => "Scor",
                            "MUNICH RE" => "Munich RE",
                            "AUSTRAL RE" => "Austral RE",
                        ];

                        $searchText = $search[strtoupper($request[$column["variable"]])];
                        
                        if ($searchText == null) {
                            $searchText = $request[$column["variable"]];
                        }

                        $tempRow[] = $searchText;
                        break;
                    case 'DDMMAA':
                        $timestamp = strtotime($request[$column["variable"]]); 
                        $tempRow[] = date('d/m/Y', $timestamp);
                        break;
                    case 'percentage':
                        $tempRow[] = !empty($request[$column["variable"]]) ? $request[$column["variable"]] / 100 : 0;
                        $convertPercentage[] = Coordinate::stringFromColumnIndex(count($tempRow)) . (count($table_data) + 7); 
                        break;
                    default:
                        $tempRow[] =  $request[$column["variable"]];
                        break;
                }
            }
        }
    }

    $table_data[] = $tempRow;
}

// Get report template File
$excel_template_name = getExcelTemplate($excelTemplateId);

// Set Data in File
if($excel_template_name !== "" && $excel_template_name  !== null){
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/tmp/'. $excel_template_name);
    $spreadsheet->getActiveSheet()->setTitle("Report");
    $sheet = $spreadsheet->getActiveSheet();

    if (!empty($individual_cell)) {
        putIndividualCell($individual_cell);
    }

    if($formatParameters != null) {
        putHeaders("A6", $formatParameters["startColumn"] . "6", $formatParameters["headers"]);
    }

    if (!empty($table_data)) {
        putTableData("A7", $table_data, $convertPercentage);

        for ($row = 6; $row <= (6 + count($table_data)); $row++) {
            $currentHeight = $sheet->getRowDimension($row)->getRowHeight();
            if ($currentHeight < 30) {
                $sheet->getRowDimension($row)->setRowHeight(30);
            }
        }
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save('/tmp/'. $excel_template_name);
    $fileContents = file_get_contents('/tmp/'. $excel_template_name);

    return [
        'fileContents' => base64_encode($fileContents), 
        'value' => rand(0, 1000)
    ];
    
}

return $response;

/* Get template file and temp path
 *
 * @param array $excelTemplateId
 * @return string $fileName
 *
 * by Diego Tapia
*/
function getExcelTemplate($excelTemplateId){
    global $apiInstance;

    $file = $apiInstance->getFileById($excelTemplateId);
    $fileName = $file->getFileName();
    $file = $apiInstance->getFileContentsById($excelTemplateId);
    rename($file->getPathname(), '/tmp/'.$fileName);
    chmod('/tmp/'.$fileName, 777);
    $fileContents = $file->getPathname();
    return $fileName;
}

/* Set Value in only a cell in the excel
 *
 * @param array $individual_cell
 *
 * by Diego Tapia
*/
function putIndividualCell($individual_cell){
    global $sheet;

    foreach($individual_cell as $key){
        $sheet->setCellValue($key["cell"], $key["value"]);
    }
}

/* Set Value in excel template based on data array
 *
 * @param string $individual_cell
 * @param array $dataExcel
 * @param array $convertPercentage
 *
 * by Diego Tapia
*/
function putTableData($initial_cell, $dataExcel, $convertPercentage){
    global $sheet, $domain, $data;
    $column = substr($initial_cell, 0, 1); 
    $row = substr($initial_cell, 1, 2); 

    foreach($dataExcel as $key){
        $columnStart = $column;

        foreach($key as $value){
            if (($data["VAR_TEMPLATE"] == "FORTE_BDRX_REPORT" && ($columnStart == "AI" || $columnStart == "AJ" || $columnStart == "AK" || $columnStart == "AR" || $columnStart == "AV" || $columnStart == "BJ")) ||
            ($data["VAR_TEMPLATE"] == "FORTE_PRODUCTION_REPORT" && ($columnStart == "W" || $columnStart == "Z" || $columnStart == "AC" || $columnStart == "AD" || $columnStart == "AG" || $columnStart == "BJ")) ||
            ($data["VAR_TEMPLATE"] == "FORTE_CONSOLIDADO_REPORT" && $columnStart == "J")) {
                $tempValue = empty($value) ? 0 : $value/100;
                $sheet->setCellValue($columnStart.$row, $tempValue);
            } else {
                $sheet->setCellValue($columnStart.$row, $value);
            }

            $sheet->getStyle($columnStart.$row)->applyFromArray([
                'alignment' => [
                    'horizontal' => "left",
                    'wrapText' => true,
                ]
            ]);

            if (in_array($columnStart.$row, $convertPercentage)) {
                $sheet->getStyle($columnStart.$row)->getNumberFormat()->setFormatCode('0.00%');
            }

            ++$columnStart;
        }

        $row++;
    }
}

/* Set dinamyc header in excel template based on data array
 *
 * @param string $start_filters
 * @param string $initial_cell
 * @param array $data
 *
 * by Diego Tapia
*/
function putHeaders($start_filters, $initial_cell, $data){
    global $sheet, $domain;
    $column = substr($initial_cell, 0, 1); 
    $row = substr($initial_cell, 1, 2); 

    foreach($data as $key){
        $sheet->getRowDimension($row)->setRowHeight(-1); 
        $sheet->setCellValue($column.$row, $key["name"] . " ");
        $sheet->getStyle($column.$row)->getAlignment()->setHorizontal("left")->setWrapText(true);
        $sheet->getStyle($column.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($key["color"]);
        $sheet->getColumnDimension($column)->setWidth(25);
        ++$column;
    }

    $lastRow = $sheet->getHighestRow();
    $sheet->setAutoFilter($start_filters . ':' . Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString(preg_replace('/\d/', '', $initial_cell)) + count($data) - 1) . $lastRow);
}


/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res 
 *
 * by Diego Tapia
 */
function getSqlData ($requestType, $postdata = [], bool $contentFile = false) {
    $headers = [
        "Accept" => $acceptType,
        "Authorization" => "Bearer " . getenv("API_TOKEN"),
        "Content-Type" => $contentFile ? "'application/octet-stream'" : "application/json"

    ];

    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);

    $request = new \GuzzleHttp\Psr7\Request($requestType, getenv('API_HOST') . getenv('API_SQL'), $headers, json_encode(["SQL" => base64_encode($postdata)]));
    
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }

    return $res;
}