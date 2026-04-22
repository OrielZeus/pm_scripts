<?php
/**********************************
 * IN - DHS Pre Processing Consolidated
 *
 *********************************/
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");

$apiInstance = $api->requestFiles();
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;
$requestId = $data['_request']['id'];
$environmentBaseUrl = getenv("ENVIRONMENT_BASE_URL");

//Get collection ID
$collectionName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get Collection Data
$commentLogData = getCommentsLog($collectionID, $apiUrl, $requestId);

$dataReturn["IN_COMMENT_LOG"] = $commentLogData;

//Clear variables - Jhon Chacolla
//$dataReturn["IN_SAVE_SUBMIT"] = "SUBMIT";
$dataReturn["fakeSaveCloseButton"] = null;
$dataReturn["saveButtonFake"] = null;
$dataReturn["submitButtonFake"] = null;
$dataReturn["validateForm"] = null;
$dataReturn["saveForm"] = null;
$dataReturn["saveFormClose"] = null;
$dataReturn["validation"] = null;
$dataReturn["readyScreen"] = null;
// $dataReturn["IN_SUBMITTER_MANAGER_EDIT_ACTION"] = null;
// $dataReturn["IN_COMMENT_MANAGER_EDIT"] = null;
// Clean Action Variables  DHS.01
$dataReturn["IN_SUBMITTER_MANAGER_ACTION"] = null;
$dataReturn["IN_COMMENT_SUBMITTER"] = null;

$dataReturn["IN_VENDOR_NAME"] = $data["vendorInformation"]["0"]["VENDOR_LABEL"];
$dataReturn["IN_CASE_NUMBER"] = $data['_request']['case_number'];
$dataReturn["DHS01_LINK_TO_APPROVE"] = $environmentBaseUrl . "webentry/request/" . $requestId . "/abeNode?addComments=1";
$dataReturn["DHS01_LINK_TO_EDIT"] = $environmentBaseUrl . "webentry/request/" . $requestId . "/abeNode?addComments=2";
$dataReturn["DHS01_LINK_TO_REJECT"] = $environmentBaseUrl . "webentry/request/" . $requestId . "/abeNode?addComments=3";

//Format Amounts
$dataReturn["IN_TOTAL_PRE_TAX_AMOUNT_FORMATTED"] = $data["IN_INVOICE_PRE_TAX"];
$dataReturn["IN_INVOICE_PRE_TAX_FORMATTED"] = number_format($data["IN_INVOICE_TAX_TOTAL"], 2, '.', ',');
$dataReturn["IN_INVOICE_TOTAL_FORMATTED"] = number_format($data["IN_INVOICE_TOTAL"], 2, '.', ',');

/*  
 *  IN - DHS Table PDF
 *  By Adriana Centellas
 */

//Get global Variables

$northleafLogoUrl = getenv('ORIGINAL_URL_PDF_LOGO');
$cherryCheckUrl = getenv("CHECK_IMAGE_FOR_DOCUMENTS_URL");
$serverUrl = getenv('ENVIRONMENT_BASE_URL');

//Initialize variables
$processRequestId = $data['_request']['id'];
$caseNumber = $data['_request']['case_number'];
$pdfName = "DHS.01-Invoice Process-" . $dataReturn["IN_CASE_NUMBER"] . ".pdf";
$outputDocumentTempPath = '/tmp/' . $pdfName;


$role = "";
$comments = "";
if ($data['newStatus'] == "corporateH") {
    $role = "Corporate Finance Approver";
    $comments = empty($data["IN_COMMENT_COH"]) ? "" : $data["IN_COMMENT_COH"];
    $approval = $data["IN_SUBMITTER_COH"] == "Rejected" ? "Rejected" : "Approved";
}

//Save comments into collection
/*$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => $data["commentsData"]["userName"],
    "IN_CL_USER_ID" => $data["commentsData"]["userID"],
    "IN_CL_ROLE" => $role,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => $approval,
    "IN_CL_COMMENT_SAVED" => $comments,
    "IN_CL_DATE" => date("m/d/Y H:i:s"),
    "IN_SUBMIT" => null
];
$getResponse    = postRecordToCollection($dataRecord, getCollectionId("IN_COMMENTS_LOG", $apiUrl));
*/

$dataToGeneratePDF = $data;

$dataToGeneratePDF["NORTHLEAF_LOGO_URL"] = $northleafLogoUrl;
$dataToGeneratePDF["CHERRY_CHECK_URL"] = $serverUrl . $cherryCheckUrl;

//Get History Comments for current Case
$collectionTasksName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionTasksName, $apiUrl);

