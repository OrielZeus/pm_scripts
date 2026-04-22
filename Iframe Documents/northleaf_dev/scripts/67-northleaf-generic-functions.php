<?php
/******************************** 
 * Northleaf - Generic Functions
 *
 * by Cinthia Romero
 *******************************/
ini_set("memory_limit", "1500M");
ini_set('max_execution_time', '0');

/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $url
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res 
 *
 * by Elmer Orihuela 
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        //$res = json_decode($res, true);
         if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

/***
 * Get list of uploaded files by Case Number
 *
 * @param string $case_number
 * @return array $historyFileResponse
 *
 * by Telmo Chiri
 ***/
function getListUploadedFiles($case_number) {
    // Set Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
    // Get IC Approvers collection ID
    $queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' = 'HISTORY_FILES_BY_CASE'";
    $collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
    if (empty($collectionInfo["error_message"])) {
        // Set collection ID
        $collectionId =  $collectionInfo[0]["ID"];
        // Get List Uploaded Files
        $sql = "SELECT FD.data->>'$.HFC_FILE_ID' AS FILE_ID, 
                        FD.data->>'$.HFC_USER_ID' AS USER_ID, 
                        FD.data->>'$.HFC_USER_NAME' AS USER_NAME, 
                        FD.data->>'$.HFC_FILE_NAME' AS FILE_NAME, 
                        FD.data->>'$.HFC_TASK_NAME' AS TASK_NAME  
                FROM collection_$collectionId AS FD
                WHERE FD.data->>'$.HFC_CASE_NUMBER' = '$case_number' AND FD.data->>'$.HFC_STATUS' = 'ACTIVE'";
        $historyFileResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
        if (empty($historyFileResponse["error_message"])) {
            return $historyFileResponse;
        }
    }
    return [];
}
/**
* Class to set the subject and body in an immutable way for sending notifications
* @param string $subject
* @param string $body
*
* created by Telmo Chiri
**/
class EmailDinamicParameters {
    private $subject;
    private $body;

    public function __construct($subject, $body) {
        $this->subject = $subject;
        $this->body = $body;
    }

    public function getSubject() {
        return $this->subject;
    }

    public function getBody() {
        return $this->body;
    }

    public function clearValues() {
        $this->subject = '';
        $this->body = '';
    }
}

/***
 * Send Notification
 * @param array $data
 * @param string $task
 * @param string $emailType
 * By Telmo Chiri
 * Modified by Adriana Centellas
 ***/
