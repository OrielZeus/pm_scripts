<?php 
/*  
 * Recalculate Rules
 * by Helen Callisaya
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

//Set variable of return
$dataReturn = array();

//Get days difference between effective date and current date
$periodFrom = new DateTime($data['YQP_PERIOD_FROM']);
$periodTo  = new DateTime($data['YQP_PERIOD_TO']);
$dateDifference = $periodFrom->diff($periodTo);
$daysDifference = $dateDifference->days;
$dataReturn['YQP_DAYS_DIFFERENCE'] = $daysDifference;

/************************** Set OpenL Parameters ************************************/
//Get URL Connection
$openLUrl = getenv('OPENL_CONNECTION');
//Set Exchange rate to use it with all amounts
if ($data["YQP_CURRENCY"] == "USD") {
    $dataReturn["YQP_EXCHANGE_RATE"] = 1;
    $exchange = 1;
} else {
    $exchange = $data["YQP_EXCHANGE_RATE"];
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

/*********************** Get Other Coverages *********************************/
$dataSend = array();
$dataSend['final1'] = array();
$dataSend['final1']['SumInsured'] = ($data["YQP_SUM_INSURED_VESSEL"] * $exchange);
$dataSend['final1']['FinalRate'] = $data["YQP_FINAL_RATE_VALUE"];
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
    $dataSend['final1']['MedicalPaymentsSumInsured'] = ($data["YQP_MEDICAL_PAYMENTS_LIMIT"] * $exchange);
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
} else {
    $dataSend['final1']['TypeCoverage'] = "";
    $dataSend['final1']['WarSumInsured'] = 0;
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

//Set total of Prime for Anual Premium and Gross Annual Premium
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
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Anual Premium</b></td><td style='padding: 5px;'><b>Gross Annual Premium</b></td></tr>";
        } else {
            $htmlTotalPrime .= "<table id='tableCoverages' width='70%' border='1'>";
            $htmlTotalPrime .= "<tr style='background:#D9D9D9;'><td style='padding: 5px;'><b>Coverages</b></td><td style='padding: 5px;'><b>Anual Premium</b></td></tr>";
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
        $dataReturn['YQP_TOTAL_PREMIUM_SLIP'] = $totalPrimeAnnual;

        //Change format number if the total prime is a number for Annual
        if (is_numeric($totalPrimeAnnual)) {
            $totalPrimeAnnual = number_format($totalPrimeAnnual, 2, ".", ",");
        }

        //Add final Rate to the table
        //Set total Prime if Broker exist or not
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            //Add total Premium value as with Broker
            $dataReturn['YQP_TOTAL_PREMIUM_SLIP'] = $totalPrimeGross;
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

/*********************** Get Slip Rates *********************************/
$dataReturn["DATA_SLIP_RULES_RATE_SEND"] = $dataSend;

$openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/SlipRulesRates" . $data["YQP_LANGUAGE"];

$aResponse = callCurlOpenL($openLUrlCurl, $dataSend);
$response = $aResponse["DATA"];
$err = $aResponse["ERROR"];

//Set variable with options
$aOptions = array();

//Set table of Slip Rates
$htmlSlipRates = "";

if ($err) {
    //If there is an error, set the error
    $dataReturn['YQP_SLIP_RATES_RESPONSE'] = "cURL Error #:" . $err;
} else {
    //Get values of the response
    $response = json_decode($response);
    $dataReturn['SLIP_RATES_RESPONSE_OPENL'] = $response;
    $slipRates = array();
    //Set table width
    $tableStyle = 0;
    if ($response != "" && $response != null) {
        //Set max column
        $maxColumn = 0;
        foreach ($response as $key => $value) {
            //Get Row and Column of the table
            $column = explode("_", $key)[0];
            $column = explode("Column", $column)[1];
            $column = $column * 1;
            if ($column > $maxColumn) {
                $maxColumn = $column;
            }
            $row = explode("_", $key)[1];

            //Save table with
            if ($row == "TableStyle") {
                $tableStyle = $value;
            } else {
                //Add the row if exist
                if (!isset($slipRates[$row])) {
                    $slipRates[$row] = array();
                }

                //Validate if it is the second Column to Do format on fields as currency
                if ($column == 2) {
                    //Analyze value if there is only amounts with breaks
                    $flagAmount = 0;
                    if (is_numeric($value)) {
                        $flagAmount = 1;
                        $value = number_format($value, 2, ".", ",");
                    } else {
                        //Validate that the value is string or number
                        if (!is_object($value)) {
                            //Split string to stract <br> and <b>
                            $amounts = explode("<br/>", $value);
                            if (count($amounts) > 1) {
                                //Set flag to concatenate the amount and <br/>
                                $brAmounts = "";
                                for ($a = 0; $a < count($amounts); $a++) {
                                    //Validate <b> tag
                                    $bAmount = explode("<b>", $amounts[$a]);
                                    $flagBTag = false;
                                    if (count($bAmount) > 1) {
                                        //Delete <b> tag and set flag to add it
                                        $bAmount = explode("</b>", $bAmount[1])[0];
                                        $amounts[$a] = $bAmount;
                                        $flagBTag = true;
                                    }
                                    //Validate if field is amount
                                    if (is_numeric($amounts[$a])) {
                                        //Validate if field has <b> tag
                                        if ($flagBTag) {
                                            $brAmounts .= "<b>" . number_format($amounts[$a], 2, ".", ",") . "</b>";
                                        } else {
                                            $brAmounts .= number_format($amounts[$a], 2, ".", ",");
                                        }
                                    } else {
                                        $brAmounts .= $amounts[$a];
                                    }
                                    //Add <br/> if it is not the last field
                                    if ($a + 1 < count($amounts)) {
                                        $brAmounts .= "<br/>";
                                    }
                                }
                                //Set value with format amounts
                                $value = $brAmounts;
                            }
                        }
                    }

                    //Set column on the array
                    $slipRates[$row][$column] = $value;
                } else {
                    //Set column on the array
                    $slipRates[$row][$column] = $value;
                }
            }
        }
        //Set OpenL array Response
        $dataReturn['YQP_SLIP_RATES_RESPONSE'] = $slipRates;

        //Set HTML for Slip Rates
        $stylesRow = array();
        if ($maxColumn > 0) {
            //Start drawing table HTML
            $htmlSlipRates = "<table style='" . $tableStyle . "'>";
            foreach ($slipRates as $key => $value) {
                $flagDrawTable = 0;
                for ($c = 1; $c <= $maxColumn; $c++) {
                    if ($key == "Style") {
                        $stylesRow[$c] = $value[$c];
                    } else {
                        //Validate that the Key is Row if not don't draw it
                        $validKey = explode("Row", $key);
                        if (count($validKey) > 1) {
                            
                            if ($flagDrawTable == 0) {
                                $flagDrawTable = 1;
                                $htmlSlipRates .= "<tr>";
                            }
                            //Set value if it is None
                            $newValue = "";
                            if ($value[$c] != "None") {
                                //Change format number if the value is a number
                                //Separate the string into an array
                                $validateIsNumeric = explode(" ", $value[$c]);                    
                                for ($x = 0; $x < count($validateIsNumeric); $x++) {
                                    //Validate value is numeric
                                    if (is_numeric($validateIsNumeric[$x])) {
                                        $validateIsNumeric[$x] = number_format($validateIsNumeric[$x], 2, '.', ',');                            
                                    }                  
                                }
                                //Convert array to string
                                $newValue = implode(" ", $validateIsNumeric);
                            }
                            //Valdiate if the Value needs replace of data
                            if (count(explode("REQUEST_DATA_", $newValue)) > 1) {
                                $replaceDataUid = explode("REQUEST_DATA", $newValue)[1];
                                //Set flag to bold field
                                $bold = 0;
                                if (count(explode("BOLD_", $replaceDataUid)) > 1) {
                                    $bold = 0;
                                    //Delete Bold of UID Request data
                                    $replaceDataUid = explode("BOLD_", $replaceDataUid)[1];
                                }

                                //Verify if the value is on previous data of this script or data of the request
                                if (isset($dataReturn[$replaceDataUid])) {
                                    $newValue = number_format($dataReturn[$replaceDataUid], 2, ".", ",");
                                } else {
                                    $newValue = number_format($data[$replaceDataUid], 2, ".", ",");
                                }
                            }

                            //Cut numbers to 2 decimals
                            //$newValue = preg_replace('/\.(\d{2}).*/', '.$1', $newValue);

                            //Set new td
                            $htmlSlipRates .= "<td style='" . $stylesRow[$c] . "'>" . $newValue . "</td>";
                        }
                    }
                }
                if ($flagDrawTable == 1) {
                    $htmlSlipRates .= "</tr>";
                }
            }
            $htmlSlipRates .= "</table>";
        }
        //Set HTMl of Slip Rates
        $dataReturn['YQP_SLIP_RATES_HTML'] = htmlentities($htmlSlipRates);
    }
}
//-------------Close Requests In Progress-----------
$processId = $data['_request']['process_id'];
$requestId = $data['_request']['id'];
$clientName = $data['YQP_CLIENT_NAME'];
$vesselName = $data['YQP_INTEREST_ASSURED'];

//Get Id collection Additional Contacts
$collectionNames = ["FORTE_OPERATION_TYPE_TASK", "FORTE_ID_YACHT", "FORTE_GESTION_SOLICITUDES"];
$collectionsInfo = getCollectionIdMaster($collectionNames, $apiUrl);
$collectionTask = $collectionsInfo["FORTE_OPERATION_TYPE_TASK"];
$forteIdyacht = $collectionsInfo["FORTE_ID_YACHT"];
$forteGestionSolicitudesId = $collectionsInfo["FORTE_GESTION_SOLICITUDES"];

//Get Task Available
$listTask = [];
$sqlTaskOpType = "SELECT T.data->>'$.FORTE_OTT_TASK' as FORTE_OTT_TASK 
                  FROM collection_" . $collectionTask . " AS T 
                  WHERE T.data->>'$.FORTE_OTT_PROCESS' = '" . $processId . "' 
                    AND T.data->>'$.FORTE_OTT_OPERATION_TYPE' = 'CLONE' 
                    AND T.data->>'$.FORTE_OTT_STATUS' = 'ACTIVE'";
$resTaskOpType = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlTaskOpType));

