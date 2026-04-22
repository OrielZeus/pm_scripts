<?php

set_time_limit(0);
/*********************
This PHP script is designed to send daily digest emails to all users.
Developed by Bruno Montecinos Bailey.
 *********************/

require_once 'vendor/autoload.php';

// Start PM configuration
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$startDatetime = date("Y-m-d H:i:s");
$startDatetimeFormatted = date("m/d/Y H:i:s");

$chunkSize      = 100;
$pauseInSeconds = 60;
$countEmails    = 0;

$urlHost = $_SERVER["HOST_URL"];
$apiHost = getenv("API_HOST");
$apiToken = getenv("API_TOKEN");
$imageLogoUrl = $urlHost . '/public-files/processmaker-logo.svg';
//Daily Difest Collection Table
$collectionConfiguration = "collection_" . getenv("PMB_COLLECTION_ID_DAILY_DIGEST_CONFIGURATION");
// modification consult 
$sql = "
    SELECT 
        C.data->>'$.ID' AS 'ID',
        C.data->>'$.VALUE' AS 'VALUE',
        C.data->>'$.DESCRIPTION' AS 'DESCRIPTION'
    FROM {$collectionConfiguration} C
    WHERE C.data->>'$.ID' IN (
        'ALL_PROCESSES', 
        'PROCESSES_LIST', 
        'SUBJECT_EMAIL_DAILY_REPORT', 
        'BODY_EMAIL_DAILY_REPORT',
        'CASES_TODAY',
        'CASES_PENDING',
        'CASES_COMPLETED',
        'DD_TITLE',
        'DD_HEADER_BACKGROUND_COLOR',
        'DD_HEADER_FONT_COLOR'
    );
";
$responseQuery = executeSQL($sql);


$configurations = [];
foreach ($responseQuery as $row) {
    $configurations[$row['ID']] = $row['VALUE'];
}


$allCasesFlag = $configurations['ALL_PROCESSES'] ?? 'NO';
$subjectEmailReport = $configurations['SUBJECT_EMAIL_DAILY_REPORT'] ?? 'Daily Report';
$bodyEmailReport = $configurations['BODY_EMAIL_DAILY_REPORT'] ?? '';
$casesTodayFlag = $configurations['CASES_TODAY'] ?? 'NO';
$casesPendingFlag = $configurations['CASES_PENDING'] ?? 'NO';
$casesCompletedFlag = $configurations['CASES_COMPLETED'] ?? 'NO';
$titleHeader = $configurations['DD_TITLE'] ?? 'Processmaker';
$headerBackgroundColor = validateHexColor($configurations['DD_HEADER_BACKGROUND_COLOR'] ?? '#0872C2', '#0872C2');
$headerFontColor = validateHexColor($configurations['DD_HEADER_FONT_COLOR'] ?? '#FFFFFF', '#FFFFFF');


$sqlAndListProcesses = '';
if ($allCasesFlag === 'NO' && !empty($configurations['PROCESSES_LIST'])) {
    $aListProcesses = json_decode($configurations['PROCESSES_LIST'], true);
    if (is_array($aListProcesses)) {
        $processIdsString = implode(',', $aListProcesses);
        $sqlAndListProcesses = " AND PR.process_id IN ($processIdsString) ";
    }
}

//end



//Get Settinges of Server Email
$sql = "";
$sql .= "SELECT * ";
$sql .= "FROM settings S ";
$sql .= "WHERE S.key like '%EMAIL_CONNECTOR_MAIL_%' ";
$responseQuery = executeSQL($sql);
$aEmailsConnector = [];
foreach ($responseQuery as $value) {
    $aEmailsConnector[$value["key"]] = $value["config"];
}

