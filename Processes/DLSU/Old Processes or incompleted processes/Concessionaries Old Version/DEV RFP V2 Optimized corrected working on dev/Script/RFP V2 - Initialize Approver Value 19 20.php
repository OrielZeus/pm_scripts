<?php
/*********************************************
 * RFP CON - Resolve and Initialize Approvers
 *
 * Purpose:
 * - Resolve the next approvers for node_11.
 * - Reuse the same script for update steps (node_15/node_17).
 *
 * Environment variables:
 * - API_HOST
 * - API_TOKEN
 * - API_SQL (optional, default: /admin/package-proservice-tools/sql)
 * - RFP_CON_COLLECTION_75_NAME (optional, default: collection_75)
 * - RFP_CON_COLLECTION_124_NAME (optional, default: collection_124)
 *********************************************/

/**
 * Execute ProcessMaker API calls with a common Guzzle wrapper.
 */
function rfpApiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
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

    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);

    $body = $contentFile ? null : json_encode($postdata);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, $body);

    try {
        $res = $client->sendAsync($request)->wait();
        $resBody = $res->getBody()->getContents();
        if (!$contentFile) {
            $resBody = json_decode($resBody, true);
        }
    } catch (\Throwable $e) {
        $resBody = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ':' . ($e->getLine() ?? '')),
            'error_response' => method_exists($e, 'getResponse') && $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '',
        ];
    }

    return $resBody;
}

/**
 * Encode SQL query payload expected by ProcessMaker SQL endpoint.
 */
function rfpEncodeSql(string $sql): array
{
    return ['SQL' => base64_encode($sql)];
}

/**
 * Escape text values used in SQL literals.
 */
function rfpEscSql($value): string
{
    return str_replace("'", "''", trim((string)$value));
}

/*
 * Branch A: Update current approval cycle after approver decision.
 * This is used by script tasks that already have approval_input.
 */
$approvalInputExists = array_key_exists('approval_input', $data) && $data['approval_input'] !== null && $data['approval_input'] !== '';
if ($approvalInputExists) {
    $level_number = ((int)($data['level_number'] ?? 0)) + 1;
    $approver_array = $data['approver_array'] ?? [];
    if ($approver_array == null) {
        $approver_array = [];
    }

    $email_error_message = '';
    $subject_header = '';
    $approver_level = $level_number - 1;
    $approver_name = (string)($data['approver_name'] ?? '');
    $approval_date = (string)($data['approval_date'] ?? '');
    $approver_remarks = (string)($data['remarks_input'] ?? '');
    $approval_input = $data['approval_input'];
    $rfp_number = (string)($data['number'] ?? '');

    if ($approval_input == 1) {
        $approval_input = 'APPROVED';
    }
    if ($approval_input == 2) {
        $approval_input = 'REQUEST FOR MORE INFORMATION';
        $email_error_message = 'The approver has requested for more information for the submitted RFP, please refer to the remarks of the approver.';
        $subject_header = 'Request For More Information for RFP - ' . $rfp_number;
    }
    if ($approval_input == 3) {
        $approval_input = 'REJECTED';
    }

    $approver_array[] = [
        'approver_level' => $approver_level,
        'approval_date' => $approval_date,
        'approver_name' => $approver_name,
        'approver_remarks' => $approver_remarks,
        'approval_input' => $approval_input,
    ];

    $variable_id = 'L' . ($level_number) . 'managerId';
    $variable_name = 'L' . ($level_number) . 'managerName';
    $variable_email = 'L' . ($level_number) . 'managerEmail';

    return [
        'approver_array' => $approver_array,
        'approver_id' => (string)($data[$variable_id] ?? ''),
        'approver_name' => (string)($data[$variable_name] ?? ''),
        'approver_email' => (string)($data[$variable_email] ?? ''),
        'level_number' => $level_number,
        'approval_input' => '',
        'remarks_input' => '',
        'email_error_message' => $email_error_message,
        'subject_header' => $subject_header,
    ];
}

/*
 * Branch B: Resolve next approver chain from routing collections.
 * This is used by node_11 before approvers start reviewing.
 */
require 'vendor/autoload.php';

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$collection75 = getenv('RFP_CON_COLLECTION_75_NAME') ?: 'collection_75';
$collection124 = getenv('RFP_CON_COLLECTION_124_NAME') ?: 'collection_124';

$email_error_message = '';
$subject_header = '';
$rfp_number = (string)($data['number'] ?? '');
$no_error = "1";
$string_input = strtoupper((string)($data['request_name'] ?? ''));
$search_string = 'RFP V2 - INTERNAL';
$ITEM_record_list = $data['ITEM_recordList'] ?? null;

if ($ITEM_record_list == null && $string_input !== $search_string) {
    $no_error = "0";
    $subject_header = 'RFP ERROR: ' . $rfp_number;
    $email_error_message = 'Please add transactions under the record list to prevent encountering the error upon posting to ORACLE.';
}

$campus_input = strtoupper((string)($data['campusChoiceCode'] ?? ''));
$original_dept_code = strtoupper((string)($data['requestor_department_code'] ?? ''));
$dept_code_input = strtoupper((string)($data['deprt_code_selected'] ?? ''));
$dept_input = $dept_code_input !== '' ? $dept_code_input : $original_dept_code;

