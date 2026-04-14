<?php 
/*  
* Calculate new premium for Extension dates
* by Ana Castillo
* modified by Helen Callisaya
*/
//Set variable of return
$dataReturn = array();

/**
* Function that calls the OpenL
*
* @param (String) $url
* @param (Object) $dataSend
* @return (Array) $aDataResponse
*
* by Ana Castillo
*/
function callCurlOpenL($url, $dataSend)
{
    //Curl init
    $curl = curl_init();
    //Curl to the End point
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($dataSend),
        CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json"
        ),
    ));
    //Set response
    $responseCurl = curl_exec($curl);
    //Set error
    $errorCurl = curl_error($curl);
    curl_close($curl);

    //Set array to response
    $aDataResponse = array();
    $aDataResponse["ERROR"] = $errorCurl;
    $aDataResponse["DATA"] = $responseCurl;

    //Return Response
    return $aDataResponse;
}
/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @return (Array) $responseCurl
 *
 * by Helen Callisaya
 */
function callGetCurl($url, $method, $json_data)
{
    try {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $json_data,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . getenv('API_TOKEN'),
                "Content-Type: application/json",
                "cache-control: no-cache"
            ),
        ));
        $responseCurl = curl_exec($curl);
        if ($responseCurl === false) {
            throw new Exception('Curl error: ' . curl_error($curl));
        }
        $responseCurl = json_decode($responseCurl, true);
        curl_close($curl);
        return $responseCurl;
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }
}
/* 
 * Insert or update data in collection
 *
 * @param (string) $idCollection
 * @param (string) $requestId
 * @param (string) $json_data  
 * @return none
 *
 * by Helen Callisaya
 */
function saveUpdateCollection($idCollection, $requestId, $json_data, $fieldRequest)
{
    $searchRequestUrl = getenv('API_HOST') . '/collections/' . $idCollection . '/records?pmql=(data.' . $fieldRequest . '="' . $requestId . '")';
    $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
    if (count($searchRequest["data"]) == 0) {
        $insertRequestUrl = getenv('API_HOST') . '/collections/' . $idCollection . '/records';
        $insertRequest = callGetCurl($insertRequestUrl, "POST", $json_data);
    } else {
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . $idCollection . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", $json_data);
    }    
}

//-----------------------------------------------------------------------------------------------
$openLUrl = getenv('OPENL_CONNECTION');
/*********************** Get Coverage Rate if it is necessary *********************************/
//Set Rate no Losses
$rateNoLosses = 0;
if (count(explode("NOLOSSES", $data["YQP_PRODUCT"])) > 1) {
    //Set necessary variables
    $dataSend = array();
    $dataSend['Country'] = $data["YQP_COUNTRY_BUSINESS"];

    //Set OpenL Url
    $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetAdditionalRateAnnualBasic";

    //Call Curl OpenL
    $aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
    //Set response
    $response = $aResponse["DATA"];
    //Set error
    $err = $aResponse["ERROR"];
    if ($err) {
        //If there is an error, set the error
        $dataReturn['END_EXTENSION_RATE_COVERAGE_RESPONSE_OPENL'] = "cURL Error #:" . $err;
    } else {
        //Get values of the response
        $response = json_decode($response);
        $dataReturn['END_EXTENSION_RATE_COVERAGE_RESPONSE_OPENL'] = $response;
        if ($response != "" && $response != null) {
            $rateNoLosses = $response;
        }
    }
}
//--------------------
$exchange = $data["YQP_EXCHANGE_RATE"];
$finalRate = $data['YQP_FINAL_RATE_VALUE'];

$periodFrom = new DateTime($data['YQP_PERIOD_FROM']);
$periodTo  = new DateTime($data['END_NEW_PERIOD_TO']);
$dateDifference = $periodFrom->diff($periodTo);
$daysDifference = $dateDifference->days;

