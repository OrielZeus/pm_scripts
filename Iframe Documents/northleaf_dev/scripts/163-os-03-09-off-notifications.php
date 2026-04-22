<?php
/**********************************
 * OFF - Send email to OFF TASK USERS
 *
 * by Favio Mollinedo
 *********************************/

// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");
//Import Swift_SmtpTransport classes into the global namespace
require_once 'vendor/autoload.php';
/**
 * Get list of active application tasks collection
 *
 * @return array $collectionInfo
 *
 * by Favio Mollinedo
 */
function getOffApplicationUsers()
{
    // Set Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
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
 * Get Users Information
 *
 * @param string $userID
 * @return array $userData
 *
 * by Favio Mollinedo
 */
function getuserData($userID)
{
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $userData = array();
    // Get User Information
    $queryUser = "SELECT U.id AS USER_ID, 
                        CONCAT(U.firstname, ' ', U.lastname) AS USER_FULLNAME, 
                        U.email AS USER_EMAIL, 
                        U.status AS USER_STATUS 
                  FROM users AS U 
                  WHERE U.id = " . $userID;
    $userDataResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUser));
    if (!empty($userDataResponse[0]) && $userDataResponse[0]["USER_STATUS"] == "ACTIVE") {
        $userData = $userDataResponse;
    }
    return $userData;
}

$currentProcess = $data["_request"]["process_id"];
$requestId = $data['_request']['id'];

/***
 * Send Notification for Off Boarding
 * @param array $data
 * @param string $task
 * @param string $emailType
 * @param array $userInfoArray
 * By Adriana Centellas
 * Adapted copy from sendNotification
 ***/
function sendUserNotification($data, $task, $emailType = '', $api, $userInfoArray)
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
    $serverEnvironment = getenv('SERVER_ENVIRONMENT');
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
        //Initialize Email Variables
        $emailFrom = $configurationInfo[0]["EMAIL_FROM"];
        $emailFromName = $configurationInfo[0]["EMAIL_FROM_NAME"];
        $data["OFF_APPLICATION_TASK_NAMES"] = $userInfoArray["OFF_APPLICATION_TASK_NAMES"];
        $emailParameters = new EmailDinamicParameters($configurationInfo[0]["SUBJECT"], $configurationInfo[0]["BODY"]);
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
        $message->setTo($userInfoArray["USER_EMAIL"], $userInfoArray["USER_FULLNAME"]);
       // $message->addCc('ana.castillo+qa@processmaker.com', 'PM Tester Email');
        // We check if it is a development environment so as not to send emails to the client
        if ($serverEnvironment == 'DEV' && strpos($userInfoArray["USER_EMAIL"], "northleafcapital.com")) {
            $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumber . ",
                                                        '" . $task . "',
                                                        '" . $emailFrom . "',
                                                        '" . $userInfoArray["USER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        false,
                                                        'Actual Server Enviroment is DEV'
                                                    );";
            $detailResponse[] = [
                'status' => false,
                'message' => 'Could not send email to ' . $userInfoArray["USER_EMAIL"] . ' because the Enviroment is DEV'
            ];
            //Record on collection
            $arrayNote = [];
            $arrayNote['BODY'] = $emailBody;
            $arrayNote['DATE'] = $currentDate;
            $arrayNote['SEND'] = false;
            $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
            $arrayNote['DETAIL'] = 'Actual Server Enviroment is DEV';
            $arrayNote['SUBJECT'] = $emailSubject;
            $arrayNote['TO_SEND'] = $userInfoArray["USER_EMAIL"];
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
                                                        '" . $userInfoArray["USER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        false,
                                                        '" . $mail->ErrorInfo . "'
                                                    );";
            $detailResponse[] = [
                'status' => false,
                'message' => 'Could not send email to ' . $userInfoArray["USER_EMAIL"]
            ];
            //Record on collection
            $arrayNote = [];
            $arrayNote['BODY'] = $emailBody;
            $arrayNote['DATE'] = $currentDate;
            $arrayNote['SEND'] = false;
            $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
            $arrayNote['DETAIL'] = $mail->ErrorInfo;
            $arrayNote['SUBJECT'] = $emailSubject;
            $arrayNote['TO_SEND'] = $userInfoArray["USER_EMAIL"];
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
                                                        '" . $userInfoArray["USER_EMAIL"] . "',
                                                        '" . $emailSubject . "',
                                                        " . ($attachFiles ? 'true' : 'false') . ",
                                                        true,
                                                        ''
                                                    );";
            $detailResponse[] = [
                'status' => true,
                'message' => 'Email successfully sent to ' . $userInfoArray["USER_EMAIL"]
            ];
            //Record on collection
            $arrayNote = [];
            $arrayNote['BODY'] = $emailBody;
            $arrayNote['DATE'] = $currentDate;
            $arrayNote['SEND'] = true;
            $arrayNote['ATTACH'] = ($attachFiles ? 'true' : 'false');
            $arrayNote['DETAIL'] = '';
            $arrayNote['SUBJECT'] = $emailSubject;
            $arrayNote['TO_SEND'] = $userInfoArray["USER_EMAIL"];
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
        //Clear parameters subject and body
        $emailParameters->clearValues();
    } else {
        $detailResponse = [
            'status' => false,
            'message' => 'No active configuration found for this node ' . $task
        ];
    }

    return $configurationInfo;
    ;
}

return [];

//Get User from collection
$offApplicationTaskUsers = getOffApplicationUsers();

//Send Notifications
$userDataArray = [];
$userOffNotifications = [];
foreach ($offApplicationTaskUsers as $offApplicationTaskUser) {
    $userInfoArray = getuserData($offApplicationTaskUser["OFF_APPLICATION_TASK_USER_ID"])[0];
    $userInfoArray += ["OFF_APPLICATION_TASK_NAMES" => $offApplicationTaskUser["OFF_APPLICATION_TASK_NAMES"]];
    $notificationStatus = "";
    $notificationStatus = sendUserNotification($data, "OS.03-09", $emailType = '', $api, $userInfoArray);
    $userOffNotifications += [$userInfoArray["USER_ID"] => $notificationStatus];
    array_push($userDataArray, $userInfoArray["USER_ID"]);
}
return [
    "userOffNotifications" => $userOffNotifications
];