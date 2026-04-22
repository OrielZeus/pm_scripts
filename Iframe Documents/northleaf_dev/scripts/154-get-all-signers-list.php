<?php 
/**********************************
 * Get All Signers List
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();
$aPrimary = array();
$aSecondary = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get Signing Users
$sql = "SELECT U.id AS user_id, 
            U.status,
            CONCAT (U.firstname, ' ', U.lastname) AS user_fullname, 
            U.meta->>'$.typeOfSigner' AS type_signer,
            IF(U.meta->>'$.signature' != '', 'YES', 'NO') AS signature
        FROM users AS U
        WHERE  U.meta->>'$.typeOfSigner' != '' AND U.meta->>'$.typeOfSigner' IS NOT NULL AND U.meta->>'$.typeOfSigner' != 'null'
            AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
        ORDER BY user_fullname";
$usersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
//Loop Users
foreach($usersResponse as $user) {
    if ($user['type_signer'] == 'Both') {
        array_push($aPrimary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
        array_push($aSecondary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
    }
    if ($user['type_signer'] == 'Primary') {
        array_push($aPrimary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
    }
    if ($user['type_signer'] == 'Secondary') {
        array_push($aSecondary, [
            "USER_ID" => $user['user_id'],
            "USER_NAME" => $user['user_fullname'],
            "SIGNATURE" => $user['signature']
        ]);
    }
}
$dataReturn["PRIMARY_SIGNERS"] = $aPrimary;
$dataReturn["SECONDARY_SIGNERS"] = $aSecondary;

return $dataReturn;