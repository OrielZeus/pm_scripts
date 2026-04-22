<?php
/**********************************************
 * PE - Save history of uploaded files
 *
 * by Telmo Chiri
*********************************************/
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
//Set initial values
$dataReturn = array();
$requestId = $data['_parent']['request_id'] ?? $data["_request"]["id"];
$caseNumber = $data["_request"]["case_number"];
$prevNodeId = $config['prev_node'] ?? ($data['_parent']['node_id'] ?? '');

//Get collections IDs
$collectionNames = array("HISTORY_FILES_BY_CASE", "PE_UPLOAD_FILE_HISTORY_CONTROLLER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);

$idCollectionHistoryFilesByCase = $collectionsInfo["HISTORY_FILES_BY_CASE"] ?? false;
$idCollectionPeUploadFileHistoryController = $collectionsInfo["PE_UPLOAD_FILE_HISTORY_CONTROLLER"] ?? false;

// Obtain the Collection configuration of the Task and Input Files validator
$urlCollection = $apiHost . '/collections/'. $idCollectionPeUploadFileHistoryController .'/records';
$dataCollectionControllerInputFiles = callApiUrlGuzzle($urlCollection, 'GET');
$validatorTaskAndInputFile =  $dataCollectionControllerInputFiles['data'][0]['data']['TASK_FILES_SETTINGS'];

//Get list of Input Files by Task
$filteredDataController = filterByTaskNode($validatorTaskAndInputFile, $prevNodeId);

//Clear deleted file records from the collection
$querydeletedInputFilesInCollection = "DELETE FROM collection_" . $idCollectionHistoryFilesByCase . "
                                        WHERE id IN (
                                                    SELECT id FROM (
                                                                    SELECT DISTINCT HF.id 
                                                                    FROM collection_" . $idCollectionHistoryFilesByCase . " AS HF
                                                                    LEFT JOIN media AS M ON M.id = HF.data->>'$.HFC_FILE_ID'
                                                                        WHERE HF.data->>'$.HFC_CASE_NUMBER' = '". $caseNumber ."' 
                                                                        AND HF.data->>'$.HFC_STATUS' = 'ACTIVE'
                                                                        AND M.id IS NULL
                                                                ) AS temp_table
                                                    )";
callApiUrlGuzzle($apiUrl, "POST", encodeSql($querydeletedInputFilesInCollection));

// Query to verify which documents were uploaded to the case number
$queryCheckInputFilesInCollection = "SELECT id, data->>'$.HFC_FILE_ID' AS HFC_FILE_ID, 
                                            data->>'$.HFC_REQUEST_ID' AS HFC_REQUEST_ID, 
                                            data->>'$.HFC_CASE_NUMBER' AS HFC_CASE_NUMBER
                                    FROM collection_" . $idCollectionHistoryFilesByCase . "
                                    WHERE data->>'$.HFC_CASE_NUMBER' = '". $caseNumber ."' 
                                        AND data->>'$.HFC_STATUS' = 'ACTIVE'";
$inputFilesCollectionResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckInputFilesInCollection));

//Set collection url
$insertResponseUrl = $apiHost . "/collections/" . $idCollectionHistoryFilesByCase . "/records";
$responseTest = array();
$filesInserted = 0;

