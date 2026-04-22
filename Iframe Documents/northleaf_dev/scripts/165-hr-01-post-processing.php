<?php
/******************************** 
 * HR.01 - Post Processing
 *
 * by Adriana Centellas
 *******************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Define collection name
$collectionName = 'OFF_TASKS_TITLES';
//Get collection ID
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get collection records
$collectionRecords = getCollectionRecords($collectionID, $apiUrl);

//Format JSON Data
$formattedDataTitles = array_map(function ($item) {
    return json_decode($item['data'], true);
}, $collectionRecords);

//Task Code
$taskCodeCF = "CF.01";

//Get collections IDs
$collectionNameLead = "OFF_TASK_LEADER";
$collectionsIDLead = getCollectionId($collectionNameLead, $apiUrl);

//Get Task Lead
$userTaskLead = getTaskLeadGroup($collectionsIDLead, $taskCodeCF, $apiUrl, $data["OFF_OFFICE_LOCATION"]["OFFICE_DESCRIPTION"]);

$dataReturn['CORPORATE_FINANCE'] = $userTaskLead;
$dataReturn["OFF_TITLES_INFO"] = $formattedDataTitles;

//Format date for emails
$dataReturn["OFF_LAST_DAY_EMPLOYMENT_FORMAT"] = convertDate($data["OFF_LAST_DAY_EMPLOYMENT"]);

//Merge with $data
$data = array_merge($data, $dataReturn);

//Send notification
$taskCode = "POST_HR.01";
$emailType = '';
$notificationSent = sendNotificationToEmailAddress($data, $taskCode, $emailType, $api, $data["OFF_MANAGER_EMAIL"]);

$dataReturn['OFF_SENT_DETAILS_POST-HR.01'] = $notificationSent;

//Get collections email IDs
$collectionNameEmail = "OFF_EMAIL_NOTIFICATIONS";
$collectionsIDEmail = getCollectionId($collectionNameEmail, $apiUrl);

//Get the emails
$emailAddresses = getEmailAddresses($collectionsIDEmail, $apiUrl);

//Send operations notification
$taskCodeO = "CC.00";
$emailTypeO = '';

foreach ($emailAddresses as $entry) {
    $notificationSentO .= sendNotificationToEmailAddress($data, $taskCodeO, $emailTypeO, $api, $entry["EMAIL"]);
}

$dataReturn['OFF_SENT_DETAILS_OPERATIONS'] = $notificationSentO;
$dataReturn['OFF_TASKS_COMPLETED'] = null;

return $dataReturn;

/**
 * Sends a notification email to the manager with task-related information.
 *
 * @param array  $data       - The data containing the manager's email and other relevant information.
 * @param string $task       - The current task for which the notification is sent.
 * @param string $emailType  - (Optional) The type of email to filter the notification by.
 * @param string $api        - The API client instance for sending requests.
 * @param string $emailAddress - The email address where this information will be send.
 *
 * @return array $configurationInfo - Contains details about the email configuration for the task.
 *
 * by Adriana Centellas
 */
