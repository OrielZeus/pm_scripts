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

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$apiUrl = $apiHost . $apiSql;

$userId = (int)($data['_request']['user_id'] ?? 0);
$requestName = (string)($data['_request']['name'] ?? '');
$requestId = (int)($data['_request']['id'] ?? 0);
$requestLink = $apiHost . '/requests/' . $requestId;

$userProfileCollectionId = getenv('RFP_HON_COLLECTION_USER_PROFILE_ID') ?: '43';
$userProfileTable = 'collection_' . preg_replace('/[^0-9]/', '', (string)$userProfileCollectionId);
if ($userProfileTable === 'collection_') {
    $userProfileTable = 'collection_43';
}

$multipleDepartments = false;
$userDepartmentList = [];
$userFullname = '';
$userEmail = '';
$userCollege = '';
$userDepartment = '';
$userDepartmentCode = '';
$userEmployeeId = '';
$requestorCampus = '';

if ($userId > 0) {
    $sql = "
        SELECT
            data->>'$.user_id' AS user_id,
            data->>'$.employee_name' AS employee_name,
            data->>'$.employee_email' AS employee_email,
            data->>'$.employee_id' AS employee_id,
            data->>'$.campus.code' AS campus_code,
            data->>'$.college.name' AS college_name,
            data->>'$.department.department_name' AS department_name,
            data->>'$.department.department_code' AS department_code
        FROM {$userProfileTable}
        WHERE CAST(data->>'$.user_id' AS UNSIGNED) = {$userId}
        ORDER BY id DESC
        LIMIT 1
    ";
    $profileResp = rfpHonApiGuzzle($apiUrl, 'POST', rfpHonEncodeSql($sql));

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
    }
}

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
        // Keep defaults.
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
