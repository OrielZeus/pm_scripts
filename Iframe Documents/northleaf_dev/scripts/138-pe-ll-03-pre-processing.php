<?php
/**********************************
 * PE - LL.03 Pre-Processing
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$caseNumber = $data['_request']['case_number'] ?? 0;

//Get collections IDs
$collectionNames = array("PE_IC_APPROVER_RESPONSE");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionICApproverResponse = $collectionsInfo["PE_IC_APPROVER_RESPONSE"];
//Get All Info Approvers
$sqlSettings = "SELECT data->>'$.IAR_CASE_NUMBER' AS IAR_CASE_NUMBER,
                       data->>'$.IAR_APPROVER_EMAIL' AS IAR_APPROVER_EMAIL,
                       data->>'$.IAR_APPROVER_COMMENTS' AS IAR_APPROVER_COMMENTS
                FROM collection_" . $collectionICApproverResponse . "
                WHERE data->>'$.IAR_CASE_NUMBER' = " . $caseNumber;
$approversResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlSettings));

//Set Approvers Response
if (empty($approversResponse["error_message"])) {
    $icApprovers = $data['PE_IC_01_APPROVERS'] ?? [];
    $showComments = 'NO';
    $ic01Comments = [];
    foreach ($icApprovers as $approver) {
        foreach ($approversResponse as $approverCollection) {
            if ($approverCollection['IAR_APPROVER_EMAIL'] == $approver['PE_IC_APPROVER_EMAIL']) {
                $ic01Comments[] = [
                    "PE_IC01_USER_APPROVED" => $approver['PE_IC_APPROVER_NAME'],
                    "PE_IC01_USER_COMMENT" =>
                        isset($approverCollection['IAR_APPROVER_COMMENTS']) && $approverCollection['IAR_APPROVER_COMMENTS'] !== null && $approverCollection['IAR_APPROVER_COMMENTS'] !== 'null'
                        ? $approverCollection['IAR_APPROVER_COMMENTS']
                        : '',

                ];
                if ($approverCollection['IAR_APPROVER_COMMENTS'] != '' && $approverCollection['IAR_APPROVER_COMMENTS'] != 'null') {
                    $showComments = 'YES';
                }
            }
        }
    }
    $dataReturn['PE_IC01_COMMENTS'] = $ic01Comments;
    $dataReturn['PE_IC01_COMMENTS_SHOW'] = $showComments;
}
// Send Notifications
$task = 'node_LL08';
$emailType = 'TO_LL08';
sendNotification($data, $task, $emailType, $api);

$task = 'node_LL08';
$emailType = 'TO_GROUP_DEAL_TEAM';
sendNotification($data, $task, $emailType, $api);

return $dataReturn;