//$dataReturn['YQP_DAYS_DIFFERENCE'] = $daysDifference;
/*********************** Get Other Coverages *********************************/
//Set necessary variables
$dataSend = array();
$dataSend['final1'] = array();
$dataSend['final1']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
$dataSend['final1']['FinalRate'] = $finalRate;
$dataSend['final1']['TypeYacht'] = $data["YQP_TYPE_YACHT"];
$dataSend['final1']['Country'] = $data["YQP_COUNTRY_BUSINESS"];
if ($data["YQP_PERSONAL_EFFECTS"] == "YES") {
    $dataSend['final1']['PersonalEffects'] = $data["YQP_PERSONAL_EFFECTS"];
    $dataSend['final1']['PersonalEffectsSumInsured'] = ($data["YQP_PERSONAL_EFFECTS_LIMIT"] * $exchange);
} else {
    $dataSend['final1']['PersonalEffects'] = "";
    $dataSend['final1']['PersonalEffectsSumInsured'] = 0;
}
if ($data["YQP_MEDICAL_PAYMENTS"] == "YES") {
    $dataSend['final1']['MedicalPayments'] = $data["YQP_MEDICAL_PAYMENTS"];
    $dataSend['final1']['MedicalPaymentsSumInsured'] = ($data["YQP_MEDICAL_PAYMENTS_LIMIT"]*$exchange);
} else {
    $dataSend['final1']['MedicalPayments'] = "";
    $dataSend['final1']['MedicalPaymentsSumInsured'] = 0;
}
$maxTender = 0;
$maxSumInsuredTender = 0;
$sumInsuredWar = 0;
$descriptionTenderTowed = "";
$flagTenderTowed = "NO";
if ($data["YQP_TENDERS"] == "YES") {
    $tendersGrid = $data["YQP_TENDERS_INFORMATION"];
    for ($t = 0; $t < count($tendersGrid); $t++) {
        if ($tendersGrid[$t]['YQP_TENDERS_TOWED'] == "YES") {
            $descriptionTenderTowed = $tendersGrid[$t]['YQP_TENDERS_DESCRIPTION'];
            $flagTenderTowed = "YES";
        }
        $tG = $t + 1;
        $tenderSumInsured = ($tendersGrid[$t]["YQP_TENDERS_LIMIT"] * $exchange);
        $tenderSameHull = $tendersGrid[$t]["YQP_TENDERS_HULL"];
        $dataSend['final1']['TenderPercentage' . $tG] = $tenderSameHull;
        $dataSend['final1']['TenderSumInsured' . $tG] = $tenderSumInsured;

        //Add the sum to war if the Tender is not in Hull
        if ($tenderSameHull == "NO") {
            $sumInsuredWar = $sumInsuredWar + $tenderSumInsured;
            //Get the max value of Tenders if The Tender is not in Hull
            if ($tenderSumInsured > $maxSumInsuredTender) {
                $maxSumInsuredTender = $tenderSumInsured;
            }
        }
    }
    $maxTender = count($tendersGrid);
} else {
    $dataSend['final1']['TenderSumInsured1'] = 0;
    $dataSend['final1']['TenderPercentage1'] = 0;
}
if ($data["YQP_WAR"] == "YES") {
    $dataSend['final1']['TypeCoverage'] = $data["YQP_WAR_TYPE_COVERAGE"];
    $dataSend['final1']['WarSumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
    $dataReturn["END_EXTENSION_DATA_WAR_SUM_INSURED"] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
} else {
    $dataSend['final1']['TypeCoverage'] = "";
    $dataSend['final1']['WarSumInsured'] = 0;
    $dataReturn["END_EXTENSION_DATA_WAR_SUM_INSURED"] = $data["YQP_SUM_INSURED_VESSEL"];
}
$dataSend['final1']['LimitPI'] = ($data["YQP_LIMIT_PI"] * $exchange);
//Set Excluded No Losses
if ($rateNoLosses != 0) {
    $dataSend['final1']['ExcludedPartialLosses'] = "YES";
} else {
    $dataSend['final1']['ExcludedPartialLosses'] = "NO";
}
$dataSend['final1']['MaxTenders'] = $maxTender;
$dataSend['final1']['Language'] = $data["YQP_LANGUAGE"];
$dataSend['final1']['BaseText'] = $data["YQP_BASE_TEXT"];
$brokerNecessary = "NO";
$brokerPercentage = 0;
if ($data["YQP_GROSS_BROKER_CHANGE"]) {
    $brokerNecessary = "YES";
    $brokerPercentage = (($data["YQP_BROKER_PERCENTAGE"] * 1) / 100);
}
$dataSend['final1']['BrokerNecessary'] = $brokerNecessary;
$dataSend['final1']['PercentageBroker'] = $brokerPercentage;
$dataSend['final1']['DaysPeriod'] = $daysDifference;

//Set data Send to Open L
$dataReturn["END_EXTENSION_DATA_OTHER_COVERAGES_OPENL_SEND"] = $dataSend;
$dataReturn["END_EXTENSION_DATA_WAR_SUM_INSURED_TENDER"] = $sumInsuredWar;

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/BusinessFinalPremium";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Total Prime
$htmlTotalPrime = "";

//Set total of Prime for Annual Premium and Gross Annual Premium
$totalPrimeAnnual = 0;
$totalPrimeGross = 0;

//Set difference Limit PI
$differenceLimitPI = 0;

if ($err) {
    //If there is an error, set the error
    $dataReturn['END_EXTENSION_YQP_OTHER_COVERAGES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataReturn['END_EXTENSION_OTHER_COVERAGES_RESPONSE_OPENL'] = $response;
    if ($response != "" && $response != null) {
        //Set table with depends on Broker exist
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            $htmlTotalPrime .= "<table width='90%' border='1'>";
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Annual Premium</b></td><td style='padding: 5px;'><b>Gross Annual Premium</b></td></tr>";
        } else {
            $htmlTotalPrime .= "<table width='70%' border='1'>";
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Annual Premium</b></td></tr>";
        }
        //Reorder the response to create the HTMl
        $responseConverted = array();
        foreach ($response as $key => $value) {
            //Set action and openL variables
            $action = explode("_", $key)[0];
            $variableOpenL = explode("_", $key, 2)[1];
            //Not consider options not necessary
            if ($variableOpenL != "_90Percent" && $variableOpenL != "_10Percent" && $variableOpenL != "PrimaTotal" && 
                $variableOpenL != "DifferenceLimitPI" && $variableOpenL != "MaxTenders") {
                //Add the option if not exist
                if (!isset($responseConverted[$variableOpenL])) {
                    $responseConverted[$variableOpenL] = array();
                }
                $responseConverted[$variableOpenL][$action] = $value;
            }

            //Set difference PI to use in next rule
            if ($variableOpenL == "DifferenceLimitPI" && $action == "AnnualPremium") {
                $differenceLimitPI = $value * 1;
            }
        }
        $dataReturn['END_EXTENSION_YQP_OTHER_COVERAGES_RESPONSE_CONVERTED'] = $responseConverted;

        //Form table of HTML
        foreach ($responseConverted as $key => $value) {
            //Replace & and espace in Key if it is necessary
            $key = str_replace("_AND_", "&", $key);
            $key = str_replace("_", " ", $key);
            //Validate if we need to draw or not
            $validateDraw = true;

            //If it is a tender validate that the tender was selected on the request
            if (count(explode("Tender ", $key)) > 1) {
                if ($maxTender < (explode("Tender ", $key)[1] * 1)) {
                    $validateDraw = false;
                }
            }

            if ($validateDraw) {
                //Set total value to sum Broker or normal
                if ($data["YQP_GROSS_BROKER_CHANGE"]) {
                    $totalPrimeGross = $totalPrimeGross + ($value["GrossAnnualPremium"] * 1);
                    $totalPrimeAnnual = $totalPrimeAnnual + ($value["AnnualPremium"] * 1);
                    //Change format number if the value is a number for Gross Annual Premium that cannot exist
                    //Separate the string into an array
                    $validateIsNumeric = explode(" ", $value["GrossAnnualPremium"]);                    
                    for ($vn = 0; $vn < count($validateIsNumeric); $vn++) {
                        //Validate value is numeric
                        if (is_numeric($validateIsNumeric[$vn])) {
                            $validateIsNumeric[$vn] = number_format($validateIsNumeric[$vn], 2, '.', ',');                            
                        }                  
                    }
                    //Convert array to string
                    $value["GrossAnnualPremium"] = implode(" ", $validateIsNumeric);
                } else {
                    $totalPrimeAnnual = $totalPrimeAnnual + ($value["AnnualPremium"] * 1);
                }

                //Change format number if the value is a number for Annual Premium that always exist
                //Separate the string into an array
                $validateIsNumeric = explode(" ", $value["AnnualPremium"]);                    
                for ($vn = 0; $vn < count($validateIsNumeric); $vn++) {
                    //Validate value is numeric
                    if (is_numeric($validateIsNumeric[$vn])) {
                        $validateIsNumeric[$vn] = number_format($validateIsNumeric[$vn], 2, '.', ',');                            
                    }                  
                }
                //Convert array to string
                $value["AnnualPremium"] = implode(" ", $validateIsNumeric);


                //Set html if exist Broker as a new column or not
                if ($data["YQP_GROSS_BROKER_CHANGE"]) {
                    //Set html with Broker
                    $htmlTotalPrime .= "<tr><td width='30%' style='text-align: left; padding: 5px;'>" . $key . "</td>";
                    $htmlTotalPrime .= "<td width='35%' style='text-align:right; padding: 5px;'>" . $value["AnnualPremium"] . "</td>";
                    $htmlTotalPrime .= "<td width='35%' style='text-align:right; padding: 5px;'>" . $value["GrossAnnualPremium"] . "</td></tr>";
                } else {
                    //Set html without Broker
                    $htmlTotalPrime .= "<tr><td width='50%' style='text-align: left; padding: 5px;'>" . $key . "</td>";
                    $htmlTotalPrime .= "<td width='50%' style='text-align:right; padding: 5px;'>" . $value["AnnualPremium"] . "</td></tr>";
                }
            }
        }
        //Add total Premium value ass Annual Total
        $dataReturn['END_EXTENSION_YQP_TOTAL_PREMIUM'] = $totalPrimeAnnual;

        //Change format number if the total prime is a number for Annual
        if (is_numeric($totalPrimeAnnual)) {
            $totalPrimeAnnual = number_format($totalPrimeAnnual, 2, ".", ",");
        }

        //Add final Rate to the table
        //Set total Prime if Broker exist or not
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            //Change format number if the total prime is a number
            if (is_numeric($totalPrimeGross)) {
                $totalPrimeGross = number_format($totalPrimeGross, 2, ".", ",");
            }
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'>";
            $htmlTotalPrime .= "<td width='30%' style='text-align: left; padding: 5px;'><b>PRIMA TOTAL 100.00%</b></td>";
            $htmlTotalPrime .= "<td width='35%' style='text-align: right; padding: 5px;'><b>" . $totalPrimeAnnual . "</b></td>";
            $htmlTotalPrime .= "<td width='35%' style='text-align: right; padding: 5px;'><b>" . $totalPrimeGross . "</b></td></tr>";          
        } else {
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td width='50%' style='text-align: left; padding: 5px;'><b>PRIMA TOTAL 100.00%</b></td>";
            $htmlTotalPrime .= "<td width='50%' style='text-align: right; padding: 5px;'><b>" . $totalPrimeAnnual . "</b></td></tr>";
        }

        //Close Table
        $htmlTotalPrime .= "</table>";
        //Add html with other coverages table
        $dataReturn['END_EXTENSION_YQP_OTHER_COVERAGES_RESPONSE'] = $htmlTotalPrime;

        //Set Html of Slip Final Premium
        $dataReturn['END_EXTENSION_YQP_DOCUMENT_FINAL_PREMIUM_HTML'] = htmlentities($htmlTotalPrime);
    }
}