function sendNotification($data, $task, $emailType = '', $api)
{
     // Set Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    //Get Settinges of Server Email
    $sql = "";
    $sql .= "SELECT * ";
    $sql .= "FROM settings S ";
    $sql .= "WHERE S.key like '%EMAIL_CONNECTOR_MAIL_%' ";
    $responseQuery = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($sql));
    $aEmailsConnector = [];
    foreach ($responseQuery as $value) {
        $aEmailsConnector[$value["key"]] = $value["config"];
    }    
    
    //Get Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $apiToken = getenv("API_TOKEN");
    $emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
    $masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
    $smtpServerUrl = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"] : getenv('NORTHLEAF_SMTP_ADDRESS');
    $smtpUser = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"] : getenv('NORTHLEAF_SMTP_USER');
    $smtpPassword = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"] : getenv('NORTHLEAF_SMTP_PASSWORD');
    $northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
    $environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
    $serverEnvironment = getenv('SERVER_ENVIRONMENT');
    $requestId = $data['_request']['id'];
    $parentRequestId = $data['_parent']['request_id'] ?? $data['_request']['id'];
    $caseNumber = (int) $data['_request']['case_number'];
    $currentDate = date('Y-m-d');

    $detailResponse = [];
    //Get Collections IDs
    $queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN ('PE_BODY_NOTIFICATION')";
    $collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
    if (empty($collectionInfo["error_message"])) {
        // If email type is diferent to empty
        //$filterEmailType = ($emailType == '') ? "" : " AND data->>'$.EMAIL_TYPE' = '" . $emailType . "' ";
        $filterEmailType = ($emailType == '') ? "AND (data->>'$.EMAIL_TYPE' = 'null' OR data->>'$.EMAIL_TYPE' = '') " : " AND data->>'$.EMAIL_TYPE' = '" . $emailType . "' ";
        // Get Configuration for this task
        $queryConfiguration = "SELECT data->>'$.NODE_ID' AS NODE_ID,
                                    data->>'$.EMAIL_FROM' AS EMAIL_FROM,
                                    data->>'$.EMAIL_FROM_NAME' AS EMAIL_FROM_NAME,
                                    data->>'$.EMAIL_TYPE' AS EMAIL_TYPE,
                                    data->>'$.SUBJECT' AS SUBJECT,
                                    data->>'$.BODY' AS BODY,
                                    data->>'$.TO_SEND_TYPE' AS TO_SEND_TYPE,
                                    data->>'$.EMAIL_LIST_USER_VARIABLE' AS EMAIL_LIST_USER_VARIABLE,
                                    data->>'$.EMAIL_LIST_GROUP_ID' AS EMAIL_LIST_GROUP_ID,
                                    data->>'$.EMAIL_LIST_EMAIL' AS EMAIL_LIST_EMAIL,
                                    data->>'$.ATTACHMENT' AS ATTACHMENT,
                                    data->>'$.ATTACH_FILES' AS ATTACH_FILES
                                FROM collection_" . $collectionInfo[0]['ID'] . "
                                WHERE data->>'$.NODE_ID' = '" . $task . "'
                                    " . $filterEmailType . "
                                    AND data->>'$.STATUS' = 'ACTIVE'
                                LIMIT 1";
        $configurationInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryConfiguration));
        if (empty($configurationInfo["error_message"]) && $configurationInfo[0]['NODE_ID'] == $task) {
            $TO_SEND_TYPE = json_decode($configurationInfo[0]['TO_SEND_TYPE'], true);
            $receiverInfoResponseUserVariable = $receiverInfoResponseGroupId = $receiverInfoResponseEmail = $receiverInfoResponseEveryone = [];
            //------=========== Get All User ==========------
            $queryUsers = "SELECT CONCAT(U.firstname, ' ', U.lastname) AS RECEIVER_FULL_NAME, U.email AS RECEIVER_EMAIL, U.id AS RECEIVER_ID 
                            FROM users AS U
                            WHERE U.status = 'ACTIVE'
                                  AND U.deleted_at IS NULL";
            $usersInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUsers));
            if (!empty($receiverInfoResponse["error_message"])) {
                return [
                    'status' => false,
                    'message' => 'A problem occurred while getting the list of users.'
                ];
            }
            $aUsers = [];
            foreach ($usersInfo as $user) {
                $aUsers[$user['RECEIVER_ID']] = [
                    'RECEIVER_FULL_NAME' => $user['RECEIVER_FULL_NAME'],
                    'RECEIVER_EMAIL' => $user['RECEIVER_EMAIL']
                ];
            }
            //-------------------- Get User List by Variable User --------------------
            if (in_array("USER_VARIABLE", $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_USER_VARIABLE']);
                $variableUsers = [];
                foreach ($emailList as $userEmail) {
                    //Get Information of USER TO SEND EMAIL
                    if ($data[$userEmail->TO_SEND] && $aUsers[$data[$userEmail->TO_SEND]]) {
                        $receiverInfoResponseUserVariable[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $data[$userEmail->TO_SEND]
                        ];
                    }
                }
            }
            //-------------------- Get User List by Group Name --------------------
            if (in_array("GROUP_ID", $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_GROUP_ID']);
                $variableGroups = [];
                foreach ($emailList as $groupEmail) {
                    $variableGroups[] = $groupEmail->TO_SEND;
                }
                $getReceiverInformation = "SELECT GM.member_id
                                            FROM `groups` AS G
                                            INNER JOIN group_members AS GM ON GM.group_id = G.id
                                            WHERE G.name IN ('" . implode("','", $variableGroups) . "')
                                                AND G.status = 'ACTIVE'
                                            GROUP BY GM.member_id";
                $responseInfoResponseGroupId = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getReceiverInformation));
                foreach ($responseInfoResponseGroupId as $member) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$member['member_id']]) {
                        $receiverInfoResponseGroupId[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$member['member_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$member['member_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $member['member_id']
                        ];
                    }
                }
            }
            //-------------------- Get Email List by Email Type --------------------
            if (in_array("EMAIL", $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_EMAIL']);
                $receiverInfoResponseEmail = [];
                foreach ($emailList as $groupEmail) {
                    $name = explode('@', $groupEmail->TO_SEND);
                    $receiverInfoResponseEmail[] = [
                        "RECEIVER_FULL_NAME" => $name[0],
                        "RECEIVER_EMAIL" => $groupEmail->TO_SEND,
                        "RECEIVER_ID" => $groupEmail->TO_SEND
                    ];
                }
            }
            //-------------------- Get Everyone involved  --------------------
            if (in_array("EVERYONE", $TO_SEND_TYPE)) {
                $queryEveryone = "SELECT status, PRT.process_id, PRT.process_request_id as request_id, PRT.user_id, PRT.element_id AS node, PRT.element_name AS task, PRT.element_type
                                FROM process_request_tokens AS PRT
                                WHERE PRT.element_type='task' AND 
                                    (PRT.process_request_id='" . $parentRequestId . "' OR PRT.data->>'$._parent.request_id' = '" . $parentRequestId . "')";
                $responseEveryone = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryEveryone));
                foreach ($responseEveryone as $user) {
                    //Get Information of USER TO SEND EMAIL
                    if (is_array($user) && isset($user['user_id']) && isset($aUsers[$user['user_id']])) {
                        $receiverInfoResponseEveryone[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$user['user_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$user['user_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $user['user_id']
                        ];
                    }
                }
            }
            // Merge Email List
            $receiverInfoResponse = array_unique(array_merge($receiverInfoResponseUserVariable, $receiverInfoResponseGroupId, $receiverInfoResponseEmail, $receiverInfoResponseEveryone), SORT_REGULAR);
            if (empty($receiverInfoResponse["error_message"]) && count($receiverInfoResponse) > 0) {
                //Initialize Email Variables
                $emailFrom = $configurationInfo[0]["EMAIL_FROM"];
                $emailFromName = $configurationInfo[0]["EMAIL_FROM_NAME"];
                $emailParameters = new EmailDinamicParameters($configurationInfo[0]["SUBJECT"], $configurationInfo[0]["BODY"]);
                //Add PDF Generated
                $documentsList = $configurationInfo[0]["ATTACHMENT"] == 'true' ? json_decode($configurationInfo[0]["ATTACH_FILES"]) : [];
                $idFiles = [];
                foreach ($documentsList as $document) {
                    if ($data[$document->FILE_ID]) {
                        $idFiles[] = $data[$document->FILE_ID];
                    }
                }
                //Get document's names
                $getDocumentsInformation = "SELECT id AS FILE_ID,
                                                file_name AS FILE_NAME
                                            FROM media
                                            WHERE id IN ('" . implode("','", $idFiles) . "')";
                $documentsInformationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getDocumentsInformation));
                $documentsToAttach = [];
                if (empty($documentsInformationResponse["error_message"])) {
                    foreach ($documentsInformationResponse as $document) {
                        $documentsToAttach[] = [
                            "DOCUMENT_ID" => $document["FILE_ID"],
                            "DOCUMENT_NAME" => $document["FILE_NAME"]
                        ];
                    }
                }
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
                $attachFiles = false;
                //Review if is necesary attach files
                if (count($documentsToAttach) > 0) {
                    //Attach documents
                    $apiInstance = $api->requestFiles();
                    foreach ($documentsToAttach as $document) {
                        // Attach PDF Generate
                        $file = $apiInstance->getRequestFilesById($requestId, $document["DOCUMENT_ID"]);
                        $documentPath = $file->getPathname();
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($document["DOCUMENT_NAME"]));
                        $attachFiles = true;
                    }
                }
                // Send to Receivers
                foreach ($receiverInfoResponse as $receiver) {
                    $data['USER_NAME'] = $receiver["RECEIVER_FULL_NAME"];
                    // Extra name 
                    $data['USER_DT01'] = $aUsers[$data['PE_USER_DT01']]['RECEIVER_FULL_NAME'] ?? '';
                    $data['USER_LL02'] = $aUsers[$data['PE_RED_FLAG_LEGAL_REVIEW']]['RECEIVER_FULL_NAME'] ?? '';
                    $data['USER_LL04'] = $aUsers[$data['PE_RED_FLAG_LEGAL_REVIEW']]['RECEIVER_FULL_NAME'] ?? '';
                    $data['USER_TX02'] = $aUsers[$data['PE_RED_FLAG_TAX_REVIEW']]['RECEIVER_FULL_NAME'] ?? '';
                    $data['USER_TX04'] = $aUsers[$data['PE_RED_FLAG_TAX_REVIEW']]['RECEIVER_FULL_NAME'] ?? '';
                    $data['USER_LL08'] = $aUsers[$data['PE_RED_FLAG_LEGAL_REVIEW']]['RECEIVER_FULL_NAME'] ?? '';
                    $data['USER_PA01'] = $aUsers[$data['PE_PORTFOLIO_MANAGER_APPROVER']]['RECEIVER_FULL_NAME'] ?? '';
                    //Set Subject
                    $emailSubject = $emailParameters->getSubject();
                    //Replace variables in subject
                    $emailSubject = replaceVariables($emailSubject, $data);
                    //Set email subject
                    $message->setSubject($emailSubject);
                    //Set Body
                    $emailBody = $emailParameters->getBody();
                    //Check if body contains logo
                    if (strpos($emailBody, '{NORTHLEAF_LOGO}') !== false) {
                        $emailBody = str_replace("{NORTHLEAF_LOGO}", "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "'  width='200'/>", $emailBody);
                    }
                    //Replace variables in body
                    $emailBody = replaceVariables($emailBody, $data);
                    //Set Body
                    $message->setBody($emailBody, 'text/html');
                    //Send Email
                    $message->setTo($receiver["RECEIVER_EMAIL"], $receiver["RECEIVER_FULL_NAME"]);
                    //$message->addCc('ana.castillo+qa@processmaker.com', 'PM Tester Email');
                    // We check if it is a development environment so as not to send emails to the client
                    if ($serverEnvironment == 'DEV' && strpos(strtolower($receiver['RECEIVER_EMAIL']), '@northleaf')) {
                        $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $receiver["RECEIVER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        false,
                                                        'Actual Server Enviroment is DEV'
                                                    );";
                        $detailResponse[] = [
                            'status' => false,
                            'message' => 'Could not send email to ' . $receiver["RECEIVER_EMAIL"] . ' because the Enviroment is DEV'
                        ];
                        //Record on collection
                        $arrayNote = [];
                        $arrayNote['BODY'] = $emailBody;
                        $arrayNote['DATE'] = $currentDate;
                        $arrayNote['SEND'] = false;
                        $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
                        $arrayNote['DETAIL'] = 'Actual Server Enviroment is DEV';
                        $arrayNote['SUBJECT'] = $emailSubject;
                        $arrayNote['TO_SEND'] = $receiver["RECEIVER_EMAIL"];
                        $arrayNote['EMAIL_FROM'] = $emailFrom;
                        $arrayNote['CASE_NUMBER'] = $caseNumber;
                        $arrayNote['NODE_TASK_ID'] = $task;
                        $notificationLogcollectionId = getCollectionId("PE_NOTIFICATION_LOG", $apiUrl);
                        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
                        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
                        // Skip send email
                        goto saveLog;
                    }
                    // Try send Email
                    if (!$mailer->send($message)) {
                        $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $receiver["RECEIVER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        false,
                                                        '" . $mail->ErrorInfo . "'
                                                    );";
                        $detailResponse[] = [
                            'status' => false,
                            'message' => 'Could not send email to ' . $receiver["RECEIVER_EMAIL"]
                        ];
                        //Record on collection
                        $arrayNote = [];
                        $arrayNote['BODY'] = $emailBody;
                        $arrayNote['DATE'] = $currentDate;
                        $arrayNote['SEND'] = false;
                        $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
                        $arrayNote['DETAIL'] = $mail->ErrorInfo;
                        $arrayNote['SUBJECT'] = $emailSubject;
                        $arrayNote['TO_SEND'] = $receiver["RECEIVER_EMAIL"];
                        $arrayNote['EMAIL_FROM'] = $emailFrom;
                        $arrayNote['CASE_NUMBER'] = $caseNumber;
                        $arrayNote['NODE_TASK_ID'] = $task;
                        $notificationLogcollectionId = getCollectionId("PE_NOTIFICATION_LOG", $apiUrl);
                        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
                        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
                    } else {
                        $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $receiver["RECEIVER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        true,
                                                        ''
                                                    );";
                        $detailResponse[] = [
                            'status' => true,
                            'message' => 'Email successfully sent to ' . $receiver["RECEIVER_EMAIL"]
                        ];
                        //Record on collection
                        $arrayNote = [];
                        $arrayNote['BODY'] = $emailBody;
                        $arrayNote['DATE'] = $currentDate;
                        $arrayNote['SEND'] = true;
                        $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
                        $arrayNote['DETAIL'] = '';
                        $arrayNote['SUBJECT'] = $emailSubject;
                        $arrayNote['TO_SEND'] = $receiver["RECEIVER_EMAIL"];
                        $arrayNote['EMAIL_FROM'] = $emailFrom;
                        $arrayNote['CASE_NUMBER'] = $caseNumber;
                        $arrayNote['NODE_TASK_ID'] = $task;
                        $notificationLogcollectionId = getCollectionId("PE_NOTIFICATION_LOG", $apiUrl);
                        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
                        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
                    }
                    saveLog:
                    // Save in Log
                    $responseInsertLog = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryInsert));
                }
                //Clear parameters subject and body
                $emailParameters->clearValues();
            } else {
                $detailResponse = [
                    'status' => false,
                    'message' => 'A problem occurred when obtaining data from the receivers (' . implode(', ', $TO_SEND_TYPE) . ')'
                ];
            }
        } else {
            $detailResponse = [
                'status' => false,
                'message' => 'No active configuration found for this node ' . $task
            ];
        }
    }
    return $detailResponse;
}