$getHistoryComments = "SELECT 
    IFNULL(NULLIF(NULLIF(data->>'$.IN_CL_USER', ''), 'null'), '') AS IN_CL_USER,
    IFNULL(NULLIF(NULLIF(data->>'$.IN_CL_ROLE', ''), 'null'), '') AS IN_CL_ROLE,
    IFNULL(NULLIF(NULLIF(data->>'$.IN_CL_APPROVAL', ''), 'null'), '') AS IN_CL_APPROVAL,
    IFNULL(NULLIF(NULLIF(data->>'$.IN_CL_COMMENT_SAVED', ''), 'null'), '') AS IN_CL_COMMENT_SAVED,
    IFNULL(NULLIF(NULLIF(data->>'$.IN_CL_DATE', ''), 'null'), '') AS IN_CL_DATE
FROM collection_" . $collectionID . " 
WHERE data->>'$.IN_CL_CASE_NUMBER' = " . $caseNumber;

$commentsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getHistoryComments));

$dataToGeneratePDF["IN_COMMENTS_HISTORY"] = $commentsResponse;

//Get Documents Name from IN.01
$documentsToValidate = array();

$documentsToValidate[] = array(
    "VARIABLE" => "IN_UPLOAD_PDF",
    "CONDITION" => ""
);

$documentsToValidate[] = array(
    "VARIABLE" => "IN_UPLOAD_EXCEL",
    "CONDITION" => ""
);

$documentsToValidate[] = array(
    "VARIABLE" => "IN_FX_UPLOAD_FILE",
    "CONDITION" => ""
);

$documentsToValidate[] = array(
    "VARIABLE" => "IN_ADDITIONAL_FILES",
    "CONDITION" => ""
);

$documentsNames = getDocumentsNames($dataToGeneratePDF, $documentsToValidate, $apiUrl);

//Get all the rows from the Expense Table

$getTableExpenseQl = "select * from EXPENSE_TABLE where IN_EXPENSE_CASE_ID = " . $processRequestId . " order by IN_EXPENSE_TEAM_ROW_INDEX asc;";

$tableExpense = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getTableExpenseQl));

$dataToGeneratePDF["IN_EXPENSE_TABLE"] = $tableExpense;

$mergedData = array_merge($data, $dataReturn, $dataToGeneratePDF);

//Generate PDF
generatePDF($outputDocumentTempPath, $mergedData, $documentsNames, $serverUrl);

//Add pdf to request
$newFile = $apiInstance->createRequestFile($processRequestId, $pdfName, $outputDocumentTempPath);
$fileUID = $newFile->getFileUploadId();
$fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;


$dataReturn["IN_DHS01_PDF_URL"] = $fileGeneratedUrl;
$dataReturn["IN_DHS01_PDF_ID"] = $fileUID;

// -- Send Notification --

if ($data["IN_SAVE_SUBMIT"] != "SAVE_AND_CLOSE" && $data["IN_SAVE_SUBMIT"] != "SAVE" ) {
    $task = "DHS01";
    $emailType = "";
    $data = array_merge($data, $dataReturn);
    sendInvoiceNotification($data, $task, $emailType, $api);
}

return $dataReturn;

/**
 * Get the comment logs from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getCommentsLog($ID, $apiUrl, $requestId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT LOG.data->>'$.IN_CL_REQUEST_ID' AS REQUEST_ID,
                        LOG.data->>'$.IN_CL_USER' AS IN_COMMENT_USER,
                        LOG.data->>'$.IN_CL_USER_ID' AS IN_COMMENT_USER_ID,
                        LOG.data->>'$.IN_CL_ROLE' AS IN_COMMENT_ROLE,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_APPROVAL', 'null'), '') AS IN_COMMENT_APPROVAL,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_COMMENT_SAVED', 'null'), '') AS IN_COMMENT_SAVED,
                        LOG.data->>'$.IN_CL_DATE' AS IN_COMMENT_DATE
                        FROM collection_" . $ID . " AS LOG
                        WHERE LOG.data->>'$.IN_CL_REQUEST_ID' = " . $requestId . " 
                        ORDER BY id";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
// 2
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
 * @return none
 *
 * by Cinthia Romero
 * modified by Adriana Centellas
 */

