<?php 
/********************************
 * Bulk Reassignment - Get Data
 *
 * by Cinthia Romero
 * Modified by Telmo Chiri
 *******************************/
require_once("/Northleaf_PHP_Library.php");

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set initial Values
$bulkReassignmentCasesArray = array();
$currentUser = $data["CURRENT_USER"];
$userCanSeeAllCases = empty($data["USER_CAN_SEE_ALL_CASES"]) ? "NO" : $data["USER_CAN_SEE_ALL_CASES"];
$filteredByProcess = $data["FILTER_PROCESS"];
$filteredByUser = $data["FILTER_USER"];
$bulkReassignmentTotalRows = 0;
$bulkReassignmentCasesList = array();
$bulkReassignmentSettingsCollectionID = getenv('BULK_REASSIGNMENT_SETTINGS_COLLECTION_ID');

function checkProcessesPermissions($currentUser) {
    global $bulkReassignmentSettingsResponse, $apiUrl;
    // check if user is super admin
    $query1 = "SELECT is_administrator FROM users WHERE id = $currentUser LIMIT 1";
    $response1 = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query1));
    $isAdmin = isset($response1[0]['is_administrator']) ? (bool)$response1[0]['is_administrator'] : false;

    $processIds = [];

    if (!$isAdmin) {
        //check settings, if user has processes assigned
        $permissionsSettings = json_decode($bulkReassignmentSettingsResponse[0]['PERMISSIONS_SETTINGS'], true);
        foreach ($permissionsSettings as $processSettings) {
            // check if user is assigned to the user list
            if (in_array($currentUser, $processSettings['USER_LIST'])) {
                $processIds[] = $processSettings['PROCESS'];
            } else { // check if user is assigned to a group
                foreach ($processSettings['GROUP_LIST'] as $groupId) {
                    $query2 = "SELECT member_id FROM group_members WHERE group_id = $groupId";
                    $response2 = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query2));
                    if (in_array($currentUser, array_column($response2, 'member_id'))) {
                        $processIds[] = $processSettings['PROCESS'];
                    }   
                }                
            }
        }
    } else {
        $processIds = ['all'];
    }
    
    return implode(", ", $processIds);
}

//Pages, Order and Search
$orderBy = $data["columns"][$data["order"][0]['column']]['data'];
switch($orderBy) {
    case 1:
        $orderBy = "CASE_NUMBER";
        break;
    case 2:
        $orderBy = "CASE_TITLE";
        break;
    case 3:
        $orderBy = "TASK_TITLE";
        break;
    case 4:
        $orderBy = "USER_FULL_NAME";
        break;
}
$orderType = $data["order"][0]['dir'];
$pageSize = $data['length'];
$pageNumber = $data["draw"];
$start = $data['start'];
$search = $data["search"]["value"];

//Get Bulk Reassign Settings
$queryBulkReassignmentSettings = "SELECT data->>'$.BRS_ALL_CASES_GROUP.id' AS BRS_ALL_CASES_GROUP,
                                         data->>'$.BRS_TASKS_TO_EXCLUDE' AS BRS_TASKS_TO_EXCLUDE,
                                         data->>'$.PERMISSIONS_SETTINGS' AS PERMISSIONS_SETTINGS
                                    FROM collection_" . $bulkReassignmentSettingsCollectionID . "
                                    ORDER BY id DESC 
                                    LIMIT 1";
$bulkReassignmentSettingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBulkReassignmentSettings));
//return [$bulkReassignmentSettingsCollectionID, $bulkReassignmentSettingsResponse];
$processesCanAccess = checkProcessesPermissions($currentUser);

//Analyze Dashboard Criteria to Show Cases
$queryCasesAdditionalWhere = "";
//Check if user is admin or belongs to group configured in settings
if ($userCanSeeAllCases == "NO") {
    $queryCasesAdditionalWhere = " AND PRT.user_id = " . $currentUser;
} else {
    //Check if the user wants to filter by user
    if (!empty($filteredByUser)) {
        $queryCasesAdditionalWhere = " AND PRT.user_id = " . $filteredByUser;
    }
}