$emailHost = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_HOST"] : "";
$emailPort = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PORT"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PORT"] : "";
$emailUsername = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_USERNAME"] : "";
$emailPassword = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_PASSWORD"] : "";
$emailFromEmail = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_ADDRESS"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_ADDRESS"] : "";
$emailFromName = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_NAME"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_FROM_NAME"] : "";
//{"options": ["no", "tls", "ssl"]}
$emailEncryptation = ($aEmailsConnector["EMAIL_CONNECTOR_MAIL_ENCRYPTION"]) ? $aEmailsConnector["EMAIL_CONNECTOR_MAIL_ENCRYPTION"] : "";
switch ($emailEncryptation) {
    case '0':
        $emailEncryptation = "no";
        break;
    case '1':
        $emailEncryptation = "tls";
        break;
    case '0':
        $emailEncryptation = "ssl";
        break;
    default:
        $emailEncryptation = "no";
        break;
}
//end email connector modification


try {
    // Configure SMTP server
    // $transport = (new Swift_SmtpTransport('smtp.sendgrid.net', 587, 'tls'))
    $transport = (new Swift_SmtpTransport($emailHost, $emailPort, $emailEncryptation))
        ->setUsername($emailUsername) // Your SMTP username
        ->setPassword($emailPassword); // Your SMTP password

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);
    // Attempt to connect
    $mailer->getTransport()->start();
} catch (Exception $e) {
    // Connection failed
    // Errors control
    $endDatetime = date("Y-m-d H:i:s");
    $endDatetimeFormatted = date("m/d/Y H:i:s");
    return [
        "START_DATATIME" => $startDatetime,
        "START_DATATIME_FORMATTED" => $startDatetimeFormatted,
        "END_DATATIME" => $endDatetime,
        "END_DATATIME_FORMATTED" => $endDatetimeFormatted,
        "EMAILS_COUNT" => 0,
        "SWIFT_ERROR_MESSAGE" => $e->getMessage()
    ];
}

// Fetch all active users
$sql = "
    SELECT id, email, CONCAT(firstname, ' ', lastname) AS name
    FROM users
    WHERE status = 'ACTIVE' 
    LIMIT 1000;";
$aUsers = executeSQL($sql);

// Initialize the list of users to notify
$batch = [];

foreach ($aUsers as $user) {
    $userId    = $user['id'];
    $userName  = $user['name'];
    $userEmail = $user['email'];

    $casesToday     = ($casesTodayFlag     === "YES") ? getCasesToday($userId, $sqlAndListProcesses) : [];
    $casesPending   = ($casesPendingFlag   === "YES") ? getCasesPending($userId, $sqlAndListProcesses) : [];
    $casesCompleted = ($casesCompletedFlag === "YES") ? getCasesCompleted($userId, $sqlAndListProcesses) : [];

    if (!empty($casesToday) || !empty($casesPending) || !empty($casesCompleted)) {
        $batch[] = [
            'name'           => $userName,
            'email'          => $userEmail,
            'casesToday'     => $casesToday,
            'casesPending'   => $casesPending,
            'casesCompleted' => $casesCompleted,
        ];
    }

    
    if (count($batch) === $chunkSize) {
        foreach ($batch as $u) {
            if (SendEmail($u['name'],$u['email'], $u['casesToday'], $u['casesPending'], $u['casesCompleted'])) {
                $countEmails++;
            }
        }
        sleep($pauseInSeconds);
        $batch = []; 
    }
}

// 
if (!empty($batch)) {
    foreach ($batch as $u) {
        if (SendEmail($u['name'], $u['email'], $u['casesToday'], $u['casesPending'], $u['casesCompleted'])) {
            $countEmails++;
        }
    }
}

// Return notification summary
$endDatetime = date("Y-m-d H:i:s");
$endDatetimeFormatted = date("m/d/Y H:i:s");

return [
    "START_DATETIME" => $startDatetime,
    "START_DATETIME_FORMATTED" => $startDatetimeFormatted,
    "END_DATETIME" => $endDatetime,
    "END_DATETIME_FORMATTED" => $endDatetimeFormatted,
    "EMAILS_SENT" => $countEmails,
];
/*
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postdata
 * @param (bool ) $contentFile
 * @return (Array) $res
 *
 * by Elmer Orihuela
 */
function apiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
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
        $res = json_decode($res, true);
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}/*
 * Encode SQL
 *
 * @param (String) $string
 * @return (Array) $variablePut
 *
 * by Elmer Orihuela
 */
function encodeSql($string)
{
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
}

/**
 * Fetch today's cases for a user.
 *
 * @param int $userId
 * @param string $sqlAndListProcesses
 * @return array
 */
function getCasesToday($userId, $sqlAndListProcesses)
{
    global $apiHost;
    $apiSql = '/admin/package-proservice-tools/sql';
    $url = $apiHost . $apiSql;

    // Obtener los grupos del usuario
    $findGroups = "";
    $findGroups .= "SELECT ";
    $findGroups .= "GROUP_CONCAT(DISTINCT group_id ORDER BY group_id ASC) AS group_ids ";
    $findGroups .= "FROM group_members ";
    $findGroups .= "WHERE member_id = " . $userId . " ";
    $findGroups .= "ORDER BY group_id ASC;";
    $getGroupsIds = apiGuzzle($url, "POST", encodeSql($findGroups));
    $userGroups = reset($getGroupsIds)['group_ids'];

    // Construir el SQL para los casos de hoy
    $sql = "";
    $sql .= "SELECT ";
    $sql .= "    PRT.id AS task_id, ";
    $sql .= "    PR.id AS process_request_id, ";
    $sql .= "    PR.name AS name, ";
    $sql .= "    DATE_FORMAT(PRT.updated_at, '%m/%d/%Y') AS date, ";
    $sql .= "    PRT.is_self_service AS unassigned ";
    $sql .= "FROM ";
    $sql .= "    process_request_tokens PRT ";
    $sql .= "    INNER JOIN process_requests PR ON PRT.process_request_id = PR.id ";
    $sql .= "    AND PRT.id = ( ";
    $sql .= "        SELECT ";
    $sql .= "            MAX(T1.id) AS id ";
    $sql .= "        FROM ";
    $sql .= "            process_request_tokens T1 ";
    $sql .= "        WHERE ";
    $sql .= "            T1.process_request_id = PRT.process_request_id ";
    $sql .= "            AND T1.element_type = 'task' ";
    $sql .= "    ) ";
    $sql .= "    LEFT JOIN JSON_TABLE( ";
    $sql .= "        PRT.self_service_groups ->> '$.users', ";
    $sql .= "        '$[*]' COLUMNS (userIds VARCHAR(10) PATH '$') ";
    $sql .= "    ) AS usersTable ON ( ";
    $sql .= "        JSON_LENGTH(PRT.self_service_groups ->> '$.users') > 0 ";
    $sql .= "        AND PRT.is_self_service = 1 ";
    $sql .= "    ) ";
    $sql .= "    LEFT JOIN JSON_TABLE( ";
    $sql .= "        PRT.self_service_groups ->> '$.groups', ";
    $sql .= "        '$[*]' COLUMNS (groupIds VARCHAR(10) PATH '$') ";
    $sql .= "    ) AS groupsTable ON ( ";
    $sql .= "        JSON_LENGTH(PRT.self_service_groups ->> '$.groups') > 0 ";
    $sql .= "        AND PRT.is_self_service = 1 ";
    $sql .= "    ) ";
    $sql .= "WHERE ";
    $sql .= "    PRT.status = 'ACTIVE' ";
    $sql .= "    AND DATE(PRT.updated_at) = CURRENT_DATE() ";
    $sql .= "    AND ( ";
    $sql .= "        PRT.user_id = " . $userId . " ";
    $sql .= "        OR usersTable.userIds = " . $userId . " ";
    $sql .= "        OR groupsTable.groupIds IN (" . $userGroups . ") ";
    $sql .= "    ) ";
    $sql .= $sqlAndListProcesses;
    $sql .= " LIMIT 20;";

    return executeSQL($sql);
}

