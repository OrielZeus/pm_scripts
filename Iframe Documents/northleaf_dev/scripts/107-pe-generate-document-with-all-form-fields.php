<?php
/**********************************************
 * PE - Generate Document With All Form Fields
 *
 * by Cinthia Romero
 * modified by Adriana Centellas
 * modified by Telmo Chiri
 * modified by Favio Mollinedo
 * modified by Diego Tapia
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
                    //$documentLink = $serverUrl . 'request/' . $processRequestId . '/files/' . $document[$documentKey];
                    //$htmlDocument .= "<a download='" . $documentName . "' href='" . $documentLink . "'>" . $documentName . "</a>";
                    $htmlDocument .= $documentName;
                }
            }
        } else {
            if (!empty($documentsNames[$documentValue])) {
                $documentName = $documentsNames[$documentValue];
                //$documentLink = $serverUrl . 'request/' . $processRequestId . '/files/' . $documentValue;
                //$htmlDocument = "<a download='" . $documentName . "' href='" . $documentLink . "'>" . $documentName . "</a>";
                $htmlDocument .= $documentName;
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
    return number_format((float) $number, 2, '.', ',');
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
    $filteredValues = array_filter($usersIDs, function ($value) {
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
    $emptyValues = array_filter($usersIDs, function ($value, $key) {
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
    $imageCherry = file_get_contents(getenv("CHECK_IMAGE_FOR_DOCUMENTS_URL_NEW"));
    file_put_contents('/tmp/cherry.png', $imageCherry);
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $html2pdf->setTestTdInOnePage(false);
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);
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
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2' class='titleRow'>Private Equity Deal Closing Document</td>";
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
    $pdfHTML .= "<td class='label'>Type of Deal:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_TYPE"] . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_DEAL_TYPE"] == "Secondary") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Secondary Deal Type:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_SECONDARY_DEAL_TYPE"] . "</td>";
        $pdfHTML .= "</tr>";
    }
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
    if ($data["PE_DEAL_TYPE"] != "Primary") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Base Case MOIC:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_BASE_CASE_MOIC"] . "x</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Base Case IRR:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_BASE_CASE_IRR"] . "%</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Target Close Date:</td>";
    $pdfHTML .= "<td class='text'>" . formatDate($data["PE_TARGET_CLOSE_DATE"], "m/d/Y") . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Salesforce ID:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_SALESFORCE_ID"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Date of IC Approval:</td>";
    $pdfHTML .= "<td class='text'>" . formatDate($data["PE_DATE_APPROVAL"], "m/d/Y") . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Deal Team Information");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Deal Team Senior:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_TEAM_SENIOR_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";

    if ($data["PE_DEAL_TEAM_SENIOR_2_LABEL"] != null && $data["PE_DEAL_TEAM_SENIOR_2_LABEL"] != "") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Deal Team Senior 2:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_TEAM_SENIOR_2_LABEL"] . "</td>";
        $pdfHTML .= "</tr>";
    } 

    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>IC Sponsor:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_IC_SPONSOR_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Deal Team Junior:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_TEAM_JUNIOR_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Black Hat Review:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_BLACK_HAT_REVIEW_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Mandates");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>IC approval amount:</td>";
    $pdfHTML .= "<td class='text'>" . formatCurrency($data["PE_MANDATE_INITIAL_TOTAL_AMOUNT"]) . "</td>";
    $pdfHTML .= "</tr>";
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
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . round($mandate["PE_MANDATE_PERCENTAGE_DEAL"], 2) . "%</td>";
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
    $pdfHTML .= addSectionTitle("Conditions to IC approval");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Conditions to IC approval at time of IC presentation:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_CONDITIONS_IC_APPROVAL"] . "</td>";
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
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Document Type</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>DOcument Name</th>";
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

    //DT.02
    $pdfHTML .= addSectionTitle("Assign Legal Counsel and Tax Representative");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Legal Review:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_RED_FLAG_LEGAL_REVIEW_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Tax Review:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_RED_FLAG_TAX_REVIEW_LABEL"] . "</td>";
    $pdfHTML .= "</tr>";

    //DT.03
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
    //DT.05
    $pdfHTML .= addSectionTitle("Complete AML Request form");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Due date:</td>";
    $pdfHTML .= "<td class='text'>" . formatDate($data["PE_DUE_DATE"], "m/d/Y") . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:12%'>Full Entity Name</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:9%'>One-Time Screen Sanctions</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:9%'>One-Time Screen Adverse Media</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:9%'>Ongoing Screen Sanctions</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:9%'>Ongoing Screen Adverse Media</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:13%'>Entity Address</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:13%'>Individual Names</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:13%'>Individual State/Province and Country Location</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:13%'>Additional Information for Individuals</th>";
    $pdfHTML .= "</tr>";
    foreach ($data["PE_ENTITIES"] as $mandate) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:12%'>" . $mandate["PE_ENTITY_FULL_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:9%'>" . setCheckGrid($mandate["PE_ENTITY_ONTIME_SCREEN_SANCTIONS"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:9%'>" . setCheckGrid($mandate["PE_ENTITY_ONTIME_SCREEN_ADVERSE_MEDIA"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:9%'>" . setCheckGrid($mandate["PE_ENTITY_ONGOING_SCREEN_SANCTIONS"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:9%'>" . setCheckGrid($mandate["PE_ENTITY_ONGOING_SCREEN_ADVERSE_MEDIA"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:13%'>" . $mandate["PE_ENTITY_ADDRESS"] . "</td>";
        $pdfHTML .= "<td class='textGrid' colspan='3' style='width:39%'>";
        $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
        foreach ($mandate["PE_INDIVIDUAL_INFORMATION"] as $individual) {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='textGrid' style='width:34%;'>" . $individual["PE_INDIVIDUAL_NAME"] . "</td>";
            $pdfHTML .= "<td class='textGrid' style='width:33%'>" . $individual["PE_INDIVIDUAL_STATE_PROVINCE"] . "</td>";
            $pdfHTML .= "<td class='textGrid' style='width:33%'>" . $individual["PE_INDIVIDUAL_ADDITIONAL_INFORMATION"] . "</td>";
            $pdfHTML .= "</tr>";
        }
        $pdfHTML .= "</table>";
        $pdfHTML .= "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    //LC.01 Conduct KYC/AML on sponsor and portfolio company (node_LC02)
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>AML Results:</td>";
    $amlResults = $data["PE_AML_RESULTS_DOCUMENT"];
    if (!is_array($amlResults)) {
        $auxResults = $amlResults;
        $amlResults = array();
        $amlResults[0]["file"] = $auxResults;
    }
    $pdfHTML .= "<td class='text'>";
    $pdfHTML .= "<table style='width:100%'>";
    foreach ($amlResults as $result) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='text' style='width:100%'>" . setDocumentLink($processRequestId, $result["file"], "", $serverUrl, $documentsNames) . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Complete?</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_AML_REVIEW_COMPLETE"] . "</td>";
    $pdfHTML .= "</tr>";
    //DT.06
    $pdfHTML .= addSectionTitle("Obtain wire instructions and call back contacts");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Does deal require funding on close?</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Confirm that wire instructions have been sent to the cash team:</td>";
        $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_WIRE_INSTRUCTIONS_SENT"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "</tr>";
    }
    // DT.07
    $pdfHTML .= addSectionTitle("Complete draft deal metrics file");
    if ($data["PE_DEAL_TYPE"] == "Primary") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Fund Legal Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_PRIMARY_FUND_LEGAL"] . "</td>";
        $pdfHTML .= "</tr>";
        /*$pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Fund Currency:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_PRIMARY_FUND_CURRENCY"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>GP:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_PRIMARY_GP"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>New GP:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_PRIMARY_NEW_GP"] . "</td>";
        $pdfHTML .= "</tr>";*/
    }
    if ($data["PE_DEAL_TYPE"] == "Secondary") {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td colspan='2'>";
        $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<th class='titleGrid' style='width:100%'>Fund Legal Name</th>";
        /*$pdfHTML .= "<th class='titleGrid' style='width:25%'>GP</th>";
        $pdfHTML .= "<th class='titleGrid' style='width:25%'>New GP</th>";
        $pdfHTML .= "<th class='titleGrid' style='width:25%'>Reporting Frequency</th>";*/
        $pdfHTML .= "</tr>";
        if (is_array($data["PE_SECONDARY_FUNDS_LOOP"])) {
            foreach ($data["PE_SECONDARY_FUNDS_LOOP"] as $secondaries) {
                $pdfHTML .= "<tr>";
                $pdfHTML .= "<td class='textGrid' style='width:100%'>" . $secondaries["PE_SECONDARY_FUND_LEGAL"] . "</td>";
                /*$pdfHTML .= "<td class='textGrid' style='width:25%'>" . $secondaries["PE_SECONDARY_GP"] . "</td>";
                $pdfHTML .= "<td class='textGrid' style='width:25%'>" . $secondaries["PE_SECONDARY_NEW_GP"] . "</td>";
                $pdfHTML .= "<td class='textGrid' style='width:25%'>" . $secondaries["PE_SECONDARY_REPORTING_FREQUENCY"] . "</td>";*/
                $pdfHTML .= "</tr>";
            }
        }
        $pdfHTML .= "</table>";
        $pdfHTML .= "</td>";
        $pdfHTML .= "</tr>";
        if ($data["PE_SECONDARY_DEAL_TYPE"] == "Direct Secondary") {
            /*$pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>Company Name:</td>";
            $pdfHTML .= "<td class='text'>" . $data["PE_SECONDARY_COMPANY_NAME"] . "</td>";
            $pdfHTML .= "</tr>";*/
        }
    }
    if ($data["PE_DEAL_TYPE"] == "Direct") {
        /*$pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Company Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_COMPANY_NAME"] . "</td>";
        $pdfHTML .= "</tr>";*/
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Fund Legal Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_FUND_LEGAL"] . "</td>";
        $pdfHTML .= "</tr>";
        /*$pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Fund Currency:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_FUND_CURRENCY"] . "</td>";
        $pdfHTML .= "</tr>";
        if ($data["PE_DIRECT_FUND_CURRENCY"] == "Other") {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>Fund Other Currency:</td>";
            $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_FUND_OTHER_CURRENCY"] . "</td>";
            $pdfHTML .= "</tr>";
        }
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>GP:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_FUND_GP"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>New GP:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_FUND_NEW_GP"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Class of Shares (if applicable):</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_CLASS_SHARES"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Number of Shares (if applicable):</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_DIRECT_NUMBER_SHARES"] . "</td>";
        $pdfHTML .= "</tr>";*/
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Draft deal metrics file completed for Operations team to review:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_DRAFT_DEAL_METRICS_FILE_COMPLETED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";

    //DT.08
    if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") {
        $pdfHTML .= addSectionTitle("Finalize funding file");
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Confirm funding file (by mandate) sent to Cash team:</td>";
        $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_FUNDING_AMOUNT_SENT_OPS"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Confirm currency to Cash team:</td>";
        $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_OPS_FUNDING_CURRENCY"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Final funding file (by mandate):</td>";
        $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PE_FINAL_FUNDING_AMOUNT_FILE"], "", $serverUrl, $documentsNames) . "</td>";
        $pdfHTML .= "</tr>";
    }
    //LC.02
    $pdfHTML .= addSectionTitle("External Legal Counsel Contact Information");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONTACT_INFORMATION_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_CONTACT_INFORMATION_NOT_APPLICABLE"] !== true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Company Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_LEGAL_COUNSEL_COMPANY_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Contact Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_LEGAL_COUNSEL_CONTACT_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $emails = "";
        foreach ($data["PE_LEGAL_COUNSEL_EMAIL_TABLE"] as $email) {
            if ($emails != "") {
                $emails .= "<br>";
            }
            $emails .= $email["PE_LEGAL_COUNSEL_EMAIL"];
        }
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Email:</td>";
        $pdfHTML .= "<td class='text'>" . $emails . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Phone Number:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_LEGAL_COUNSEL_PHONE_NUMBER"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Additional Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_CONTACT_INFORMATION_ADDITIONAL_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";

    $pdfHTML .= addSectionTitle("GP Counsel Contact Information");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_GP_CONTACT_INFORMATION_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if (isset($data["PE_GP_CONTACT_INFORMATION_NOT_APPLICABLE"]) && $data["PE_GP_CONTACT_INFORMATION_NOT_APPLICABLE"] !== true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Company Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_GP_COUNSEL_COMPANY_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Contact Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_GP_COUNSEL_CONTACT_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $emails = "";
        foreach ($data["PE_GP_COUNSEL_EMAIL_TABLE"] as $email) {
            if ($emails != "") {
                $emails .= "<br>";
            }
            $emails .= $email["PE_GP_COUNSEL_EMAIL"];
        }
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Email:</td>";
        $pdfHTML .= "<td class='text'>" . $emails . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Phone Number:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_GP_COUNSEL_PHONE_NUMBER"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Additional Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_GP_CONTACT_INFORMATION_ADDITIONAL_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";
    //LL.02
    $pdfHTML .= addSectionTitle("Confirm KYC/AML on sponsor and portfolio company");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Approved?</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_AML_LEGAL_REVIEW_COMPLETE"] == "Yes", '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    //LC.03 Legal Review Memo
    $pdfHTML .= addSectionTitle("Legal Review Memo");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_LEGAL_REVIEW_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_LEGAL_REVIEW_NOT_APPLICABLE"] !== true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Description:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_MEMO_DESCRIPTION"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td colspan='2'>";
        $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<th class='titleGrid' style='width:100%'>Memo Upload</th>";
        $pdfHTML .= "</tr>";
        foreach ($data["PE_MEMO_DOCUMENT"] as $memoDocument) {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='textGrid' style='width:100%'>" . setDocumentLink($processRequestId, $memoDocument["PE_MEMO_UPLOAD"], "", $serverUrl, $documentsNames) . "</td>";
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
    //LL.01
    $pdfHTML .= addSectionTitle("SPV Confirmation");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Is an SPV required?</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_SPV_REQUIRED"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Transaction Documents");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Advisory/Management Agreement:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_ADVISORY_MANAGEMENT_AGREEMENT"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Certificate(s):</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CERTIFICATES"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Corporate documents - articles/bylaws:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CORPORATE_DOCUMENTS_ARTICLES"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Governing documents - LPA/LLCA/shareholder agreement:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_GOVERNING_DOCUMENTS_AGREEMENT"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Guarantee:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_GUARANTEE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Opinion:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_OPINION"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Purchase & Sale Agreement:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_PURCHASE_SALE_AGREEMENT"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Side Letter:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_SIDE_LETTER"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Subscription Agreement:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_SUBSCRIPTION_AGREEMENT"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Transfer Agreement:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_TRANSFER_AGREEMENT"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Other:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_OTHER_LL2"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_OTHER_LL2"]) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Other Description:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_LL2_OTHER_DESCRIPTION"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_SPV_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";
    //OR.02
    if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") {
        $pdfHTML .= addSectionTitle("Prepare Funding");
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Comments:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_PREPARE_FUNDING_COMMENTS"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    //TX.01
    $pdfHTML .= addSectionTitle("External Tax Counsel Contact Information");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_TAX_COUNSEL_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_TAX_COUNSEL_NOT_APPLICABLE"] !== true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Company Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_TAX_COUNSEL_COMPANY_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Contact Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_TAX_COUNSEL_CONTACT_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $emails = "";
        foreach ($data["PE_TAX_COUNSEL_EMAIL_TABLE"] as $email) {
            if ($emails != "") {
                $emails .= "<br>";
            }
            $emails .= $email["PE_TAX_COUNSEL_EMAIL"];
        }
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Email:</td>";
        $pdfHTML .= "<td class='text'>" . $emails . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Phone Number:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_TAX_COUNSEL_PHONE_NUMBER"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Additional Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_TAX_COUNSEL_ADDITIONAL_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Additional External Tax Counsel Contact Information");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Not Applicable:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_EXTERNAL_TAX_COUNSEL_NOT_APPLICABLE"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    if ($data["PE_EXTERNAL_TAX_COUNSEL_NOT_APPLICABLE"] !== true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Company Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_EXTERNAL_TAX_COUNSEL_COMPANY_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Contact Name:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_EXTERNAL_TAX_COUNSEL_CONTACT_NAME"] . "</td>";
        $pdfHTML .= "</tr>";
        $emails = "";
        foreach ($data["PE_EXTERNAL_TAX_COUNSEL_EMAIL_TABLE"] as $email) {
            if ($emails != "") {
                $emails .= "<br>";
            }
            $emails .= $email["PE_EXTERNAL_TAX_COUNSEL_EMAIL"];
        }
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Email:</td>";
        $pdfHTML .= "<td class='text'>" . $emails . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Phone Number:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_EXTERNAL_TAX_COUNSEL_PHONE_NUMBER"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Additional Comments:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_EXTERNAL_TAX_COUNSEL_ADDITIONAL_COMMENTS"] . "</td>";
    $pdfHTML .= "</tr>";
    //TX.02 Tax Review Memo
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

    //TX.04 => TX.02 Tax Post-Close
    $pdfHTML .= addSectionTitle("Tax Post-Close");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Fund Legal Name</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:15%'>Is the tax entity same as the legal entity?</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>If No, what is the tax entity?</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:15%'>Is there an alternative investment vehicle ('AIV')?</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>If yes, what is the tax entity?</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:10%'>Tax Domicile country</th>";
    $pdfHTML .= "</tr>";
    foreach ($data["FUND_LEGAL_NAME_LOOP"] as $fundLegal) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $fundLegal["FUND_LEGAL_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:15%'>" . $fundLegal["TAX_ENTITY_LEGAL_ENTITY"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $fundLegal["ENTITY"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:15%'>" . $fundLegal["ALTERNATIVE_INVESTMENT_VEHICLE"] . "</td>";
        $entityAivHTML = "";
        if (!empty($fundLegal["ENTITY_AIV_ARRAY"])) {
            foreach ($fundLegal["ENTITY_AIV_ARRAY"] as $entityAiv) {
                $entityAivHTML .= $entityAiv["ENTITY_AIV"]."<br>";
            }
        }
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $entityAivHTML . "</td>";
        if ($fundLegal["PE_TAX_DOMICILE_COUNTRY_NA"] == true) {
            $pdfHTML .= "<td class='textGrid' style='width:10%'>N/A</td>";
        } else {
            $pdfHTML .= "<td class='textGrid' style='width:10%'>" . $fundLegal["PE_TAX_DOMICILE_COUNTRY"] . "</td>";
        }
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";

    //IC.01
    $pdfHTML .= addSectionTitle("IC Approval");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:50%'>Approver Name</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:50%'>Comments</th>";
    $pdfHTML .= "</tr>";
    if (!empty($data["IC_APPROVERS_RESPONSE"]) && is_array($data["IC_APPROVERS_RESPONSE"])) {
        foreach ($data["IC_APPROVERS_RESPONSE"] as $approver) {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='textGrid' style='width:50%'>" . $approver["IAR_APPROVER_FULL_NAME"] . "</td>";
            if ($approver["IAR_APPROVER_COMMENTS"] != null && $approver["IAR_APPROVER_COMMENTS"] != "NULL" && $approver["IAR_APPROVER_COMMENTS"] != "null") {
                $pdfHTML .= "<td class='textGrid' style='width:50%'>" . $approver["IAR_APPROVER_COMMENTS"] . "</td>";
            } else {
                $pdfHTML .= "<td class='textGrid' style='width:50%'></td>";
            }
            $pdfHTML .= "</tr>";
        }
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>IC Approval Document:</td>";
    $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PDF_IC_APPROVAL_ID"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "</tr>";
    //TX.03 Provide Final Closing Authorization
    $pdfHTML .= addSectionTitle("Provide Final Closing Authorization");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Confirm tax review is complete:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_TAX_REVIEW"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Confirm key tax document issues are resolved and signatures can be sent in escrow:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_TAX_DOCUMENTS_ISSUES_RESOLVED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Ensure key Northleaf tax requirements are addressed:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_ENSURE_KEY_TAX_REQUIREMENTS_ADDRESSED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    
    //PA.01
    $pdfHTML .= addSectionTitle("Portfolio Manager Approval");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Portfolio Manager Approver:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_PORTFOLIO_MANAGER_APPROVER_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Portfolio Manager Authorization:</td>";
    $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["PA_PDF_ID"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "</tr>";
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
    //LL.03
    $pdfHTML .= addSectionTitle("Legal Counsel Final Sign Off");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Confirm key legal document issues are resolved and signatures can be sent in escrow:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_LEGAL_DOCUMENTS_ISSUES_RESOLVED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Ensure key Northleaf legal requirements are addressed:</td>";
    $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_ENSURE_KEY_LEGAL_REQUIREMENTS_ADDRESSED"], '/tmp/cherry.png') . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    //LC.04 Review Funding Authorization and obtain signatures
    $pdfHTML .= addSectionTitle("Review Funding Authorization and obtain signatures");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Request Primary Authorization:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_PRIMARY_AUTHORIZATION"]["USER_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Request Secondary Authorization:</td>";
    $pdfHTML .= "<td class='text'>" . $data["PE_SECONDARY_AUTHORIZATION"]["USER_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Funding Request and Authorization:</td>";
    $pdfHTML .= "<td class='text'>" . setDocumentLink($processRequestId, $data["FRA_PDF"], "", $serverUrl, $documentsNames) . "</td>";
    $pdfHTML .= "</tr>";
    //LL.04
    if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") {
        $pdfHTML .= addSectionTitle("Final Funding Authorization");
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Confirm signatures can be released from escrow and funding can be released:</td>";
        $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_CONFIRM_SIGNATURES_RELEASED_ESCROW"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "</tr>";
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td colspan='2'></td>";
        $pdfHTML .= "</tr>";
        //OR.03 Confirm funds release
        $pdfHTML .= addSectionTitle("Confirm funds release");
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Funds Released:</td>";
        $pdfHTML .= "<td class='text'>" . setCheckGrid($data["PE_FUNDS_RELEASED"], '/tmp/cherry.png') . "</td>";
        $pdfHTML .= "</tr>";
    }
    //DT.10 Complete post-close checklist
    $pdfHTML .= addSectionTitle("Northleaf Private Equity Closing Check List");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Task</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>To Do</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:10%'>Deal Type</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Responsability</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Complete</th>";
    $pdfHTML .= "</tr>";
    foreach ($data["PE_CLOSING_CHECK_LIST"] as $postClose) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $postClose["PE_CLOSING_CHECK_TASK"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:30%'>" . $postClose["PE_CLOSING_CHECK_TODO"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:10%'>" . $postClose["PE_CLOSING_CHECK_DEAL_TYPE"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $postClose["PE_CLOSING_CHECK_RESPONSIBILITY"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $postClose["PE_CLOSING_CHECK_COMPLETE"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Legal Post-Close");
    if ($data["PE_DEAL_TYPE"] == "Secondary") {
        if ($data["PE_COMMITMENT_CLOSING_DATE_NA"] == true) {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>Commitment closing date:</td>";
            $pdfHTML .= "<td class='text'>N/A</td>";
            $pdfHTML .= "</tr>";
        } else {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>Commitment closing date:</td>";
            $pdfHTML .= "<td class='text'>" . (!empty($data["PE_COMMITMENT_CLOSING_DATE"]) ? formatDate($data["PE_COMMITMENT_CLOSING_DATE"], "m/d/Y") : "") . "</td>";
            $pdfHTML .= "</tr>";
        }
    }
    if ($data["PE_NAME_DISCLOSURE_RESTRICTION_NA"] == true) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Name Disclosure Restriction?:</td>";
        $pdfHTML .= "<td class='text'>N/A</td>";
        $pdfHTML .= "</tr>";
    } else {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='label'>Name Disclosure Restriction?:</td>";
        $pdfHTML .= "<td class='text'>" . $data["PE_NAME_DISCLOSURE_RESTRICTION"] . "</td>";
        $pdfHTML .= "</tr>";
        if ($data["PE_NAME_DISCLOSURE_RESTRICTION"] == "Yes") {
            if ($data["PE_NAME_DISCLOSURE_RESTRICTION_DETAILS_NA"] == true) {
                $pdfHTML .= "<tr>";
                $pdfHTML .= "<td class='label'>If yes, details:</td>";
                $pdfHTML .= "<td class='text'>N/A</td>";
                $pdfHTML .= "</tr>";
            } else {
                $pdfHTML .= "<tr>";
                $pdfHTML .= "<td class='label'>If yes, details:</td>";
                $pdfHTML .= "<td class='text'>" . $data["PE_NAME_DISCLOSURE_RESTRICTION_DETAILS"] . "</td>";
                $pdfHTML .= "</tr>";
            }
        }
    }
    //OR.04 Operations Post-Close
    if ($data["PE_DEAL_TYPE"] == "Secondary") {
        $pdfHTML .= addSectionTitle("Operations Post-Close");
        if ($data["PE_TAX_TRANSFER_EFFECTIVE_DATE_NA"] == true) {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>Transfer effective date:</td>";
            $pdfHTML .= "<td class='text'>N/A</td>";
            $pdfHTML .= "</tr>";
        } else {
            $pdfHTML .= "<tr>";
            $pdfHTML .= "<td class='label'>Transfer effective date:</td>";
            $pdfHTML .= "<td class='text'>" . (!empty($data["PE_TAX_TRANSFER_EFFECTIVE_DATE"]) ? formatDate($data["PE_TAX_TRANSFER_EFFECTIVE_DATE"], "m/d/Y") : "") . "</td>";
            $pdfHTML .= "</tr>";
        }
    }

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
$pdfName = "Private Equity Deal Closing Document.pdf";
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
//AML Results
$documentsToValidate[] = array(
    "VARIABLE" => "PE_AML_RESULTS_DOCUMENT",
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
    "VARIABLE" => "PE_INVESTMENT_ADVISOR_DOCUMENT_GENERATED",
    "CONDITION" => $data["IA_01_IS_NECESSARY"] ?? "NO"
);
//PA.01
$documentsToValidate[] = array(
    "VARIABLE" => "PA_PDF",
    "CONDITION" => ""
);
//IC.01
$documentsToValidate[] = array(
    "VARIABLE" => "PDF_IC_APPROVAL",
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
    "PE_ALL_FORMS_PDF" => $fileGeneratedUrl,
    "PE_ALL_FORMS_PDF_ID" => $fileUID
);