<?php 
/**********************************
 * PE - LC.06 Generate Final Excel
 *
 * by Favio Mollinedo
 * modified by Adriana Centellas
 *********************************/
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

//Initialize variables
$processRequestId = $data['_request']['id'];
//$currentCaseNumber = $data['_request']['case_number'];
$excelName = "Final_Allocation.xlsx";
$outputDocumentTempPath = '/tmp/' . $excelName;
$mandateDataArray = empty($data["PE_MANDATES"]) ? [] : $data["PE_MANDATES"];

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set up Project Name and data in the first row
$sheet->setCellValue('A1', 'Project / Investment Name:')
      ->setCellValue('B1', $data["PE_PROJECT_INVESTMENT_NAME"]);

// Apply formats
$sheet->getStyle('A1:B1')->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

// Set up subtitle with style and data in the second row
$sheet->setCellValue('A2', 'Final Allocation');

// Apply color al subtitle
$sheet->getStyle('A2')->applyFromArray([
    /*'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFFFFF'], // White color
    ],*/
    'font' => [
        'color' => ['rgb' => '800000'], // Slate color
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_LEFT,
    ],
]);

// Create table from row 3 - Heeaders
$sheet->setCellValue('A3', 'Mandate')
      ->setCellValue('B3', 'IC Approved Amount')
      ->setCellValue('C3', 'Actual Allocation')
      ->setCellValue('D3', '% of Deal')
      ->setCellValue('E3', 'Entity');

// Apply formats
$sheet->getStyle('A3:E3')->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

$rowIndex = 4; // From row 4
//Data iteration
foreach ($mandateDataArray as $index => $row) {
    $columnIndex = 1; // Reset to column A
    $sheet->setCellValueByColumnAndRow(($columnIndex), $rowIndex, $row["PE_MANDATE_NAME"]);
    $sheet->setCellValueByColumnAndRow(($columnIndex+1), $rowIndex, number_format((float)$row["PE_MANDATE_AMOUNT"], 2, '.', ''));
    $sheet->setCellValueByColumnAndRow(($columnIndex+2), $rowIndex, number_format((float)$row["PE_MANDATE_ACTUAL_ALLOCATION"], 2, '.', ''));
    $sheet->setCellValueByColumnAndRow(($columnIndex+3), $rowIndex, round($row["PE_MANDATE_PERCENTAGE_DEAL"], 2));
    $sheet->setCellValueByColumnAndRow(($columnIndex+4), $rowIndex, $row["PE_MANDATE_ENTITY"]);

    // Set up decimals to amounts
    $sheet->getStyle("B{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    $sheet->getStyle("C{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

    // Set up percentage format to columns
    //$sheet->getStyle("D{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

    $rowIndex++;
}

// Table formats
$rowIndex = $rowIndex-1;
$sheet->getStyle("A3:E{$rowIndex}")->applyFromArray([
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'], // Black
        ],
    ]/*,
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],*/
]);

// Set up totals
$rowIndex = $rowIndex+1;
$sheet->setCellValue("A{$rowIndex}", "Total")
      ->setCellValue("B{$rowIndex}", number_format((float)$data["PE_MANDATE_TOTAL_AMOUNT"], 2, '.', ''))
      ->setCellValue("C{$rowIndex}", number_format((float)$data["PE_MANDATE_TOTAL_ACTUAL_ALLOCATION"], 2, '.', ''))
      ->setCellValue("D{$rowIndex}", round($data["PE_MANDATE_TOTAL_PERCENTAGE_ACTUAL_ALLOCATION"], 2));

