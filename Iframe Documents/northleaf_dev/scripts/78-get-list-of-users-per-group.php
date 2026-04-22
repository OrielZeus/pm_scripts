<?php

/**********************************
 * Get List of Users per Group
 *
 * by Elmer Orihuela
 *********************************/
require_once("/Northleaf_PHP_Library.php");
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$host = $_SERVER["HOST_URL"];
// Url SQL
$sqlUrl = $apiHost . $apiSql;
$dataEncode = str_replace("|", "=", $data);
$pmqlVariables = json_decode(base64_decode($dataEncode[0]), true);
$signatureGroupId = empty($pmqlVariables['groupId']) ? 20 : $pmqlVariables['groupId'];
// Check if the user is in the signature group and has a signature configuration
$getICApprovers = "SELECT ";
$getICApprovers .= "U.id as id, ";
$getICApprovers .= "U.firstname as firstname, ";
$getICApprovers .= "U.lastname as lastname, ";
$getICApprovers .= "U.username as username, ";
$getICApprovers .= "U.email as email, ";
$getICApprovers .= "CONCAT(U.firstname, ' ', U.lastname) AS fullname ";
$getICApprovers .= "FROM group_members as GM ";
$getICApprovers .= "JOIN users AS U on GM.member_id = U.id ";
$getICApprovers .= "WHERE GM.group_id = " . $signatureGroupId . " ";
//Extra validation for Portfolio Manager Approver in screen 176
if ($signatureGroupId == getenv('PORTFOLIO_MANAGER_GROUP_ID')) {
    $getICApprovers .= "AND U.meta->>'$.signature' IS NOT NULL AND U.meta->>'$.signature' IS NOT NULL AND U.meta->>'$.signature' != 'null' ";
}
$getICApprovers .= "ORDER BY fullname";

$responseGetICApprovers = callApiUrlGuzzle($sqlUrl, "POST", encodeSql($getICApprovers));
return $responseGetICApprovers;