//Check if the user wants to filter by process
if (!empty($filteredByProcess)) {
    //Get XML of process selected to check if it has pm blocks associated
    $queryGetProcessSelectedXML = "SELECT bpmn
                                   FROM processes
                                   WHERE id = " . $filteredByProcess;
    $processBPMNResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetProcessSelectedXML));
    if(!empty($processBPMNResponse[0]["bpmn"])) {
        //Divide XML by phrase processmaker-pm-block
        $processPMBlocks = explode("processmaker-pm-block", $processBPMNResponse[0]["bpmn"]);
        //Check if there is at least one pm block in main process XML
        if (count($processPMBlocks) > 1) {
            $pmBlocksKeys = array();
            foreach ($processPMBlocks as $pmBlock) {
                //Check if first character is hyphen - to recognize pm block key
                if ($pmBlock[0] == "-") {
                    //Divide string by hyphen -
                    $pmBlocksKeys[] = "'pm-block-" . explode("-", $pmBlock)[1] . "'";
                }
            }
            //Join all keys to search their corresponding pm block ids
            $pmBlocksKeys = implode(",", $pmBlocksKeys);
            //Query to obtain pm blocks ids
            $queryPMBlockIDs = "SELECT editing_process_id
                                FROM pm_blocks
                                WHERE process_package_key IN (" . $pmBlocksKeys . ")";
            $pmBlocksIdsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryPMBlockIDs));
            if (!empty($pmBlocksIdsResponse[0]["editing_process_id"])) {
                //Join all pm block ids to use them in main query
                $pmBlockIds = array($filteredByProcess);
                foreach ($pmBlocksIdsResponse as $pmBlockID) {
                    $pmBlockIds[] = $pmBlockID["editing_process_id"];
                }
                $pmBlockIds = implode(",", $pmBlockIds);
                //$queryCasesAdditionalWhere .= " AND PR.process_id IN (" . $pmBlockIds . ")"; 
            }
        } else {
            //$queryCasesAdditionalWhere .= " AND PR.process_id = " . $filteredByProcess; 
        }   
    } else {
        //$queryCasesAdditionalWhere .= " AND PR.process_id = " . $filteredByProcess; 
    }

    if(isset($pmBlockIds) && count(explode(",", $pmBlockIds)) > 1) {
        $queryCasesAdditionalWhere .= " AND PR.process_id IN (" . $pmBlockIds . ")";
    } else {
        $queryCasesAdditionalWhere .= (($processesCanAccess != 'all' && in_array($filteredByProcess, explode(",", $processesCanAccess))) || $processesCanAccess == 'all') 
        ? " AND PR.process_id = " . $filteredByProcess
        : " AND PR.process_id = -1";  
    } 

} else {
    if ($processesCanAccess != 'all') {
        $queryCasesAdditionalWhere .= " AND PR.process_id IN (" . $processesCanAccess . ")"; 
    }    
}

//Add search criteria in case the user has entered a value to search
if (!empty($search)) {
    $search = escapeString($search);
    $queryCasesAdditionalWhere .= " AND (CASE_NUMBER LIKE '%" . $search . "%'
                                        OR CASE_TITLE LIKE '%" . $search . "%'
                                        OR TASK_TITLE LIKE '%" . $search . "%'
                                        OR USER_FULL_NAME LIKE '%" . $search . "%')";
}

$queryExcludeTasks = "";
if (!empty($bulkReassignmentSettingsResponse[0]["BRS_ALL_CASES_GROUP"])) {
   $taskToExclude = json_decode($bulkReassignmentSettingsResponse[0]["BRS_TASKS_TO_EXCLUDE"]); 
   if (count($taskToExclude) > 0) {
        $aNodes = [];
        foreach($taskToExclude as $task) {
            if ($task->NODE_ID != '') {
                $aNodes[] = $task->NODE_ID;
            }
        }
        if (count($aNodes) > 0) {
            $queryExcludeTasks .= " AND PRT.element_id NOT IN ('" . implode("','", $aNodes). "') ";
        }
   }
}
//Get List of Cases in Progress
$queryGetCasesList = "SELECT CONCAT(PRT.id, '_', PRT.element_id, '_', PRT.user_id) AS DELEGATION_ID,
                             PR.case_number AS CASE_NUMBER,
                             PR.case_title AS CASE_TITLE,
                             PRT.element_name AS TASK_TITLE,
                             (SELECT CONCAT (U.firstname, ' ', U.lastname)
                              FROM users AS U
                              WHERE id = PRT.user_id) AS USER_FULL_NAME
                      FROM process_request_tokens PRT
                      INNER JOIN process_requests AS PR
                          ON PR.id = PRT.process_request_id
                      WHERE PR.case_number != '' 
                          AND PR.STATUS = 'ACTIVE'
                          AND PRT.STATUS = 'ACTIVE'
                          AND PRT.element_type = 'task'
                          AND PRT.user_id != ''";
$queryGetCasesList .= $queryExcludeTasks;
$queryGetCasesList .= $queryCasesAdditionalWhere;
$casesListWithoutLimit = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));

//Add pagination and order
$queryGetCasesList .= " ORDER BY " . $orderBy . " " . $orderType . "
                        LIMIT " . $pageSize . " OFFSET " . $start;
$casesList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));
if (!empty($casesList[0]["CASE_NUMBER"])) {
    //Get total of cases
    $bulkReassignmentTotalRows = count($casesListWithoutLimit);
    foreach ($casesList as $case) {
        $bulkReassignmentCasesList[] = array(
            $case["DELEGATION_ID"],
            $case["CASE_NUMBER"],
            $case["CASE_TITLE"],
            $case["TASK_TITLE"],
            $case["USER_FULL_NAME"]
        );  
    }
}

return array(
            "draw" => $data['draw'],
            "recordsFiltered" => $bulkReassignmentTotalRows,
            "recordsTotal" => $bulkReassignmentTotalRows,
            "data" => $bulkReassignmentCasesList
        );

/**
 * Escape String
 *
 * @param string $stringToEvaluate
 * @return string $escapedString
 *
 * by Cinthia Romero
 */
function escapeString($stringToEvaluate)
{
    $escapedString = str_replace('"', "\"", $stringToEvaluate);
    $escapedString = str_replace("'", "\'", $escapedString);
    $escapedString = str_replace("\\", "\\\\", $escapedString);
    return $escapedString;
}