<?php 
/**********************************************
 * PE - Generate Document LL.04
 *
 * by Adriana Centellas
 *********************************************/
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->requestFiles();

/**
 * Set Checkbox with image
 *
 * @param bool $checkValue
 * @param string $checkUrl
 * @return string $htmlCheck
 *
 * by Cinthia Romero
 */
function setCheckGrid($checkValue, $checkUrl)
{
    if ($checkValue) {
        $htmlCheck = "<img src='" . $checkUrl . "' height='10'/>";
    } else {
        $htmlCheck = "";
    }
    return $htmlCheck;
}

/**
 * Get Documents Names
 *
 * @param array $data 
 * @param array $documentsToValidate
 * @param string $apiUrl
 * @return array $documentsNames
 *
 * by Cinthia Romero
 */
function getDocumentsNames($data, $documentsToValidate, $apiUrl)
{
    $documentsIds = array();
    foreach ($documentsToValidate as $document) {
        //Validate document condition
        if ($document["CONDITION"] == "YES" || $document["CONDITION"] == "") {
            //Check if variable is array and has at least one row
            if (is_array($data[$document["VARIABLE"]]) && count($data[$document["VARIABLE"]]) > 0) {
                $loopVariable = "file";
                if (!empty($document["IF_ARRAY_DOCUMENT_VARIABLE"])) {
                    $loopVariable = $document["IF_ARRAY_DOCUMENT_VARIABLE"];
                }
                foreach ($data[$document["VARIABLE"]] as $multiDocument) {
                    if (!empty($multiDocument[$loopVariable])) {
                        $documentsIds[] = $multiDocument[$loopVariable];
                    }
                } 
            } else {
                if (!empty($data[$document["VARIABLE"]])) {
                    $documentsIds[] = $data[$document["VARIABLE"]];
                }
            }
        }
    }
    //Join all ids for query
    $documentsIds = implode(',', $documentsIds);
    $getDocumentName = "SELECT id,
                               file_name 
                        FROM media 
                        WHERE id IN (" . $documentsIds . ")";
    $documentsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getDocumentName));
    $documentsNames = array();
    if (!empty($documentsResponse[0]["id"])) {
        foreach ($documentsResponse as $document) {
            $documentsNames[$document["id"]] = $document["file_name"];
        }
    }
    return $documentsNames;
}

/**
 * Set Document Link
 *
 * @param int $processRequestId
 * @param int/array $documentValue
 * @param string $specialLoopKey
 * @param string $serverUrl
 * @param array $documentsNames
 * @return string $htmlDocument
 *
 * by Cinthia Romero
 */
function setDocumentLink($processRequestId, $documentValue, $specialLoopKey, $serverUrl, $documentsNames)
{
    $htmlDocument = "";
    if (!empty($documentValue)) {
        if (is_array($documentValue) && count($documentValue) > 0) {
            foreach ($documentValue as $document) {
                //Check if document inside array has special key
                $documentKey = "file";
                if (!empty($specialLoopKey)) {
                    $documentKey = $specialLoopKey;
                }
                if (!empty($documentsNames[$document[$documentKey]])) {
                    if ($htmlDocument != "") {
                        $htmlDocument .= "<br>"; 
                    }
                    $documentName = $documentsNames[$document[$documentKey]];
                    $documentLink = $serverUrl . 'request/' . $processRequestId . '/files/' . $document[$documentKey];
                    $htmlDocument .= "<a download='" . $documentName . "' href='" . $documentLink . "'>" . $documentName . "</a>";
                }
            }
        } else {
            if (!empty($documentsNames[$documentValue])) {
                $documentName = $documentsNames[$documentValue];
                $documentLink = $serverUrl . 'request/' . $processRequestId . '/files/' . $documentValue;
                $htmlDocument = "<a download='" . $documentName . "' href='" . $documentLink . "'>" . $documentName . "</a>";
            }
        }
    }
    return $htmlDocument;
}

/**
 * Format Date
 *
 * @param string $date
 * @param string $format
 * @return string $formatedDate
 *
 * by Cinthia Romero
 */
function formatDate($date, $format)
{
    $date = strtotime($date);
    $formatedDate = date($format, $date);
    return $formatedDate;
}

/**
 * Format Currency
 *
 * @param float $number
 * @return string $currency
 *
 * by Cinthia Romero
 */
function formatCurrency($number)
{
    if (!is_numeric($number)) {
        return 'Invalid number format';
    }
    return number_format((float)$number, 2, '.', ',');
}

