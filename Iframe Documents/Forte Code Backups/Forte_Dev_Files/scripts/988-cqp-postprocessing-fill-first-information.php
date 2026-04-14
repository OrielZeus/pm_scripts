<?php 
/*  
 *  CQP - Postprocessing Fill First Information
 *  By Natalia Mendez
 *  Modified by Adriana Centellas
 *  Modified by Diego Tapia
 *  Modified by Mateo Rada
 *  Modified by Natalia Mendez
 */

// Clear error variable to avoid previous error to be shown afterwards
$data["FORTE_ERRORS"] = null;

require_once("/CQP_Generic_Functions.php");

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$action = $data["CQP_ACTION"];
$submitionDate = $data["CQP_SUBMITION_DATE"];
$insuredName = $data["CQP_INSURED_NAME"];
$insuredCode = $data["CQP_INSURED_CODE"];
$quote = $data["CQP_QUOTE"];
$reason = $data["CQP_REASON"];
$comments = $data["CQP_COMMENTS"];
$type = $data["CQP_TYPE"];
$country = $data["CQP_COUNTRY"];
$inceptionDate = $data["CQP_INCEPTION_DATE"];
$underwritingYear = $data["CQP_UNDERWRITING_YEAR"];
$reinsuranceBroker = $data["CQP_REINSURANCE_BROKER"];
$commoditiesProfile = $data["CQP_COMMODITIES_PROFILE"];
$brokerStatus = $data["CQP_BROKER_STATUS"];
$searchCode = !empty($data['CQP_SEARCH_CODE']) ? $data['CQP_SEARCH_CODE'] : generateSearchId();

//Record on collection  
$notificationLogcollectionId = getCollectionId('CQP_FORTE_CARGO_INSURED', $apiUrl);
$url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
$validateExist = callApiUrlGuzzle($url . "?pmql=data.CQP_REQUEST_ID=" . $data["_request"]["id"], "GET");

// Validates if record was created in collection
if ($validateExist["meta"]["total"] > 0) {
    $collectionID = $validateExist["data"][0]["id"];
} else {        
    $arrayNote = [];
    $arrayNote['CQP_INSURED_NAME'] = $insuredName;
    $arrayNote['CQP_INSURED_CODE'] = $insuredCode;
    $arrayNote['CQP_TYPE'] = $type;
    $arrayNote['CQP_COUNTRY'] = $country;
    $arrayNote['CQP_INCEPTION_DATE'] = $inceptionDate;
    $arrayNote['CQP_UNDERWRITING_YEAR'] = $underwritingYear;
    $arrayNote['CQP_REINSURANCE_BROKER'] = $reinsuranceBroker;
    $arrayNote['CQP_COMMODITIES_PROFILE'] = $commoditiesProfile;
    $arrayNote['CQP_BROKER_STATUS'] = $brokerStatus;
    $arrayNote['CQP_REQUEST_ID'] = $data["_request"]["id"];
    $arrayNote['CQP_SEARCH_CODE'] = $searchCode;
    $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
    $collectionID = $createRecord["id"];
}

return [
    "CQP_SEARCH_CODE" => $searchCode, 
    "CQP_COLLECTION_REQUEST_ID" => $collectionID,
    "resErrorHandling"   => "", // Clear error variable to avoid previous error to be shown afterwards
    "FORTE_ERROR"       => ['data' => ["FORTE_ERROR_LOG" => ""]],
    "FORTE_ERROR_MESSAGE" => ""
];


/**
 * Generate a Unique string for searching
 * 
 * @return string $uniqueCode
 *
 * by Diego Tapia
 */ 
function generateSearchId() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $uniqueCode = '';

    for ($i = 0; $i < 24; $i++) {
        $uniqueCode .= $characters[random_int(0, $charactersLength - 1)];
    }

    return $uniqueCode;
}