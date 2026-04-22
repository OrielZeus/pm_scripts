<?php 
/***************************
 * PE - Set OR Current User
 *
 * by Cinthia Romero 
 **************************/
//Initialize Variables
$dataReturn = array();
//Get variable to set user
$orVariable = empty($config["OR_USER_VARIABLE"]) ? "" : $config["OR_USER_VARIABLE"];
if ($orVariable != "") {
    $dataReturn[$orVariable] = $data["AUX_OR_USER"]; 
}
$dataReturn["AUX_OR_USER"] = "";
return $dataReturn;