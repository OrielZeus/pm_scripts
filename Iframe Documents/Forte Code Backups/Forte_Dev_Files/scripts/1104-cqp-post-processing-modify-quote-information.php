<?php 
/*  
 *  CQP - Postprocessing  Modify Quote Information
 *  By Diego Tapia
 * Modified by Natalia Mendez
 */

require_once("/CQP_Generic_Functions.php");

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$insuredName = $data["CQP_INSURED_NAME"];
$insuredCode = $data["CQP_INSURED_CODE"];
$type = $data["CQP_TYPE"];
$country = $data["CQP_COUNTRY"];
$currency = $data["CQP_CURRENCY"];
$underwritingYear = $data["CQP_UNDERWRITING_YEAR"];
$riskClassification = $data["CQP_RISK_CLASSIFICATION"];
$cedant = $data["CQP_CEDANT"];
$reinsuranceBroker = $data["CQP_REINSURANCE_BROKER"];
$inceptionDate = $data["CQP_INCEPTION_DATE"];
$addInceptionDateNormal = $data["CQP_ADD_INCEPTION_DATE_NORMAL"]; //
$specialCase = $data["CQP_SPECIAL_CASE"];
$addInceptionDateSpecial = $data["CQP_ADD_INCEPTION_DATE_SPECIAL"]; //
$expirationDate = $data["CQP_EXPIRATION_DATE"];
$specialCaseReason = $data["CQP_SPECIAL_CASE_REASON"];
$days = $data["CQP_DAYS"];
$months = $data["CQP_MONTHS"];
$interest = $data["CQP_INTEREST"];
$commoditiesProfile = $data["CQP_COMMODITIES_PROFILE"];

$totalForteShare = $data["CQP_TOTAL_FORTE_SHARE"];


$argument = $data["CQP_ARGUMENT"];


//Record on collection          
$arrayNote = [];

$arrayNote['REQUEST_ID'] = $data['_request']['id'];
$arrayNote['CASE_NUMBER'] = $data['_request']['case_number'];

$arrayNote['CQP_INSURED_NAME'] = $insuredName;
$arrayNote['CQP_INSURED_CODE'] = $insuredCode;
$arrayNote['CQP_TYPE'] = $type;
$arrayNote['CQP_COUNTRY'] = $country;
$arrayNote['CQP_CURRENCY'] = $currency;
$arrayNote['CQP_UNDERWRITING_YEAR'] = $underwritingYear;
$arrayNote['CQP_RISK_CLASSIFICATION'] = $riskClassification;
$arrayNote['CQP_CEDANT'] = $cedant;
$arrayNote['CQP_REINSURANCE_BROKER'] = $reinsuranceBroker;
$arrayNote['CQP_INCEPTION_DATE'] = $inceptionDate;
$arrayNote['CQP_EXPIRATION_DATE'] = $expirationDate;
$arrayNote['CQP_SPECIAL_CASE'] = $specialCase;
$arrayNote['CQP_ADD_INCEPTION_DATE_NORMAL'] = $addInceptionDateNormal;
$arrayNote['CQP_ADD_INCEPTION_DATE_SPECIAL'] = $addInceptionDateSpecial;
$arrayNote['CQP_SPECIAL_CASE_REASON'] = $specialCaseReason;
$arrayNote['CQP_DAYS'] = $days;
$arrayNote['CQP_MONTHS'] = $months;
$arrayNote['CQP_INTEREST'] = $interest;
$arrayNote['CQP_COMMODITIES_PROFILE'] = $commoditiesProfile;

$arrayNote['CQP_TOTAL_FORTE_SHARE'] = $totalForteShare;

//Table: Transit Exposure
$arrayNote["CQP_TRANSIT_EXPOSURE"] = $data["CQP_TRANSIT_EXPOSURE"];


//Table: Storage Exposure
$arrayNote["CQP_STORAGE_EXPOSURE"] = $data["CQP_STORAGE_EXPOSURE"];

//Table: Transit
$arrayNote["CQP_TRANSIT"] = $data["CQP_TRANSIT"];

//Table: Storage
$arrayNote['CQP_TURNOVER_DATES'] = $turnoverDates;

$arrayNote["CQP_STORAGE"] = $data["CQP_TRANSIT"];


//Table: Summary Claims
$arrayNote['CQP_NUMBER_OF_PERIODS'] = $numberOfPeriods;

