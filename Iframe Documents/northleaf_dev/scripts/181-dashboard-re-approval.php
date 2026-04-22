<?php 
/************************
 *  Reapproval Case
 *  by Telmo Chiri
 * modified by Favio Mollinedo
 ************************/
require_once("/Northleaf_PHP_Library.php");
//Import Swift_SmtpTransport classes into the global namespace
require_once 'vendor/autoload.php';

//Validation
if (!$data['caseNumber']) {
    return [
        "status" => false,
        "message" => 'Case not selected.'
    ];
}

// Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
$smtpServerUrl = getenv('NORTHLEAF_SMTP_ADDRESS');
$smtpUser = getenv('NORTHLEAF_SMTP_USER');
$smtpPassword = getenv('NORTHLEAF_SMTP_PASSWORD');
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');

//Read Data
$caseNumber = $data['caseNumber'];
$userId = $data['currentUserId'];
$reason = $data['reason'];
$icReApproval = $data['icReApproval'];
$dataToSave = $data['dataToSave'];
//Define node to jump
$nodeToReturn = 'DT02';

/*
// Collection Data
$collectionNL = "PE_NOTIFICATION_LOG";
$collectionNLID = getCollectionId($collectionNL, $apiUrl);

// Get all Emails

$allEmailInRequests = getNorthUserEmailsData($collectionNLID, $apiUrl, $caseNumber);
$emailsArray = array_column($allEmailInRequests, "EMAIL_TO_SEND");
*/

$listIdUsers = getParticipatedUsers($apiUrl, $caseNumber);
//$emailsArray = getEmailsUsers($apiUrl, $listIdUsers);
// $emailsArray[] = 'brayan@processmaker.com';

// Delete repeated emails
//$uniqueEmails = array_values(array_unique($emailsArray));

$currentProcess = getActiveProcessesData($apiUrl, $caseNumber)[0]["process_id"];

//Check if current case is empty or different than the private equity process id
if (empty($currentProcess) || $currentProcess != 16) {
    $currentProcess = 16;
}

//Get notification configuration for Re Approval
$getEmailConfiguration = "SELECT data->>'$.EMS_EMAIL_FROM' AS EMS_EMAIL_FROM,
                                 data->>'$.EMS_EMAIL_FROM_NAME' AS EMS_EMAIL_FROM_NAME,
                                 data->>'$.EMS_EMAIL_SUBJECT' AS EMS_EMAIL_SUBJECT,
                                 data->>'$.EMS_EMAIL_BODY' AS EMS_EMAIL_BODY  
                          FROM collection_" . $emailSettingsCollectionID . "
                          WHERE data->>'$.EMS_PROCESS_ID' = " . $currentProcess . "
                              AND data->>'$.EMS_EMAIL_TYPE' = 'IC_RE_APPROVAL'
                              AND data->>'$.EMS_EMAIL_STATUS' = 'Active'";
$emailConfigurationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getEmailConfiguration));

