<?php 
/*************************************
 * Bulk Reassignment - Reassign Tasks
 *
 * by Cinthia Romero
 * Modified by Telmo Chiri
 ************************************/

// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get Global Variables
$client = new GuzzleHttp\Client(['verify' => false]);
$headers = [
    'Authorization' => 'Bearer ' .   getenv('API_TOKEN'),        
    'Accept'        => 'application/json',
];

$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$bulkReassignmentHistoryCollection = getenv('BULK_REASSIGNMENT_HISTORY_ID');
$emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$smtpServerUrl = getenv('NORTHLEAF_SMTP_ADDRESS');
$smtpUser = getenv('NORTHLEAF_SMTP_USER');
$smtpPassword = getenv('NORTHLEAF_SMTP_PASSWORD');
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
$serverEnvironment = getenv('SERVER_ENVIRONMENT');

//Initialize variables
$casesToReassign = empty($data["casesToReassign"]) ? array() : $data["casesToReassign"];
$reassignmentReason = empty($data["reassignmentReason"]) ? array() : $data["reassignmentReason"];
$insertHistoryUrl = $apiHost . '/collections/' . $bulkReassignmentHistoryCollection . '/records';

function getRequestIdByTaskId($taskId) {
    global $client, $headers, $apiHost;
    $endpoint = '/tasks/'.$taskId;
    $url = $apiHost.$endpoint;
    $res = $client->request("GET", $url, [
        'headers' => $headers,
    ]);
    $request = json_decode($res->getBody(), true);    
    return [
        'requestId' => $request['process_request_id'],
        'taskName' => $request['element_name'],
        'taskNodeId' => $request['element_id']
    ];
}

function getTaskAssignmentVaraibleName($taskName) {
    $taskNameCoded = substr($taskName, 0, 5);
    $taskAssignments = [
        //PM BLOCK 2nd part
        "DT.02" => "PE_DEAL_TEAM_JUNIOR",
        "DT.03" => "PE_DEAL_TEAM_JUNIOR",
        "DT.04" => "PE_DEAL_TEAM_JUNIOR",
        "DT.05" => "PE_DEAL_TEAM_JUNIOR",
        "DT.06" => "PE_DEAL_TEAM_JUNIOR",
        "DT.07" => "PE_DEAL_TEAM_JUNIOR",
        "LC.01" => "PE_LEAD_LAW_CLERK",
        "LC.02" => "PE_LEAD_LAW_CLERK",
        "LC.03" => "PE_LEAD_LAW_CLERK",
        "LL.01" => "PE_RED_FLAG_LEGAL_REVIEW",
        "LL.02" => "PE_RED_FLAG_LEGAL_REVIEW",
        "OR.01" => "Self Service",
        "OR.02" => "Self Service",
        "TX.01" => "PE_RED_FLAG_TAX_REVIEW",
        "TX.02" => "PE_RED_FLAG_TAX_REVIEW",
        "IR.01" => "PE_LEAD_IR",
        //PM BLOCK IC01
        "IC.01" => "PE_IC_APPROVER_ID",
        //PM BLOCK 3rd part
        "IA.01" => "PE_INVESTMENT_ADVISOR_APPROVER",
        "PA.01" => "PE_PORTFOLIO_MANAGER_APPROVER",
        "LC.04" => "PE_LEAD_LAW_CLERK",
        "LL.03" => "PE_RED_FLAG_LEGAL_REVIEW",
        "TX.03" => "PE_RED_FLAG_TAX_REVIEW",
        "TX.04" => "PE_RED_FLAG_TAX_REVIEW",
        "OR.03" => "Self Service",
        "OR.04" => "Self Service",
        "DT.09" => "PE_DEAL_TEAM_SENIOR",
        "DT.10" => "PE_DEAL_TEAM_JUNIOR",
        "LC.05" => "PE_LEAD_LAW_CLERK",
        "LC.06" => "PE_LEAD_LAW_CLERK",
        //PM BLOCK IC02
        "SS.01" => "PE_IC_APPROVER_ID"
    ];
    return (isset($taskAssignments[$taskNameCoded]) && $taskAssignments[$taskNameCoded] != "Self Service") ? $taskAssignments[$taskNameCoded] : false;
}