foreach($filteredDataController as $key => $controller) {
    $nodeData = explode(".", $controller['VARIABLE']);
    //Loop or Multiple Files
    if (count($nodeData) > 1) {
        $arrayName = $nodeData[0];
        $nodeName = $nodeData[1];
        $conditionToSkip = '';
        //Check the configuration of the specific file
        if ($controller['CONDITIONS_FOR_NON_REQUIRED']) {
            foreach($controller['CONDITIONS'] as $condition) {
                switch(gettype($data[$condition['CONDITION_VARIABLE']])) {
                    case 'boolean':
                        $valueInData = (int) $data[$condition['CONDITION_VARIABLE']];
                        break;
                    case 'array':
                        $valueInData = (boolean) $data[$condition['CONDITION_VARIABLE']];
                        break;
                    case 'string':
                        $valueInData = "'" . $data[$condition['CONDITION_VARIABLE']] . "'";
                        break;
                    case 'NULL':
                        $valueInData = "'null'";
                        break;
                    default:
                        $valueInData = $data[$condition['CONDITION_VARIABLE']];
                    break;
                }
                $valueToCompare = ($condition['CONDITION_VALUE'] == 'true' ? true : ($condition['CONDITION_VALUE'] == 'false' ? false : "'" . $condition['CONDITION_VALUE'] . "'") );
                $conditionToSkip .= " ".$valueInData . " " . $condition['CONDITION_EVALUATE'] . " " . $valueToCompare . " " . $condition['CONDITION_NEXT_CONDITION'];
            }
            //If the condition for the file not to be required is met, it skips to the next file to process.
            if (eval("return " . $conditionToSkip . ";")) {
                continue;
            }
        }
        //Check if array is load
        if (gettype($data[$arrayName]) != 'array') {
            $multipleFilesUpload = 0;
            //Wait for File ID in request data
            do {
                $sql = "SELECT data->>'$." . $arrayName . "' AS  ".$arrayName."
                    FROM process_requests 
                    WHERE id = " . $data["_request"]["id"];
                $responseData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
                $multipleFilesUpload = (int) $responseData[0][$nodeName];
            } while ($multipleFilesUpload === 0);
            $data[$arrayName] = $responseData[0][$nodeName];
        }
        foreach($data[$arrayName] as $index => $loopFile) {
            //Get File ID
            $fileUpload = $data[$arrayName][$index][$nodeName] ?? 0;
            $fileUpload = (int)$fileUpload;
            if ($fileUpload === 0) {
                $conditionToSkip = '';
                //Check the configuration of the specific file
                if ($controller['CONDITIONS_FOR_NON_REQUIRED']) {
                    foreach($controller['CONDITIONS'] as $condition){
                        $valueToCompare = ($condition['CONDITION_VALUE'] == 'true' ? true : ($condition['CONDITION_VALUE'] == 'false' ? false : "'" . $condition['CONDITION_VALUE'] . "'") );
                        switch(gettype($data[$condition['CONDITION_VARIABLE']])) {
                            case 'boolean':
                                $valueInData = (int) $data[$condition['CONDITION_VARIABLE']];
                                break;
                            case 'array':
                                $valueInData = (boolean) $data[$condition['CONDITION_VARIABLE']];
                                if (count($data[$condition['CONDITION_VARIABLE']]) == 0) {
                                    $valueInData = $valueToCompare;
                                }
                                break;
                            case 'string':
                                $valueInData = "'" . $data[$condition['CONDITION_VARIABLE']] . "'";
                                break;
                            case 'NULL':
                                $valueInData = "'null'";
                                break;
                            default:
                                $valueInData = $data[$condition['CONDITION_VARIABLE']];
                            break;
                        }
                        $conditionToSkip .= " ".$valueInData . " " . $condition['CONDITION_EVALUATE'] . " " . $valueToCompare . " " . $condition['CONDITION_NEXT_CONDITION'];
                    }
                    //If the condition for the file not to be required is met, it skips to the next file to process.
                    if (eval("return " . $conditionToSkip . ";")) {
                        continue;
                    }
                }
                //Wait for File ID in request data
                do {
                    $sql = "SELECT JSON_EXTRACT(data->>'$." . $arrayName . "[".$index."]', '$.". $nodeName ."') AS  ".$nodeName."
                        FROM process_requests 
                        WHERE id = " . $data["_request"]["id"];
                    $responseData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
                    $fileUpload = (int) $responseData[0][$nodeName];
                } while ($fileUpload === 0);
            }
            //Save in Collection
            if (!is_in_array($inputFilesCollectionResponse, 'HFC_FILE_ID', $fileUpload) && $fileUpload != 0) {
                //Get information from the Media table
                $queryCheckInputFiles = "SELECT M.*, M.custom_properties->>'$.updatedBy' AS user_id, CONCAT(U.firstname, ' ', U.lastname) AS user_fullname
                                        FROM media AS M 
                                        LEFT JOIN users AS U ON U.id = M.custom_properties->>'$.updatedBy'
                                        WHERE M.id = '".$fileUpload."'
                                            AND M.model_type= 'ProcessMaker\\\\Models\\\\ProcessRequest'
                                        ORDER BY M.id DESC";
                $inputFilesResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckInputFiles));
                $inputFile = $inputFilesResponse[0];
                // Insert new Record in Collection
                //Set array of values to insert in collection
                $insertValues = [
                    'data' => [
                        "HFC_CASE_NUMBER" => $caseNumber,
                        "HFC_REQUEST_ID" => $requestId,
                        "HFC_FILE_ID" => $inputFile["id"],
                        "HFC_FILE_NAME" => $inputFile["file_name"],
                        "HFC_USER_ID" => $inputFile["user_id"],
                        "HFC_USER_NAME" => $inputFile["user_fullname"],
                        "HFC_TASK_ID" => $controller['TASK_NODE'],
                        "HFC_TASK_NAME" => $controller['TASK_NAME'],
                        "HFC_STATUS" => 'ACTIVE'
                    ]
                ];
                $filesInserted++;
                callApiUrlGuzzle($insertResponseUrl, "POST", $insertValues);
            }
        }
    } else if (count($nodeData) == 1) {
        $nodeName = $nodeData[0];   // --> $controller['VARIABLE']
        $fileUploadArray = $data[$nodeName];

        //Add validation for multiple files - Ana 2025-03-02
        //Set as array if it is not multiple files
        if (!is_array($fileUploadArray)) {
            $auxFileUpload = $fileUploadArray;
            $fileUploadArray = array();
            $fileUploadArray[0] = array();
            $fileUploadArray[0]["file"] = $auxFileUpload;
        }
        ///------ END

        foreach ($fileUploadArray as $fileUpload) {
            $fileUpload = (int)$fileUpload["file"];
            if ($fileUpload === 0) {
                $conditionToSkip = '';
                //Check the configuration of the specific file
                if ($controller['CONDITIONS_FOR_NON_REQUIRED']) {
                    foreach($controller['CONDITIONS'] as $condition){
                        $valueToCompare = ($condition['CONDITION_VALUE'] == 'true' ? true : ($condition['CONDITION_VALUE'] == 'false' ? false : "'" . $condition['CONDITION_VALUE'] . "'") );
                        switch(gettype($data[$condition['CONDITION_VARIABLE']])) {
                            case 'boolean':
                                $valueInData = (int) $data[$condition['CONDITION_VARIABLE']];
                                break;
                            case 'array':
                                $valueInData = (boolean) $data[$condition['CONDITION_VARIABLE']];
                                if (count($data[$condition['CONDITION_VARIABLE']]) == 0) {
                                    $valueInData = $valueToCompare;
                                }
                                break;
                            case 'string':
                                $valueInData = "'" . $data[$condition['CONDITION_VARIABLE']] . "'";
                                break;
                            case 'NULL':
                                $valueInData = "'null'";
                                break;
                            default:
                                $valueInData = $data[$condition['CONDITION_VARIABLE']];
                            break;
                        }
                        $conditionToSkip .= " ".$valueInData . " " . $condition['CONDITION_EVALUATE'] . " " . $valueToCompare . " " . $condition['CONDITION_NEXT_CONDITION'];
                    }
                    //If the condition for the file not to be required is met, it skips to the next file to process.
                    if (eval("return " . $conditionToSkip . ";")) {
                        continue;
                    }
                }
                //Wait for File ID in request data
                do {
                    $sql = "SELECT data->>'$." . $nodeName . "' AS  ".$nodeName."
                            FROM process_requests 
                            WHERE id = " . $data["_request"]["id"];
                    $responseData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
                    $fileUpload = (int) $responseData[0][$nodeName];
                } while ($fileUpload === 0);
            }
            
            //Save in Collection
            if (!is_in_array($inputFilesCollectionResponse, 'HFC_FILE_ID', $fileUpload) && $fileUpload != 0) {
                //Get information from the Media table
                $queryCheckInputFiles = "SELECT M.*, M.custom_properties->>'$.updatedBy' AS user_id, CONCAT(U.firstname, ' ', U.lastname) AS user_fullname
                                        FROM media AS M 
                                        LEFT JOIN users AS U ON U.id = M.custom_properties->>'$.updatedBy'
                                        WHERE M.id = '".$fileUpload."'
                                            AND M.model_type= 'ProcessMaker\\\\Models\\\\ProcessRequest'
                                        ORDER BY M.id DESC";
                $inputFilesResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckInputFiles));
                $inputFile = $inputFilesResponse[0];
                // Insert new Record in Collection
                //Set array of values to insert in collection
                $insertValues = [
                    'data' => [
                        "HFC_CASE_NUMBER" => $caseNumber,
                        "HFC_REQUEST_ID" => $requestId,
                        "HFC_FILE_ID" => $inputFile["id"],
                        "HFC_FILE_NAME" => $inputFile["file_name"],
                        "HFC_USER_ID" => $inputFile["user_id"],
                        "HFC_USER_NAME" => $inputFile["user_fullname"],
                        "HFC_TASK_ID" => $controller['TASK_NODE'],
                        "HFC_TASK_NAME" => $controller['TASK_NAME'],
                        "HFC_STATUS" => 'ACTIVE'
                    ]
                ];
                $filesInserted++;
                callApiUrlGuzzle($insertResponseUrl, "POST", $insertValues);
            }
        }
    }
}
$dataReturn["history_track"] = array_merge(
                                            $data["history_track"] ?? [], 
                                            [$prevNodeId => $filesInserted]
                                        );
return $dataReturn;

/***
* Search in Associative Array
* by Telmo Chiri
***/
function is_in_array($array, $key, $key_value){
    $within_array = false;
    foreach( $array as $k=>$v ){
        if( is_array($v) ){
            $within_array = is_in_array($v, $key, $key_value);
            if( $within_array == true ){
                break;
            }
        } else {
            if( $v == $key_value && $k == $key ){
                $within_array = true;
                break;
            }
        }
    }
    return $within_array;
}

// Función para filtrar por TASK_NODE específico
function filterByTaskNode($data, $taskNode) {
    return array_filter($data, function($item) use ($taskNode) {
        return $item['TASK_NODE'] === $taskNode && $item['STATUS'] == 'ACTIVE';
    });
}