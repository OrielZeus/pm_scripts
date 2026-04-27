<?php
require 'vendor/autoload.php';

/**
 * Encodes SQL payload for PSTOOLS endpoint.
 */
function encodeSql(string $query): array
{
    return ["SQL" => base64_encode($query)];
}

/**
 * Generic ProcessMaker API caller with Guzzle.
 */
function apiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }

    $acceptType = $contentFile ? "application/octet-stream" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        "Authorization" => "Bearer " . $apiToken,
    ];
    if (!$contentFile) {
        $headers["Content-Type"] = "application/json";
    }

    $client = new \GuzzleHttp\Client(['verify' => false]);
    $body = $contentFile ? null : json_encode($postdata);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, $body);

    try {
        $res = $client->send($request);
        $resBody = $res->getBody()->getContents();
        return $contentFile ? $resBody : json_decode($resBody, true);
    } catch (\Throwable $e) {
        return [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . $e->getLine()),
            'error_response' => method_exists($e, 'getResponse') && $e->getResponse()
                ? $e->getResponse()->getBody()->getContents()
                : '',
        ];
    }
}

function sqlSafeUpper($value): string
{
    $safe = strtoupper((string)$value);
    return str_replace("'", "''", $safe);
}

function normalizeQueryRows($response): array
{
    if (!is_array($response)) {
        return [];
    }
    if (isset($response['data']) && is_array($response['data'])) {
        return $response['data'];
    }
    if (isset($response[0]) && is_array($response[0])) {
        return $response;
    }
    return [];
}

$pmHost = rtrim((string)getenv('API_HOST'), '/');
$routingCollectionId = (string)(getenv('RFP_V2_ROUTING_COLLECTION_ID') ?: '124');

$rfpNumber = (string)($data['number'] ?? '');
$campusInput = sqlSafeUpper($data['campusChoiceCode'] ?? '');
$originalDeptCode = sqlSafeUpper($data['requestor_department_code'] ?? '');
$deptCodeInput = sqlSafeUpper($data['deprt_code_selected'] ?? '');
$deptInput = $deptCodeInput !== '' ? $deptCodeInput : $originalDeptCode;
$amountRequested = (float)($data['amountRequested'] ?? 0);
$transactionCurrency = sqlSafeUpper($data['transactionCurrency'] ?? '');
$supplier = sqlSafeUpper($data['payee']['NAME'] ?? '');

$emailErrorMessage = '';
$subjectHeader = '';
$noError = 1;

$currencyField = $transactionCurrency === 'USD' ? 'min_usd_amount' : 'min_amount';
$tableName = "collection_" . preg_replace('/[^0-9]/', '', $routingCollectionId);
if ($tableName === 'collection_') {
    $tableName = 'collection_124';
}

$sqlApprovers = "
SELECT
    data->>'$.approver_level' AS approver_level,
    data->>'$.RUL_USER' AS manager_id,
    data->>'$.{$currencyField}' AS min_amount,
    data->>'$.approver_name' AS approver_name
FROM {$tableName}
WHERE
    UPPER(data->>'$.userDept') = '{$deptInput}'
    AND UPPER(data->>'$.campusChoiceCode') LIKE '%{$campusInput}%'
    AND (
        data->>'$.{$currencyField}' = ''
        OR CAST(REPLACE(data->>'$.{$currencyField}', ',', '') AS DECIMAL(20,2)) <= {$amountRequested}
    )
ORDER BY CAST(data->>'$.approver_level' AS UNSIGNED) ASC
";

$responseApprovers = apiGuzzle(
    $pmHost . '/pstools/script/query-request',
    'POST',
    encodeSql($sqlApprovers)
);

$approvers = normalizeQueryRows($responseApprovers);
$values = [
    'supplier' => $supplier,
    'no_error' => 1,
    'requestor_department_code' => $deptInput,
];

$untilLevelNumber = 0;
$approverIndex = 0;

foreach ($approvers as $row) {
    $level = (int)($row['approver_level'] ?? 0);
    $mgrId = (string)($row['manager_id'] ?? '');
    if ($level < 1 || $mgrId === '') {
        continue;
    }

    $approverIndex++;
    if ($level !== $approverIndex) {
        continue;
    }

    $varId = 'L' . $level . 'managerId';
    $varName = 'L' . $level . 'managerName';
    $varEmail = 'L' . $level . 'managerEmail';

    $values[$varId] = $mgrId;

    $userResp = apiGuzzle($pmHost . '/users/' . $mgrId, 'GET');
    $mgrData = is_array($userResp) && isset($userResp['data']) && is_array($userResp['data'])
        ? $userResp['data']
        : (is_array($userResp) ? $userResp : []);

    $values[$varEmail] = $mgrData['email'] ?? null;
    $values[$varName] = $mgrData['fullname'] ?? null;
    $untilLevelNumber = $level;
}

if ($untilLevelNumber < 1 || empty($values['L1managerId'])) {
    $noError = 0;
    $emailErrorMessage = "No approver was found for the selected campus and department. Please contact support.";
    $subjectHeader = "RFP ERROR: " . $rfpNumber;
}

$values['until_level_number'] = $untilLevelNumber;
$values['level_number'] = 1;
$values['approver_id'] = $values['L1managerId'] ?? null;
$values['approver_name'] = $values['L1managerName'] ?? null;
$values['approver_email'] = $values['L1managerEmail'] ?? null;
$values['email_error_message'] = $emailErrorMessage;
$values['subject_header'] = $subjectHeader;
$values['no_error'] = $noError;

return $values;
