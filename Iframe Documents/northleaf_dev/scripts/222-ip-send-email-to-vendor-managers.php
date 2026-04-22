<?php
/**********************************
 * IP - Send email to Vendor Managers
 *
 * Cochalo :)
 *********************************/

try {
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;

    $dataProperties = getAllPropertiesNotifications();
    
    //$listEmails = getListManagers();
    $message = str_replace("<--","<", $data['IN_MESSAGE']);
    $message = str_replace("-->",">", $message);
    $message = str_replace(
                    ['"', '|', '\\', '^', '&', '*', '_', '+', '{', '}', '=', '[', ']', '`', '~'],
                    '',
                    $message
                );

    $dataNotification = [
        'IN_REQUESTER' => (empty($data['IN_REQUESTER'])) ? '' : $data['IN_REQUESTER'],
        'IN_REQUESTER_EMAIL' => (empty($data['IN_REQUESTER_EMAIL'])) ? '' : $data['IN_REQUESTER_EMAIL'],
        'IN_MESSAGE' => $message ?? '',
        'IN_DATE_NOTIFICATION' => date('m/d/Y'),
        'REQUEST_ID' => $data['REQUEST_ID'] ?? 0,
    ];
    return sendNotification($dataNotification, 'NODE_NEW_VENDOR', 'NEW_VENDOR', $dataProperties);

} catch (Exception $e) {
    return $e->getMessage();
    //die($e->getMessage());
}

function getAllPropertiesNotifications()
{
    try {
        global $apiUrl;

        if (empty(getenv('COLLECTION_MANAGERS_INVOICE'))) {
            throw new Exception("The environment variable COLLECTION_MANAGERS_INVOICE doesn't exist");
        }

        $idCollectionManagers = getenv('COLLECTION_MANAGERS_INVOICE');

        $queryCollectionManagers = '';
        $queryCollectionManagers .= 'SELECT data->>"$.EMAIL_PEOPLE" AS "JSON_MANAGER", ';
        $queryCollectionManagers .= 'data->>"$.ATTACHMENTS" AS "ATTACHMENTS", ';
        $queryCollectionManagers .= 'data->>"$.CURRENT_USER" AS "CURRENT_USER", ';
        $queryCollectionManagers .= 'data->>"$.ATTACHMENT_VARIABLES" AS "ATTACHMENT_VARIABLES" ';
        $queryCollectionManagers .= 'FROM collection_' . $idCollectionManagers;
        $resposeQueryCollectionManagers = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionManagers));

        if (empty($resposeQueryCollectionManagers['0']['JSON_MANAGER'])) {
            throw new Exception("Error in query for Managers");
        }

        $idManagers = json_decode($resposeQueryCollectionManagers['0']['JSON_MANAGER']);
        $idManagers = implode(',', $idManagers);

        $queryEmailUsers = '';
        $queryEmailUsers .= 'SELECT CONCAT(firstname, " ", lastname) AS RECEIVER_FULL_NAME,';
        $queryEmailUsers .= 'email AS RECEIVER_EMAIL,';
        $queryEmailUsers .= 'id AS RECEIVER_ID ';
        $queryEmailUsers .= 'FROM users WHERE id IN (' . $idManagers . ')';
        $resposeQueryEmailUsers = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryEmailUsers));

        $response = [
            'MANAGERS' => $resposeQueryEmailUsers,
            'FIELD_FILES' => json_decode($resposeQueryCollectionManagers['0']['ATTACHMENT_VARIABLES']),
            'CURRENT_USER' => json_decode($resposeQueryCollectionManagers['0']['CURRENT_USER']),
            'ATTACHMENTS' => json_decode($resposeQueryCollectionManagers['0']['ATTACHMENTS'])
        ];
        return $response;
    } catch (Exception $e) {
        throw $e->getMessage();
    }
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

