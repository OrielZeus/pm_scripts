<?php 

/*****************************************
* Get original data and save
*
* by Diego Tapia
*****************************************/
ini_set('memory_limit', '-1');
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
require 'vendor/autoload.php';

// Set Default values
$apiInstanceCollections = $api->collections();
$apiInstance = $api->files();
$EXCEL_TEMPLATE_ID = 31111;

// Get list of field related to a dropdown control
$collections = json_decode(json_encode($apiInstanceCollections->getCollections(null,"ID", "desc", "1000")->getData()));
$collectionOriginal = getCollectionId("CQP_FORTE_CARGO_ORIGINAL_REQUESTS");
$countriesCollection = json_decode(json_encode($apiInstanceCollections->getRecords(getCollectionId("CQP_FORTE_CARGO_COUNTRIES"),null , 1000)->getData()), true);
$countriesSearch = array_column(array_column($countriesCollection, "data"), "COUNTRY");
$riskCollection = json_decode(json_encode($apiInstanceCollections->getRecords(getCollectionId("CQP_FORTE_CARGO_RISKS"), null, 1000)->getData()), true);
$cedantCollection = json_decode(json_encode($apiInstanceCollections->getRecords(getCollectionId("CQP_FORTE_CARGO_CEDANT"), null, 1000)->getData()), true);
$cedantCollection = array_column($cedantCollection, "data");
$brokerCollection = json_decode(json_encode($apiInstanceCollections->getRecords(getCollectionId("CQP_FORTE_CARGO_BROKER"), null, 1000)->getData()), true);
$brokerCollection = array_column($brokerCollection, "data");
$commoditiesCollectionList = json_decode(json_encode($apiInstanceCollections->getRecords(getCollectionId("CQP_FORTE_CARGO_COMMODITIES"), null, 1000)->getData()), true);
$commoditiesCollection = [];

