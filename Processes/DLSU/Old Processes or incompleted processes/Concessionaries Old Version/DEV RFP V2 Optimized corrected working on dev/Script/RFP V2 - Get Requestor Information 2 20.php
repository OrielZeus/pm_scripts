<?php
/*********************************************
 * RFP CON - Get Requestor Information
 *
 * Purpose:
 * - Retrieve requestor profile from User Profile collection using SQL API.
 * - Normalize requestor fields used by forms and routing logic.
 *
 * Environment variables:
 * - API_HOST
 * - API_TOKEN
 * - API_SQL (optional, default: /admin/package-proservice-tools/sql)
 * - RFP_CON_COLLECTION_USER_PROFILE_ID (optional, default: 43)
 *********************************************/

require 'vendor/autoload.php';

/**
 * Generic API wrapper for ProcessMaker endpoints.
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
 * Encode SQL query payload expected by SQL endpoint.
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

// -------------------------- Environment --------------------------- //
$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$apiUrl = $apiHost . $apiSql;

// -------------------------- Request context ----------------------- //
$userId = (int)($data['_request']['user_id'] ?? 0);
$requestName = (string)($data['_request']['name'] ?? '');
$requestId = (int)($data['_request']['id'] ?? 0);
$requestLink = $apiHost . '/requests/' . $requestId;

// -------------------------- Configurable collections ------------- //
$userProfileCollectionId = getenv('RFP_CON_COLLECTION_USER_PROFILE_ID') ?: '43';
$userProfileTable = 'collection_' . preg_replace('/[^0-9]/', '', (string)$userProfileCollectionId);

// -------------------------- Defaults ------------------------------ //
$multipleDepartments = false;
$userDepartmentList = [];
$userFullname = '';
$userEmail = '';
$userCollege = '';
$userDepartment = '';
$userDepartmentCode = '';
$userEmployeeId = '';
$requestorCampus = '';

if ($userId > 0 && $userProfileTable !== 'collection_') {
    // Optimized SQL lookup by user_id from profile collection.
    $sql = "
        SELECT
            data->>'$.user_id' AS user_id,
            data->>'$.employee_name' AS employee_name,
            data->>'$.employee_email' AS employee_email,
            data->>'$.employee_id' AS employee_id,
            data->>'$.campus.code' AS campus_code,
            data->>'$.college.name' AS college_name,
            data->>'$.department.department_name' AS department_name,
            data->>'$.department.department_code' AS department_code,
            data->>'$.department' AS department_json
        FROM {$userProfileTable}
        WHERE CAST(data->>'$.user_id' AS UNSIGNED) = {$userId}
        ORDER BY id DESC
        LIMIT 1
    ";

    $profileResp = rfpApiGuzzle($apiUrl, 'POST', rfpEncodeSql($sql));

    if (is_array($profileResp) && !isset($profileResp['error_message']) && !empty($profileResp[0])) {
        $profile = $profileResp[0];

        $userId = (int)($profile['user_id'] ?? $userId);
        $userFullname = (string)($profile['employee_name'] ?? '');
        $userEmail = (string)($profile['employee_email'] ?? '');
        $userEmployeeId = (string)($profile['employee_id'] ?? '');
        $requestorCampus = (string)($profile['campus_code'] ?? '');
        $userCollege = (string)($profile['college_name'] ?? '');
        $userDepartment = (string)($profile['department_name'] ?? '');
        $userDepartmentCode = (string)($profile['department_code'] ?? '');

        // Parse full department object/array if present.
        $departmentJson = $profile['department_json'] ?? '';
        if (is_string($departmentJson) && $departmentJson !== '') {
            $departmentDecoded = json_decode($departmentJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($departmentDecoded)) {
                if (array_keys($departmentDecoded) === range(0, count($departmentDecoded) - 1)) {
                    $userDepartmentList = $departmentDecoded;
                    if (count($departmentDecoded) > 1) {
                        $multipleDepartments = true;
                    }
                    if (!empty($departmentDecoded[0]['department_code'])) {
                        $userDepartmentCode = (string)$departmentDecoded[0]['department_code'];
                    }
                    if (!empty($departmentDecoded[0]['department_name'])) {
                        $userDepartment = (string)$departmentDecoded[0]['department_name'];
                    }
                }
            }
        }
    }
}

// Fallback to ProcessMaker user object if profile collection has no record.
if ($userFullname === '' && $userId > 0) {
    try {
        $apiInstance = $api->users();
        $user = $apiInstance->getUserById($userId);
        if ($user) {
            $userEmail = (string)$user->getEmail();
            $userFullname = (string)$user->getFullName();
            $userCollege = (string)$user->getFax();
            $userDepartment = (string)$user->getTitle();
            $userEmployeeId = (string)$user->getPhone();
        }
    } catch (\Throwable $e) {
        // Keep defaults if fallback fails.
    }
}

return [
    'multiple_departments' => $multipleDepartments,
    'requestor_department_list' => $userDepartmentList,
    'email' => $userEmail,
    'user_id' => $userId,
    'requestor_employee_id' => $userEmployeeId,
    'requestor_fullname' => strtoupper($userFullname),
    'requestor_email' => $userEmail,
    'requestor_campus' => strtoupper($requestorCampus),
    'requestor_college' => strtoupper($userCollege),
    'requestor_department' => strtoupper($userDepartment),
    'requestor_department_code' => strtoupper($userDepartmentCode),
    'requestId' => $requestId,
    'request_link' => $requestLink,
    'request_name' => $requestName,
];