function sendNotificationToEmailAddress($data, $task, $emailType = '', $api, $emailAddress)
{
    // Get Global Variables
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
    $serverEnvironment = getenv('OFF_SERVER_ENVIRONMENT');
    $requestId = $data['_request']['id'];
    $parentRequestId = $data['_parent']['request_id'] ?? $data['_request']['id'];
    $caseNumber = (int) $data['_request']['case_number'];
    $currentDate = date('Y-m-d');

    $detailResponse = [];

    // Get collection ID for the notification body
    $collectionName = "OFF_BODY_NOTIFICATION";
    $collectionInfo = getCollectionId($collectionName, $apiUrl);

    // Build the email filter based on the email type
    $filterEmailType = ($emailType == '')
        ? "AND (data->>'$.EMAIL_TYPE' = 'null' OR data->>'$.EMAIL_TYPE' = '') "
        : "AND data->>'$.EMAIL_TYPE' = '" . $emailType . "' ";

    // Query for configuration for the specific task
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

        // Set receiver info based on manager's email
        $name = explode('@', $emailAddress);
        $receiverInfoResponseEmail[] = [
            "RECEIVER_FULL_NAME" => $name[0],
            "RECEIVER_EMAIL" => $emailAddress,
            "RECEIVER_ID" => $emailAddress
        ];

        // Merge receiver information
        $receiverInfoResponse = $receiverInfoResponseEmail;

        // Ensure the receiver info is valid
        if (empty($receiverInfoResponse["error_message"]) && count($receiverInfoResponse) > 0) {
            // Initialize email variables
            $emailFrom = $configurationInfo[0]["EMAIL_FROM"];
            $emailFromName = $configurationInfo[0]["EMAIL_FROM_NAME"];
            $emailParameters = new EmailDinamicParameters($configurationInfo[0]["SUBJECT"], $configurationInfo[0]["BODY"]);

            // Attach documents if applicable
            $documentsList = $configurationInfo[0]["ATTACHMENT"] == 'true' ? json_decode($configurationInfo[0]["ATTACH_FILES"]) : [];
            $idFiles = [];
            foreach ($documentsList as $document) {
                if ($data[$document->FILE_ID]) {
                    $idFiles[] = $data[$document->FILE_ID];
                }
            }

            // Query the document's names from the media table
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

            // Create the SMTP transport
            $transport = (new Swift_SmtpTransport($smtpServerUrl, 587, 'tls'))
                ->setUsername($smtpUser)
                ->setPassword($smtpPassword)
                ->setTimeout(0);

            // Create a mailer instance with the transport
            $mailer = new Swift_Mailer($transport);

            // Create a message
            $message = new Swift_Message();
            $message->setFrom([$emailFrom => $emailFromName]);

            // Prepare and send emails to the receivers
            foreach ($receiverInfoResponse as $receiver) {
                // Set subject and body with replaced variables
                $data['USER_NAME'] = $receiver["RECEIVER_FULL_NAME"];
                $emailSubject = replaceVariables($emailParameters->getSubject(), $data);
                $message->setSubject($emailSubject);

                $emailBody = replaceVariables($emailParameters->getBody(), $data);
                $emailBody = "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "' width='200'/><br><br>" . $emailBody;
                $message->setBody($emailBody, 'text/html');

                // Set the recipient and CC email
                $message->setTo($receiver["RECEIVER_EMAIL"], $receiver["RECEIVER_FULL_NAME"]);
               // $message->addCc('ana.castillo+qa@processmaker.com', 'PM Tester Email');
                if ($serverEnvironment == 'DEV' && strpos($receiver["RECEIVER_EMAIL"], "northleafcapital.com")) {
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
    return $configurationInfo;
}

/**
 * Convert a date string into a human-readable format with the day suffix.
 *
 * @param string $date - The date string to convert.
 * @return string - The formatted date with the appropriate day suffix.
 *
 * by Adriana Centellas
 */
function convertDate($date) {
    // Convert the string to a DateTime object
    $date_obj = new DateTime($date);

    // Get the day and determine the correct suffix (st, nd, rd, th)
    $day = $date_obj->format('j');
    $suffix = ($day % 10 == 1 && $day != 11) ? 'st' : 
              (($day % 10 == 2 && $day != 12) ? 'nd' : 
              (($day % 10 == 3 && $day != 13) ? 'rd' : 'th'));

    // Set locale to English for month formatting
    setlocale(LC_TIME, 'en_US.UTF-8');
    // Format month and year in English
    $month = strftime('%B', $date_obj->getTimestamp());
    $year = $date_obj->format('Y');

    // Return the final formatted date with the correct order
    return "$month $day$suffix, $year";
}

/**
 * Fetch email addresses from a collection where the status is "Active".
 *
 * @param int $collectionId The ID of the collection to query.
 * @param string $apiUrl The URL of the API to fetch data.
 * @return array An array of email addresses.
 *
 * by Adriana Centellas
 */
function getEmailAddresses($collectionId, $apiUrl)
{
    // SQL query to select active email addresses from the specified collection
    $sqlUserLeader = "SELECT 
                        TL.data->>'$.email' AS EMAIL
                      FROM collection_" . $collectionId . " AS TL
                      WHERE TL.data->>'$.status' = 'Active'";

    // Call the API to execute the SQL query and fetch the results
    $rQUserLeader = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUserLeader)) ?? [];

    // Return the list of email addresses
    return $rQUserLeader;
}