<?php
/*  
 *  IN - DHS Table PDF
 *  By Adriana Centellas
 */

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
$caseNumber = $data['_request']['case_number'];
$pdfName = "DHS.01-Invoice Process-" . $data["IN_CASE_NUMBER"] . ".pdf";
$outputDocumentTempPath = '/tmp/' . $pdfName;


$role     = "";
$comments = "";
if($data['newStatus'] == "corporateH"){
    $role     = "Corporate Finance Approver";
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

//Generate PDF
generatePDF($outputDocumentTempPath, $dataToGeneratePDF, $documentsNames, $serverUrl);

//Add pdf to request
$newFile = $apiInstance->createRequestFile($processRequestId, $pdfName, $outputDocumentTempPath);
$fileUID = $newFile->getFileUploadId();
$fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;

return array(
  "IN_DHS01_PDF_URL" => $fileGeneratedUrl,
  "IN_DHS01_PDF_ID" => $fileUID
);

/**
 * Get tasks information
 * @param (int) $requestId - The request id of the current case.
 * @param (String) $apiUrl - The URL of the API to call.
 * @return (array) $formattedData - The collection records, or an empty array if none are found.
 *
 * by Adriana Centellas
 * modified by Favio Mollinedo
 */
function getTasksInformation($requestId, $apiUrl)
{
  // Prepare SQL query to fetch records for the collection using its ID
  $sQCollectionsId = "select 
                        process_request_tokens.id as TASK_ID,
                        process_request_tokens.process_request_id as REQUEST_ID,
                        process_request_tokens.element_id as TASK_NODE,
                        process_request_tokens.element_name as TASK_NAME, 
                        process_request_tokens.completed_at as COMPLETED_AT,
                        users.id as USER_ID,
                        users.firstname as USER_FIRSTNAME,
                        users.lastname as USER_LASTNAME
                        from process_request_tokens
                        inner join users on users.id = process_request_tokens.user_id
                        where process_request_tokens.process_request_id = " . $requestId . "
                        and process_request_tokens.element_type = 'task'";

  // Send API request to fetch collection records, return an empty array if none are found
  $queryRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

  return $queryRecords;
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
 * @return none
 *
 * by Cinthia Romero
 * modified by Adriana Centellas
 */
         
function generatePDF($outputDocumentTempPath, $data, $documentsNames, $serverUrl)
{
  $processRequestId = $data['_request']['id'];
  // Create a new instance of HTML2PDF
  $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('L', 'A4', 'en', true, 'UTF-8', [0, 0, 0, 0]);
  $pdfHTML = "
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
                  font-size: 3px;
                  text-align: center;
                  border: 1px solid #222;
                  white-space: normal;
                  word-break: break-word;
                  overflow-wrap: break-word;
                  vertical-align: middle;
                }
                .titleLineItems {
                  font-size: 5px;
                  text-align: center;
                  border: 1px solid #222;
                  font-weight: bold;
                  padding: 2px;
                  white-space: normal !important;
                  word-break: break-word;
                  overflow-wrap: break-word;
                  background-color: #711426;
                  color: white;               
                  vertical-align: middle; 
                }

            </style>";
  //1st page portrait
  $pdfHTML .= "<page orientation='L' backtop='5mm' backbottom='5mm' backleft='10mm' backright='10mm'>";

        $pdfHTML .= "<table style='width:100%'>";
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
          $pdfHTML .= addSectionTitle("Invoice Line Items");
          $pdfHTML .= "<tr>";
            $pdfHTML .= "<td colspan='2'></td>";
          $pdfHTML .= "</tr>";
          $pdfHTML .= "<tr>";
            $pdfHTML .= "<td colspan='2'>";
              $pdfHTML .= "<table style='border-collapse:collapse; width:100%; page-break-inside:auto;' split='true' >";
              $pdfHTML .= "<tr split='true' style='page-break-inside: avoid; page-break-after: auto;'>";
                  $pdfHTML .= "<th split='true'style='width:2%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:20%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:6%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:6%;'></th>";
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:4%;'>USD</th>";
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:3%;'>".$data["IN_INVOICE_PRE_TAX"]."</th>";
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:3%;'>".number_format($data["IN_TOTAL_HST"], 2, '.', ',')."</th>";
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:3%;'>".number_format($data["IN_TOTAL_TOTAL"], 2, '.', ',')."</th>";
                  $pdfHTML .= "<th split='true'style='width:4%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:5%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:4%;'></th>";
                  $pdfHTML .= "<th split='true'style='width:10%;'></th>";
                $pdfHTML .= "</tr>";
                $pdfHTML .= "<tr split='true' style='page-break-inside: avoid; page-break-after: auto;'>";
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:2%;'>#</th>";                          
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:20%;'>Expense Description</th>";       
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:6%;'>Non-Rec / Rec</th>";              
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:6%;'>Team Routing/Asset Class</th>";   
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:4%;'>Account</th>";                     
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:3%;'>Pre Tax Amount</th>";             
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:3%;'>Tax Amount</th>";                 
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:3%;'>Total Amount</th>";               
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:4%;'>% of Total Invoice Amount</th>";   
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Corporate Project</th>";          
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Deal</th>";                        
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Fund Manager</th>";              
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Mandate</th>";               
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Activity</th>";                 
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Corporate Entity</th>";            
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:5%;'>Department</th>";                  
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:4%;'>Office</th>";
                  $pdfHTML .= "<th split='true'class='titleLineItems' style='width:10%;'>GL Code</th>";
                $pdfHTML .= "</tr>";

                foreach ($data['IN_EXPENSE_TABLE'] as $item) {
                  $pdfHTML .= "<tr split='true' style='page-break-inside: avoid; page-break-after: auto;'>";
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:2%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_TEAM_ROW_INDEX"] . "</td>";    
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:20%;  white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . nl2br(htmlspecialchars(wordwrap($item["IN_EXPENSE_DESCRIPTION"], 28, ' ', true))) . "</td>";
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:6%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . nl2br(htmlspecialchars(preg_replace('/([;_-])/', ' ', $item["IN_EXPENSE_NR_LABEL"]))) . "</td>";      
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:6%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_TEAM_ROUTING_LABEL"] . "</td>";    
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:4%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_ACCOUNT_LABEL"] . "</td>";        
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:3%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . number_format($item["IN_EXPENSE_PRETAX_AMOUNT"], 2, '.', ',') . "</td>";   
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:3%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . number_format($item["IN_EXPENSE_HST"], 2, '.', ',') . "</td>";         
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:3%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . number_format($item["IN_EXPENSE_TOTAL"], 2, '.', ',') . "</td>";      
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:4%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_PERCENTAGE_TOTAL"] . "%</td>"; 
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_CORP_PROJ_LABEL"] . "</td>"; 
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_PROJECT_DEAL_LABEL"] . "</td>";   
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_FUND_MANAGER_LABEL"] . "</td>"; 
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_MANDATE_LABEL"] . "</td>"; 
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_ACTIVITY_LABEL"] . "</td>";
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_CORP_ENTITY_LABEL"] . "</td>";
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:5%; white-space: normal; word-wrap: break-word; overflow-wrap: break-word; word-break: break-all;'>" . nl2br(htmlspecialchars(wordwrap(preg_replace('/([;_-])/', ' ', $item["IN_EXPENSE_DEPARTMENT_LABEL"]), 20, "\n", true))) ."</td>";
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:4%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . $item["IN_EXPENSE_OFFICE_LABEL"] . "</td>";         
                    $pdfHTML .= "<td split='true' class='lineItems' style='width:10%; white-space: normal; word-break: break-word; overflow-wrap: break-word;'>" . nl2br(htmlspecialchars(wordwrap($item["IN_EXPENSE_GL_CODE"], 28, ' ', true))) . "</td>";  
                  $pdfHTML .= "</tr>";
                }
              $pdfHTML .= "</table>";
            $pdfHTML .= "</td>";
          $pdfHTML .= "</tr>";
       $pdfHTML .= "</table>";
  $pdfHTML .= "</page>";


    try {
      $html2pdf->pdf->SetDisplayMode('fullpage');
      $html2pdf->pdf->SetAutoPageBreak(true, 10);

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

require_once("/Northleaf_PHP_Library.php");
/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by 
*/
function postRecordToCollection($record, $collectionID){
    try{
        $pmheaders = [
            'Authorization' => 'Bearer ' . getenv('API_TOKEN'),        
            'Accept'        => 'application/json',
        ];
        $apiHost = getenv('API_HOST');
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request("POST", $apiHost ."/collections/$collectionID/records", [
                    "headers" => $pmheaders,
                    "http_errors" => false,
                    "json" => [
                        "data" => $record
                        ]
                    ]);
        if ($res->getStatusCode() == 201){
            $response = json_decode($res->getBody(), true);
            return $response["id"];
        }
        return "Status Code " . $res->getStatusCode() . ".  Unable to Save";
        
    }
    catch(\Exception $e){
        return $e->getMessage();
    }
}