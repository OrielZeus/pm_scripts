<?php 
/**********************************************
 * PE - Check Open Nodes Before Third PM Block
 *
 * by Cinthia Romero
 * modified by Telmo Chiri
*********************************************/
require_once("/Northleaf_PHP_Library.php");
$apiInstance = $api->tasks();

/**
 * Close Thread
 *
 * @param int $openThread
 * @param array $dataToClose
 * @param object $apiInstance
 * @return none
 *
 * by Cinthia Romero
 */ 
function closeThread($openThread, $dataToClose, $apiInstance)
{
    $apiInstance->getTasksById($openThread);
    $taskDefinitionAttributes = new \ProcessMaker\Client\Model\ProcessRequestTokenEditable();
    $taskDefinitionAttributes->setStatus('COMPLETED');
    $taskDefinitionAttributes->setData($dataToClose); 
    $apiInstance->updateTask($openThread, $taskDefinitionAttributes);
}

/**
 * Update Request
 *
 * @param string $apiHost
 * @param int $requestId
 * @return none
 *
 * by Cinthia Romero
 */ 
function updateRequest($apiHost, $requestId, $dataToUpdate)
{
    $urlUpdateData = $apiHost . '/requests/' . $requestId;
    $resUpdate = callApiUrlGuzzle($urlUpdateData, "PUT", $dataToUpdate);
}

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
//Set initial values
$dataReturn = array();
$requestId = $data["_request"]["id"];
$allICApproversAnswered = empty($data["PE_ALL_APPROVERS_ANSWERED"]) ? "NO" : $data["PE_ALL_APPROVERS_ANSWERED"];

$track =[];
//Query to verify if there is at least one task open
$queryCheckOpenThreads = "SELECT COUNT(element_id) AS TOTAL_OPEN_TASKS 
                          FROM process_request_tokens 
                          WHERE process_request_id = " . $requestId . "
                              AND element_type = 'task'
                              AND element_id <> 'node_OR1'
                              AND element_id <> 'node_OR2'
                              AND status = 'ACTIVE'";
$openThreadsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckOpenThreads));

