<?php 
require_once("/Northleaf_PHP_Library.php");

$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$deleteRowsList = json_decode(html_entity_decode($data['IN_EXPENSE_DELETED_ROWS']), true);
if(empty($deleteRowsList)){
    return 'Any row deleted';
}
$deleted = [];
foreach($deleteRowsList as $key => $rowId){
    $deleted[] = $rowId;
    deleleteRowById($apiUrl, $rowId);
}

return [$deleted];


function deleleteRowById($apiUrl, $rowId){
    $query  = "";
    $query .= "DELETE FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_ROW_ID = '" . $rowId . "';";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}