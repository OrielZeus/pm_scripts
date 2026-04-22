<?php
/**
 * funding_request_and_authorization IC 02
 * Created by Elmer Orihuela
 * Modified by Adriana Centellas
 * This script generates a PDF document for portfolio manager approval using HTML2PDF.
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");

/**
 * Formats a date to 'm/d/Y'. If the date includes time, it formats to 'm/d/Y H:i:s'.
 *
 * @param string $date The date to format.
 * @return string The formatted date.
 * Created by Elmer Orihuela
 */
function formatDate($date)
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'Invalid date format';
    }

    // Check if the original date includes a time component
    if (strpos($date, ':') !== false) {
        return date('m/d/Y H:i:s', $timestamp);
    } else {
        return date('m/d/Y', $timestamp);
    }
}
/**
 * Formats a number as currency with two decimal places and a dollar sign.
 *  //return $currency . ' ' . number_format((float)$number, 2, '.', ',');
 *
 * @param mixed $number The number to format.
 * @return string The value formatted as currency.
 * Created by Elmer Orihuela
 */
function formatCurrency($number)
{
    if (!is_numeric($number)) {
        return 'Invalid number format';
    }
    return number_format((float) $number, 2, '.', ',');
}

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
 * Creates a blank temporary image.
 *
 * @return string The path to the temporary blank image file.
 * Created by Elmer Orihuela
 */
function createBlankTempImage()
{
    $blankBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAArCAYAAAA65tviAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAfSURBVGhD7cExAQAAAMKg9U9tDB8gAAAAAAAAAAC+aiHDAAHaYQoFAAAAAElFTkSuQmCC';
    return createTempImage($blankBase64);
}
/**
 * Generates a PDF document using HTML2PDF.
 *
 * @param string $pathOutput The output path for the PDF file.
 * @param array $data The data to be included in the PDF.
 * @param bool $withoutSignature Flag to determine if signatures should be omitted.
 * Created by Elmer Orihuela
 * Modified by Telmo Chiri
 */
