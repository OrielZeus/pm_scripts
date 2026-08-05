<?php 
/*  
 * Get all rules of OpenL Final Rate, Business Final and Other deductible
 * Set some parameters needed
 * by Ana Castillo
 * modified by Helen Callisaya
 */
//Set variable of return
$dataReturn = array();

//Get days difference between effective date and current date
$periodFrom = new DateTime($data['YQP_PERIOD_FROM']);
$periodTo  = new DateTime($data['YQP_PERIOD_TO']);
$dateDifference = $periodFrom->diff($periodTo);
$daysDifference = $dateDifference->days;
$dataReturn['YQP_DAYS_DIFFERENCE'] = $daysDifference;

//Set Exchange rate to use it with all amounts
if ($data["YQP_CURRENCY"] == "USD") {
    $dataReturn["YQP_EXCHANGE_RATE"] = 1;
    $exchange = 1;
} else {
    $exchange = $data["YQP_EXCHANGE_RATE"];
}

//Set Tenders HTML to use it on Slip
$tenders = array();
$tenders = $data["YQP_TENDERS_INFORMATION"];
$tendersSlip = "";
if ($data["YQP_TENDERS"] == "YES") {
    $tendersSlip = "<table style='width:100%;border: 1px solid black; border-collapse: collapse;'>";
    //Html tender for Spanish language
    if ($data["YQP_LANGUAGE"] == "ES") {
        $tendersSlip .= "<tr><td colspan='2' style='font-size: 10pt;font-family: Corbel;text-align: left;font-weight: bolder;'>Embarcaciones</td></tr>";
        //Label Tender for Spanish languaje
        $labelTender = "Embarcación Auxiliar ";
    } else {
        $tendersSlip .= "<tr><td colspan='2' style='font-size: 10pt;font-family: Corbel;text-align: left;font-weight: bolder;'>Tenders</td></tr>";
        //Label Tender for other languaje
        $labelTender = "Tender Information ";
    }
    for ($t = 0; $t < count($tenders); $t++) {
        //Set columns of the HTML
        $tendersSlip .= "<tr><td style='width:40%;font-size: 10pt;font-family: Corbel;'>" . $labelTender . ($t + 1) . "</td>";
        $tendersSlip .= "<td style='width:60%;font-size: 10pt;font-family: Corbel;'>" . $tenders[$t]["YQP_TENDERS_DESCRIPTION"] . "</td></tr>";
    }
    $tendersSlip .= "</table>";
}
$dataReturn["YQP_SLIP_TENDERS"] = htmlentities($tendersSlip);

//Get URL Connection of OpenL
$openLUrl = getenv('OPENL_CONNECTION');

/*
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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
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
        $dataReturn['RATE_COVERAGE_RESPONSE_OPENL'] = "cURL Error #:" . $err;
    } else {
        //Get values of the response
        $response = json_decode($response);
        $dataReturn['RATE_COVERAGE_RESPONSE_OPENL'] = $response;
        if ($response != "" && $response != null) {
            $rateNoLosses = $response;
        }
    }
}

/*********************** Get Final Rate *********************************/
//Set necessary variables
$dataSend = array();
$dataSend['yacht'] = array();
$dataSend['yacht']['Country'] = $data["YQP_COUNTRY_BUSINESS"];
$dataSend['yacht']['TypeYacht'] = $data["YQP_TYPE_YACHT"];
if ($data["YQP_DEDUCTIBLE"] == "null") {
    $dataSend['yacht']['Deductible'] = 0;
} else {
    $dataSend['yacht']['Deductible'] = $data["YQP_DEDUCTIBLE"];
}
$dataSend['yacht']['Age'] = $data["YQP_AGE"];
$dataSend['yacht']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
$dataSend['yacht']['Experience'] = $data["YQP_OWNER_EXPERIENCE"];
$dataSend['yacht']['Activity'] = $data["YQP_USE"];
$dataSend['yacht']['Port'] = $data["YQP_MOORING_PORT"];
$dataSend['yacht']['LossActivity'] = $data["YQP_LOSS_PAYEE"];
$dataSend['yacht']['Location'] = $data["YQP_LOCATION_MOORING_PORT"];

