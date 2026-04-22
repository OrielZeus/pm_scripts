<?php 
$time_start = microtime(true); //////////////////time execution
$initDate = date('Y-m-d h:i:s'); //////////////////time execution

/**********************************
 * IN - IS.01 post processing
 *
 * by Manuel Monroy
 * modified by Adriana Centellas
 * modified by Ana Castillo
 *********************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Define collection name
$collectionName = 'IN_VENDORS';
//Get collection ID
$collectionID = getCollectionId($collectionName, $apiUrl);

// NEW VENDOR ID
$newVendorID = getNewVendorID($collectionID, $apiUrl);

// Add preview link by jhon Chacolla
$requestId = $data["_request"]["id"];
$fileId = $data["IN_UPLOAD_PDF"];
if (isset($data["IN_UPLOAD_PDF"])) {
    //Convert PDF File on table HTML code
    $htmlPDFDownload = '<table width="100%" class="table">';
    $fileLinkPDF = !empty($fileId) ? $baseURL . getFileDataLink($apiHost, $fileId) : null;
    $fileLinkPDF = getFileDataLink($apiHost, $fileId);
    $fileInfoPDF = pathinfo($fileLinkPDF);
    $htmlPDFDownload .= '<tr> ';
    $htmlPDFDownload .= '<td class=""> ';
    $htmlPDFDownload .= '<a href="' . $fileLinkPDF . '" download="' . getLastSegment($fileLinkPDF) . '"><i class="fas fa-file-download"></i> <span style="text-decoration: underline;"> Download</span></a> ';
    $htmlPDFDownload .= '</td>';
    $htmlPDFDownload .= '<td>';
    $htmlPDFDownload .= getLastSegment($fileLinkPDF) ;
    $htmlPDFDownload .= '</td>';
    $htmlPDFDownload .= '<td>';
    $htmlPDFDownload .= '<p>';
    $htmlPDFDownload .= '<a class="btn btn-primary" draggable="false" href="' . $fileLinkPDF . '" target="_blank" rel="noopener">Preview file</a>';
    $htmlPDFDownload .= '</p>';
    $htmlPDFDownload .= '</td>';
    $htmlPDFDownload .= '</tr>';
    $htmlPDFDownload .= "</table>";
} else {
    $htmlPDFDownload = "";
}

//URL of PDF of Additional documents
$tableForAdditionalFiles = "";
if (isset($data["IN_ADDITIONAL_FILES"]) && is_array($data["IN_ADDITIONAL_FILES"])) {
    $tableForAdditionalFiles = '<table width="100%" class="table">';
    foreach ($data["IN_ADDITIONAL_FILES"] as &$item) {
        $item["url"] = !empty($item["file"]) ? getFileDataLink($apiHost, $item["file"]) : null;
        $fileLink = getFileDataLink($apiHost, $item["file"]);
        $fileInfo = pathinfo($fileLink);

        $tableForAdditionalFiles .= '<tr> ';
        $tableForAdditionalFiles .= '<td class=""> ';
        $tableForAdditionalFiles .= '<a href="' . $fileLink . '" download="' . getLastSegment($fileLink) . '"><i class="fas fa-file-download"></i> <span style="text-decoration: underline;"> Download</span></a> ';
        $tableForAdditionalFiles .= '</td>';
        $tableForAdditionalFiles .= '<td>';
        $tableForAdditionalFiles .= getLastSegment($fileLink) ;
        $tableForAdditionalFiles .= '</td>';
        $tableForAdditionalFiles .= '<td>';
        
        if(strtoupper($fileInfo['extension']) == 'PDF') {
            $tableForAdditionalFiles .= '<p>';
            $tableForAdditionalFiles .= '<a class="btn btn-primary" draggable="false" href="' . $fileLink . '" target="_blank" rel="noopener">Preview file</a>';
            $tableForAdditionalFiles .= '</p>';
        }
        $tableForAdditionalFiles .= '</td>';
        $tableForAdditionalFiles .= '</tr>';
    }
    unset($item);
    $tableForAdditionalFiles.= "</table>";
}



// Remove rows from the EXPENSE_TABLE
deleteRow($apiUrl, $requestId);

$time_end = microtime(true); //////////////////time execution
$execution_time = ($time_end - $time_start); //////////////////time execution
$dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];//////////////////time execution
$dataTimeExec['IN - IS.01 post processing'][] = $initDate;//////////////////time execution
$dataTimeExec['IN - IS.01 post processing'][] = $execution_time;//////////////////time execution
//$dataReturn['dataTimeExec'] = $dataTimeExec; //////////////////time execution

return [
    "dataTimeExec" => $dataTimeExec,
    "IN_DATA_INITIAL_USER_TEST" => $data['IN_DATA_INITIAL_USER'],
    "NEW_VENDOR_ID" => $newVendorID,
    "IN_PDF_URL" => $fileLinkPDF,
    "IN_PDF_HTML_DOWNLOAD" => $htmlPDFDownload,
    "IN_ADDITIONAL_FILES_TABLE" => $tableForAdditionalFiles,
    "IN_ADDITIONAL_FILES" => $data["IN_ADDITIONAL_FILES"]
];

/**
 * Fetch the ID of a vendor labeled as 'NEW VENDOR' from a specific collection.
 *
 * @param int $collectionId The ID of the collection to query.
 * @param string $apiUrl The URL of the API to fetch data.
 * @return array An array containing the IDs of vendors labeled as 'NEW VENDOR'.
 *
 * by Adriana Centellas
 */
