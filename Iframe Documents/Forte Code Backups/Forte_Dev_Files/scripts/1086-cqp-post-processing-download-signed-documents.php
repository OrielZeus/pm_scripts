<?php 
/*  
 *  CQP - Postprocessing Set email Information
 *  By Adriana Centellas
 */

require_once("/CQP_Generic_Functions.php");

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$languageSelected = $data["CQP_LANGUAGE_OPTION"];

sendNotification($data, 'EMAILDOC', $languageSelected, $api);

return [];