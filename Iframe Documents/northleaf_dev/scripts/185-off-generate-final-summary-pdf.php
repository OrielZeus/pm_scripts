<?php
/**********************************************
 * OFF - Offboarding Process PDF
 *
 * by Adriana Centellas
 * modified by Luz Nina
 * modified by Favio Mollinedo
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
$caseNumber = $data['_request']['case_number'];
$pdfName = "HR Offboarding-". $data["OFF_EMPLOYEE_NAME"] .".pdf";
$outputDocumentTempPath = '/tmp/' . $pdfName;
$dataToGeneratePDF = $data;

//Get collections IDs
$collectionTasksName ="OFF_NOTIFICATION_LOG";
$collectionID = getCollectionId($collectionTasksName, $apiUrl);

//Get collection records
$collectionRecords = getCollectionRecordsByCase($collectionID, $apiUrl, $caseNumber);

//Generate the PDF
$dataToGeneratePDF["NORTHLEAF_LOGO_URL"] = $northleafLogoUrl;
$dataToGeneratePDF["CHERRY_CHECK_URL"] = $serverUrl . $cherryCheckUrl;
$dataToGeneratePDF["TASK_INFORMATION"] = getTasksInformation($processRequestId, $apiUrl);
$dataToGeneratePDF["OFF_MANAGER_NAME"] = $data["OFF_MANAGER"];
$dataToGeneratePDF["OFF_EXECUTIVE_ASSISTANT_NAME"] = getUserFullName($data["OFF_EXECUTIVE_ASSISTANT"], $apiUrl);
$dataToGeneratePDF["APPLICATIONS"] = compareEmailsLogTasksArrays($collectionRecords, $apiUrl);

//Stard - Array with data of Upload Documents of the task IT.02 and the task OS.02
$uploadDocumentTask = [];
if (isset($data["OFF_UPLOAD_IT2"]) && !empty($data["OFF_UPLOAD_IT2"])) {
    foreach ($dataToGeneratePDF["TASK_INFORMATION"] as $row) {
        if ($row["TASK_NODE"] == "IT.02") {
            $apiInstanceFile = $api->files();
            $fileId = $data["OFF_UPLOAD_IT2"];
            $file = $apiInstanceFile->getFileById($fileId);
            $documentName = $file->getFileName();
            $uploadDocumentTask[] = [
                "TASK_NAME" => $row['TASK_NAME'],
                "USER" => $row['USER_FIRSTNAME'].' '. $row['USER_LASTNAME'],
                "URL_DOCUMENT" => $serverUrl . 'request/' . $processRequestId . '/files/' . $data["OFF_UPLOAD_IT2"],
                "FILE_NAME" => $documentName
            ];
        }
    } 
}

if ((isset($data["OFF_UPLOAD_OS2"]) && !empty($data["OFF_UPLOAD_OS2"])) || (isset($data["OFF_COMMENTS_OS2"]) && !empty($data["OFF_COMMENTS_OS2"]))) {
    foreach ($dataToGeneratePDF["TASK_INFORMATION"] as $row) {
        if ($row["TASK_NODE"] == "OS.02" && !empty($data["OFF_UPLOAD_OS2"])) {
            $apiInstanceFile = $api->files();
            $fileId = $data["OFF_UPLOAD_OS2"];
            $file = $apiInstanceFile->getFileById($fileId);
            $documentName = $file->getFileName();
            $uploadDocumentTask[] = [
                "TASK_NAME" => $row['TASK_NAME'],
                "USER" => $row['USER_FIRSTNAME'].' '. $row['USER_LASTNAME'],
                "URL_DOCUMENT" => $serverUrl . 'request/' . $processRequestId . '/files/' . $data["OFF_UPLOAD_OS2"],
                "OFF_COMMENTS_OS2" => $data['OFF_COMMENTS_OS2'],
                "FILE_NAME" => $documentName
            ];
        }
        if ($row["TASK_NODE"] == "OS.02" && empty($data["OFF_UPLOAD_OS2"])) {
            $uploadDocumentTask[] = [
                "TASK_NAME" => $row['TASK_NAME'],
                "USER" => $row['USER_FIRSTNAME'].' '. $row['USER_LASTNAME'],
                "URL_DOCUMENT" => null,
                "OFF_COMMENTS_OS2" => $data['OFF_COMMENTS_OS2'],
                "FILE_NAME" => null
            ];
        }
    } 
}
//End - Array with data of Upload

//Add element 'UPLOAD_DOCUMENTS' with $uploadDocuemtTask array
$dataToGeneratePDF['UPLOAD_DOCUMENTS'] = $uploadDocumentTask; 

generatePDF($outputDocumentTempPath, $dataToGeneratePDF, $apiUrl);

//Add pdf to request
$newFile = $apiInstance->createRequestFile($processRequestId, $pdfName, $outputDocumentTempPath);
$fileUID = $newFile->getFileUploadId();
$fileGeneratedUrl = $serverUrl . 'request/' . $processRequestId . '/files/' . $fileUID;

return array(
    "PE_ALL_FORMS_PDF" => $fileGeneratedUrl,
    "PE_ALL_FORMS_PDF_ID" => $fileUID
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
 * Get the fullname of a User.
 *
 * @param (String) $userId - The name of the collection to search for.
 * @param (String) $apiUrl - The URL of the API to call.
 * @return (Array|int) $result - The collection information, or 0 if not found.
 *
 * by Adriana Centellas
 */
