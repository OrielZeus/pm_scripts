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


$loop = $data["LOOP"];

foreach ($loop as &$item) {
    $item["CH_FIELD_1"] = $data["CH_FIELD_1"];
    $item["CH_FIELD_2"] = $data["CH_FIELD_2"];
}

 return ["LOOP" => $loop];