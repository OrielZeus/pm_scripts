<?php  
/********************************************
 * Bulk Reassignment - Get users to reassign
 *
 * by Cinthia Romero
 * Modified by Telmo Chiri
 *******************************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get list of selected cases
$selectedRows = empty($data["selectedRows"]) ? array() : $data["selectedRows"];
$differentNodes = empty($data["differentNodes"]) ? "NO" : $data["differentNodes"];
$currentUser = empty($data["currentUser"]) ? "NO" : $data["currentUser"];

//Initialize Variables
$tasksUsersArray = array();

//Check if the selected cases are from different nodes
if ($differentNodes == "YES") {
    foreach ($selectedRows as $row) {
        //Obtain delegation id and node user from row id
        $delegationInFo = explode("_", $row[0]);
        $delegationId = $delegationInFo[0];
        $maxSize = sizeof($delegationInFo);
        //$nodeUser = $delegationInFo[3];
        $nodeUser = $delegationInFo[$maxSize - 1];
        $tasksUsersArray[] = array(
            "DELEGATION_ID" => $delegationId,
            "USERS_TO_REASSIGN" => getUsersList($nodeUser),
            "CASE_NUMBER" => $row[1],
            "TASK_TITLE" => $row[3]
        );
    }
} else {
    $delegationInFo = explode("_", $selectedRows[0][0]);
    $maxSize = sizeof($delegationInFo);
    //$nodeUser = $delegationInFo[3];
    $nodeUser = $delegationInFo[$maxSize - 1];
    $tasksUsersArray = getUsersList($nodeUser);
}
return $tasksUsersArray;

/**
 * Get Users List
 *
 * @param string $nodeUser
 * @param string $currentUser
 * @return array $usersList
 *
 * by Cinthia Romero
 */
function getUsersList($nodeUser)
{
    global $apiUrl;
    $usersList = array();
    $queryUsers = "SELECT U.id AS USER_ID,
                          CONCAT(U.firstname, ' ', U.lastname) AS USER_FULLNAME
                   FROM users AS U
                   INNER JOIN group_members AS GM
                       ON GM.member_id = U.id
                   WHERE GM.member_id != " . $nodeUser . "
                       AND U.status = 'ACTIVE'
                       AND GM.group_id IN (SELECT group_id
					                       FROM group_members
					                       WHERE member_id = " . $nodeUser . ")";
    $usersListResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUsers));
    if (!empty($usersListResponse[0]["USER_ID"])) {
        $usersList = $usersListResponse;
    }
    return removeDuplicatesAndSort($usersList);
}
/**
 * Remove Duplicates and Sort Array
 * @param array $array
 * @return array $uniqueArray
 *
 * by Telmo Chiri
 **/
function removeDuplicatesAndSort($array) {
    $uniqueArray = [];
    $userIds = [];

    // Remove Duplicates
    foreach ($array as $item) {
        if (!in_array($item['USER_ID'], $userIds)) {
            $userIds[] = $item['USER_ID']; // Save USER_ID for duplicates
            $uniqueArray[] = $item; // Add the unique element
        }
    }
    // Sort by USER_FULLNAME
    usort($uniqueArray, function ($a, $b) {
        return strcmp($a['USER_FULLNAME'], $b['USER_FULLNAME']);
    });
    return $uniqueArray;
}
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