function getUserFullName($userId, $apiUrl)
{
    // Prepare SQL query to fetch the collection ID by its name
    $sQUserName = "SELECT id AS USER_ID,
                          CONCAT(firstname, ' ', lastname) AS USER_FULL_NAME
                   FROM users
                   WHERE id = '" . $userId . "'";

    // Send API request to fetch collection information, return 0 if not found
    $result = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQUserName)) ?? 0;
    
    return $result[0]["USER_FULL_NAME"];
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
 * modified by Favio Mollinedo
 */
function generatePDF($outputDocumentTempPath, $data)
{
    $processRequestId = $data['_request']['id'];
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
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
                .linkDownload {
                    color: #711426;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>";
    // Download Image
    $image = file_get_contents(getenv('ORIGINAL_URL_PDF_LOGO'));
    file_put_contents('/tmp/logo.png', $image);
    //Build PDF Body
    $pdfHTML .= "<table style='width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2' style='text-align:center;'><img src='/tmp/logo.png' height='50'/></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'><br></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2' class='titleRow'>Offboarding Process</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Employee Name:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_EMPLOYEE_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Last Day of Employment:</td>";
    $pdfHTML .= "<td class='text'>" . formatDate($data["OFF_LAST_DAY_EMPLOYMENT"], "m/d/Y") . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Job Title:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_JOB_TITLE"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Manager:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_MANAGER_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Executive Assistant:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_EXECUTIVE_ASSISTANT_NAME"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Office Location:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_OFFICE_LOCATION"]["OFFICE_DESCRIPTION"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Department:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_DEPARTMENT"]["DEPARTMENT_DESCRIPTION"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Department Code: </td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_DEPARTMENT_CODE"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>OOO Notifications:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_OOO_NOTIFICATIONS"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td class='label'>Mailbox Access Required:</td>";
    $pdfHTML .= "<td class='text'>" . $data["OFF_MAILBOX_ACCESS_REQUIRED"] . "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle("Users Approval");
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:45%'>Task</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:25%'>User</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:10%'>Approve</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%;'><br>Task Completion Date</th>";
    $pdfHTML .= "</tr>";
    // Download Image
    $image = file_get_contents($data["CHERRY_CHECK_URL"]);
    file_put_contents('/tmp/cherryCheck.png', $image);
    foreach ($data["TASK_INFORMATION"] as $item) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:45%'>" . $item["TASK_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:25%'>" . $item["USER_FIRSTNAME"] ." ". $item["USER_LASTNAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:10%'>" . "<img src='/tmp/cherryCheck.png' height='10'/>" . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . formatDate($item["COMPLETED_AT"], "m/d/Y") . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";

    //Add section 'Applications'
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td>";
    $pdfHTML .= "<br>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle('Applications');
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:40%'>Application</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>User</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Date Email Sent</th>";
    $pdfHTML .= "</tr>";
    foreach ($data['APPLICATIONS'] as $item) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:40%'>" . $item["APP_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:30%'>" . $item["USER_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $item["DATE"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .="</tr>";
    //End of section'Applications'

    //Add section 'Documents'
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td>";
    $pdfHTML .= "<br>";
    $pdfHTML .= "</td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= addSectionTitle('Documents');
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'></td>";
    $pdfHTML .= "</tr>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<td colspan='2'>";
    $pdfHTML .= "<table style='border-collapse:collapse; width:100%'>";
    $pdfHTML .= "<tr>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Task</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>User</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:30%'>Document</th>";
    $pdfHTML .= "<th class='titleGrid' style='width:20%'>Comments</th>";
    $pdfHTML .= "</tr>";
    foreach ($data['UPLOAD_DOCUMENTS'] as $item) {
        $pdfHTML .= "<tr>";
        $pdfHTML .= "<td class='textGrid' style='width:30%'>" . $item["TASK_NAME"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $item["USER"] . "</td>";
        $pdfHTML .= "<td class='textGrid' style='width:30%'><a class='linkDownload' href='" . $item["URL_DOCUMENT"] . "'> " . $item["FILE_NAME"] . "</a></td>";
        $pdfHTML .= "<td class='textGrid' style='width:20%'>" . $item["OFF_COMMENTS_OS2"] . "</td>";
        $pdfHTML .= "</tr>";
    }
    $pdfHTML .= "</table>";
    $pdfHTML .= "</td>";
    $pdfHTML .="</tr>";
    //End of section'Documents'

    $pdfHTML .= "</table>";
    $pdfHTML .= "</body>";
    $pdfHTML .= "</html>";

    // Create the PDF
    $html2pdf->writeHTML($pdfHTML);

    // Save the PDF to the specified path
    $html2pdf->output($outputDocumentTempPath, 'F');
}

/**
 * Get the records of a collection based on its ID, Case number and task node.
 *
 * @param (String) $ID - The ID of the collection to fetch records from.
 * @param (String) $apiUrl - The URL of the API to call.
 * @param (int) $caseNumber - request case number.
 * @return (Array) $collectionRecords - The collection records, or an empty array if none are found.
 *
 * by Favio Mollinedo
 * Adapted copy from getCollectionRecords
 */
function getCollectionRecordsByCase($ID, $apiUrl, $caseNumber)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data
                        FROM collection_" . $ID. "
                        WHERE data->>'$.CASE_NUMBER' = " . $caseNumber .
                        " AND data->>'$.NODE_TASK_ID' = 'OS.03-09'";
    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    return $collectionRecords;
}

/**
 * Get matched records based on notification logs array and task information array.
 *
 * @param (array) $logsArray - array from the logs collection.
 * @param (string) $apiUrl - The URL of the API to call.
 * @return (array) $newArray - All matched records.
 *
 * by Favio Mollinedo
 */
function compareEmailsLogTasksArrays($logsArray, $apiUrl){
    $newArray = [];
    $appsByUser = getOffApplicationUsers($apiUrl);

    foreach ($logsArray as $item1) {
        $logData = json_decode($item1["data"], true);
        $nodeTaskId = $logData["NODE_TASK_ID"];
        $userLog = getuserData($logData["TO_SEND"], $apiUrl);
        $userApps = searchByUserId($appsByUser, $userLog[0]["USER_ID"]);
 
        $newArray[] = [
            "DATE" => formatDate($logData["DATE"], "m/d/Y"),
            "USER_NAME" => $userLog[0]['USER_FULLNAME'],
            "APP_NAME" => $userApps["OFF_APPLICATION_TASK_NAMES"]
        ];
    }
    return $newArray;
}
/**
 * Get User Information
 *
 * @param string $userEmail
 * @return array $userData
 * @param (String) $apiUrl - The URL of the API to call.
 *
 * by Favio Mollinedo
 */
function getuserData($userEmail, $apiUrl)
{
    $userData = array();
    // Get User Information
    $queryUser = "SELECT U.id AS USER_ID, 
                        CONCAT(U.firstname, ' ', U.lastname) AS USER_FULLNAME, 
                        U.email AS USER_EMAIL, 
                        U.status AS USER_STATUS 
                  FROM users AS U 
                  WHERE U.email = '" . $userEmail ."'";
    $queryRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUser)) ?? [];

    return $queryRecords;
}
/**
 * Get list of active application tasks collection
 *
 * @param (String) $apiUrl - The URL of the API to call.
 * @return array $collectionInfo
 *
 * by Favio Mollinedo
 */
