<?php 
/**********************************
 * IN - DHS.01 Post Processing 
 *
 * by Favio Mollinedo
 *********************************/
require_once("/Northleaf_PHP_Library.php");
/* Call Processmaker API with Guzzle
 *
 * @param (Array) $record
 * @param (Int) $collectionID
 * @return (Array) $response["id"]
 *
 * by Favio Mollinedo
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
//Get Global variables
$apiHost = getenv("API_HOST");
$apiPackageSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiPackageSql;

//Get collections IDs
$collectionName = "IN_COMMENTS_LOG";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get role from task
$userRole = isset($data["submittorRoleInfo"]) && $data["submittorRoleInfo"] !== null 
    ? $data["submittorRoleInfo"] 
    : "Submitter";

//Save comments into collection
$dataRecord = [
    "IN_CL_CASE_NUMBER" => $data['_request']['case_number'],
    "IN_CL_REQUEST_ID" => $data['_request']['id'],
    "IN_CL_USER" => $data["currentUser"]["fullname"],
    "IN_CL_USER_ID" => $data["currentUser"]["id"],
    "IN_CL_ROLE" => $userRole,
    "IN_CL_ROLE_ID" => 2,
    "IN_CL_APPROVAL" => $data["IN_SUBMITTER_MANAGER_ACTION"],
    "IN_CL_COMMENT_SAVED" => empty($data['IN_COMMENT_SUBMITTER']) ? "" : $data['IN_COMMENT_SUBMITTER'],
    "IN_CL_DATE" => date("m/d/Y"),
    "IN_SUBMIT" => null,
];
//$getResponse = !empty($data['_request']['id']) ? postRecordToCollection($dataRecord, $collectionID) : null;
$getResponse = $data['IN_SAVE_SUBMIT'] == 'SUBMIT' ? postRecordToCollection($dataRecord, $collectionID) : null;

return [
    "RESPONSE_COMMENTS" => $getResponse,
    //"IN_SUBMITTER_MANAGER" => $data['IN_SAVE_SUBMIT'] == 'SUBMIT' ? null : $data['IN_SUBMITTER_MANAGER'],
    //"IN_COMMENT_SUBMITTER" => null,
    //"IN_SAVE_SUBMIT" => null,
    "IN_SUBMITTER_MANAGER_ID" => $data["IN_SUBMITTER_MANAGER"]["ID"]
];