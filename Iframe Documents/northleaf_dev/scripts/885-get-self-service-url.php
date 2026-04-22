<?php 
/**********************************
*  Get Saved Search - Self Service URL by User
*
*  by Telmo Chiri
**********************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiSql = getenv('API_SQL');
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$currentUser = $data["CURRENT_USER"];

//Default URL
$url = '#';

//Get Self Service Info by User
$querySelfService = "SELECT 
                        id, title, pmql, type
                    FROM
                        saved_searches
                    WHERE 
                        user_id='". $currentUser ."'
                        AND type='task'
                        AND title='Self Service'";
$selfServiceResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySelfService));

if (!empty($selfServiceResponse)) {
    // Set url
    $url = getenv('ENVIRONMENT_BASE_URL'). 'tasks/saved-searches/' . $selfServiceResponse[0]['id'];   
}

return $url;