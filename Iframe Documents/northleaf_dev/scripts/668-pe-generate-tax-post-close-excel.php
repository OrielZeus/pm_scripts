<?php 
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
$excelName = "Tax_Post_Close.xlsx";
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
$sheet->setCellValue('A2', 'Tax Post-Close');

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
$sheet->setCellValue('A3', 'Fund Legal Name')
      ->setCellValue('B3', 'Is the tax entity same as the legal entity?')
      ->setCellValue('C3', 'If No, what is the tax entity?')
      ->setCellValue('D3', 'Is there an alternative investment vehicle ("AIV")?')
      ->setCellValue('E3', 'If yes, what is the tax entity?')
      ->setCellValue('F3', 'Tax Domicile country');

// Apply formats
$sheet->getStyle('A3:F3')->applyFromArray([
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

foreach ($data["FUND_LEGAL_NAME_LOOP"] as $index => $fundLegal) {
    $columnIndex = 1; // Reset to column A
    $sheet->setCellValueByColumnAndRow(($columnIndex), $rowIndex, $fundLegal["FUND_LEGAL_NAME"]);
    $sheet->setCellValueByColumnAndRow(($columnIndex+1), $rowIndex, $fundLegal["TAX_ENTITY_LEGAL_ENTITY"]);
    $sheet->setCellValueByColumnAndRow(($columnIndex+2), $rowIndex, $fundLegal["ENTITY"]);
    $sheet->setCellValueByColumnAndRow(($columnIndex+3), $rowIndex, $fundLegal["ALTERNATIVE_INVESTMENT_VEHICLE"]);

    $entityAivHTML = "";
    if (!empty($fundLegal["ENTITY_AIV_ARRAY"])) {
        foreach ($fundLegal["ENTITY_AIV_ARRAY"] as $k => $entityAiv) {
            $entityAivHTML .= ($k < count($fundLegal["ENTITY_AIV_ARRAY"]) -1) ? $entityAiv["ENTITY_AIV"]."\n" : $entityAiv["ENTITY_AIV"];
            //$entityAivHTML .= $entityAiv["ENTITY_AIV"]."\n";
        }
    }
    $sheet->setCellValueByColumnAndRow(($columnIndex+4), $rowIndex, $entityAivHTML);

    $PE_TAX_DOMICILE_COUNTRY_NA = ($fundLegal["PE_TAX_DOMICILE_COUNTRY_NA"] == true) ? "N/A" : $fundLegal["PE_TAX_DOMICILE_COUNTRY"];
    $sheet->setCellValueByColumnAndRow(($columnIndex+5), $rowIndex, $PE_TAX_DOMICILE_COUNTRY_NA);

    $sheet->getStyle("E{$rowIndex}")->getAlignment()->setWrapText(true);

    $sheet->getStyle("A{$rowIndex}:F{$rowIndex}")->applyFromArray([
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);

    $rowIndex++;
}

// Table formats
$rowIndex = $rowIndex-1;
$sheet->getStyle("A3:F{$rowIndex}")->applyFromArray([
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

// Auto Size
foreach (range('A', 'F') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

$dataReturn = [];
try {
    //Save Excel File
    $writer = new Xlsx($spreadsheet);
    $writer->save($outputDocumentTempPath);

    //Add excel to the request
    $apiInstance = $api->requestFiles();
    $newFile = $apiInstance->createRequestFile($processRequestId, $excelName, $outputDocumentTempPath);
    $fileUID = $newFile->getFileUploadId();
    $fileGeneratedUrl = ($serverUrl ?? '') . 'request/' . $processRequestId . '/files/' . $fileUID;
    $dataReturn["TAX_POST_CLOSE_FILE_ID"] = $fileUID;
    $dataReturn["TAX_POST_CLOSE_FILE_URL"] = $fileGeneratedUrl;

} catch (Exception $e) {
    $dataReturn["TAX_POST_CLOSE_FILE_ID"] = null;
    $dataReturn["TAX_POST_CLOSE_FILE_URL"] = null;
    $dataReturn["FINAL_EXCEL_ERROR"] = $e->getMessage();
}

return $dataReturn;