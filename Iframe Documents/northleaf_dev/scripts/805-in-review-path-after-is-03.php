<?php 
/*  
 *  Review path after IS.03
 *  By Adriana Centellas
 */
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");

$apiInstance = $api->requestFiles();
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;
$environmentBaseUrl = getenv("ENVIRONMENT_BASE_URL");

$processRequestId = $data["RequestID"];
$userId = $data["UserID"];

$getTableExpenseQl = "select IN_EXPENSE_TEAM_ROUTING_LABEL from EXPENSE_TABLE where IN_EXPENSE_CASE_ID = " . $processRequestId . " group by IN_EXPENSE_TEAM_ROUTING_LABEL;";
$tableExpense = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getTableExpenseQl));

$getUsersByGroup = "select concat(u.firstName, ' ', u.lastName) as fullname, g.id_ as group_id, g.name_ as group_name
from users u
inner join group_members gm on gm.member_id = u.id
inner join dlv_groups g on g.id_ = gm.group_id
where u.id = " . $userId;
$usersGroups = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getUsersByGroup));

$hasNextTask = false;

// Tomamos el primer valor de la tabla (ajustar si hay más de uno)
$teamLabel = $tableExpense[0]["IN_EXPENSE_TEAM_ROUTING_LABEL"] ?? null;

if ($teamLabel) {
    switch ($teamLabel) {
        case "Corporate":
            $requiredGroup = "IN_Corporate Finance";
            break;
        case "Infrastructure":
            $requiredGroup = "IN_Infra Ops";
            break;
        case "Private Credit":
            $requiredGroup = "IN_PC Ops";
            break;
        case "Private Equity":
            $requiredGroup = "IN_PE Ops";
            break;
        default:
            $requiredGroup = null;
    }

    if ($requiredGroup) {
        foreach ($usersGroups as $group) {
            if ($group["group_name"] === $requiredGroup) {
                $hasNextTask = true;
                break;
            }
        }
    }
}

return $hasNextTask;