//Calculos Nuevos


//-----------------
//$dataReturn['END_EXTENSION_YQP_TOTAL_PREMIUM'] = $totalPrimeAnnual;
$dataHistory = $data['END_CALCULATE_HISTORY'];

$periodFrom = $dataHistory['FORTE_END_PERIOD_FROM'];
$periodTo = $dataHistory['FORTE_END_PERIOD_TO'];
$premiumOriginalOld = $dataHistory['FORTE_END_ORIGINAL_PREMIUM'];
$premiumNewOld = $dataHistory['FORTE_END_NEW_PREMIUM']; //Prima que se arrastra
$cumulutivePremium= $dataHistory['FORTE_END_CUMULUTIVE'];
$totalDays = $dataHistory['FORTE_END_DAYS_DIFERENCE'];//Dias del rango de fechas
$typeEndorsementOld = $dataHistory['FORTE_END_TYPE_ENDORSEMENT'];
$calculateType = $dataHistory['FORTE_END_CALCULATE_TYPE'];

$dateExtension = $data['END_NEW_PERIOD_TO'];//Nueva fecha de extension
$newPeriodTo =  new DateTime($dateExtension);
//$validity = $data['END_VALIDITY_ENDORSEMENT']; //Fecha de vigencia de la extension

$validityEndorsement  = new DateTime($validity); //Revisar
$periodFromDate = new DateTime($periodFrom);//Revisar
$periodToDate = new DateTime($periodTo);

