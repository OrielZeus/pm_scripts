<?php 
/**********************************
 * IN - 
 *
 * by 
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$groupName = $data['groupName'];
$query = "SELECT AD.data->>'$.SUBMITTER_MANAGER.id' as USER_ID,AD.data->>'$.SUBMITTER_MANAGER.fullname' as USER_NAME,
            AD.data->>'$.SUBMITTER_DEFAULT' as SUBMITTER_DEFAULT
            FROM collection_".getCollectionId('IN_GROUP_DEPARTMENT', $apiUrl)." AS GD,
            collection_".getCollectionId('IN_APPROVER_DEPARTMENT', $apiUrl)." AD, 
            users as U 
            WHERE GD.data->>'$.DEPARTMENT.DEPARTMENT_LABEL' = AD.data->>'$.SUBMITTER_DEPARTMENT.DEPARTMENT_LABEL'
            AND GD.data->>'$.GROUP.name' = '" . $groupName . "'
            AND U.id = AD.data->>'$.SUBMITTER_MANAGER.id'
            AND U.deleted_at IS NULL 
            ORDER BY USER_NAME asc";

$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
$defaultApp = [];

if($groupName == 'IN_Infra Ops'){
    $selectApp  = (isset($data['IN_SUBMITTER_INFRA']) AND !empty(($data['IN_SUBMITTER_INFRA']))) ? $data['IN_SUBMITTER_INFRA'] : '';
}
if($groupName == 'IN_PE Ops'){
    $selectApp  = (isset($data['IN_SUBMITTER_PE']) AND !empty(($data['IN_SUBMITTER_PE']))) ? $data['IN_SUBMITTER_PE'] : '';
}
if($groupName == 'IN_PC Ops'){
    $selectApp  = (isset($data['IN_SUBMITTER_PC']) AND !empty(($data['IN_SUBMITTER_PC']))) ? $data['IN_SUBMITTER_PC'] : '';
}
if($groupName == 'IN_Corporate Finance'){
    $selectApp  = (isset($data['IN_SUBMITTER_CO']) AND !empty(($data['IN_SUBMITTER_CO']))) ? $data['IN_SUBMITTER_CO'] : '';
}


if(!empty($selectApp)){
    foreach($response as $approver){
        if($approver['USER_ID'] == $selectApp){
            $defaultApp = $approver;
            break;
        }
    }
}
else{
    foreach($response as $approver){
        if($approver['SUBMITTER_DEFAULT'] == "true"){
            $defaultApp = $approver;
            break;
        }
    }
}
return [
    "DATA" => $response,
    "DEFAULT" => $defaultApp
];