//Set data Send OpenL
$dataReturn["DATA_FINAL_RATE_OPENL_SEND"] = $dataSend;

//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/FinalRateSurcharges";

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Base Rate
$htmlBaseRate = "";

//Set total of Final Rate
$finalRate = 0;

if ($err) {
    //If there is an error, set the error
    $dataReturn['YQP_FINAL_RATE_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $dataReturn['FINAL_RATE_RESPONSE_OPENL'] = $response;
    $response = json_decode($response);
    if ($response != "" && $response != null) {
        $htmlBaseRate .= "<table id='tableBaseRate' width='70%' border='1'>";
        // Sum Discount 
        $discountSum = 0;
        //Set first parameters
        $label = "";
        $desc = "";
        $result = "";
        foreach ($response as $key => $value) {
            //Get Action and value
            //Label_BaseRate
            $action = explode("_", $key)[0];
            $variableOpenL = explode("_", $key)[1];
            switch ($action) {
                case 'Label':
                    $label = $value;
                    break;
                case 'Desc':
                    $desc = $value;
                    break;
                case 'Result':
                    $result = $value;
                    //This is the latest field of the row so we need to draw it
                    if ($variableOpenL == "BaseRate") {
                        //Base rate only has 2 columns
                        $finalRate = $result * 1;
                        $htmlBaseRate .= "<tr style='background:#D9D9D9;'><td width='60%' style='text-align: left; padding: 5px;' colspan='2'><b>Base Rate</b></td>";
                        $htmlBaseRate .= "<td width='40%' style='text-align:right; padding: 5px;'><b>" . preg_replace('/\.(\d{4}).*/', '.$1', $result) . "</b></td></tr>";
                    } else {
                        $discount = $result * 1;
                        $discountSum = $discountSum + $discount;
                        $htmlBaseRate .= "<tr><td width='30%' style='text-align:left; padding: 5px;'>" . $label . "</td>";
                        $htmlBaseRate .= "<td width='30%' style='text-align:left; padding: 5px;'>" . $desc . "</td>";
                        $htmlBaseRate .= "<td width='40%' style='text-align:right; padding: 5px;'>" . preg_replace('/\.(\d{4}).*/', '.$1', $result) . "</td></tr>";
                    }
                    break;
            }
        }

        //Add other Percentage if exist
        if ($data["YQP_OTHER_FACTORS_BASIC_RATE"] != "" && $data["YQP_OTHER_FACTORS_BASIC_RATE"] != null) {
            $discount = $data["YQP_OTHER_FACTORS_BASIC_RATE"] * 1;
            $discount = $discount / 100;
            $discountSum = $discountSum + $discount;
            $htmlBaseRate .= "<tr><td width='30%' style='text-align: left; padding: 5px;'>Surcharge/Discount</td>";
            $htmlBaseRate .= "<td width='30%' style='text-align: left; padding: 5px;'>Other</td>";
            $htmlBaseRate .= "<td width='40%' style='text-align: right; padding: 5px;'>" . preg_replace('/\.(\d{4}).*/', '.$1', $discount) . "</td></tr>";
        }

        //Calculate Final Rate
        $finalRate = $finalRate * (1 + ($discountSum));

        //Add final Rate to the table
        $htmlBaseRate .= "<tr style='background:#D9D9D9;'><td width='60%' style='text-align: left; padding: 5px;' colspan='2'><b>Final Rate 100%</b></td>";
        $htmlBaseRate .= "<td width='40%' style='text-align: right; padding: 5px;'><b>" . preg_replace('/\.(\d{4}).*/', '.$1', $finalRate) . "</b></td></tr>";

        //Set Excluded No Losses
        if ($rateNoLosses != 0) {
            $coverageNoLosses = $finalRate * $rateNoLosses;
            $htmlBaseRate .= "<tr style='background:#D9D9D9;'><td width='60%' style='text-align: left; padding: 5px;' colspan='2'><b></b></td>";
            $htmlBaseRate .= "<td width='40%' style='text-align: right; padding: 5px;'><b>" . preg_replace('/\.(\d{4}).*/', '.$1', $coverageNoLosses) . "</b></td></tr>";
        }

        //Close Table
        $htmlBaseRate .= "</table>";

        //Set Html to show it on the screen
        $dataReturn['YQP_FINAL_RATE_RESPONSE'] = $htmlBaseRate;

        //Set Html of Slip Base Rate
        $dataReturn['YQP_DOCUMENT_BASE_RATE_HTML'] = htmlentities($htmlBaseRate);

        //Set Final Rate to use on the Approvers rules
        $dataReturn["YQP_FINAL_RATE_VALUE"] = $finalRate;
    }
}


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
    $dataReturn["DATA_WAR_SUM_INSURED"] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
} else {
    $dataSend['final1']['TypeCoverage'] = "";
    $dataSend['final1']['WarSumInsured'] = 0;
    $dataReturn["DATA_WAR_SUM_INSURED"] = $data["YQP_SUM_INSURED_VESSEL"];
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
$dataReturn["DATA_OTHER_COVERAGES_OPENL_SEND"] = $dataSend;
$dataReturn["DATA_WAR_SUM_INSURED_TENDER"] = $sumInsuredWar;

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
    $dataReturn['YQP_OTHER_COVERAGES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataReturn['OTHER_COVERAGES_RESPONSE_OPENL'] = $response;
    if ($response != "" && $response != null) {
        //Set table with depends on Broker exist
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            $htmlTotalPrime .= "<table id='tableCoverages' width='90%' border='1'>";
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Annual Premium</b></td><td style='padding: 5px;'><b>Gross Annual Premium</b></td></tr>";
        } else {
            $htmlTotalPrime .= "<table id='tableCoverages' width='70%' border='1'>";
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
        $dataReturn['YQP_OTHER_COVERAGES_RESPONSE_CONVERTED'] = $responseConverted;

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
        $dataReturn['YQP_TOTAL_PREMIUM'] = $totalPrimeAnnual;

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
        $dataReturn['YQP_OTHER_COVERAGES_RESPONSE'] = $htmlTotalPrime;

        //Set Html of Slip Final Premium
        $dataReturn['YQP_DOCUMENT_FINAL_PREMIUM_HTML'] = htmlentities($htmlTotalPrime);
    }
}

/*********************** Get Other Deductibles *********************************/
//Set necessary variables
$dataSend = array();
$dataSend['OtherDeductible'] = array();
$dataSend['OtherDeductible']['Deductible'] = $data["YQP_DEDUCTIBLE"];
$dataSend['OtherDeductible']['Country'] = $data["YQP_COUNTRY_BUSINESS"];
$dataSend['OtherDeductible']['LengthYacht'] = $data["YQP_LENGTH_UNIT"];
$dataSend['OtherDeductible']['VesselAge'] = $data["YQP_AGE"];
$dataSend['OtherDeductible']['Language'] = $data["YQP_LANGUAGE"];
$dataSend['OtherDeductible']['TypeYacht'] = $data["YQP_TYPE_YACHT"];
$dataSend['OtherDeductible']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
if ($data["YQP_WAR"] == "YES") {
    $dataSend['OtherDeductible']['War'] = $data["YQP_WAR_DEDUCTIBLE"];
    $dataSend['OtherDeductible']['WarSumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange) + $sumInsuredWar;
} else {
    $dataSend['OtherDeductible']['War'] = "NO";
    $dataSend['OtherDeductible']['WarSumInsured'] = 0;
}
if ($data["YQP_PERSONAL_EFFECTS"] == "YES") {
    $dataSend['OtherDeductible']['PersonalEffectsSumInsured'] = ($data["YQP_PERSONAL_EFFECTS_LIMIT"] * $exchange);
} else {
    $dataSend['OtherDeductible']['PersonalEffectsSumInsured'] = 0;
}
if ($data["YQP_MEDICAL_PAYMENTS"] == "YES") {
    $dataSend['OtherDeductible']['MedicalPaymentsSumInsured'] = ($data["YQP_MEDICAL_PAYMENTS_LIMIT"] * $exchange);
} else {
    $dataSend['OtherDeductible']['MedicalPaymentsSumInsured'] = 0;
}
$dataSend['OtherDeductible']['LimitPI'] = ($data["YQP_LIMIT_PI"] * $exchange);
if ($data["YQP_TENDERS"] == "YES") {
    $dataSend['OtherDeductible']['TendersMaxSumInsured'] = $maxSumInsuredTender;
} else {
    $dataSend['OtherDeductible']['TendersMaxSumInsured'] = 0;
}
//Validate if The Product is with HULL on the options
if (explode($data["YQP_PRODUCT"], "HULL") > 1) {
    //Validate if the Special Area is YES
    if ($data["YQP_SPECIAL_AREA"] == "YES") {
        $dataSend['OtherDeductible']['SpecialDeductibleCode'] = $data["YQP_TYPE_SPECIAL_DEDUCTIBLE"];
        $dataSend['OtherDeductible']['ShowDeductibleCode'] = $data["YQP_SHOW_SPECIAL_DEDUCTIBLE"];
        $dataSend['OtherDeductible']['SpecialArea'] = $data["YQP_SPECIAL_AREA_ZONE"];
        if ($data["YQP_SHOW_SPECIAL_DEDUCTIBLE"] == "PER") {
            $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = $data["YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE"];
        } else {
            $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = "";
        }
    } else {
        $dataSend['OtherDeductible']['SpecialDeductibleCode'] = "";
        $dataSend['OtherDeductible']['ShowDeductibleCode'] = "";
        $dataSend['OtherDeductible']['SpecialArea'] = "";
        $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = "";
    }
} else {
    $dataSend['OtherDeductible']['SpecialDeductibleCode'] = "";
    $dataSend['OtherDeductible']['ShowDeductibleCode'] = "";
    $dataSend['OtherDeductible']['SpecialArea'] = "";
    $dataSend['OtherDeductible']['ShowDeductiblePercentage'] = "";
}
$dataSend['OtherDeductible']['LimitExcessPI'] = $differenceLimitPI;
$dataSend['OtherDeductible']['Contamination'] = $data['YQP_CONTAMINATION'];
$dataSend['OtherDeductible']['DamageEnvironment'] = $data['YQP_DAMAGE'];
$dataSend['OtherDeductible']['OwnersUninsured'] = $data['YQP_OWNERS_UNINSURED_VESSEL'];
if (count(explode("NOLOSSES", $data["YQP_PRODUCT"])) > 1) {
    $dataSend['OtherDeductible']['ProductExcluding'] = "YES";
} else {
    $dataSend['OtherDeductible']['ProductExcluding'] = "NO";
}
$dataSend['OtherDeductible']['WaterSkiing'] = $data['YQP_WATER_SKIING'];
$dataSend['OtherDeductible']['TextMachinery'] = $data['YQP_MACHINERY'];
$dataSend['OtherDeductible']['ShowDeductible'] = $data['YQP_SHOW_DEDUCTIBLE'];
$dataSend['OtherDeductible']['PIRCValidation'] = $data['YQP_PIRC_VALIDATION'];
$dataSend['OtherDeductible']['PIDeductible'] = $data['YQP_LIMIT_PI_DEDUCTIBLE'] * 1;

//Set data Send to OpenL
$dataReturn["DATA_OTHER_DEDUCTIBLES_OPENL_SEND"] = $dataSend;
//Set OpenL Url
$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/OtherDeductibles";
//Set another URL only for Multinational Reinsurer
if ($data["YQP_COUNTRY_BUSINESS"] == "Puerto Rico" && $data["YQP_REASSURED_CEDENT"]["LABEL"] == "Multinational Insurance Company (Puerto Rico)-MIC") {
    $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/OtherDeductiblesMultinational";
}

//Call Curl OpenL
$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
//Set response
$response = $aResponse["DATA"];
//Set error
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Total Prime
$htmlOtherDeductibles = "";

if ($err) {
    //If there is an error, set the error
    $dataReturn['YQP_OTHER_DEDUCTIBLES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataReturn['OTHER_DEDUCTIBLES_RESPONSE_OPENL'] = $response;
    if ($response != "" && $response != null) {
        $htmlOtherDeductibles .= "<table id='tableDeductibles' width='70%' border='1'>";
        $htmlOtherDeductibles .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverage</b></td><td style='padding: 5px;'><b>Deductible</b></td></tr>";
        $i = 1;
        foreach ($response as $key => $value) {
            //Validate Keys to not show
            if ($key != "HullValue") {
                //Slip text in 2 columns if it is necessary
                if (count(explode("SPLIT", $key)) > 1) {
                    $key = explode("_SPLIT_", $value)[0];
                    $value = explode("_SPLIT_", $value)[1];                 
                }

                //Replace & and espace on Key if it is necessary
                $key = str_replace("_AND_", "&", $key);
                $key = str_replace("_", " ", $key);
                
                //Validate if value has not an answer
                $valueAux = explode("null", $value);
                if (count($valueAux) > 1) {
                    $value = "";
                }
                //Validate if Key is not empty
                if ($key != "null") {
                    $htmlOtherDeductibles .= "<tr><td width='50%' style='text-align: left; padding: 5px;'>" . $key . "</td>";
                    //Validate if value is Numeric
                    //Separate the string into an array
                    $validateIsNumeric = explode(" ", $value);                    
                    for ($vn = 0; $vn < count($validateIsNumeric); $vn++) {
                        //Search % in string
                        $pos = strpos($validateIsNumeric[$vn], "%");
                        //Not Exists %
                        if ($pos === false) {
                            //Validate value is numeric
                            if (is_numeric($validateIsNumeric[$vn])) {
                                $validateIsNumeric[$vn] = number_format($validateIsNumeric[$vn], 2, '.', ',');
                            }
                        } else {
                            //Separate the string into an array
                            $numberPercentage = explode("%", $validateIsNumeric[$vn]);
                            for ($vp = 0; $vp < count($numberPercentage); $vp++) {
                                //Validate value is numeric
                                if (is_numeric($numberPercentage[$vp])) {
                                    $numberPercentage[$vp] = number_format($numberPercentage[$vp], 2, '.', ',');                            
                                }
                            }
                            //Convert array to string
                            $validateIsNumeric[$vn] = implode("%", $numberPercentage);
                        }                 
                    }
                    //Convert array to string
                    $value = implode(" ", $validateIsNumeric); 

                    $htmlOtherDeductibles .= "<td width='50%' style='text-align:right; padding: 5px;'>" . $value . "</td></tr>";
                }
                $i++;
            }
        }
        //Close Table
        $htmlOtherDeductibles .= "</table>";

        $dataReturn['YQP_OTHER_DEDUCTIBLES_RESPONSE'] = $htmlOtherDeductibles;
        $dataReturn['YQP_OTHER_DEDUCTIBLES_RESPONSE_HTML'] = htmlentities($htmlOtherDeductibles);
    }
}
//Format period dates
$dataReturn['YQP_PERIOD_FROM_REPORT'] = date('m/d/Y', strtotime($data['YQP_PERIOD_FROM']));
$dataReturn['YQP_PERIOD_TO_REPORT'] = date('m/d/Y', strtotime($data['YQP_PERIOD_TO']));
$dataReturn['YQP_TENDER_TOWED_DESCRIPTION'] = $descriptionTenderTowed;
$dataReturn['YQP_TENDER_TOWED'] = $flagTenderTowed;
return $dataReturn;