// Set up decimals to amounts
$sheet->getStyle("B{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
$sheet->getStyle("C{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

// Set up percentage format to columns
///$sheet->getStyle("D{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE);

// Apply formats
$sheet->getStyle("A{$rowIndex}")->applyFromArray([
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);

// Auto Size
foreach (range('A', 'E') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Secondaries Deal Team
if (isset($data['PE_DEAL_TYPE']) && $data['PE_DEAL_TYPE'] == "Secondary" && false) {
    $excelName1 = "Final_Secondaries_Deal_Team.xlsx";
    $outputDocumentTempPath1 = '/tmp/' . $excelName1;
    $dataArray = empty($data["PE_SECONDARY_FUNDS_LOOP"]) ? [] : $data["PE_SECONDARY_FUNDS_LOOP"];

    $spreadsheet1 = new Spreadsheet();
    $sheet1 = $spreadsheet1->getActiveSheet();

    $sheet1->setCellValue('A1', 'Project / Investment Name:')
        ->setCellValue('B1', $data["PE_PROJECT_INVESTMENT_NAME"]);

    $sheet1->getStyle('A1:B1')->applyFromArray([
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    $sheet1->setCellValue('A2', 'Final Secondaries - Deal Team');

    $sheet1->getStyle('A2')->applyFromArray([
        'font' => [
            'color' => ['rgb' => '800000'],
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
        ],
    ]);

    $sheet1->setCellValue('A3', 'Fund Legal Name')
        ->setCellValue('B3', 'GP')
        ->setCellValue('C3', 'Reporting Frequency');

    $sheet1->getStyle('A3:C3')->applyFromArray([
        'font' => [
            'bold' => true,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    $rowIndex1 = 4;
    foreach ($dataArray as $index => $row) {
        $columnIndex1 = 1;
        $sheet1->setCellValueByColumnAndRow(($columnIndex1), $rowIndex1, $row["PE_SECONDARY_FUND_LEGAL"]);
        $col2 = empty($row["PE_SECONDARY_GP"]) ? $row["PE_SECONDARY_NEW_GP"] : $row["PE_SECONDARY_GP"];
        $sheet1->setCellValueByColumnAndRow(($columnIndex1 + 1), $rowIndex1, $col2);
        $sheet1->setCellValueByColumnAndRow(($columnIndex1 + 2), $rowIndex1, $row["PE_SECONDARY_REPORTING_FREQUENCY"]);
        
        $rowIndex1++;
    }

    $rowIndex1 = $rowIndex1 - 1;
    $sheet1->getStyle("A3:C{$rowIndex1}")->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => '000000'],
            ],
        ]
    ]);
    foreach (range('A', 'C') as $columnID) {
        $sheet1->getColumnDimension($columnID)->setAutoSize(true);
    }
}
// End Secondaies Deal Team

$dataReturn = [];
try {
    //Save Excel File
    $writer = new Xlsx($spreadsheet);
    $writer->save($outputDocumentTempPath);

    //Add excel to the request
    $apiInstance = $api->requestFiles();
    $newFile = $apiInstance->createRequestFile($processRequestId, $excelName, $outputDocumentTempPath);
    $fileUID = $newFile->getFileUploadId();
    $fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;
    $dataReturn["FINAL_EXCEL_ID"] = $fileUID;
    $dataReturn["FINAL_EXCEL_URL"] = $fileGeneratedUrl;

    //Secondaies Deal Team
    if (isset($data['PE_DEAL_TYPE']) && $data['PE_DEAL_TYPE'] == "Secondary" && false) {
        $writer1 = new Xlsx($spreadsheet1);
        $writer1->save($outputDocumentTempPath1);

        //$apiInstance = $api->requestFiles();
        $newFile1 = $apiInstance->createRequestFile($processRequestId, $excelName1, $outputDocumentTempPath1);
        $fileUID1 = $newFile1->getFileUploadId();
        $fileGeneratedUrl1 = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID1;
        $dataReturn["FINAL_SECONDARIES_EXCEL_ID"] = $fileUID1;
        $dataReturn["FINAL_SECONDARIES_EXCEL_URL"] = $fileGeneratedUrl1;
    }
    //End Secondaies Deal Team

} catch (Exception $e) {
    $dataReturn["FINAL_EXCEL_ID"] = null;
    $dataReturn["FINAL_EXCEL_URL"] = null;
    $dataReturn["FINAL_EXCEL_ERROR"] = $e->getMessage();

    $dataReturn["FINAL_SECONDARIES_EXCEL_ID"] = null;
    $dataReturn["FINAL_SECONDARIES_EXCEL_URL"] = null;
}

return $dataReturn;