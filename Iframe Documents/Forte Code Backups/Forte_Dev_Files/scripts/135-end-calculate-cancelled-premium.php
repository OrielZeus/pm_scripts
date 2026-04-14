<?php 
/****************************  
* Calculate Cancelled Premium
* 
* by Cinthia Romero
* modified by Helen Callisaya
****************************/

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

/**
 * Calculate Days Between Two Dates
 *
 * @param string $firstDate
 * @param string $secondDate
 * @return string $days
 *
 * by Cinthia Romero
 */
function calculateDaysBetweenTwoDates($firstDate, $secondDate)
{
    $firstDate = strtotime($firstDate);
    $secondDate = strtotime($secondDate);
    //Difference between dates
    $dateDiff = $firstDate - $secondDate;
    //Converting the difference in days
    $days = round($dateDiff / (60 * 60 * 24));
    return $days;
} 

/**
 * Pro rata calculation
 *
 * @param array $caseData
 * @return array $cancellationDetails
 *
 * by Cinthia Romero
 * modified by Helen Callisaya
 */
function prorataCalculation($caseData)
{
    $dataHistory = $caseData['END_CALCULATE_HISTORY'];
    $periodFrom = $caseData['END_CALCULATE_HISTORY']['FORTE_END_PERIOD_FROM'];
    $periodTo = $caseData['END_CALCULATE_HISTORY']['FORTE_END_PERIOD_TO'];
    $periodToOriginal = $caseData['END_CALCULATE_HISTORY']['FORTE_END_PERIOD_TO_ORIGINAL'];
    $totalDaysOriginal = calculateDaysBetweenTwoDates($periodToOriginal, $periodFrom);//366
    $daysEarned =  calculateDaysBetweenTwoDates($periodToOriginal, $caseData["END_VALIDITY_ENDORSEMENT"]) * (-1);//Dias devengar 301
    $daysCoverage = calculateDaysBetweenTwoDates($caseData["END_VALIDITY_ENDORSEMENT"], $periodFrom); //Dias Transcurridos 65

    //Get data from previous endorsements
    $urlHistoryPremium = getenv('API_HOST') . '/collections/' . getenv('FORTE_ENDORSEMENT_PREMIUM_ID') . '/records?order_by=updated_at&order_direction=desc&pmql=';
    $searchHistoryPremium = $urlHistoryPremium . urlencode('(data.FORTE_EP_REQUEST_PARENT = "' . $caseData['END_ID_SELECTION'] . '" AND data.FORTE_EP_REQUEST != "' . $caseData['_request']['id'] . '")');
    $responseSearchHistoryPremium = callGetCurl($searchHistoryPremium, "GET", "");
    $dataHistoryCollection = $responseSearchHistoryPremium['data'];
    $sumEndorsement = 0;
    $sumEndorsement = $caseData['END_CALCULATE_HISTORY']['FORTE_END_CUMULUTIVE'];
    if (count($dataHistoryCollection) > 0) {
        $premiumOriginal = $dataHistoryCollection[count($dataHistoryCollection) - 1]['data']['FORTE_EP_ORIGINAL_PREMIUM'];
    } else {
        $premiumOriginal = $caseData['END_CALCULATE_HISTORY']['FORTE_END_ORIGINAL_PREMIUM'];
    }
    //Get Premium for canceling - Prima por cancelar (E83)
    $premiumCanceling = ($premiumOriginal / $totalDaysOriginal) * $daysEarned;
    //Get Premium for days elapsed - Prima a Cobrar al cliente (B92)
    $premiumElapsed = ($premiumOriginal / $totalDaysOriginal) * $daysCoverage;
    //Premium Invoiced to the client - Prima Facturada al cliente (B91)
    $sumEndorsement = $sumEndorsement + $premiumOriginal;
    $amountLastEndorsement = $sumEndorsement - $premiumElapsed;
    //Actual days elapsed
    $actualDaysElapsed = $daysCoverage; // Dias reales transcurridos
    //Days to Cancel
    $daysToCancel = calculateDaysBetweenTwoDates($periodTo, $caseData["END_VALIDITY_ENDORSEMENT"]); //Dias a Cancelar 
    //Total Days
    $totalDays = calculateDaysBetweenTwoDates($periodTo, $periodFrom); //Dias totales
    //Get Factor
    $factor = ($amountLastEndorsement * $totalDays) / $daysToCancel;
    //Get Premium Earned
    $premiumEarned = ($factor / $totalDays) * ($daysToCancel * (-1));

    $cancellationDetails = array(
        "END_CANCELLED_DAYS" => $daysToCancel,
        "END_PREMIUM_TO_CANCEL" => round($premiumEarned, 2),
        "END_FACTOR" => round($factor, 2),
        "END_OPEN_L_ERROR" => "NO"
    );
    return $cancellationDetails;
}

