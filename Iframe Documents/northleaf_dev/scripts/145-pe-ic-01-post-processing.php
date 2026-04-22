<?php 
/**********************************
 * PE - IC.01 Post-Processing
 *
 * by Telmo Chiri
 * modified by Favio Mollinedo
 * modified by Adriana Centellas
 *********************************/
require_once __DIR__ . '/vendor/autoload.php';
require_once("/Northleaf_PHP_Library.php");
// Retrieve environment variables for API
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');

$dataICApprovers = [];

/*
// Iterate over the array and add PE_IC_APPROVER_SIGNATURE
foreach ($data["PE_IC_01_APPROVERS_REVIEWED"] as $approver) {
    $dataICApprovers[] = [
        "PE_IC_APPROVER_SIGNATURE" => getSignature($approver["PE_IC_APPROVER_ID"], $apiUrl),
        "PE_IC_APPROVER_ID" => $approver["PE_IC_APPROVER_ID"],
        "PE_IC_APPROVER_NAME" => $approver["PE_IC_APPROVER_NAME"]
    ];
}
*/
$dataReturn = array();
// Clean PE_IC_01_APPROVERS variable
//$dataReturn['PE_APPROVERS_INFO'] = $dataICApprovers;
$dataReturn['PE_IC_01_APPROVERS'] = $data['PE_IC_01_APPROVERS'] ?? [];
$dataReturn['PE_IC_01_APPROVERS_REVIEWED'] = $data['PE_IC_01_APPROVERS_REVIEWED'] ?? [];
$dataReturn['PE_ALL_APPROVERS_ANSWERED'] = 'YES';
$dataReturn['PE_DATE_APPROVAL'] = date('m/d/Y');

return $dataReturn;

/*
*   Get the signature from the user ID
*   By Adriana Centellas
*/
function getSignature($userID, $apiUrl)
{
    $userSignature = "SELECT JSON_UNQUOTE(JSON_EXTRACT(meta, '$.signature')) AS signature from users where id = " . $userID;

    // Execute the query via API call
    $userSignatureResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($userSignature));

    return $userSignatureResponse[0]["signature"];
}