function getNewVendorID($collectionId, $apiUrl)
{
    // SQL query to fetch the IDs of vendors labeled as 'NEW VENDOR'
    $sqlQuery = "
        SELECT id
        FROM collection_" . $collectionId . " 
        WHERE data->>'$.VENDOR_LABEL' = 'NEW VENDOR'";

    // Call the API to execute the query and fetch results
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlQuery)) ?? [];

    // Return the result from the query
    return $response[0]["id"];
}

/**
 * Get get File Link.
 *
 * @param string $apiHost - PM API string.
 * @param string $fileId - File ID.
 * @return string - File link.
 *
 * by Jhon Chacolla
 * ednit by Daniel Aguilar
 */
function getFileDataLink($apiHost, $fileId) {
    if(empty($fileId)){ 
        return '';
    }
    $fileData = callApiUrlGuzzle($apiHost .'/files/' . $fileId, 'GET');
    $url = $fileData['original_url'];
    
    // get last part of URL. By Daniel Aguilar
    $lastSlashPos = strrpos($url, '/');
    if ($lastSlashPos !== false) {
        $pathBeforeLastPart = substr($url, 0, $lastSlashPos + 1);
        $lastPart = substr($url, $lastSlashPos + 1);
    } else {
        // If no slash is found, the entire URL is considered the "last part"
        $pathBeforeLastPart = "";
        $lastPart = $url;
    }
    //encoding the name of file
    $encodedLastPart = rawurlencode($lastPart);
    return $pathBeforeLastPart.$encodedLastPart;
}

/**
 * Retrieves the last segment of a given URL.
 * 
 * @param string $url The URL from which to extract the last segment.
 * @return string The last segment of the URL, or an empty string if the URL is invalid.
 */
function getLastSegment($url) {
    // Ensure the URL is a valid string
    if (!is_string($url) || empty($url)) {
        return ''; // Return an empty string for invalid input
    }

    // Remove trailing slash, if any
    $url = rtrim($url, '/');

    // Get the last segment after the final "/"
    return basename($url);
}

/**
 * Generates a standart UUID with 32 characters
 * @return string - A UUID string
 * by Jhon Chacolla
 */
function deleteRow($apiUrl, $requestId){
    $query  = "";
    $query .= "DELETE FROM EXPENSE_TABLE ";
    $query .= "WHERE EXPENSE_TABLE.IN_EXPENSE_CASE_ID = '" . $requestId . "' ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}