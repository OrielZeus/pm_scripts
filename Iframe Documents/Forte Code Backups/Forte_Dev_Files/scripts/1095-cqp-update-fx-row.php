<?php 
/*  
 *  Update FX related field in request
 *  By Diego Tapia
 */

// Set initial variable
$apiInstanceRequest = $api->processRequests();

// Get request data
$processRequest = $apiInstanceRequest->getProcessRequestById($data["request_id"], "data");
$dataNew = json_decode(json_encode($processRequest->getData()), true);

// Update variables
$fxRate = $data["new_fx"];
$dataNew['CQP_FX_RATE'] = $fxRate;

// List of fields to get the Fx Rate

$convertFields = [
    "CQP_TRANSIT_USD_100",
    "CQP_TRANSIT.0.CQP_TOTAL_TTP_ANNUAL_USD",
    "CQP_STORAGE.0.CQP_TOTAL_INVENTORIES_GWP",
    "CQP_TRANSIT.0.CQP_TOTAL_TTP_GWD",
    "CQP_STORAGE_EXPOSURE.0.CQP_STORAGE_AGG",
    "CQP_STORAGE_EXPOSURE.0.CQP_STORAGE_EEL",
    "CQP_SUMMARY_DETAILS.0.CQP_MINDEP_USD",
    "CQP_SUMMARY_DETAILS.0.CQP_TAX_USD",
    "CQP_SUMMARY_DETAILS.0.CQP_MIN_DEP",
    "CQP_GWP_TOTAL_EPI_MRE_SHARE",
    "CQP_GWP_MINDEP_MRE_SHARE",
    "CQP_NWP_TOTAL_EPI_MRE_SHARE",
    "CQP_MINDEP_USD",
    "CQP_TAX_PERCENTAGE",
    "CQP_TOTAL_TTP_GWD_MRE",
    "CQP_TOTAL_INVENTORIES_GWP_MRE",
    "CQP_NWP_MINDEP_MRE_SHARE",
    "CQP_GWP_TOTAL_EPI_100",
    "CQP_GWP_PRIMA_DE_AJUSTE",
    "CQP_NWP_AT_100",
    "CQP_GWP_MINDEP_PLUS_AJUSTE"
];

$convertFieldstMarket = [
    "CQP_GWP_TRANSIT_REINSURER_SHARE",
    "CQP_GWP_STORAGE_REINSURER_SHARE",
    "CQP_GWP_TOTAL_EPI_REINSURER_SHARE",
    "CQP_GWP_MINDEP_REINSURER_SHARE",
    "CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE",
    "CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE",
    "CQP_BROKER_DEDUCTION_USD",
    "CQP_BROKER_DEDUCTION_ADJUSTMENT",
    "CQP_BROKER_DEDUCTION_TOTAL",
    "CQP_TAX",
    "CQP_TAX_ADJUSTMENT",
    "CQP_TAX_TOTAL",
    "CQP_NWP_TRANSIT_REINSURER_SHARE",
    "CQP_NWP_STORAGE_REINSURER_SHARE",
    "CQP_NWP_TOTAL_EPI_REINSURER_SHARE",
    "CQP_NWP_MINDEP_REINSURER_SHARE",
    "CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX",
    "CQP_NWP_ADJUSTMENT_REINSURER_SHARE",
    "CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX",
    "CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE",
    "CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX",
    "CQP_FORTE_FEE_USD",
    "CQP_FORTE_FEE_ADJUSTMENT",
    "CQP_FORTE_FEE_TOTAL",
    "CQP_NET_CEDED_PREMIUM_MINDEP",
    "CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT",
    "CQP_NET_CEDED_PREMIUM",
    "CQP_TRANSIT_REINSURER_SHARE",
    "CQP_STORAGE_REINSURER_SHARE",
    "CQP_CAT_REINSURER_SHARE"
];

$dataNew = convertUSD($dataNew, $convertFields);

foreach ($dataNew["CQP_MARKETS"] as $indexMarket => &$market) {
    $market = convertUSD($market, $convertFieldstMarket);
}

$processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
$processRequestEditable->setData($dataNew);
$apiInstanceRequest->updateProcessRequest($data["request_id"], $processRequestEditable);

// Return a random number to validate update in screen
return ["updatedId" => rand(1, 1500)];

/*
 * Convert the current value to USD
 *
 * @param array $dataConvert
 * @param array $convertFietldsVar
 * @return array $dataConvert 
 *
 * by Diego Tapia
 */
function convertUSD ($dataConvert, $convertFietldsVar) {
    global $fxRate;
    
    foreach ($convertFietldsVar as $fields) {
        $parts = explode('.', $fields);
        $ref = &$dataConvert;
        $value = 0;

        // Get the value
        foreach ($parts as $part) {
            if (!isset($ref[$part])) {
                continue 2;
            }
            $ref = &$ref[$part];
        }
        
        // Set the new fx value in request data
        if (is_numeric($ref)) {
        
            $value = $ref;
            $indexSearch = &$dataConvert;

            foreach ($parts as $part) {
                if ($indexSearch[$part] != $value) {
                    $indexSearch = &$indexSearch[$part];
                }
            }
            
            if (is_numeric($value)) {
                $indexSearch[$part . '_USD_VALUE'] = round($value / $fxRate, 2);
            } else {
                $indexSearch[$part . '_USD_VALUE'] = 0;
            }
        }
    }

    return $dataConvert;

}