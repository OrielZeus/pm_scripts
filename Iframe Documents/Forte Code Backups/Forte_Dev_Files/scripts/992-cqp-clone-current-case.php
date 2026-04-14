<?php 

/*****************************************
* Clone the current request
*
* by Diego Tapia
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
require_once("/CQP_Generic_Functions.php");

// Set initial Variables
$apiInstanceRequest = $api->processRequests();
$apiInstanceProcess = $api->processes();
$apiInstanceFiles = $api->requestFiles();
$currentRequestId = $data["_request"]["id"];
$returnData = $data;
unset($returnData["CQP_CLONE_PARENT"]);
$returnData["CQP_ACTION"] = "NEW";

$body = new \stdClass; 
$body->CQP_CLONE_PARENT = $currentRequestId;
$body->CQP_STARTER_USER = $data["CQP_STARTER_USER"];

// Set manual replace variables/values
$manualFiles = [
];

$manualVariables = [
    [
        "original" => "CQP_SLIP",
        "copy" => "CQP_GEMINI_FILE_VALIDATE",
        "condition" => [
            "variable" => "CQP_SUBMIT_BROKER",
            "value" => "CLONE"
        ]
    ],
    [
        "original" => "CQP_NEW_FILE_UPLOAD",
        "copy" => "CQP_GEMINI_FILE_COMPARE",
        "condition" => [
            "variable" => "CQP_SUBMIT_BROKER",
            "value" => "CLONE"
        ]
    ]
];

$cleanVariables = [
    "CQP_STATUS",
    "CQP_FILE_LIST",
    "CQP_BROKER_CURRENT_USER",
    "CQP_REQUEST_STARTER",
    "CQP_ADOBE_WORKFLOW_SELECTED",
    "CQP_ADOBE_WORKFLOW_LIST",
    "CQP_REPLACE_SLIP"
];

// Get new request
$newRequest = $data["CQP_NEW_REQUEST"];

// Upload Files to the new request
$listFiles = [];
$currentFiles = $apiInstanceFiles->getRequestFiles($currentRequestId);

foreach ($currentFiles->getData() as $file) {
    $indexFile = findValuePath($data, $file->getId(), $file["custom_properties"]["data_name"]);
    $setFile = $apiInstanceFiles->getRequestFilesById($currentRequestId, $file->getId());
    $fileContents = file_get_contents($setFile->getPathname());
    $filePath = '/tmp/' . $file["file_name"];
    file_put_contents($filePath, $fileContents);

    if($indexFile == "") {
        $newFile = $apiInstanceFiles->createRequestFile($newRequest, $file["custom_properties"]["data_name"], $filePath);
    } else {
        // Manually update Variables in files
        $replaceIndex = array_search(explode(".", $indexFile)[0], array_column($manualFiles, "old_var"));

        if ($replaceIndex !== false) {
            $indexFile = str_replace(explode(".", $indexFile)[0], $manualFiles[$replaceIndex]["new_var"], $indexFile);
        } 

        $newFile = $apiInstanceFiles->createRequestFile($newRequest, $indexFile, $filePath);
        setValueByPath($returnData, $indexFile, $newFile->getFileUploadId());
    }
}

// Update individual variables
foreach($manualVariables as $manual) {
    if ($returnData[$manual["original"]] != null && $returnData[$manual["condition"]["variable"]] == $manual["condition"]["value"]) {
        $returnData[$manual["copy"]] = $returnData[$manual["original"]];
    }
}

// Clean variables in the current data
foreach($cleanVariables as $cleanVar) {
    unset($returnData[$cleanVar]);
}

// Register new request in the collection
$insurerCollection = getCollectionId('CQP_FORTE_CARGO_INSURED', getEnv("API_HOST") . getEnv("API_SQL"));
$url = getEnv("API_HOST") . "/collections/" . $insurerCollection . "/records";
$arrayNote = [];
$arrayNote['CQP_INSURED_NAME'] = $data["CQP_INSURED_NAME"];
$arrayNote['CQP_INSURED_CODE'] = $data["CQP_INSURED_CODE"];
$arrayNote['CQP_TYPE'] = $data["CQP_TYPE"];
$arrayNote['CQP_COUNTRY'] = $data["CQP_COUNTRY"];
$arrayNote['CQP_INCEPTION_DATE'] = $data["CQP_INCEPTION_DATE"];
$arrayNote['CQP_UNDERWRITING_YEAR'] = $data["CQP_UNDERWRITING_YEAR"];
$arrayNote['CQP_REINSURANCE_BROKER'] = $data["CQP_REINSURANCE_BROKER"];
$arrayNote['CQP_COMMODITIES_PROFILE'] = $data["CQP_COMMODITIES_PROFILE"];
$arrayNote['CQP_BROKER_STATUS'] = $data["CQP_BROKER_STATUS"];
$arrayNote['CQP_REQUEST_ID'] = $newRequest;
$arrayNote['CQP_SEARCH_CODE'] = $data['CQP_SEARCH_CODE'];
$createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
$returnData["CQP_COLLECTION_REQUEST_ID"] = $createRecord["id"];
$returnData["CQP_CARGO_CURRENT_STATUS"] = "QUOTED";
$returnData["CQP_SUBMIT_BROKER"] = null;
$returnData["CQP_START_CLONE"] = null;
$returnData["CQP_CONFIRM_SUBMIT_TAKEN"] = "NO";
$returnData["CQP_CONFIRM_SUBMIT_NOT_TAKEN"] = "NO";
$returnData["CQP_CONTINUE"] = true;

// Update New request
$processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
$processRequestEditable->setData($returnData);
sleep(10);
$apiInstanceRequest->updateProcessRequest($newRequest, $processRequestEditable);

$client = new Client([
    'verify' => false,
    'defaults' => ['verify' => false]
]);  

$response = $client->request('POST', getenv('API_HOST') . "/requests/" . $newRequest ."/events/wait_data", [
    'headers' => [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        "Authorization" => "Bearer " . getenv('API_TOKEN')
    ]
]);

return ["CQP_SUBMIT_BROKER" => null, "CQP_START_CLONE" => null, "CQP_CONFIRM_SUBMIT_TAKEN" => "NO", "CQP_CONFIRM_SUBMIT_NOT_TAKEN" => "NO"];

/* Search File in the object
*
 * @param array $array
 * @param string $searchValue
 * @param string $prefix
 * @return string $response
 *
 * by Diego Tapia
*/