/**
 * Fetch pending cases for a user.
 *
 * @param int $userId
 * @param string $sqlAndListProcesses
 * @return array
 */
function getCasesPending($userId, $sqlAndListProcesses)
{
    global $apiHost;
    $apiSql = '/admin/package-proservice-tools/sql';
    $url = $apiHost . $apiSql;

    // Get user groups assigned
    $findGroups = "";
    $findGroups .= "SELECT ";
    $findGroups .= "GROUP_CONCAT(DISTINCT group_id ORDER BY group_id ASC) AS group_ids ";
    $findGroups .= "FROM group_members ";
    $findGroups .= "WHERE member_id = " . $userId . " ";
    $findGroups .= "ORDER BY group_id ASC;";
    $getGroupsIds = apiGuzzle($url, "POST", encodeSql($findGroups));
    $userGroups = reset($getGroupsIds)['group_ids'];
    $currentUserInfo = "(PRT.user_id = " . $userId . " OR PRT.usersTable.userIds = " . $userId; 
    if (!empty($userGroups)) {
        $currentUserInfo .= " OR PRT.groupsTable.groupIds IN (" . $userGroups . ")";
    }
    $currentUserInfo .= ")";
    // Build SQL query for pending cases
    $sql = "";
    $sql .= "SELECT ";
    $sql .= "    PR.id AS id, ";
    $sql .= "    CN.id AS case_number, ";
    $sql .= "    PR.name AS name, ";
    $sql .= "    DATE_FORMAT(PR.updated_at, '%m/%d/%Y') AS date, ";
    $sql .= "    PRT.id AS task_id, ";
    $sql .= "    PRT.is_self_service AS unassigned ";
    $sql .= "FROM ";
    $sql .= "    process_request_tokens PRT ";
    $sql .= "    INNER JOIN process_requests PR ON PRT.process_request_id = PR.id ";
    $sql .= "    INNER JOIN case_numbers CN ON CN.process_request_id = PR.id ";
    $sql .= "    AND PRT.id = ( ";
    $sql .= "        SELECT ";
    $sql .= "            MAX(T1.id) AS id ";
    $sql .= "        FROM ";
    $sql .= "            process_request_tokens AS T1 ";
    $sql .= "        WHERE ";
    $sql .= "            T1.process_request_id = PRT.process_request_id ";
    $sql .= "            AND element_type = 'task' ";
    $sql .= "    ) ";
    $sql .= "    LEFT JOIN JSON_TABLE( ";
    $sql .= "        PRT.self_service_groups ->> '$.users', ";
    $sql .= "        '$[*]' COLUMNS (userIds VARCHAR(10) PATH '$') ";
    $sql .= "    ) AS usersTable ON ( ";
    $sql .= "        JSON_LENGTH(PRT.self_service_groups ->> '$.users') > 0 ";
    $sql .= "        AND PRT.is_self_service = 1 ";
    $sql .= "    ) ";
    $sql .= "    LEFT JOIN JSON_TABLE( ";
    $sql .= "        PRT.self_service_groups ->> '$.groups', ";
    $sql .= "        '$[*]' COLUMNS (groupIds VARCHAR(10) PATH '$') ";
    $sql .= "    ) AS groupsTable ON ( ";
    $sql .= "        JSON_LENGTH(PRT.self_service_groups ->> '$.groups') > 0 ";
    $sql .= "        AND PRT.is_self_service = 1 ";
    $sql .= "    ) ";
    $sql .= "WHERE ";
    $sql .= "    PRT.status = 'ACTIVE' ";
    //$sql .= "    AND DATE(PRT.updated_at) <= DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) ";
    $sql .= "    AND DATE(PRT.updated_at) <= CURRENT_DATE() ";
    $sql .= "    AND " . $currentUserInfo . $sqlAndListProcesses ;
    $sql .= " LIMIT 10;";
    return executeSQL($sql);
}

