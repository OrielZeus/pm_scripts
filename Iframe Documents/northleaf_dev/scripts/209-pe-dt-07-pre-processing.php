<?php 
/*************************************
 * PE - DT.07 Initialize Variables
 *
 * by Telmo Chiri
 ***********************************/
require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get Collections IDs
$collectionsToSearch = array('PE_GP', 'PE_REPORTING_FREQUENCY');
$collectionsArray = getCollectionIdMaster($collectionsToSearch, $apiUrl);
$gpOptions = array();
$reportingFrequencyOptions = array();
if (count($collectionsArray) > 0) {
    //Get options for dropdown GP
    if (!empty($collectionsArray["PE_GP"])) {
        $queryGPOptions = "SELECT data->>'$.GP' AS LABEL
                                 FROM collection_" . $collectionsArray["PE_GP"] . "
                                 WHERE data->>'$.GP_STATUS' = 'Active'
                                 ORDER BY data->>'$.GP' ASC";
        $gpOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGPOptions));
    }

    //Get options for dropdown Reporting Frequency
    if (!empty($collectionsArray["PE_REPORTING_FREQUENCY"])) {
        $queryReportingFrequencyOptions = "SELECT data->>'$.REPORTING_LABEL' AS LABEL
                                    FROM collection_" . $collectionsArray["PE_REPORTING_FREQUENCY"] . "
                                    WHERE data->>'$.REPORTING_STATUS' = 'Active'
                                    ORDER BY CAST(data->>'$.REPORTING_ORDER' AS UNSIGNED) ASC";
        $reportingFrequencyOptions = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryReportingFrequencyOptions));
    }
}

$dataReturn["PE_GP_OPTIONS"] = $gpOptions;
$dataReturn["PE_REPORTING_FREQUENCY_OPTIONS"] = $reportingFrequencyOptions;
$dataReturn["PE_SAVE_SUBMIT_DT7"] = "";
return $dataReturn;