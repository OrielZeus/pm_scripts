<?php 
/*  
 *  CQP - Watcher update distribution value based if taken
 *  By Adriana Centellas
 */

 //Get data
 $isTaken = $data["taken"];
 $redistribution = $data["CQP_REINSURANCE_DISTRIBUTION"];

 if ($isTaken) {
    return $redistribution;
 } else {
    return 0;
 }