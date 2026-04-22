<?php 
/**********************************
 * IN - IS.02 Pre-processing
 * by Ana Castillo
 *********************************/
//Set return
$dataReturn = array();

//Set flag if it is IS.02
$dataReturn["IN_FLAG_IS02"] = true;

//Set variable to know that this is IS.02 and if it is Excel
$dataReturn["IN_SHOW_EXCEL_NUMBER"] = false;
if ($data['CHECK_EXCEL_FLOW']) {
    $dataReturn["IN_SHOW_EXCEL_NUMBER"] = true;
}

//Set Submit to required fields
$dataReturn["IN_SAVE_SUBMIT"] = "SUBMIT";

//Fill a first value for calc in next screen
$dataReturn["just60Characters"] = true;

return $dataReturn;