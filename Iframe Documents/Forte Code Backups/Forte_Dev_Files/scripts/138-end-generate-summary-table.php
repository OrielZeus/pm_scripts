<?php 
/*  
 * Generate Summary Table
 * by Helen Callisaya
 */
$dataReturn = array();

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
//Generate Table
$changesVariables = $data['END_CHANGED_VARIABLES'];
$changesVariablesHtml = "<table style='width:100%;font-family: Corbel;font-size: 11pt;' border='1'>";
for ($i = 0; $i < count($changesVariables); $i++) {
    $label = $changesVariables[$i]['END_VARIABLE_LABEL'];
    $valueLabel = $changesVariables[$i]['END_VARIABLE_NEW_VALUE'];
    //Valid if variable is array
    if (is_array($changesVariables[$i]['END_VARIABLE_NEW_VALUE'])) {
        $textValue = "";
        for ($j = 0; $j < count($changesVariables[$i]['END_VARIABLE_NEW_VALUE']); $j++) {
            $textValue .= $changesVariables[$i]['END_VARIABLE_NEW_VALUE'][$j];
        }
        $valueLabel = $textValue;
    }
    $changesVariablesHtml .= "<tr><td style='width:60%;text-align:left;'>" . $label . "</td>";
    $changesVariablesHtml .= "<td style='width:40%;text-align:left;'>" . $valueLabel . "</td></tr>";
}
$changesVariablesHtml .= "</table>";
//Encoded table
$dataReturn['END_CHANGED_VARIABLES_HTML'] = htmlentities($changesVariablesHtml);

return $dataReturn;