// Validate the collection to register the historical data
if ($collectionOriginal !== false) {
    
    // Validates dropdown fields
    foreach ($commoditiesCollectionList as $commodity) {
        $commoditiesCollection[] = $commodity["data"]["COMMODITY"];
    }

    $countriesFinal = [];

    foreach ($riskCollection as $risk) {
        $search = array_search(strtolower($risk["data"]["COUNTRY"]),array_map('strtolower', $countriesSearch));
        if ($search !== false) {
            $countriesFinal[] = [
                "COUNTRY" =>  $countriesCollection[$search]["data"]["COUNTRY"],
                "COUNTRY_CODE" =>  $countriesCollection[$search]["data"]["COUNTRY_CODE"],
                "RISK" =>  $risk["data"]["RISK"],
                "TYPE" =>  $risk["data"]["TYPE"]
            ];
        }
    }

    $countriesSearch = array_column($countriesFinal, "COUNTRY");
    $accent = array('á','é','í','ó','ú','Á','É','Í','Ó','Ú');
    $noAccent = array('a','e','i','o','u','A','E','I','O','U');

    array_walk($countriesSearch, function(&$valor) {
        global $accent, $noAccent;
        $valor = str_replace($accent, $noAccent, $valor);
    });

    // Process excel information
    $excelTemplateName = getExcelTemplate($EXCEL_TEMPLATE_ID);
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('/tmp/'. $excelTemplateName);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();
    $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

    // Format data to import in to the collection
    $dataRes = [];
    $listNames = [];
    $misssingCedant = [];
    $misssingBroker = [];
    $misssingCommodity = [];

    for ($row = 3; $row <= $highestRow; ++$row) {
        $tempRow = [];
        for ($col = 1; $col <= $highestColumnIndex; ++$col) {
            if ($col == "13" || $col == "14") {
                $cell = $worksheet->getCellByColumnAndRow($col, $row);
                $tempRow[$col] = $cell->getFormattedValue();
            } else {
                $cell = $worksheet->getCellByColumnAndRow($col, $row);
                $tempRow[$col] = trim($cell->getCalculatedValue());
            }
        }
        
        if ($tempRow["7"] == "New" || $tempRow["7"] == "NEW" || $tempRow["7"] == "Renewal" || $tempRow["7"] == "RENEWAL") {
            $indexInsured = trim($tempRow["4"]);
            $temp = [];
            $market = [];
            
            if ($dataRes[$indexInsured] == null) {

                $searchInCode = array_search($tempRow["6"], array_column($listNames, "CQP_INSURED_NAME"));

                if ($searchInCode !== false) {
                    $newCode = $listNames[$searchInCode]["CQP_INSURED_CODE"];
                } else {
                    $newCode = str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
                    $listNames[] = [
                        "CQP_INSURED_CODE" => $newCode,
                        "CQP_INSURED_NAME" => $tempRow["6"]
                    ];
                }

                $temp["CQP_TYPE"] = strtoupper($tempRow["3"]);
                $temp["CQP_INSURED_CODE"] = $newCode;
                $temp["CQP_PIVOT_TABLE_NUMBER"] = $tempRow["4"];
                $temp["CQP_CONTRACT"] = $tempRow["5"];
                $temp["CQP_CURRENCY"] = "USD";
                $temp["CQP_INSURED_NAME"] = $tempRow["6"];
                $temp["CQP_RENEWAL_CARGO"] = $tempRow["7"] == "NEW" || $tempRow["7"] == "New" ? "NO" : "YES";
                $temp["CQP_CEDANT"] = $tempRow["10"];
                $cedant = array_search( $tempRow["10"], array_column($cedantCollection, "CEDANT"));

                if ($cedant === false) {
                    $temp["CQP_CEDANT"] = $tempRow["10"];
                    $misssingCedant[] = $tempRow["10"];
                } else {
                    $temp["CQP_CEDANT"] = $tempRow["10"];
                }

                $temp["CQP_COUNTRY"] = ($tempRow["11"] == null || $tempRow["11"] == "" ? "" : (array_search(str_replace($accent, $noAccent, strtoupper($tempRow["11"])), $countriesSearch) !== false ? $countriesFinal[array_search(str_replace($accent, $noAccent, strtoupper($tempRow["11"])), $countriesSearch)] : ""));
                $broker = array_search( $tempRow["12"], array_column($brokerCollection, "COMPANY_NAME"));

                if ($broker === false) {
                    $temp["CQP_REINSURANCE_BROKER"] = $brokerCollection[$broker];
                    $misssingBroker[] = $tempRow["12"];
                } else {
                    $temp["CQP_REINSURANCE_BROKER"] = $brokerCollection[$broker];
                }

                $incetionDate = DateTime::createFromFormat('j-M-Y', $tempRow["13"]);
                $incetionDate = $incetionDate->format('Y-m-d');
                $temp["CQP_INCEPTION_DATE"] = $incetionDate;
                $incetionDate = DateTime::createFromFormat('j-M-Y', $tempRow["14"]);
                $incetionDate = $incetionDate->format('Y-m-d');
                $temp["CQP_EXPIRATION_DATE"] = $incetionDate;
                $temp["CQP_MONTHS"] = $tempRow["15"];
                $temp["CQP_UNDERWRITING_YEAR"] = $tempRow["16"];
                $temp["CQP_UW_FWK"] = $tempRow["17"];
                $temp["CQP_UW_QUARTER"] = $tempRow["18"];
                $temp["CQP_INCEPTION_MONTH"] = $tempRow["19"];
                $temp["CQP_ADJUSTMENT_DATE"] = $tempRow["20"];
                $temp["CQP_ADJUSTMENT_YEAR"] = $tempRow["21"];
                $temp["CQP_ADJUSTMENT_MONTH"] = $tempRow["22"];
                $temp["CQP_ADJUSTMENT_QUARTER"] = $tempRow["23"];
                $temp["CQP_INTEREST"] = $tempRow["24"];
                $commodity = array_search(explode(":", $tempRow["25"])[0], $commoditiesCollection);
                $temp["CQP_COMMODITIES_PROFILE"] = explode(":", $tempRow["25"])[0];

                if ($commodity === false) {
                    $misssingCommodity[] =  $tempRow["25"];
                }

                $temp["CQP_TOTAL_TTP_ANNUAL_USD"] = $tempRow["26"]; 
                $temp["CQP_TRANSIT_USD_100"] = $tempRow["27"];
                $temp["CQP_STORAGE_EEL"] = $tempRow["28"];
                $temp["CQP_STORAGE_AGG"] = $tempRow["29"];
                $temp["CQP_TOTAL_TTP_GWD"] = $tempRow["30"];
                $temp["CQP_TOTAL_INVENTORIES_GWP"] = $tempRow["31"];
                $temp["CQP_GWP_TOTAL_EPI_100"] = $tempRow["32"];
                $temp["CQP_MINDEP_USD"] = $tempRow["33"];
                $temp["CQP_GWP_PRIMA_DE_AJUSTE"] = $tempRow["34"];
                $temp["CQP_GWP_MINDEP_PLUS_AJUSTE"] = $tempRow["35"];
                $temp["CQP_BROKER_DEDUCTIONS"] = $tempRow["46"] != null && $tempRow["46"] != "" ? str_replace("%", "", $tempRow["46"]) * 100 : "";
                $temp["CQP_TAX_USD_IF_APPLAY"] = $tempRow["50"] != null && $tempRow["50"] != "" ? str_replace("%", "", $tempRow["50"]) * 100 : "";
                $temp["CQP_NWP_AT_100"] = $tempRow["54"];
                $temp["CQP_UNDERWRITING_EXPENSES"] = $tempRow["64"] != null && $tempRow["64"] != "" ? str_replace("%", "", $tempRow["64"]) * 100 : "";
                $temp["CQP_N_INSTALLMENTS"] = $tempRow["74"];
                $temp["CQP_MARKETS"] = [];
            } else {
                $temp = $dataRes[$indexInsured];
            }

            $market["CQP_REINSURER"] = $tempRow["36"];
            $market["CQP_FORTE_SHARE"] = $tempRow["37"] != null && $tempRow["37"] != "" ? str_replace("%", "", $tempRow["37"]) * 100 : "";
            $market["CQP_AUSTRAL_RETENTION"] = $tempRow["38"] != null && $tempRow["38"] != "" ? str_replace("%", "", $tempRow["38"]) * 100 : "";
            $market["CQP_AXA"] = $tempRow["39"] != null && $tempRow["39"] != "" ? str_replace("%", "", $tempRow["39"]) * 100 : "";
            $market["CQP_GWP_TRANSIT_REINSURER_SHARE"] = $tempRow["40"];
            $market["CQP_GWP_STORAGE_REINSURER_SHARE"] = $tempRow["41"];
            $market["CQP_GWP_TOTAL_EPI_REINSURER_SHARE"] = $tempRow["42"];
            $market["CQP_GWP_MINDEP_REINSURER_SHARE"] = $tempRow["43"];
            $market["CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE"] = $tempRow["44"];
            $market["CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE"] = $tempRow["45"];
            $market["CQP_BROKER_DEDUCTION_USD"] = $tempRow["47"];
            $market["CQP_BROKER_DEDUCTION_ADJUSTMENT"] = $tempRow["48"];
            $market["CQP_BROKER_DEDUCTION_TOTAL"] = $tempRow["49"];
            $market["CQP_TAX"] = $tempRow["51"];
            $market["CQP_TAX_ADJUSTMENT"] = $tempRow["52"];
            $market["CQP_TAX_TOTAL"] = $tempRow["53"];
            $market["CQP_NWP_TRANSIT_REINSURER_SHARE"] = $tempRow["55"];
            $market["CQP_NWP_STORAGE_REINSURER_SHARE"] = $tempRow["56"];
            $market["CQP_NWP_TOTAL_EPI_REINSURER_SHARE"] = $tempRow["57"];
            $market["CQP_NWP_MINDEP_REINSURER_SHARE"] = $tempRow["58"];
            $market["CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX"] = $tempRow["59"];
            $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE"] = $tempRow["60"];
            $market["CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX"] = $tempRow["61"];
            $market["CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE"] = $tempRow["62"];
            $market["CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX"] = $tempRow["63"];
            $market["CQP_FORTE_FEE_USD"] = $tempRow["65"];
            $market["CQP_FORTE_FEE_ADJUSTMENT"] = $tempRow["66"];
            $market["CQP_FORTE_FEE_TOTAL"] = $tempRow["67"];
            $market["CQP_NET_CEDED_PREMIUM_MINDEP"] = $tempRow["68"];
            $market["CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT"] = $tempRow["69"];
            $market["CQP_NET_CEDED_PREMIUM"] = $tempRow["70"];
            $market["CQP_TRANSIT_REINSURER_SHARE"] = $tempRow["71"];
            $market["CQP_STORAGE_REINSURER_SHARE"] = $tempRow["72"];
            $market["CQP_CAT_REINSURER_SHARE"] = $tempRow["73"];
            $temp["CQP_MARKETS"][] = $market;
            $dataRes[$indexInsured] = $temp;
        }
    }
    
    // Truncate collection and insert new data
    $apiInstanceCollections->truncateCollection($collectionOriginal);

    foreach ($dataRes as $row) {
        $record = new \ProcessMaker\Client\Model\RecordsEditable();
        $record->setData($row);
        $result = $apiInstanceCollections->createRecord($collectionOriginal, $record);
    }

    return [
        "synch" => $dataRes,
        "misssingBroker" => array_values(array_unique($misssingBroker)),
        "misssingCedant" => array_values(array_unique($misssingCedant)),
        "misssingCommodity" => array_values(array_unique($misssingCommodity))
    ];
}

return null;

/* Get template file and temp path
 *
 * @param array $excelTemplateId
 * @return string $fileName
 *
 * by Diego Tapia
*/
function getExcelTemplate($excelTemplateId){
    global $apiInstance;
    $file = $apiInstance->getFileById($excelTemplateId);
    $fileName = $file->getFileName();
    $file = $apiInstance->getFileContentsById($excelTemplateId);
    rename($file->getPathname(), '/tmp/'.$fileName);
    chmod('/tmp/'.$fileName, 777);
    return $fileName;
}

/*
* Get collection ID using the name
*
* @param string $name
* @return int $collectionId
*
* by Diego Tapia
*/
function getCollectionId ($name) {
    global $collections;
    $index = array_search($name, array_column($collections, "name"));
    
    if ($index !== false) {
        $collection = $collections[$index];
        if ($collection == null || $collection === false) {
            return false;
        } else {
            return $collection->id;
        }
    }

    return false;
}