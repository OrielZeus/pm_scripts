<?php


/*********************
This PHP script is designed to save the processes in Daily Digest Configuration
Developed by Bruno Montecinos Bailey.
*********************/
require_once 'vendor/autoload.php';

// Start PM configuration
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$urlHost = $_SERVER["HOST_URL"];
$apiHost = getenv("API_HOST");
$apiToken = getenv("API_TOKEN");

$userCreatedBy = ($data["_request"]["user_id"]) ? $data["_request"]["user_id"] : "1";

//Daily Difest Collection Table
$collectionConfiguration = "collection_" . getenv("PMB_COLLECTION_ID_DAILY_DIGEST_CONFIGURATION");

$aProcesses = json_decode(base64_decode(html_entity_decode($data['PROCESSES_LIST_SELECTED'])), true) ?? [];
$subjectEmail = ($data["SUBJECT_EMAIL_DAILY_REPORT"]) ? $data["SUBJECT_EMAIL_DAILY_REPORT"] : "";
//return $bodyEmail = ($data["BODY_EMAIL_DAILY_REPORT"]) ? htmlentities($data["BODY_EMAIL_DAILY_REPORT"]) : "";
$bodyEmail = json_decode(base64_decode(html_entity_decode($data['BODY_EMAIL_DAILY_REPORT'])), true) ?? [];
//$bodyEmail = json_encode($bodyEmail);
$allProcessesWatcher = ($data["ALL_PROCESSES"]) ? $data["ALL_PROCESSES"] : false;
$allProcesses = "NO";
if($allProcessesWatcher == "YES" || $allProcessesWatcher == "true"){
    $allProcesses = "YES";
}

$title = ($data["DD_TITLE"]) ? $data["DD_TITLE"] : "";
$headerBackgroundColor = ($data["DD_HEADER_BACKGROUND_COLOR"]) ? $data["DD_HEADER_BACKGROUND_COLOR"] : "";
$headerFontColor = ($data["DD_HEADER_FONT_COLOR"]) ? $data["DD_HEADER_FONT_COLOR"] : "";

$casesTodayWatcher = ($data["DD_CASES_TODAY"]) ? $data["DD_CASES_TODAY"] : false;
$casesToday = "NO";
if($casesTodayWatcher == "YES" || $casesTodayWatcher == "true"){
    $casesToday = "YES";
}

$casesPendingWatcher = ($data["DD_CASES_PENDING"]) ? $data["DD_CASES_PENDING"] : false;
$casesPending = "NO";
if($casesPendingWatcher == "YES" || $casesPendingWatcher == "true"){
    $casesPending = "YES";
}

$casesCompletedWatcher = ($data["DD_CASES_COMPLETED"]) ? $data["DD_CASES_COMPLETED"] : false;
$casesCompleted = "NO";
if($casesCompletedWatcher == "YES" || $casesCompletedWatcher == "true"){
    $casesCompleted = "YES";
}


//return $aProcesses = ($data["PROCESSES_LIST_SELECTED"]) ? $data["PROCESSES_LIST_SELECTED"] : [];

//Get PROCESSES_LIST Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'PROCESSES_LIST' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . json_encode($aProcesses) . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'PROCESSES_LIST' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "PROCESSES_LIST",
        "VALUE" => $aProcesses,
        "DESCRIPTION" => "List of Active Processes"
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get SUBJECT_EMAIL_DAILY_REPORT Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'SUBJECT_EMAIL_DAILY_REPORT' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . $subjectEmail . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'SUBJECT_EMAIL_DAILY_REPORT' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "SUBJECT_EMAIL_DAILY_REPORT",
        "VALUE" => $subjectEmail,
        "DESCRIPTION" => "Daily Report Email Subject"
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get BODY_EMAIL_DAILY_REPORT Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'BODY_EMAIL_DAILY_REPORT' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . str_replace("'", "''", $bodyEmail) . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'BODY_EMAIL_DAILY_REPORT' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "BODY_EMAIL_DAILY_REPORT",
        "VALUE" => str_replace("'", "''", $bodyEmail),
        "DESCRIPTION" => "If you require additional text in the email, it can be set using HTML formatting."
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get ALL_PROCESSES Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'ALL_PROCESSES' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . $allProcesses . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'ALL_PROCESSES' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "ALL_PROCESSES",
        "VALUE" => $allProcesses,
        "DESCRIPTION" => "''true'' if you''d like to include all processes!"
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get DD_TITLE Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'DD_TITLE' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . str_replace("'", "''", $title) . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'DD_TITLE' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "DD_TITLE",
        "VALUE" => str_replace("'", "''", $title),
        "DESCRIPTION" => "Title for the Header."
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get DD_HEADER_BACKGROUND_COLOR Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'DD_HEADER_BACKGROUND_COLOR' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . str_replace("'", "''", $headerBackgroundColor) . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'DD_HEADER_BACKGROUND_COLOR' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "DD_HEADER_BACKGROUND_COLOR",
        "VALUE" => str_replace("'", "''", $headerBackgroundColor),
        "DESCRIPTION" => "Hexadecimal Color Code for the Header Background."
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get DD_HEADER_FONT_COLOR Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'DD_HEADER_FONT_COLOR' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . str_replace("'", "''", $headerFontColor) . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'DD_HEADER_FONT_COLOR' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "DD_HEADER_FONT_COLOR",
        "VALUE" => str_replace("'", "''", $headerFontColor),
        "DESCRIPTION" => "Hexadecimal Color Code for the Header Font."
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get CASES_TODAY Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'CASES_TODAY' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . $casesToday . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'CASES_TODAY' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "CASES_TODAY",
        "VALUE" => $casesToday,
        "DESCRIPTION" => "''true'' if you''d like to include Today Cases Pending"
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get CASES_PENDING Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'CASES_PENDING' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . $casesPending . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'CASES_PENDING' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "CASES_PENDING",
        "VALUE" => $casesPending,
        "DESCRIPTION" => "''true'' if you''d like to include Pending Cases until Yesterday"
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}

//Get CASES_COMPLETED Configurations
$sql = "";
$sql .= " SELECT COUNT(*) AS 'TOTAL' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'CASES_COMPLETED' ";
$responseQuery = executeSQL($sql);

if ($responseQuery[0]["TOTAL"] > 0) {
    // Update
    $sql = "";
    $sql .= " UPDATE " . $collectionConfiguration . " ";
    $sql .= " SET data = JSON_SET(data, '$.VALUE', '" . $casesCompleted . "') ";
    $sql .= " WHERE JSON_EXTRACT(data, '$.ID') = 'CASES_COMPLETED' ";
    executeSQL($sql);
} else {
    // Insert
    $jsonData = json_encode([
        "ID" => "CASES_COMPLETED",
        "VALUE" => $casesCompleted,
        "DESCRIPTION" => "''true'' if you''d like to include Today Completed Cases"
    ]);
    $sql = "";
    $sql .= "INSERT INTO " . $collectionConfiguration . " (data, created_by_id, updated_by_id, created_at, updated_at) ";
    $sql .= "VALUES ('" . $jsonData . "', " . $userCreatedBy . ", " . $userCreatedBy . ", NOW(), NOW())";
    executeSQL($sql);
}


return [];

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