function findValuePath($array, $searchValue, string $prefix = '') { 
    $matches = "";
    $exceptions = ["CQP_MAIL_FILE_IDS", "CQP_SLIP_ATTACHMENT"];

    foreach ($exceptions as $exception) {
        unset($array[$exception]);
    }

    $walker = function ($node, string $currentPath) use (&$walker, $searchValue, $prefix, &$matches) {
        if (is_array($node)) {
            foreach ($node as $key => $child) {
                $segment = is_int($key) ? (string) $key : $key;
                $newPath = $currentPath === '' ? $segment : $currentPath . '.' . $segment;
                $walker($child, $newPath);
            }
        } else {
            $startsWithPrefix = $prefix === '' || substr($currentPath, 0, strlen($prefix)) === $prefix;
            if ($startsWithPrefix) {
                if ($node === $searchValue || (string)$node === (string)$searchValue) {
                    $matches = $currentPath;
                }
            }
        }
    };

    $walker($array, '');
    return $matches;
}


/* Update Value in array base on the index
 *
 * @param array $array
 * @param string $path
 * @param string $value
 *
 * by Diego Tapia
*/
function setValueByPath(&$array, $path, $value) {
    $keys = explode('.', $path);
    $temp = &$array;

    foreach ($keys as $key) {
        if (!isset($temp[$key]) || !is_array($temp[$key])) {
            $temp[$key] = [];
        }

        $temp = &$temp[$key];
    }

    $temp = $value;
}