function generatePDF($outputDocumentTempPath, $data, $documentsNames, $serverUrl)
{
    $processRequestId = $data['_request']['id'];
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'en', true, 'UTF-8', [0, 0, 0, 0]);
    // ← Permite que una celda se parta entre páginas
    $html2pdf->setTestTdInOnePage(false);
    $pdfHTML = "
            <style>
            table.invoiceTable {
                font-size: 7px;
                table-layout: auto;
                border-collapse: collapse;
                margin: 0 auto;
                width: auto;
            }
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
                    vertical-align: middle;
                    background-color: #711426;
                    color: white;
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
                .linkDownload {
                    color: #711426;
                    text-decoration: none;
                }
                .lineItems {
                  font-size: 7px;
                  text-align: center;
                  border: 1px solid #222;
                  vertical-align: middle;
                  white-space: normal !important;
                word-break: break-word !important;
                overflow-wrap: break-word !important;
                hyphens: auto;
                box-sizing: border-box;
                }
                .titleLineItems {
                    font-size: 7px;
                    text-align: center;
                    border: 1px solid #222;
                    font-weight: bold;
                    padding: 2px;
                    white-space: normal !important;
                    word-break: break-word !important;
                    overflow-wrap: break-word !important;
                    hyphens: auto;
                    box-sizing: border-box;
                    background-color: #711426;
                    color: white;
                    vertical-align: middle;
                    line-height: 1.2;
                    }
                    /* === Header (solo primera tabla) === */
table.headerTable{
  width:100%;
  border-collapse:collapse;
  margin:0 0 4px 0;          /* separa del contenido siguiente */
}
table.headerTable td{
  padding:0;                 /* evita altura extra inesperada */
  vertical-align:middle;
}
table.headerTable .logoCell{
  text-align:center;
}
table.headerTable .logoCell img{
  height:34px;               /* ajusta si necesitas más/menos espacio */
  display:block;
  margin:0 auto;
}

/* Barra vino del título */
table.headerTable .titleRow{
  display:block;
  width:100%;
  font-size:12px;            /* antes 14px: más compacto */
  background:#711426;
  color:#fff;
  font-weight:bold;
  padding:2px 4px;
  margin:2px 0 2px 0;
  line-height:1.0;
}

