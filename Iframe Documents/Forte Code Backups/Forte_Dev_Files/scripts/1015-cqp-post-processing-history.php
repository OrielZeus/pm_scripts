<?php 

/*****************************************
* Select the clone request
*
* by Diego Tapia
*****************************************/

$id = null;
$status = $data["CQP_ACTION"];

if ($data["CQP_SUBMIT"] == "NEW_RECORD") {
    $status = "NEW";
} else {
    $renewal = $data["CQP_CLIENT_HISTORY"][array_search(true, array_column($data["CQP_CLIENT_HISTORY"], "SELECTED_RECORD"))];
    $id = $renewal["CQP_REQUEST_ID"];
    $origin = $renewal["origin"];
}

return ["CQP_CLONE_RENEWAL_ID" => $id, "CQP_CLONE_RENEWAL_ORIGIN" => $origin, "CQP_ACTION" => $status];