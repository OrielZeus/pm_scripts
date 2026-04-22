<?php 
/**
 * PHP script to retrieve process list.
 * by Bruno Montecinos Bailey
 */

// Start PM configuration
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
$urlHost = $_SERVER["HOST_URL"];
$apiHost = getenv("API_HOST");
$apiToken = getenv("API_TOKEN");

//Daily Difest Collection Table
$collectionConfiguration = "collection_" . getenv("PMB_COLLECTION_ID_DAILY_DIGEST_CONFIGURATION");

//Get Body Email Configurations
$sql = "";
$sql .= " SELECT C.data->>'$.ID' AS 'ID', ";
$sql .= "        C.data->>'$.VALUE' AS 'VALUE', ";
$sql .= " 	     C.data->>'$.DESCRIPTION' AS 'DESCRIPTION' ";
$sql .= " FROM " . $collectionConfiguration . " C ";
$sql .= " WHERE C.data->>'$.ID' = 'ALL_PROCESSES' ";
$responseQuery = executeSQL($sql);
$allProcesses = "";
if (count($responseQuery) > 0) {
    $allProcesses = $responseQuery[0]["VALUE"];
}
if($allProcesses == "YES"){
    return true;
} else {
    return false;
}

/**
 * Execute Query
 *
 * Function to execute query
 *
 * @param $sql (string)
 *
 * @return array
 */
function executeSQL($sql) {
    global $apiToken, $apiHost, $apiToken;
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
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request("POST", $apiHost . $url, $headers, json_encode($postfiles));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        // Captura la excepción y obtén el mensaje de error
        $errorMessage = $exception->getMessage();
        $responseBody = $exception->getResponse() ? $exception->getResponse()->getBody(true) : '';
        $res = json_decode($responseBody, true);
        if (!$res) {
            // Si no se pudo decodificar la respuesta como JSON, devolvemos el mensaje de error original
            $res = ['error' => $errorMessage];
        }
    }
    $res = json_decode($res, true);
    return $res;
}