function getOffApplicationUsers($apiUrl)
{
    $offApplicationTaskID = getenv('OFF_APPLICATIONS_TASK_COLLECTION_ID');
    // Get OFF Application Tasks collection ID
    $queryCollectionID = "SELECT MIN(id) AS ID, 
                                GROUP_CONCAT(data->>'$.OFF_APPLICATION_TASK_NAME' SEPARATOR ', ') AS OFF_APPLICATION_TASK_NAMES,
                                data->>'$.OFF_APPLICATION_TASK_USER' AS OFF_APPLICATION_TASK_USER_ID,
                                data->>'$.OFF_APPLICATION_TASK_STATUS' AS OFF_APPLICATION_TASK_STATUS
                        FROM collection_" . $offApplicationTaskID . "
                        WHERE data->>'$.OFF_APPLICATION_TASK_STATUS' = 'Active' 
                        GROUP BY OFF_APPLICATION_TASK_USER_ID, OFF_APPLICATION_TASK_STATUS";
    $collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));

    return $collectionInfo;
}
/**
 * Get list of user application by ID
 *
 * @param (array) $array - array of applications.
 * @param (int) $userId - user ID.
 * @return $item - user application data or null if it's not found.
 *
 * by Favio Mollinedo
 */
function searchByUserId($array, $userId) {
    foreach ($array as $item) {
        if ($item['OFF_APPLICATION_TASK_USER_ID'] == $userId) {
            return $item;
        }
    }
    return null;
}