/**
 * Send Notification for Invoice Process
 * @param array $data
 * @param string $task
 * @param string $emailType
 * @param object $api
 * @return array
 * created by Telmo Chiri
 */
function sendInvoiceNotification($data, $task, $emailType, $api)
{
    // Set Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    //Get Settinges of Server Email
    $sql = "";
    $sql .= "SELECT * ";
    $sql .= "FROM settings S ";
    $sql .= "WHERE S.key like '%EMAIL_CONNECTOR_MAIL_%' ";
    $responseQuery = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($sql));
    $aEmailsConnector = [];
    foreach ($responseQuery as $value) {
        $aEmailsConnector[$value["key"]] = $value["config"];
    }

    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $masterCollectionID = getenv('IN_MASTER_COLLECTION_ID');
    $smtpServerUrl = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"] : getenv('NORTHLEAF_SMTP_ADDRESS');
    $smtpUser = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"] : getenv('NORTHLEAF_SMTP_USER');
    $smtpPassword = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"] : getenv('NORTHLEAF_SMTP_PASSWORD');
    $northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
    $environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
    $serverEnvironment = getenv('IN_SERVER_ENVIRONMENT');

    $requestId = $data['_request']['id'];
    $parentRequestId = $data['_parent']['request_id'] ?? $data['_request']['id'];
    $caseNumber = (int) $data['_request']['case_number'];
    $currentDate = date('Y-m-d');

    $detailResponse = [];
    //Get Collections IDs
    $queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN ('IN_BODY_NOTIFICATION')";
    $collectionInfo = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($queryCollectionID));
    if (empty($collectionInfo['error_message'])) {
        // If email type is diferent to empty
        $filterEmailType = ($emailType == '') ?
            "AND (data->>'$.EMAIL_TYPE' = 'null' OR data->>'$.EMAIL_TYPE' = '') "
            :
            " AND data->>'$.EMAIL_TYPE' = '" . $emailType . "' ";
        // Get Configuration for this task
        $queryConfiguration = "SELECT data->>'$.NODE_ID' AS NODE_ID,
                                    data->>'$.EMAIL_FROM' AS EMAIL_FROM,
                                    data->>'$.EMAIL_FROM_NAME' AS EMAIL_FROM_NAME,
                                    data->>'$.EMAIL_TYPE' AS EMAIL_TYPE,
                                    data->>'$.SUBJECT' AS SUBJECT,
                                    data->>'$.BODY' AS BODY,
                                    data->>'$.TO_SEND_TYPE' AS TO_SEND_TYPE,
                                    data->>'$.EMAIL_LIST_USER_VARIABLE' AS EMAIL_LIST_USER_VARIABLE,
                                    data->>'$.EMAIL_LIST_GROUP_ID' AS EMAIL_LIST_GROUP_ID,
                                    data->>'$.EMAIL_LIST_EMAIL' AS EMAIL_LIST_EMAIL,
                                    data->>'$.ATTACHMENT' AS ATTACHMENT,
                                    data->>'$.ATTACH_FILES' AS ATTACH_FILES,
                                    data->>'$.SEND_CC' AS SEND_CC,
                                    data->>'$.TO_SEND_TYPE_CC' AS TO_SEND_TYPE_CC,
                                    data->>'$.EMAIL_LIST_USER_VARIABLE_CC' AS EMAIL_LIST_USER_VARIABLE_CC,
                                    data->>'$.EMAIL_LIST_GROUP_ID_CC' AS EMAIL_LIST_GROUP_ID_CC,
                                    data->>'$.EMAIL_LIST_EMAIL_CC' AS EMAIL_LIST_EMAIL_CC
                                FROM collection_" . $collectionInfo[0]['ID'] . "
                                WHERE data->>'$.NODE_ID' = '" . $task . "'
                                    " . $filterEmailType . "
                                    AND data->>'$.STATUS' = 'ACTIVE'
                                LIMIT 1";
        $configurationInfo = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($queryConfiguration));
        
        if (empty($configurationInfo['error_message']) && $configurationInfo[0]['NODE_ID'] == $task) {
            $TO_SEND_TYPE = json_decode($configurationInfo[0]['TO_SEND_TYPE'], true);
            $receiverInfoResponseUserVariable = [];
            $receiverInfoResponseGroupId = [];
            $receiverInfoResponseEmail = [];
            $receiverInfoResponseEveryone = [];
            // CC
            $TO_SEND_TYPE_CC = json_decode($configurationInfo[0]['TO_SEND_TYPE_CC'] ?? '[]', true) ?? [];
            $receiverInfoResponseUserVariableCC = [];
            $receiverInfoResponseGroupIdCC = [];
            $receiverInfoResponseEmailCC = [];
            $receiverInfoResponseEveryoneCC = [];

            //------=========== Get All User ==========------
            $queryUsers = "SELECT CONCAT(U.firstname, ' ', U.lastname) AS RECEIVER_FULL_NAME,
                                U.email AS RECEIVER_EMAIL,
                                U.id AS RECEIVER_ID
                            FROM users AS U
                            WHERE U.status = 'ACTIVE'
                                  AND U.deleted_at IS NULL";
            $usersInfo = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($queryUsers));
            if (!empty($receiverInfoResponse['error_message'])) {
                return [
                    'status' => false,
                    'message' => 'A problem occurred while getting the list of users.',
                ];
            }
            $aUsers = [];
            foreach ($usersInfo as $user) {
                $aUsers[$user['RECEIVER_ID']] = [
                    'RECEIVER_FULL_NAME' => $user['RECEIVER_FULL_NAME'],
                    'RECEIVER_EMAIL' => $user['RECEIVER_EMAIL'],
                ];
            }
            //-------------------- Get User List by Variable User --------------------
            if (in_array('USER_VARIABLE', $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_USER_VARIABLE']);
                $variableUsers = [];
                foreach ($emailList as $userEmail) {
                    //Get Information of USER TO SEND EMAIL
                    if ($data[$userEmail->TO_SEND] && $aUsers[$data[$userEmail->TO_SEND]]) {
                        $receiverInfoResponseUserVariable[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $data[$userEmail->TO_SEND],
                        ];
                    }
                }
            }
            //-------------------- Get User List by Group Name --------------------
            if (in_array('GROUP_ID', $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_GROUP_ID']);
                $variableGroups = [];
                foreach ($emailList as $groupEmail) {
                    $variableGroups[] = $groupEmail->TO_SEND;
                }
                $getReceiverInformation = "SELECT GM.member_id
                                            FROM `groups` AS G
                                            INNER JOIN group_members AS GM ON GM.group_id = G.id
                                            WHERE G.name IN ('" . implode("','", $variableGroups) . "')
                                                AND G.status = 'ACTIVE'
                                            GROUP BY GM.member_id";
                $responseInfoResponseGroupId = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($getReceiverInformation));
                foreach ($responseInfoResponseGroupId as $member) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$member['member_id']]) {
                        $receiverInfoResponseGroupId[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$member['member_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$member['member_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $member['member_id'],
                        ];
                    }
                }
            }
            //-------------------- Get Email List by Email Type --------------------
            if (in_array('EMAIL', $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_EMAIL']);
                $receiverInfoResponseEmail = [];
                foreach ($emailList as $groupEmail) {
                    $name = explode('@', $groupEmail->TO_SEND);
                    $receiverInfoResponseEmail[] = [
                        'RECEIVER_FULL_NAME' => $name[0],
                        'RECEIVER_EMAIL' => $groupEmail->TO_SEND,
                        'RECEIVER_ID' => $groupEmail->TO_SEND,
                    ];
                }
            }
            //-------------------- Get Everyone involved  --------------------
            if (in_array('EVERYONE', $TO_SEND_TYPE)) {
                $queryEveryone = "SELECT DISTINCT PRT.user_id
                                FROM process_request_tokens AS PRT
                                WHERE PRT.element_type='task'
                                    AND PRT.process_request_id='" . $parentRequestId . "'";
                $responseEveryone = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($queryEveryone));
                foreach ($responseEveryone as $user) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$user['user_id']]) {
                        $receiverInfoResponseEveryone[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$user['user_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$user['user_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $user['user_id'],
                        ];
                    }
                }
            }
            //================================ CC ================================
            //-------------------- Get User List by Variable User (CC) --------------------
            if (in_array('USER_VARIABLE', $TO_SEND_TYPE_CC)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_USER_VARIABLE_CC']);
                $variableUsers = [];
                foreach ($emailList as $userEmail) {
                    //Get Information of USER TO SEND EMAIL
                    if ($data[$userEmail->TO_SEND] && $aUsers[$data[$userEmail->TO_SEND]]) {
                        $receiverInfoResponseUserVariableCC[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $data[$userEmail->TO_SEND],
                        ];
                    }
                }
            }
            //-------------------- Get User List by Group Name (CC) --------------------
            if (in_array('GROUP_ID', $TO_SEND_TYPE_CC)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_GROUP_ID_CC']);
                $variableGroups = [];
                foreach ($emailList as $groupEmail) {
                    $variableGroups[] = $groupEmail->TO_SEND;
                }
                $getReceiverInformation = "SELECT GM.member_id
                                            FROM `groups` AS G
                                            INNER JOIN group_members AS GM ON GM.group_id = G.id
                                            WHERE G.name IN ('" . implode("','", $variableGroups) . "')
                                                AND G.status = 'ACTIVE'
                                            GROUP BY GM.member_id";
                $responseInfoResponseGroupId = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($getReceiverInformation));
                foreach ($responseInfoResponseGroupId as $member) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$member['member_id']]) {
                        $receiverInfoResponseGroupIdCC[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$member['member_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$member['member_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $member['member_id'],
                        ];
                    }
                }
            }
            //-------------------- Get Email List by Email Type (CC) --------------------
            if (in_array('EMAIL', $TO_SEND_TYPE_CC)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_EMAIL_CC']);
                $receiverInfoResponseEmail = [];
                foreach ($emailList as $groupEmail) {
                    $name = explode('@', $groupEmail->TO_SEND);
                    $receiverInfoResponseEmailCC[] = [
                        'RECEIVER_FULL_NAME' => $name[0],
                        'RECEIVER_EMAIL' => $groupEmail->TO_SEND,
                        'RECEIVER_ID' => $groupEmail->TO_SEND,
                    ];
                }
            }
            //-------------------- Get Everyone involved (CC) --------------------
            if (in_array('EVERYONE', $TO_SEND_TYPE_CC)) {
                $queryEveryone = "SELECT DISTINCT PRT.user_id
                                FROM process_request_tokens AS PRT
                                WHERE PRT.element_type='task'
                                    AND PRT.process_request_id='" . $parentRequestId . "'";
                $responseEveryone = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($queryEveryone));
                foreach ($responseEveryone as $user) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$user['user_id']]) {
                        $receiverInfoResponseEveryoneCC[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$user['user_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$user['user_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $user['user_id'],
                        ];
                    }
                }
            }
            // Merge Email List
            $receiverInfoResponse = array_unique(
                array_merge(
                    $receiverInfoResponseUserVariable,
                    $receiverInfoResponseGroupId,
                    $receiverInfoResponseEmail,
                    $receiverInfoResponseEveryone
                ),
                SORT_REGULAR
            );
            // Merge Email List (CC)
            $receiverInfoResponseCC = array_unique(
                array_merge(
                    $receiverInfoResponseUserVariableCC,
                    $receiverInfoResponseGroupIdCC,
                    $receiverInfoResponseEmailCC,
                    $receiverInfoResponseEveryoneCC
                ),
                SORT_REGULAR
            );

            if (is_array($receiverInfoResponseCC) && count($receiverInfoResponseCC) > 0) {
                // --- CC LIST CLEANING ---
                // Step A: Extract only the emails from array $receiverInfoResponse to speed up the search
                $mainEmails = array_column($receiverInfoResponse, 'RECEIVER_EMAIL');
                // Step B: Filter the $receiverInfoResponseCC array
                $receiverInfoResponseCC_clean = array_filter($receiverInfoResponseCC, function($nodo) use ($mainEmails) {
                    // If the email in the current node of $receiverInfoResponseCC is NOT in the $receiverInfoResponse list, keep it
                    return !in_array($nodo['RECEIVER_EMAIL'], $mainEmails);
                });
                // Step C: Reindex the keys of the resulting array
                $receiverInfoResponseCC_clean = array_values($receiverInfoResponseCC_clean);
                $receiverInfoResponseCC = $receiverInfoResponseCC_clean;
            }

            if (empty($receiverInfoResponse['error_message']) && count($receiverInfoResponse) > 0) {
                //Initialize Email Variables
                $emailFrom = $configurationInfo[0]['EMAIL_FROM'];
                $emailFromName = $configurationInfo[0]['EMAIL_FROM_NAME'];
                $emailParameters = new EmailDinamicParameters($configurationInfo[0]['SUBJECT'], $configurationInfo[0]['BODY']);
                //Add PDF Generated
                $documentsList = $configurationInfo[0]['ATTACHMENT'] == 'true' ?
                    json_decode($configurationInfo[0]['ATTACH_FILES'])
                    :
                    [];
                // Merge PDF Case
                $withMerge = false;
                foreach ($documentsList as $document) {
                    // Verify if node FILE_ID exist
                    if (isset($document->FILE_ID) && $document->FILE_ID === "MERGE_PDF") {
                        // We check if node NO_MERGED has information
                        if (isset($data['NO_MERGED']) && count($data['NO_MERGED'])>0 ) {
                            $withMerge = true;
                        }
                        break;
                    }
                }
                if ($withMerge) {
                    $objeto = new stdClass();
                    $objeto->FILE_ID = "NO_MERGED";
                    $documentsList[] = $objeto;
                }
                // End Merge
                $idFiles = [];
                foreach ($documentsList as $document) {
                    // If variable exist in Request Data
                    if ($data[$document->FILE_ID]) {
                        // If is Multiple File
                        if (is_array($data[$document->FILE_ID])) {
                            foreach($data[$document->FILE_ID] as $multiFile) {
                                if (isset($multiFile["file"])) {
                                    $idFiles[] = $multiFile["file"];
                                }
                            }
                        } else {
                            $idFiles[] = $data[$document->FILE_ID];
                        }
                    }
                }
                $documentsToAttach = [];
                if (count($idFiles) > 0) {
                    //Get document's names
                    $getDocumentsInformation = "SELECT id AS FILE_ID,
                                                    file_name AS FILE_NAME
                                                FROM media
                                                WHERE id IN ('" . implode("','", $idFiles) . "')";
                    $documentsInformationResponse = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($getDocumentsInformation));
                    if (empty($documentsInformationResponse['error_message'])) {
                        foreach ($documentsInformationResponse as $document) {
                            $documentsToAttach[] = [
                                'DOCUMENT_ID' => $document['FILE_ID'],
                                'DOCUMENT_NAME' => $document['FILE_NAME'],
                            ];
                        }
                    }
                }
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
                $attachFiles = false;
                //Review if is necesary attach files
                if (count($documentsToAttach) > 0) {
                    //Attach documents
                    $apiInstance = $api->requestFiles();
                    foreach ($documentsToAttach as $document) {
                        // Attach PDF Generate
                        $file = $apiInstance->getRequestFilesById($requestId, $document['DOCUMENT_ID']);
                        $documentPath = $file->getPathname();
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($document['DOCUMENT_NAME']));
                        $attachFiles = true;
                    }
                }
                // Send to Receivers
                foreach ($receiverInfoResponse as $receiver) {
                    // 1. Initialize control variables
                    $isRestricted = false;
                    // $restrictedEmail = ''; // This may be used later if you want to save the email address for which the notification could not be sent.

                    // 2. Validate the main receiver
                    if ($serverEnvironment == 'DEV' && strpos(strtolower($receiver['RECEIVER_EMAIL']), '@northleaf') !== false) {
                        $isRestricted = true;
                        // $restrictedEmail = $receiver['RECEIVER_EMAIL'];
                    }

                    // 3. Validate CC Recipients (Only if not yet marked as restricted)
                    if (!$isRestricted && $serverEnvironment == 'DEV') {
                        foreach ($receiverInfoResponseCC as $receiverCC) {
                            if (strpos(strtolower($receiverCC['RECEIVER_EMAIL']), '@northleaf') !== false) {
                                $isRestricted = true;
                                // $restrictedEmail = $receiverCC['RECEIVER_EMAIL'] . ' (CC)';
                                break; // Exit the CC loop upon finding the first one
                            }
                        }
                    }
                    // 4. Common configuration of mail content
                    //Set Data
                    $data['USER_NAME'] = $receiver['RECEIVER_FULL_NAME'];
                    $data['CASE_NUMBER'] = $caseNumber;
                    $data['LINK'] = $environmentBaseUrl . 'cases/' . $caseNumber;
                    //Set Subject
                    $emailSubject = $emailParameters->getSubject();
                    //Replace variables in subject
                    $emailSubject = replaceVariables($emailSubject, $data);
                    //Set email subject
                    $message->setSubject($emailSubject);
                    //Set Body
                    $emailBody = $emailParameters->getBody();
                    //Check if body contains logo
                    if (strpos($emailBody, '{NORTHLEAF_LOGO}') !== false) {
                        $emailBody = str_replace('{NORTHLEAF_LOGO}', "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "'  width='200'/>", $emailBody);
                    }
                    //Replace variables in body
                    $emailBody = replaceVariables($emailBody, $data);
                    //Set Body
                    $message->setBody($emailBody, 'text/html');
                    
                    // 5. Sending Logic vs Restriction Log
                    // We check if it is a development environment so as not to send emails to the client
                    if ($isRestricted) {
                        // Bloqueado por entorno DEV y dominio restringido
                        $detailResponse[] = [
                            'status' => false,
                            'message' => "Could not send email to {$receiver['RECEIVER_EMAIL']} because the Enviroment is DEV",
                        ];
                        
                        logNotification($emailBody, $currentDate, false, $attachFiles, 'Actual Server Enviroment is DEV', $emailSubject, $receiver['RECEIVER_EMAIL'], json_encode($receiverInfoResponseCC), $emailFrom, $caseNumber, $task, $apiUrl, $apiHost);
                        
                    } else {
                        // Proceed with recipient and shipping configuration 
                        $message->setTo($receiver['RECEIVER_EMAIL'], $receiver['RECEIVER_FULL_NAME']);
                        // Clear previous CCs (important if $message is reused in the loop)
                        $message->setCc([]); 
                        foreach ($receiverInfoResponseCC as $receiverCC) { 
                            $message->addCc($receiverCC['RECEIVER_EMAIL'], $receiverCC['RECEIVER_FULL_NAME']); 
                        }
                        // Try send Email
                        if (!$mailer->send($message)) {
                            $detailResponse[] = [
                                'status' => false,
                                'message' => 'Could not send email to ' . $receiver['RECEIVER_EMAIL'],
                            ];
                            logNotification($emailBody, $currentDate, false, $attachFiles, $mailer->ErrorInfo, $emailSubject, $receiver['RECEIVER_EMAIL'], json_encode($receiverInfoResponseCC), $emailFrom, $caseNumber, $task, $apiUrl, $apiHost);
                        } else {
                            $detailResponse[] = [
                                'status' => true,
                                'message' => 'Email successfully sent to ' . $receiver['RECEIVER_EMAIL'],
                            ];
                            logNotification($emailBody, $currentDate, true, $attachFiles, '', $emailSubject, $receiver['RECEIVER_EMAIL'], json_encode($receiverInfoResponseCC), $emailFrom, $caseNumber, $task, $apiUrl, $apiHost);
                        }
                    }
                }
                //Clear parameters subject and body
                $emailParameters->clearValues();
            } else {
                $detailResponse = [
                    'status' => false,
                    'message' => 'A problem occurred when obtaining data from the receivers (' . implode(', ', $TO_SEND_TYPE) . ')',
                ];
            }
        } else {
            $detailResponse = [
                'status' => false,
                'message' => 'No active configuration found for this node ' . $task,
            ];
        }
    }

    return $detailResponse;
}