/**
 * Fetch completed cases for a user.
 *
 * @param int $userId
 * @param string $sqlAndListProcesses
 * @return array
 */
function getCasesCompleted($userId, $sqlAndListProcesses)
{
    $sql = "
        SELECT PR.id AS id, MAX(CR.id) AS case_number, PR.name AS name, 
               MAX(DATE_FORMAT(PR.updated_at, '%m/%d/%Y')) AS date
        FROM process_request_tokens PRT
        INNER JOIN process_requests PR ON PR.id = PRT.process_request_id
        LEFT JOIN case_numbers CR ON CR.process_request_id = PR.id
        INNER JOIN processes P ON P.id = PR.process_id
        WHERE PRT.user_id = $userId
          AND P.process_category_id IN (SELECT id FROM process_categories WHERE is_system = 0)
          AND P.status = 'ACTIVE' AND P.is_template = 0 AND P.asset_type IS NULL
          AND PR.status = 'COMPLETED'
          AND DATE_FORMAT(PR.updated_at, '%Y-%m-%d') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m-%d')
          $sqlAndListProcesses
        GROUP BY PR.id, PR.name;
    ";
    return executeSQL($sql);
}

/**
 * Execute Query
 * Function to execute query
 * @param $sql (string)
 * @return array
 * by Bruno Montecinos Bailey
 */
function executeSQL($sql)
{
    global $apiToken, $apiHost, $apiToken;
    $sql = base64_encode($sql);
    $url = '/admin/package-proservice-tools/sql';
    $postfiles = [
        'SQL' => $sql
    ];
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request("POST", $apiHost . $url, $headers, json_encode($postfiles));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        // Captura la excepción y obtén el mensaje de error
        $errorMessage = $exception->getMessage();
        $responseBody = $exception->getResponse() ? $exception->getResponse()->getBody(true) : '';
        $res = json_decode($responseBody, true);
        if (!$res) {
            // Si no se pudo decodificar la respuesta como JSON, devolvemos el mensaje de error original
            $res = ['error' => $errorMessage];
        }
    }
    $res = json_decode($res, true);
    return $res;
}

/**
 * Send Email
 * Function to send Daily Digest email
 * @param $userName (string)
 * @param $userEmail (string)
 * @param $responseCasesToday (array)
 * @param $responseCasesPending (array)
 * @param $responseCasesCompleted (array)
 * @return bool
 * by Bruno Montecinos Bailey
 */