/**
 * Short Period Calculation
 *
 * @param array $data
 * @return array $cancellationDetails
 *
 * by Cinthia Romero
 * modified by Helen Callisaya
 */
function shortPeriodCalculation($caseData)
{
    //Set message error if it is needed
    $requestError = [
        'FORTE_ERROR_LOG' => '',
        'FORTE_ERROR_DATE' => '',
        'FORTE_ERROR_BODY' => '',
        'FORTE_ERROR_REQUEST_ID' => $data['_request']['id'],
        'FORTE_ERROR_ELEMENT_ID' => '',
        'FORTE_ERROR_ELEMENT_NAME' => '',
        'FORTE_ERROR_PROCESS_ID' => $data['_request']['process_id'],
        'FORTE_ERROR_PROCESS_NAME' => $data['_request']['name']
    ];

    $dataHistory = $caseData['END_CALCULATE_HISTORY'];
    $periodFrom = $caseData['END_CALCULATE_HISTORY']['FORTE_END_PERIOD_FROM'];
    $periodTo = $caseData['END_CALCULATE_HISTORY']['FORTE_END_PERIOD_TO'];
    $periodToOriginal = $caseData['END_CALCULATE_HISTORY']['FORTE_END_PERIOD_TO_ORIGINAL'];
    
    $totalDaysOriginal = calculateDaysBetweenTwoDates($periodToOriginal, $periodFrom);//366
    $daysEarned =  calculateDaysBetweenTwoDates($periodToOriginal, $caseData["END_VALIDITY_ENDORSEMENT"]) * (-1);//Dias devengar
    $daysCoverage = calculateDaysBetweenTwoDates($caseData["END_VALIDITY_ENDORSEMENT"], $periodFrom); //Dias Transcurridos
    //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //Get data from previous endorsements
    $urlHistoryPremium = getenv('API_HOST') . '/collections/' . getenv('FORTE_ENDORSEMENT_PREMIUM_ID') . '/records?order_by=updated_at&order_direction=desc&pmql=';
    $searchHistoryPremium = $urlHistoryPremium . urlencode('(data.FORTE_EP_REQUEST_PARENT = "' . $caseData['END_ID_SELECTION'] . '" AND data.FORTE_EP_REQUEST != "' . $caseData['_request']['id'] . '")');
    $responseSearchHistoryPremium = callGetCurl($searchHistoryPremium, "GET", "");
    $dataHistoryCollection = $responseSearchHistoryPremium['data'];
    $sumEndorsement = 0;
    $sumEndorsement = $caseData['END_CALCULATE_HISTORY']['FORTE_END_CUMULUTIVE'];
    if (count($dataHistoryCollection) > 0) {
        $premiumOriginal = $dataHistoryCollection[count($dataHistoryCollection) - 1]['data']['FORTE_EP_ORIGINAL_PREMIUM'];
    } else {
        $premiumOriginal = $caseData['END_CALCULATE_HISTORY']['FORTE_END_ORIGINAL_PREMIUM'];
    }
    $businessTotalPremium = $premiumOriginal;

    //Calculate the Days of Original Coverage
    $coverageDays = calculateDaysBetweenTwoDates($caseData["END_VALIDITY_ENDORSEMENT"], $periodFrom);

    //Check if Cancellation Date is superior to Period From
    if ($caseData["END_VALIDITY_ENDORSEMENT"] > $caseData["YQP_PERIOD_FROM"]) {
        //Calculate Earned Days
        $earnedDays = calculateDaysBetweenTwoDates($caseData["END_VALIDITY_ENDORSEMENT"], $caseData["YQP_PERIOD_FROM"]);
    } else {
        $earnedDays = 0;
    }
    //Calculate Earned Months
    $earnedMonths = (int) ($earnedDays / 30);

    $percentageAnnualPremiumCharged = 0;
    $cancelledPremium = 0;
    $cancelledDays = "0";
    $earnedPremium = $businessTotalPremium;
    $openLError = "NO";
    //Check if cancellation date is the same as period to in that case the cancelled period should be 0 because the coverage period was accomplished
    if ($caseData["END_VALIDITY_ENDORSEMENT"] != $caseData["YQP_PERIOD_TO"]) {
        //Check if cancellation date is prior to period from
        if ($caseData["END_VALIDITY_ENDORSEMENT"] > $caseData["YQP_PERIOD_FROM"]) {
            $openLParameters = array(
                "Period" => $earnedMonths
            );

            //Get URL Connection of OpenL
            $openLUrl = getenv('OPENL_CONNECTION');
            //Set OpenL Url
            $openLUrlCurl = $openLUrl . "Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetPercentageShortPeriodEndoso";

            //Call Curl OpenL
            $openLResponse = callCurlOpenL($openLUrlCurl, $openLParameters);
            if ($openLResponse["ERROR"] == ""){
                //Sometimes the error in open-l is not detected as error and it is passed in the variable DATA so in order to avoid this kind of issues let's check if the variable DATA contains at least one space
                if ($openLResponse["DATA"] == trim($openLResponse["DATA"]) && strpos($openLResponse["DATA"], ' ') !== false) {
                    $openLError = "YES";
                    $requestError['FORTE_ERROR_LOG'] = "Open L error while obtaining short period percentage.";
                    $requestError['FORTE_ERROR_BODY'] = $openLResponse["DATA"];
                    $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
                    $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
                    $requestError['FORTE_ERROR_ELEMENT_NAME'] = "Open L error while obtaining short period percentage.";
                } else {
                    $percentageAnnualPremiumCharged = $openLResponse["DATA"];
                    //Calculate Cancelled Premium
                    $earnedPremium = ($businessTotalPremium * $percentageAnnualPremiumCharged) / 100; //E84  Prima transcurrida
                    $cancelledPremium = $businessTotalPremium - $earnedPremium; //E83 y prima por cancelar
                }
            } else {
                $openLError = "YES";
                $requestError['FORTE_ERROR_LOG'] = "Open L error while obtaining short period percentage.";
                $requestError['FORTE_ERROR_BODY'] = $openLResponse["ERROR"];
                $requestError['FORTE_ERROR_DATE'] = date("Y-m-d H:i");
                $requestError['FORTE_ERROR_ELEMENT_ID'] = "1";
                $requestError['FORTE_ERROR_ELEMENT_NAME'] = "Open L error while obtaining short period percentage."; 
            }
        } else {
            $cancelledPremium = $businessTotalPremium; //E83         
        }
        $premiumElapsed = $earnedPremium; //E84
        //Premium Invoiced to the client - Prima Facturada al cliente (B91)        
        $sumEndorsement = $sumEndorsement + $premiumOriginal;
        $amountLastEndorsement = $sumEndorsement - $premiumElapsed;
        //Actual days elapsed
        $actualDaysElapsed = $daysCoverage; // Dias reales transcurridos
        //Days to Cancel
        $daysToCancel = calculateDaysBetweenTwoDates($periodTo, $caseData["END_VALIDITY_ENDORSEMENT"]); //Dias a Cancelar 
        //Total Days
        $totalDays = calculateDaysBetweenTwoDates($periodTo, $periodFrom); //Dias totales
        //Get Factor
        $factor = ($amountLastEndorsement * $totalDays) / $daysToCancel;
        //Get Premium Earned
        $premiumEarned = ($factor / $totalDays) * ($daysToCancel * (-1));
        $cancellationDetails = array(
            "END_CANCELLED_DAYS" => $daysToCancel,
            "END_PREMIUM_TO_CANCEL" => round($premiumEarned, 2),
            "END_FACTOR" => round($factor, 2),
            "END_OPEN_L_ERROR" => "NO",
            "FORTE_ERRORS" => $requestError
        );
        return $cancellationDetails;
    }
     //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
}

//Set variable of return
$cancellationDetails = array();
$cancellationType = $data["END_CANCELLATION_TYPE"];
if ($cancellationType == "PRORATA") {
    $cancellationDetails = prorataCalculation($data);
} else {
    $cancellationDetails = shortPeriodCalculation($data);
}

return $cancellationDetails;