function updateRequestData($process_request_id, $userId, $taskName) {
    global $client, $headers, $apiHost;
    $endpoint = '/requests/'.$process_request_id;
    $url = $apiHost.$endpoint;
    $variableName = getTaskAssignmentVaraibleName($taskName);
    if($variableName) {
        $dataToUpdate = [
            'data' => [$variableName => $userId]
        ];
        $response = $client->request("PUT", $url, [
            'headers' => $headers,
            'body' => json_encode($dataToUpdate)
        ]);
        if($response->getStatusCode() == "200" || $response->getStatusCode() == "204") return true;
    }
    return false;
}

//Check if there is at least one case to reassign
if (count($casesToReassign) > 0) {
    foreach ($casesToReassign as $case) {
        //Check if old and new users are different
        if ($case["OLD_USER"] != $case["NEW_USER"]) {
            //Assign new user
            $updateTask = "UPDATE process_request_tokens
                                SET user_id=" . $case["NEW_USER"] . "
                            WHERE id=" . $case["DELEGATION_ID"] . "
                                AND `status`='ACTIVE'";
            $responseUpdateTask = callApiUrlGuzzle($apiUrl, "POST", encodeSql($updateTask));
            
            if (empty($responseUpdateTask["error_message"])) {
                //Insert history
                $historyData = array(
                    "BRH_CASE_NUMBER" => $case["CASE_NUMBER"],
                    "BRH_DELEGATION_ID" => $case["DELEGATION_ID"],
                    "BRH_OLD_USER" => $case["OLD_USER"],
                    "BRH_NEW_USER" => $case["NEW_USER"],
                    "BRH_REASSIGNMENT_DATE" => date("Y-m-d"),
                    "BRH_USER_PERFOM_ACTION" => $case["USER_LOGGED"]
                );
                callApiUrlGuzzle($insertHistoryUrl, "POST", $historyData);
                
                //Get New Task Info
                $sqlTask = "SELECT PRT.id AS TASK_ID,
                                PRT.element_id AS TASK_NODE,
                                PRT.element_name AS TASK_NAME,
                                PR.case_title AS CASE_TITLE,
                                IF (PR.data->>'$._parent.process_id' != '', 
                                        PR.data->>'$._parent.process_id', 
                                        P.id
                                    ) AS PROCESS_ID,
                                IF (PR.data->>'$._parent.process_id' != '',
                                        (SELECT P.name as name FROM processes AS P WHERE P.id = PR.data->>'$._parent.process_id') ,
                                        P.name
                                    ) AS PROCESS_NAME,
                                CONCAT(U.firstname,' ',U.lastname) AS USER_NAME,
                                U.email AS USER_EMAIL,
                                PRT.process_request_id
                            FROM process_request_tokens AS PRT 
                                INNER JOIN process_requests AS PR ON PR.id = PRT.process_request_id
                                INNER JOIN processes AS P ON P.id = PRT.process_id
                                INNER JOIN users AS U ON U.id = PRT.user_id
                            WHERE PRT.id = " . $case["DELEGATION_ID"];
                $responseDataTask = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlTask));
                
                if (empty($responseDataTask["error_message"])) {
                    //Data of Task
                    $taskId = $responseDataTask[0]["TASK_ID"];
                    $taskName = $responseDataTask[0]["TASK_NAME"];
                    $processName = $responseDataTask[0]["PROCESS_NAME"];
                    $userId = $case["NEW_USER"];
                    $userName = $responseDataTask[0]["USER_NAME"];
                    $userEmail = $responseDataTask[0]["USER_EMAIL"];
                    $responseDataTask[0]["PROCESSMAKER_LINK"] = $environmentBaseUrl;

                    $process_request_id = $responseDataTask[0]["process_request_id"];
                    updateRequestData($process_request_id, $userId, $taskName);

                    //Get notification configuration for BULK REASSIGNMENT EMAIL
                    $currentProcess = $responseDataTask[0]['PROCESS_ID'];
                    $getEmailConfiguration = "SELECT data->>'$.EMS_EMAIL_FROM' AS EMS_EMAIL_FROM,
                                                    data->>'$.EMS_EMAIL_FROM_NAME' AS EMS_EMAIL_FROM_NAME,
                                                    data->>'$.EMS_EMAIL_SUBJECT' AS EMS_EMAIL_SUBJECT,
                                                    data->>'$.EMS_EMAIL_BODY' AS EMS_EMAIL_BODY
                                            FROM collection_" . $emailSettingsCollectionID . "
                                            WHERE data->>'$.EMS_PROCESS_ID' = " . $currentProcess . "
                                                AND data->>'$.EMS_EMAIL_TYPE' = 'BULK_REASSIGNMENT_EMAIL'
                                                AND data->>'$.EMS_EMAIL_STATUS' = 'Active'";
                    $emailConfigurationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getEmailConfiguration));
                    if (empty($emailConfigurationResponse["error_message"])) {
                        if(!empty($emailConfigurationResponse)){
                            //Initialize Email Variables
                            $emailFrom = $emailConfigurationResponse[0]["EMS_EMAIL_FROM"];
                            $emailFromName = $emailConfigurationResponse[0]["EMS_EMAIL_FROM_NAME"];
                            $emailParameters = new EmailDinamicParameters($emailConfigurationResponse[0]["EMS_EMAIL_SUBJECT"], $emailConfigurationResponse[0]["EMS_EMAIL_BODY"]);
                            //Create the Transport
                            $transport = (new Swift_SmtpTransport($smtpServerUrl, 587, 'tls'))
                                ->setUsername($smtpUser)
                                ->setPassword($smtpPassword)
                                ->setTimeout(0);
                            //Create the Mailer using your created Transport
                            $mailer = new Swift_Mailer($transport);
                            //Create a message
                            $message = new Swift_Message();
                            //Set email From Address"
                            $message->setFrom([$emailFrom => $emailFromName]);
                            //--- Send Notification ---
                            //Set Subject
                            $emailSubject = $emailParameters->getSubject();
                            //Replace variables in subject
                            $emailSubject = replaceVariables($emailSubject, $responseDataTask[0]);
                            //Set email subject
                            $message->setSubject($emailSubject);
                            //Set Body
                            $emailBody = $emailParameters->getBody();
                            //Check if body contains logo
                            if (strpos($emailBody, '{NORTHLEAF_LOGO}') !== false) {
                                $emailBody = str_replace("{NORTHLEAF_LOGO}", "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "'  width='200'/>", $emailBody);
                            }
                            //Replace variables in body
                            $emailBody = replaceVariables($emailBody, $responseDataTask[0]);
                            //Set Body
                            $message->setBody($emailBody, 'text/html');
                            //Send Email
                            $message->setTo($userEmail, $userName);
                            
                            $sendEmail = false;
                            if ($mailer->send($message)) {
                                $sendEmail = true;
                            }
                            //Clear parameters subject and body
                            $emailParameters->clearValues();
                        }
                        //Save in Log
                        $queryInsert = 'INSERT INTO BULK_REASSIGNMENT_EMAIL_LOG
                                                    (CASE_NUMBER,
                                                    TASK_ID,
                                                    TASK_NAME,
                                                    PROCESS_NAME,
                                                    USER_ID,
                                                    USER_NAME,
                                                    USER_EMAIL,
                                                    SUBJECT,
                                                    BODY,
                                                    SEND)
                                            VALUES (' . $case["CASE_NUMBER"] . ',
                                                    ' . $taskId . ',
                                                    "' . $taskName . '",
                                                    "' . $processName . '",
                                                    ' . $case["NEW_USER"] . ',
                                                    "' . $userName . '",
                                                    "' . $userEmail . '",
                                                    "' . str_replace('"', "'", $emailSubject) . '",
                                                    "' . str_replace('"', "'", $emailBody) . '",
                                                    ' . ($sendEmail ? "true" : "false") . '
                                                    )';
                        $responseInsertLog = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryInsert));
                        
                    }
                }
            }
        }
    }
}
return true;