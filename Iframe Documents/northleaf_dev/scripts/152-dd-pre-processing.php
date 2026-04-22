<?php 
/*  
 *  Pre-processing Script
 *  Created by Telmo Chiri
 */
 //Get Global Variables
$apiHost = getenv('API_HOST');
//Check if API_SQL exist
if (!getenv('API_SQL')) {
    $apiInstance = $api->environmentVariables();
    $environment_variable_editable = new \ProcessMaker\Client\Model\EnvironmentVariableEditable();
    $environment_variable_editable->setName('API_SQL');
    $environment_variable_editable->setDescription('API_SQL');
    $environment_variable_editable->setValue('/admin/package-proservice-tools/sql');
    $newEnvVar = $apiInstance->createEnvironmentVariable($environment_variable_editable);
}
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$processId = $data["_request"]["process_id"] ?? false; 

//We check if the table for the logs was created
$query = "SHOW TABLES LIKE 'PMB_DAILY_DIGEST_LOGS'";
$existTable = apiGuzzle($apiUrl, "POST", encodeSql($query));
$createTableResponse = $existTable;
if (count($existTable) == 0) {
    //Create Log Table
    $queryCreateTable = "CREATE TABLE PMB_DAILY_DIGEST_LOGS (
                                        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                        email VARCHAR(150),
                                        time_zone VARCHAR(100),
                                        date_time_server TIMESTAMP NOT NULL,
                                        date_time_receiver TIMESTAMP NOT NULL,
                                        subject VARCHAR(150),
                                        body MEDIUMTEXT,
                                        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                                    )";
    $createTableResponse = apiGuzzle($apiUrl, "POST", encodeSql($queryCreateTable));
}
//Delete Old record
$numberMonths = 1;
$queryDeleteOldLogs = "DELETE 
                        FROM PMB_DAILY_DIGEST_LOGS
                        WHERE registration_date < DATE_SUB(CURDATE(), INTERVAL $numberMonths MONTH)";
apiGuzzle($apiUrl, "POST", encodeSql($queryDeleteOldLogs));
if ($processId) {
    //Delete Tasks
    $queryDeleteOldTasks = "DELETE
                            FROM process_request_tokens
                            WHERE process_id = 00
                                AND status = 'COMPLETED'
                                AND completed_at < DATE_SUB(CURDATE(), INTERVAL $numberMonths MONTH);";
    apiGuzzle($apiUrl, "POST", encodeSql($queryDeleteOldTasks));
}
return [
    "ATTEMPT_COUNTER" => 1,
    "EXIST_TABLE" => (bool)$createTableResponse
];

/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $url
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res
 *
 * by Elmer Orihuela 
 */
function apiGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        //$res = json_decode($res, true);
         if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}
/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Elmer Orihuela
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

return [];