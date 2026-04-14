<?php 
/*  
 *  CQP - Watcher - Get Email Body and Subject
 *  By Adriana Centellas
 */


require_once("/CQP_Generic_Functions.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$dataToReplace = [];

$base64String = $data["CQP_SEND_DATA"];
$base64String = trim($base64String);
$base64String = strip_tags($base64String);
$base64String = html_entity_decode($base64String, ENT_QUOTES | ENT_HTML5, "UTF-8");
$base64String = preg_replace("/\s+/", "", $base64String);

$json = base64_decode($base64String);

$dataToReplace = json_decode($json, true);

$dataToReplace["CQP_MARKETS_DETAIL_EMAIL"] = convertDashLinesToHtmlBullets($dataToReplace["CQP_MARKETS_DETAIL_EMAIL"]);

$typeEmail = $config["EMAIL_TYPE"];

$response = getFinalEmailContent($dataToReplace, $typeEmail, $dataToReplace["CQP_LANGUAGE_OPTION"], $api);

return $response;


/*
* Sends a notification email using the ProcessMaker email plugin endpoint.
* This function resolves recipients, subject, body, screen variables and attachments
* from the notification collection configuration.
*
* @param (array) $data //1-10
* @param (string) $type
* @param (string) $language
* @param (mixed) $api
* @return (array) Execution result with status, message and detail
*
* by Adriana Centellas
*/
function getFinalEmailContent ($data, $type, $language = '', $api = null)
{
    // -----------------------------
    // Defensive defaults
    // -----------------------------
    $receiverInfoResponseUserVariable = [];
    $receiverInfoResponseEmail = [];
    $receiverInfoResponse = [];
    $attachments = [];
    $subjectEmail = '';
    $ccList = [];
    $bccList = [];
    $currentDate = date('Y-m-d');

    // -----------------------------
    // Get environment variables
    // -----------------------------
    $apiHost = getenv('API_HOST');
    $apiSql = getenv('API_SQL');
    $apiUrl = $apiHost . $apiSql;
    $apiToken = getenv('API_TOKEN');
    $serverEnvironment = getenv('FORTE_SERVER_ENVIRONMENT');
    $environmentBaseUrl = getenv('FORTE_ENVIRONMENT_BASE_URL');

    // -----------------------------
    // Basic validations
    // -----------------------------
    if (empty($apiHost) || empty($apiSql) || empty($apiToken)) {
        return [
            'status' => false,
            'message' => 'Missing API_HOST, API_SQL or API_TOKEN environment variables.'
        ];
    }

    // -----------------------------
    // Get collection ID
    // -----------------------------
    $emailNotificationCollectionID = getCollectionId('CQP_FORTE_CARGO_EMAIL_NOTIFICATION', $apiUrl);

    if (empty($emailNotificationCollectionID)) {
        return [
            'status' => false,
            'message' => 'No active configuration found for this node ' . $type
        ];
    }

    // -----------------------------
    // Resolve request values
    // -----------------------------
    $requestId = $data['_request']['id'] ?? null;

    // -----------------------------
    // Escape filters
    // -----------------------------
    $escapedType = addslashes($type);
    $escapedLanguage = addslashes($language);

    $filterLanguage = '';
    if ($escapedLanguage !== '') {
        $filterLanguage = " AND data->>'$.CQP_EMAIL_LANGUAGE' = '" . $escapedLanguage . "'";
    }

    // -----------------------------
    // Get notification configuration
    // -----------------------------
    $queryConfiguration = "
        SELECT
            data->>'$.CQP_EMAIL_LANGUAGE'              AS CQP_EMAIL_LANGUAGE,
            data->>'$.CQP_EMAIL_TYPE'                  AS CQP_EMAIL_TYPE,
            data->>'$.CQP_EMAIL_NAME'                  AS CQP_EMAIL_NAME,
            data->>'$.CQP_EMAIL_FROM'                  AS CQP_EMAIL_FROM,
            data->>'$.TO_SEND'                         AS TO_SEND,
            data->>'$.EMAIL_LIST_REQUEST_VARIABLE'     AS EMAIL_LIST_REQUEST_VARIABLE,
            data->'$.EMAIL_LIST_EMAIL'                 AS EMAIL_LIST_EMAIL,
            data->>'$.TO_CC'                           AS TO_CC,
            data->'$.EMAIL_LIST_USER_VARIABLE_CC'      AS EMAIL_LIST_USER_VARIABLE_CC,
            data->>'$.EMAIL_LIST_EMAIL_CC'             AS EMAIL_LIST_EMAIL_CC,
            data->>'$.SUBJECT_CONFIG'                  AS SUBJECT_CONFIG,
            data->>'$.SUBJECT'                         AS SUBJECT,
            data->>'$.SUBJECT_VARIABLE'                AS SUBJECT_VARIABLE,
            data->>'$.CQP_EMAIL_MESSAGE'               AS CQP_EMAIL_MESSAGE,
            data->>'$.IS_SIGN_NEEEDED'                 AS IS_SIGN_NEEEDED,
            data->>'$.CQP_EMAIL_LOGO'                  AS CQP_EMAIL_LOGO,
            data->>'$.ATTACHMENT'                      AS ATTACHMENT,
            data->>'$.SIMPLE'                          AS SIMPLE,
            data->>'$.ATTACH_FILES'                    AS ATTACH_FILES,
            data->>'$.ARRAY_CUSTOM'                    AS ARRAY_CUSTOM,
            data->>'$.ARRAY_NAME_CUSTOM'               AS ARRAY_NAME_CUSTOM,
            data->>'$.FILE_NAME_CUSTOM'                AS FILE_NAME_CUSTOM
        FROM collection_" . $emailNotificationCollectionID . "
        WHERE data->>'$.CQP_EMAIL_TYPE' = '" . $escapedType . "'
            " . $filterLanguage . "
        LIMIT 1
    ";

    $configurationInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryConfiguration));

    if (!empty($configurationInfo["error_message"]) || empty($configurationInfo) || !isset($configurationInfo[0])) {
        return [
            'status' => false,
            'message' => 'No active configuration found for this node ' . $type
        ];
    }

    $collectionInfo = $configurationInfo[0];

    // -----------------------------
    // Resolve subject
    // -----------------------------
    if (($collectionInfo['SUBJECT_CONFIG'] ?? '') === 'REQUEST') {
        $subjectVar = $collectionInfo['SUBJECT_VARIABLE'] ?? '';
        $subjectEmail = ($subjectVar !== '') ? ($data[$subjectVar] ?? '') : '';
    } elseif (($collectionInfo['SUBJECT_CONFIG'] ?? '') === 'COLLECTION') {
        $subjectEmail = $collectionInfo['SUBJECT'] ?? '';
    }

    if ($subjectEmail === '') {
        $subjectEmail = $collectionInfo['SUBJECT'] ?? $type;
    }

    // -----------------------------
    // Resolve body and branding data
    // -----------------------------
    $emailLogo = json_decode($collectionInfo['CQP_EMAIL_LOGO'] ?? '[]', true);
    $logoSign = $emailLogo[0]['CQP_EMAIL_LOGO_UPLOAD']['name'] ?? null;

    $bodyEmail = $collectionInfo['CQP_EMAIL_MESSAGE'] ?? '';

    $signName = $data['CQP_SIGN_NAME'] ?? '';
    $signPosition = $data['CQP_SIGN_POSITION'] ?? '';
    $signPhone = $data['CQP_SIGN_PHONE_NUMBER'] ?? '';
    $signEmail = $data['CQP_SIGN_EMAIL'] ?? '';

    // -----------------------------
    // Prepare subject and body
    // -----------------------------
    $emailParameters = new EmailDinamicParameters(
        $subjectEmail,
        $bodyEmail
    );

    $emailSubject = $emailParameters->getSubject();
    $emailSubject = replaceVariables($emailSubject, $data);

    $emailBody = $emailParameters->getBody();

    // Replace logo placeholder with public image URL
    if (strpos($emailBody, '{CQP_EMAIL_LOGO_UPLOAD}') !== false) {
        $emailBody = str_replace(
            '{CQP_EMAIL_LOGO_UPLOAD}',
            "<img src='" . $environmentBaseUrl . "public-files/logoforte.png' width='200'/>",
            $emailBody
        );
    }

    // Build replacement data for body placeholders
    $data['CQP_EMAIL_LOGO_UPLOAD'] = "<img src='" . $environmentBaseUrl . "public-files/logoforte.png' width='200'/>";
    
    $emailBody = replaceVariables($emailBody, $data);

    $data['EMAIL_SUBJECT'] = $emailSubject;
    $data['EMAIL_BODY'] = $emailBody;
    $data['CQP_EMAIL_MESSAGE'] = $emailBody;

    
    return [
        'subject' => $emailSubject,
        'body' => $emailBody
    ];
}
/*
* Convert dash-prefixed text lines into an HTML bullet list
* styled to match email template typography.
*
* @param (string) $text //1-10
* @return (string) HTML list
*
* by Adriana Centellas
*/
function convertDashLinesToHtmlBullets($text)
{
    $lines = preg_split('/\r\n|\r|\n/', (string)$text);

    $html = '<ul style="margin:0;padding-left:20px;color:#556271;font-family:Arial, sans-serif;">';

    foreach ($lines as $line) {

        $line = trim($line);
        if ($line === '') {
            continue;
        }

        // remove leading dash
        $line = preg_replace('/^\-\s*/', '', $line);

        $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');

        $html .= '<li style="color:#556271;margin-bottom:2px;">'.$line.'</li>';
    }

    $html .= '</ul>';

    return $html;
}