//Get Days Original Coverage - Dias Transcurridos
$daysCoverage = $totalDays;
//Get Premium Coverage - Prima Transcurrida
$premiumCoverage = ($premiumNewOld / $totalDays) * $daysCoverage;

//Get Days Coverage Accrue - Dias Devengar
$differenceNewPeriodTo = $newPeriodTo->diff($periodToDate);
$daysCoverageAccrue = $differenceNewPeriodTo->days;//157

//Get Earned Premium - Prima devengar
$earnedPremium = ($dataReturn['END_EXTENSION_YQP_TOTAL_PREMIUM'] / ($totalDays + $daysCoverageAccrue)) * $daysCoverageAccrue;

//Get Risk Annual Premium - Prima anual del riesgo
$riskAnnualPremium = $premiumNewOld + $earnedPremium;
//Get accrued premium - prima acumulada
$accruedPremium = $premiumNewOld;
//Get premium endorsement - Prima de endoso
$premiumEndorsement = $riskAnnualPremium - $accruedPremium;

//--------------Get Premium Endorsement
$dataReturn['END_VALUE_PREMIUM_ENDORSEMENT'] = round($premiumEndorsement, 2);
$dataReturn['END_COLLECTION_OLD_PREMIUM'] = round($premiumNewOld, 2);
$dataReturn['END_COLLECTION_TOTAL_DAYS'] = $totalDays;
$dataReturn['END_COLLECTION_NEW_RECALCULATED_PREMIUM'] = round($dataReturn['END_EXTENSION_YQP_TOTAL_PREMIUM'], 2);
//$dataReturn['END_COLLECTION_DAYS_COVERAGE'] = $daysDifferenceCoverage;
$dataReturn['END_COLLECTION_PREMIUM_COVERAGE'] = round($premiumCoverage, 2);
$dataReturn['END_COLLECTION_DAYS_COVERAGE_ACCRUE'] = $daysCoverageAccrue;
$dataReturn['END_COLLECTION_EARNED_PREMIUM'] = round($earnedPremium, 2);
$dataReturn['END_COLLECTION_ACCRUED_PREMIUM'] = round($accruedPremium, 2);

