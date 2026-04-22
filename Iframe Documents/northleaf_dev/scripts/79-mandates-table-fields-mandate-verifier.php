<?php
require_once("/Northleaf_PHP_Library.php");

/**********************************
 * Mandates Table Fields Mandate Verifier
 *
 * by Elmer Orihuela
 * modified by Telmo Chiri
 *********************************/

$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$host = $_SERVER["HOST_URL"];
$sqlUrl = $apiHost . $apiSql;
// Decode PE_MANDATES_NAMES_ENCODED
$decoded_PE_MANDATES_NAMES = json_decode(urldecode($data['PE_MANDATES_NAMES_ENCODED']), true);
// Initialize variables for concatenated message
$invalidMandates = [];
$message = "";
if (empty($decoded_PE_MANDATES_NAMES)) {
    return [
        'valid' => true,
        'message' => ''
    ];
}
// Iterate over the decoded names and process each mandate
foreach ($decoded_PE_MANDATES_NAMES as $peMandate) {
    $peMandateName = $peMandate["NAME"];
    // If mandate name is empty, skip the query
    if (empty($peMandateName)) {
        continue;
    }
    // If mandate is co-investor, skip the query
    $peMandateCoInvestor = $peMandate["COINVESTOR"];
    if ($peMandateCoInvestor == "true") {
        continue;
    }

    $peMandateCollection = !empty($data['PE_MANDATE_IC_APPROVERS']) ? $data['PE_MANDATE_IC_APPROVERS'] : '18';

    // Build the query
    $checkMandateQuery = "SELECT ";
    $checkMandateQuery .= "COUNT(*) AS count ";
    $checkMandateQuery .= "FROM ";
    $checkMandateQuery .= "collection_" . $peMandateCollection . " ";
    $checkMandateQuery .= "WHERE ";
    $checkMandateQuery .= "data->>'$.MIA_MANDATE_NAME' = '" . $peMandateName . "' ";
    $checkMandateQuery .= "AND JSON_LENGTH(data->>'$.MIA_APPROVER_USERS_IDS') > 0;";
    $responseCheckMandateQuery = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($checkMandateQuery))[0];

    // Check the result and set the response variables
    if ($responseCheckMandateQuery['count'] == 0) {
        if (!in_array($peMandateName, $invalidMandates)) {
            // Add the mandate to the array
            $invalidMandates[] = $peMandateName;
        }
    }
}

// Concatenate invalid mandates for the message
if (!empty($invalidMandates)) {
    $message = "Mandates: " . implode(", ", $invalidMandates) . " have not IC Approvers associated, please ask your system administrator.";
}

// Output the result
$response = [
    'valid' => empty($invalidMandates),
    'messageVisibility' => empty($invalidMandates),
    'message' => $message
];
//Validate if this watcher was in DT01
if ($data['PE_MANDATES_TASK'] == "DT01") {
    $response['valid'] = true;
}

return $response;