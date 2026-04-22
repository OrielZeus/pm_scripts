<?php 
/**********************************
 * PE - PA.01 Validation Post-Processing
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$userLead = $data['PE_PORTFOLIO_MANAGER_APPROVER'];

//Validation in case of Reassignment
if ($data["ID_USER_PA"] != $data['PE_PORTFOLIO_MANAGER_APPROVER']) {
    $userLead = $data["ID_USER_PA"];
    $dataReturn['PE_PORTFOLIO_MANAGER_APPROVER'] = $userLead;
}

//Check Signature
$sql = "SELECT U.id AS user_id, 
            U.status,
            CONCAT (U.firstname, ' ', U.lastname) AS user_fullname, 
            IF((U.meta->>'$.signature' != '' AND U.meta->>'$.signature' != 'null' AND U.meta->>'$.signature' IS NOT NULL), 
                'YES', 
                'NO'
            ) AS signature
        FROM users AS U
        WHERE U.id = " . $userLead . "
            AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
        LIMIT 1";
$usersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));

$dataReturn['PE_PORTFOLIO_MANAGER_APPROVER_SIGNATURE'] = 'NO';
if (empty($usersResponse["error_message"]) && count($usersResponse) > 0) {
    $dataReturn['PE_PORTFOLIO_MANAGER_APPROVER_SIGNATURE'] = $usersResponse[0]['signature'];
} else {
    $dataReturn['error_PA_Validation'] = [$usersResponse, $sql];
}
$dataReturn['log_PA'] = $data["_request"]["user_id"];
$dataReturn["PE_SAVE_SUBMIT_PA1"] = '';

return $dataReturn;