/**
 * Get users name with the ID
 *
 * @param (Array) $usersIDs
 * @param (Array) $dataToGeneratePDF
 * @param (String) $apiUrl
 * @return (Array) $dataToGeneratePDF
 *
 * by Ana Castillo
 */
function getUsersName($usersIDs, $dataToGeneratePDF, $apiUrl)
{
    //Filter empty values on the array
    $filteredValues = array_filter($usersIDs, function($value) {
        return !empty($value);
    });

    //Get only values of the array
    $values = array_values($filteredValues);

    //Get all values into a string separated by comma
    $inQuery = implode(", ", $values);

    //Transpose the userIds to get key as ID and value as variable name
    $transposedUsers = array_flip($filteredValues);

    //Get user name
    $sQUserName = "SELECT id AS USER_ID,
                          CONCAT(firstname, ' ', lastname) AS USER_FULL_NAME
                   FROM users
                   WHERE id IN (" . $inQuery . ")";
    $rQUserName = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQUserName));
    //Validate answer to get at least 1 value
    if (!empty($rQUserName[0]["USER_ID"])) {
        foreach ($rQUserName as $userInfo) {
            //Set variable name
            $variableLabel = $transposedUsers[$userInfo["USER_ID"]];
            //Set label of the user that we need
            $dataToGeneratePDF[$variableLabel . "_LABEL"] = $userInfo["USER_FULL_NAME"];
        }
    }

    //Get Empty values of the array with empty value
    $emptyValues = array_filter($usersIDs, function($value, $key) {
        return empty($value);
    }, ARRAY_FILTER_USE_BOTH);
    foreach ($emptyValues as $key => $empty) {
        //Set as empty
        $dataToGeneratePDF[$key . "_LABEL"] = "";
    }

    return $dataToGeneratePDF;
}

/**
 * Add Section Title
 *
 * @param string $title
 * @return $sectionTitleHTML
 *
 * by Cinthia Romero
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
 * @param string $serverUrl
 * @param array $documentsNames
 * @return none
 *
 * by Cinthia Romero
 */
