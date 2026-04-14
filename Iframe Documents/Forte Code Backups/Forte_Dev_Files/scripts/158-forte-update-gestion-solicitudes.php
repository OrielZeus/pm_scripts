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
$collectionGestion = "12";

$sql = "SELECT c.id, c.data->>'$.FORTE_REQUEST' as FORTE_REQUEST,";
$sql .= " c.data->>'$.FORTE_PROCESS' as FORTE_PROCESS,";
$sql .= " c.data->>'$.FORTE_CREATE_DATE' as FORTE_CREATE_DATE,";
$sql .= " c.data->>'$.FORTE_CREATE_USER' as FORTE_CREATE_USER,";
$sql .= " c.data->>'$.FORTE_REQUEST_ORDER' as FORTE_REQUEST_ORDER,";
$sql .= " c.data->>'$.FORTE_REQUEST_PARENT' as FORTE_REQUEST_PARENT,";
$sql .= " c.data->>'$.FORTE_REQUEST_CHILD' as FORTE_REQUEST_CHILD,";
$sql .= " c.data->>'$.END_TYPE_ENDORSEMENT' as END_TYPE_ENDORSEMENT,";
$sql .= " c.data->>'$.YQP_CLIENT_NAME' as YQP_CLIENT_NAME,";
$sql .= " c.data->>'$.YQP_STATUS' as YQP_STATUS,";
$sql .= " c.data->>'$.YQP_INTEREST_ASSURED' as YQP_INTEREST_ASSURED,";
$sql .= " c.data->>'$.YQP_PERIOD_FROM_REPORT' as YQP_PERIOD_FROM_REPORT,";
$sql .= " c.data->>'$.YQP_PERIOD_TO_REPORT' as YQP_PERIOD_TO_REPORT,";
$sql .= " c.data->>'$.YQP_TYPE_VESSEL_REPORT' as YQP_TYPE_VESSEL_REPORT,";
$sql .= " c.data->>'$.YQP_SUM_INSURED_VESSEL' as YQP_SUM_INSURED_VESSEL,";
$sql .= " c.data->>'$.YQP_LIMIT_PI' as YQP_LIMIT_PI,";
$sql .= " c.data->>'$.YQP_COUNTRY_BUSINESS' as YQP_COUNTRY_BUSINESS,";
$sql .= " c.data->>'$.YQP_SITUATION' as YQP_SITUATION,";
$sql .= " c.data->>'$.YQP_TYPE' as YQP_TYPE,";
$sql .= " c.data->>'$.YQP_REASSURED_CEDENT_LABEL' as YQP_REASSURED_CEDENT_LABEL,";
$sql .= " c.data->>'$.YQP_REINSURANCE_BROKER_LABEL' as YQP_REINSURANCE_BROKER_LABEL,";
$sql .= " c.data->>'$.YQP_BROKER_TOTAL_PREMIUM_REPORT' as YQP_BROKER_TOTAL_PREMIUM_REPORT,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_FORTE_ORDER_SHARE' as YQP_REINSURER_INFORMATION_YQP_FORTE_ORDER_SHARE,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_TOTAL_PREMIUM_SHARE' as YQP_REINSURER_INFORMATION_YQP_TOTAL_PREMIUM_SHARE,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_BROKER_DEDUCTION' as YQP_REINSURER_INFORMATION_YQP_BROKER_DEDUCTION,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_BROKER_DEDUCTION_USD' as YQP_REINSURER_INFORMATION_YQP_BROKER_DEDUCTION_USD,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_TAX_ON_GROSS' as YQP_REINSURER_INFORMATION_YQP_TAX_ON_GROSS,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_TAX_GROSS_SHARE' as YQP_REINSURER_INFORMATION_YQP_TAX_GROSS_SHARE,";
$sql .= " c.data->>'$.YQP_TOTAL_PREMIUM_SHARED_REPORT' as YQP_TOTAL_PREMIUM_SHARED_REPORT,";
$sql .= " c.data->>'$.YQP_TOTAL_NWP_USD_REPORT' as YQP_TOTAL_NWP_USD_REPORT,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_FORTE_FEE_PERCENTAGE' as YQP_REINSURER_INFORMATION_YQP_FORTE_FEE_PERCENTAGE,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_FORTE_FEE' as YQP_REINSURER_INFORMATION_YQP_FORTE_FEE,";
$sql .= " c.data->>'$.YQP_REINSURER_INFORMATION_YQP_CEDED_PREMIUM_SHARE' as YQP_REINSURER_INFORMATION_YQP_CEDED_PREMIUM_SHARE,";
$sql .= " c.data->>'$.YQP_LINE_BUSINESS' as YQP_LINE_BUSINESS,";
$sql .= " c.data->>'$.YQP_SOURCE' as YQP_SOURCE,";
$sql .= " c.data->>'$.YQP_SUBMISSION_DATE_REPORT' as YQP_SUBMISSION_DATE_REPORT,";
$sql .= " c.data->>'$.YQP_SUBMISSION_MONTH_REPORT' as YQP_SUBMISSION_MONTH_REPORT,";
$sql .= " c.data->>'$.YQP_COMMENTS' as YQP_COMMENTS,";
$sql .= " c.data->>'$.YQP_RETROCESIONARY' as YQP_RETROCESIONARY,";
$sql .= " c.data->>'$.YQP_TERM' as YQP_TERM,";
$sql .= " c.data->>'$.YQP_SLIP_DOCUMENT_NAME' as YQP_SLIP_DOCUMENT_NAME,";
$sql .= " c.data->>'$.YQP_RISK_ATTACHING_MONTH' as YQP_RISK_ATTACHING_MONTH,";
$sql .= " c.data->>'$.YQP_MONTH_SENT_ADOBE_REPORT' as YQP_MONTH_SENT_ADOBE_REPORT,";
$sql .= " c.data->>'$.YQP_MOORING_PORT_REPORT' as YQP_MOORING_PORT_REPORT,";
$sql .= " c.data->>'$.YQP_CLUB_MARINA' as YQP_CLUB_MARINA";
$sql .= " FROM collection_12 as c";
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
    $dataCollection['YQP_REINSURER_INFORMATION'] = $reinsurer;
    //Update Data Collection
    $urlUpdateCollection = $apiHost . '/collections/' . $collectionGestion . '/records/' . $idCollection;
    $updateCollection = apiGuzzle($urlUpdateCollection, 'PUT', $dataCollection);
}
return $responseSql;