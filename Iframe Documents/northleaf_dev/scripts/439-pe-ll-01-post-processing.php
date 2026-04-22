<?php 
/**********************************
 * PE - LL.01 Post-processing
 *
 * by Ana Castillo
 * modified by Adriana Centellas
 *********************************/
// Import Generic Functions 
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = [];
$collectionNames = array();
$collectionNames[0] = "PE_MANDATE_ENTITY";

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$aCollections = getCollectionIdMaster($collectionNames, $apiUrl);

//Get all options of Entities Collections with Document Label
$sQGetEntityDocument = "SELECT data->>'$.MANDATE_ENTITY_DOCUMENT_LABEL' AS DOCUMENT_LABEL,
                               data->>'$.MANDATE_ENTITY_LABEL' AS ENTITY
                        FROM collection_" . $aCollections["PE_MANDATE_ENTITY"];
$rQGetEntityDocument = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQGetEntityDocument));
//Set array to get Entity and Document Label
$aEntities = array();
if (count($rQGetEntityDocument) > 0) {
    foreach ($rQGetEntityDocument as $item) {
        $aEntities[$item['ENTITY']] = $item['DOCUMENT_LABEL'];
    }
}

//Set the Loop of Mandates and Entity with Entity Document to generate PDF
$mandates = $data["PE_MANDATES"];
if (count($mandates) > 0) {
    for ($m = 0; $m < count($mandates); $m++) {
        $entity = $mandates[$m]["PE_MANDATE_ENTITY"];
        $mandates[$m]["PE_MANDATE_ENTITY_DOCUMENT"] = "";
        if (isset($aEntities[$entity])) {
            if ($mandates[$m]["PE_MANDATE_CO_INVESTOR"] == "YES") {
                $mandates[$m]["PE_MANDATE_ENTITY_DOCUMENT"] = $mandates[$m]["PE_MANDATE_NAME"];
            } else {
                $mandates[$m]["PE_MANDATE_ENTITY_DOCUMENT"] = $aEntities[$entity];
            }
        }
    }
}

// Get Collections IDs
$collection = getCollectionId('PE_ALLOCATION_INFO', $apiUrl);

// Record on collection New GP
    $aNewMandate = [];
    $aNewMandate['CASE_NUMBER'] = $data["_request"]["case_number"];
    $aNewMandate['ALLOCATION_VARIABLE'] = $mandates;
    $url = $apiHost . "/collections/" . $collection . "/records";
    $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $aNewMandate]);

//Return loop of mandates with Document Entity Label
$dataReturn["PE_MANDATES"] = $mandates;
return $dataReturn;