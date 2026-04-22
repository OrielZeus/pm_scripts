<?php
/**
 * Portfolio_Manager_Authorization_PDF PA.01
 * Created by Elmer Orihuela
 * Modified by Adriana Centellas
 * This script generates a PDF document for portfolio manager approval using HTML2PDF.
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");

// Retrieve environment variables for API
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');

// Retrieve server host URL
$host = $_SERVER["HOST_URL"];
$sqlUrl = $apiHost . $apiSql;

// Retrieve portfolio manager approver information
$portfolioManagerApprover = $data['PE_PORTFOLIO_MANAGER_APPROVER'];
$getUserInfoUrl = $apiHost . "/users/" . $portfolioManagerApprover;
$portfolioManagerApproverInfo = callApiUrlGuzzle($getUserInfoUrl, 'GET', []);

// Prepare variables for the PDF
$pdfVariables = [
    "northImage" => getenv('ORIGINAL_URL_PDF_LOGO'),
    "approverName" => $portfolioManagerApproverInfo['firstname'] . ' ' . $portfolioManagerApproverInfo['lastname'],
    "approverTitle" => $portfolioManagerApproverInfo['title'],
    "approverSignature64" => $portfolioManagerApproverInfo['meta']['signature'],
    "currentDate" => date('m/d/Y'),
    "PE_RE_TEXT" => $data['PE_RE_TEXT'],
    "PE_UNDERSIGNED_TEXT" => $data['PE_UNDERSIGNED_TEXT'],
    "PE_MANDATES" => $data['PE_MANDATES'],
    "PE_COLLECTIVELY_TEXT" => $data['PE_COLLECTIVELY_TEXT'],
    "PE_INVESTMENT_COMMITTEE_TEXT" => $data['PE_INVESTMENT_COMMITTEE_TEXT'],
];

/**
 * Converts a base64 string to an image and saves it as a temporary file.
 *
 * @param string $base64String The base64 encoded image string.
 * @return string The path to the temporary image file.
 * Created by Elmer Orihuela
 * Modified by Adriana Centellas
 */