function SendEmail($userName, $userEmail, $responseCasesToday, $responseCasesPending, $responseCasesCompleted)
{
    //Global Varaibles
    global $transport, $imageLogoUrl, $apiToken, $emailFromEmail, $emailFromName, $urlHost, $subjectEmailReport, $bodyEmailReport, $titleHeader, $headerBackgroundColor, $headerFontColor;
    // Create an instance of Swift Mailer with configured SMTP server
    $mailer = new Swift_Mailer($transport);

    $body = '<html>' .
        '<head>' .
        '<title>Daily Report</title>' .
        '<style>' .
        'table {border-collapse: collapse; width: 600px;}' .
        'th, td {border: 1px solid ' . $headerBackgroundColor . '; padding: 8px; text-align: center;}' .
        'th {background-color: ' . $headerBackgroundColor . ';}' .
        'table th:nth-child(1), table td:nth-child(1) { width: 20%; }' .
        'table th:nth-child(2), table td:nth-child(2) { width: 45%; }' .
        'table th:nth-child(3), table td:nth-child(3) { width: 20%; }' .
        'table th:nth-child(4), table td:nth-child(4) { width: 15%; }' .
        '</style>' .
        '</head>' .
        '<body>' .
        '<div style="text-align: left; margin-bottom: 20px; background-color: ' . $headerBackgroundColor . '; padding: 15px; width: 570px;">' .
        /*'<img src="' . $imageLogoUrl . '" alt="Logo" style="max-width: 200px;">' . // Logo image path*/
        '<strong style="color: ' . $headerFontColor . ' !important;"> Dear ' . $userName . '</strong>' .
        '</div>';
    $body .= $bodyEmailReport;

    if (count($responseCasesToday) > 0) {
        $body .= '<h2>Today Submitted Requests</h2>' .
            '<table>' .
            '<tr style="color:' . $headerFontColor . ' !important;"><th style="width: 20%;">Case Number</th><th style="width: 45%;">Process Name</th><th style="width: 20%;">Date</th><th style="width: 15%;"></th></tr>';
        foreach ($responseCasesToday as $value) {
            $label = "View";
            if ($value["unassigned"] == "1") {
                $label = "Claim";
            }
            $body .= '<tr><td>' . $value["case_number"] . '</td><td style="text-align: left;">' . $value["name"] . '</td><td>' . $value["date"] . '</td><td><a href="' . $urlHost . '/tasks/' . $value["task_id"] . '/edit">' . $label . '</a></td></tr>';
        }
        $body .= '</table>';
    }

    if (count($responseCasesPending) > 0) {
        $body .= '<h2>Pending Requests</h2>' .
            '<table>' .
            '<tr style="color:' . $headerFontColor . ' !important;"><th style="width: 20%;">Case Number</th><th style="width: 45%;">Process Name</th><th style="width: 20%;">Date</th><th style="width: 15%;"></th></tr>';
        foreach ($responseCasesPending as $value) {
            $label = "View";
            if ($value["unassigned"] == "1") {
                $label = "Claim";
            }
            $body .= '<tr><td>' . $value["case_number"] . '</td><td style="text-align: left;">' . $value["name"] . '</td><td>' . $value["date"] . '</td><td><a href="' . $urlHost . '/tasks/' . $value["task_id"] . '/edit">' . $label . '</a></td></tr>';
        }
        $body .= '</table>';
    }

    if (count($responseCasesCompleted) > 0) {
        $body .= '<h2>Today Completed Requests</h2>' .
            '<table>' .
            '<tr style="color:' . $headerFontColor . ' !important;"><th style="width: 20%;">Case Number</th><th style="width: 45%;">Process Name</th><th style="width: 20%;">Date</th><th style="width: 15%;"></th></tr>';
        foreach ($responseCasesCompleted as $value) {
            $body .= '<tr><td>' . $value["case_number"] . '</td><td style="text-align: left;">' . $value["name"] . '</td><td>' . $value["date"] . '</td><td><a href="' . $urlHost . '/requests/' . $value["id"] . '">View</a></td></tr>';
        }
        $body .= '</table>';
    }

    $body .= '<div style="text-align: left; margin-top: 20px;">' .
        '<p>Best regards</p>' .
        '</div>' .
        '</body>' .
        '</html>';

    // Create a message
    $message = (new Swift_Message($subjectEmailReport))
        ->setFrom([$emailFromEmail => $emailFromName]) // Your email address and name
        ->setTo([$userEmail => $userName]) // Recipient's email address and name
        ->setBody($body, 'text/html'); // Email body in HTML format

    // Send the email
    $result = $mailer->send($message);

    // Check if the email was sent successfully
    if ($result) {
        return true;
    } else {
        return false;
    }
}

/**
 * Hexadecimal Color Validation
 * Function to validate hexadecimal color
 * @param $color (string)
 * @param $fallback (string)
 * @return string
 * by Bruno Montecinos Bailey
 */
function validateHexColor($color, $fallback)
{
    // Regex to check if the color is a valid hexadecimal color code
    if (preg_match('/^#?([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color)) {
        // If the color does not start with '#', add it
        return (strpos($color, '#') === 0) ? $color : '#' . $color;
    } else {
        return $fallback;
    }
}