/* Subtítulo con línea inferior */
table.headerTable .subtitleRow{
  display:block;
  width:100%;
  font-size:11px;
  color:#711426;
  font-weight:bold;
  border-bottom:1px solid #711426;
  padding:1px 0;
  margin:0 0 2px 0;
  line-height:1.0;
}
            </style>";

    //1st page portrait
    $pdfHTML .= "<page orientation='L' backtop='5mm' backbottom='5mm' backleft='10mm' backright='10mm'>";

    $pdfHTML .= "<table class='headerTable'>";
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);

    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td style='text-align:center;'><img src='/tmp/logo.png' height='50'/></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td><br></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='titleRow'>Invoice Process</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML
        .= addSectionTitle("Invoice Line Items");
    $pdfHTML .= "</table><br>";


    $columns = [
        ['label' => '#', 'key' => 'IN_EXPENSE_TEAM_ROW_INDEX'],
        ['label' => 'Expense Description', 'key' => 'IN_EXPENSE_DESCRIPTION'],
        ['label' => 'Non-Rec / Rec', 'key' => 'IN_EXPENSE_NR_LABEL'],
        ['label' => 'Team Routing/Asset Class', 'key' => 'IN_EXPENSE_TEAM_ROUTING_LABEL'],
        ['label' => 'Account', 'key' => 'IN_EXPENSE_ACCOUNT_LABEL'],
        ['label' => 'Pre Tax Amount', 'key' => 'IN_EXPENSE_PRETAX_AMOUNT', 'format' => 'number'],
        ['label' => 'Tax Amount', 'key' => 'IN_EXPENSE_HST', 'format' => 'number'],
        ['label' => 'Total Amount', 'key' => 'IN_EXPENSE_TOTAL', 'format' => 'number'],
        ['label' => '% of Total Invoice Amount', 'key' => 'IN_EXPENSE_PERCENTAGE_TOTAL', 'suffix' => '%', 'format' => 'number'],
        ['label' => 'Corporate Project', 'key' => 'IN_EXPENSE_CORP_PROJ_LABEL'],
        ['label' => 'Deal', 'key' => 'IN_EXPENSE_PROJECT_DEAL_LABEL'],
        ['label' => 'Fund Manager', 'key' => 'IN_EXPENSE_FUND_MANAGER_LABEL'],
        ['label' => 'Mandate', 'key' => 'IN_EXPENSE_MANDATE_LABEL'],
        ['label' => 'Activity', 'key' => 'IN_EXPENSE_ACTIVITY_LABEL'],
        ['label' => 'Corporate Entity', 'key' => 'IN_EXPENSE_CORP_ENTITY_LABEL'],
        ['label' => 'Department', 'key' => 'IN_EXPENSE_DEPARTMENT_LABEL'],
        ['label' => 'Office', 'key' => 'IN_EXPENSE_OFFICE_LABEL'],
        ['label' => 'GL Code', 'key' => 'IN_EXPENSE_GL_CODE'],
    ];

    $pdfHTML .= "<table class='invoiceTable' style='border-collapse: collapse; table-layout: auto;'>";

    $pdfHTML .= "<colgroup>";
    foreach ($columns as $col) {
        $pdfHTML .= "<col style='width:auto'>";
    }
    $pdfHTML .= "</colgroup>";

    // TOTALS
    $pdfHTML .= "<tr class='summaryRow'>";
    foreach ($columns as $i => $col) {
        if ($col['label'] === 'Account') {
            $pdfHTML .= "<td class='titleLineItems'>" . htmlspecialchars($data["IN_INVOICE_CURRENCY"]) . "</td>";
        } elseif ($col['label'] === 'Pre Tax Amount') {
            $pdfHTML .= "<td class='titleLineItems'>" . $data["IN_TOTAL_PRE_TAX_AMOUNT_FORMATTED"] . "</td>";
        } elseif ($col['label'] === 'Tax Amount') {
            $pdfHTML .= "<td class='titleLineItems'>" . $data["IN_INVOICE_PRE_TAX_FORMATTED"] . "</td>";
        } elseif ($col['label'] === 'Total Amount') {
            $pdfHTML .= "<td class='titleLineItems'>" . $data["IN_INVOICE_TOTAL_FORMATTED"] . "</td>";
        } else {
            $pdfHTML .= "<td></td>";
        }
    }
    $pdfHTML .= "</tr>";

    $pdfHTML .= "<tr>";
    foreach ($columns as $col) {
        $label = wordwrap($col['label'], 15, "\n", true); 
        $label = nl2br(htmlspecialchars($label));
        $pdfHTML .= "<th class='titleLineItems'>{$label}</th>";
    }
    $pdfHTML .= "</tr>";

    foreach ($data['IN_EXPENSE_TABLE'] as $item) {
        $pdfHTML .= "<tr>";
        foreach ($columns as $col) {

            $value = $item[$col['key']] ?? '';

            //  Wordwrap
            if (
                in_array($col['label'], [
                    'Expense Description',
                    'Non-Rec / Rec',
                    'Team Routing/Asset Class',
                    'Account',
                    'Pre Tax Amount',
                    'Tax Amount',
                    'GL Code',
                    'Total Amount',
                    'Deal',
                    'Mandate',
                    'Corporate Project',
                    'Department',
                    'Fund Manager',
                    'Mandate',
                    'Activity',
                    'Corporate Entity',
                    'Office'
                ])
            ) {
                $value = wordwrap($value, 25, "\n", true);
            }
            if (
                in_array($col['label'], [
                    'Pre Tax Amount',
                    'Tax Amount',
                    'Total Amount'
                ])
            ) {
                $value = wordwrap($value, 40, "\n", true);
            }
            // Fromat numbers
            if (isset($col['format']) && $col['format'] === 'number') {
                if (!empty($value)) {
                    
                    if (is_string($value)) {
                        $value = str_replace(',', '', $value);
                    }
                    
                    
                    $numericValue = floatval($value);

                    
                    $value = number_format($numericValue, 2, '.', ',');
                } else {
                    $value = '0.00';
                }
            }
            
            if (isset($col['suffix'])) {
                $value .= $col['suffix'];
            }

            
            $value = nl2br(htmlspecialchars($value));

            $pdfHTML .= "<td class='lineItems'>{$value}</td>";
        }
        $pdfHTML .= "</tr>";
    }

    $pdfHTML .= "</table>";
    $pdfHTML .= "</page>";

    try {
        // Create the PDF
        $html2pdf->writeHTML($pdfHTML);

        // Save the PDF to the specified path
        $html2pdf->output($outputDocumentTempPath, 'F');
    } catch (Html2PdfException $e) {
        // Handle the exception
        $formatter = new ExceptionFormatter($e);
    }
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
/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by 
 */
function postRecordToCollection($record, $collectionID)
{
    try {
        $pmheaders = [
            'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
            'Accept' => 'application/json',
        ];
        $apiHost = getenv('API_HOST');
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request("POST", $apiHost . "/collections/$collectionID/records", [
            "headers" => $pmheaders,
            "http_errors" => false,
            "json" => [
                "data" => $record
            ]
        ]);
        if ($res->getStatusCode() == 201) {
            $response = json_decode($res->getBody(), true);
            return $response["id"];
        }
        return "Status Code " . $res->getStatusCode() . ".  Unable to Save";

    } catch (\Exception $e) {
        return $e->getMessage();
    }
}