$arrayNote["CQP_SUMMARY_CLAIMS"] = $data["CQP_SUMMARY_CLAIMS"];
$arrayNote["CQP_SUMMARY_CLAIMS_LAST"] = $data["CQP_SUMMARY_CLAIMS_LAST"];


//Table: Summary Details
$arrayNote["CQP_SUMMARY_DETAILS"] = $data["CQP_SUMMARY_DETAILS"];


//Table: Markets
$arrayNote["CQP_MARKETS"] = $data["CQP_MARKETS"];
$arrayNote["CQP_MARKETS_TOTALS"] = $data["CQP_MARKETS_TOTALS"];


//Table: Period
$arrayNote["ROWS"] = $data["ROWS"];
$arrayNote["AS_IF"] = $data["AS_IF"];
$arrayNote["TOTALS"] = $data["TOTALS"];


$arrayNote['CQP_ARGUMENT'] = $argument;

//From the real case data, the request ID and the case number must be stored
$historicalCollectionId = getCollectionId('CQP_FORTE_CARGO_QUOTE_HISTORICAL', $apiUrl);

$url = $apiHost . "/collections/" . $historicalCollectionId . "/records";

//$createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);

// Calc values for reports

$marketList = array_column(callApiUrlGuzzle($apiHost . "/collections/" . getCollectionId('CQP_FORTE_CARGO_REINSURER', $apiUrl) . "/records", "GET")["data"], "data");
$CQP_GWP_PRIMA_DE_AJUSTE = 0;
$CQP_TOTAL_TTP_ANNUAL_USD = !empty($data["CQP_TRANSIT"][0]["CQP_TOTAL_TTP_ANNUAL_USD"]) ? $data["CQP_TRANSIT"][0]["CQP_TOTAL_TTP_ANNUAL_USD"] : 0;
$CQP_TOTAL_INVENTORIES_GWP = !empty($data["CQP_STORAGE"][0]["CQP_TOTAL_INVENTORIES_GWP"]) ? $data["CQP_STORAGE"][0]["CQP_TOTAL_INVENTORIES_GWP"] : 0;
$CQP_TOTAL_TTP_GWD = !empty($data["CQP_TRANSIT"][0]["CQP_TOTAL_TTP_GWD"]) ? $data["CQP_TRANSIT"][0]["CQP_TOTAL_TTP_GWD"] : 0;
$CQP_STORAGE_AGG = !empty($data["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_AGG"]) ? $data["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_AGG"] : 0;
$CQP_STORAGE_EEL = !empty($data["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_EEL"]) ? $data["CQP_STORAGE_EXPOSURE"][0]["CQP_STORAGE_EEL"] : 0;
$CQP_MINDEP_USD = !empty($data["CQP_SUMMARY_DETAILS"][0]["CQP_MINDEP_USD"]) ? $data["CQP_SUMMARY_DETAILS"][0]["CQP_MINDEP_USD"] : 0;
$CQP_BROKER_DEDUCTIONS_USD = !empty($data["CQP_SUMMARY_DETAILS"][0]["CQP_BROKER_DEDUCTIONS_USD"]) ? ($data["CQP_SUMMARY_DETAILS"][0]["CQP_BROKER_DEDUCTIONS_USD"]  / 100) : 0;
$CQP_TAX_USD = !empty($data["CQP_SUMMARY_DETAILS"][0]["CQP_TAX_USD"]) ? ($data["CQP_SUMMARY_DETAILS"][0]["CQP_TAX_USD"]  / 100) : 0;
$CQP_UNDERWRITING_EXPENSES = !empty($data["TOTALS"][0]["CQP_UNDERWRITING_EXPENSES"]) ? ($data["TOTALS"][0]["CQP_UNDERWRITING_EXPENSES"]  / 100) : 0;

