<?php
/**********************************
 * PE - Send email to IC 02 Approvers
 *
 * by Telmo Chiri
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Initialize Variables
$aApprovers = array();
$responseData = array();
$currentProcess = $data["_request"]["process_id"];
//Check if current case belongs to PM Block
if (!empty($data["_parent"])) {
    $currentProcess = $data["_parent"]["process_id"];
}

//Get Information Approvers IC 02  
$getApproversInformation = "SELECT  ";
$getApproversInformation .= "CONCAT(U.firstname, ' ', U.lastname) AS APPROVER_FULL_NAME, ";
$getApproversInformation .= "U.email AS APPROVER_EMAIL, ";
$getApproversInformation .= "U.id AS APPROVER_ID ";
$getApproversInformation .= "FROM users AS U ";
$getApproversInformation .= "WHERE U.id IN ('" . $data['PE_PRIMARY_AUTHORIZATION']['USER_ID'] . "', '" . $data['PE_SECONDARY_AUTHORIZATION']['USER_ID'] . "') ";

$approversInfoResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getApproversInformation));

// get List of Uploaded Files
$historyFiles = getListUploadedFiles($data["_request"]["case_number"]);
foreach($historyFiles as &$file) {
    // If user is Admin, change the name
    if ($file['USER_ID'] == '1') { $file['USER_NAME'] = '-'; }
}
if (empty($approversInfoResponse["error_message"])) {
    foreach ($approversInfoResponse as $key => $approver) {
        $aApprovers[$key] = [
            "PE_PARENT_PROCESS_ID" => $currentProcess,
            "PE_PARENT_CASE_NUMBER" => $data["_request"]["case_number"],
            "PE_PARENT_REQUEST_ID" => $data["_request"]["id"],
            "PE_IC_APPROVER_NAME" => $approver["APPROVER_FULL_NAME"],
            "PE_IC_APPROVER_EMAIL" => $approver["APPROVER_EMAIL"],
            "PE_IC_APPROVER_ID" => $approver["APPROVER_ID"],
            "PE_IC_APPROVER_TYPE" => ($approver["APPROVER_ID"] == $data['PE_PRIMARY_AUTHORIZATION']['USER_ID'] ? 'PRIMARY' : 'SECONDARY' ),
            "PE_HISTORY_FILE" => $historyFiles
        ];
    }
}

// Custom comparison function
usort($aApprovers, function($a, $b) {
    // Define the desired order
    $order = ['PRIMARY' => 0, 'SECONDARY' => 1];
    // Compare the PE_IC_APPROVER_TYPE values ​​according to the defined order
    return $order[$a['PE_IC_APPROVER_TYPE']] <=> $order[$b['PE_IC_APPROVER_TYPE']];
});

$responseData["PE_IC_02_APPROVERS"] = $aApprovers;
$responseData["PE_ALL_APPROVERS_IC02_ANSWERED"] = 'NO';
return $responseData;