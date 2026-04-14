<?php 
/*
  Update Collection Gestion Solicitudes
  by Helen Callisaya
*/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
/* 
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postfiles
 * @return (Array) $res
 *
 * by Elmer Orihuela 
 */

function apiGuzzle($url, $requestType, $postfiles)
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
    $request = new Request($requestType, $url, $headers, json_encode($postfiles));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $res = json_decode($res, true);
    return $res;
}
/* 
 * Encode SQL
 *
 * @param (String) $string
 * @return (Array) $variablePut
 *
 * by Elmer Orihuela 
 */
function encodeSql($string)
{
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
}
//-----------------------------------------------------
//Global Variables
$apiSQL = "/admin/package-proservice-tools/sql";
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$collection = "13"; //Id Collection Producing

$sql = "SELECT c.id,";
$sql .= " c.data->>'$.FORTE_REQUEST' as FORTE_REQUEST,";
$sql .= " c.data->>'$.FORTE_PROCESS' as FORTE_PROCESS,";
$sql .= " c.data->>'$.FORTE_CREATE_DATE' as FORTE_CREATE_DATE,";
$sql .= " c.data->>'$.FORTE_CREATE_USER' as FORTE_CREATE_USER,";
//SOLO ENDOSOS
$sql .= " c.data->>'$.FORTE_MODIFICATION_USER' as FORTE_MODIFICATION_USER,";
$sql .= " c.data->>'$.FORTE_MODIFICATION_DATE' as FORTE_MODIFICATION_DATE,";

$sql .= " c.data->>'$.YQP_CLIENT_NAME' as YQP_CLIENT_NAME,";
$sql .= " c.data->>'$.YQP_INTEREST_ASSURED' as YQP_INTEREST_ASSURED,";
$sql .= " c.data->>'$.YQP_COUNTRY_BUSINESS' as YQP_COUNTRY_BUSINESS,";
$sql .= " c.data->>'$.YQP_CATCH_MONTH_REPORT' as YQP_CATCH_MONTH_REPORT,";
$sql .= " c.data->>'$.YQP_PIVOT_TABLE_NUMBER' as YQP_PIVOT_TABLE_NUMBER,";
$sql .= " c.data->>'$.YQP_SLIP_DOCUMENT_NAME' as YQP_SLIP_DOCUMENT_NAME,";
$sql .= " c.data->>'$.YQP_TYPE' as YQP_TYPE,";
$sql .= " c.data->>'$.YQP_REASSURED_CEDENT_LABEL' as YQP_REASSURED_CEDENT_LABEL,";
$sql .= " c.data->>'$.YQP_REINSURANCE_BROKER_LABEL' as YQP_REINSURANCE_BROKER_LABEL,";
$sql .= " c.data->>'$.YQP_PERIOD_FROM_REPORT' as YQP_PERIOD_FROM_REPORT,";
$sql .= " c.data->>'$.YQP_PERIOD_TO_REPORT' as YQP_PERIOD_TO_REPORT,";
$sql .= " c.data->>'$.YQP_TRIMESTER' as YQP_TRIMESTER,";
$sql .= " c.data->>'$.YQP_SITUATION' as YQP_SITUATION,";
$sql .= " c.data->>'$.YQP_YEAR_PERIOD' as YQP_YEAR_PERIOD,";
$sql .= " c.data->>'$.YQP_CURRENCY' as YQP_CURRENCY,";
$sql .= " c.data->>'$.YQP_SUM_INSURED_VESSEL' as YQP_SUM_INSURED_VESSEL,";
$sql .= " c.data->>'$.DATA_WAR_SUM_INSURED_TENDER' as DATA_WAR_SUM_INSURED_TENDER,";
$sql .= " c.data->>'$.DATA_WAR_SUM_INSURED_NET' as DATA_WAR_SUM_INSURED_NET,";
$sql .= " c.data->>'$.YQP_LIMIT_PI' as YQP_LIMIT_PI,";
$sql .= " c.data->>'$.DATA_WAR_SUM_INSURED' as DATA_WAR_SUM_INSURED,";
$sql .= " c.data->>'$.YQP_PERSONAL_EFFECTS_LIMIT' as YQP_PERSONAL_EFFECTS_LIMIT,";
$sql .= " c.data->>'$.YQP_MEDICAL_PAYMENTS_LIMIT' as YQP_MEDICAL_PAYMENTS_LIMIT,";
$sql .= " c.data->>'$.YQP_OWNERS_UNINSURED_VESSEL_LIMIT' as YQP_OWNERS_UNINSURED_VESSEL_LIMIT,";
$sql .= " c.data->>'$.YQP_TOTAL_PREMIUM_FINAL' as YQP_TOTAL_PREMIUM_FINAL,";
$sql .= " c.data->>'$.YQP_DEDUCTIBLE' as YQP_DEDUCTIBLE,";
$sql .= " c.data->>'$.YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE' as YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE,";
$sql .= " c.data->>'$.YQP_WAR_DEDUCTIBLE_SLIP' as YQP_WAR_DEDUCTIBLE_SLIP,";
$sql .= " c.data->>'$.YQP_PI_DEDUCTIBLE_SLIP' as YQP_PI_DEDUCTIBLE_SLIP,";
$sql .= " c.data->>'$.YQP_NUMBER_PAYMENTS' as YQP_NUMBER_PAYMENTS,";
$sql .= " c.data->>'$.YQP_BROKER_PERCENTAGE' as YQP_BROKER_PERCENTAGE,";
$sql .= " c.data->>'$.YQP_TOTAL_REINSURANCE_COMMISSION_SHARE' as YQP_TOTAL_REINSURANCE_COMMISSION_SHARE,";
$sql .= " c.data->>'$.YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE' as YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE,";
$sql .= " c.data->>'$.YQP_TYPE_VESSEL_REPORT' as YQP_TYPE_VESSEL_REPORT,";
$sql .= " c.data->>'$.YQP_TYPE_CODE_REPORT' as YQP_TYPE_CODE_REPORT,";
$sql .= " c.data->>'$.YQP_VESSEL_MARK_MODEL' as YQP_VESSEL_MARK_MODEL,";
$sql .= " c.data->>'$.YQP_LENGTH_UNIT_REPORT' as YQP_LENGTH_UNIT_REPORT,";
$sql .= " c.data->>'$.YQP_YEAR' as YQP_YEAR,";
$sql .= " c.data->>'$.YQP_NAVIGATION_LIMITS' as YQP_NAVIGATION_LIMITS,";
$sql .= " c.data->>'$.YQP_USE' as YQP_USE,";
$sql .= " c.data->>'$.YQP_LOSS_PAYEE' as YQP_LOSS_PAYEE,";
$sql .= " c.data->>'$.YQP_HULL_MATERIAL' as YQP_HULL_MATERIAL,";
$sql .= " c.data->>'$.YQP_FLAG' as YQP_FLAG,";
$sql .= " c.data->>'$.YQP_MOORING_PORT_REPORT' as YQP_MOORING_PORT_REPORT,";
$sql .= " c.data->>'$.YQP_CLUB_MARINA' as YQP_CLUB_MARINA,";
$sql .= " c.data->>'$.YQP_COMMENTS' as YQP_COMMENTS,";
$sql .= " c.data->>'$.YQP_USER_USERNAME' as YQP_USER_USERNAME,";
$sql .= " c.data->>'$.YQP_RISK_ATTACHING_MONTH' as YQP_RISK_ATTACHING_MONTH,";
$sql .= " c.data->>'$.YQP_TERM' as YQP_TERM,";
$sql .= " c.data->>'$.YQP_TYPE_YACHT' as YQP_TYPE_YACHT,";
$sql .= " c.data->>'$.YQP_RANGE_SI_HULL' as YQP_RANGE_SI_HULL,";
$sql .= " c.data->>'$.YQP_RANGE_YEAR' as YQP_RANGE_YEAR,";
$sql .= " c.data->>'$.YQP_STATUS' as YQP_STATUS,";
//SOLO ENDOSOS
$sql .= " c.data->>'$.END_TYPE_ENDORSEMENT' as END_TYPE_ENDORSEMENT,";
$sql .= " c.data->>'$.FORTE_REQUEST_ORDER' as FORTE_REQUEST_ORDER,";