if (empty($emailConfigurationResponse["error_message"])) {
    //Get data from the first task.
    $dataPERequestData = getRequestData($apiUrl, $caseNumber);
    $dataPERequestData[0]["RL_REASON"] = $reason;
    // Get SERVER_ENVIRONMENT value
    $serverEnvironment = getenv('SERVER_ENVIRONMENT');
    foreach($listIdUsers as $userData) {
        $userEmail = $userData['email'];
        $fullName  = $userData['fullName'];
        //Initialize Email Variables
        $emailFrom = $emailConfigurationResponse[0]["EMS_EMAIL_FROM"];
        $emailFromName = $emailConfigurationResponse[0]["EMS_EMAIL_FROM_NAME"];
        $emailSubject = $emailConfigurationResponse[0]["EMS_EMAIL_SUBJECT"];
        $emailBody = $emailConfigurationResponse[0]["EMS_EMAIL_BODY"];
        // Search the user data
        //$userInfo = getUserData($userEmail, $apiUrl);
        //return $userInfo;
        $dataPERequestData[0]["PE_USER_NAME"] = $fullName;//$userInfo[0]["USER_FULLNAME"];
        //Create the Transport
        $transport = (new Swift_SmtpTransport($smtpServerUrl, 587, 'tls'))
            ->setUsername($smtpUser)
            ->setPassword($smtpPassword);
        //Create the Mailer using your created Transport
        $mailer = new Swift_Mailer($transport);
        //Create a message
        $message = new Swift_Message();
        //Replace variables in subject
        $emailSubject = replaceVariables($emailSubject, $dataPERequestData[0]);
        //Set email subject
        $message->setSubject($emailSubject);
        //Set email From Address"
        $message->setFrom([$emailFrom => $emailFromName]);
        //Check if body contains logo
        if (strpos($emailBody, '{NORTHLEAF_LOGO}') !== false) {
            $emailBody = str_replace("{NORTHLEAF_LOGO}", "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "'  width='200'/>", $emailBody);
        }
        //Replace variables in body
        $emailBody = replaceVariables($emailBody, $dataPERequestData[0]);
        //Set Body
        $message->setBody($emailBody, 'text/html');
        $message->setTo($userEmail, $fullName);

        $statusSend = [];
        //Record on collection
        $arrayNote = [];
        $arrayNote['BODY'] = $emailBody;
        $arrayNote['DATE'] = date('Y-m-d');
        $arrayNote['ATTACH'] = 'false';
        $arrayNote['SUBJECT'] = $emailSubject;
        $arrayNote['TO_SEND'] = $userEmail;
        $arrayNote['EMAIL_FROM'] = $emailFrom;
        $arrayNote['CASE_NUMBER'] = $caseNumber;
        $arrayNote['NODE_TASK_ID'] = 'REAPPROVAL';
        
         // We check if the environment variable is set to DEV; we do not send emails to @northleaf domains.
        if ($serverEnvironment == 'DEV' && strpos(strtolower($userEmail), '@northleaf')) {
            $arrayNote['SEND'] = false;
            $arrayNote['DETAIL'] = 'Could not send email to ' . $userEmail . ' because the Enviroment is DEV';
        } else {
            if (!$mailer->send($message)) {
                $arrayNote['SEND'] = false;
                $arrayNote['DETAIL'] = $mailer->ErrorInfo;
                $statusSend[] = [
                    "SEND_STATUS" => "ERROR",
                    "SEND_MESSAGE" => "Error..." . $mailer->ErrorInfo
                ];
            } else {
                $arrayNote['SEND'] = true;
                $arrayNote['DETAIL'] = '';
            }
        }
        // Save in Collection Log
        $notificationLogcollectionId = getCollectionId("PE_NOTIFICATION_LOG", $apiUrl);
        $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
        $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
    }
}

return jumpToTask($caseNumber, $userId, $icReApproval, $reason, $dataToSave, $nodeToReturn);

/***
 * Jump to another Task
 * @param int $caseNumber
 * @param int $userId
 * @param string $icReapproval
 * @param string $reason
 * @param string $dataToSave
 * @param string $nodeToReturn
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 ***/
function jumpToTask($caseNumber, $userId, $icReApproval, $reason, $dataToSave, $nodeToReturn) {
    // Global Variables
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $ICapprovalNeeded = $icReApproval;
    $idIC01Process = getenv('PE_IC01_APRROVAL_PROCESS_ID');
    //Get collections IDs
    $collectionNames = array("HISTORY_FILES_BY_CASE", "REAPPROVAL_LOG");
    $collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
    
    //Get collections IDs
    $idCollectionApprovalLog = $collectionsInfo["REAPPROVAL_LOG"] ?? false;
    $idCollectionHistoryFilesByCase = $collectionsInfo["HISTORY_FILES_BY_CASE"] ?? false;

    //Set collection url
    $collectionLogUrl = $apiHost . "/collections/" . $idCollectionApprovalLog . "/records";

    // Review PE_MANDATES
    $peMandates = json_decode(base64_decode($dataToSave));
    foreach($peMandates as &$mandate) {
        if ($mandate->PE_MANDATE_CO_INVESTOR === "YES") {
            $mandate->PE_MANDATE_NAME = $mandate->PE_MANDATE_NAME_SL->LABEL;
        }
    }

    // Get All Active Requests
    $query = "SELECT PR.case_number as case_number, 
                     PR.id, 
                     PR.process_id, 
                     PR.status, 
                     PR.name,
                     (SELECT true AS toActive
                      FROM process_request_tokens AS PRT
                      WHERE PRT.process_request_id = PR.id 
                            AND PRT.element_id = '" . $nodeToReturn . "'
                      GROUP BY PRT.process_request_id) AS to_active
            FROM process_requests AS PR
            WHERE PR.case_number = '".$caseNumber."'
            ORDER BY to_active DESC, process_id DESC";
    $responseRequests = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    if (!empty($responseRequests[0]["id"])) {
        // Loop requests
        foreach ($responseRequests as $request) {
            // Get Request Id
            $requestID = $request['id'];
            $statusRequest = $request["to_active"] ? 'ACTIVE' : 'COMPLETED';

            // Update Data Request
            $urlUpdateData = $apiHost . '/requests/' . $requestID;
            $dataToUpdate['data'] = [
                'PE_REAPPROVAL_CASE' => date('Y-m-d H:i:s'),
                'PE_IC_NECESSARY' => $icReApproval,
                'PE_REAPPROVAL_IC_NEEDED' => $ICapprovalNeeded,
                'PE_MANDATES' => $peMandates,
                'PE_ACTIVE_REAPPROVAL' => 'YES'
            ];
            $resUpdate = callApiUrlGuzzle($urlUpdateData, "PUT", $dataToUpdate);

            if (!($icReApproval == "NO" && $request["process_id"] == $idIC01Process)) {
                // Change Status of Request
                if ($statusRequest == 'COMPLETED') {
                    //Clean PE_PARENT_CASE_NUMBER in Request Data   
                    $sqlUpdateRequest = "UPDATE process_requests AS PR
                                            SET PR.case_number = null,
                                                PR.status = 'ERROR'
                                        WHERE PR.id = " . $requestID . "";
                    $responseUpdateRequest = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdateRequest));
                    // Close All Tasks and Gateways
                    $sqlUpdate = "UPDATE process_request_tokens
                                        SET `status` = 'CLOSED'
                                    WHERE process_request_id = " . $requestID . "
                                        AND (
                                                (status = 'ACTIVE' AND element_type = 'event') 
                                                OR 
                                                (status = 'INCOMING' AND element_type = 'gateway')
                                            );";
                    $responseUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUpdate));
                    // API COMPLETE Request
                    $params = [
                        "status" => $statusRequest,
                    ];
                    $responseUpdate = callApiUrlGuzzle($urlUpdateData, "PUT", $params);

                    //Clean Records Files in HISTORY_FILES_BY_CASE Collection
                    if ($idCollectionHistoryFilesByCase) {
                        $sqlCleanHistoryLog = "UPDATE collection_" . $idCollectionHistoryFilesByCase . "
                                                    SET data = JSON_SET(data, '$.HFC_STATUS', 'INACTIVE')
                                                WHERE data->>'$.HFC_CASE_NUMBER' = '" . $caseNumber . "'
                                                        AND data->>'$.HFC_REQUEST_ID' = '" . $requestID . "';";
                        callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlCleanHistoryLog));
                    }
                }
            }
            
            //SAVE IN LOG
            //Set array of values to insert in collection
            $insertValues = [
                'data' => [
                    "RL_CASE_NUMBER" => $caseNumber,
                    "RL_REQUEST_ID" => $requestID,
                    "RL_USER" => $userId,
                    "RL_CURRENT_DATE" =>date('Y-m-d H:i:s'),
                    "RL_REASON" => $reason,
                    "RL_CHANGE_IC" => $icReApproval,
                    "RL_TASKS_TO_UPDATE" => json_encode($responseTasksToUpdate),
                    "RL_BACKUP_AMOUNTS" => base64_decode($dataToSave)
                ]
            ];
            // Insert new Record in Collection
            callApiUrlGuzzle($collectionLogUrl, "POST", $insertValues);
        } 
        // End Foreach Requests

        // Remove History Data by Case Number
        if($icReApproval == "YES"){
            $sQCollectionsId = "DELETE FROM collection_" . getCollectionId('PE_IC_APPROVER_RESPONSE', $apiUrl) . " AS PEAR
                                WHERE PEAR.data->>'$.IAR_CASE_NUMBER' = '" . $caseNumber . "'";
            $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];
            $sQCollectionsAllocationId = "DELETE FROM collection_" . getCollectionId('PE_ALLOCATION_INFO', $apiUrl) . " AS PEAR
                                WHERE PEAR.data->>'$.CASE_NUMBER' = '" . $caseNumber . "'";
            $collectionRecordsAllocation = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsAllocationId)) ?? [];
        }
    } else {
        return [
            "status" => false,
            "message" => $responseRequests["message"]
        ];    
    }
    return [
        "status" => true
    ];
}