function generatePDF($pathOutput, $data, $withoutSignature)
{
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $html2pdf->setTestTdInOnePage(false);
    // Create temporary image file for signature if required
    if (!$withoutSignature) {   // Will all signatures
        $signaturePathPrimary = createTempImage($data['PE_PRIMARY_AUTHORIZATION']['meta']['signature']);
        $signaturePathSecondary = createTempImage($data['PE_SECONDARY_AUTHORIZATION']['meta']['signature']);
        $currentDatePrimary = $data['PE_CONFIRMATION_DATE_IC2_PRIMARY'];
        $currentDateSecondary = $data['PE_CONFIRMATION_DATE_IC2_SECONDARY'];
    } else {
        if ($data['PE_IC_APPROVER_TYPE'] == 'SECONDARY') {
            $signaturePathPrimary = createTempImage($data['PE_PRIMARY_AUTHORIZATION']['meta']['signature']);
            $signaturePathSecondary = createBlankTempImage();
            $currentDatePrimary = $data['PE_CONFIRMATION_DATE_IC2_PRIMARY'];
            $currentDateSecondary = '';
        } else {
            $signaturePathPrimary = $signaturePathSecondary = createBlankTempImage();
            $currentDatePrimary = $currentDateSecondary = '';
        }
    }
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);

    // HTML content for the first page
    $page1 = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Northleaf - Funding Request and Authorization</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .content { font-size: 12px; }
            .list { margin-left: 20px; }
            .list li { margin-bottom: 5px; }
            .header-text { color: #711426; text-decoration: underline; padding-bottom: 5px; font-size: 24px; font-weight: bold;}
            .custom-style {
                font-size: 16px;
            }
            .table-mandates {
                width: 100%;
            }
            .table-mandates td {
                padding: 2px;
            }
            .table-mandates .name {
                width:75%;
                border-bottom: 1px dashed black;
                text-align: left;
            }
            .table-mandates .amount {
                width:25%;
                text-align: right;
                border-bottom: 1px dashed black;
            }
            .table-mandates .totalLabel {
                width:75%;
                text-align: left;
            }
            .table-mandates .totalAmount {
                width:25%;
                text-align: right;
            }
            
        </style>
    </head>
    <body>
        <div class="header">
            <img src="/tmp/logo.png" alt="Northleaf Logo" height="50">
            <p class="header-text">Funding Request & Authorization</p>
        </div>
        
        <div class="content">
            <p class="custom-style">RE: <strong>' . $data['PE_PROJECT_INVESTMENT_NAME'] . '</strong><br/>Date: ' . $data['PE_TARGET_CLOSE_DATE'] . '</p>
            
            
            <p class="custom-style">The transaction regarding Northleaf’s investment in ' . $data['PE_PROJECT_INVESTMENT_NAME'] . ' (the “Investment”), as presented, conforms to the Investment Committee approval dated ' . $data['PE_DATE_APPROVAL'] . '. Northleaf hereby requests funding authorization of up to the aggregate amount of the participating mandates noted below (plus fees and expenses) for Northleaf’s investment in ' . $data['PE_PROJECT_INVESTMENT_NAME'] . ', as follows:</p>
            <table class="table-mandates">';
    $totalMandates = 0;
    if (count($data['PE_MANDATES']) > 0) {
        foreach ($data['PE_MANDATES'] as $mandate) {
            // Verify mandate
            if (
                isset($mandate['PE_CO_INVESTOR_FUNDING']) &&
                isset($mandate['PE_CO_INVESTOR_FUNDING']['ID']) &&
                $mandate['PE_CO_INVESTOR_FUNDING']['ID'] == 1
            ) {
                // Exclude coinvestor direct funding
                continue;
            }

            $totalMandates += $mandate['PE_MANDATE_ACTUAL_ALLOCATION'];
            $page1 .= '<tr>';

            if (isset($mandate['PE_MANDATE_ENTITY_DOCUMENT'])) {
                $page1 .= '<td class="custom-style name">' . $mandate['PE_MANDATE_ENTITY_DOCUMENT'] . '</td>';
            } else {
                $page1 .= '<td class="custom-style name"></td>';
            }

            $page1 .= '<td class="custom-style amount">' . $data['PE_CURRENCY'] . ' ' . formatCurrency($mandate['PE_MANDATE_ACTUAL_ALLOCATION']) . '</td>
            </tr>';
        }
    }

    $page1 .= '
                <tr>
                    <td class="custom-style totalLabel"><b>TOTAL</b></td>
                    <td class="custom-style totalAmount">' . $data['PE_CURRENCY'] . ' ' . formatCurrency($totalMandates) . '</td>
                </tr>';
    $page1 .= '</table>
            
            <p class="custom-style">' . $data['PE_TEXT_TO_ASK_CLIENT'] . '</p>
            
            <p class="custom-style">The undersigned confirms that (i) the material terms of the Investment, as presented, conform to the Investment Committee approval dated ' . $data['PE_DATE_APPROVAL'] . ' (ii) there are no conditions precedent to such Investment Committee approval that have not been satisfied or waived as at the date hereof and (iii) all documentation required prior to funding by Northleaf’s Policies and Procedures has been satisfactorily addressed.</p>
            <div class="signature">
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:left;width:50%;border-bottom: 1px solid;vertical-align: bottom;"><img src="' . $signaturePathPrimary . '" alt="Signature" style="margin-right: 10px; height: 50px;"></td>
                        <td style="text-align:right;width:10%;vertical-align: bottom;font-size: 16px;"><p>Date:</p></td>
                        <td style="text-align:left;width:40%; border-bottom: 1px solid;vertical-align: bottom;font-size: 16px;"><p>' . $currentDatePrimary . '</p></td>
                    </tr>
                </table>
                <p class="custom-style">' . $data['PE_PRIMARY_AUTHORIZATION']['firstname'] . ' ' . $data['PE_PRIMARY_AUTHORIZATION']['lastname'] . ', ' . $data['PE_PRIMARY_AUTHORIZATION']['title'] . '</p>
            </div>
        </div>
        
        <p class="custom-style">As the General Counsel, or one of her authorized surrogates, has approved the above confirmations for the transaction, a Managing Director or the Chief Financial Officer hereby authorizes the requested funding for Northleaf’s investment in ' . $data['PE_PROJECT_INVESTMENT_NAME'] . ', as set out above.</p>
        <div class="signature">
            <p class="custom-style">FUNDING APPROVED:</p>
            <table style="width:100%;">
                <tr>
                    <td style="text-align:left;width:50%;border-bottom: 1px solid;vertical-align: bottom;"><img src="' . $signaturePathSecondary . '" alt="Signature" style="margin-right: 10px; height: 50px;"></td>
                    <td style="text-align:right;width:10%;vertical-align: bottom;font-size: 16px;"><p>Date:</p></td>
                    <td style="text-align:left;width:40%; border-bottom: 1px solid;vertical-align: bottom;font-size: 16px;"><p>' . $currentDateSecondary . '</p></td>
                </tr>
            </table>
            <p class="custom-style">' . $data['PE_SECONDARY_AUTHORIZATION']['firstname'] . ' ' . $data['PE_SECONDARY_AUTHORIZATION']['lastname'] . ', ' . $data['PE_SECONDARY_AUTHORIZATION']['title'] . '</p>
        </div>
    </body>
    </html>
    ';

    // Create the PDF
    $html2pdf->writeHTML($page1);

    // Save the PDF to the specified path
    $html2pdf->output($pathOutput, 'F');
}
// Retrieve environment variables for API
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
// Retrieve server host URL
$host = $_SERVER["HOST_URL"];
$sqlUrl = $apiHost . $apiSql;
// Continue with the API processing
$processRequestId = $data['_request']['id'];
$dataParent = ($config["withOutSignature"] == true) ? $data['_parent'] : $data;

