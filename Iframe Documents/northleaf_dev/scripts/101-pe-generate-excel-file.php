<?php 
/***************************
 * PE - Generate Excel File 
 *
 * by Cinthia Romero
 **************************/
//Load autoloader
require_once 'vendor/autoload.php';

/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $url
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res
 *
 * by Elmer Orihuela 
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

/**
 * Build Field Format
 *
 * @param string $formatType
 * @param string $formatCharacteristics
 * @return $fieldFormat
 *
 * by Cinthia Romero
 */
function buildFieldFormat($formatType, $formatCharacteristics)
{
    $fieldFormat = "";
    switch($formatType) {
        case "Currency":
            //Set base format
            $fieldFormat = "##,##0";
            if (isset($formatCharacteristics["ECC_CURRENCY_NUMBER_DECIMALS"])) {
                //Add decimals
                for ($i=0; $i<$formatCharacteristics["ECC_CURRENCY_NUMBER_DECIMALS"]; $i++) {
                    if ($i == 0) {
                        $fieldFormat = $fieldFormat . "." . "0";
                    } else {
                        $fieldFormat = $fieldFormat . "0";
                    }
                }
                //Add symbol
                if (!empty($formatCharacteristics["ECC_CURRENCY_SYMBOL"])) {
                    //Check where the symbol should be positioned
                    if ($formatCharacteristics["ECC_CURRENCY_SYMBOL_POSITION"] == "Before") {
                        //Check if there should be a space between the number and the symbol
                        if ($formatCharacteristics["ECC_CURRENCY_ADD_SPACE_SYMBOL"] === true) {
                            $fieldFormat = $formatCharacteristics["ECC_CURRENCY_SYMBOL"] . " " . $fieldFormat;
                        } else {
                            $fieldFormat = $formatCharacteristics["ECC_CURRENCY_SYMBOL"] . $fieldFormat;
                        }
                    } else {
                        //Check if there should be a space between the number and the symbol
                        if ($formatCharacteristics["ECC_CURRENCY_ADD_SPACE_SYMBOL"] === true) {
                            $fieldFormat = $fieldFormat . " " . $formatCharacteristics["ECC_CURRENCY_SYMBOL"];
                        } else {
                            $fieldFormat = $fieldFormat . $formatCharacteristics["ECC_CURRENCY_SYMBOL"];
                        }
                    }
                }
            }
            break;
        case "Percentage":
            //Set base format
            $fieldFormat = "0";
            if (isset($formatCharacteristics["ECC_PERCENTAGE_NUMBER_DECIMALS"])) {
                //Add decimals
                for ($i=0; $i<$formatCharacteristics["ECC_PERCENTAGE_NUMBER_DECIMALS"]; $i++) {
                    if ($i == 0) {
                        $fieldFormat = $fieldFormat . "." . "0";
                    } else {
                        $fieldFormat = $fieldFormat . "0";
                    }
                }
            }
            //Check if percentage symbol should be added
            if ($formatCharacteristics["ECC_PERCENTAGE_USE_SYMBOL"] === true) {
                $fieldFormat = $fieldFormat . "%";
            }
            break;
    }
    return $fieldFormat;
}

//Create instances
$apiInstance = $api->requestFiles();
$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();

//Create a document excel with the class PhpSpreadsheet
$sheet = $spreadsheet->getActiveSheet();

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$serverUrl = getenv('ENVIRONMENT_BASE_URL');

//Initialize variables
$documentToGenerate = $data["PE_GENERATE_EXCEL_DOCUMENT_ID"];
$processID = $data["_request"]["process_id"];
$processRequestId = $data['_request']['id'];
$documentCreator = "ProcessMaker";
$errorMessage = "";
$filename = "";
$fileGeneratedUrl = "";
$variableToSaveDocumentUrl = "";
$dataReturn = array();
$cssClassesStyles = array();
$columnsValues = array();

//Check if case is in child pm block
if (!empty($data["_parent"]["process_id"])) {
    $processID = $data["_parent"]["process_id"];
}

//Get excel configuration
$getCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                    FROM collection_" . $masterCollectionID . "
                    WHERE data->>'$.COLLECTION_NAME' = 'EXCEL_GENERAL_PROPERTIES'";
$collectionIDResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getCollectionID));
if (empty($collectionIDResponse["status"]) || $collectionIDResponse["status"] != "Error") {
    $getExcelProperties = "SELECT data->>'$.EGP_CASE_VARIABLE_FOR_DOCUMENT_URL' AS 'EGP_CASE_VARIABLE_FOR_DOCUMENT_URL',
                                  data->>'$.EGP_FILE_NAME' AS 'EGP_FILE_NAME',
                                  data->>'$.EGP_FILE_DESCRIPTION' AS 'EGP_FILE_DESCRIPTION',
                                  data->>'$.EGP_TAB_TITLE' AS 'EGP_TAB_TITLE',
                                  data->>'$.EGP_CSS_CONFIGURATION' AS 'EGP_CSS_CONFIGURATION',
                                  data->>'$.EGP_COLUMNS_CONFIGURATION' AS 'EGP_COLUMNS_CONFIGURATION'
                           FROM collection_" . $collectionIDResponse[0]["ID"] . "
                           WHERE data->>'$.EGP_PROCESS_ID' = '" . $processID . "'
                               AND data->>'$.EGP_DOCUMENT_ID' = '" . $documentToGenerate . "'
                               AND data->>'$.EGP_STATUS' = 'Active'";
    $excelProperties = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getExcelProperties));
    if (empty($excelProperties["status"]) && !empty($excelProperties[0]["EGP_FILE_NAME"])) {
        $variableToSaveDocumentUrl = $excelProperties[0]["EGP_CASE_VARIABLE_FOR_DOCUMENT_URL"];
        $filename = $excelProperties[0]["EGP_FILE_NAME"];
        //Set document general properties
        $spreadsheet->getProperties()
            ->setCreator($documentCreator)
            ->setLastModifiedBy($documentCreator)
            ->setTitle($filename)
            ->setDescription($excelProperties[0]["EGP_FILE_DESCRIPTION"])
            ->setCategory($documentCreator);
        //Set the tab title
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($excelProperties[0]["EGP_TAB_TITLE"]);
        //Create array of styles
        $cssStyles = json_decode($excelProperties[0]["EGP_CSS_CONFIGURATION"], true);
        foreach ($cssStyles as $cssConfiguration) {
            $className = $cssConfiguration["ECS_CLASS_NAME"];
            $cssClassesStyles[$className] = array();
            //Add font styles
            //Remove # from color
            $fontColor = str_replace("#", "", $cssConfiguration["ECS_FONT_COLOR"]);
            $cssClassesStyles[$className]["font"] = array(
                'bold' => $cssConfiguration["ECS_BOLD_TEXT"],
                'name' => $cssConfiguration["ECS_FONT_FAMILY"],
                'size' => $cssConfiguration["ECS_FONT_SIZE"],
                'color' => array (
                    'rgb' => $fontColor
                )
            );
            //Add alignment
            $cssClassesStyles[$className]["alignment"] = array(
                'horizontal' => $cssConfiguration["ECS_HORIZONTAL_ALIGNMENT"],
                'vertical' => $cssConfiguration["ECS_VERTICAL_ALIGNMENT"]
            );
            //Add fill color
            if ($cssConfiguration["ECS_FILLED"] === true) {
                //Remove # from color
                $cellColor = str_replace("#", "", $cssConfiguration["ECS_CELL_COLOR"]);
                $cssClassesStyles[$className]["fill"] = array(
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => array (
                        'rgb' => $cellColor
                    )
                );
            }
            //Add border color
            if ($cssConfiguration["ECS_BORDER"] === true) {
                //Remove # from color
                $borderColor = str_replace("#", "", $cssConfiguration["ECS_BORDER_COLOR"]);
                $cssClassesStyles[$className]["borders"] = array(
                    $cssConfiguration["ECS_BORDER_PATTERN"] => array(
                        'borderStyle' => $cssConfiguration["ECS_BORDER_STYLE"],
                        'color' => array (
                            'rgb' => $borderColor
                        )
                    )
                );
            }
        }
        //Draw excel rows
        $columnsValues = json_decode($excelProperties[0]["EGP_COLUMNS_CONFIGURATION"], true);
        $gridRowsToAdd = 0;
        foreach ($columnsValues as $columnsConfiguration) {
            $sheet->getColumnDimension($columnsConfiguration["ECC_COLUMN"])->setAutoSize(true);
            //Merge columns
            $columnsMergedRange = "";
            $columnID = $columnsConfiguration["ECC_COLUMN"];
            $rowID = $columnsConfiguration["ECC_ROW"];
            //Check if grid was drawn
            if ($gridRowsToAdd > 0) {
                $rowID = $rowID + $gridRowsToAdd;
            }
            $columnsToMerge = explode("-", $columnID);
            if (count($columnsToMerge) > 1) {
                $columnID = $columnsToMerge[0];
                $columnsMergedRange = $columnID . $rowID . ":" . $columnsToMerge[1] . $rowID;
                $sheet->mergeCells($columnsMergedRange);
            }
            $cellID = $columnID . $rowID;
            if ($columnsMergedRange == "") {
                $columnsMergedRange = $cellID;
            }
            //Analyze type of value
            switch($columnsConfiguration["ECC_VALUE_TYPE"]) {
                case "Label":
                    $cellCSSClassName = $columnsConfiguration["ECC_CSS_CLASS_NAME"];
                    $cellValue = $columnsConfiguration["ECC_LABEL_VALUE"];
                    if (!empty($cssClassesStyles[$cellCSSClassName])) {
                        $sheet->getStyle($cellID)->applyFromArray($cssClassesStyles[$cellCSSClassName]);
                    }
                    $sheet->setCellValue($cellID, $cellValue);
                    break;
                case "Variable":
                    $variableToUse = $columnsConfiguration["ECC_CASE_VARIABLE"];
                    //Check if variable exist in $data
                    if (!empty($data[$variableToUse])) {
                        //Analyze type of variable
                        switch ($columnsConfiguration["ECC_VARIABLE_TYPE"]) {
                            case "Text":
                                $cellCSSClassName = $columnsConfiguration["ECC_CSS_CLASS_NAME"];
                                $sheet->setCellValue($cellID, $data[$variableToUse]);
                                //Add css class
                                if (!empty($cssClassesStyles[$cellCSSClassName])) {
                                    $sheet->getStyle($columnsMergedRange)->applyFromArray($cssClassesStyles[$cellCSSClassName]);
                                }
                                break;
                            case "Date":
                                $cellCSSClassName = $columnsConfiguration["ECC_CSS_CLASS_NAME"];
                                $desiredFormat = $columnsConfiguration["ECC_DATE_FORMAT"];
                                //Transform value to the desired format
                                $formatedValue = date($desiredFormat, strtotime($data[$variableToUse]));
                                $sheet->setCellValue($cellID, $formatedValue);
                                //Add css class
                                if (!empty($cssClassesStyles[$cellCSSClassName])) {
                                    $sheet->getStyle($columnsMergedRange)->applyFromArray($cssClassesStyles[$cellCSSClassName]);
                                }
                                break;
                            case "Currency":
                                $cellCSSClassName = $columnsConfiguration["ECC_CSS_CLASS_NAME"];
                                $desiredFormat = buildFieldFormat("Currency", $columnsConfiguration);
                                //Set special format to currency in cell
                                $sheet->getStyle($cellID)->getNumberFormat()->setFormatCode($desiredFormat);
                                $sheet->setCellValue($cellID, $data[$variableToUse]);
                                //Add css class
                                if (!empty($cssClassesStyles[$cellCSSClassName])) {
                                    $sheet->getStyle($columnsMergedRange)->applyFromArray($cssClassesStyles[$cellCSSClassName]);
                                }
                                break;
                            case "Percentage":
                                $cellCSSClassName = $columnsConfiguration["ECC_CSS_CLASS_NAME"];
                                $desiredFormat = buildFieldFormat("Percentage", $columnsConfiguration);
                                //Set special format to currency in cell
                                $sheet->getStyle($cellID)->getNumberFormat()->setFormatCode($desiredFormat);
                                //Check if value should be formatted with symbol
                                $percentageValue = $data[$variableToUse];
                                if (strpos($desiredFormat, "%") !== false) {
                                    $percentageValue = $percentageValue / 100;
                                }
                                $sheet->setCellValue($cellID, $percentageValue);
                                //Add css class
                                if (!empty($cssClassesStyles[$cellCSSClassName])) {
                                    $sheet->getStyle($columnsMergedRange)->applyFromArray($cssClassesStyles[$cellCSSClassName]);
                                }
                                break;
                            case "Array":
                                //Define titles styles
                                $cssClassNamesTitles = $columnsConfiguration["ECC_CSS_CLASS_NAME_TABLE_TITLES"];
                                $cssClassNamesTitles = explode("|", $cssClassNamesTitles);
                                //Define column titles
                                $tableTitles = $columnsConfiguration["ECC_TABLE_TITLES"];
                                $tableTitles = explode("|", $tableTitles);
                                //Define table values
                                $tableVariables = $columnsConfiguration["ECC_TABLE_VARIABLES"];
                                //Draw Titles
                                $columnTitles = $columnID;
                                foreach ($tableTitles as $key=>$title) {
                                    $cellID = $columnTitles . $columnsConfiguration["ECC_ROW"];
                                    $sheet->setCellValue($cellID, $title);
                                    //Add css class
                                    if (!empty($cssClassesStyles[$cssClassNamesTitles[$key]])) {
                                        $sheet->getStyle($cellID)->applyFromArray($cssClassesStyles[$cssClassNamesTitles[$key]]);
                                    }
                                    //Increase one column
                                    $columnTitles = ++$columnTitles;
                                    $sheet->getColumnDimension($columnTitles)->setAutoSize(true);
                                }
                                //Draw Table Body
                                $gridToDraw = $data[$variableToUse];
                                //Add row for grid body
                                $gridRowsToAdd = $gridRowsToAdd + count($gridToDraw); 
                                $rowID = $rowID + 1;
                                //Loop grid
                                foreach ($gridToDraw as $gridKey=>$gridRow) {
                                    $columnVariables = $columnID;
                                    //Loop table variables
                                    foreach ($tableVariables as $key=>$variable) {
                                        //Check if variable exist in array
                                        if (!empty($gridToDraw[$gridKey][$variable["ECC_ARRAY_VARIABLE_ID"]])) {
                                            $arrayValue = $gridToDraw[$gridKey][$variable["ECC_ARRAY_VARIABLE_ID"]];
                                            if (gettype($arrayValue) != "array") {
                                                $cellID = $columnVariables . $rowID;
                                                //Transform value to the desired format
                                                switch ($variable["ECC_ARRAY_VARIABLE_TYPE"]) {
                                                    case "Date":
                                                        $formatedValue = date($columnsConfiguration["ECC_DATE_FORMAT"], strtotime($arrayValue));
                                                        $sheet->setCellValue($cellID, $formatedValue);
                                                        break;
                                                    case "Currency":
                                                        $desiredFormat = buildFieldFormat("Currency", $columnsConfiguration);
                                                        $sheet->getCell($cellID)->getStyle()->getNumberFormat()->setFormatCode($desiredFormat);
                                                        $sheet->setCellValue($cellID, $arrayValue);
                                                        break;
                                                    case "Percentage":
                                                        $cellCSSClassName = $columnsConfiguration["ECC_CSS_CLASS_NAME"];
                                                        $desiredFormat = buildFieldFormat("Percentage", $columnsConfiguration);
                                                        //Check if value should be formatted with symbol
                                                        $percentageValue = $arrayValue;
                                                        if (strpos($desiredFormat, "%") !== false) {
                                                            $percentageValue = $percentageValue / 100;
                                                        }
                                                        //Set special format to currency in cell
                                                        $sheet->getCell($cellID)->getStyle()->getNumberFormat()->setFormatCode($desiredFormat);
                                                        $sheet->setCellValue($cellID, $percentageValue);
                                                        //Add css class
                                                        if (!empty($cssClassesStyles[$cellCSSClassName])) {
                                                            $sheet->getStyle($cellID)->applyFromArray($cssClassesStyles[$cellCSSClassName]);
                                                        }
                                                        break;
                                                    default:
                                                        $sheet->setCellValue($cellID, $arrayValue);
                                                        break;
                                                }
                                            }
                                            //Add css class
                                            if (!empty($cssClassesStyles[$variable["ECC_ARRAY_VARIABLE_CSS_CLASS"]])) {
                                                $sheet->getStyle($cellID)->applyFromArray($cssClassesStyles[$variable["ECC_ARRAY_VARIABLE_CSS_CLASS"]]);
                                            }
                                            //Increase one column
                                            $columnVariables = ++$columnVariables;
                                        }
                                    }
                                    $rowID++;
                                }
                                break;
                        }
                        break;
                    }
                    break;
                case "Image":
                    $imageUrl = $serverUrl . $columnsConfiguration["ECC_IMAGE_URL"];
                    $imageContent = file_get_contents($imageUrl);
                    $tempImagePath = '/tmp/tempImage.png';
                    file_put_contents($tempImagePath, $imageContent);
                    $imageInstance = new PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                    $imageInstance->setPath($tempImagePath);
                    $imageInstance->setCoordinates($cellID); //Set image to cell
                    //Set image with and height 6cm x 1.5cm by default
                    $imageWidth = empty($columnsConfiguration["ECC_IMAGE_WIDTH"]) ? 180 : $columnsConfiguration["ECC_IMAGE_WIDTH"];
                    $imageHeight = empty($columnsConfiguration["ECC_IMAGE_HEIGHT"]) ? 55 : $columnsConfiguration["ECC_IMAGE_HEIGHT"];
                    $imageInstance->setWidth($imageWidth);
                    $imageInstance->setHeight($imageHeight);
                    $imageInstance->setWorksheet($sheet);
                    $sheet->getRowDimension($rowID)->setRowHeight($imageHeight);
                    break;
            }
        }
    } else {
        $errorMessage = "There is not any configuration for the document you are trying to generate, please contact your system administrator.";
    }
} else {
    $errorMessage = "Collection of excel configuration is not correctly configured.";
}
if ($errorMessage == "") {
    //Save the document
    $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $outFile = '/tmp/' . $filename . '.xlsx';
    $writer->save($outFile);
        
    //Attach the file on the request
    $newFile = $apiInstance->createRequestFile($processRequestId, $filename, $outFile);
    $fileUID = $newFile->getFileUploadId();
        
    $fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;
}
//Check if variable to save document url was configured
if ($variableToSaveDocumentUrl != "") {
    $dataReturn[$variableToSaveDocumentUrl] = $fileGeneratedUrl;
}
$dataReturn["ERROR_MESSAGE"] = $errorMessage;
return $dataReturn;