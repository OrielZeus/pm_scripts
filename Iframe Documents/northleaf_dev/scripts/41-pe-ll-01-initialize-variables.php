<?php 
/**********************************
 * PE - LL.01 Initialize Variables
 *
 * by Helen Callisaya
 * modified by Cinthia Romero
 * modified by Adriana Centellas
 * modified by Telmo Chiri
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Cinthia Romero
 */ 
function callApiUrlGuzzle($url, $method, $sendData)
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
    $request = new Request($method, $url, $headers, json_encode($sendData));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $executionResponse = json_decode($res, true);
    return $executionResponse;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

//Set initial values
$dataReturn = [];
$collectionsArray = array();
$mandateNameOptions = array();
$mandateEntityOptionsSorted = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$apiUrl = $apiHost . $apiSql;

//Get Collections IDs
$queryCollections = "SELECT data->>'$.COLLECTION_ID' AS ID,
                            data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                     FROM collection_" . $masterCollectionID . "
                     WHERE data->>'$.COLLECTION_NAME' IN ('PE_MANDATE_NAME', 'PE_MANDATE_ENTITY')";
$collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollections));
if (count($collectionsInfo) > 0) {
    foreach($collectionsInfo as $collection) {
        $collectionsArray[$collection["COLLECTION_NAME"]] = $collection["ID"];
    }
    //Get options for dropdown Mandate Name
    if (!empty($collectionsArray["PE_MANDATE_NAME"])) {
        $queryMandateNameOptions = "SELECT data->>'$.MANDATE_NAME_LABEL' AS LABEL, 
                                    data->>'$.MANDATE_COMPLETE_NAME' AS COMPLETE_NAME, 
                                    data->>'$.MANDATE_FUND_NAME' AS FUND_NAME, 
                                    data->>'$.MANDATE_CO_INVESTOR' AS CO_INVESTOR  
                                    FROM collection_" . $collectionsArray["PE_MANDATE_NAME"] . "
                                    WHERE data->>'$.MANDATE_NAME_STATUS' = 'Active'
                                    ORDER BY CAST(data->>'$.MANDATE_NAME_ORDER' AS UNSIGNED) ASC";
        $mandateNameOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryMandateNameOptions));
    }
    //Get options for dropdown Mandate Entity
    if (!empty($collectionsArray["PE_MANDATE_ENTITY"])) {
        $queryMandateEntityOptions = "SELECT data->>'$.MANDATE_NAME' AS MANDATE,
                                             data->>'$.MANDATE_ENTITY_LABEL' AS ENTITY
                                      FROM collection_" . $collectionsArray["PE_MANDATE_ENTITY"] . "
                                      WHERE data->>'$.MANDATE_ENTITY_STATUS' = 'Active'
                                      ORDER BY CAST(data->>'$.MANDATE_ENTITY_ORDER' AS UNSIGNED) ASC";
        $mandateEntityOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryMandateEntityOptions));
        if (!empty($mandateEntityOptions[0]["MANDATE"])) {
            foreach ($mandateEntityOptions as $mandateEntity) {
                $mandateName = $mandateEntity["MANDATE"];
                $mandateEntityOptionsSorted[$mandateName][]["LABEL"] = $mandateEntity["ENTITY"];
            }
        }
    } 
}

$dataReturn['PE_MANDATE_NAME_OPTIONS'] = $mandateNameOptions;
$dataReturn['PE_MANDATE_ENTITIES_OPTIONS'] = json_encode($mandateEntityOptionsSorted);
$dataReturn["PE_SAVE_SUBMIT_LL1"] = "";
return $dataReturn;