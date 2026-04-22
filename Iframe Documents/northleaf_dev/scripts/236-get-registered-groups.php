<?php

/**********************************
 * get registered groups in collection IN_SUBMITTER_DEPARTMENT
 *
 * by Manuel Monroy
 * modified by Adriana Centellas
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$submitterDepartmentCollectionID = getCollectionId("IN_SUBMITTER_DEPARTMENT", $apiUrl);
$options = getSubmitterDepartments($submitterDepartmentCollectionID, $apiUrl);

return ["DEPARTMENTS" => $options];

function getSubmitterDepartments($collectionID, $apiUrl)
{
        $querySubmitterDepartments = "
SELECT 
  JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_DB')) AS NL_DEPARTMENT_SYSTEM_ID_DB,
  JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.DEPARTMENT_LABEL'))         AS DEPARTMENT_LABEL,
  ANY_VALUE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.CAN_BYPASS')))    AS CAN_BYPASS,
  JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_ACTG')) AS NL_DEPARTMENT_SYSTEM_ID_ACTG
FROM collection_".$collectionID." 
WHERE JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.DEPARTMENT_STATUS')) = 'Active'
GROUP BY
  JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_DB')),
  JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.DEPARTMENT_LABEL')),
  JSON_UNQUOTE(JSON_EXTRACT(data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_ACTG'))
ORDER BY DEPARTMENT_LABEL DESC;";
        
        $groupSubmitterDepartments = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySubmitterDepartments));

    return $groupSubmitterDepartments;
}