/**
 * Get Notification Emails Response data from a specified collection by its ID.
 *
 * @param (int) $ID - The ID of the collection.
 * @param (string) $apiUrl - The API URL for making the request.
 * @param (int) $caseNumber - The case number.
 * @return array - An array of collection records with 'ID' key, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getNorthUserEmailsData($ID, $apiUrl, $caseNumber)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.TO_SEND' AS EMAIL_TO_SEND
                        FROM collection_" . $ID . " AS PENL
                        WHERE PENL.data->>'$.CASE_NUMBER' = '" . $caseNumber . "'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Get Active Process Response data by its case number.
 *
 * @param (string) $apiUrl - The API URL for making the request.
 * @param (int) $caseNumber - The case number.
 * @return array - An array of collection records with 'ID' key, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getActiveProcessesData($apiUrl, $caseNumber)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT PR.process_id,
                        PR.status,
                        PR.data
                        FROM process_requests AS PR
                        WHERE PR.case_number = '".$caseNumber."' and
                        PR.status = 'ACTIVE'
                        ORDER BY id ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Get User Information
 *
 * @param string $userEmail
 * @return array $userData
 * @param (String) $apiUrl - The URL of the API to call.
 *
 * by Favio Mollinedo
 */
function getUserData($userEmail, $apiUrl)
{
    $userData = array();
    // Get User Information
    $queryUser = "SELECT U.id AS USER_ID, 
                        CONCAT(U.firstname, ' ', U.lastname) AS USER_FULLNAME, 
                        U.email AS USER_EMAIL, 
                        U.status AS USER_STATUS 
                  FROM users AS U 
                  WHERE U.email = '" . $userEmail ."'";
    $queryRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUser)) ?? [];

    return $queryRecords;
}

