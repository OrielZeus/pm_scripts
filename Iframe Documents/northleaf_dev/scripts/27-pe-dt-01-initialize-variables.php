<?php 
/*************************************
 * PE - DT.01 Initialize Variables
 *
 * by Cinthia Romero
 * Modified by Elmer Orihuela
 * Modified by Adriana Centellas
 * Modified by Telmo Chiri
 ***********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get options for dropdowns
//Get Deal Team Users $apiUrl
$getDealTeamGroupID = getGroupId("PE - Deal Team", $apiUrl);
$dealTeamUsers = getGroupUsers($getDealTeamGroupID, $apiUrl);
//Get IC Sponsor Users
$getICSponsorGroupID = getGroupId("PE - IC Sponsor", $apiUrl);
$icSponsorUsers = getGroupUsers($getICSponsorGroupID, $apiUrl);
//Get Black Hat Users
$getBlackHatGroupID = getGroupId("PE - Black Hat", $apiUrl);
$blackHatUsers = getGroupUsers($getBlackHatGroupID, $apiUrl);
//Get Collections IDs
$collectionsToSearch = array('PE_DEAL_TYPE', 'PE_SECONDARY_DEAL_TYPE', 'PE_CURRENCY', 'PE_MANDATE_NAME');
$collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl);
$dealTypeOptions = array();
$secDealTypeOptions = array();
$currencyOptions = array();
$mandateNameOptions = array();
if (count($collectionsArray) > 0) {
    //Get options for dropdown Deal Type
    if (!empty($collectionsArray["PE_DEAL_TYPE"])) {
        $queryDealTypeOptions = "SELECT data->>'$.DEAL_TYPE_LABEL' AS LABEL
                                 FROM collection_" . $collectionsArray["PE_DEAL_TYPE"] . "
                                 WHERE data->>'$.DEAL_TYPE_STATUS' = 'Active'
                                 ORDER BY CAST(data->>'$.DEAL_TYPE_ORDER' AS UNSIGNED) ASC";
        $dealTypeOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryDealTypeOptions));
    }

    //Get options for dropdown Secondary Deal Type
    if (!empty($collectionsArray["PE_SECONDARY_DEAL_TYPE"])) {
        $querySecDealTypeOptions = "SELECT data->>'$.SEC_DEAL_TYPE_LABEL' AS LABEL
                                    FROM collection_" . $collectionsArray["PE_SECONDARY_DEAL_TYPE"] . "
                                    WHERE data->>'$.SEC_DEAL_TYPE_STATUS' = 'Active'
                                    ORDER BY CAST(data->>'$.SEC_DEAL_TYPE_ORDER' AS UNSIGNED) ASC";
        $secDealTypeOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySecDealTypeOptions));
    }

    //Get options for dropdown Currency
    if (!empty($collectionsArray["PE_CURRENCY"])) {
        $queryCurrencyOptions = "SELECT data->>'$.CURRENCY_LABEL' AS LABEL
                                 FROM collection_" . $collectionsArray["PE_CURRENCY"] . "
                                 WHERE data->>'$.CURRENCY_STATUS' = 'Active'
                                 ORDER BY CAST(data->>'$.CURRENCY_ORDER' AS UNSIGNED) ASC";
        $currencyOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCurrencyOptions));
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
}

// get dashboard collecition ID
$collectionName = "DASHBOARD_COLLECTION";
$collectionID = getCollectionId($collectionName, $apiUrl);
$dataReturn['BASE_URL'] = getenv('HOST_URL');
$dataReturn['DASHBOARD_LINK'] = "/collections/{$collectionID}/records/1/edit";
$dataReturn["PE_DEAL_TEAM_SENIOR_OPTIONS"] = $dealTeamUsers;
$dataReturn["PE_DEAL_TEAM_JUNIOR_OPTIONS"] = $dealTeamUsers;
$dataReturn["PE_DEAL_IC_SPONSOR_OPTIONS"] = $icSponsorUsers;
$dataReturn["PE_BLACK_HAT_REVIEW_OPTIONS"] = $blackHatUsers;
$dataReturn["PE_DEAL_TYPE_OPTIONS"] = $dealTypeOptions;
$dataReturn["PE_SECONDARY_DEAL_TYPE_OPTIONS"] = $secDealTypeOptions;
$dataReturn["PE_CURRENCY_OPTIONS"] = $currencyOptions;
$dataReturn["PE_MANDATE_NAME_OPTIONS"] = $mandateNameOptions;
$dataReturn["PE_SAVE_SUBMIT_DT1"] = "";
return $dataReturn;