// Save in Log Notification
/**
 * Save in Log Notification Collection
 * @param string $emailBody
 * @param string $currentDate
 * @param bool $sendStatus
 * @param bool $attachFiles
 * @param string $detail
 * @param string $emailSubject
 * @param string $receiverEmail
 * @param string $receiverCCEmail
 * @param string $emailFrom
 * @param int $caseNumber
 * @param string $task
 * @param string $apiUrl
 * @param string $apiHost
 * @return void
 * created by Telmo Chiri
 */
function logNotification($emailBody, $currentDate, $sendStatus, $attachFiles, $detail, $emailSubject, $receiverEmail, $receiverCCEmail, $emailFrom, $caseNumber, $task, $apiUrl, $apiHost)
{
    // Prepare data to save
    $arrayNote = [
        'BODY' => $emailBody,
        'DATE' => $currentDate,
        'SEND' => $sendStatus ? true : false,
        'ATTACH' => $attachFiles ? 'true' : 'false',
        'DETAIL' => $detail,
        'SUBJECT' => $emailSubject,
        'TO_SEND' => $receiverEmail,
        'TO_SEND_CC' => $receiverCCEmail,
        'EMAIL_FROM' => $emailFrom,
        'CASE_NUMBER' => $caseNumber,
        'NODE_TASK_ID' => $task,
    ];
    //Record on collection
    $notificationLogCollectionId = getCollectionId('IN_NOTIFICATION_LOG', $apiUrl);
    $url = $apiHost . '/collections/' . $notificationLogCollectionId . '/records';
    callApiUrlGuzzle($url, 'POST', ['data' => $arrayNote]);
}

