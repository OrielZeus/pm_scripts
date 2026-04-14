<?php 
/******************************  
 * Set Update Yacht Data
 *
 * by Helen Callisaya
 * modified by Cinthia Romero
 *****************************/

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
//-------------------------------------------------------------------------
$typeEndorsement = $data['END_TYPE_ENDORSEMENT'];
$requestId = $data['_request']['id'];
$idRequestYacht = $data['END_ID_SELECTION'];
$dataReturn = array();

if ($data['YQP_STATUS'] != "DECLINED") {
    //Update Client Name and Vessel Name in Requests
    $flagChange = isset($data['END_FLAG_CHANGE_CLIENT_VESSEL']) ? $data['END_FLAG_CHANGE_CLIENT_VESSEL'] : "";
    //There is a change of client or vessel
    if ($flagChange == "CHANGE") {
        //Prepare service main url
        $urlGestionSol = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?order_by=updated_at&order_direction=asc&pmql=';
        $clientName = $data['YQP_CLIENT_NAME'];
        $vesselName = $data['YQP_INTEREST_ASSURED'];
        
        //Get all requests related to the parent request
        $getRequest = $urlGestionSol . urlencode('(data.FORTE_REQUEST_PARENT = "' . $idRequestYacht . '" AND data.FORTE_REQUEST_CHILD != "' . $requestId . '")');
        $getRequestResponse = callGetCurl($getRequest, "GET", "");
        foreach($getRequestResponse['data'] as $key => $value ) {
            $requestChild = $value['data']['FORTE_REQUEST_CHILD'];
            $urlGetDataRequest = getenv('API_HOST') . '/requests/' . $requestChild . '?include=data';
            $getDataRequestResponse = callGetCurl($urlGetDataRequest, "GET", "");
            $newDataRequest = $getDataRequestResponse;
            $newDataRequest['data']['YQP_CLIENT_NAME'] = $clientName;
            $newDataRequest['data']['YQP_INTEREST_ASSURED'] = $vesselName;
            //Update request data
            $apiInstance = $api->processRequests();
            $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
            $processRequestEditable->setData($newDataRequest['data']);
            $apiInstance->updateProcessRequest($requestChild, $processRequestEditable);
        }
        $dataReturn['END_UPDATE'] = 'OK';   
    }
    //---------------------------------------------------------------------------------------------------
    //Update Data Coverage Extension and Cancelled Endorsement
    if ($typeEndorsement == "Coverage Extension" || $typeEndorsement == "Cancelled") {
        $premiumFinal = $data['YQP_TOTAL_PREMIUM_FINAL'];
        //If it is a Coverage Extension, the value of the extension premium is added to the Final Premium
        if ($typeEndorsement == 'Coverage Extension') {
            $extensionPremium = $data['END_TOTAL_PREMIUM_EXTENSION'];        
            $premiumFinal = $premiumFinal + $extensionPremium;
            //The extension date becomes the new extension period to.
            $dataReturn['YQP_PERIOD_TO'] = $data['END_NEW_PERIOD_TO'];
            //Get days difference between effective date and current date
            $periodFrom = new DateTime($data['YQP_PERIOD_FROM']);
            $periodTo  = new DateTime($data['END_NEW_PERIOD_TO']);
            $dateDifference = $periodFrom->diff($periodTo);
            $daysDifference = $dateDifference->days;
            $dataReturn['YQP_DAYS_DIFFERENCE'] = $daysDifference;
    
        } else {
            //If it is a Canceled endorsement, the value of the cancellation premium is subtracted from the Final Premium
            $premiumToCancel = $data['END_PREMIUM_TO_CANCEL'];
            $premiumFinal = $premiumFinal - $premiumToCancel;
        }
    
        $totalPremium = 0;
        $brokerTotalPremium = 0;
        //There is a broker    
        if ($data["YQP_GROSS_BROKER_CHANGE"]) {
            //Calculate Broker Total Premium and Premium
            //Variable YQP_BROKER_TOTAL_PREMIUM
            $brokerTotalPremium = $premiumFinal;
            $percentage = $data['YQP_BROKER_PERCENTAGE'] / 100;
            //Variable YQP_TOTAL_PREMIUM
            $totalPremium = $premiumFinal - ($premiumFinal * $percentage);
            $dataReturn['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = number_format($brokerTotalPremium, 2, ".", "");
        } else {
            //Variable YQP_TOTAL_PREMIUM
            $totalPremium = $premiumFinal;
            $dataReturn['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = number_format($totalPremium, 2, ".", "");
        }
        //Values of the Final Premium, Total Premium and Broker Premium
        $dataReturn['YQP_TOTAL_PREMIUM_FINAL'] = $premiumFinal;
        $dataReturn['YQP_TOTAL_PREMIUM'] = $totalPremium;
        $dataReturn['YQP_BROKER_TOTAL_PREMIUM'] = $brokerTotalPremium;
            
        //Set parameters to calc
        $forteOrder = $data["YQP_FORTE_ORDER"];
        //Get Percentage Forte Order
        $forteOrder = $forteOrder / 100;
        //Calc Total Premium Share
        $totalPremiumShareNew = $premiumFinal * $forteOrder;
        $dataReturn["YQP_PREMIUM_SHARE"] = $totalPremiumShareNew;
            
        //Calculate values recordList
        $reinsurerList = $data['YQP_REINSURER_INFORMATION'];
        $totalForteFee = 0;
        $totalTaxGrossShare = 0;
        $totalTaxNetShare = 0;
        $totalPremiumReservesGrossShare = 0;
        $totalPremiumReservesNetShare = 0;
        $epsilon = 2.2204460492503E-16;
        for ($i = 0; $i < count($reinsurerList); $i++ ) {
            $reinsurerList[$i]['YQP_TOTAL_PREMIUM_GRID'] = $premiumFinal;
            $reinsurerList[$i]['YQP_TOTAL_PREMIUM_GRID_TOTAL'] = $premiumFinal;
            $totalPremiumCalc = $premiumFinal;
            //----------Calc YQP_TOTAL_PREMIUM_SHARE----------------
            $sharePercentage = $reinsurerList[$i]['YQP_SHARE_PERCENTAGE'];
            $forteOrder = $reinsurerList[$i]['YQP_FORTE_ORDER_GRID'];
            //Validate no empty values
            if ($sharePercentage != "" && $totalPremiumCalc != "" && $forteOrder != "") {
                //Set percentages
                $forteOrder = $forteOrder / 100;
                $sharePercentage = $sharePercentage / 100;
                //Get total Forte order
                $newTotalForteOrder = $totalPremiumCalc * $forteOrder;
                //Calc total and round Javascript without losses
                $totalPremiumShare = round(($newTotalForteOrder * $sharePercentage + $epsilon) * 100) / 100;
            } else {
                $totalPremiumShare = "";
            }
            $reinsurerList[$i]['YQP_TOTAL_PREMIUM_SHARE'] = $totalPremiumShare;
            //------------Calc YQP_BROKER_DEDUCTION_TOTAL--------------
            $brokerDeductionTotal = 0;
            //Parameters to calculate
            $brokerDeduction = $reinsurerList[$i]['YQP_BROKER_DEDUCTION'];
            if ($brokerDeduction != "" && $totalPremiumCalc != "") {
                //Get percentage
                $brokerDeduction = $brokerDeduction / 100;
                //Do it negative
                $brokerDeduction = $brokerDeduction * (-1);
                //Calc Broker Total deduction and round it with no losses
                $brokerDeductionTotal = round(($totalPremiumCalc * $brokerDeduction + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_BROKER_DEDUCTION_TOTAL'] = $brokerDeductionTotal;
            //-------------Calc YQP_BROKER_DEDUCTION_USD-----------------
            $brokerDeductionUSD = 0;
            //Parameters to calculate
            $totalPremium_deduction = $reinsurerList[$i]['YQP_TOTAL_PREMIUM_SHARE'];
            $brokerPercentage = $reinsurerList[$i]['YQP_BROKER_DEDUCTION'];
            //Validate no empty values
            if ($brokerPercentage != "" && $totalPremium_deduction != "") {
                //Get percentage
                $brokerPercentage = $brokerPercentage / 100;
                //Do it negative
                $brokerPercentage = $brokerPercentage * (-1);
                //Calc Broker deduction and round it with no losses
                $brokerDeductionUSD = round(($totalPremium_deduction * $brokerPercentage + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_BROKER_DEDUCTION_USD'] = $brokerDeductionUSD;
            //-------------------Calc YQP_NET_TECHNICAL_PREMIUM_TOTAL--------------------
            $netTechnicalTotal = 0;
            //Validate no empty values
            if ($totalPremiumCalc != "") {
                if ($brokerDeductionTotal == "") {
                    $brokerDeductionTotal = 0;
                }
                //Calc Net Technical Total and round it with no losses
                $netTechnicalTotal = round(($totalPremiumCalc + $brokerDeductionTotal + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_NET_TECHNICAL_PREMIUM_TOTAL'] = $netTechnicalTotal;
            //-----------------Calc YQP_NET_TECHNICAL_PREMIUM_SHARE----------------------------
            $netTechnicalShare = 0;
            //Validate no empty values
            if ($totalPremiumShare != "") {
                if ($brokerDeductionUSD == "") {
                    $brokerDeductionUSD = 0;
                }
                //Calc Net Technical Share and round it with no losses
                $netTechnicalShare = round(($totalPremiumShare + $brokerDeductionUSD + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_NET_TECHNICAL_PREMIUM_SHARE'] = $netTechnicalShare;
            //-----------------------Calc YQP_TAX_GROSS_SHARE-------------------------
            $taxGrossShare = 0;
            //Parameters to calculate
            $taxPercentage = $reinsurerList[$i]['YQP_TAX_ON_GROSS'];
            //Validate no empty values
            if ($taxPercentage != "" && $totalPremiumShare != "") {
                //Get percentage
                $taxPercentage = $taxPercentage / 100;
                //Do it negative
                $taxPercentage = $taxPercentage * (-1);
                //Calc Gross Share and round it with no losses
                $taxGrossShare = round(($totalPremiumShare * $taxPercentage + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_TAX_GROSS_SHARE'] = $taxGrossShare;
            //------------------------Calc YQP_TAX_NET_SHARE-------------------------
            $taxNetShare = 0;
            $taxPercentageNet = $reinsurerList[$i]['YQP_TAX_ON_NET'];
            //Validate no empty values
            if ($taxPercentageNet != "" && $netTechnicalShare != "") {
                //Get Percentage
                $taxPercentageNet = $taxPercentageNet / 100;
                //Do it negative
                $taxPercentageNet = $taxPercentageNet * (-1);
                //Calc the Tax net Share with no losses
                $taxNetShare = round(($netTechnicalShare * $taxPercentageNet + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_TAX_NET_SHARE'] = $taxNetShare;
            //--------------------------Calc YQP_PREMIUM_RESERVES_GROSS_SHARE---------------
            $premiumReserves = 0;
            $reservesGross = $reinsurerList[$i]['YQP_PREMIUM_RESERVES_ON_GROSS'];
            //Validate no empty values
            if ($reservesGross != "" && $totalPremiumShare != "") {
                //Get Percentage
                $reservesGross = $reservesGross / 100;
                //Do it negative
                $reservesGross = $reservesGross * (-1);
                //Calc the Premium Reserves Gross with no losses
                $premiumReserves = round(($totalPremiumShare * $reservesGross + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_PREMIUM_RESERVES_GROSS_SHARE'] = $premiumReserves;
            //-------------------------Calc YQP_PREMIUM_RESERVES_NET_SHARE---------------------------------
            $reservesNetShare = 0;
            $reservesNet = $reinsurerList[$i]['YQP_PREMIUM_RESERVES_ON_NET'];
            //Validate no empty values
            if ($reservesNet != "" && $totalPremiumShare != "") {
                //Get Percentage
                $reservesNet = $reservesNet / 100;
                //Do it negative
                $reservesNet = $reservesNet * (-1);
                //Calc the Premium Reserves Net with no losses
                $reservesNetShare = round(($totalPremiumShare * $reservesNet + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_PREMIUM_RESERVES_NET_SHARE'] = $reservesNetShare;
            //--------------------------Calc YQP_FORTE_FEE---------------------------------
            $forteFee = 0;
            $forteFeePercentage = $reinsurerList[$i]['YQP_FORTE_FEE_PERCENTAGE'];
            //Validate no empty values
            if ($forteFeePercentage != "" && $netTechnicalShare != "") {
                //Get Percentage
                $forteFeePercentage = $forteFeePercentage / 100;
                //Do it negative
                $forteFeePercentage = $forteFeePercentage * (-1);
                //Calc Forte Fee with no losses
                $forteFee = round(($netTechnicalShare * $forteFeePercentage + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_FORTE_FEE'] = $forteFee;
            //----------------------Calc YQP_CEDED_PREMIUM_SHARE------------------
            $cededShare = 0;
            if ($taxGrossShare == "NaN") $taxGrossShare = 0;
            if ($taxNetShare == "NaN") $taxNetShare = 0;
            if ($premiumReserves == "NaN") $premiumReserves = 0;
            if ($reservesNetShare == "NaN") $reservesNetShare = 0;
            //Validate no empty values
            if ($netTechnicalShare != "" && $forteFee != "") {
                //Sumatory of all values
                $cededShare = $netTechnicalShare + $taxGrossShare + $taxNetShare + $premiumReserves + $reservesNetShare + $forteFee;
                //Calc Ceded Share with no losses
                $cededShare = round(($cededShare + $epsilon) * 100) / 100;
            }
            $reinsurerList[$i]['YQP_CEDED_PREMIUM_SHARE'] = $cededShare;
            //Calculate Sum YQP_TOTAL_FORTE_FEE
            $totalForteFee = $totalForteFee + ($reinsurerList[$i]["YQP_FORTE_FEE"] * 1);
            //Calculate Sum YQP_TOTAL_TAX_GROSS_SHARE
            $totalTaxGrossShare = $totalTaxGrossShare + ($reinsurerList[$i]["YQP_TAX_GROSS_SHARE"] * 1);
            //Calculate Sum YQP_TOTAL_TAX_NET_SHARE
            $totalTaxNetShare = $totalTaxNetShare + ($reinsurerList[$i]["YQP_TAX_NET_SHARE"] * 1);
            //Calculate Sum YQP_TOTAL_PREMIUM_RESERVES_GROSS_SHARE
            $totalPremiumReservesGrossShare = $totalPremiumReservesGrossShare + ($reinsurerList[$i]["YQP_PREMIUM_RESERVES_GROSS_SHARE"] * 1);
            //Calculate Sum YQP_TOTAL_PREMIUM_RESERVES_NET_SHARE
            $totalPremiumReservesNetShare = $totalPremiumReservesNetShare + ($reinsurerList[$i]["YQP_PREMIUM_RESERVES_NET_SHARE"] * 1);
            //Calculate Sum YQP_TOTAL_REINSURANCE_COMMISSION_SHARE
            $totalReinsuranceComissionShare = $totalReinsuranceComissionShare + ($reinsurerList[$i]["YQP_BROKER_DEDUCTION_USD"] * 1);
    
        }
        $dataReturn['YQP_REINSURER_INFORMATION'] = $reinsurerList;
        $dataReturn['YQP_TOTAL_FORTE_FEE'] = $totalForteFee;
        $dataReturn['YQP_TOTAL_TAX_GROSS_SHARE'] = $totalTaxGrossShare;
        $dataReturn['YQP_TOTAL_TAX_NET_SHARE'] = $totalTaxNetShare;
        $dataReturn['YQP_TOTAL_PREMIUM_RESERVES_GROSS_SHARE'] = $totalPremiumReservesGrossShare;
        $dataReturn['YQP_TOTAL_PREMIUM_RESERVES_NET_SHARE'] = $totalPremiumReservesNetShare;
        $dataReturn['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'] = $totalReinsuranceComissionShare;
        //Calculate YQP_TOTAL_REINSURANCE_COMMISION_BROKER
        $dataReturn['YQP_TOTAL_REINSURANCE_COMMISION_BROKER'] = 0;
        if ($dataReturn['YQP_TOTAL_PREMIUM_FINAL'] != "" && $data['YQP_TOTAL_BROKER_DEDUCTION_PERCENTAGE'] != "") {
            $totalBrokerPercentage = $data['YQP_TOTAL_BROKER_DEDUCTION_PERCENTAGE'] / 100;
            $totalBrokerPercentage = $totalBrokerPercentage * (-1);
            $dataReturn['YQP_TOTAL_REINSURANCE_COMMISION_BROKER'] = round(($dataReturn['YQP_TOTAL_PREMIUM_FINAL'] * $totalBrokerPercentage + $epsilon) * 100) / 100;
        }
        //Calculate YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE
        $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] = 0;
        if ($dataReturn['YQP_PREMIUM_SHARE'] != "" && $totalReinsuranceComissionShare != "") {
            $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] = round(($dataReturn['YQP_PREMIUM_SHARE'] + $totalReinsuranceComissionShare + $epsilon) * 100) / 100;
        }
        //Calculate YQP_TOTAL_NET_TECHNICAL_PREMIUM
        $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM'] = 0;
        if ($dataReturn['YQP_TOTAL_PREMIUM_FINAL'] != "") {
            $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM'] = round(($dataReturn['YQP_TOTAL_PREMIUM_FINAL'] + $dataReturn['YQP_TOTAL_REINSURANCE_COMMISION_BROKER'] + $epsilon) * 100) / 100;
        }
        //Calculate YQP_TOTAL_CEDED_PREMIUM_SHARE
        $dataReturn['YQP_TOTAL_CEDED_PREMIUM_SHARE'] = 0;
        if ($dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] != "" && $totalForteFee != "") {
            $totalCededShare = $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] + $totalTaxGrossShare + $totalTaxNetShare + $totalPremiumReservesGrossShare + $totalPremiumReservesNetShare + $totalForteFee;
            $dataReturn['YQP_TOTAL_CEDED_PREMIUM_SHARE'] = round(($totalCededShare + $epsilon) * 100) / 100;
        }
        //Calculate YQP_TOTAL_FORTE_FEE_PERCENTAGE
        $dataReturn['YQP_TOTAL_FORTE_FEE_PERCENTAGE'] = 0;
        if ($totalForteFee != "" && $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] != "") {
                $totalForteFeePercentage = (($totalForteFee / $dataReturn['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE']) * (-1)) * 100;
                //Calc round forte fee with no losses
                $dataReturn['YQP_TOTAL_FORTE_FEE_PERCENTAGE'] = round(($totalForteFeePercentage + $epsilon) * 100) / 100;
        }

        $dataReturn['YQP_FORTE_ORDER_OLD'] = $data["YQP_FORTE_ORDER"];
        $dataReturn['YQP_TOTAL_PREMIUM_FINAL_OLD'] = $dataReturn['YQP_TOTAL_PREMIUM_FINAL'];
    }
    //-------------Update data Original when uses Cancel endorsement------------------
    if ($typeEndorsement == "Cancel Endorsement") {
        $idRequestOld = $data['END_REQUEST_ENDORSEMENT_OLD'];
        //First we get the Original data that saved the current request
        $getDataOriginalFromRequest = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?pmql=' . urlencode('(data.FORTE_OD_REQUEST = "' . $data['_request']['id'] . '")');
        $responseDataOriginalFromRequest = callGetCurl($getDataOriginalFromRequest, "GET", "");
        //Id collection current Request
        $idCollectionCurrentRequest = $responseDataOriginalFromRequest["data"][0]["id"];
        $dataRequestOld = $responseDataOriginalFromRequest['data'][0]['data']['FORTE_OD_DATA'];
        $flagChangeClientVesselOld = isset($dataRequestOld['END_FLAG_CHANGE_CLIENT_VESSEL']) ? $dataRequestOld['END_FLAG_CHANGE_CLIENT_VESSEL'] : "";
    
        //Get original data the last request
        $getDataOriginalRequest = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?pmql=' . urlencode('(data.FORTE_OD_REQUEST = "' . $idRequestOld . '")');
        $responseDataOriginalRequest = callGetCurl($getDataOriginalRequest, "GET", "");
        //Data original request to cancel
        $dataOriginalOld = $responseDataOriginalRequest['data'][0]['data']['FORTE_OD_DATA'];
        //Id request to Cancel
        $idCollectionRequestToCancel = $responseDataOriginalRequest["data"][0]["id"];
        //Was there a change of client or vessel?
        if ($flagChangeClientVesselOld == "CHANGE") {
            //Client and Vessel Original
            $clientNameOld = $dataOriginalOld['YQP_CLIENT_NAME'];
            $vesselNameOld = $dataOriginalOld['YQP_INTEREST_ASSURED'];
    
            //Prepare service main url
            $urlGestionSol = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?order_by=updated_at&order_direction=asc&pmql=';   
            //Get all requests related to the parent request
            $getRequest = $urlGestionSol . urlencode('(data.FORTE_REQUEST_PARENT = "' . $idRequestYacht . '" AND data.FORTE_REQUEST_CHILD != "' . $requestId . '")');
            $getRequestResponse = callGetCurl($getRequest, "GET", "");
            foreach($getRequestResponse['data'] as $key => $value ) {
                $requestChild = $value['data']['FORTE_REQUEST_CHILD'];
                $urlGetDataRequest = getenv('API_HOST') . '/requests/' . $requestChild . '?include=data';
                $getDataRequestResponse = callGetCurl($urlGetDataRequest, "GET", "");
                $newDataRequest = $getDataRequestResponse;
                $newDataRequest['data']['YQP_CLIENT_NAME'] = $clientNameOld;
                $newDataRequest['data']['YQP_INTEREST_ASSURED'] = $vesselNameOld;
                //Update request data
                $apiInstance = $api->processRequests();
                $processRequestEditable = new \ProcessMaker\Client\Model\ProcessRequestEditable();
                $processRequestEditable->setData($newDataRequest['data']);
                $apiInstance->updateProcessRequest($requestChild, $processRequestEditable);
            }
            $dataReturn['END_UPDATE'] = 'OK';
        }
        //Update Request in collection Data original  
        $dataOriginalSave = array();
        $dataOriginalSave['FORTE_OD_REQUEST'] = $data['_request']['id'];
        $dataOriginalSave['FORTE_OD_PROCESS'] = $data['_request']['process_id'];
        $dataOriginalSave['FORTE_OD_OLD_REQUEST'] = $idRequestOld;
        $dataOriginalSave['FORTE_OD_DATA'] = $responseDataOriginalFromRequest['data'][0]['data']['FORTE_OD_DATA'];
        $dataOriginalSave['FORTE_OD_TYPE_ENDORSEMENT'] = $typeEndorsement;
        $dataOriginalSave['FORTE_OD_PARENT'] = $data['END_ID_SELECTION'];
        $dataOriginalSave['FORTE_OD_CANCEL'] = "YES";  
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records/' . $idCollectionCurrentRequest;
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataOriginalSave));
        //Update Request Old in collection
        $dataOriginalSave = array();
        $dataOriginalSave['FORTE_OD_REQUEST'] = $idRequestOld;
        $dataOriginalSave['FORTE_OD_PROCESS'] = $data['_request']['process_id'];
        $dataOriginalSave['FORTE_OD_OLD_REQUEST'] = $data['_request']['id'];
        $dataOriginalSave['FORTE_OD_DATA'] = $responseDataOriginalRequest['data'][0]['data']['FORTE_OD_DATA'];
        $dataOriginalSave['FORTE_OD_TYPE_ENDORSEMENT'] = $responseDataOriginalRequest['data'][0]['data']['FORTE_OD_TYPE_ENDORSEMENT'];
        $dataOriginalSave['FORTE_OD_PARENT'] = $responseDataOriginalRequest['data'][0]['data']['FORTE_OD_PARENT'];
        $dataOriginalSave['FORTE_OD_CANCEL'] = "YES";
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records/' . $idCollectionRequestToCancel;
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataOriginalSave));      
        $dataReturn['END_UPDATE'] = "CANCEL_OK";
    }
    
    //------------------------------Save to Gestion Solicitudes Collection-----------------------------------------------------
    $searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
    $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
    $dataSave = array();
    $dataSave['FORTE_REQUEST'] = $data['_request']['id'];
    $dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
    $dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
    $dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
    //Validate Process
    if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
        $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
        $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        //Name Process
        $dataRequest['FORTE_TITLE_PROCESS'] = "Yacht Quotation Process";
    } else {
        $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
        $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
        //Name Process
        $dataRequest['FORTE_TITLE_PROCESS'] = "Yacht Endorsement Process";
    }  
    $dataSave['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
    $dataSave['YQP_STATUS'] = $data['YQP_STATUS'];
    $dataSave['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
    $dataSave['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
    $dataSave['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
    $dataSave['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
    $dataSave['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
    $dataSave['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
    $dataSave['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
    $dataSave['YQP_SITUATION'] = $data['YQP_SITUATION'];
    $dataSave['YQP_TYPE'] = $data['YQP_TYPE'];
    $dataSave['YQP_REASSURED_CEDENT_LABEL'] = isset($data['YQP_REASSURED_CEDENT']['LABEL']) ? $data['YQP_REASSURED_CEDENT']['LABEL'] : "";
    $dataSave['YQP_REINSURANCE_BROKER_LABEL'] = isset($data['YQP_REINSURANCE_BROKER']['LABEL']) ? $data['YQP_REINSURANCE_BROKER']['LABEL'] : "";
    $dataSave['YQP_BROKER_TOTAL_PREMIUM_REPORT'] = $data['YQP_BROKER_TOTAL_PREMIUM_REPORT'];
    $dataSave['YQP_LINE_BUSINESS'] = $data['YQP_LINE_BUSINESS'];
    $dataSave['YQP_SOURCE'] = $data['YQP_SOURCE'];
    $dataSave['YQP_SUBMISSION_DATE_REPORT'] = $data['YQP_SUBMISSION_DATE_REPORT'];
    $dataSave['YQP_SUBMISSION_MONTH_REPORT'] = $data['YQP_SUBMISSION_MONTH_REPORT'];
    $dataSave['YQP_COMMENTS'] = $data['YQP_COMMENTS'];
    $dataSave['YQP_RETROCESIONARY'] = $data['YQP_RETROCESIONARY'];
    $dataSave['YQP_TERM'] = $data['YQP_TERM'];
    $dataSave['YQP_SLIP_DOCUMENT_NAME'] = $data['YQP_SLIP_DOCUMENT_NAME'];
    $dataSave['YQP_RISK_ATTACHING_MONTH'] = $data['YQP_RISK_ATTACHING_MONTH'];
    $dataSave['YQP_MONTH_SENT_ADOBE_REPORT'] = $data['YQP_MONTH_SENT_ADOBE_REPORT'];
    $dataSave['YQP_FORTE_ORDER'] = empty($data['YQP_FORTE_ORDER']) ? "" : $data['YQP_FORTE_ORDER'];
    //Saving mooring port and club marina to report (Added by Cinthia Romero 2023-11-03)
    $mooringPortReport = empty($data['YQP_MOORING_PORT']) ? "" : $data['YQP_MOORING_PORT'];
    if (empty($data['YQP_LOCATION_MOORING_PORT']) != true && $data['YQP_LOCATION_MOORING_PORT'] == "Other") {
        $mooringPortReport = empty($data['YQP_SPECIFY_PORT']) ? "" : $data['YQP_SPECIFY_PORT'];
    }
    $dataSave['YQP_MOORING_PORT_REPORT'] = $mooringPortReport;
    $dataSave['YQP_CLUB_MARINA'] = empty($data['YQP_CLUB_MARINA']) ? "" : $data['YQP_CLUB_MARINA'];
    $dataSave['YQP_REINSURER_INFORMATION'] = isset($data['YQP_REINSURER_INFORMATION']) ? $data['YQP_REINSURER_INFORMATION'] : [];
    //
    
    //Check Process Type
    if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
        $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
        $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
    } else {
        $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
        $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
        $dataSave['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
    }
    
    //Validate if the request exists
    if (count($searchRequest["data"]) == 0) {
        $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records';
        $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
    } else {
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_GESTION_SOLICITUDES_ID') . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
    }
    
    //--------------------------Save to Producing Files Collection----------------------------------------
    $searchRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records?pmql=(data.FORTE_REQUEST="' . $data['_request']['id'] . '")';
    $searchRequest = callGetCurl($searchRequestUrl, "GET", "");
    $dataSave = array();
    $dataSave['FORTE_REQUEST'] = $data['_request']['id'];
    $dataSave['FORTE_PROCESS'] = $data['_request']['process_id'];
    $dataSave['FORTE_CREATE_DATE'] = $data['YQP_CREATE_DATE'];
    $dataSave['FORTE_CREATE_USER'] = $data['YQP_USER_ID'];
    
    $dataSave['YQP_CLIENT_NAME'] = $data['YQP_CLIENT_NAME'];
    $dataSave['YQP_INTEREST_ASSURED'] = $data['YQP_INTEREST_ASSURED'];
    $dataSave['YQP_COUNTRY_BUSINESS'] = $data['YQP_COUNTRY_BUSINESS'];
    $dataSave['YQP_CATCH_MONTH_REPORT'] = $data['YQP_CATCH_MONTH_REPORT'];
    $dataSave['YQP_PIVOT_TABLE_NUMBER'] = $data['YQP_PIVOT_TABLE_NUMBER'];
    $dataSave['YQP_SLIP_DOCUMENT_NAME'] = $data['YQP_SLIP_DOCUMENT_NAME'];
    $dataSave['YQP_TYPE'] = $data['YQP_TYPE'];
    $dataSave['YQP_REASSURED_CEDENT_LABEL'] = $data['YQP_REASSURED_CEDENT']['LABEL'];
    $dataSave['YQP_REINSURANCE_BROKER_LABEL'] = $data['YQP_REINSURANCE_BROKER']['LABEL'];
    $dataSave['YQP_PERIOD_FROM_REPORT'] = $data['YQP_PERIOD_FROM_REPORT'];
    $dataSave['YQP_PERIOD_TO_REPORT'] = $data['YQP_PERIOD_TO_REPORT'];
    $dataSave['YQP_TRIMESTER'] = $data['YQP_TRIMESTER'];
    $dataSave['YQP_YEAR_PERIOD'] = $data['YQP_YEAR_PERIOD'];
    $dataSave['YQP_CURRENCY'] = $data['YQP_CURRENCY'];
    $dataSave['YQP_SUM_INSURED_VESSEL'] = $data['YQP_SUM_INSURED_VESSEL'];
    $dataSave['DATA_WAR_SUM_INSURED_TENDER'] = $data['DATA_WAR_SUM_INSURED_TENDER'];
    $dataSave['DATA_WAR_SUM_INSURED_NET'] = $data['DATA_WAR_SUM_INSURED'];
    $dataSave['YQP_LIMIT_PI'] = $data['YQP_LIMIT_PI'];
    $dataSave['DATA_WAR_SUM_INSURED'] = $data['DATA_WAR_SUM_INSURED'];
    $dataSave['YQP_PERSONAL_EFFECTS_LIMIT'] = $data['YQP_PERSONAL_EFFECTS_LIMIT'];
    $dataSave['YQP_MEDICAL_PAYMENTS_LIMIT'] = $data['YQP_MEDICAL_PAYMENTS_LIMIT'];
    $dataSave['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'] = $data['YQP_OWNERS_UNINSURED_VESSEL_LIMIT'];
    $dataSave['YQP_TOTAL_PREMIUM_FINAL'] = $data['YQP_TOTAL_PREMIUM_FINAL'];
    $dataSave['YQP_DEDUCTIBLE'] = $data['YQP_DEDUCTIBLE'];
    $dataSave['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'] = $data['YQP_SHOW_SPECIAL_PERCENTAGE_DEDUCTIBLE'];
    $dataSave['YQP_WAR_DEDUCTIBLE_SLIP'] = $data['YQP_WAR_DEDUCTIBLE_SLIP'];
    $dataSave['YQP_PI_DEDUCTIBLE_SLIP'] = $data['YQP_PI_DEDUCTIBLE_SLIP'];
    $dataSave['YQP_NUMBER_PAYMENTS'] = $data['YQP_NUMBER_PAYMENTS'];
    $dataSave['YQP_BROKER_PERCENTAGE'] = $data['YQP_BROKER_PERCENTAGE'];
    $dataSave['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'] = $data['YQP_TOTAL_REINSURANCE_COMMISSION_SHARE'];
    $dataSave['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] = $data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'];
    $dataSave['YQP_TYPE_VESSEL_REPORT'] = $data['YQP_TYPE_VESSEL_REPORT'];
    $dataSave['YQP_TYPE_CODE_REPORT'] = $data['YQP_TYPE_CODE_REPORT'];
    $dataSave['YQP_VESSEL_MARK_MODEL'] = $data['YQP_VESSEL_MARK_MODEL'];
    $dataSave['YQP_LENGTH_UNIT_REPORT'] = $data['YQP_LENGTH_UNIT_REPORT'];
    $dataSave['YQP_YEAR'] = $data['YQP_YEAR'];
    $dataSave['YQP_NAVIGATION_LIMITS'] = $data['YQP_NAVIGATION_LIMITS'];
    $dataSave['YQP_USE'] = $data['YQP_USE'];
    $dataSave['YQP_LOSS_PAYEE'] = $data['YQP_LOSS_PAYEE'];
    $dataSave['YQP_HULL_MATERIAL'] = $data['YQP_HULL_MATERIAL'];
    $dataSave['YQP_FLAG'] = $data['YQP_FLAG'];
    $dataSave['YQP_MOORING_PORT_REPORT'] = $data['YQP_MOORING_PORT_REPORT'];
    $dataSave['YQP_CLUB_MARINA'] = $data['YQP_CLUB_MARINA'];
    $dataSave['YQP_COMMENTS'] = $data['YQP_COMMENTS'];
    $dataSave['YQP_USER_USERNAME'] = $data['YQP_USER_USERNAME'];
    $dataSave['YQP_RISK_ATTACHING_MONTH'] = $data['YQP_RISK_ATTACHING_MONTH'];
    $dataSave['YQP_TERM'] = $data['YQP_TERM'];
    $dataSave['YQP_TYPE_YACHT'] = $data['YQP_TYPE_YACHT'];
    $dataSave['YQP_RANGE_SI_HULL'] = $data['YQP_RANGE_SI_HULL'];
    $dataSave['YQP_RANGE_YEAR'] = $data['YQP_RANGE_YEAR'];
    $dataSave['YQP_STATUS'] = $data['YQP_STATUS'];
    //Calculate variables YQP_SUM_INSURED_HULL_CESSION_REPORT, YQP_RATE_CESSION_REPORT, YQP_TAXES_USD_100_REPORT used in Producing report. Added by Cinthia Romero 2024-01-08
    $reinsurerGridData = isset($data['YQP_REINSURER_INFORMATION']) ? $data['YQP_REINSURER_INFORMATION'] : [];
    if (count($reinsurerGridData) > 0 && !empty($data['DATA_WAR_SUM_INSURED'])) {
        foreach ($reinsurerGridData as $key=>$reinsurer) {
            /*if (!empty($reinsurer['YQP_FORTE_ORDER_SHARE'])) {
                //YQP_SUM_INSURED_HULL_CESSION_REPORT
                $sumInsuredHullCessionReport = $data['DATA_WAR_SUM_INSURED'] * ($reinsurer['YQP_FORTE_ORDER_SHARE'] / 100);
                $reinsurerGridData[$key]["YQP_SUM_INSURED_HULL_CESSION_REPORT"] = $sumInsuredHullCessionReport;
                //YQP_RATE_CESSION_REPORT
                if (!empty($data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'])) {
                    if ($sumInsuredHullCessionReport > 0) {
                        $reinsurerGridData[$key]['YQP_RATE_CESSION_REPORT'] = round($data['YQP_TOTAL_NET_TECHNICAL_PREMIUM_SHARE'] / $sumInsuredHullCessionReport, 2);
                    } else {
                        $reinsurerGridData[$key]['YQP_RATE_CESSION_REPORT'] = 0;
                    }
                }
            }*/
            //YQP_TAXES_USD_100_REPORT
            $reinsurerGridData[$key]['YQP_TAXES_USD_100_REPORT'] = $reinsurer['YQP_TOTAL_PREMIUM_GRID_TOTAL'] * ($reinsurer['YQP_TAX_ON_GROSS'] / 100);
        }
    }
    $dataSave['YQP_REINSURER_INFORMATION'] = $reinsurerGridData;
    //-----------------Calculate Rate Cession and Hull Cession--------------------
    if ($data['YQP_PRODUCT'] != "PI_RC") {
        //(Total Hull Sum Insured * Forte Order) Add HC
        $dataSave['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = $data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100);
        //(Total Premium * FORTE ORDER) / (Total Hull Sum Insured * Forte Order) Add HC
        $dataSave['YQP_RATE_CESSION_REPORT'] = ($data['YQP_BROKER_TOTAL_PREMIUM_REPORT'] * ($data['YQP_FORTE_ORDER'] / 100)) / ($data['YQP_SUM_INSURED_VESSEL'] * ($data['YQP_FORTE_ORDER'] / 100));
    } else {
        $dataSave['YQP_SUM_INSURED_HULL_CESSION_REPORT'] = 0;
        $dataSave['YQP_RATE_CESSION_REPORT'] = 0;
    }    //---------------------------------------------------------------------------
    //Validate Process
    if ($data['_request']['process_id'] == getenv('FORTE_ID_YACHT')) {
        $dataSave['FORTE_REQUEST_PARENT'] = $data['_request']['id'];
        $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        $dataSave['FORTE_REQUEST_ORDER'] = $data['_request']['id'] . ".0";
    } else {
        $dataSave['FORTE_REQUEST_PARENT'] = $data['END_ID_SELECTION'];
        $dataSave['FORTE_REQUEST_CHILD'] = $data['_request']['id'];
        $dataSave['END_TYPE_ENDORSEMENT'] = $data['END_TYPE_ENDORSEMENT'];
        $dataSave['FORTE_REQUEST_ORDER'] = $data['END_ID_SELECTION'] . "." . $data['END_NUMBER_ENDORSEMENT'];
    } 
    
    //Validate if the request exists
    if (count($searchRequest["data"]) == 0) {
        $insertRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records';
        $insertRequest = callGetCurl($insertRequestUrl, "POST", json_encode($dataSave));
    } else {
        $updateRequestUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_PRODUCING_FILES_ID') . '/records/' . $searchRequest["data"][0]["id"];
        $updateRequest = callGetCurl($updateRequestUrl, "PUT", json_encode($dataSave));
    }
} else {
    $dataReturn['END_UPDATE'] = 'NO UPDATE';
}
return $dataReturn;