foreach ($resTaskOpType as $item) {
    $listTask[] = '"' . $item['FORTE_OTT_TASK'] . '"';
}
$taskIn = implode(",", $listTask);

//Requests Related
$sqlSearchRequest = "SELECT PR.id AS RES_REQUEST_ID, 
                            PR.data->>'$.YQP_CLIENT_NAME' AS RES_YQP_CLIENT_NAME,
                            PR.data->>'$.YQP_INTEREST_ASSURED' AS RES_YQP_INTEREST_ASSURED,
                            PRT.element_name AS RESP_YQP_TASK_NAME
                     FROM process_requests as PR
                     INNER JOIN  process_request_tokens as PRT
                         ON PRT.process_request_id = PR.id
                     WHERE PR.status = 'ACTIVE'
                         AND PR.process_id = " . $processId . "
                         AND PR.id != " . $requestId . "                         
                         AND PRT.status = 'ACTIVE'
                         AND PR.data->>'$.YQP_CLIENT_NAME' = '" . $clientName ."'
                         AND PR.data->>'$.YQP_INTEREST_ASSURED' = '" . $vesselName . "'";
$resSearchRequest = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlSearchRequest)) ?? [];
$listRequests = implode(",", array_column($resSearchRequest, 'RES_REQUEST_ID'));
if (!empty($listRequests)) {
    //Update Request to ERROR
    $sqlRequestsUpdate = "UPDATE process_requests 
                          SET status = 'ERROR'
                          WHERE id IN (" . $listRequests . ")
                              AND status = 'ACTIVE'";
    $resRequestsUpdate = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlRequestsUpdate)) ?? [];
    $maxRetries   = 10;
    $retryCount   = 0;
    $sleepSeconds = 1;
    do {
        
        $sqlCheck = "
            SELECT id, status
              FROM process_requests
             WHERE id IN ({$listRequests})
        ";
        $resStatus = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlCheck)) ?: [];
        
        $notYetError = array_filter($resStatus, function($row) {
            return strtoupper($row['status']) !== 'ERROR';
        });
        if (empty($notYetError)) {
            break;
        }
        sleep($sleepSeconds);
        $retryCount++;
    } while ($retryCount < $maxRetries);
    //Set Requests COMPLETED
    foreach($resSearchRequest as $row) {
        //Close request
        $resRequestID = $row['RES_REQUEST_ID'];
        $include = 'data';
        $apiInstance = $api->processRequests();
        $requesInfo = $apiInstance->getProcessRequestById($resRequestID, $include);
        $requesInfoData = $requesInfo->getData();
        $requesInfoData['YQP_STATUS'] = "QUOTED, NOT TAKEN";
        $requesInfoData['YQP_SITUATION'] = "CLOSED";
        $requesInfoUpdate = new \ProcessMaker\Client\Model\ProcessRequestEditable();        
        $requesInfoUpdate->setData($requesInfoData);              
        $apiInstance->updateProcessRequest($resRequestID, $requesInfoUpdate);

        $requesInfoUpdate->setStatus("COMPLETED");
        $apiInstance->updateProcessRequest($resRequestID, $requesInfoUpdate);
        //Save to Gestion Solicitudes Collection
        $sqlGestionSolicitudes = "SELECT id AS ID_COLLECTION,
                                         data as COLLECTION_DATA
                                  FROM collection_" . $forteGestionSolicitudesId . " AS C
                                  WHERE C.data->>'$.FORTE_REQUEST' = '" . $resRequestID . "'";
        $resGestionSolicitudes = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlGestionSolicitudes));
        $dataSave = json_decode($resGestionSolicitudes[0]['COLLECTION_DATA'], true);
        $dataSave['YQP_STATUS'] = "QUOTED, NOT TAKEN";
        $dataSave['YQP_SITUATION'] = "CLOSED";

        //Validate if the request exists
        if (count($resGestionSolicitudes) == 0) {
            $insertRequestUrl = $apiHost . '/collections/' . $forteGestionSolicitudesId . '/records';
            $insertRequest = callApiUrlGuzzle($insertRequestUrl, "POST", $dataSave);
        } else {
            $idCollectionGestion = $resGestionSolicitudes[0]['ID_COLLECTION'];
            $updateRequestUrl = $apiHost . '/collections/' . $forteGestionSolicitudesId . '/records/' . $idCollectionGestion;
            $updateRequest = callApiUrlGuzzle($updateRequestUrl, "PUT", $dataSave);
        }
    }
}
//----------------------------------------------------

