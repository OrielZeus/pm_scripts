<?php 
/**********************************
 * PE - IA.01 Define Route
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();
$match = false;

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get collections IDs
$collectionNames = array("PE_INVESTMENT_ADVISOR_SETTINGS");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionIASettingsId = $collectionsInfo["PE_INVESTMENT_ADVISOR_SETTINGS"];
//Get Settings
$sqlSettings = "SELECT data->>'$.PE_MANDATES' AS PE_MANDATES
                FROM collection_$collectionIASettingsId
                LIMIT 1";
$settingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlSettings));

if (empty($settingsResponse["error_message"])) {
    $mandates = json_decode($settingsResponse[0]['PE_MANDATES']);    
    foreach($mandates as $mandate) {
        //Match Mandates
        if (is_in_array($data['PE_MANDATES'], 'PE_MANDATE_NAME', $mandate->MANDATE_CODE)) {
            $match = true;
        }
    }
}

$dataReturn['IA_01_IS_NECESSARY'] = $match ? 'YES' : 'NO';

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