<?php 
/******************************** 
 * DT.01 Signature Validations - Verify users signatures
 *
 * by Favio Mollinedo
 * modified by Telmo Chiri
 *******************************/
require_once("/Northleaf_PHP_Library.php");

//Global variables
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get collection ID
$collectionName = "PE_MANDATE_IC_APPROVERS";
$collectionID = getCollectionId($collectionName, $apiUrl);

$jsonMandateDecoded = !empty($data['PE_MANDATES_NAMES_ENCODED']) ? json_decode(urldecode($data['PE_MANDATES_NAMES_ENCODED']), true) : [];

$usersIDsArray = [];
$usersNotConfiureArray = [];
foreach($jsonMandateDecoded as $jsonMandateDataRecord){
    $jsonMandateData = $jsonMandateDataRecord['NAME'];
    $jsonMandateCoinvestor = $jsonMandateDataRecord['COINVESTOR'];
    if(!empty($jsonMandateData) && $jsonMandateCoinvestor !== "true"){
        //Get Collection Data
        $icApproversData = getMandateIcApproversData($collectionID, $apiUrl, $jsonMandateData);
        $usersIDsArray = json_decode($icApproversData[0]["MIA_APPROVER_USERS_IDS"], true);
        if ($usersIDsArray) {
            foreach($usersIDsArray as $usersID){
                //Get Collection Data
                $userVerification = getUserConfiguredSignatureData($apiUrl, $usersID);
                //Verifying users signatures and setup message.
                if($userVerification[0]["CONFIGURED"] == null || $userVerification[0]["SIGNATURE_STATUS"] != "ACTIVE" || $userVerification[0]["SIGNATURE_URL"] == ""){
                    $usersNotConfiureArray[] = $userVerification[0]["FIRST_NAME"]. " ". $userVerification[0]["LAST_NAME"] . " does not have a signature configured in the " . $jsonMandateData . " mandate.";
                }
            }
        } else {
            $usersNotConfiureArray[] = " No data found for mandate " . $jsonMandateData;
        }
    }   
}

return [
    "USER_MESSAGE" => $usersNotConfiureArray, 
    "COUNT_INVALID_USERS" => count($usersNotConfiureArray)
    ];

/**
 * Get data signature config collection by its user ID.
 *
 * @param string $apiUrl - The API URL for making the request.
 * @param int $userId - The user ID.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getUserConfiguredSignatureData($apiUrl, $userId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT SC.CONFIGURED AS CONFIGURED,
                        IFNULL(NULLIF(SC.FIRST_NAME, 'null'), '') AS FIRST_NAME,
                        IFNULL(NULLIF(SC.LAST_NAME, 'null'), '') AS LAST_NAME,
                        IFNULL(NULLIF(SC.SIGNATURE_STATUS, 'null'), '') AS SIGNATURE_STATUS,
                        IFNULL(NULLIF(SC.SIGNATURE_URL, 'null'), '') AS SIGNATURE_URL,
                        SC.fundingApprovalRequired AS fundingApprovalRequired,
                        SC.typeOfSigner AS typeOfSigner
                        FROM SIGNATURE_CONFIGURATION AS SC
                        WHERE SC.USER_ID = " . $userId;

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Get data ic approvers collection by its mandate name.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $mandateName - The name of the mandate.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getMandateIcApproversData($ID, $apiUrl, $mandateName)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT MIA.data->>'$.MIA_APPROVER_USERS_IDS' AS MIA_APPROVER_USERS_IDS
                        FROM collection_" . $ID . " AS MIA
                        WHERE MIA.data->>'$.MIA_MANDATE_NAME' = '" . $mandateName . "'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}