$sql .= " c.data->>'$.FORTE_REQUEST_CHILD' as FORTE_REQUEST_CHILD,";
$sql .= " c.data->>'$.FORTE_REQUEST_PARENT' as FORTE_REQUEST_PARENT,";
$sql .= " c.data->>'$.YQP_RATE_CESSION_REPORT' as YQP_RATE_CESSION_REPORT,";
$sql .= " c.data->>'$.YQP_SUM_INSURED_HULL_CESSION_REPORT' as YQP_SUM_INSURED_HULL_CESSION_REPORT,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION' as YQP_REINSURER_INFORMATION";
$sql .= " FROM collection_13 as c"; //WHERE c.id = 2";
$url = $apiHost . $apiSQL;
$responseSql = apiGuzzle($url, "POST", encodeSql($sql));
foreach($responseSql as $res) {
    $dataCollection = [];
    $idCollection = $res['id'];
    unset($res['id']);
    $dataCollection = $res;
    $requestId = $res['FORTE_REQUEST'];
    $urlUpdateData = $apiHost . '/requests/' . $requestId;
    $dataList = apiGuzzle($urlUpdateData . '?include=data', "GET", '');
    $reinsurer = empty($dataList['data']['YQP_REINSURER_INFORMATION']) ? []:$dataList['data']['YQP_REINSURER_INFORMATION'];
    if ($dataList['data']['YQP_PRODUCT'] != "PI_RC") {
        //(Total Hull Sum Insured * Forte Order) Add HC
        $sumInsuredHullCessionReport = $dataList['data']['YQP_SUM_INSURED_VESSEL'] * ($dataList['data']['YQP_FORTE_ORDER'] / 100);
        //(Total Premium * FORTE ORDER) / (Total Hull Sum Insured * Forte Order) Add HC
        $rateCessionReport = ($dataList['data']['YQP_BROKER_TOTAL_PREMIUM_REPORT'] * ($dataList['data']['YQP_FORTE_ORDER'] / 100)) / ($dataList['data']['YQP_SUM_INSURED_VESSEL'] * ($dataList['data']['YQP_FORTE_ORDER'] / 100));
    } else {
        $sumInsuredHullCessionReport = 0;
        $rateCessionReport = 0;
    }
    $dataCollection['YQP_REINSURER_INFORMATION'] = $reinsurer;
    $dataCollection['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = $sumInsuredHullCessionReport;
    $dataCollection['YQP_RATE_CESSION_REPORT'] = $rateCessionReport;
    //Update Data Collection
    $urlUpdateCollection = $apiHost . '/collections/' . $collection . '/records/' . $idCollection;
    //$updateCollection = apiGuzzle($urlUpdateCollection, 'PUT', $dataCollection);
}
return $responseSql;