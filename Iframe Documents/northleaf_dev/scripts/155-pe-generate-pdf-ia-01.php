<?php
/**
 * Investment Adviser Review and Authorization IA 01
 * Created by Telmo Chiri
 * This script generates a PDF document for Investment Advisor Approver using HTML2PDF.
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");

// Retrieve environment variables for API
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$serverUrl = getenv('ENVIRONMENT_BASE_URL');
// Retrieve server host URL
$host = $_SERVER["HOST_URL"];
$sqlUrl = $apiHost . $apiSql;

// Retrieve Investment Advisor Approver Information
$investmentAdvisorApprover = $data['PE_INVESTMENT_ADVISOR_APPROVER'];
$getUserInfoUrl = $apiHost . "/users/" . $investmentAdvisorApprover;
$investmentAdvisorApproverInfo = callApiUrlGuzzle($getUserInfoUrl, 'GET', []);

$madatesFromCollection = getMandatesData();

//Review Mandates
$mandatesIA = [];
$mandateInitialTotalAmont = 0;
foreach($data['PE_MANDATES'] as $mandate) {
    if ($mandate['PE_MANDATE_NAME_SL']['LABEL'] == 'NCO' || $mandate['PE_MANDATE_NAME_SL']['LABEL'] == 'NSP III') {
        $mandateInitialTotalAmont += $mandate['PE_MANDATE_AMOUNT'];
        $mandatesIA[] = $mandate;
    }
}

// Prepare variables for the PDF
$pdfVariables = [
    "northImage" => getenv('ORIGINAL_URL_PDF_LOGO'),
    "approverName" => $investmentAdvisorApproverInfo['firstname'] . ' ' . $investmentAdvisorApproverInfo['lastname'],
    "approverTitle" => $investmentAdvisorApproverInfo['title'],
    "approverSignature64" => $investmentAdvisorApproverInfo['meta']['signature'],
    "currentDate" => date('m/d/Y'),
    "PE_PROJECT_INVESTMENT_NAME" => $data['PE_PROJECT_INVESTMENT_NAME'],
    "PE_MANDATE_INITIAL_TOTAL_AMOUNT" => formatCurrency($mandateInitialTotalAmont),
    "PE_TARGET_CLOSE_DATE" => formatDate($data['PE_TARGET_CLOSE_DATE']),
    "PE_DATE_APPROVAL" => convertFormatDate($data['PE_DATE_APPROVAL']),
    "PE_CURRENCY" => $data['PE_CURRENCY'] !== "Other" ? $data['PE_CURRENCY'] : $data['PE_OTHER_CURRENCY'],
    "PE_MANDATE_TOTAL_ACTUAL_ALLOCATION" => formatCurrency($data['PE_MANDATE_TOTAL_ACTUAL_ALLOCATION']),
    "PE_MANDATES" => $mandatesIA,
    "PE_INVESTMENT_COMMITTEE_TEXT" => $data['PE_INVESTMENT_COMMITTEE_TEXT'],
    "PE_COLLECTIVELY_TEXT" => $data['PE_COLLECTIVELY_TEXT'],
    "PE_UNDERSIGNED_TEXT" => $data['PE_UNDERSIGNED_TEXT'],
    "PE_TEXT_TO_ASK_CLIENT" => $data['PE_TEXT_TO_ASK_CLIENT'],
    "PE_IC_MEETING_DATE" => $data['PE_IC_MEETING_DATE']
];

// Check if the signature should be omitted
$withoutSignature = isset($config["withOutSignature"]) ? $config["withOutSignature"] : false;
// Set the output path for the PDF file
$pathOutput = '/tmp/Investment Adviser Review and Authorization.pdf';

// Generate the PDF
generatePDF($pathOutput, $pdfVariables, $withoutSignature);

// Continue with the API processing
$processRequestId = $data['_request']['id'];

$dataReturn = array();

// Define variable into data
$dataName = 'PE_INVESTMENT_ADVISOR_DOCUMENT_GENERATED';
$dataReturn['pdfName'] = $dataName;

// Create a new request file
$apiInstance = $api->requestFiles();
$newFile = $apiInstance->createRequestFile($processRequestId, $dataName, $pathOutput);
$fileUID = $newFile->getFileUploadId();
$fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;
$dataReturn["PE_INVESTMENT_ADVISOR_DOCUMENT_GENERATED_URL"] = $fileGeneratedUrl;
$dataReturn["PE_INVESTMENT_ADVISOR_DOCUMENT_GENERATED_ID"] = $fileUID;

return $dataReturn;

/**
 * getMandatesData
 *
 * Created by MC
 */
function getMandateLegalName ($name) {
    global $madatesFromCollection;
    $legalName = '';
    foreach ($madatesFromCollection as $mandate) {
        if($mandate['LABEL'] == $name) {
            $legalName = $mandate['LEGAL_NAME'];
            break;
        }
    }
    return $legalName;
}

