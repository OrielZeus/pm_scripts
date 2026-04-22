<?php 
/*****************************
 * PE - DT.04 Clean Variables
 * 
 * by Cinthia Romero
 ****************************/
$dataReturn = array();
if ($data["PE_SAVE_SUBMIT_DT4"] == "SUBMIT") {
    $dataReturn["PE_AML_REVIEW_COMPLETE"] = "";
    $dataReturn["PE_LAW_CLERK_COMMENTS"] = "";
}
return $dataReturn;