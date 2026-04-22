<?php 
/********************************
 * Get Group Users - Watcher Collection
 *
 * by Helen Callisaya
 *******************************/
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

//Get global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get Variable
$groupId = $data['groupId'] ?? '';

//Set initial values
$dataReturn = [];

if (!empty($groupId)) {
    $queryGroupUsers = "SELECT U.id AS ID,
                               CONCAT(U.firstname, ' ', U.lastname) AS FULL_NAME
                        FROM users AS U
                        INNER JOIN group_members AS G
                            ON G.member_id = U.id
                        WHERE G.group_id = " . $groupId . " AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
                        ORDER BY U.lastname ASC";
    $groupUsersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupUsers));
    $dataReturn['USERS_GROUP'] = $groupUsersResponse;
}

return $dataReturn;