return $dataReturn;
//****************FUNCTIONS*******************************
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
 * Call Processmaker API with Guzzle
 *
 * @param string $url
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res
 *
 * by Elmer Orihuela 
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        //$res = json_decode($res, true);
         if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

/**
 * Get IDs of collections with the Master collection
 *
 * @param (Array) $collectionNames
 * @param (String) $apiUrl
 * @return (Array) $aCollections
 *
 * by Ana Castillo
 */
function getCollectionIdMaster($collectionNames, $apiUrl)
{
    //Set Master Collection ID
    $masterCollectionID = getenv('FORTE_MASTER_COLLECTION_ID');

    //Add semicolon with all fields of the array
    $collectionName = array_map(function($item) {
        return '"' . $item . '"';
    }, $collectionNames);

    //Merge all values of the array with commas
    $collections = implode(", ", $collectionName);

    //Get Collections IDs
    $sQCollectionsId = "SELECT data->>'$.COLLECTION_ID' AS ID,
                               data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN (" . $collections . ")";
    $collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    //Set array to get the name with the ID
    $aCollections = array();
    if (count($collectionsInfo) > 0) {
        foreach ($collectionsInfo as $item) {
            $aCollections[$item['COLLECTION_NAME']] = $item['ID'];
        }
    }

    return $aCollections;
}