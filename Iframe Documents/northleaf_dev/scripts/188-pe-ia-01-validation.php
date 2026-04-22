<?php 
/**********************************
 * PE - IA.01 Validation Post-Processing
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

$userLead = $data['PE_INVESTMENT_ADVISOR_APPROVER'];

//Validation in case of Reassignment
if ($data["ID_USER_IA"] != $data['PE_INVESTMENT_ADVISOR_APPROVER']) {
    $userLead = $data["ID_USER_IA"];
    $dataReturn['PE_INVESTMENT_ADVISOR_APPROVER'] = $userLead;
}

//Check Signature
$sql = "SELECT U.id AS user_id, 
            U.status,
            CONCAT (U.firstname, ' ', U.lastname) AS user_fullname, 
            U.meta->>'$.typeOfSigner' AS type_signer,
            IF((U.meta->>'$.signature' != '' AND U.meta->>'$.signature' != 'null' AND U.meta->>'$.signature' IS NOT NULL), 
                'YES', 
                'NO'
            ) AS signature
        FROM users AS U
        WHERE U.id = " . $userLead . "
            AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
        LIMIT 1";
$usersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
$dataReturn['PE_INVESTMENT_ADVISOR_APPROVER_SIGNATURE'] = 'NO';
if (empty($usersResponse["error_message"]) && count($usersResponse) > 0) {
    $dataReturn['PE_INVESTMENT_ADVISOR_APPROVER_SIGNATURE'] = $usersResponse[0]['signature'];
    $dataReturn['PE_INVESTMENT_ADVISOR_APPROVER_NAME'] = $usersResponse[0]['user_fullname'];
} else {
    $dataReturn['error_IA_Validation'] = [$usersResponse, $sql];
}
$dataReturn["PE_SAVE_SUBMIT_IA1"] = '';

return $dataReturn;