<?php
/******************************** 
 * FORTE CARGO - Generic Functions
 *
 * by Adriana Centellas
 *******************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
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
function getListUploadedFiles($case_number)
{
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
    $collectionId = $collectionInfo[0]["ID"];
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
class EmailDinamicParameters
{
  private $subject;
  private $body;

  public function __construct($subject, $body)
  {
    $this->subject = $subject;
    $this->body = $body;
  }

  public function getSubject()
  {
    return $this->subject;
  }

  public function getBody()
  {
    return $this->body;
  }

  public function clearValues()
  {
    $this->subject = '';
    $this->body = '';
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
function sendNotification($data, $type, $language = '', $api = null)
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
  $emailEdited = $data["CQP_GENERATE_EMAIL"] ?? null;
  $subjectEmailEdited = $data["CQP_EMAIL_GENERATED"]["subject"] ?? null;
  $base64String = $data["CQP_BODY_BASE64"];
  $base64String = trim($base64String);
  $base64String = strip_tags($base64String);
  $base64String = html_entity_decode($base64String, ENT_QUOTES | ENT_HTML5, "UTF-8");
  $base64String = preg_replace("/\s+/", "", $base64String);

  $json = base64_decode($base64String);
  $bodyEmailEdited = json_decode($json, true);

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
  // Resolve receivers from request variables
  // -----------------------------
  if (($collectionInfo['TO_SEND'] ?? '') === 'REQUEST') {
    $emailList = json_decode($collectionInfo['EMAIL_LIST_REQUEST_VARIABLE'] ?? '[]', true);

    if (is_array($emailList)) {
      foreach ($emailList as $userEmail) {
        $toNameKey = $userEmail['TO_NAME'] ?? null;
        $toEmailKey = $userEmail['TO_SEND'] ?? null;

        $receiverName = ($toNameKey !== null) ? ($data[$toNameKey] ?? '') : '';
        $receiverEmail = ($toEmailKey !== null) ? ($data[$toEmailKey] ?? '') : '';

        if (!empty($receiverEmail)) {
          $receiverInfoResponseUserVariable[] = [
            'RECEIVER_FULL_NAME' => $receiverName,
            'RECEIVER_EMAIL' => $receiverEmail
          ];
        }
      }
    }
  }

  // -----------------------------
  // Resolve receivers from fixed emails
  // -----------------------------
  if (($collectionInfo['TO_SEND'] ?? '') === 'EMAIL') {
    $emailList = json_decode($collectionInfo['EMAIL_LIST_EMAIL'] ?? '[]', true);

    if (is_array($emailList)) {
      foreach ($emailList as $userEmail) {
        $receiverName = $userEmail['TO_NAME'] ?? '';
        $receiverEmail = $userEmail['TO_SEND'] ?? '';

        if (!empty($receiverEmail)) {
          $receiverInfoResponseEmail[] = [
            'RECEIVER_FULL_NAME' => $receiverName,
            'RECEIVER_EMAIL' => $receiverEmail
          ];
        }
      }
    }
  }

  // -----------------------------
  // Resolve CC from request data
  // -----------------------------
  if (!empty($collectionInfo['TO_CC'])) {
    if (!empty($data['CQP_CC_LIST']) && is_array($data['CQP_CC_LIST'])) {
      foreach ($data['CQP_CC_LIST'] as $cc) {
        if (!empty($cc['CQP_CC_EMAIL'])) {
          $ccList[] = $cc['CQP_CC_EMAIL'];
        }
      }
    }
  }

  // -----------------------------
  // Resolve fixed CC from collection
  // -----------------------------
  if (!empty($collectionInfo['EMAIL_LIST_EMAIL_CC'])) {
    $fixedCcList = json_decode($collectionInfo['EMAIL_LIST_EMAIL_CC'], true);

    if (is_array($fixedCcList)) {
      foreach ($fixedCcList as $cc) {
        if (!empty($cc['TO_SEND'])) {
          $ccList[] = $cc['TO_SEND'];
        }
      }
    }
  }

  // -----------------------------
  // Resolve body and branding data
  // -----------------------------
  $emailLogo = json_decode($collectionInfo['CQP_EMAIL_LOGO'] ?? '[]', true);
  $logoSign = $emailLogo[0]['CQP_EMAIL_LOGO_UPLOAD']['name'] ?? null;

  $signName = $data['CQP_SIGN_NAME'] ?? '';
  $signPosition = $data['CQP_SIGN_POSITION'] ?? '';
  $signPhone = $data['CQP_SIGN_PHONE_NUMBER'] ?? '';
  $signEmail = $data['CQP_SIGN_EMAIL'] ?? '';

  // -----------------------------
  // Merge and deduplicate receivers
  // -----------------------------
  $receiverInfoResponse = array_unique(
    array_merge($receiverInfoResponseUserVariable, $receiverInfoResponseEmail),
    SORT_REGULAR
  );

  if (empty($receiverInfoResponse)) {
    return [
      'status' => false,
      'message' => "No hay destinatarios definidos para el idioma '{$language}'."
    ];
  }

  // -----------------------------
  // Prepare subject and body
  // -----------------------------

  if ($emailEdited === "review_email") {

    $subjectEmail = $subjectEmailEdited;
    $bodyEmail = $bodyEmailEdited;

    $emailParameters = new EmailDinamicParameters(
      $subjectEmail,
      $bodyEmail
    );
    
    $emailSubject = $emailParameters->getSubject();
    $emailBody = $emailParameters->getBody();

  } else {

    $bodyEmail = $collectionInfo['CQP_EMAIL_MESSAGE'] ?? '';

    // Resolve subject
    if (($collectionInfo['SUBJECT_CONFIG'] ?? '') === 'REQUEST') {
      $subjectVar = $collectionInfo['SUBJECT_VARIABLE'] ?? '';
      $subjectEmail = ($subjectVar !== '') ? ($data[$subjectVar] ?? '') : '';
    } elseif (($collectionInfo['SUBJECT_CONFIG'] ?? '') === 'COLLECTION') {
      $subjectEmail = $collectionInfo['SUBJECT'] ?? '';
    }

    if ($subjectEmail === '') {
      $subjectEmail = $collectionInfo['SUBJECT'] ?? $type;
    }

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

  }

  $data['EMAIL_SUBJECT'] = $emailSubject;
  $data['EMAIL_BODY'] = $emailBody;
  $data['CQP_EMAIL_MESSAGE'] = $emailBody;

  // -----------------------------
  // Resolve attachments for plugins/email/send
  // -----------------------------
  if (($collectionInfo['SIMPLE'] ?? '') === 'true' && ($collectionInfo['ATTACHMENT'] ?? '') === 'true') {
    $attachFiles = json_decode($collectionInfo['ATTACH_FILES'] ?? '[]', true);

    if (is_array($attachFiles)) {
      foreach ($attachFiles as $attachmentConfig) {
        $fileIdVariable = $attachmentConfig['FILE_ID'] ?? '';

        if ($fileIdVariable !== '') {
          $attachments[] = [
            'source' => 'variable',
            'variableInArray' => null,
            'value' => $fileIdVariable
          ];
        }
      }
    }
  }

  if (($collectionInfo['ARRAY_CUSTOM'] ?? '') === 'true') {
    $arrayNameCustom = $collectionInfo['ARRAY_NAME_CUSTOM'] ?? '';
    $fileNameCustom = $collectionInfo['FILE_NAME_CUSTOM'] ?? '';

    if ($arrayNameCustom !== '' && $fileNameCustom !== '') {
      $attachments[] = [
        'source' => 'array',
        'variableInArray' => $fileNameCustom,
        'value' => $arrayNameCustom
      ];
    }
  }

  // Deduplicate attachment definitions
  if (!empty($attachments)) {
    $uniqueAttachments = [];
    foreach ($attachments as $attachment) {
      $attachmentKey = ($attachment['source'] ?? '') . '|' .
        ($attachment['variableInArray'] ?? 'null') . '|' .
        ($attachment['value'] ?? '');

      $uniqueAttachments[$attachmentKey] = $attachment;
    }
    $attachments = array_values($uniqueAttachments);
  }
  // -----------------------------
  // Build final TO list with DEV restrictions
  // -----------------------------
  $toRecipients = [];

  foreach ($receiverInfoResponse as $receiver) {
    if (empty($receiver['RECEIVER_EMAIL'])) {
      continue;
    }

    $receiverEmail = $receiver['RECEIVER_EMAIL'];

    $isDev = ($serverEnvironment === 'DEV');
    $isAllowedDomain =
      (strpos($receiverEmail, 'processmaker.com') !== false) ||
      (strpos($receiverEmail, 'decisions.com') !== false);

    // Skip non-allowed external emails in DEV
    if ($isDev && !$isAllowedDomain) {
      continue;
    }

    $toRecipients[] = $receiverEmail;
  }

  $toRecipients = array_values(array_unique($toRecipients));
  $ccList = array_values(array_unique(array_filter($ccList)));
  $bccList = array_values(array_unique(array_filter($bccList)));

  if (empty($toRecipients)) {
    return [
      'status' => false,
      'message' => 'There are no valid TO recipients after environment validation.'
    ];
  }

  // -----------------------------
  // Validate screen configuration
  // -----------------------------
  $screenRef = getenv("CQP_SCREEN_REF_FOR_EMAILS");
  $emailServer = getenv("CQP_EMAIL_SERVER");

  if (empty($screenRef)) {
    return [
      'status' => false,
      'message' => 'SCREEN_REF is required in email configuration to use plugins/email/send.'
    ];
  }

  // -----------------------------
  // Format recipients inline
  // -----------------------------
  $formattedToRecipients = [];
  foreach ($toRecipients as $email) {
    if (!empty($email)) {
      $formattedToRecipients[] = [
        'type' => 'email',
        'value' => $email
      ];
    }
  }

  $formattedCcRecipients = [];
  foreach ($ccList as $email) {
    if (!empty($email)) {
      $formattedCcRecipients[] = [
        'type' => 'email',
        'value' => $email
      ];
    }
  }

  $formattedBccRecipients = [];
  foreach ($bccList as $email) {
    if (!empty($email)) {
      $formattedBccRecipients[] = [
        'type' => 'email',
        'value' => $email
      ];
    }
  }

  if (empty($formattedToRecipients)) {
    return [
      'status' => false,
      'message' => 'At least one TO recipient is required.'
    ];
  }

  // -----------------------------
  // Send email using ProcessMaker Email Plugin API
  // -----------------------------
  $sendResult = [
    'isSuccess' => false,
    'statusCode' => null,
    'response' => null,
    'message' => 'Email was not sent.'
  ];

  try {
    $client = new Client([
      'base_uri' => rtrim(getenv('HOST_URL'), '/') . '/',
      'headers' => [
        'Authorization' => 'Bearer ' . $apiToken,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
      ],
      'verify' => false
    ]);

    $body = [
      'subject' => $emailSubject,
      'emailServer' => (string) $emailServer,
      'type' => 'screen',
      'screenRef' => (string) $screenRef,
      'toRecipients' => $formattedToRecipients,
      'ccRecipients' => $formattedCcRecipients,
      'bccRecipients' => $formattedBccRecipients,
      'json_data' => json_encode($data),
      'attachments' => $attachments
    ];

    $response = $client->post('plugins/email/send', [
      'json' => $body
    ]);

    $responseBody = (string) $response->getBody();
    $decodedResponse = json_decode($responseBody, true);

    $sendResult = [
      'isSuccess' => true,
      'statusCode' => $response->getStatusCode(),
      'response' => $decodedResponse !== null ? $decodedResponse : $responseBody,
      'message' => 'Notification process completed.'
    ];
  } catch (BadResponseException $e) {
    $errorResponse = $e->getResponse();
    $errorBody = $errorResponse ? (string) $errorResponse->getBody() : null;

    $sendResult = [
      'isSuccess' => false,
      'statusCode' => $errorResponse ? $errorResponse->getStatusCode() : null,
      'response' => $errorBody,
      'message' => $e->getMessage()
    ];
  } catch (RequestException $e) {
    $sendResult = [
      'isSuccess' => false,
      'statusCode' => null,
      'response' => null,
      'message' => $e->getMessage()
    ];
  } catch (\Exception $e) {
    $sendResult = [
      'isSuccess' => false,
      'statusCode' => null,
      'response' => null,
      'message' => $e->getMessage()
    ];
  }

  // -----------------------------
  // Save historical log
  // -----------------------------
  $notificationLogcollectionId = getCollectionId(
    'CQP_FORTE_CARGO_EMAIL_NOTIFICATION_HISTORICAL',
    $apiUrl
  );

  if (!empty($notificationLogcollectionId)) {
    foreach ($toRecipients as $receiverEmail) {
      $arrayNote = [];
      $arrayNote['CQP_EMAIL_MESSAGE'] = $emailBody;
      $arrayNote['DATE'] = $currentDate;
      $arrayNote['SEND'] = $sendResult['isSuccess'] ?? false;
      $arrayNote['CQP_QUOT_SUBJECT'] = $emailSubject;
      $arrayNote['CQP_TO'] = $receiverEmail;
      $arrayNote['CQP_EMAIL_NAME'] = $collectionInfo['CQP_EMAIL_NAME'] ?? '';
      $arrayNote['CQP_EMAIL_FROM'] = $collectionInfo['CQP_EMAIL_FROM'] ?? '';
      $arrayNote['CQP_EMAIL_LOGO_UPLOAD'] = $logoSign;
      $arrayNote['CQP_EMAIL_TYPE'] = $type;
      $arrayNote['CQP_EMAIL_LANGUAGE'] = $language;
      $arrayNote['CQP_SIGN_NAME'] = $signName;
      $arrayNote['CQP_SIGN_POSITION'] = $signPosition;
      $arrayNote['CQP_SIGN_PHONE_NUMBER'] = $signPhone;
      $arrayNote['CQP_SIGN_EMAIL'] = $signEmail;

      $url = $apiHost . '/collections/' . $notificationLogcollectionId . '/records';
      callApiUrlGuzzle($url, 'POST', ['data' => $arrayNote]);
    }
  }

  // -----------------------------
  // Clear email parameters
  // -----------------------------
  $emailParameters->clearValues();

  return [
    'status' => (bool) ($sendResult['isSuccess'] ?? false),
    'message' => $sendResult['message'] ?? 'Notification process completed.',
    'detail' => $sendResult
  ];
}