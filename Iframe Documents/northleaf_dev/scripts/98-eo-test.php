<?php 
/************************
 * Dashboard - Get Data
 *
 * by Cinthia Romero 
 * modified by Telmo Chiri
 * modified by Elmer Orihuela
 ***********************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

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

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$dashboardCollectionID = getenv('DASHBOARD_COLLECTION_ID');
//Set initial Values
$dashboardCasesArray = array();
$currentUser = $data["CURRENT_USER"];
$currentUserLoged = $data["CURRENT_USER"];
$dashboardTotalRows = 0;
//Pages, Order and Search
$orderBy = $data["columns"][$data["order"][0]['column']]['data'];
switch($orderBy) {
    case 0:
        $orderBy = "CASE_NUMBER";
    break;
}
$orderType = $data["order"][0]['dir'];
$pageSize = $data['length'];
$pageNumber = $data["draw"];
$start = $data['start'];
$search = $data["search"]["value"];

//Get Dashboard Settings
$queryDashboardSettings = "SELECT data->>'$.DASHBOARD_PROCESS.id' AS DASHBOARD_PROCESS,
                                  data->>'$.DASHBOARD_VIEW_ALL_CASES_GROUP' AS DASHBOARD_VIEW_ALL_CASES_GROUP,
                                  data->>'$.DASHBOARD_SHOW_CASES_CRITERIA' AS DASHBOARD_SHOW_CASES_CRITERIA,
                                  data->>'$.DASHBOARD_CASE_VARIABLES_USER_REFERENCED' AS DASHBOARD_CASE_VARIABLES_USER_REFERENCED,
                                  data->>'$.DASHBOARD_TASKS_SETTINGS' AS DASHBOARD_TASKS_SETTINGS
                           FROM collection_" . $dashboardCollectionID . "
                           ORDER BY id DESC 
                           LIMIT 1";
$dashboardSettingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryDashboardSettings));
if (!empty($dashboardSettingsResponse[0]["DASHBOARD_TASKS_SETTINGS"])) {
    $groupsInUse = array(); //Array to save all the ids of groups referenced in all tasks of the process
    //Sort process tasks whith their corresponding configuration
    $tasksConfiguration = $dashboardSettingsResponse[0]["DASHBOARD_TASKS_SETTINGS"]; //Array with all process nodes configured
    $tasksConfiguration = json_decode($tasksConfiguration, true);
    $taskConfigurationSortedByGroup = array(); //Array which will contain all nodes sorted at the end
    foreach ($tasksConfiguration as $taskConfiguration) {
        $taskGroupId = $taskConfiguration["DASHBOARD_TASK_GROUP"];
        $taskOrder = $taskConfiguration["DASHBOARD_TASK_ORDER"];
        $taskConfigurationSortedByGroup[$taskGroupId][$taskOrder] = array(
            "TASK_NODE" => $taskConfiguration["DASHBOARD_TASK_NODE"],
            "TASK_NAME" => $taskConfiguration["DASHBOARD_TASK_NAME"],
            "TASK_CONDITIONS_EXIST" => $taskConfiguration["DASHBOARD_TASK_CONDITIONS_REQUIRED"],
            "TASK_CONDITIONS" => $taskConfiguration["DASHBOARD_TASK_CONDITIONS"]
        );
        if (in_array($taskGroupId, $groupsInUse) === false) {
            $groupsInUse[] = $taskGroupId;
        }
    }

    //Get all groups in use labels
    $groupsInUseWhere = implode(",", $groupsInUse);
    $queryGroupName = "SELECT id, 
                              name
                       FROM `groups`
                       WHERE id IN (" . $groupsInUseWhere . ")";
    $groupNameResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupName));
    $groupInUseLabels = array();
    if (!empty($groupNameResponse[0]["id"])) {
        foreach ($groupNameResponse as $group) {
            $groupInUseLabels[$group["id"]] = $group["name"];
        }
    }

    //Analyze Dashboard Criteria to Show Cases
    $queryCasesAdditionalWhere = "";
    //Check if user is Admin
    $userIsAdmin = "NO";
    $queryUserIsAdmin = "SELECT is_administrator
                         FROM users
                         WHERE id = '" . $currentUser . "'";
    $userIsAdminResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUserIsAdmin));
    if (!empty($userIsAdminResponse[0]["is_administrator"]) && $userIsAdminResponse[0]["is_administrator"] == 1) {
        $userIsAdmin = "YES";
    }
    if ($userIsAdmin == "NO") {
        //Check if current user is able to see all cases of current process
        $userCanSeeAllCases = "NO";
        if (!empty($dashboardSettingsResponse[0]["DASHBOARD_VIEW_ALL_CASES_GROUP"])) {
            $groupToSeeAllCases = json_decode($dashboardSettingsResponse[0]["DASHBOARD_VIEW_ALL_CASES_GROUP"], true);
            $queryBelongsToGroup = "SELECT member_id 
                                    FROM group_members 
                                    WHERE member_id = " . $currentUser . "
                                        AND group_id = " . $groupToSeeAllCases["id"];
            $userBelongsToGroupResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBelongsToGroup));
            if (!empty($userBelongsToGroupResponse[0]["member_id"])) {
                $userCanSeeAllCases = "YES";
            }
        }
        if ($userCanSeeAllCases == "NO") {
            $queryCasesAdditionalWhere = "";
            switch($dashboardSettingsResponse[0]["DASHBOARD_SHOW_CASES_CRITERIA"]) {
                case "Show only cases created by the user":
                    $queryCasesAdditionalWhere = " AND PR.user_id = " . $currentUser;
                    break;
                case "Show cases where the user has participated in":
                    $queryCasesAdditionalWhere = " AND PRT.user_id = " . $currentUser;
                    break;
                case "Show cases where the user has participated and is referenced in the case variables":
                    $queryCasesAdditionalWhere = " AND (PRT.user_id = " . $currentUser;
                    if (!empty($dashboardSettingsResponse[0]["DASHBOARD_CASE_VARIABLES_USER_REFERENCED"])) {
                        $caseVariablesReferencingUser = json_decode($dashboardSettingsResponse[0]["DASHBOARD_CASE_VARIABLES_USER_REFERENCED"], true);
                        foreach ($caseVariablesReferencingUser as $key=>$caseVariable) {
                            $queryCasesAdditionalWhere .= " OR PR.data->>'$." . $caseVariable["DASHBOARD_CASE_VARIABLE"] . "' = " . $currentUser;
                        }
                    }
                    $queryCasesAdditionalWhere .= ")";
                    break;
            } 
        }
    }
    
    //Get List of Cases in Progress
    $queryGetCasesList = "SELECT DISTINCT(PR.case_number) AS CASE_NUMBER,
                                 PR.case_title AS CASE_TITLE,
                                 PR.data->>'$.PE_TARGET_CLOSE_DATE' AS PE_TARGET_CLOSE_DATE
                          FROM process_requests AS PR
                          INNER JOIN process_request_tokens PRT
                              ON PRT.process_request_id = PR.id
                          WHERE PR.process_id = " . $dashboardSettingsResponse[0]["DASHBOARD_PROCESS"] . "
                              AND PR.case_number != '' 
                              AND PR.STATUS = 'ACTIVE'
                              AND (ISNULL(PR.parent_request_id) 
                                  OR PR.parent_request_id = '')";
    $queryGetCasesList .= $queryCasesAdditionalWhere;
    //Add search criteria in case the user has entered a value to search
    if (!empty($search)) {
        $search = escapeString($search);
        $queryGetCasesList .= " AND (CASE_NUMBER = '" . $search . "'
                                    OR CASE_TITLE = '" . $search . "')";
    }
    $casesListWithoutLimit = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));
    $queryGetCasesList .= " ORDER BY " . $orderBy . " " . $orderType . "
                            LIMIT " . $pageSize . " OFFSET " . $start;
    $casesList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));
    
    if (!empty($casesList[0]["CASE_NUMBER"])) {
        //Get total of cases
        $dashboardTotalRows = count($casesListWithoutLimit);
        //Get case completed and in progress threads
        $caseTasks = array();
        foreach ($casesList as $case) {
            $caseNumber = $case["CASE_NUMBER"];
            $queryCaseThreads = "SELECT PRT.id,
                                        PRT.element_id,
                                        PRT.process_request_id,
                                        PRT.version_id,
                                        PRT.user_id,
                                        PRT.status,
                                        IF(PRT.user_id = '' || ISNULL(PRT.user_id), 
                                            'Unassigned',
                                            (SELECT CONCAT (U.firstname, ' ', U.lastname)
                                             FROM users AS U
                                             WHERE id = PRT.user_id)) AS USER_FULL_NAME
                                 FROM process_request_tokens AS PRT
                                 INNER JOIN process_requests AS PR
                                     ON PR.id = PRT.process_request_id
                                 WHERE PR.case_number = '" . $caseNumber . "'
                                     AND PRT.element_type = 'task'
                                 ORDER BY PRT.id DESC";
            $casesThreadsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCaseThreads));
            if (!empty($casesThreadsResponse[0]["element_id"])) {
                $caseTasks[$caseNumber]["CASE_TITLE"] = $case["CASE_TITLE"];
                $caseTasks[$caseNumber]["PE_TARGET_CLOSE_DATE"] = $case["PE_TARGET_CLOSE_DATE"];
                $caseTasks[$caseNumber]["SHOW_ICON_EDIT_TARGET"] = true;
                //Set array with node id as keys
                $nodesAlreadyConsidered = array();
                foreach ($casesThreadsResponse as $thread) {
                    $nodeId = $thread["element_id"];
                    if ($thread["status"] == 'ACTIVE' && ($nodeId == 'DT01' || $nodeId == 'DT02')) { $caseTasks[$caseNumber]["SHOW_ICON_EDIT_TARGET"] = false; }
                    if (in_array($nodeId, $nodesAlreadyConsidered) === false) {
                        $caseTasks[$caseNumber]["TASKS"][$nodeId] = array(
                            "STATUS" => $thread["status"],
                            "CURRENT_USER" => $thread["USER_FULL_NAME"],
                            "REQUEST_ID" => $thread["process_request_id"],
                            "NODE_VERSION_ID" => $thread["version_id"],
                            "REQUEST_TASK_ID" => $thread["id"],
                            "CURRENT_USER_ID" => $thread["user_id"],
                        );
                        $nodesAlreadyConsidered[] = $nodeId;
                    }
                }
            }
        }
        
        //Join case tasks with group tasks and create HTML to show in dashboard
        foreach ($caseTasks as $caseNumber => $case) {
            //Set initial html with headers per case
            $groupTaskHTML = "<table class='lineHeightTable'>";
            $groupTaskHTML .= "<tr>";
            $groupTaskHTML .= "<th colspan='2' class='projectTitle' onclick='openCase()'><b>" . $case["CASE_TITLE"] . "</b></th>";
            $groupTaskHTML .= "<th colspan='4'>";
            $groupTaskHTML .= "<table class='tasksTable'>";
            $groupTaskHTML .= "<tr>";
            $groupTaskHTML .= "<th class='completeStyle' width='5%'><b>Complete</b></th>";
            $groupTaskHTML .= "<th class='inProgressStyle' width='5%'><b>Actionable</b></th>";
            $groupTaskHTML .= "<th class='pendingStyle' style='text-align: center' width='5%'><b>Not Yet<br>Actionable</b></th>";
            $groupTaskHTML .= "<th class='neverRoutedStyle' style='text-align: center' width='5%'><b>Not Currently<br>Required</b></th>";
            $groupTaskHTML .= "</tr>";
            $groupTaskHTML .= "</table>";
            $groupTaskHTML .= "</th>";
            $groupTaskHTML .= "</tr>";
            foreach ($taskConfigurationSortedByGroup as $groupId => $taskGroup) {
                //Set initial counters and html of tasks per status
                $countPending = 0;
                $countInProgress = 0;
                $countCompleted = 0;
                $countNeverRouted = 0;
                $completedTasksHTML = "";
                $inProgressTasksHTML = "";
                $pendingTasksHTML = "";
                $neverRoutedTasksHTML = "";
                //Add Group Title
                $groupTaskHTML .= "<tr>";
                $groupTaskHTML .= "<td class='groupLabelStyle' width='10%'><b>" . $groupInUseLabels[$groupId] . "</b></td>";
                $groupTaskHTML .= "<td width='70%'>";
                $groupTaskHTML .= "<table class='tasksTable'>";
                $groupTaskHTML .= "<tr>";
                foreach ($taskGroup as $taskOrder => $task) {
                    $nodeToEvaluate = $task["TASK_NODE"];
                    if (array_key_exists($nodeToEvaluate, $case["TASKS"])) {
                        $threadInformation = $case["TASKS"][$nodeToEvaluate];
                        $currentUser = $threadInformation["CURRENT_USER"];
                        $requestId = $threadInformation["REQUEST_ID"];
                        $nodeversionID = $threadInformation["NODE_VERSION_ID"];
                        $requestTaskId = $threadInformation["REQUEST_TASK_ID"];
                        //Check if task is in progress
                        if ($case["TASKS"][$nodeToEvaluate]["STATUS"] == "ACTIVE") {
                            $countInProgress++;
                            if ($case["TASKS"][$nodeToEvaluate]["CURRENT_USER_ID"] == $currentUserLoged) {
                                $inProgressTasksHTML .= "<td class='inProgressStyle tdTasks' onclick='showCurrentUserTasks(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                            } else {
                                $inProgressTasksHTML .= "<td class='inProgressStyle tdTasks' onclick='showThreadDynaform(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                            }
                        } else {
                            $countCompleted++;
                            $completedTasksHTML .= "<td class='completeStyle tdTasks' onclick='showThreadDynaform(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                        }
                    } else {
                        //Check task conditions to define if task will be pending or the case will never go to the task in evaluation
                        $taskConditionExist = $task["TASK_CONDITIONS_EXIST"];
                        if ($taskConditionExist) {
                            $taskConditions = $task["TASK_CONDITIONS"];
                            $validationString = "";
                            $variablesToCheckInQuery = array();
                            foreach ($taskConditions as $taskCondition) {
                                //Set condition elements
                                $variableTobeEvaluated = trim($taskCondition["DASHBOARD_CONDITION_VARIABLE"]);
                                $conditionToEvaluateVariable = $taskCondition["DASHBOARD_CONDITION_EVALUATE"];
                                $valueToBeEvaluated = trim($taskCondition["DASHBOARD_CONDITION_VALUE"]); 
                                $nextConditionExist = $taskCondition["DASHBOARD_CONDITION_NEXT_CONDITION"]; 
                                //Form condition
                                $validationString .= " '" . $variableTobeEvaluated . "' " . $conditionToEvaluateVariable . " '" . $valueToBeEvaluated . "'";
                                if (!empty($nextConditionExist)) {
                                    $validationString .= " " . $nextConditionExist;
                                }
                                //Add variable to check in query
                                $variablesToCheckInQuery[] = $variableTobeEvaluated;
                            }
                            if (count($variablesToCheckInQuery) > 0) {
                                //Form query to get variables values
                                $queryCheckVariablesInData = "SELECT ";
                                foreach($variablesToCheckInQuery as $variableKey=>$variable) {
                                    if ($variableKey == 0) {
                                        $queryCheckVariablesInData .= "data->>'$." . $variable . "' AS " . $variable;
                                    } else {
                                        $queryCheckVariablesInData .= ", data->>'$." . $variable . "' AS " . $variable;
                                    }
                                }
                                $queryCheckVariablesInData .= " FROM process_requests 
                                                               WHERE case_number = " . $caseNumber ."
                                                               ORDER BY id DESC
                                                               LIMIT 1";
                                $variablesResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckVariablesInData));
                                if (empty($variablesResponse["error"])) {
                                    foreach($variablesToCheckInQuery as $variableKey=>$variable) {
                                        //Check if variable has value in data. If it is null means that it probably will be set in later tasks so the current task will be pending
                                        if ($variablesResponse[0][$variable] == null) {
                                            $validationString = "";
                                            break;
                                        } else {
                                            //Replace values in condition
                                            $validationString = str_replace($variable, $variablesResponse[0][$variable], $validationString);
                                        }
                                    }
                                }
                            }
                            //If no validations are needed means that the variables don't exist by now so we assume that it is pending by now
                            if ($validationString == "") {
                                $countPending++;
                                $pendingTasksHTML .= "<td class='pendingStyle tdTasks'>" . $task["TASK_NAME"] . "</td>";
                            } else {
                                //If the validations return true means that the task will be actionable in a later stage otherwise the task will be never actionable
                                if (eval("return " . $validationString . ";")) {
                                    $countPending++;
                                    $pendingTasksHTML .= "<td class='pendingStyle tdTasks'>" . $task["TASK_NAME"] . "</td>";
                                } else {
                                    $countNeverRouted++;
                                    $neverRoutedTasksHTML .= "<td class='neverRoutedStyle tdTasks'>" . $task["TASK_NAME"] . "</td>";
                                }
                            }
                        } else {
                            $countPending++;
                            //If no conditions are needed means that the task will be actionable in a later stage that is why it is pending by now
                            $pendingTasksHTML .= "<td class='pendingStyle tdTasks'>" . $task["TASK_NAME"] . "</td>";
                        }
                    }
                }
                $groupTaskHTML .= $completedTasksHTML;
                $groupTaskHTML .= $inProgressTasksHTML;
                $groupTaskHTML .= $pendingTasksHTML;
                $groupTaskHTML .= $neverRoutedTasksHTML;
                $groupTaskHTML .= "</tr>";
                $groupTaskHTML .= "</table>";
                $groupTaskHTML .= "</td>";
                //Add counters
                $totalTasks = $countCompleted + $countInProgress + $countPending + $countNeverRouted;
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countCompleted . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countInProgress . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countPending . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countNeverRouted . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "</tr>";
            }
            $groupTaskHTML .= "</table>";
            $dashboardCasesArray[] = array(
                $caseNumber,
                $groupTaskHTML,
                $case["SHOW_ICON_EDIT_TARGET"],
                $case["PE_TARGET_CLOSE_DATE"]
            );
        }
    }
}

return array(
            "draw" => $data['draw'],
            "recordsFiltered" => $dashboardTotalRows,
            "recordsTotal" => $dashboardTotalRows,
            "data" => $dashboardCasesArray
        );