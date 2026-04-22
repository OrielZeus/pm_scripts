<?php 
/**********************************
 * PE - SS.01 Post-Processing
 *
 * by Telmo Chiri
 *********************************/
 //Before IC.02 Post-Processing
$dataReturn = array();
//Get Info IC 02 Approvers
$aApprovers = $data['PE_IC_02_APPROVERS'][0]['PE_IC_02_APPROVERS'] ?? [];
foreach($aApprovers as &$approver){
    //Get Dates of Confirmation
    if ($approver['PE_IC_APPROVER_TYPE'] == 'PRIMARY') {
        $approver['PE_CONFIRMATION_DATE'] = $data['PE_IC_02_APPROVERS'][0]['PE_CONFIRMATION_DATE_IC2_PRIMARY'];
    }
    if ($approver['PE_IC_APPROVER_TYPE'] == 'SECONDARY') {
        $approver['PE_CONFIRMATION_DATE'] = $data['PE_IC_02_APPROVERS'][0]['PE_CONFIRMATION_DATE_IC2_SECONDARY'];
    }
    //Remove History File node
    unset($approver['PE_HISTORY_FILE']);
}
// Clean PE_IC_02_APPROVERS variable
$dataReturn['PE_IC_02_APPROVERS'] = $aApprovers ?? [];

return $dataReturn;