function getMandatesData () {
    global $apiHost, $apiSql;
    $apiUrl = $apiHost . $apiSql;
    $masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
    $result = [];
    //Get Collections IDs
    $queryCollections = "SELECT data->>'$.COLLECTION_ID' AS ID,
                                data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN ('PE_MANDATE_NAME', 'PE_MANDATE_ENTITY')";
    $collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollections));

    if (count($collectionsInfo) > 0) {
        foreach($collectionsInfo as $collection) {
            $collectionsArray[$collection["COLLECTION_NAME"]] = $collection["ID"];
        }
        $queryMandateNameOptions = "SELECT data->>'$.MANDATE_NAME_LABEL' AS LABEL, 
                                        data->>'$.MANDATE_COMPLETE_NAME' AS COMPLETE_NAME, 
                                        data->>'$.MANDATE_LEGAL_NAME' AS LEGAL_NAME,
                                        data->>'$.MANDATE_CO_INVESTOR' AS CO_INVESTOR  
                                        FROM collection_" . $collectionsArray["PE_MANDATE_NAME"] . "
                                        WHERE data->>'$.MANDATE_NAME_STATUS' = 'Active'
                                        ORDER BY CAST(data->>'$.MANDATE_NAME_ORDER' AS UNSIGNED) ASC";
        $result = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryMandateNameOptions));
    }
    return $result;
}
/**
 * Generates a PDF document using HTML2PDF.
 *
 * @param string $pathOutput The output path for the PDF file.
 * @param array $data The data to be included in the PDF.
 * @param bool $withoutSignature Flag to determine if signatures should be omitted.
 * Created by Telmo Chiri
 */
function generatePDF($pathOutput, $data, $withoutSignature)
{
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $html2pdf->setTestTdInOnePage(false);
    // Create temporary image file for signature if required
    if (!$withoutSignature) {
        $signaturePathPrimary = createTempImage($data['approverSignature64']);
        $currentDate = $data['currentDate'];
    } else {
        $signaturePathPrimary = createBlankTempImage();
        $currentDate = '';
    }
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);

    // HTML content for the first page
    $page1 = '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Northleaf - Investment Adviser Review and Authorization</title>
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
                color: black;
                text-align: left;
            }
            .table-mandates .amount {
                width:25%;
                text-align: right;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="/tmp/logo.png" alt="Northleaf Logo" height="50">
            <p class="header-text">Investment Adviser Review and Authorization</p>
        </div>
        
        <div class="content">
            <p class="custom-style">RE: <strong> Commitment of up to ' . $data['PE_CURRENCY'] . ' ' . $data['PE_MANDATE_INITIAL_TOTAL_AMOUNT'] . ' plus fees and expenses, in ' . $data['PE_PROJECT_INVESTMENT_NAME'] . ' (the “Investment”)</strong></p>
            <p class="custom-style">The undersigned has reviewed the Investment Committee Presentation dated ' . convertFormatDate(formatDate($data['PE_IC_MEETING_DATE'])) . ' and the approval of the Investment Committee regarding the investment to be made by:</p>
            <table class="table-mandates">';
    if (count($data['PE_MANDATES']) > 0) {
        foreach ($data['PE_MANDATES'] as $mandate) {
            $page1 .= '<tr>';
            if (isset($mandate['PE_MANDATE_ENTITY_DOCUMENT'])) {
                $legaName = getMandateLegalName($mandate['PE_MANDATE_NAME_SL']['LABEL']);
                $legaName = !empty($legaName) ? $legaName : $mandate['PE_MANDATE_ENTITY_DOCUMENT'];
                $page1 .= '<td class="custom-style name">•' . $legaName . ' </td>';
            } else {
                $page1 .= '<td class="custom-style name"></td>';
            }
            $page1 .= '<td class="custom-style amount">' . $mandate['PE_MANDATE_NAME'] . ' </td>
                    </tr>';
        }
    }
    $page1 .= '</table>
            <p class="custom-style">(collectively, the <strong>“Funds”</strong>)</p>
            <p class="custom-style">
            Based on a review of the Investment Committee Presentation, the Investment Committee approval and any other factors considered relevant by the undersigned, as an investment adviser registered with the U.S. Securities and Exchange Commission under the Investment Advisers Act of 1940, as amended, acting in its capacity as an investment adviser to one or more of the Funds, the undersigned hereby authorizes the making of the investment by the Funds.
            </p>
            <p class="custom-style">NORTHLEAF CAPITAL ADVISORS LTD.</p>
            <div class="signature">
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:right;width:15%;vertical-align: bottom;font-size: 16px;"><p>By:</p></td>
                        <td style="text-align:left;width:35%;border-bottom: 1px solid;vertical-align: bottom;"><img src="' . $signaturePathPrimary . '" alt="Signature" style="margin-right: 10px; height: 50px;"></td>
                    </tr>
                    <tr>
                        <td style="text-align:right;width:15%;vertical-align: bottom;font-size: 16px;"></td>
                        <td style="text-align:left;width:35%;vertical-align: bottom;font-size: 16px;">Name: ' . $data['approverName'] . '<br>
                        Title:  ' . $data['approverTitle'] . '</td>
                    </tr>
                </table>
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:right;width:15%;vertical-align: bottom;font-size: 16px;"><p>Date:</p></td>
                        <td style="text-align:left;width:35%; border-bottom: 1px solid;vertical-align: bottom;font-size: 16px;"><p>' . $currentDate . '</p></td>
                    </tr>
                </table>
            </div>
        </div>
    </body>
    </html>';

    // Create the PDF
    $html2pdf->writeHTML($page1);
    // Save the PDF to the specified path
    $html2pdf->output($pathOutput, 'F');
}
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
 * Formats a date to 'M d, Y'.
 *
 * @param string $date The date to format.
 * @return string The formatted date.
 * Created by Telmo Chiri
 */
function convertFormatDate($fecha) {
    // Convert date to a Unix timestamp
    $timestamp = strtotime($fecha);
    // Format the date in the new format
    $nuevaFecha = date('M d, Y', $timestamp);
    return $nuevaFecha;
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
    return number_format((float)$number, 2, '.', ',');
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