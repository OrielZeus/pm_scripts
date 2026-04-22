<?php 
/*****************************
 * PE - LC.01 Clean Variables
 * 
 * by Cinthia Romero
 * modified by Favio Mollinedo
 ****************************/
$dataReturn = array();
if ($data["PE_SAVE_SUBMIT_LC1"] == "SUBMIT") {
    $dataReturn["PE_DEAL_TEAM_COMMENTS"] = "";
    $dataReturn["PE_AML_LEGAL_REVIEW_COMMENTS"] = "";
}
return $dataReturn;