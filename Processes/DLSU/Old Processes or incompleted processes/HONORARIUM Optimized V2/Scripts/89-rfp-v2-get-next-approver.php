<?php
require 'vendor/autoload.php';

function rfpHonApiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }

    $acceptType = $contentFile ? 'application/octet-stream' : 'application/json';
    $headers = [
        'Accept' => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken,
    ];
    if (!$contentFile) {
        $headers['Content-Type'] = 'application/json';
    }

    $client = new \GuzzleHttp\Client(['verify' => false]);
    $body = $contentFile ? null : json_encode($postdata);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, $body);

    try {
        $res = $client->sendAsync($request)->wait();
        $resBody = $res->getBody()->getContents();
        return $contentFile ? $resBody : json_decode($resBody, true);
    } catch (\Throwable $e) {
        return [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ':' . ($e->getLine() ?? '')),
            'error_response' => method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '',
        ];
    }
}

function rfpHonEncodeSql(string $sql): array
{
    return ['SQL' => base64_encode($sql)];
}

function rfpHonEscSql($value): string
{
    return str_replace("'", "''", trim((string)$value));
}

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$collection75 = getenv('RFP_HON_COLLECTION_75_NAME') ?: 'collection_75';
$routingCollectionId = getenv('RFP_V2_ROUTING_COLLECTION_ID') ?: '124';
$collection124 = 'collection_' . preg_replace('/[^0-9]/', '', (string)$routingCollectionId);
if ($collection124 === 'collection_') {
    $collection124 = 'collection_124';
}

$rfpNumber = (string)($data['number'] ?? '');
$emailErrorMessage = '';
$subjectHeader = '';
$noError = "1";

$requestName = strtoupper((string)($data['request_name'] ?? ''));
$campusInput = strtoupper((string)($data['campusChoiceCode'] ?? ''));
$originalDeptCode = strtoupper((string)($data['requestor_department_code'] ?? ''));
$deptCodeInput = strtoupper((string)($data['deprt_code_selected'] ?? ''));
$deptInput = $deptCodeInput !== '' ? $deptCodeInput : $originalDeptCode;
$deptFromForm = strtoupper((string)($data['department_selected'] ?? ''));
$amountRequested = (float)($data['amountRequested'] ?? 0);
$transactionCurrency = strtoupper((string)($data['transactionCurrency'] ?? ''));
$supplier = strtoupper((string)($data['payee']['NAME'] ?? ''));
$internalTransactionType = strtoupper((string)($data['transactionTypeCode'] ?? ''));
$transactionType = (string)($data['transactionType'] ?? '');
$conditions = trim((string)($data['conditions'] ?? ''));

$itemRecordList = $data['ITEM_recordList'] ?? null;
if ($itemRecordList == null && $requestName !== 'RFP V2 - INTERNAL') {
    $noError = "0";
    $subjectHeader = 'RFP ERROR: ' . $rfpNumber;
    $emailErrorMessage = 'Please add transactions under the record list to prevent encountering the error upon posting to ORACLE.';
}

$isInternal = ($requestName === 'RFP V2 - INTERNAL' && $deptInput === '4-02-06-021');
$isLiderProject = ($deptInput === '3-14-45-265');

$approvalRows = [];
$sqlError = null;

if ($conditions !== '') {
    $sqlByCode = "
        SELECT
            data->>'$.approver_level' AS approver_level,
            data->>'$.RUL_USER' AS manager_id,
            data->>'$.approver_name' AS approver_name
        FROM {$collection75}
        WHERE UPPER(data->>'$.RUL_CODE') = '" . rfpHonEscSql(strtoupper($conditions)) . "'
        ORDER BY CAST(data->>'$.approver_level' AS SIGNED) ASC
    ";
    $respByCode = rfpHonApiGuzzle($apiHost . $apiSql, 'POST', rfpHonEncodeSql($sqlByCode));
    if (is_array($respByCode) && isset($respByCode['error_message'])) {
        $sqlError = $respByCode['error_message'];
    } elseif (is_array($respByCode)) {
        $approvalRows = $respByCode;
    }
}