/* 
 * Replace Variables
 *
 * @param string $text
 * @param array $caseData
 * @return string $finalText
 *
 * by Cinthia Romero
 */
function replaceVariables($text, $caseData)
{
    //Divide string by {
    $variablesExtracted = explode('{', $text);
    foreach ($variablesExtracted as $variable) {
        //Check if text contains }
        if (strpos($variable, '}') !== false) {
            //Remove additional text after variable
            $evaluatedVariable = explode('}', $variable)[0];
            //Check if variable exist in data
            if (!empty($caseData[$evaluatedVariable])) {
                //Replace value
                $text = str_replace("{" . $evaluatedVariable . "}", $caseData[$evaluatedVariable], $text);
            } else {
                $text = str_replace("{" . $evaluatedVariable . "}", "", $text);
            }
        }
    }
    return $text;
}

/**
 * Get Group Users
 *
 * @param (String) $groupId
 * @param (String) $apiUrl
 * @return (Array) $groupUsersResponse
 *
 * by Cinthia Romero
 * modified by Telmo Chiri
 */
function getGroupUsers($groupId, $apiUrl)
{
    //Select Group id and name
    $queryGroupUsers = "SELECT U.id AS ID,
                               CONCAT(U.firstname, ' ', U.lastname) AS FULL_NAME
                        FROM users AS U
                        INNER JOIN group_members AS G
                            ON G.member_id = U.id
                        WHERE G.group_id = " . $groupId . " 
                            AND U.status = 'ACTIVE' 
                            AND U.deleted_at IS NULL
                        ORDER BY U.lastname ASC";
    $groupUsersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupUsers)) ?? [];
    return $groupUsersResponse;
}

