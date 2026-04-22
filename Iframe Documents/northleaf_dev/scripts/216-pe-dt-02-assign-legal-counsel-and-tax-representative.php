<?php 
/********************************
 * Assign Legal Counsel and Tax Representative
 *
 * by Favio Mollinedo
 * modified by Adriana Centellas
 *******************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set initial values
$dataReturn = [];

//Get collections IDs
$collectionNames = array("PE_GROUP_LEADER");
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionLeaderId = $collectionsInfo["PE_GROUP_LEADER"];

//Get Id Group
$taxGroupName = "PE - Tax";
$taxGroupId = getGroupId($taxGroupName, $apiUrl);
$legalGroupName = "PE - Legal Counsel Managers";
$legalGroupId = getGroupId($legalGroupName, $apiUrl);

//Get User Leader
$userTaxLead = getUserLeadGroup($collectionLeaderId, $taxGroupId, $apiUrl);
$userLegalLead = getUserLeadGroup($collectionLeaderId, $legalGroupId, $apiUrl);

//Get Tax representative users
$taxRepresentativeUsers = getGroupUsers($taxGroupId, $apiUrl);

//Get Legal Counsel Managers users
$legalCounselUsers = getGroupUsers($legalGroupId, $apiUrl);

//Return Variables
$dataReturn['PE_TAX_REPRESENTATIVE_OPTIONS'] = $taxRepresentativeUsers;
$dataReturn['PE_RED_FLAG_TAX_LEADER'] = $userTaxLead;
$dataReturn['PE_LEGAL_COUNSEL_OPTIONS'] = $legalCounselUsers;
$dataReturn['PE_RED_FLAG_LEGAL_LEADER'] = $userLegalLead;
/*
if ($data["PE_SAVE_SUBMIT_DT2"] == "SAVE") {
    $dataReturn["PE_DEAL_TEAM_JUNIOR"] = $data["PE_DEAL_TEAM_JUNIOR_UPDATED"];
}
*/
//Set submit
$dataReturn['PE_SAVE_SUBMIT_DT2'] = "SUBMIT";

//$data = array_merge($data, $dataReturn);

return $dataReturn;