if (empty($approvalRows)) {
    $amountField = $transactionCurrency === 'USD' ? 'min_usd_amount' : 'min_amount';
    $where = [];
    $where[] = "UPPER(data->>'$.userDept') = '" . rfpHonEscSql($deptInput) . "'";
    $where[] = "UPPER(data->>'$.campusChoiceCode') LIKE '%" . rfpHonEscSql($campusInput) . "%'";
    $where[] = "CAST(COALESCE(NULLIF(data->>'$.{$amountField}', ''), '0') AS DECIMAL(18,2)) <= " . (float)$amountRequested;

    if ($isInternal && $internalTransactionType !== '') {
        $where[] = "UPPER(data->>'$.transactionTypeCode') = '" . rfpHonEscSql($internalTransactionType) . "'";
    }
    if ($isLiderProject && $deptFromForm !== '') {
        $where[] = "UPPER(data->>'$.userDeptName') = '" . rfpHonEscSql($deptFromForm) . "'";
    }

    $sqlMatrix = "
        SELECT
            data->>'$.approver_level' AS approver_level,
            data->>'$.RUL_USER' AS manager_id,
            data->>'$.approver_name' AS approver_name
        FROM {$collection124}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY CAST(data->>'$.approver_level' AS SIGNED) ASC
    ";
    $respMatrix = rfpHonApiGuzzle($apiHost . $apiSql, 'POST', rfpHonEncodeSql($sqlMatrix));
    if (is_array($respMatrix) && isset($respMatrix['error_message'])) {
        $sqlError = $respMatrix['error_message'];
        $approvalRows = [];
    } elseif (is_array($respMatrix)) {
        $approvalRows = $respMatrix;
    }
}

$values = [
    'supplier' => $supplier,
    'requestor_department_code' => $deptInput,
    'converted_amount' => null,
    'transactionType' => $transactionType,
];

$untilLevelNumber = 0;
$managerCount = 0;
foreach ($approvalRows as $row) {
    $managerId = (string)($row['manager_id'] ?? '');
    if ($managerId === '') {
        continue;
    }
    $managerCount++;
    $idKey = 'L' . $managerCount . 'managerId';
    $nameKey = 'L' . $managerCount . 'managerName';
    $emailKey = 'L' . $managerCount . 'managerEmail';

    $values[$idKey] = $managerId;
    $userResp = rfpHonApiGuzzle($apiHost . '/users/' . $managerId, 'GET');
    if (is_array($userResp) && !isset($userResp['error_message'])) {
        $values[$emailKey] = (string)($userResp['email'] ?? '');
        $values[$nameKey] = (string)($userResp['fullname'] ?? ($row['approver_name'] ?? ''));
    } else {
        $values[$emailKey] = '';
        $values[$nameKey] = (string)($row['approver_name'] ?? '');
    }
    $untilLevelNumber = $managerCount;
}

$values['until_level_number'] = $untilLevelNumber;
$values['level_number'] = 1;
$values['approver_id'] = (string)($values['L1managerId'] ?? '');
$values['approver_name'] = (string)($values['L1managerName'] ?? '');
$values['approver_email'] = (string)($values['L1managerEmail'] ?? '');

if ($values['approver_id'] === '') {
    $noError = "0";
    $subjectHeader = 'RFP ERROR: ' . $rfpNumber;
    $emailErrorMessage = 'No approver was found for the selected campus and department. Please contact support for the issue.';
}
if ($sqlError !== null) {
    $noError = "0";
    $subjectHeader = 'RFP ERROR: ' . $rfpNumber;
    $emailErrorMessage = 'Failed to resolve approver matrix through SQL API. Please contact support. Detail: ' . $sqlError;
}

$values['no_error'] = $noError;
$values['email_error_message'] = $emailErrorMessage;
$values['subject_header'] = $subjectHeader;

return $values;