$amount_requested = (float)($data['amountRequested'] ?? 0);
$transaction_currency = strtoupper((string)($data['transactionCurrency'] ?? ''));
$supplier = strtoupper((string)($data['payee']['NAME'] ?? ''));
$internal_transaction_type = strtoupper((string)($data['transactionTypeCode'] ?? ''));
$transaction_type = (string)($data['transactionType'] ?? '');
$dept_from_form = strtoupper((string)($data['department_selected'] ?? ''));
$conditions = trim((string)($data['conditions'] ?? ''));

$is_internal = ($string_input === $search_string && $dept_input === '4-02-06-021');
$is_lider_project = ($dept_input === '3-14-45-265');

$approvalRows = [];
$sqlError = null;

// Priority 1: explicit conditions code from legacy dropdown routing.
if ($conditions !== '') {
    $sqlByCode = "
        SELECT
            data->>'$.approver_level' AS approver_level,
            data->>'$.RUL_USER' AS manager_id,
            data->>'$.approver_name' AS approver_name,
            data->>'$.min_amount' AS min_amount,
            data->>'$.min_usd_amount' AS min_usd_amount
        FROM {$collection75}
        WHERE UPPER(data->>'$.RUL_CODE') = '" . rfpEscSql(strtoupper($conditions)) . "'
        ORDER BY CAST(data->>'$.approver_level' AS SIGNED) ASC
    ";

    $respByCode = rfpApiGuzzle($apiHost . $apiSql, 'POST', rfpEncodeSql($sqlByCode));
    if (is_array($respByCode) && isset($respByCode['error_message'])) {
        $sqlError = $respByCode['error_message'];
    } elseif (is_array($respByCode)) {
        $approvalRows = $respByCode;
    }
}

// Priority 2: dynamic matrix lookup by campus, department, amount and type.
if (empty($approvalRows)) {
    $amountField = $transaction_currency === 'USD' ? 'min_usd_amount' : 'min_amount';

    $where = [];
    $where[] = "UPPER(data->>'$.userDept') = '" . rfpEscSql($dept_input) . "'";
    $where[] = "UPPER(data->>'$.campusChoiceCode') LIKE '%" . rfpEscSql($campus_input) . "%'";
    $where[] = "CAST(COALESCE(NULLIF(data->>'$.{$amountField}', ''), '0') AS DECIMAL(18,2)) <= " . (float)$amount_requested;

    if ($is_internal && $internal_transaction_type !== '') {
        $where[] = "UPPER(data->>'$.transactionTypeCode') = '" . rfpEscSql($internal_transaction_type) . "'";
    }

    if ($is_lider_project && $dept_from_form !== '') {
        $where[] = "UPPER(data->>'$.userDeptName') = '" . rfpEscSql($dept_from_form) . "'";
    }

    $sqlMatrix = "
        SELECT
            data->>'$.approver_level' AS approver_level,
            data->>'$.RUL_USER' AS manager_id,
            data->>'$.approver_name' AS approver_name,
            data->>'$.min_amount' AS min_amount,
            data->>'$.min_usd_amount' AS min_usd_amount
        FROM {$collection124}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY CAST(data->>'$.approver_level' AS SIGNED) ASC
    ";

    $respMatrix = rfpApiGuzzle($apiHost . $apiSql, 'POST', rfpEncodeSql($sqlMatrix));
    if (is_array($respMatrix) && isset($respMatrix['error_message'])) {
        $sqlError = $respMatrix['error_message'];
        $approvalRows = [];
    } elseif (is_array($respMatrix)) {
        $approvalRows = $respMatrix;
    }
}

$values = [
    'supplier' => $supplier,
    'requestor_department_code' => $dept_input,
    'converted_amount' => null,
    'transactionType' => $transaction_type,
];

$until_level_number = 0;
$managerCount = 0;
if (!empty($approvalRows)) {
    foreach ($approvalRows as $row) {
        $managerId = (string)($row['manager_id'] ?? '');
        if ($managerId === '') {
            continue;
        }

        $managerCount++;
        $variable_id = 'L' . $managerCount . 'managerId';
        $variable_name = 'L' . $managerCount . 'managerName';
        $variable_email = 'L' . $managerCount . 'managerEmail';

        $values[$variable_id] = $managerId;

        $userResp = rfpApiGuzzle($apiHost . '/users/' . $managerId, 'GET');
        if (is_array($userResp) && !isset($userResp['error_message'])) {
            $values[$variable_email] = (string)($userResp['email'] ?? '');
            $values[$variable_name] = (string)($userResp['fullname'] ?? ($row['approver_name'] ?? ''));
        } else {
            $values[$variable_email] = '';
            $values[$variable_name] = (string)($row['approver_name'] ?? '');
        }

        $until_level_number = $managerCount;
    }
}

$values['until_level_number'] = $until_level_number;
$values['level_number'] = 1;
$values['approver_id'] = (string)($values['L1managerId'] ?? '');
$values['approver_name'] = (string)($values['L1managerName'] ?? '');
$values['approver_email'] = (string)($values['L1managerEmail'] ?? '');

if ($values['approver_id'] === '') {
    $no_error = "0";
    $subject_header = 'RFP ERROR: ' . $rfp_number;
    $email_error_message = 'No approver was found for the selected campus and department. Please contact support for the issue.';
}

if ($sqlError !== null) {
    $no_error = "0";
    $subject_header = 'RFP ERROR: ' . $rfp_number;
    $email_error_message = 'Failed to resolve approver matrix through SQL API. Please contact support. Detail: ' . $sqlError;
}

$values['no_error'] = $no_error;
$values['email_error_message'] = $email_error_message;
$values['subject_header'] = $subject_header;

return $values;