if (empty($openThreadsResponse["error_message"]) && $openThreadsResponse[0]["TOTAL_OPEN_TASKS"] == 0) {
    //Check if all approvers answered
   // if ($allICApproversAnswered == "YES" && empty($data["PE_OR_ALREADY_CHECKED"])) {
    if (empty($data["PE_OR_ALREADY_CHECKED"]) && ($data["REAPPROVAL_IC"] === false || ($allICApproversAnswered == "YES" && (empty($data["REAPPROVAL_IC"])) 
    || $data["REAPPROVAL_IC"] === true ))) {
        $dataReturn = array(
            "PE_OR1_OPEN_THREAD_SELF_SERVICE" => "NO",
            "PE_OR1_OPEN_THREAD_USER" => "NO",
            "PE_OR2_OPEN_THREAD_SELF_SERVICE" => "NO",
            "PE_OR2_OPEN_THREAD_USER" => "NO"
        );
        //Check which OR need to still be open
        $queryOpenOR = "SELECT id,
                               element_id,
                               user_id,
                               status
                        FROM process_request_tokens 
                        WHERE process_request_id = " . $requestId . "
                            AND element_id IN ('node_OR1', 'node_OR2')";
        $openORResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryOpenOR));
        if (empty($openORResponse["error_message"])) {
            $dataReturn["PE_OR_ALREADY_CHECKED"] = "YES";
            $existOR1 = false;
            $existOR2 = false;
            foreach ($openORResponse as $openThread) {
                if ($openThread["element_id"] == "node_OR1") {
                    $existOR1 = true;
                    $track[] = $openThread["element_id"];
                    $track[] = $openThread["status"];
                    if ($openThread["status"] == 'ACTIVE') {
                        $track[] = "ACTIVE Route";
                        if (empty($openThread["user_id"])) {
                            $dataReturn["PE_OR1_OPEN_THREAD_SELF_SERVICE"] = "YES"; 
                            $dataToUpdate['data'] = [
                                'PE_OR1_OPEN_THREAD_SELF_SERVICE' => 'YES',
                                "PE_OR1_OPEN_THREAD_USER" => "NO",
                                "PE_OR_ALREADY_CHECKED" => "YES",
                                "PE_OR1_USER" => ""
                            ];
                        } else {
                            $dataReturn["PE_OR1_OPEN_THREAD_USER"] = "YES";
                            $dataToUpdate['data'] = [
                                'PE_OR1_OPEN_THREAD_USER' => 'YES',
                                'PE_OR1_OPEN_THREAD_SELF_SERVICE' => 'NO',
                                "PE_OR_ALREADY_CHECKED" => "YES",
                                "PE_OR1_USER" => $openThread["user_id"]
                            ];
                        }
                        //Update request
                        updateRequest($apiHost, $requestId, $dataToUpdate);
                        //Update Parent Request
                        updateRequest($apiHost, $data["_parent"]["request_id"], $dataToUpdate);
                        //Close thread
                        closeThread($openThread["id"], $dataToUpdate['data'], $apiInstance);
                    } else {
                        $track[] = "CLOSED Route";
                    }
                }
                if ($openThread["element_id"] == "node_OR2") {
                    $existOR2 = true;
                    $track[] = $openThread["element_id"];
                    $track[] = $openThread["status"];
                    if ($openThread["status"] == 'ACTIVE') {
                        $track[] = "Active Route";
                        if (empty($openThread["user_id"])) {
                            $dataReturn["PE_OR2_OPEN_THREAD_SELF_SERVICE"] = "YES"; 
                            $dataToUpdate['data'] = [
                                'PE_OR2_OPEN_THREAD_SELF_SERVICE' => 'YES',
                                'PE_OR2_OPEN_THREAD_USER' => 'NO',
                                "PE_OR_ALREADY_CHECKED" => "YES",
                                "PE_OR2_USER" => ""
                            ];
                        } else {
                            $dataReturn["PE_OR2_OPEN_THREAD_USER"] = "YES";
                            $dataToUpdate['data'] = [
                                'PE_OR2_OPEN_THREAD_USER' => 'YES',
                                'PE_OR2_OPEN_THREAD_SELF_SERVICE' => 'NO',
                                "PE_OR_ALREADY_CHECKED" => "YES",
                                "PE_OR2_USER" => $openThread["user_id"]
                            ];
                        }
                        //Update request
                        updateRequest($apiHost, $requestId, $dataToUpdate);
                        //Update Parent Request
                        updateRequest($apiHost, $data["_parent"]["request_id"], $dataToUpdate);
                        //Close thread
                        closeThread($openThread["id"], $dataToUpdate['data'], $apiInstance);
                    } else {
                        $track[] = "CLOSED Route";
                    }
                }
            }
            if ($existOR1 == false && $data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") {
                $track[] = "No node_OR1";
                $dataReturn["PE_OR1_OPEN_THREAD_SELF_SERVICE"] = "YES";
            }
            if ($existOR2 == false && $data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "Yes") {
                $track[] = "No node_OR2";
                $dataReturn["PE_OR2_OPEN_THREAD_SELF_SERVICE"] = "YES";
            }
        }
    }
}
// New validation
if ($data["PE_DEAL_REQUIRE_FUNDING_CLOSE"] == "No") {
    $dataReturn["PE_OR1_OPEN_THREAD_SELF_SERVICE"] = "NO";
    $dataReturn["PE_OR1_OPEN_THREAD_USER"] = "NO";
    $dataReturn["PE_OR2_OPEN_THREAD_SELF_SERVICE"] = "NO";
    $dataReturn["PE_OR2_OPEN_THREAD_USER"] = "NO";
}
//
$dataReturn["tracking.".date("Y-m-d H:i:s")] = $track;
return $dataReturn;