function generatePDF($outputDocumentTempPath, $data, $serverUrl, $documentsNames)
{
    $processRequestId = $data['_request']['id'];
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);

    $imageCherry = file_get_contents(getenv("CHECK_IMAGE_FOR_DOCUMENTS_URL_NEW"));
    file_put_contents('/tmp/cherry.png', $imageCherry);

    $pdfHTML = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Final sign-off including Risk & Process Review</title>
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
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2' class='titleRow'>Final sign-off including Risk & Process Review</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Deal Overview");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Project / Investment Name:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_PROJECT_INVESTMENT_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Deal Team Senior:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_TEAM_SENIOR_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Deal Team Junior:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_TEAM_JUNIOR_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Date of IC Approval:</td>";
    $pdfHTML .= "<td class='text'>" . formatDate($data["PE_DATE_APPROVAL"], "m/d/Y") . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Target Close Date:</td>";
    $pdfHTML .= "<td class='text'>" . formatDate($data["PE_TARGET_CLOSE_DATE"], "m/d/Y") . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Currency:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_CURRENCY"] . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_CURRENCY"] == "Other") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Other currency:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_OTHER_CURRENCY"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Brief deal description:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_BRIEF_DEAL_DESCRIPTION"] . "</td>";
    $pdfHTML .= "</tr>";

    $pdfHTML .= addSectionTitle("Mandates");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Mandate</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>IC Approved Amount</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Actual Allocation</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>% of Deal</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Entity</th>";
    $pdfHTML .= "</tr>";
    foreach ($data["PE_MANDATES"] as $mandate) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $mandate["PE_MANDATE_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . formatCurrency($mandate["PE_MANDATE_AMOUNT"]) . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . formatCurrency($mandate["PE_MANDATE_ACTUAL_ALLOCATION"]) . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . round($mandate["PE_MANDATE_PERCENTAGE_DEAL"], 2). "%</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $mandate["PE_MANDATE_ENTITY"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='titleGrid' style='width:20%'>Total:</td>";
    $pdfHTML .= "<td class='textGrid' style='width:20%'>" . formatCurrency($data["PE_MANDATE_TOTAL_AMOUNT"]) . "</td>";
    $pdfHTML .= "<td class='textGrid' style='width:20%'>" . formatCurrency($data["PE_MANDATE_TOTAL_ACTUAL_ALLOCATION"]) . "</td>";
    $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $data["PE_MANDATE_TOTAL_PERCENTAGE"] . "%</td>";
    $pdfHTML .= "<td class='textGrid' style='width:20%'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    
    $pdfHTML .= addSectionTitle("Supporting Documentation");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Deal Folder:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_FOLDER"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Document</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Link</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:10%'>N/A</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Comment</th>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>Due Diligence Recommendation</td>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>" . setDocumentLink($processRequestId, $data["PE_UPLOAD_DD_REC"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "<td class='textGrid' style='width:10%'>" . setCheckGrid($data["PE_UPLOAD_DD_REC_NA"], '/tmp/cherry.png') . "</td>";
    if ($data["PE_UPLOAD_DD_REC_NA"]) {
        $pdfHTML .= "<td class='textGrid' style='width:30%'>" . $data["PE_UPLOAD_DD_REC_COMMENTS"] . "</td>";
    } else {
        $pdfHTML .= "<td class='textGrid' style='width:30%'></td>";
    }
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>Beat up</td>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>" . setDocumentLink($processRequestId, $data["PE_UPLOAD_BEAT_UP"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "<td class='textGrid' style='width:10%'>" . setCheckGrid($data["PE_UPLOAD_BEAT_UP_NA"], '/tmp/cherry.png') . "</td>";
    if ($data["PE_UPLOAD_BEAT_UP_NA"]) {
        $pdfHTML .= "<td class='textGrid' style='width:30%'>" . $data["PE_UPLOAD_BEAT_UP_COMMENTS"] . "</td>";
    } else {
        $pdfHTML .= "<td class='textGrid' style='width:30%'></td>";
    }
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>IC Presentation</td>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>" . setDocumentLink($processRequestId, $data["PE_UPLOAD_IC_PRESENTATION"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "<td class='textGrid' style='width:10%'></td>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>Black Hat</td>";
    $pdfHTML .= "<td class='textGrid' style='width:30%'>" . setDocumentLink($processRequestId, $data["PE_UPLOAD_BLACK_HAT"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "<td class='textGrid' style='width:10%'>" . setCheckGrid($data["PE_UPLOAD_BLACK_HAT_NA"], '/tmp/cherry.png') . "</td>";
    if ($data["PE_UPLOAD_BLACK_HAT_NA"]) {
        $pdfHTML .= "<td class='textGrid' style='width:30%'>" . $data["PE_UPLOAD_BLACK_HAT_COMMENTS"] . "</td>";
    } else {
        $pdfHTML .= "<td class='textGrid' style='width:30%'></td>";
    }
    $pdfHTML .= "</tr>";
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    //DT.01
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Complies with Conflicts Management Policy:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_COMPLIES_CONFLICTS_MANAGEMENT_POLICY"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Complies with Prohibited Investments and Related Party Restrictions Policy:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_COMPLIES_PROHIBITED_INVESTMENTS"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    //LC.03 Legal Review Memo
    $pdfHTML .= addSectionTitle("Legal Review Memo");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_LEGAL_REVIEW_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_LEGAL_REVIEW_NOT_APPLICABLE"] !== true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td colspan='2'>";
        $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<th class='titleGrid' style='width:50%'>Memo Upload</th>";
        $pdfHTML .= "<th class='titleGrid' style='width:50%'>Description</th>";
        $pdfHTML .= "</tr>";
        foreach ($data["PE_MEMO_DOCUMENT"] as $memoDocument) {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='textGrid' style='width:50%'>" . setDocumentLink($processRequestId, $memoDocument["PE_MEMO_UPLOAD"], "", $serverUrl, $documentsNames) . "</td>";
            $pdfHTML .= "<td class='textGrid' style='width:50%'>" . $memoDocument["PE_MEMO_DESCRIPTION"] . "</td>";
            $pdfHTML .= "</tr>";
        }
        $pdfHTML .= "</table>";
        $pdfHTML .= "</td>";
        $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Additional Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_LEGAL_REVIEW_ADDITIONAL_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";
    }
    //TX.03 Tax Review Memo
    $pdfHTML .= addSectionTitle("Tax Review Memo");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_TAX_REVIEW_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:50%'>Memo Upload</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:50%'>Description</th>";
    $pdfHTML .= "</tr>";
    foreach ($data["PE_TAX_REVIEW_MEMO_DOCUMENTS"] as $memoDocument) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:50%'>" . setDocumentLink($processRequestId, $memoDocument["PE_TAX_REVIEW_MEMO_UPLOAD"], "", $serverUrl, $documentsNames) . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:50%'>" . $memoDocument["PE_TAX_REVIEW_MEMO_DESCRIPTION"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Additional Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_TAX_REVIEW_ADDITIONAL_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";
    //DT.02
    if ($data["PE_CFOF_DOCUMENT_REQUIRED"] == "YES" || $data["PE_NVCF_DOCUMENT_REQUIRED"] == "YES") {
        $pdfHTML .= addSectionTitle("Complete Heads up memo");
        if ($data["PE_CFOF_DOCUMENT_REQUIRED"] == "YES") {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>CFOF Upload:</td>";
            $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PE_CFOF_UPLOAD"], "", $serverUrl, $documentsNames) . "</td>";
            $pdfHTML .= "</tr>";
        }
        if ($data["PE_NVCF_DOCUMENT_REQUIRED"] == "YES") {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>NVCF Upload:</td>";
            $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PE_NVCF_UPLOAD"], "", $serverUrl, $documentsNames) . "</td>";
            $pdfHTML .= "</tr>";
        }
    }
    //IC.01 Investment Advisor Approval
    if ($data["IA_01_IS_NECESSARY"] == "YES") {
        $pdfHTML .= addSectionTitle("Investment Advisor Approval");
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Investment Advisor Approver:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_INVESTMENT_ADVISOR_APPROVER_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Investment Adviser Review and Authorization:</td>";
        $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PE_INVESTMENT_ADVISOR_DOCUMENT_GENERATED_ID"], "", $serverUrl, $documentsNames) . "</td>";
        $pdfHTML .= "</tr>";
    }

    //LL.06 Final sign-off including Risk & Process Review
    $pdfHTML .= addSectionTitle("Portfolio Manager Approval");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Portfolio Manager Approval:</td>";
    $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PA_PDF_ID"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "</tr>";
    //TX.04 Provide Final Closing Authorization
    $pdfHTML .= addSectionTitle("Provide Final Closing Authorization");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Confirm key legal document issues are resolved and signatures can be sent in escrow:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_LEGAL_DOCUMENTS_ISSUES_RESOLVED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Ensure key Northleaf legal requirements are addressed:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_ENSURE_KEY_LEGAL_REQUIREMENTS_ADDRESSED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "</table>";
    $pdfHTML .= "</body>";
    $pdfHTML .= "</html>";

    // Create the PDF
    $html2pdf->writeHTML($pdfHTML);

    // Save the PDF to the specified path
    $html2pdf->output($outputDocumentTempPath, 'F');
}

//Get environment variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$northleafLogoUrl = getenv('ORIGINAL_URL_PDF_LOGO');
$cherryCheckUrl = getenv("CHECK_IMAGE_FOR_DOCUMENTS_URL");
$serverUrl = getenv('ENVIRONMENT_BASE_URL');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');

//Initialize variables
$processRequestId = $data['_request']['id'];
$currentCaseNumber = $data['_request']['case_number'];
$pdfName = "Final sign-off including Risk & Process Review.pdf";
$outputDocumentTempPath = '/tmp/' . $pdfName;
$dataToGeneratePDF = $data;

//Get Users Name with user ID
$usersIDs = array();
$usersIDs["PE_BLACK_HAT_REVIEW"] = $dataToGeneratePDF["PE_BLACK_HAT_REVIEW"];
$usersIDs["PE_RED_FLAG_LEGAL_REVIEW"] = $dataToGeneratePDF["PE_RED_FLAG_LEGAL_REVIEW"];
$usersIDs["PE_RED_FLAG_TAX_REVIEW"] = $dataToGeneratePDF["PE_RED_FLAG_TAX_REVIEW"];
$usersIDs["PE_PORTFOLIO_MANAGER_APPROVER"] = $dataToGeneratePDF["PE_PORTFOLIO_MANAGER_APPROVER"];
$dataToGeneratePDF = getUsersName($usersIDs, $dataToGeneratePDF, $apiUrl);

//Get Documents Name
$documentsToValidate = array();
//Due Dilligence
$documentsToValidate[] = array(
    "VARIABLE" => "PE_UPLOAD_DD_REC",
    "CONDITION" => ($data["PE_UPLOAD_DD_REC_NA"] !== true) ? "YES" : "NO"
);
//Beat Up
$documentsToValidate[] = array(
    "VARIABLE" => "PE_UPLOAD_BEAT_UP",
    "CONDITION" => ($data["PE_UPLOAD_BEAT_UP_NA"] !== true) ? "YES" : "NO"
);
//Black Hat
$documentsToValidate[] = array(
    "VARIABLE" => "PE_UPLOAD_BLACK_HAT",
    "CONDITION" => ($data["PE_UPLOAD_BLACK_HAT_NA"] !== true) ? "YES" : "NO"
);
//IC Presentation
$documentsToValidate[] = array(
    "VARIABLE" => "PE_UPLOAD_IC_PRESENTATION",
    "CONDITION" => ""
);
//CFOF Document
$documentsToValidate[] = array(
    "VARIABLE" => "PE_CFOF_UPLOAD",
    "CONDITION" => ($data["PE_CFOF_DOCUMENT_REQUIRED"] == "YES") ? "YES" : "NO"
);
//NVCF Document
$documentsToValidate[] = array(
    "VARIABLE" => "PE_NVCF_UPLOAD",
    "CONDITION" => ($data["PE_NVCF_DOCUMENT_REQUIRED"] == "YES") ? "YES" : "NO"
);
//Funding Request and Authorization uploaded
$documentsToValidate[] = array(
    "VARIABLE" => "FRA_PDF",
    "CONDITION" => ""
);
//Final Funding amount file
$documentsToValidate[] = array(
    "VARIABLE" => "PE_FINAL_FUNDING_AMOUNT_FILE",
    "CONDITION" => ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") ? "YES" : "NO"
);
//Wire Reference
$documentsToValidate[] = array(
    "VARIABLE" => "PE_UPLOAD_WIRE_REFERENCE",
    "CONDITION" => ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") ? "YES" : "NO"
);
//Legal Review Memo
$documentsToValidate[] = array(
    "VARIABLE" => "PE_MEMO_DOCUMENT",
    "CONDITION" => ($data["PE_LEGAL_REVIEW_NOT_APPLICABLE"] !== true) ? "YES" : "NO",
    "IF_ARRAY_DOCUMENT_VARIABLE" => "PE_MEMO_UPLOAD"
);
//Tax Review Memo
$documentsToValidate[] = array(
    "VARIABLE" => "PE_TAX_REVIEW_MEMO_DOCUMENTS",
    "CONDITION" => "",
    "IF_ARRAY_DOCUMENT_VARIABLE" => "PE_TAX_REVIEW_MEMO_UPLOAD"
);
//Investment Advisor Approval
$documentsToValidate[] = array(
    "VARIABLE" => "PE_INVESTMENT_ADVISOR_DOCUMENT_GENERATED_ID",
    "CONDITION" => $data["IA_01_IS_NECESSARY"] ?? "NO"
);
//Portfolio Manager Approval
$documentsToValidate[] = array(
    "VARIABLE" => "PA_PDF_ID",
    "CONDITION" => ""
);
$documentsNames = getDocumentsNames($dataToGeneratePDF, $documentsToValidate, $apiUrl);
//Get IC Approvers Response
$queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                      FROM collection_" . $masterCollectionID . "
                      WHERE data->>'$.COLLECTION_NAME' IN ('PE_IC_APPROVER_RESPONSE')";
$collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
if (!empty($collectionInfo[0]["ID"])) {
    $getICApprovers = "SELECT data->>'$.IAR_APPROVER_FULL_NAME' AS IAR_APPROVER_FULL_NAME,
                              data->>'$.IAR_APPROVER_COMMENTS' AS IAR_APPROVER_COMMENTS
                        FROM collection_" . $collectionInfo[0]["ID"] . " 
                        WHERE data->>'$.IAR_CASE_NUMBER' = " . $currentCaseNumber;
    $icApproversResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getICApprovers));
    $dataToGeneratePDF["IC_APPROVERS_RESPONSE"] = $icApproversResponse;
}
//Generate the PDF
$dataToGeneratePDF["NORTHLEAF_LOGO_URL"] = $northleafLogoUrl;
$dataToGeneratePDF["CHERRY_CHECK_URL"] = $serverUrl . $cherryCheckUrl;
generatePDF($outputDocumentTempPath, $dataToGeneratePDF, $serverUrl, $documentsNames);
//Add pdf to request
$newFile = $apiInstance->createRequestFile($processRequestId, $pdfName, $outputDocumentTempPath);
$fileUID = $newFile->getFileUploadId();
$fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;

return array(
    "PE_LL04_PDF" => $fileGeneratedUrl,
    "PE_LL04_PDF_ID" => $fileUID
);