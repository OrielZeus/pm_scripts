<?php
/******************************** 
 * PE - Generate PDF IC 01 - PDF generate
 *
 * by Adriana Centellas
 *******************************/
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

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


function generatePDF($pathOutput, $dataForPDF)
{
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $html2pdf->setTestTdInOnePage(false);
    $css = '
    <style>
        body { font-family: Arial, sans-serif; }
        .content { font-size: 12px; }
        .list { margin-left: 20px; }
        .list li { margin-bottom: 5px; }
        .header-text { color: #711426; text-decoration: underline; padding-bottom: 5px; font-size: 24px; font-weight: bold; }
        .custom-style { font-size: 16px; }
    </style>';

    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);

    $htmlContent = '';

    foreach ($dataForPDF as $dPDF) {
        $htmlContent .= $css;
        $htmlContent .= '
        <page>
            <div class="header">
                <img src="/tmp/logo.png" alt="Northleaf Logo" height="50">
            </div>
            <div class="content">
                <p><strong>Approval of the Investment Committee of ' . $dPDF["PE_MANDATE_NAME"] . '</strong></p>
                <p><strong>Date: ' . formatDate($dPDF["PE_DATE_APPROVAL"]) . '</strong></p>
                <p><strong>RE: ' . $dPDF["PE_PROJECT_INVESTMENT_NAME"] . ' ' . $dPDF["PE_BRIEF_DEAL_DESCRIPTION"] . ' (the “' . $dPDF["PE_PROJECT_INVESTMENT_NAME"] . ' Investment”)</strong></p>
                <hr><p>
                    The undersigned comprises the Investment Committee of ' . $dPDF["PE_MANDATE_NAME"] . ' (“<strong>' . $dPDF["PE_MANDATE_NAME_ABR"] . '</strong>” or the “<strong>Fund</strong>”).</p>
                    <p>A presentation on the ' . $dPDF["PE_PROJECT_INVESTMENT_NAME"] . ' Investment dated ' . formatDate($dPDF["PE_IC_MEETING_DATE"]) . ' (the “<strong>Presentation</strong>”) was made available to the members of the Investment Committee of the Fund at the Investment Committee Meeting on ' . formatDate($dPDF["PE_IC_MEETING_DATE"]) . ' (the “<strong>IC Meeting</strong>”) and/or by email discussions (the “<strong>IC Discussion</strong>”).
                    Further to the Presentation, the investment team sought approval by the Investment Committee of the Fund for a commitment by ' . $dPDF["PE_MANDATE_NAME_ABR"] . ' of up to ' . $dPDF["PE_CURRENCY"] . ' ' . formatCurrency($dPDF["PE_MANDATE_AMOUNT"]) . ', plus fees and expenses, to the ' . $dPDF["PE_PROJECT_INVESTMENT_NAME"] . ' Investment (the “<strong>' . $dPDF["PE_MANDATE_NAME_ABR"] . ' Commitment</strong>”).</p>
                    <p>The Investment Committee members via the IC Meeting and/or IC Discussion approved the ' . $dPDF["PE_MANDATE_NAME_ABR"] . ' Commitment.</p>
                <hr>
                <p>The undersigned, comprising the Investment Committee of the Fund, hereby confirms that the ' . $dPDF["PE_PROJECT_INVESMENT_NAME"] . ' Investment was approved by ' . $dPDF["PE_MANDATE_NAME_ABR"] . ' as described above.</p>
                <p><strong>Investment Committee</strong></p>';

        //INEW LOGIC FOR TWO COLUMNS SIGNATURES
        $htmlContent .= '<table style="width: 100%;">';
        $counter = 0;

        foreach ($dPDF["PE_IC_01_APPROVERS_REVIEWED"] as $approver) {
            $image = $approver["PE_IC_APPROVER_SIGNATURE"];
            $imagePath = createTempImage($image);

            // Abrimos una fila cada 2 elementos
            if ($counter % 2 == 0) {
                $htmlContent .= '<tr>';
            }

            $htmlContent .= '<td style="width: 50%; text-align: center; vertical-align: top; padding: 10px;">';
            if (strpos($imagePath, 'Error') === false) {
                $htmlContent .= '<p><img src="' . $imagePath . '" alt="Firma" style="height: 50px;"></p>';
            } else {
                $htmlContent .= '<p style="height: 50px;"></p>';
            }
            $htmlContent .= '<p>______________________________________</p>';
            $htmlContent .= '<p>' . $approver["PE_IC_APPROVER_NAME"] . '</p>';
            $htmlContent .= '</td>';

            // Cerramos la fila cada 2 elementos
            if ($counter % 2 == 1) {
                $htmlContent .= '</tr>';
            }

            $counter++;
        }

        // Si hay un número impar, cerramos la fila final
        if ($counter % 2 == 1) {
            $htmlContent .= '<td style="width: 50%;"></td></tr>';
        }

        $htmlContent .= '</table>';
        //END
        $htmlContent .= '</div></page>';
    }

    $html2pdf->writeHTML($htmlContent);
    $html2pdf->output($pathOutput, 'F');
}


//Collect information data for each of the mandates


