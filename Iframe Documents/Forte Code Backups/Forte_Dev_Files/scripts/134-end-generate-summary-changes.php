<?php 
/*   
 *  Generate Summary Changes
 *  by Helen Callisaya
 */

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

/* Function that convert html > ul > li to a PHP array
*
* @param string $html
* @return array
*
* Original by https://gist.github.com/molotovbliss/18acc1522d3c23382757df2dbe6f0134
* Modified by Ronald Nina
*/
function ulToArray($ul)
{
    try {
        if (is_string($ul)) {
            // encode ampersand appropiately to avoid parsing warnings
            $ul = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $ul);
            if (@!$ul = simplexml_load_string($ul)) {
                throw new Exception("Syntax error in UL/LI structure");
                return FALSE;
            }
            return ulToArray($ul);
        } else if (is_object($ul)) {
            $output = array();
            foreach ($ul->li as $li) {
                $output[] = (isset($li->ul)) ? ulToArray($li->ul) : (string) $li;
            }
            return $output;
        } else {
            // In case Unknow type
            throw new Exception("Unknow type");
            return FALSE;
        }
    } catch (Exception $e) {
        $output = ['Exception: ' .  $e->getMessage()];
        return $output;
    }
}
/* Function search variable and format
*
* @param string $variableName
* @param string $variableValue
* @param string $listVariable
* @param string $nameColumn
* @return array
*
* by Helen Callisaya
*/
function searchVariableFormat($variableName, $variableValue, $listVariable, $nameColumn)
{
    $formatVariable = $variableValue;
    $position = array_search($variableName, array_column($listVariable, $nameColumn));
    if ($position != false) {
        switch ($listVariable[$position]['FORTE_SFS_VARIABLES_FORMAT_TYPE']) {
          case "CURRENCY":
            $variableValue = empty(!$variableValue) ? $variableValue : 0;
            $formatVariable = number_format($variableValue, 2, '.', ',');
            break;
          case "PERCENTAGE":
            $variableValue = empty(!$variableValue) ? $variableValue : 0;
            $formatVariable = round($variableValue, 2) . "%";
            break;
          case "Cut-Round 2":
            $variableValue = empty(!$variableValue) ? $variableValue : 0;
            $formatVariable = number_format($variableValue, 2, ".", ",");
            break;
        }        
    }
    return $formatVariable;
}
//Clean message error
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
$dataReturn['FORTE_ERRORS'] = $requestError;

