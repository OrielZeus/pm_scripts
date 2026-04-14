<?php 
/*  
 * Update some data request with new values required
 * by Elmer Orihuela
 * modify Helen Callisaya
 */
/* 
 * Call Processmaker API
 *
 * @param (string) $url
 * @param (string) $requestType
 * @param (string) $postfiles
 * @return (Array) $responseCurl
 *
 * by Elmer Orihuela 
 */

function callCurl($url, $requestType, $postfiles)
{
    global $apiToken;
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
            CURLOPT_CUSTOMREQUEST => $requestType,
            CURLOPT_POSTFIELDS => $postfiles,
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . $apiToken,
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
* Get Value Variable or Text
*
* @param (array) $dataRequest
* @param (string) $conditionVarText
* @param (string) $conditionVariable
* @param (string) $conditionText
* @return (string) $valueVariableText
*
* by Helen Callisaya
*/
function getValueVariableText($dataRequest, $conditionVarText, $conditionVariable, $conditionText)
{
    //Validates if the condition applies to variable or text
    if ($conditionVarText == "VARIABLE") {
        //Get the value of the variable to evaluate registered in the request example: $date['YQP_SUM_INSURED_VESSEL']
        if ($dataRequest[$conditionVariable]) {
            $valueVariableText = $dataRequest[$conditionVariable];
        } else {
            //When the variable does not exist we set it to 0
            $valueVariableText = 0;
        }
    } else {
        $valueVariableText = $conditionText;
    }
    return $valueVariableText;
}
/*
* Get Name Range
*
* @param (string) $listRange
* @param (array) $dataRequest
* @return (string) $clasification
*
* by Helen Callisaya
*/
function rangeValues($typeRange, $dataRequest)
{
    $url = getenv('API_HOST') . '/collections/' . getenv('FORTE_RANGE_COLLECTION') . '/records?pmql=(data.FORTE_RANGE_TYPE="' . $typeRange . '")';
    $getConditionRange = callCurl($url, "GET", $request);
    $clasification = "";
    foreach ($getConditionRange['data'] as $collection) {
        $conditionList = $collection['data']['FORTE_RANGE_CONDITION'];
        $conditions = "";
        for ($r = 0; $r < count($conditionList); $r++) {
            $operator = "";
            $valueVariableText1 = getValueVariableText($dataRequest, $conditionList[$r]['FORTE_RANGE_VARTEXT1'], $conditionList[$r]['FORTE_RANGE_VARIABLE1'], $conditionList[$r]['FORTE_RANGE_TEXT1']);
            $valueVariableText2 = getValueVariableText($dataRequest, $conditionList[$r]['FORTE_RANGE_VARTEXT2'], $conditionList[$r]['FORTE_RANGE_VARIABLE2'], $conditionList[$r]['FORTE_RANGE_TEXT2']);
            //Get condition operator
            if (($r + 1) < count($conditionList)) {
                $operator = $conditionList[$r]['FORTE_RANGE_OPERATOR'] . ' ';
            }
            $conditions .= "'" . $valueVariableText1 . "' " . $conditionList[$r]['FORTE_RANGE_SIGN'] . " '" . $valueVariableText2 . "'" . $operator;
        }
        $evaluate = "\$resultCondition = $conditions;";
        $evaluate = @eval($evaluate);
        //Meets all conditions
        if ($resultCondition == true) {
            $clasification = $collection['data']['FORTE_RANGE_NAME'];
            break;
        }
    }
    return $clasification;
}
//Variables
$apiToken = getenv('API_TOKEN');
$apiHost = getenv('API_HOST');
//Get request from Processmaker Process
$url = $apiHost . '/requests?type=all&order_direction=asc&per_page=10000000';
$requestsAll = callCurl($url, "GET", $request);
$requests = $requestsAll['data'];
//Filter request by processName 
$procesName = "Yacht Quotation Process";
$countRequests = count($requests);
if ($countRequests > 0) {
    foreach ($requests as $request) {
        //Is process Yacht?
        if ($request['name'] == $procesName) {
            $valuesToUpdateOnData = [];
            //Id Request
            $requestId = $request['id'];
            //Get request data
            $url = $apiHost . '/requests/' . $requestId . '?include=data'; 
            $requestsCompleteInfo = callCurl($url, "GET", $request);
            
            if(!empty($requestsCompleteInfo['data']['YQP_TOTAL_PREMIUM_FINAL'])) {
                //***************************Block to add code in case of updating variables*********************
                //Update variable YQP_RANGE_SI_HULL
                $valuesToUpdateOnData['data']['YQP_RANGE_SI_HULL'] = rangeValues("RANGE_HULL", $requestsCompleteInfo['data']);
                //Update variable YQP_RANGE_YEAR
                $valuesToUpdateOnData['data']['YQP_RANGE_YEAR'] = rangeValues("RANGE_YEAR", $requestsCompleteInfo['data']);

                //Check if the variable YQP_SUM_INSURED_HULL_CESION_REPORT exists
                if (empty($requestsCompleteInfo['data']['YQP_SUM_INSURED_HULL_CESSION_REPORT'])) {
                    $valuesToUpdateOnData['data']['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = $requestsCompleteInfo['data']['DATA_WAR_SUM_INSURED'] * ($requestsCompleteInfo['data']['YQP_REINSURER_INFORMATION'][0]['YQP_FORTE_ORDER_SHARE'] / 100);
                    $hullCesionReport = $valuesToUpdateOnData['data']['YQP_SUM_INSURED_HULL_CESSION_REPORT'];
                } else {
                    $hullCesionReport = $requestsCompleteInfo['data']['YQP_SUM_INSURED_HULL_CESSION_REPORT'];
                }
                //**********************************************************************************************
                //Validate if there is data to update
                if (count($valuesToUpdateOnData) > 0 ) {
                    //Combine new information with old information
                    $dataMergeResult = array_merge($requestsCompleteInfo['data'], $valuesToUpdateOnData['data']);
                    //Update request data
                    $apiInstance = $api->processRequests();
                    $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
                    $processRequestEditable->setData($dataMergeResult);
                    $apiInstance->updateProcessRequest($requestId, $processRequestEditable);
                    //Save request Id on an array
                    $requestUpdated[] = array(
                        "requestId" => $requestId
                    );
                }
            }
        }
    }
}
return ['requestUpdated' => $requestUpdated];