function createTempImage($base64String)
{
    try {
        // Default extension (will be adjusted if needed)
        $extension = 'png';

        // Detect and strip data URI headers
        if (strpos($base64String, 'data:image/png;base64,') === 0) {
            $base64String = str_replace('data:image/png;base64,', '', $base64String);
            $extension = 'png';
        } elseif (strpos($base64String, 'data:image/jpeg;base64,') === 0 || 
                  strpos($base64String, 'data:image/jpg;base64,') === 0) {
            $base64String = str_replace(['data:image/jpeg;base64,', 'data:image/jpg;base64,'], '', $base64String);
            $extension = 'jpg';
        }

        // Replace spaces with "+" in case the string was URL-encoded
        $base64String = str_replace(' ', '+', $base64String);

        // Generate a temporary file path
        $tempImagePath = tempnam(sys_get_temp_dir(), 'sig_') . '.' . $extension;

        // Decode base64 string
        $imageData = base64_decode($base64String);
        if ($imageData === false) {
            throw new Exception('Base64 decoding failed.');
        }

        // Write image data to file
        if (file_put_contents($tempImagePath, $imageData) === false) {
            throw new Exception('Failed to write image file.');
        }

        // Check if the file is a valid image
        if (!getimagesize($tempImagePath)) {
            unlink($tempImagePath); // Remove the invalid file
            throw new Exception('Invalid image file.');
        }

        return $tempImagePath;
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Generates a PDF document using HTML2PDF.
 *
 * @param string $pathOutput The output path for the PDF file.
 * @param array $data The data to be included in the PDF.
 */
function generatePDF($pathOutput, $data)
{
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $html2pdf->setTestTdInOnePage(false);
    // Create temporary image file for signature
    $signaturePath = createTempImage($data['approverSignature64']);
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);

    // HTML content for the first page
    $page1 = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Northleaf - Page 1</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .content { font-size: 12px; }
            .list { margin-left: 20px; }
            .list li { margin-bottom: 5px; }
            .header-text { color: #711426; text-decoration: underline; padding-bottom: 5px; font-size: 24px; font-weight: bold;}
            .custom-style {
                font-size: 16px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="/tmp/logo.png" alt="Northleaf Logo" height="50">
            <p class="header-text">Portfolio Manager Review and Authorization</p>
        </div>
        <div class="content">
            <p class="custom-style">Re: <strong>' . $data['PE_RE_TEXT'] . '</strong></p>
            <p class="custom-style">' . $data['PE_UNDERSIGNED_TEXT'] . '</p>
            <ul class="list">';
    if (count($data['PE_MANDATES']) > 0) {
        foreach ($data['PE_MANDATES'] as $mandatesCompleteName) {
            if ($mandatesCompleteName['PE_MANDATE_CO_INVESTOR'] != 'YES') {
                // New validation tlx
                $mandateNameToShow = (isset($mandatesCompleteName["PE_MANDATE_FUND_NAME"]) && $mandatesCompleteName["PE_MANDATE_FUND_NAME"] != "") ? $mandatesCompleteName["PE_MANDATE_FUND_NAME"] : $mandatesCompleteName['PE_MANDATE_ENTITY_DOCUMENT'];
                $page1 .= '<li class="custom-style">' . $mandateNameToShow . '</li>';
            }
        }
    }
    $page1 .= '</ul>
            <br/>';
    // Condition text according to the number of Mandates selected
    if (count($data['PE_MANDATES']) > 0) {
        if (count($data['PE_MANDATES']) == 1) {
            $page1 .= '<p class="custom-style">(, the “<strong>Fund</strong>”)</p>';
        } else {
            $page1 .= '<p class="custom-style">(collectively, the “<strong>Funds</strong>”)</p>';
        }
    }
    $page1 .= '<br/>
            <p class="custom-style">' . $data['PE_INVESTMENT_COMMITTEE_TEXT'] . '</p>
            <div class="signature">
                <p class="custom-style">Signed by:</p>
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:left;width:50%;border-bottom: 1px solid;vertical-align: bottom;"><img src="' . $signaturePath . '" alt="Firma"
                            style="margin-right: 10px; height: 50px;"></td>
                        <td style="text-align:right;width:10%;vertical-align: bottom;font-size: 16px;"><p>Date:</p></td>
                        <td style="text-align:left;width:40%; border-bottom: 1px solid;vertical-align: bottom;font-size: 16px;"><p>' . $data['currentDate'] . '</p></td>
                    </tr>
                </table>
                <p class="custom-style">' . $data['approverName'] . ', ' . $data['approverTitle'] . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    // Create the PDF
    $html2pdf->writeHTML($page1);
    //HTML content for the second page (if needed)
    // $page2 = '';
    // $html2pdf->writeHTML($page2);
    
    // Save the PDF to the specified path
    $html2pdf->output($pathOutput, 'F');
}

// Set the output path for the PDF file
$pathOutput = '/tmp/Portfolio_Manager_Authorization.pdf';

// Generate the PDF
generatePDF($pathOutput, $pdfVariables);

// Continue with the API processing
$processRequestId = $data['_request']['id'];

// Define variable into data
$dataName = 'PA_PDF';
$data['pdfName'] = $dataName;

// Create a new request file
$apiInstance = $api->requestFiles();
$newFile = $apiInstance->createRequestFile($processRequestId, $dataName, $pathOutput);

$data["PA_PDF_ID"] = $newFile->getFileUploadId();


// Retrieve all request files using the current request ID
/*
$requestFiles = $api->requestFiles()->getRequestFiles($data['_request']['id'])['data'];

if (count($requestFiles) > 0) {
    // Group files by their 'data_name' (spaces removed)
    $groupedFiles = [];

    foreach ($requestFiles as $requestFile) {
        // Remove spaces from the 'data_name' to use as the array key
        $key = str_replace(' ', '', $requestFile['custom_properties']['data_name']);

        // Append the file ID to the list for this key
        $groupedFiles[$key][] = $requestFile['id'];
    }

    // Process each group to assign to the final $data array
    foreach ($groupedFiles as $key => $ids) {
        if (count($ids) === 1) {
            // If there is only one file, store the ID directly
            $data[$key] = $ids[0];
        } else {
            // If there are multiple files, store them as an array of objects
            $data[$key] = array_map(function($id) {
                return ['file' => $id];
            }, $ids);
        }
    }
}*/

return $data;