function sendNotification($data, $task, $emailType = '', $dataProperties)
{
    //Get Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $apiToken = getenv("API_TOKEN");
    $emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
    $smtpServerUrl = getenv('NORTHLEAF_SMTP_ADDRESS');
    $smtpUser = getenv('NORTHLEAF_SMTP_USER');
    $smtpPassword = getenv('NORTHLEAF_SMTP_PASSWORD');
    $northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
    $environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
    $serverEnvironment = getenv('IN_SERVER_ENVIRONMENT');

    $data['IN_REQUESTER'] = ucwords($data['IN_REQUESTER']);
    $data['IN_REQUESTER_EMAIL'] = $data['IN_REQUESTER_EMAIL'];
    $requestId = $data['REQUEST_ID'];

    //$requestId = $data['_request']['id'];
    //$parentRequestId = $data['_parent']['request_id'] ?? $data['_request']['id'];
    //$caseNumber = (int) $data['_request']['case_number'];

    $detailResponse = [];
    //Get Collections IDs
    $collectionInNotification = getenv('COLLECTION_IN_BODY_NOTIFICATION');

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
                            FROM collection_" . $collectionInNotification . "
                            WHERE data->>'$.NODE_ID' = '" . $task . "'
                                ". $filterEmailType . "
                                AND data->>'$.STATUS' = 'ACTIVE'
                            LIMIT 1";
    // return $queryConfiguration;
    $configurationInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryConfiguration));
    //return $configurationInfo ;

    // funciona hasta aqui


    if (empty($configurationInfo["error_message"]) && $configurationInfo[0]['NODE_ID'] == $task) {
        $TO_SEND_TYPE = json_decode($configurationInfo[0]['TO_SEND_TYPE'], true);

        $receiverInfoResponseUserVariable = $receiverInfoResponseGroupId = $receiverInfoResponseEmail = $receiverInfoResponseEveryone = [];
        //------=========== Get All User ==========------
        //-------------------- Get User List by Variable User --------------------

        $receiverInfoResponseUserVariable = $dataProperties['MANAGERS'];
        /*
            foreach($listEmails as $userEmail) {
                //Get Information of USER TO SEND EMAIL
                return $userEmail;    
                $receiverInfoResponseUserVariable[] = [
                    'RECEIVER_FULL_NAME' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_FULL_NAME'],
                    'RECEIVER_EMAIL' => $aUsers[$data[$userEmail->TO_SEND]]['RECEIVER_EMAIL'],
                    'RECEIVER_ID' => $data[$userEmail->TO_SEND]
                ];
            }
        */

        if ($dataProperties['CURRENT_USER'] && !empty($data['IN_REQUESTER_EMAIL'] )) {
            $receiverInfoResponseUserVariable[] = array(
                'RECEIVER_FULL_NAME' => $data['IN_REQUESTER'],
                'RECEIVER_EMAIL' => $data['IN_REQUESTER_EMAIL'],
                'RECEIVER_ID' => 1
            );
            //$message->addCc($data['IN_REQUESTER_EMAIL'], $data['IN_REQUESTER']);
        }

        // Merge Email List
        $receiverInfoResponse = array_unique(array_merge($receiverInfoResponseUserVariable, $receiverInfoResponseGroupId, $receiverInfoResponseEmail, $receiverInfoResponseEveryone), SORT_REGULAR);
        //foreach($receiverInfoResponse as $key => $value){$receiverInfoResponse[$key]['RECEIVER_EMAIL'] = 'jhon.chacolla@processmaker.com';} //for test mode
        if (empty($receiverInfoResponse["error_message"]) && count($receiverInfoResponse)>0) {
            //Initialize Email Variables
            $emailFrom = $configurationInfo[0]["EMAIL_FROM"];
            $emailFromName = $configurationInfo[0]["EMAIL_FROM_NAME"];
            $emailParameters = new EmailDinamicParameters($configurationInfo[0]["SUBJECT"], $configurationInfo[0]["BODY"]);
            //Add PDF Generated
            $documentsList = $configurationInfo[0]["ATTACHMENT"] == 'true' ? json_decode($configurationInfo[0]["ATTACH_FILES"]) : [];
            $documentsToAttach = [];
            $idFiles = [];

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

            //ATTACHMENTS
            //Attach documents
            //if ($dataProperties['ATTACHMENTS'] && $requestId) {
            if (!empty($documentsList) && !empty($requestId)) {
                $fields = array();
                // foreach ($dataProperties['FIELD_FILES'] as $field) {
                //     $field = (array)$field;
                //     if (!empty($field['FILE_NAME'])) {
                //         $fields[] = $field['FILE_NAME'];
                //     }
                // }
                foreach($documentsList as $fileId) { // Using Files from IN_BODY_NOTIFICATION Collection
                    if(!empty($fileId)){
                        $fields[] = $fileId->FILE_ID;
                    }
                }

                global $api;
                $apiInstance = $api->requestFiles();

                $requestFiles    = $apiInstance->getRequestFiles($requestId)->getData();
                $allFilesRequest = json_decode(json_encode($requestFiles), true);

                foreach ($allFilesRequest as $fileData) {
                    if (in_array($fileData['custom_properties']['data_name'], $fields)) {
                        $file = $apiInstance->getRequestFilesById($requestId, $fileData['id']);
                        $documentPath = $file->getPathname();
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($fileData['file_name']));
                        $attachFiles = true;
                    }
                }
            }
            
            // Send to Receivers
            foreach($receiverInfoResponse as $receiver) {
                //$receiver["RECEIVER_EMAIL"] = 'daniel.aguilar@processmaker.com';
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

                //return $emailSubject;
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
                //$message->setBody('<h1>Hola</h1>', 'text/html');
                $message->setBody(($emailBody), 'text/html');
                //Send Email
                $message->setTo($receiver["RECEIVER_EMAIL"], $receiver["RECEIVER_FULL_NAME"]);

                //tlx $message->addCc('ana.castillo+qa@processmaker.com', 'PM Tester Email');
                // We check if it is a development environment so as not to send emails to the client
                /*
                if ($serverEnvironment == 'DEV' && strpos($receiver["RECEIVER_EMAIL"], "northleafcapital.com") ) {
                    $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                            VALUES (" . $caseNumber . ",
                                                    '" . $task . "',
                                                    '" . $emailFrom . "',
                                                    '". $receiver["RECEIVER_EMAIL"] ."',
                                                    '" . $emailSubject . "',
                                                    " . ($attachFiles ? 'true' : 'false') . ",
                                                    false,
                                                    'Actual Server Enviroment is DEV'
                                                );";
                    $detailResponse[] = [
                        'status' => false,
                        'message' => 'Could not send email to ' . $receiver["RECEIVER_EMAIL"] . ' because the Enviroment is DEV'
                    ];
                    // Skip send email
                    goto saveLog;
                }
                */
                // Try send Email
                if (!$mailer->send($message)) {
                    $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                            VALUES (" . $caseNumber . ",
                                                    '" . $task . "',
                                                    '" . $emailFrom . "',
                                                    '". $receiver["RECEIVER_EMAIL"] ."',
                                                    '" . $emailSubject . "',
                                                    " . ($attachFiles ? 'true' : 'false') . ",
                                                    false,
                                                    '" . $mail->ErrorInfo . "'
                                                );";
                    $detailResponse[] = [
                        'status' => false,
                        'message' => 'Could not send email to ' . $receiver["RECEIVER_EMAIL"]
                    ];
                } else {
                    $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG 
                                                (CASE_NUMBER, 
                                                NODE_TASK_ID, 
                                                EMAIL_FROM, 
                                                TO_SEND, 
                                                SUBJECT, 
                                                ATTACH, 
                                                SEND, 
                                                DETAIL)
                                        VALUES (" . $caseNumber . ",
                                                '" . $task . "',
                                                '" . $emailFrom . "',
                                                '". $receiver["RECEIVER_EMAIL"] ."',
                                                '" . $emailSubject . "',
                                                " . ($attachFiles ? 'true' : 'false') . ",
                                                true,
                                                ''
                                            );";
                    $detailResponse[] = [
                        'status' => true,
                        'message' => 'Email successfully sent to ' . $receiver["RECEIVER_EMAIL"]
                    ];
                }
                //saveLog:
                // Save in Log
                //$responseInsertLog = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryInsert));
            }
            //Clear parameters subject and body
            $emailParameters->clearValues();
        } else {
            $detailResponse = [
                'status' => false,
                'message' => 'A problem occurred when obtaining data from the receivers ('. implode(', ', $TO_SEND_TYPE) . ')'
            ];
        }
    } else {
        $detailResponse = [
            'status' => false,
            'message' => 'No active configuration found for this node ' . $task
        ];
    }
    return $detailResponse;
}

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