<?php 
/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use getenv("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/docker-executor-php/tree/master/docs/sdk 
 */

require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$queryAccount = 'SELECT data->>"$.NL_ACCOUNT_SYSTEM_ID_ACTG" AS ID, 
                    data->>"$.ACCOUNT_LABEL" AS LABEL 
				    FROM collection_' . getCollectionId("IN_EXPENSE_ACCOUNT", $apiUrl);
$accountData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryAccount));

$queryProject = 'SELECT data->>"$.NL_COMPANY_SYSTEM_ID_ACTG" AS ID, 
                    data->>"$.EXPENSE_CORPORATE_LABEL" AS LABEL 
				    FROM collection_' . getCollectionId("IN_EXPENSE_CORP_PROJ", $apiUrl);
$projectData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryProject));

return [
    "ACCOUNT_DATA" => $accountData,
    "PROJECT_DATA" => $projectData
];