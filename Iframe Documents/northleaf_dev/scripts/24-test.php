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
$track =[];

// New Version
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

// Query to verify which documents were uploaded to the case number
$queryCheckInputFilesInCollection = "SELECT id, data->>'$.HFC_FILE_ID' AS HFC_FILE_ID, 
                                            data->>'$.HFC_REQUEST_ID' AS HFC_REQUEST_ID, 
                                            data->>'$.HFC_CASE_NUMBER' AS HFC_CASE_NUMBER
                                    FROM collection_" . $idCollectionHistoryFilesByCase . "
                                    WHERE data->>'$.HFC_CASE_NUMBER' = '". $caseNumber ."' 
                                        AND data->>'$.HFC_STATUS' = 'ACTIVE'";
$inputFilesCollectionResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckInputFilesInCollection));

//Get Parent Task Info
$queryTaskInfo = "SELECT element_id, element_name, process_request_id, subprocess_request_id 
                FROM process_request_tokens 
                WHERE element_id = '" . $prevNodeId . "' 
                LIMIT 1";
$taskInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryTaskInfo));

//Set collection url
$insertResponseUrl = $apiHost . "/collections/" . $idCollectionHistoryFilesByCase . "/records";
$responseTest = array();
$filesInserted = 0;
//return $filteredDataController;
foreach($filteredDataController as $controller) {
    //Get the File ID within the request data
    $fileUpload = $data[$controller['VARIABLE']];
    $fileUpload = (int)$fileUpload;
    $fileUpload = 0;
    if ($fileUpload === 0) {
        $conditionToSkip = '';
        //Check the configuration of the specific file
        if ($controller['CONDITIONS_FOR_NON_REQUIRED']) {
            foreach($controller['CONDITIONS'] as $condition){
                $valueInData = ($data[$condition['CONDITION_VARIABLE']] == false ? 0 : ($data[$condition['CONDITION_VARIABLE']] == true ? 1 : $data[$condition['CONDITION_VARIABLE']]) );
                $valueToCompare = ($condition['CONDITION_VALUE'] == 'true' ? true : ($condition['CONDITION_VALUE'] == 'false' ? false : $condition['CONDITION_VALUE']) );
                $conditionToSkip .= " ".$valueInData . " " . $condition['CONDITION_EVALUATE'] . " " . $valueToCompare . " " . $condition['CONDITION_NEXT_CONDITION'];
            }
            //If the condition for the file not to be required is met, it skips to the next file to process.
            if (eval("return " . $conditionToSkip . ";")) {
                continue;
            }
        }
        //Wait for File ID in request data
        do {
            $sql = "SELECT data->>'$." . $controller['VARIABLE'] . "' AS  ".$controller['VARIABLE']."
                    FROM process_requests 
                    WHERE id = " . $requestId;
            $responseData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sql));
            $fileUpload = (int) $responseData[0][$controller['VARIABLE']];
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
                "HFC_TASK_ID" => $taskInfo[0]["element_id"],
                "HFC_TASK_NAME" => $taskInfo[0]["element_name"],
                "HFC_STATUS" => 'ACTIVE'
            ]
        ];
        $filesInserted++;
        //return [$insertValues];
        //callApiUrlGuzzle($insertResponseUrl, "POST", $insertValues);
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