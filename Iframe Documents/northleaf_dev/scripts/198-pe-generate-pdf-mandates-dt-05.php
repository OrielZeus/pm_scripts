<?php

/**********************************************
 * PE - Generate PDF Mandates after of DT.05
 *
 * by Luz Nina
 * modified by Adriana Centellas
 *********************************************/
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");

$apiInstance = $api->requestFiles();

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$northleafLogoUrl = getenv('ORIGINAL_URL_PDF_LOGO');
$cherryCheckUrl = getenv("CHECK_IMAGE_FOR_DOCUMENTS_URL");
$serverUrl = getenv('ENVIRONMENT_BASE_URL');

//Initialize variables
$processRequestId = $data['_request']['id'];
if ($data["PE_CURRENCY"] == "Other") {
                    $currency = $data["PE_OTHER_CURRENCY"];
                } else {
                    $currency = $data["PE_CURRENCY"];
                }
$pdfName = "Final Allocation (" . $currency . ").pdf";
$outputDocumentTempPath = '/tmp/' . $pdfName;
$dataToGeneratePDF = $data;

//Generate the PDF
$dataToGeneratePDF["NORTHLEAF_LOGO_URL"] = $northleafLogoUrl;

generatePDF($outputDocumentTempPath, $dataToGeneratePDF, $apiUrl);

//Add pdf to request
$newFile = $apiInstance->createRequestFile($processRequestId, $pdfName, $outputDocumentTempPath);
$fileUID = $newFile->getFileUploadId();
$fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;

return array(
    "PE_MANDATES_PDF" => $fileGeneratedUrl,
    "PE_MANDATES_PDF_ID" => $fileUID
);

/**
 * Add Section Title
 *
 * @param string $title
 * @return $sectionTitleHTML
 *
 * by Cinthia Romero
 * modified by Luz Nina
 */
function addSectionTitle($title)
{
    $sectionTitleHTML = "<tr>";
    $sectionTitleHTML .= "<td colspan='2'></td>";
    $sectionTitleHTML .= "</tr>";
    $sectionTitleHTML .= "<tr>";
    $sectionTitleHTML .= "<td colspan='2' class='subtitleRow'>" . $title . "</td>";
    $sectionTitleHTML .= "</tr>";
    $sectionTitleHTML .= "<tr>";
    $sectionTitleHTML .= "<td colspan='2'></td>";
    $sectionTitleHTML .= "</tr>";
    $sectionTitleHTML .= "<tr>";
    $sectionTitleHTML .= "<td colspan='2'></td>";
    $sectionTitleHTML .= "</tr>";
    return $sectionTitleHTML;
}

/**
 * Generate PDF
 *
 * @param string $outputDocumentTempPath
 * @param array $data
 * @return none
 *
 * by Cinthia Romero
 * modified by Luz Nina
 */
function generatePDF($outputDocumentTempPath, $data)
{
    $processRequestId = $data['_request']['id'];
    $image = file_get_contents($data['NORTHLEAF_LOGO_URL']);
    file_put_contents('/tmp/logo.png', $image);
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $html2pdf->setTestTdInOnePage(false);
    $pdfHTML = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Private Equity Deal Closing Document 1</title>
            <style>
                body {
                    font-family: Arial, sans-serif; 
                }
                .titleRow {
                    font-size: 14px;
                    background-color: #711426;
                    color:white;
                    font-weight: bold;
                    padding: 2px;
                }
                .subtitleRow {
                    font-size: 13px;
                    border-bottom: 2px solid #711426;
                    color:#711426;
                    font-weight: bold;
                    padding: 2px;
                }
                .titleGrid {
                    font-size: 12px;
                    text-align:center;
                    border:1px solid #222;
                    font-weight: bold;
                    padding: 2px;
                }
                .textGrid {
                    font-size: 12px;
                    text-align:center;
                    border:1px solid #222;
                    padding: 2px;
                    vertical-align: middle;
                }
                .label {
                    font-size: 12px;
                    text-align:right;
                    width:50%;
                    font-weight: bold;
                    padding: 2px;
                }
                .text {
                    font-size: 12px;
                    text-align:left;
                    width:50%;
                    padding: 2px;
                }
            </style>
        </head>
        <body>";
    //Build PDF Body
    $pdfHTML .= "<table style='width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2' style='text-align:center;'><img src='/tmp/logo.png' height='50'/></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'><br></td>";
    $pdfHTML .= "</tr>";
    //Add section 'Documents'
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td>";
    $pdfHTML .= "<br>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle('Mandates');
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:25%'>Mandate</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:25%'>IC Approved Amount</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:25%'>Actual Allocation</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:25%'>% of Deal</th>";
    $pdfHTML .= "</tr>";
    if ($data["PE_CURRENCY"] == "Other") {
                    $currency = $data["PE_OTHER_CURRENCY"];
                } else {
                    $currency = $data["PE_CURRENCY"];
                }
        if (isset($data['PE_MANDATES']) && !empty($data['PE_MANDATES'])) {
            foreach ($data['PE_MANDATES'] as $row) {
                $pdfHTML .= "<tr>";
                $pdfHTML .= "<td class='textGrid' style='width:25%'>" . $row["PE_MANDATE_NAME"] . "</td>";
                $pdfHTML .= "<td class='textGrid' style='width:25%'>". $currency . " " . number_format($row["PE_MANDATE_AMOUNT"], 2) . "</td>";
                $pdfHTML .= "<td class='textGrid' style='width:25%'>". $currency . " " . number_format($row["PE_MANDATE_ACTUAL_ALLOCATION"], 2) . "</td>";
                $pdfHTML .= "<td class='textGrid' style='width:25%'>" . number_format($row["PE_MANDATE_PERCENTAGE_DEAL"], 2) . '%' . "</td>";
                $pdfHTML .= "</tr>";
            }
        }
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='titleGrid' style='width:25%; color: #711426;'>" . 'Total' . "</td>";
        $pdfHTML .= "<td class='titleGrid' style='width:25%'>". $currency . " " . number_format($data["PE_MANDATE_TOTAL_AMOUNT"], 2) . "</td>";
        $pdfHTML .= "<td class='titleGrid' style='width:25%'>". $currency . " " . number_format($data["PE_MANDATE_TOTAL_ACTUAL_ALLOCATION"], 2) . "</td>";
        $pdfHTML .= "<td class='titleGrid' style='width:25%'>" . number_format($data["PE_MANDATE_TOTAL_PERCENTAGE_ACTUAL_ALLOCATION"], 2) . '%' . "</td>";
        $pdfHTML .= "</tr>";

    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    //End of section 'Documents'

    $pdfHTML .= "</table>";
    $pdfHTML .= "</body>";
    $pdfHTML .= "</html>";

    // Create the PDF
    $html2pdf->writeHTML($pdfHTML);

    // Save the PDF to the specified path
    $html2pdf->output($outputDocumentTempPath, 'F');
}