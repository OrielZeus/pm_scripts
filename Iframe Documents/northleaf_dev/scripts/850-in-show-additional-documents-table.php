<?php 
/**********************************
 * IN - Show Additional Documents Table
 *
 * by Ronald Nina
 *
 *********************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$data['IN_ADDITIONAL_FILES'] = json_decode(base64_decode($data['IN_ADDITIONAL_FILES']), true);
// return $data;
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
return $tableForAdditionalFiles;

/**
 * Get get File Link.
 *
 * @param string $apiHost - PM API string.
 * @param string $fileId - File ID.
 * @return string - File link.
 *
 * by Jhon Chacolla
 */
function getFileDataLink($apiHost, $fileId) {
    if(empty($fileId)){ 
        return '';
    }
    $fileData = callApiUrlGuzzle($apiHost .'/files/' . $fileId, 'GET');
    return $fileData['original_url'];
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