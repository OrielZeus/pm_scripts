<?php 
/***********************************************************
 * Bulk Reassignment - Check user is able to see all cases
 *
 * by Cinthia Romero 
 **********************************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Cinthia Romero
 */ 
function callApiUrlGuzzle($url, $method, $sendData)
{
    global $apiToken, $apiHost;
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request($method, $url, $headers, json_encode($sendData));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $executionResponse = json_decode($res, true);
    return $executionResponse;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$bulkReassignmentSettingsCollectionID = getenv('BULK_REASSIGNMENT_SETTINGS_COLLECTION_ID');

//Set Initial Values
$currentUser = $data["CURRENT_USER"];

//Check if user is Admin
$userCanSeeAllCases = "NO";
$queryUserIsAdmin = "SELECT is_administrator
                     FROM users
                     WHERE id = '" . $currentUser . "'";
$userIsAdminResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUserIsAdmin));
if (!empty($userIsAdminResponse[0]["is_administrator"]) && $userIsAdminResponse[0]["is_administrator"] == 1) {
    $userCanSeeAllCases = "YES";
}
if ($userCanSeeAllCases == "NO") {
    //Get Dashboard Settings
    $queryBulkReassignmentSettings = "SELECT data->>'$.BRS_ALL_CASES_GROUP.id' AS BRS_ALL_CASES_GROUP
                                      FROM collection_" . $bulkReassignmentSettingsCollectionID . "
                                      ORDER BY id DESC 
                                      LIMIT 1";
    $bulkReassignmentSettingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBulkReassignmentSettings));
    if (!empty($bulkReassignmentSettingsResponse[0]["BRS_ALL_CASES_GROUP"])) {
        //Check if current user is able to see all cases of all users
        $groupToSeeAllCases = json_decode($dashboardSettingsResponse[0]["DASHBOARD_VIEW_ALL_CASES_GROUP"], true);
        $queryBelongsToGroup = "SELECT member_id 
                                FROM group_members 
                                WHERE member_id = " . $currentUser . "
                                    AND group_id = " . $bulkReassignmentSettingsResponse[0]["BRS_ALL_CASES_GROUP"];
        $userBelongsToGroupResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBelongsToGroup));
        if (!empty($userBelongsToGroupResponse[0]["member_id"])) {
            $userCanSeeAllCases = "YES";
        }
    }
}
return $userCanSeeAllCases;