/**
 * Get IDs of collections with the Master collection
 *
 * @param (Array) $collectionNames
 * @param (String) $apiUrl
 * @param (String) $environmentName
 * @return (Array) $aCollections
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 */
function getCollectionIdMaster($collectionNames, $apiUrl, $environmentName = 'PE_MASTER_COLLECTION_ID')
{
    //Set Master Collection ID
    $masterCollectionID = getenv($environmentName);

    //Add semicolon with all fields of the array
    $collectionName = array_map(function($item) {
        return '"' . $item . '"';
    }, $collectionNames);

    //Merge all values of the array with commas
    $collections = implode(", ", $collectionName);

    //Get Collections IDs
    $sQCollectionsId = "SELECT data->>'$.COLLECTION_ID' AS ID,
                               data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN (" . $collections . ")";
    $collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    //Set array to get the name with the ID
    $aCollections = array();
    if (count($collectionsInfo) > 0) {
        foreach ($collectionsInfo as $item) {
            $aCollections[$item['COLLECTION_NAME']] = $item['ID'];
        }
    }

    return $aCollections;
}

/**
 * Get Group Id with the name
 *
 * @param (String) $groupName
 * @param (String) $apiUrl
 * @return (String) $groupID
 *
 * by Ana Castillo
 */
function getGroupId($groupName, $apiUrl)
{
    $groupID = "";
    //Get Id Group
    $sqlGroupName = "SELECT id 
                     FROM `groups` 
                     WHERE name = '" . $groupName . "'";
    $rQGroupName = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlGroupName)) ?? [];
    if (count($rQGroupName) > 0) {
        $groupID = $rQGroupName[0]['id'];
    }
    return $groupID;
}

/**
 * Get User Lead configured on the collection
 *
 * @param (String) $collectionLeaderId
 * @param (String) $groupId
 * @param (String) $apiUrl
 * @return (String) $userLeadId
 *
 * by Ana Castillo
 */
function getUserLeadGroup($collectionLeaderId, $groupId, $apiUrl)
{
    $userLeadId = "";
    //Get User Leader
    $sqlUserLeader = "SELECT GL.data->>'$.GROUP_LEADER_USER' as USER_LEADER
                      FROM collection_" . $collectionLeaderId . " as GL
                      WHERE GL.data->>'$.GROUP_LEADER_GROUP' = " . $groupId . "
                          AND GL.data->>'$.GROUP_LEADER_STATUS' = 'Active'";
    $rQUserLeader = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUserLeader)) ?? [];
    if (count($rQUserLeader) > 0) {
        $userLeadId = $rQUserLeader[0]['USER_LEADER'];
    }
    return $userLeadId;
}

/**
 * Get the ID of a collection based on its name.
 *
 * @param (String) $nameToSearch - The name of the collection to search for.
 * @param (String) $apiUrl - The URL of the API to call.
 * @return (Array|int) $collectionsInfo - The collection information, or 0 if not found.
 *
 * by Adriana Centellas
 */
function getCollectionId($nameToSearch, $apiUrl)
{
    // Prepare SQL query to fetch the collection ID by its name
    $sQCollectionsId = "SELECT collections.id
                        FROM collections
                        WHERE collections.name = '" . $nameToSearch . "'";

    // Send API request to fetch collection information, return 0 if not found
    $collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? 0;
    // Return the first id found
    return $collectionsInfo[0]["id"];
}

/**
 * Get the records of a collection based on its ID.
 *
 * @param (String) $ID - The ID of the collection to fetch records from.
 * @param (String) $apiUrl - The URL of the API to call.
 * @return (Array) $collectionRecords - The collection records, or an empty array if none are found.
 *
 * by Adriana Centellas
 */
function getCollectionRecords($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data
                        FROM collection_" . $ID;

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    return $collectionRecords;
}


/**
 * Get User Task Lead configured on the collection
 *
 * @param (String) $collectionId
 * @param (String) $taskCode
 * @param (String) $apiUrl
 * @return (String) $officeLocation
 *
 * by Adriana Centellas
 */
function getTaskLeadGroup($collectionId, $taskCode, $apiUrl, $officeLocation)
{
    $userLeadId = "";
    //Get User Leader
    $sqlUserLeader = "select 
                    TL.data->>'$.OFF_LEAD_USER' as USER_LEADER
                    from collection_" . $collectionId . " as TL
                    WHERE TL.data->>'$.OFF_TASK_CODE' = '" . $taskCode . "'
                    AND JSON_CONTAINS(TL.data->'$.OFF_OFFICE_LOCATION', '\"". $officeLocation . "\"') 
                    AND TL.data->>'$.OFF_LEAD_USER'";
    $rQUserLeader = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUserLeader)) ?? [];
    $userLeadId = $rQUserLeader[0]['USER_LEADER'];

    return $userLeadId;
}