//Convert additional to HTML for Slip and fix it if it is corrupted
if (isset($data["YQP_CLAIM_ADDITIONAL_INFORMATION"]) && $data["YQP_CLAIM_ADDITIONAL_INFORMATION"] != "") {
    $additional = $data["YQP_CLAIM_ADDITIONAL_INFORMATION"];
    //Validate if it is corrupted
    if (count(explode("<li>", $additional)) > 1) {
        //Remove tags other than <ul> and <li>
        $additional = strip_tags($additional, '<ul><li>');
        //Delete everything that is outside the ul 
        $additional = substr($additional, strpos($additional, "<ul>"), strpos($additional, '</ul>') + 5);
        $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $additional;
        //Convert to UL HTML to array for Slip
        //Decode twice for security of unknown characters
        $additional = html_entity_decode(html_entity_decode($additional));
        $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = ulToArray($additional);
    } else {
        //Fix values of additional
        $newAdditional = explode("\n", $additional);
        $additional = "<ul>";
        for ($s = 0; $s < count($newAdditional); $s++) {
            if ($newAdditional[$s] != "" && str_replace("&nbsp;", "", $newAdditional[$s]) != '') {
                $additional .= "\n<li>" . $newAdditional[$s] . "</li>";
            }
        }
        $additional .= "</ul>";
        $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = $additional;
        //Clean line break 
        $additional = str_replace("\n", '', $additional);
        //Convert to UL HTML to array for Slip
        //Decode twice for security of unknown characters
        $additional = html_entity_decode(html_entity_decode($additional));
        $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = ulToArray($additional);
    }
} else {
    $data['YQP_CLAIM_ADDITIONAL_INFORMATION'] = "";
    $data['YQP_SLIP_CLAIM_ADDITIONAL_INFORMATION'] = "";
}
//--------------------------------Compare Variables---------------------------------------
//Get Variables Excluded
$searchCollectionUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_VARIABLES_EXCLUDED') . '/records?pmql=(data.FORTE_PROCESS="' . getenv('FORTE_ID_ENDORSEMENT') . '")';
$responseSearchCollection = callGetCurl($searchCollectionUrl, "GET", "");
$requestCollection = [];
if (count($responseSearchCollection) > 0) {
    $requestCollection = $responseSearchCollection['data'][0]['data']['FORTE_LIST_VARIABLES'];    
}
//Get data Original
$searchCollectionUrl = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_ORIGINAL_DATA_ENDORSEMENT') . '/records?pmql=' . urlencode('(data.FORTE_OD_PROCESS = "' . getenv('FORTE_ID_ENDORSEMENT') . '" AND data.FORTE_OD_REQUEST = "' . $data['_request']['id'] . '")');
$responseSearchCollection = callGetCurl($searchCollectionUrl, "GET", "");
$originalData = [];
if (count($responseSearchCollection) > 0) {
    $originalData = $responseSearchCollection['data'][0]['data']['FORTE_OD_DATA'];    
}
//Get Original Data
$dataRequest = $data;
foreach ($requestCollection as $row) {
   unset($originalData[$row['FORTE_VARIABLE']]);
   unset($dataRequest[$row['FORTE_VARIABLE']]);
}
//Change Client and Vessel
$flagChange = "";
if ($data['YQP_CLIENT_NAME'] != $originalData['YQP_CLIENT_NAME'] || $data['YQP_INTEREST_ASSURED'] != $originalData['YQP_INTEREST_ASSURED']) {
    $flagChange = "CHANGE";
}

$dataReturn['END_FLAG_CHANGE_CLIENT_VESSEL'] = $flagChange;
//Get Special Format Variables
$searchSpecialFormatSummary = getenv('API_HOST') . '/collections/' . getenv('FORTE_ID_SPECIAL_FORMAT_SUMMARY') . '/records?pmql=(data.FORTE_SFS_PROCESS="' . getenv('FORTE_ID_ENDORSEMENT') . '")';
$responseSpecialFormatSummary = callGetCurl($searchSpecialFormatSummary, "GET", "");
$listSpecialFormatSummary = $responseSpecialFormatSummary['data'][0]['data']['FORTE_SFS_LIST_VARIABLES'];
$column = "FORTE_SFS_VARIABLES_ID";
//Compare Original Value and New Value
$changedVariable = [];
$row_id = 0;
foreach ($dataRequest as $key => $value) {
    if (substr($key, 0, 3) != "END") {
        if (gettype($value) != "array") {
            $value = trim($value);
        }
        if ($key == "YQP_ADJUSTERS" || $key == "YQP_CLAIM_ADDITIONAL_INFORMATION") {
            $value = strip_tags(html_entity_decode($value));
            $originalData[$key] = strip_tags(html_entity_decode($originalData[$key]));
        }
        //Validate if is array
        if (gettype($value) == "array") {
            if (array_key_exists('LABEL', $value)) {
                $value = $value['LABEL'];
                $originalData[$key] = $originalData[$key]['LABEL'];
            }
        }
        //Application of masks
        $originalData[$key] = searchVariableFormat($key, $originalData[$key], $listSpecialFormatSummary, $column);
        $value = searchVariableFormat($key, $value, $listSpecialFormatSummary, $column);
        //Compare Values
        if ($value != $originalData[$key]) {
            $changedVariable['row_id'] = $row_id; 
            $changedVariable['END_VARIABLE_NAME'] = $key; 
            $changedVariable['END_VARIABLE_OLD_VALUE'] = $originalData[$key];
            $changedVariable['END_VARIABLE_NEW_VALUE'] = $value;
            $changedVariable['END_VARIABLE_LABEL'] = "";
            $row_id = $row_id + 1;
            $dataReturn['END_CHANGED_VARIABLES'][] = $changedVariable;
        }
    }
}
return $dataReturn;