$dataReturn['END_TOTAL_PREMIUM_EXTENSION'] = $premiumEndorsement;
//Save in collecction
$requestId = $data['_request']['id'];
$dataSave = array();
$dataSave['FORTE_EP_REQUEST'] = $data['_request']['id'];
$dataSave['FORTE_EP_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
$dataSave['FORTE_EP_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
$dataSave['FORTE_EP_OLD_REQUEST'] = $data['END_REQUEST_ENDORSEMENT_OLD'];
$dataSave['FORTE_EP_PERIOD_FROM'] = $periodFrom;
$dataSave['FORTE_EP_PERIOD_TO'] = $periodTo;
$dataSave['FORTE_EP_TOTAL_DAYS'] = $totalDays + $daysCoverageAccrue;
$dataSave['FORTE_EP_ORIGINAL_PREMIUM'] = $premiumOriginalOld;
$dataSave['FORTE_EP_NEW_PREMIUM'] = $premiumNewOld;
$dataSave['FORTE_EP_NEW_PERIOD_FROM'] = $periodFrom;//$validity;
$dataSave['FORTE_EP_NEW_PERIOD_TO'] = $dateExtension;//$periodTo;
$dataSave['FORTE_EP_VALIDITY_ENDORSEMENT'] = $periodFrom;//$validity;
$dataSave['FORTE_EP_DATE_EXTENSION'] = $dateExtension;
$dataSave['FORTE_EP_DAYS_ORIGINAL_COVERAGE'] = $daysCoverage;
$dataSave['FORTE_EP_PREMIUM_COVERAGE'] = $premiumCoverage;
$dataSave['FORTE_EP_DAYS_COVERAGE_ACCRUE'] = $daysCoverageAccrue;
$dataSave['FORTE_EP_EARNED_PREMIUM'] = $earnedPremium;
$dataSave['FORTE_EP_ANNUAL_RISK_PREMIUM'] = $riskAnnualPremium;
$dataSave['FORTE_EP_ACCUMULATED_RISK_PREMIUM'] = $accruedPremium;
$dataSave['FORTE_EP_EARNED_PREMIUM_NEW'] = '';
$dataSave['FORTE_EP_VALUE_PREMIUM_ENDORSEMENT'] = round($premiumEndorsement, 2);
$dataSave['FORTE_EP_NEW_ANNUAL_PREMIUM'] = $dataReturn['END_EXTENSION_YQP_TOTAL_PREMIUM'];
saveUpdateCollection(getenv('FORTE_ENDORSEMENT_PREMIUM_ID'), $requestId, json_encode($dataSave), 'FORTE_EP_REQUEST');

//Set User Process Add HC
$urlUserId = getenv('API_HOST') . '/users/' . $data['YQP_USER_ID'];
$responseUserId = callGetCurl($urlUserId, "GET", "");
$dataReturn['YQP_USER_FULLNAME'] = $responseUserId['firstname'] . ' ' . $responseUserId['lastname'];
//--------------------------------BKP---------------------------------------
//Set variables
//$periodFrom = $data['YQP_PERIOD_FROM'];
//$periodTo = $data['YQP_PERIOD_TO'];
//Get correct Total Premium
//$totalPremium = $data["YQP_TOTAL_PREMIUM"];
//if ($data["YQP_GROSS_BROKER_CHANGE"]) {
  //  $totalPremium = $data["YQP_BROKER_TOTAL_PREMIUM"];
//}
//$extensionDate = $data["END_NEW_PERIOD_TO"];

//Get original of the coverage days
//$periodFromDate = new DateTime($periodFrom);
//$periodToDate = new DateTime($periodTo);
//$differencePeriodFromTo = $periodFromDate->diff($periodToDate);
//$originalCoverageDays = $differencePeriodFromTo->days;

//Get number of extendend days
//$periodToDate  = new DateTime($periodTo);
//$extensionDateTime = new DateTime($extensionDate);
//$differenceExtensionPeriodTo = $periodToDate->diff($extensionDateTime);
//$daysToExtend = $differenceExtensionPeriodTo->days;

//Get cost of premium per day
//$dailyCostPremium = $totalPremium / $originalCoverageDays;

//Calculate Extension Premium
//$extensionPremium = $dailyCostPremium * $daysToExtend;

//Set return to show in screen
//$dataReturn['END_TOTAL_PREMIUM_EXTENSION'] = $extensionPremium;

return $dataReturn;