/***
 * Send Notification for Off Boarding
 *
 * @param array $data
 * @param string $task
 * @param string $emailType
 *
 * By Adriana Centellas
 * Adapted copy from sendNotification
 ***/
function sendNotificationOffboarding($data, $task, $emailType = '', $api)
{ 
    //Get Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $apiToken = getenv("API_TOKEN");
    //Get Settinges of Server Email
    $sql = "";
    $sql .= "SELECT * ";
    $sql .= "FROM settings S ";
    $sql .= "WHERE S.key like '%EMAIL_CONNECTOR_MAIL_%' ";
    $responseQuery = callApiUrlGuzzle($apiUrl, 'POST', encodeSql($sql));
    $aEmailsConnector = [];
    foreach ($responseQuery as $value) {
        $aEmailsConnector[$value["key"]] = $value["config"];
    }
    $emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
    $smtpServerUrl = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"] : getenv('NORTHLEAF_SMTP_ADDRESS');
    $smtpUser = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"] : getenv('NORTHLEAF_SMTP_USER');
    $smtpPassword = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"] : getenv('NORTHLEAF_SMTP_PASSWORD');
    $northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
    $environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
    $serverEnvironment = getenv('OFF_SERVER_ENVIRONMENT');
    $requestId = $data['_request']['id'];
    $parentRequestId = $data['_parent']['request_id'] ?? $data['_request']['id'];
    $caseNumber = (int) $data['_request']['case_number'];
    $currentDate = date('Y-m-d');

    $detailResponse = [];
    //Get Collections IDs
    $collectionName = "OFF_BODY_NOTIFICATION";
    $collectionInfo = getCollectionId($collectionName, $apiUrl);

        // If email type is diferent to empty
        $filterEmailType = ($emailType == '') ? "AND (data->>'$.EMAIL_TYPE' = 'null' OR data->>'$.EMAIL_TYPE' = '') " : " AND data->>'$.EMAIL_TYPE' = '" . $emailType . "' ";
        // Get Configuration for this task
        $queryConfiguration = "SELECT data->>'$.NODE_ID' AS NODE_ID,
                                    data->>'$.EMAIL_FROM' AS EMAIL_FROM,
                                    data->>'$.EMAIL_FROM_NAME' AS EMAIL_FROM_NAME,
                                    data->>'$.EMAIL_TYPE' AS EMAIL_TYPE,
                                    data->>'$.SUBJECT' AS SUBJECT,
                                    data->>'$.BODY' AS BODY,
                                    data->>'$.TO_SEND_TYPE' AS TO_SEND_TYPE,
                                    data->>'$.EMAIL_LIST_USER_VARIABLE' AS EMAIL_LIST_USER_VARIABLE,
                                    data->>'$.EMAIL_LIST_GROUP_ID' AS EMAIL_LIST_GROUP_ID,
                                    data->>'$.EMAIL_LIST_EMAIL' AS EMAIL_LIST_EMAIL,
                                    data->>'$.ATTACHMENT' AS ATTACHMENT,
                                    data->>'$.ATTACH_FILES' AS ATTACH_FILES
                                FROM collection_" . $collectionInfo . "
                                WHERE data->>'$.NODE_ID' = '" . $task . "'
                                    " . $filterEmailType . "
                                    AND data->>'$.STATUS' = 'ACTIVE'
                                LIMIT 1";
        $configurationInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryConfiguration));
        if (empty($configurationInfo["error_message"]) && $configurationInfo[0]['NODE_ID'] == $task) {
            $TO_SEND_TYPE = json_decode($configurationInfo[0]['TO_SEND_TYPE'], true);
            $receiverInfoResponseUserVariable = $receiverInfoResponseGroupId = $receiverInfoResponseEmail = $receiverInfoResponseEveryone = [];
            //------=========== Get All User ==========------
            $queryUsers = "SELECT CONCAT(U.firstname, ' ', U.lastname) AS RECEIVER_FULL_NAME, U.email AS RECEIVER_EMAIL, U.id AS RECEIVER_ID 
                            FROM users AS U
                            WHERE U.status = 'ACTIVE'
                                  AND U.deleted_at IS NULL";
            $usersInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUsers));
            if (!empty($receiverInfoResponse["error_message"])) {
                return [
                    'status' => false,
                    'message' => 'A problem occurred while getting the list of users.'
                ];
            }
            $aUsers = [];
            foreach ($usersInfo as $user) {
                $aUsers[$user['RECEIVER_ID']] = [
                    'RECEIVER_FULL_NAME' => $user['RECEIVER_FULL_NAME'],
                    'RECEIVER_EMAIL' => $user['RECEIVER_EMAIL']
                ];
            }
            //-------------------- Get User List by Variable User --------------------
            if (in_array("USER_VARIABLE", $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_USER_VARIABLE']);
                $variableUsers = [];
                foreach ($emailList as $userEmail) {
                    //Get Information of USER TO SEND EMAIL
                    if ($data[$userEmail->TO_SEND] && $aUsers[$data[$userEmail->TO_SEND]]) {
                        $receiverInfoResponseUserVariable[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $data[$userEmail->TO_SEND]
                        ];
                    }
                }
            }
            //-------------------- Get User List by Group Name --------------------
            if (in_array("GROUP_ID", $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_GROUP_ID']);
                $variableGroups = [];
                foreach ($emailList as $groupEmail) {
                    $variableGroups[] = $groupEmail->TO_SEND;
                }
                $getReceiverInformation = "SELECT GM.member_id
                                            FROM `groups` AS G
                                            INNER JOIN group_members AS GM ON GM.group_id = G.id
                                            WHERE G.name IN ('" . implode("','", $variableGroups) . "')
                                                AND G.status = 'ACTIVE'
                                            GROUP BY GM.member_id";
                $responseInfoResponseGroupId = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getReceiverInformation));
                foreach ($responseInfoResponseGroupId as $member) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$member['member_id']]) {
                        $receiverInfoResponseGroupId[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$member['member_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$member['member_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $member['member_id']
                        ];
                    }
                }
            }
            //-------------------- Get Email List by Email Type --------------------
            if (in_array("EMAIL", $TO_SEND_TYPE)) {
                $emailList = json_decode($configurationInfo[0]['EMAIL_LIST_EMAIL']);
                $receiverInfoResponseEmail = [];
                foreach ($emailList as $groupEmail) {
                    $name = explode('@', $groupEmail->TO_SEND);
                    $receiverInfoResponseEmail[] = [
                        "RECEIVER_FULL_NAME" => $name[0],
                        "RECEIVER_EMAIL" => $groupEmail->TO_SEND,
                        "RECEIVER_ID" => $groupEmail->TO_SEND
                    ];
                }
            }
            //-------------------- Get Everyone involved  --------------------
            if (in_array("EVERYONE", $TO_SEND_TYPE)) {
                $queryEveryone = "SELECT status, PRT.process_id, PRT.process_request_id as request_id, PRT.user_id, PRT.element_id AS node, PRT.element_name AS task, PRT.element_type
                                FROM process_request_tokens AS PRT
                                WHERE PRT.element_type='task' AND 
                                    (PRT.process_request_id='" . $parentRequestId . "' OR PRT.data->>'$._parent.request_id' = '" . $parentRequestId . "')";
                $responseEveryone = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryEveryone));
                foreach ($responseEveryone as $user) {
                    //Get Information of USER TO SEND EMAIL
                    if ($aUsers[$user['user_id']]) {
                        $receiverInfoResponseEveryone[] = [
                            'RECEIVER_FULL_NAME' => $aUsers[$user['user_id']]['RECEIVER_FULL_NAME'],
                            'RECEIVER_EMAIL' => $aUsers[$user['user_id']]['RECEIVER_EMAIL'],
                            'RECEIVER_ID' => $user['user_id']
                        ];
                    }
                }
            }
            // Merge Email List
            $receiverInfoResponse = array_unique(array_merge($receiverInfoResponseUserVariable, $receiverInfoResponseGroupId, $receiverInfoResponseEmail, $receiverInfoResponseEveryone), SORT_REGULAR);
            if (empty($receiverInfoResponse["error_message"]) && count($receiverInfoResponse) > 0) {
                //Initialize Email Variables
                $emailFrom = $configurationInfo[0]["EMAIL_FROM"];
                $emailFromName = $configurationInfo[0]["EMAIL_FROM_NAME"];
                $emailParameters = new EmailDinamicParameters($configurationInfo[0]["SUBJECT"], $configurationInfo[0]["BODY"]);
                //Add PDF Generated
                $documentsList = $configurationInfo[0]["ATTACHMENT"] == 'true' ? json_decode($configurationInfo[0]["ATTACH_FILES"]) : [];
                $idFiles = [];
                foreach ($documentsList as $document) {
                    if ($data[$document->FILE_ID]) {
                        $idFiles[] = $data[$document->FILE_ID];
                    }
                }
                //Get document's names
                $getDocumentsInformation = "SELECT id AS FILE_ID,
                                                file_name AS FILE_NAME
                                            FROM media
                                            WHERE id IN ('" . implode("','", $idFiles) . "')";
                $documentsInformationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getDocumentsInformation));
                $documentsToAttach = [];
                if (empty($documentsInformationResponse["error_message"])) {
                    foreach ($documentsInformationResponse as $document) {
                        $documentsToAttach[] = [
                            "DOCUMENT_ID" => $document["FILE_ID"],
                            "DOCUMENT_NAME" => $document["FILE_NAME"]
                        ];
                    }
                }
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
                $attachFiles = false;
                //Review if is necesary attach files
                if (count($documentsToAttach) > 0) {
                    //Attach documents
                    $apiInstance = $api->requestFiles();
                    foreach ($documentsToAttach as $document) {
                        // Attach PDF Generate
                        $file = $apiInstance->getRequestFilesById($requestId, $document["DOCUMENT_ID"]);
                        $documentPath = $file->getPathname();
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($document["DOCUMENT_NAME"]));
                        $attachFiles = true;
                    }
                }
                // Send to Receivers
                foreach ($receiverInfoResponse as $receiver) {
                    $data['USER_NAME'] = $receiver["RECEIVER_FULL_NAME"];
                    //Set Subject
                    $emailSubject = $emailParameters->getSubject();
                    //Replace variables in subject
                    $emailSubject = replaceVariables($emailSubject, $data);
                    //Set email subject
                    $message->setSubject($emailSubject);
                    //Set Body
                    $emailBody = $emailParameters->getBody();
                    //Replace variables in body
                    $emailBody = replaceVariables($emailBody, $data);
                    //Add logo
                    $emailBody = "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "'  width='200'/><br><br>" . $emailBody;
                    //Set Body
                    $message->setBody($emailBody, 'text/html');
                    //Send Email
                    $message->setTo($receiver["RECEIVER_EMAIL"], $receiver["RECEIVER_FULL_NAME"]);
                    //$message->addCc('ana.castillo+qa@processmaker.com', 'PM Tester Email');
                    // We check if it is a development environment so as not to send emails to the client
                    if ($serverEnvironment == 'DEV' && strpos(strtolower($receiver['RECEIVER_EMAIL']), '@northleaf')) {
                        $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $receiver["RECEIVER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        false,
                                                        'Actual Server Enviroment is DEV'
                                                    );";
                        $detailResponse[] = [
                            'status' => false,
                            'message' => 'Could not send email to ' . $receiver["RECEIVER_EMAIL"] . ' because the Enviroment is DEV'
                        ];
                        //Record on collection
                        
                        $arrayNote = [];
                        $arrayNote['BODY'] = $emailBody;
                        $arrayNote['DATE'] = $currentDate;
                        $arrayNote['SEND'] = false;
                        $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
                        $arrayNote['DETAIL'] = 'Actual Server Enviroment is DEV';
                        $arrayNote['SUBJECT'] = $emailSubject;
                        $arrayNote['TO_SEND'] = $receiver["RECEIVER_EMAIL"];
                        $arrayNote['EMAIL_FROM'] = $emailFrom;
                        $arrayNote['CASE_NUMBER'] = $caseNumber;
                        $arrayNote['NODE_TASK_ID'] = $task;
                        $notificationLogcollectionId = getCollectionId("OFF_NOTIFICATION_LOG", $apiUrl);
                        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
                        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
                        
                        // Skip send email
                        goto saveLog;
                    }
                    // Try send Email
                    if (!$mailer->send($message)) {
                        $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $receiver["RECEIVER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        false,
                                                        '" . $mail->ErrorInfo . "'
                                                    );";
                        $detailResponse[] = [
                            'status' => false,
                            'message' => 'Could not send email to ' . $receiver["RECEIVER_EMAIL"]
                        ];
                        //Record on collection
                        
                        $arrayNote = [];
                        $arrayNote['BODY'] = $emailBody;
                        $arrayNote['DATE'] = $currentDate;
                        $arrayNote['SEND'] = false;
                        $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
                        $arrayNote['DETAIL'] = $mail->ErrorInfo;
                        $arrayNote['SUBJECT'] = $emailSubject;
                        $arrayNote['TO_SEND'] = $receiver["RECEIVER_EMAIL"];
                        $arrayNote['EMAIL_FROM'] = $emailFrom;
                        $arrayNote['CASE_NUMBER'] = $caseNumber;
                        $arrayNote['NODE_TASK_ID'] = $task;
                        $notificationLogcollectionId = getCollectionId("OFF_NOTIFICATION_LOG", $apiUrl);
                        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
                        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
                        
                    } else {
                        $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $receiver["RECEIVER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        true,
                                                        ''
                                                    );";
                        $detailResponse[] = [
                            'status' => true,
                            'message' => 'Email successfully sent to ' . $receiver["RECEIVER_EMAIL"]
                        ];
                        //Record on collection
                        
                        $arrayNote = [];
                        $arrayNote['BODY'] = $emailBody;
                        $arrayNote['DATE'] = $currentDate;
                        $arrayNote['SEND'] = true;
                        $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
                        $arrayNote['DETAIL'] = '';
                        $arrayNote['SUBJECT'] = $emailSubject;
                        $arrayNote['TO_SEND'] = $receiver["RECEIVER_EMAIL"];
                        $arrayNote['EMAIL_FROM'] = $emailFrom;
                        $arrayNote['CASE_NUMBER'] = $caseNumber;
                        $arrayNote['NODE_TASK_ID'] = $task;
                        $notificationLogcollectionId = getCollectionId("OFF_NOTIFICATION_LOG", $apiUrl);
                        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
                        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
                        
                    }
                    saveLog:
                    // Save in Log
                    $responseInsertLog = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryInsert));
                }
                //Clear parameters subject and body
                $emailParameters->clearValues();
            } else {
                $detailResponse = [
                    'status' => false,
                    'message' => 'A problem occurred when obtaining data from the receivers (' . implode(', ', $TO_SEND_TYPE) . ')'
                ];
            }
        } else {
            $detailResponse = [
                'status' => false,
                'message' => 'No active configuration found for this node ' . $task
            ];
        }
    
    return$configurationInfo;;
}