// Retrieve portfolio manager approver information
$portfolioManagerApprover = $dataParent['PE_PORTFOLIO_MANAGER_APPROVER'];
$getUserInfoUrl = $apiHost . "/users/" . $portfolioManagerApprover;
$portfolioManagerApproverInfo = callApiUrlGuzzle($getUserInfoUrl, 'GET', []);

$portfolioPrimaryAuthorisation = $dataParent['PE_PRIMARY_AUTHORIZATION']['USER_ID'];
$getUserInfoUrl = $apiHost . "/users/" . $portfolioPrimaryAuthorisation;
$portfolioPrimaryAuthorisationInfo = callApiUrlGuzzle($getUserInfoUrl, 'GET', []);

$portfolioSecondAuthorisation = $dataParent['PE_SECONDARY_AUTHORIZATION']['USER_ID'];
$getUserInfoUrl = $apiHost . "/users/" . $portfolioSecondAuthorisation;
$portfolioSecondAuthorisationInfo = callApiUrlGuzzle($getUserInfoUrl, 'GET', []);

// Prepare variables for the PDF
$pdfVariables = [
    "northImage" => getenv('ORIGINAL_URL_PDF_LOGO'),
    "approverName" => $portfolioManagerApproverInfo['firstname'] . ' ' . $portfolioManagerApproverInfo['lastname'],
    "approverTitle" => $portfolioManagerApproverInfo['title'],
    "approverSignature64" => $portfolioManagerApproverInfo['meta']['signature'],
    "currentDate" => date('m/d/Y'),
    "PE_PROJECT_INVESTMENT_NAME" => $dataParent['PE_PROJECT_INVESTMENT_NAME'],
    "PE_TARGET_CLOSE_DATE" => formatDate($dataParent['PE_TARGET_CLOSE_DATE']),
    "PE_DATE_APPROVAL" => formatDate($dataParent['PE_DATE_APPROVAL']),
    "PE_CURRENCY" => $dataParent['PE_CURRENCY'] !== "Other" ? $dataParent['PE_CURRENCY'] : $dataParent['PE_OTHER_CURRENCY'],
    "PE_MANDATE_TOTAL_ACTUAL_ALLOCATION" => formatCurrency($dataParent['PE_MANDATE_TOTAL_ACTUAL_ALLOCATION']),
    "PE_MANDATES" => $dataParent['PE_MANDATES'],
    "PE_INVESTMENT_COMMITTEE_TEXT" => $dataParent['PE_INVESTMENT_COMMITTEE_TEXT'],
    "PE_PRIMARY_AUTHORIZATION" => $portfolioPrimaryAuthorisationInfo,
    "PE_SECONDARY_AUTHORIZATION" => $portfolioSecondAuthorisationInfo,
    "PE_IC_APPROVER_TYPE" => $data['PE_IC_APPROVER_TYPE'],
    "PE_CONFIRMATION_DATE_IC2_PRIMARY" => ($data['PE_CONFIRMATION_DATE_IC2_PRIMARY'] != '') ? $data['PE_CONFIRMATION_DATE_IC2_PRIMARY'] : date('m/d/Y'),
    "PE_CONFIRMATION_DATE_IC2_SECONDARY" => ($data['PE_CONFIRMATION_DATE_IC2_SECONDARY'] != '') ? $data['PE_CONFIRMATION_DATE_IC2_SECONDARY'] : date('m/d/Y'),
    "PE_COLLECTIVELY_TEXT" => $dataParent['PE_COLLECTIVELY_TEXT'],
    "PE_UNDERSIGNED_TEXT" => $dataParent['PE_UNDERSIGNED_TEXT'],
    "PE_TEXT_TO_ASK_CLIENT" => $dataParent['PE_TEXT_TO_ASK_CLIENT']
];

// Check if the signature should be omitted
$withoutSignature = isset($config["withOutSignature"]) ? $config["withOutSignature"] : false;
// Set the output path for the PDF file
//tlx $pathOutput = '/tmp/funding_request_and_authorization.pdf';
$pathOutput = '/tmp/Funding Request and Authorization.pdf';

// Generate the PDF
generatePDF($pathOutput, $pdfVariables, $withoutSignature);

$dataReturn = array();

// Define variable into data
$dataName = 'FRA_PDF';
// Create a new request file
$apiInstance = $api->requestFiles();
$newFile = $apiInstance->createRequestFile($processRequestId, $dataName, $pathOutput);
$fileUID = $newFile->getFileUploadId();
$dataReturn["FRA_PDF"] = $fileUID;
/*
// Retrieve request files and update data
$requestFiles = $api->requestFiles()->getRequestFiles($processRequestId)['data'];

if (count($requestFiles) > 0) {
    foreach ($requestFiles as $requestFile) {
        $dataReturn[str_replace(' ', '', $requestFile['custom_properties']['data_name'])] = $requestFile['id'];
    }
}*/

return $dataReturn;