<?php 
/**********************************
 * Get File History
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

// get List of Uploaded Files
$historyFiles = getListUploadedFiles($data["case_number"]);
foreach($historyFiles as &$file) {
    //Set URL
    $file['URL'] = '/storage/' . $file['FILE_ID'] . '/'. $file['FILE_NAME'];
    // If user is Admin, change the name
    if ($file['USER_ID'] == '1') { $file['USER_NAME'] = '-'; }
}
$dataReturn["PE_HISTORY_FILE"] = $historyFiles;

return $dataReturn;