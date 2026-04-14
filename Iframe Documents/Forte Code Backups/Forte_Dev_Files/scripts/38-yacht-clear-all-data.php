<?php 
/*   
 *  Clear pre-loaded data when a new search is performed
 *  by Helen Callisaya
 */
 
//Set variable of return
$dataClean = array();

//We go through the variables and clean them
foreach ($data as $key => $value) {
    if ($key != "_request" && 
        $key != "_user" && 
        $key != "YQP_CLIENT_NAME" && 
        $key != "YQP_INTEREST_ASSURED" &&
        $key != "FORTE_ERRORS" &&
        $key != "YQP_USER_ID" &&
        $key != "YQP_REQUESTOR_NAME" &&
        $key != "YQP_USER_FULLNAME" &&
        $key != "YQP_CREATE_DATE") {
            $dataClean[$key] = "";
    }        
}
$dataClean['YQP_STATUS'] = 'PENDING';
return $dataClean;