//Get query for mandates
foreach ($data["PE_MANDATES"] as $key => $mandate) {
    if ($key == 0) {
        $mandatesQuery = "'" . $mandate["PE_MANDATE_NAME"] . "'";
    } else {
        $mandatesQuery .= ", '" . $mandate["PE_MANDATE_NAME"] . "'";
    }
}
//Get IC Approvers per mandate
$getApproversInformation = "SELECT DISTINCT ";
$getApproversInformation .= "CONCAT(U.firstname, ' ', U.lastname) AS APPROVER_FULL_NAME, ";
$getApproversInformation .= "subquery.MIA_MANDATE_NAME as MANDATE_NAME, ";
$getApproversInformation .= "U.id AS APPROVER_ID ";
$getApproversInformation .= "FROM users AS U ";
$getApproversInformation .= "JOIN ( ";
$getApproversInformation .= "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(j.user_id, '$')) AS user_id, ";
$getApproversInformation .= "data->>'$.MIA_MANDATE_NAME' as MIA_MANDATE_NAME ";
$getApproversInformation .= "FROM collection_" . getCollectionId("PE_MANDATE_IC_APPROVERS", $apiUrl) . ", ";
$getApproversInformation .= "JSON_TABLE( ";
$getApproversInformation .= "data->>'$.MIA_APPROVER_USERS_IDS', ";
$getApproversInformation .= "'$[*]' COLUMNS (user_id VARCHAR(255) PATH '$') ";
$getApproversInformation .= ") AS j ";
$getApproversInformation .= "WHERE data->>'$.MIA_MANDATE_NAME' IN (" . $mandatesQuery . ") ";
$getApproversInformation .= "AND data->>'$.MIA_APPROVER_STATUS' = 'ACTIVE' ";
$getApproversInformation .= ") AS subquery ON FIND_IN_SET(U.id, subquery.user_id) > 0 ";

$approversInfoResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getApproversInformation));


//Get currency
if ($data["PE_CURRENCY"] == "Other") {
    $currency = $data["PE_OTHER_CURRENCY"];
} else {
    $currency = $data["PE_CURRENCY"];
}

$dataForPDF = [];

foreach ($data["PE_MANDATES"] as $mandate) {
    //Check if mandate is co-investor
    if ($mandate["PE_MANDATE_CO_INVESTOR"] == "NO") {

        $mandateName = $mandate["PE_MANDATE_NAME"];
        $approvers = array_filter($approversInfoResponse, function ($approver) use ($mandateName) {
            return $approver["MANDATE_NAME"] === $mandateName;
        });

        $approverSignatures = [];
        foreach ($approvers as $approver) {
            foreach ($data["PE_IC_01_APPROVERS_REVIEWED"] as $signature) {
                if ($signature["PE_IC_APPROVER_ID"] === $approver["APPROVER_ID"]) {
                    $approverSignatures[] = [
                        "PE_IC_APPROVER_ID" => $approver["APPROVER_ID"],
                        "PE_IC_APPROVER_NAME" => $approver["APPROVER_FULL_NAME"],
                        "PE_IC_APPROVER_SIGNATURE" => $signature["PE_IC_APPROVER_SIGNATURE"]
                    ];
                    break;
                }
            }
        }
        $mandateNameToShow = (isset($mandate["PE_MANDATE_FUND_NAME"]) && $mandate["PE_MANDATE_FUND_NAME"] != "") ? $mandate["PE_MANDATE_FUND_NAME"] : $mandate["PE_MANDATE_NAME_SL"]["COMPLETE_NAME"];
        $dataForPDF[] = [
            "PE_MANDATE_NAME" => $mandateNameToShow,
            "PE_MANDATE_NAME_ABR" => $mandate["PE_MANDATE_NAME_SL"]["LABEL"],
            "PE_DATE_APPROVAL" => $data["PE_DATE_APPROVAL"],
            "PE_PROJECT_INVESTMENT_NAME" => $data["PE_PROJECT_INVESTMENT_NAME"],
            "PE_BRIEF_DEAL_DESCRIPTION" => $data["PE_BRIEF_DEAL_DESCRIPTION"],
            "PE_CURRENCY" => $currency,
            "PE_MANDATE_AMOUNT" => $mandate["PE_MANDATE_AMOUNT"],
            "PE_TARGET_CLOSE_DATE" => $data["PE_TARGET_CLOSE_DATE"],
            "PE_IC_MEETING_DATE" => $data["PE_IC_MEETING_DATE"],
            "PE_IC_01_APPROVERS_REVIEWED" => $approverSignatures
        ];

    }
}

// Set the output path for the PDF file
$pathOutput = '/tmp/IC_Approval_Document.pdf';

// Generate the PDF
generatePDF($pathOutput, $dataForPDF);

// Continue with the API processing
$processRequestId = $data['_request']['id'];

// Define variable into data
$dataName = 'PDF_IC_APPROVAL';
$data['pdfName'] = $dataName;

// Create a new request file
$apiInstance = $api->requestFiles();
$newFile = $apiInstance->createRequestFile($processRequestId, $dataName, $pathOutput);

$data["PDF_IC_APPROVAL_ID"] = $newFile->getFileUploadId();

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