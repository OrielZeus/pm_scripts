<?php 

/*************************************************** 
 *  Get user_id, element_name, again from task API
 *  By Ignacio Cardozo
 *************************************************/

//**Call Api

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

function apiGuzzle($url, $requestType, $postfiles)
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
    $request = new Request($requestType, $url, $headers, json_encode($postfiles));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $res = json_decode($res, true);
    return $res;
}

//**Use Variables

//**Global Variables
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');

$url = $apiHost . "/tasks?process_request_id=717&order_direction=asc";
$getScreenInfo = apiGuzzle($url, "GET", []);

$response = $getScreenInfo["data"];
//return $response;

$userData = [];

if ($response && is_array($response)) {
    foreach ($response as $user) {
        if (isset($user['user_id']) && isset($user['element_name'])) {
            $userId = $user['user_id'];
            $elementName = $user['element_name'];
            $name_user=$user["user"]["username"];
            
            $userData[] = ['user_id' => $userId,
                           'user_name'=> $name_user, 
                           'element_name' => $elementName];
        }
    }
} 

return $userData;


//Prueba Sql

/*Consulta SQL
SELECT DISTINCT
prt.id,
prt.user_id,
prt.element_id,
prt.element_name,
prt.element_type,
prt.process_request_id,
pta.assignment_type
FROM process_request_tokens AS prt
LEFT JOIN process_task_assignments AS pta
ON prt.element_id = pta.process_task_id
AND prt.process_id = pta.process_id
WHERE prt.element_type = 'task' AND prt.process_request_id = 715 
ORDER BY prt.id;*/

//$Configuration = "process_request_tokens" . getenv("process_request_tokens");
//Obteniendo la información de la configuración
/*$Configuration = $getScreenInfo;

// Creando la consulta SQL
$sql = "";
$sql .= " SELECT DISTINCT prt.id,";
$sql .= "        prt.user_id,";
$sql .= "        prt.element_id,";
$sql .= "        prt.element_type,";
$sql .= "        prt.process_request_id,";
$sql .= "        pta.assignment_type";
$sql .= " FROM process_request_token AS prt "; 
$sql .= " LEFT JOIN process_task_assignments AS pta";
$sql .= " ON prt.element_id = pta.process_task_id "; 
$sql .= " AND prt.process_id = pta.process_id";
$sql .= " WHERE prt.element_type = 'task' "; // AND prt.process_request_id = 175";
$sql .= " ORDER BY prt.id ";

// Ejecutando la consulta SQL
$responseQuery = executeSQL($sql);

// Función para ejecutar la consulta SQL
function executeSQL($sql) {
    global $apiToken, $apiHost;
    $sql = base64_encode($sql);
    $url = '/admin/package-proservice-tools/sql';
    $postfiles = [
        'SQL' => $sql
    ];
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new \GuzzleHttp\Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request("GET", $apiHost . $url, $headers, json_encode($postfiles));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $errorMessage = $exception->getMessage();
        $responseBody = $exception->getResponse() ? $exception->getResponse()->getBody(true) : '';
        $res = json_decode($responseBody, true);
        if (!$res) {
            $res = ['error' => $errorMessage];
        }
    }
    $res = json_decode($res, true);
    return $res;
}

return $responseQuery;*/