if ($data["CQP_MARKETS"] != null && $data["CQP_MARKETS"] != "" && $data["CQP_MARKETS"] != "null") {
    foreach ($data["CQP_MARKETS"] as &$market) {
        $indexMarketConfiguration = array_search($market["CQP_REINSURER"], array_column($marketList, "CQP_REINSURER"));
        $market["CQP_ADOBE_ALIAS"] = $marketList[$indexMarketConfiguration]["CQP_ADOBE_ALIAS"];
        $market["CQP_MASK_ADOBE_FORTE_SHARE"] = $marketList[$indexMarketConfiguration]["CQP_MASK_ADOBE_FORTE_SHARE"];
        $CQP_FORTE_SHARE = !empty($market["CQP_FORTE_SHARE"]) ? ($market["CQP_FORTE_SHARE"]  / 100) : 0;

        if ($market["CQP_REINSURER"] == "Munich RE") {
            $data["CQP_MRE_SHARE"] = $market["CQP_FORTE_SHARE"];
        }

        if ($market["CQP_REINSURER"] == "Austral RE" && $market["taken"]) {
            if ($data["CQP_COUNTRY"]["COUNTRY_CODE"] == "br" || (($data["CQP_COUNTRY"]["COUNTRY_CODE"] == "bs" || $data["CQP_COUNTRY"]["COUNTRY_CODE"] == "cu" || $data["CQP_COUNTRY"]["COUNTRY_CODE"] == "jm" || $data["CQP_COUNTRY"]["COUNTRY_CODE"] == "ht" || $data["CQP_COUNTRY"]["COUNTRY_CODE"] == "do" || $data["CQP_COUNTRY"]["COUNTRY_CODE"] == "pr") && $data["CQP_TYPE"] == "STP")) {
                $market["CQP_AUSTRAL_RETENTION"] = 0;
                $market["CQP_AXA"] = $market["CQP_FORTE_SHARE"];
            } else {
                $market["CQP_AUSTRAL_RETENTION"] = $market["CQP_FORTE_SHARE"] * 0.2;
                $market["CQP_AXA"] = $market["CQP_FORTE_SHARE"] * 0.8;
            }
        }

        if (!empty($market["CQP_FORTE_SHARE"])) {
            $market["CQP_GWP_TRANSIT_REINSURER_SHARE"] = $CQP_FORTE_SHARE * $CQP_TOTAL_TTP_GWD;
            $market["CQP_GWP_STORAGE_REINSURER_SHARE"] = $CQP_FORTE_SHARE * $CQP_TOTAL_INVENTORIES_GWP;
            $market["CQP_GWP_TOTAL_EPI_REINSURER_SHARE"] = $market["CQP_GWP_TRANSIT_REINSURER_SHARE"] + $market["CQP_GWP_STORAGE_REINSURER_SHARE"];
            $market["CQP_GWP_MINDEP_REINSURER_SHARE"] = $CQP_FORTE_SHARE * $CQP_MINDEP_USD;
            $market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"] = $CQP_FORTE_SHARE * $CQP_GWP_PRIMA_DE_AJUSTE;
            $market["CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE"] = $market["CQP_GWP_MINDEP_REINSURER_SHARE"] + $market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"];
            $market["CQP_BROKER_DEDUCTION_USD"] = $market["CQP_GWP_MINDEP_REINSURER_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD;
            $market["CQP_BROKER_DEDUCTION_ADJUSTMENT"] = $market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD;
            $market["CQP_BROKER_DEDUCTION_TOTAL"] = $market["CQP_BROKER_DEDUCTION_USD"] + $market["CQP_BROKER_DEDUCTION_ADJUSTMENT"];
            $market["CQP_TAX"] = $market["CQP_GWP_MINDEP_REINSURER_SHARE"] * $CQP_TAX_USD;
            $market["CQP_TAX_ADJUSTMENT"] = $market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"] * $CQP_TAX_USD;
            $market["CQP_TAX_TOTAL"] = $market["CQP_TAX"] + $market["CQP_TAX_ADJUSTMENT"];
            $market["CQP_NWP_AT_100"] = $market["CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE"] * (1 - $CQP_BROKER_DEDUCTIONS_USD - $CQP_TAX_USD);
            $market["CQP_NWP_TRANSIT_REINSURER_SHARE"] = $market["CQP_GWP_TRANSIT_REINSURER_SHARE"] - ($market["CQP_GWP_TRANSIT_REINSURER_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD) - $market["CQP_GWP_TRANSIT_REINSURER_SHARE"] * $CQP_TAX_USD;
            $market["CQP_NWP_STORAGE_REINSURER_SHARE"] = $market["CQP_GWP_STORAGE_REINSURER_SHARE"] - ($market["CQP_GWP_STORAGE_REINSURER_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD) - $market["CQP_GWP_STORAGE_REINSURER_SHARE"] * $CQP_TAX_USD;
            $market["CQP_NWP_TOTAL_EPI_REINSURER_SHARE"] = $market["CQP_NWP_TRANSIT_REINSURER_SHARE"] + $market["CQP_NWP_STORAGE_REINSURER_SHARE"];
            $market["CQP_NWP_MINDEP_REINSURER_SHARE"] = $market["CQP_GWP_MINDEP_REINSURER_SHARE"] - ($market["CQP_GWP_MINDEP_REINSURER_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD) - $market["CQP_GWP_MINDEP_REINSURER_SHARE"] * $CQP_TAX_USD;
            $market["CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX"] = $market["CQP_NWP_MINDEP_REINSURER_SHARE"] + $market["CQP_TAX"];
            $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE"] = $market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"] - $market["CQP_BROKER_DEDUCTION_ADJUSTMENT"] - $market["CQP_TAX_ADJUSTMENT"];
            $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX"] = $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE"] + $market["CQP_TAX_ADJUSTMENT"];
            $market["CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE"] = $market["CQP_NWP_MINDEP_REINSURER_SHARE"] + $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE"];
            $market["CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX"] = $market["CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX"] + $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX"];
            $market["CQP_FORTE_FEE_USD"] = ($market["CQP_GWP_MINDEP_REINSURER_SHARE"] - $market["CQP_BROKER_DEDUCTION_USD"]) * $CQP_UNDERWRITING_EXPENSES;
            $market["CQP_FORTE_FEE_ADJUSTMENT"] = ($market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"] - $market["CQP_BROKER_DEDUCTION_ADJUSTMENT"]) * $CQP_UNDERWRITING_EXPENSES;
            $market["CQP_FORTE_FEE_TOTAL"] = $market["CQP_FORTE_FEE_USD"] + $market["CQP_FORTE_FEE_ADJUSTMENT"];
            $market["CQP_NET_CEDED_PREMIUM_MINDEP"] = $market["CQP_NWP_MINDEP_REINSURER_SHARE"] - $market["CQP_FORTE_FEE_USD"];
            $market["CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT"] = $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE"] - $market["CQP_FORTE_FEE_ADJUSTMENT"];
            $market["CQP_NET_CEDED_PREMIUM"] = $market["CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE"] - $market["CQP_FORTE_FEE_TOTAL"];
            $market["CQP_TRANSIT_REINSURER_SHARE"] = $CQP_TOTAL_TTP_ANNUAL_USD * $CQP_FORTE_SHARE;
            $market["CQP_STORAGE_REINSURER_SHARE"] = $CQP_STORAGE_EEL * $CQP_FORTE_SHARE;
            $market["CQP_CAT_REINSURER_SHARE"] = $CQP_STORAGE_AGG * $CQP_FORTE_SHARE;
        }
    }
}

$CQP_MRE_SHARE = !empty($data["CQP_MRE_SHARE"]) ? ($data["CQP_MRE_SHARE"]  / 100) : 0;

$data["CQP_TOTAL_TTP_GWD_MRE"] = $CQP_TOTAL_TTP_GWD * $CQP_MRE_SHARE;
$data["CQP_TOTAL_INVENTORIES_GWP_MRE"] = $CQP_TOTAL_INVENTORIES_GWP * $CQP_MRE_SHARE;
$data["CQP_GWP_MINDEP_MRE_SHARE"] = $CQP_MINDEP_USD * $CQP_MRE_SHARE; 
$data["CQP_GWP_TOTAL_EPI_MRE_SHARE"] = $data["CQP_TOTAL_TTP_GWD_MRE"] + $data["CQP_TOTAL_INVENTORIES_GWP_MRE"];
$data["CQP_NWP_TOTAL_EPI_MRE_SHARE"] = $data["CQP_GWP_TOTAL_EPI_MRE_SHARE"] - ($data["CQP_GWP_TOTAL_EPI_MRE_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD); 
$data["CQP_NWP_MINDEP_MRE_SHARE"] = $data["CQP_GWP_MINDEP_MRE_SHARE"] - ($data["CQP_GWP_MINDEP_MRE_SHARE"] * $CQP_BROKER_DEDUCTIONS_USD); 
$data["CQP_GWP_TOTAL_EPI_100"] = $CQP_TOTAL_TTP_GWD + $CQP_TOTAL_INVENTORIES_GWP;
$data["CQP_GWP_PRIMA_DE_AJUSTE"] = $CQP_GWP_PRIMA_DE_AJUSTE;
$data["CQP_GWP_MINDEP_PLUS_AJUSTE"] = $CQP_MINDEP_USD + $CQP_GWP_PRIMA_DE_AJUSTE;

//Create Markets Distribution Detail text for emails.
$data["CQP_MARKETS_DETAIL_EMAIL"] = buildMarketsBullets($data["CQP_MARKETS"]);

$currency = $data["CQP_CURRENCY"];
$fxRate = $currency == "USD" ? 1 : (empty($data["CQP_FX_RATE"]) ? 1 : $data["CQP_FX_RATE"] );

// List of fields to get the Fx Rate
$convertFields = [
    "CQP_TRANSIT.0.CQP_TOTAL_TTP_ANNUAL_USD",
    "CQP_STORAGE_EXPOSURE.0.CQP_STORAGE_EEL",
    "CQP_STORAGE.0.CQP_TOTAL_INVENTORIES_GWP",
    "CQP_TRANSIT.0.CQP_TOTAL_TTP_GWD",
    "CQP_STORAGE_EXPOSURE.0.CQP_STORAGE_AGG",
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

$convertFieldsMarket = [
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

$dataReturn = convertUSD($data, $convertFields);

foreach ($dataReturn["CQP_MARKETS"] as $indexMarket => &$market) {
    $market = convertUSD($market, $convertFieldsMarket);
}

//Update insured name if needed
if (($data["EDIT_INSURED_NAME"] ?? "") === "CHANGE") {

    $newName = trim((string)($data["CQP_INSURED_NAME_NEW"] ?? ""));
    if ($newName === "") {
        // Optional: decide if you want to throw error or just skip
        $dataReturn["ERROR_MESSAGE_UPDATE_INSURED_NAME"] = "CQP_INSURED_NAME_NEW is empty. No update performed.";
        return $dataReturn;
    }

    // INSURED collection update
    $insuredCollectionId = getCollectionId("CQP_FORTE_CARGO_INSURED", $apiUrl);
    $insuredRecordId     = $data["CQP_COLLECTION_REQUEST_ID"] ?? null;

    if ($insuredCollectionId && $insuredRecordId) {
        $insuredUpdate = updateCollectionRecordField(
            $apiHost,
            $insuredCollectionId,
            $insuredRecordId,
            "CQP_INSURED_NAME",
            $newName
        );

        if (!empty($insuredUpdate["error"])) {
            $dataReturn["ERROR_MESSAGE"] = $insuredUpdate["message"] ?? "Failed updating insured record.";
            return $dataReturn;
        }
    }

    // HISTORICAL collection update
    $historicalCollectionId = getCollectionId("CQP_FORTE_CARGO_QUOTE_HISTORICAL", $apiUrl);
    $historicalRecordId     = $data["CQP_COLLECTION_HISTORICAL_ID"] ?? null;

    if ($historicalCollectionId && $historicalRecordId) {
        $historicalUpdate = updateCollectionRecordField(
            $apiHost,
            $historicalCollectionId,
            $historicalRecordId,
            "CQP_INSURED_NAME",
            $newName
        );

        if (!empty($historicalUpdate["error"])) {
            $dataReturn["ERROR_MESSAGE"] = $historicalUpdate["message"] ?? "Failed updating historical record.";
            return $dataReturn;
        }
    }

    $dataReturn["CQP_INSURED_NAME"] = $newName;
}

$dataReturn['CQP_FILE_NO'] = $data["CQP_CEDANT"] . "_01_" . $data["CQP_COUNTRY"]["COUNTRY"] . $data["CQP_INSURED_NAME"] . "_UWY" . date("Y", strtotime($data["CQP_INCEPTION_DATE"])) . "_" . $data["CQP_TYPE"] . "_Nr.1";


// Clear error variable to avoid previous error to be shown afterwards
$dataReturn["resErrorHandling"] = "";
$dataReturn["FORTE_ERROR"] = ['data' => ["FORTE_ERROR_LOG" => ""]];
$dataReturn["FORTE_ERROR_MESSAGE"] = "";

return $dataReturn;

/*
 * Convert the current value to USD
 *
 * @param array $dataConvert
 * @param array $convertFieldsVar
 * @return array $dataConvert 
 *
 * by Diego Tapia
 */
function convertUSD ($dataConvert, $convertFieldsVar) {
    global $fxRate;
    
    foreach ($convertFieldsVar as $fields) {
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

class localApi
{
    private function getClient($json = true)
    {
        $headers['Authorization'] = 'Bearer ' . getenv('API_TOKEN');
        if ($json) {
            $headers['Content-Type'] = 'application/json';
            $headers['Accept'] = 'application/json';
        } else {
            $headers['Accept'] = 'application/octet-stream';
        }
        return new Client([
            'curl' => [CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0],
            'allow_redirects' => false,
            'cookies' => true,
            'verify' => false,
            'headers' => $headers
        ]);
    }

    public static function request($method, $url, $params = [], $data = null)
    {
        if (empty($params)) {
            $url = getenv('HOST_URL') . "/api/1.0/{$url}";
        } else {
            $queryString = http_build_query($params);
            $url = getenv('HOST_URL') . "/api/1.0/{$url}?{$queryString}";
        }

        if ($data) {
            $request = new Request($method, $url, [], json_encode($data));
        } else {
            $request = new Request($method, $url);
        }

        try {
            $response = self::getClient()->send($request);
            $content = $response->getBody()->getContents();
            return json_decode($content);
        } catch (Exception $e) {
            print_r([
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public static function requestFile($method, $url, $params = [], $data = null)
    {
        if (empty($params)) {
            $url = getenv('HOST_URL') . "/api/1.0/{$url}";
        } else {
            $queryString = http_build_query($params);
            $url = getenv('HOST_URL') . "/api/1.0/{$url}?{$queryString}";
        }

        if ($data) {
            $request = new Request($method, $url, [], json_encode($data));
        } else {
            $request = new Request($method, $url);
        }
        try {
            $response = self::getClient(false)->send($request);
            $content = $response->getBody()->getContents();
            return json_decode($content);
        } catch (Exception $e) {
            print_r([
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public static function get($url, $params = [])
    {
        $response = self::request('GET', $url, $params);
        $response = json_encode($response);
        return json_decode($response, true);
    }

    public static function post($url, $data)
    {
        $response = self::request('POST', $url, [], $data);
        return $response;
    }

    public static function delete($url)
    {
        $response = self::request('DELETE', $url, [], $data);
        return $response;
    }

    public static function put($url, $data)
    {
        $response = self::request('PUT', $url, [], $data);
        return $response;
    }

    public static function getFile($url, $params = [])
    {
        $response = self::requestFile('GET', $url, $params);
        return $response;
    }
}

/*
* Builds an HTML bullet list string from the CQP_MARKETS array
* Format: - Reinsurer Full Name - XX.XX% p/d 100% Line to stand
*
* @param array $markets  // CQP_MARKETS array
* @return string        // HTML formatted bullet list
*
* by Adriana Centellas
*/

function buildMarketsBullets(array $markets)
{
    $output = [];

    foreach ($markets as $market) {
        if ($market["taken"]) {
            // Defensive: ensure required fields exist
            if (!isset($market['CQP_REINSURER_FULLNAME']) || !isset($market['CQP_FORTE_SHARE'])) {
                continue;
            }

            // Format percentage to 2 decimals
            $percentage = number_format((float)$market['CQP_FORTE_SHARE'], 2);

            // Build line
            $line = "- " 
                . $market['CQP_REINSURER_FULLNAME'] 
                . " - " 
                . $percentage 
                . "% p/d 100% Line to stand";

            $output[] = $line;
        }
    }

    // Join as HTML bullets
    return implode("<br>\n", $output);
}

/*
* Updates a record field in a ProcessMaker collection record.
*
* @param (string) $apiHost //1-10
* @param (string|int) $collectionId
* @param (string|int) $recordId
* @param (string) $fieldName
* @param (mixed) $fieldValue
* @return (array) Updated record response
*
* by Adriana Centellas
*/
function updateCollectionRecordField($apiHost, $collectionId, $recordId, $fieldName, $fieldValue)
{
    $url = rtrim($apiHost, '/') . "/collections/" . $collectionId . "/records/" . $recordId;

    $getResponse = callApiUrlGuzzle($url, "GET");
    $recordData  = $getResponse["data"] ?? null;

    if (!is_array($recordData)) {
        return ["error" => true, "message" => "Record data not found for URL: " . $url];
    }

    // Clone and update only the needed field
    $recordData[$fieldName] = $fieldValue;

    $putResponse = callApiUrlGuzzle($url, "PUT", ["data" => $recordData]);

    return $putResponse;
}