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
//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

$teamID    = $config['teamID'];
$requestId = $data['_request']['id'];

$userRole = $teamID . " Ops Reviewer";
$comments = empty($data['IN_COMMENT_'.$teamID]) ? "" : $data['IN_COMMENT_'.$teamID];

//Save comments into collection
$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => $data["currentUser_".$teamID]["fullname"],
    "IN_CL_USER_ID" => $data["currentUser_".$teamID]["id"],
    "IN_CL_ROLE" => $userRole,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => null,
    "IN_CL_COMMENT_SAVED" => $comments,
    "IN_CL_DATE" => date("m/d/Y H:i:s"),
    "IN_SUBMIT" => null,
];
$getResponse    = postRecordToCollection($dataRecord, getCollectionId("IN_COMMENTS_LOG", $apiUrl));
$commentLogData = getCommentsLog(getCollectionId("IN_COMMENTS_LOG", $apiUrl), $apiUrl, $requestId);


if($teamID == 'INFRA'){
   return [
      "SUBMIT_INFRA" => null,
      "SUBMIT_02_INFRA" => null,
      "copySubmitForm_INFRA" => null,
      "Allocated_INFRA" => true,
      "IN_COMMENT_LOG" => $commentLogData,
      "IN_INH_ACTION" => null,
      "IN_INH_Comments" => "",
      "readyScreen" => null
   ];
}

if($teamID == 'PC'){
   return [
      "SUBMIT_PC" => null,
      "SUBMIT_02_PC" => null,
      "copySubmitForm_PC" => null,
      "Allocated_PC" => true,
      "IN_COMMENT_LOG" => $commentLogData,
      "readyScreen" => null
   ];
}

if($teamID == 'PE'){
   return [
      "SUBMIT_PE" => null,
      "SUBMIT_02_PE" => null,
      "copySubmitForm_PE" => null,
      "Allocated_PE" => true,
      "IN_COMMENT_LOG" => $commentLogData,
      "readyScreen" => null
   ];
}

if($teamID == 'CORP'){
   return [
      "SUBMIT_CORP" => null,
      "SUBMIT_02_CORP" => null,
      "copySubmitForm_CORP" => null,
      "Allocated_CORP" => true,
      "IN_COMMENT_LOG" => $commentLogData,
      "readyScreen" => null
   ];
}

return [   
   "Allocated" => true
];


/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by 
*/
function postRecordToCollection($record, $collectionID){
    try{
        $pmheaders = [
            'Authorization' => 'Bearer ' . getenv('API_TOKEN'),        
            'Accept'        => 'application/json',
        ];
        $apiHost = getenv('API_HOST');
        $client = new GuzzleHttp\Client(['verify' => false]);

        $res = $client->request("POST", $apiHost ."/collections/$collectionID/records", [
                    "headers" => $pmheaders,
                    "http_errors" => false,
                    "json" => [
                        "data" => $record
                        ]
                    ]);
        if ($res->getStatusCode() == 201){
            $response = json_decode($res->getBody(), true);
            return $response["id"];
        }
        return "Status Code " . $res->getStatusCode() . ".  Unable to Save";
        
    }
    catch(\Exception $e){
        return $e->getMessage();
    }
}

/**
 * Get the comment logs from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getCommentsLog($ID, $apiUrl, $requestId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT LOG.data->>'$.IN_CL_REQUEST_ID' AS REQUEST_ID,
                        LOG.data->>'$.IN_CL_USER' AS IN_COMMENT_USER,
                        LOG.data->>'$.IN_CL_USER_ID' AS IN_COMMENT_USER_ID,
                        LOG.data->>'$.IN_CL_ROLE' AS IN_COMMENT_ROLE,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_APPROVAL', 'null'), '') AS IN_COMMENT_APPROVAL,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_COMMENT_SAVED', 'null'), '') AS IN_COMMENT_SAVED,
                        LOG.data->>'$.IN_CL_DATE' AS IN_COMMENT_DATE
                        FROM collection_" . $ID . " AS LOG
                        WHERE LOG.data->>'$.IN_CL_REQUEST_ID' = " . $requestId . " 
                        ORDER BY IN_COMMENT_DATE ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}