/**
 * Get Data Request Response data from a specified case number ID.
 *
 * @param (string) $apiUrl - The API URL for making the request.
 * @param (int) $caseNumber - The case number.
 * @return array - An array with request data, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getRequestData($apiUrl, $caseNumber)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT PR.data->>'$.PE_PROJECT_INVESTMENT_NAME' AS PE_PROJECT_INVESTMENT_NAME,
                        PR.data->>'$.PE_DEAL_TEAM_SENIOR_LABEL' AS PE_DEAL_TEAM_SENIOR_LABEL,
                        PR.data->>'$.PE_DEAL_TEAM_JUNIOR_LABEL' AS PE_DEAL_TEAM_JUNIOR_LABEL,
                        PR.data->>'$.format_target_close_date' AS format_target_close_date,
                        PR.data->>'$.PE_BRIEF_DEAL_DESCRIPTION' AS PE_BRIEF_DEAL_DESCRIPTION
                        FROM process_requests AS PR
                        WHERE PR.case_number = '" . $caseNumber . "' and 
                        PR.name = 'Private Equity Deal Closing Process'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

function getParticipatedUsers($apiUrl, $caseNumber = 0, $requestId = 0)
{
    $sql = 'SELECT
                process_request_tokens.process_request_id,
                process_request_tokens.user_id,
                process_request_tokens.element_type,
                process_request_tokens.subprocess_request_id,
				CONCAT(users.firstname, " " ,users.lastname) as fullName,
				users.email
            FROM process_requests
            INNER JOIN process_request_tokens ON (process_requests.id = process_request_tokens.process_request_id)
            INNER JOIN users ON (process_request_tokens.user_id = users.id)
            WHERE users.status = "ACTIVE" 
            AND process_requests.process_id != "26" ';

    if ($caseNumber) {
        $sql .= 'AND process_requests.case_number = ' . $caseNumber;
    } else if ($requestId) {
        $sql .= 'AND process_requests.id = ' . $requestId;
    }
    
    $dataRecord = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql)) ?? [];

    $userList = [];
    foreach($dataRecord as $value) {
        if ($value['user_id'] != '') {
            $userList[$value['user_id']] = $value;
        }

        if ($value['subprocess_request_id'] != '') {
            $tempArray = getParticipatedUsers($apiUrl, 0, $value['subprocess_request_id']);
            foreach($tempArray as $tempData){
                $userList[$tempData['user_id']] = $tempData;
            }
            //$userList = array_merge($userList, $tempArray);
        }
    }

    //$userList = array_unique($userList);
    return $userList;
}


function getEmailsUsers($apiUrl, $dataUsers)
{
    $listUsers = implode("', '", $dataUsers);
    $listUsers = "'" . $listUsers . "'";

    $sql = 'SELECT
                firstname,
                lastname,
                email
            FROM users
            WHERE status = "ACTIVE" AND id IN (' . $listUsers . ')';
    $dataRecord = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql)) ?? [];
    $emailList = [];

    foreach($dataRecord as $value) {
        $emailList[] = $value['email'];
    }

    return $emailList;
}