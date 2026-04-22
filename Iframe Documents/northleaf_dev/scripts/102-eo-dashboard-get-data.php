<?php
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// Escape String using addslashes
function escapeString($stringToEvaluate)
{
    return addslashes($stringToEvaluate);
}

// Get Global Variables
$start_time = microtime(true);
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$dashboardCollectionID = getenv('DASHBOARD_COLLECTION_ID');

// Initialize Values
$dashboardCasesArray = [];
$currentUser = $data["CURRENT_USER"];
$currentUserLoged = $data["CURRENT_USER"];
$dashboardTotalRows = 0;

// Pages, Order, and Search
$orderBy = $data["columns"][$data["order"][0]['column']]['data'];
$orderBy = ($orderBy == 0) ? "CASE_NUMBER" : $orderBy;
$orderType = $data["order"][0]['dir'];
$pageSize = $data['length'];
$pageNumber = $data["draw"];
$start = $data['start'];
$search = $data["search"]["value"];

// Get Dashboard Settings
$queryDashboardSettings = "SELECT 
    data->>'$.DASHBOARD_PROCESS.id' AS DASHBOARD_PROCESS,
    data->>'$.DASHBOARD_VIEW_ALL_CASES_GROUP' AS DASHBOARD_VIEW_ALL_CASES_GROUP,
    data->>'$.DASHBOARD_SHOW_CASES_CRITERIA' AS DASHBOARD_SHOW_CASES_CRITERIA,
    data->>'$.DASHBOARD_CASE_VARIABLES_USER_REFERENCED' AS DASHBOARD_CASE_VARIABLES_USER_REFERENCED,
    data->>'$.DASHBOARD_TASKS_SETTINGS' AS DASHBOARD_TASKS_SETTINGS
FROM collection_" . $dashboardCollectionID . " 
ORDER BY id DESC 
LIMIT 1";
$dashboardSettingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryDashboardSettings));

if (!empty($dashboardSettingsResponse[0]["DASHBOARD_TASKS_SETTINGS"])) {
    $groupsInUse = [];
    $tasksConfiguration = json_decode($dashboardSettingsResponse[0]["DASHBOARD_TASKS_SETTINGS"], true);
    $taskConfigurationSortedByGroup = [];

    foreach ($tasksConfiguration as $taskConfiguration) {
        $taskGroupId = $taskConfiguration["DASHBOARD_TASK_GROUP"];
        $taskOrder = $taskConfiguration["DASHBOARD_TASK_ORDER"];
        $taskConfigurationSortedByGroup[$taskGroupId][$taskOrder] = [
            "TASK_NODE" => $taskConfiguration["DASHBOARD_TASK_NODE"],
            "TASK_NAME" => $taskConfiguration["DASHBOARD_TASK_NAME"],
            "TASK_CONDITIONS_EXIST" => $taskConfiguration["DASHBOARD_TASK_CONDITIONS_REQUIRED"],
            "TASK_CONDITIONS" => $taskConfiguration["DASHBOARD_TASK_CONDITIONS"]
        ];
        if (!in_array($taskGroupId, $groupsInUse)) {
            $groupsInUse[] = $taskGroupId;
        }
    }

    $groupsInUseWhere = implode(",", $groupsInUse);
    $queryGroupName = "SELECT id, name FROM `groups` WHERE id IN (" . $groupsInUseWhere . ")";
    $groupNameResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupName));
    $groupInUseLabels = [];

    foreach ($groupNameResponse as $group) {
        $groupInUseLabels[$group["id"]] = $group["name"];
    }

    // Analyze Dashboard Criteria to Show Cases
    $queryCasesAdditionalWhere = "";
    $queryUserIsAdmin = "SELECT is_administrator FROM users WHERE id = '" . $currentUser . "'";
    $userIsAdminResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUserIsAdmin));
    $userIsAdmin = !empty($userIsAdminResponse[0]["is_administrator"]) && $userIsAdminResponse[0]["is_administrator"] == 1 ? "YES" : "NO";

    if ($userIsAdmin == "NO") {
        $userCanSeeAllCases = "NO";
        if (!empty($dashboardSettingsResponse[0]["DASHBOARD_VIEW_ALL_CASES_GROUP"])) {
            $groupToSeeAllCases = json_decode($dashboardSettingsResponse[0]["DASHBOARD_VIEW_ALL_CASES_GROUP"], true);
            $queryBelongsToGroup = "SELECT member_id FROM group_members WHERE member_id = " . $currentUser . " AND group_id = " . $groupToSeeAllCases["id"];
            $userBelongsToGroupResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBelongsToGroup));
            $userCanSeeAllCases = !empty($userBelongsToGroupResponse[0]["member_id"]) ? "YES" : "NO";
        }
        if ($userCanSeeAllCases == "NO") {
            switch ($dashboardSettingsResponse[0]["DASHBOARD_SHOW_CASES_CRITERIA"]) {
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
                        foreach ($caseVariablesReferencingUser as $caseVariable) {
                            $queryCasesAdditionalWhere .= " OR PR.data->>'$." . $caseVariable["DASHBOARD_CASE_VARIABLE"] . "' = " . $currentUser;
                        }
                    }
                    $queryCasesAdditionalWhere .= ")";
                    break;
            }
        }
    }

    // Get List of Cases in Progress
    $queryGetCasesList = "SELECT DISTINCT(PR.case_number) AS CASE_NUMBER,
                                 PR.case_title AS CASE_TITLE,
                                 PR.data->>'$.PE_TARGET_CLOSE_DATE' AS PE_TARGET_CLOSE_DATE
                          FROM process_requests AS PR
                          INNER JOIN process_request_tokens PRT ON PRT.process_request_id = PR.id
                          WHERE PR.process_id = " . $dashboardSettingsResponse[0]["DASHBOARD_PROCESS"] . "
                              AND PR.case_number != '' 
                              AND PR.STATUS = 'ACTIVE'
                              AND (ISNULL(PR.parent_request_id) OR PR.parent_request_id = '')";
    $queryGetCasesList .= $queryCasesAdditionalWhere;

    if (!empty($search)) {
        $search = escapeString($search);
        $queryGetCasesList .= " AND (CASE_NUMBER = '" . $search . "' OR CASE_TITLE = '" . $search . "')";
    }

    $casesListWithoutLimit = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));
    $queryGetCasesList .= " ORDER BY " . $orderBy . " " . $orderType . " LIMIT " . $pageSize . " OFFSET " . $start;
    $casesList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));

    if (!empty($casesList[0]["CASE_NUMBER"])) {
        $dashboardTotalRows = count($casesListWithoutLimit);
        $caseTasks = [];

        foreach ($casesList as $case) {
            $caseNumber = $case["CASE_NUMBER"];
            $queryCaseThreads = "SELECT PRT.id,
                                        PRT.element_id,
                                        PRT.process_request_id,
                                        PRT.version_id,
                                        PRT.user_id,
                                        PRT.status,
                                        IF(PRT.user_id = '' || ISNULL(PRT.user_id), 'Unassigned', 
                                           (SELECT CONCAT (U.firstname, ' ', U.lastname) FROM users AS U WHERE id = PRT.user_id)) AS USER_FULL_NAME
                                 FROM process_request_tokens AS PRT
                                 INNER JOIN process_requests AS PR ON PR.id = PRT.process_request_id
                                 WHERE PR.case_number = '" . $caseNumber . "'
                                     AND PRT.element_type = 'task'
                                 ORDER BY PRT.id DESC";
            $casesThreadsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCaseThreads));

            if (!empty($casesThreadsResponse[0]["element_id"])) {
                $caseTasks[$caseNumber] = [
                    "CASE_TITLE" => $case["CASE_TITLE"],
                    "PE_TARGET_CLOSE_DATE" => $case["PE_TARGET_CLOSE_DATE"],
                    "SHOW_ICON_EDIT_TARGET" => true,
                    "TASKS" => []
                ];
                $nodesAlreadyConsidered = [];

                foreach ($casesThreadsResponse as $thread) {
                    $nodeId = $thread["element_id"];
                    if ($thread["status"] == 'ACTIVE' && ($nodeId == 'DT01' || $nodeId == 'DT02')) {
                        $caseTasks[$caseNumber]["SHOW_ICON_EDIT_TARGET"] = false;
                    }
                    if (!in_array($nodeId, $nodesAlreadyConsidered)) {
                        $caseTasks[$caseNumber]["TASKS"][$nodeId] = [
                            "STATUS" => $thread["status"],
                            "CURRENT_USER" => $thread["USER_FULL_NAME"],
                            "REQUEST_ID" => $thread["process_request_id"],
                            "NODE_VERSION_ID" => $thread["version_id"],
                            "REQUEST_TASK_ID" => $thread["id"],
                            "CURRENT_USER_ID" => $thread["user_id"]
                        ];
                        $nodesAlreadyConsidered[] = $nodeId;
                    }
                }
            }
        }

        foreach ($caseTasks as $caseNumber => $case) {
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
                $countPending = 0;
                $countInProgress = 0;
                $countCompleted = 0;
                $countNeverRouted = 0;
                $completedTasksHTML = "";
                $inProgressTasksHTML = "";
                $pendingTasksHTML = "";
                $neverRoutedTasksHTML = "";

                $groupTaskHTML .= "<tr>";
                $groupTaskHTML .= "<td class='groupLabelStyle' width='10%'><b>" . $groupInUseLabels[$groupId] . "</b></td>";
                $groupTaskHTML .= "<td width='70%'>";
                $groupTaskHTML .= "<table class='tasksTable'>";
                $groupTaskHTML .= "<tr>";

                foreach ($taskGroup as $task) {
                    $nodeToEvaluate = $task["TASK_NODE"];
                    if (isset($case["TASKS"][$nodeToEvaluate])) {
                        $threadInformation = $case["TASKS"][$nodeToEvaluate];
                        $currentUser = $threadInformation["CURRENT_USER"];
                        $requestId = $threadInformation["REQUEST_ID"];
                        $nodeversionID = $threadInformation["NODE_VERSION_ID"];
                        $requestTaskId = $threadInformation["REQUEST_TASK_ID"];

                        if ($threadInformation["STATUS"] == "ACTIVE") {
                            $countInProgress++;
                            $inProgressTasksHTML .= $threadInformation["CURRENT_USER_ID"] == $currentUserLoged ?
                                "<td class='inProgressStyle tdTasks' onclick='showCurrentUserTasks(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>" :
                                "<td class='inProgressStyle tdTasks' onclick='showThreadDynaform(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                        } else {
                            $countCompleted++;
                            $completedTasksHTML .= "<td class='completeStyle tdTasks' onclick='showThreadDynaform(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                        }
                    } else {
                        $taskConditionExist = $task["TASK_CONDITIONS_EXIST"];
                        $validationString = "";
                        $variablesToCheckInQuery = [];

                        if ($taskConditionExist) {
                            $taskConditions = $task["TASK_CONDITIONS"];

                            foreach ($taskConditions as $taskCondition) {
                                $variableTobeEvaluated = trim($taskCondition["DASHBOARD_CONDITION_VARIABLE"]);
                                $conditionToEvaluateVariable = $taskCondition["DASHBOARD_CONDITION_EVALUATE"];
                                $valueToBeEvaluated = trim($taskCondition["DASHBOARD_CONDITION_VALUE"]);
                                $nextConditionExist = $taskCondition["DASHBOARD_CONDITION_NEXT_CONDITION"];
                                $validationString .= " '" . $variableTobeEvaluated . "' " . $conditionToEvaluateVariable . " '" . $valueToBeEvaluated . "'";
                                if (!empty($nextConditionExist)) {
                                    $validationString .= " " . $nextConditionExist;
                                }
                                $variablesToCheckInQuery[] = $variableTobeEvaluated;
                            }

                            if (count($variablesToCheckInQuery) > 0) {
                                $queryCheckVariablesInData = "SELECT ";
                                foreach ($variablesToCheckInQuery as $variableKey => $variable) {
                                    $queryCheckVariablesInData .= ($variableKey == 0 ? "" : ", ") . "data->>'$." . $variable . "' AS " . $variable;
                                }
                                $queryCheckVariablesInData .= " FROM process_requests WHERE case_number = " . $caseNumber . " ORDER BY id DESC LIMIT 1";
                                $variablesResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCheckVariablesInData));

                                if (empty($variablesResponse["error"])) {
                                    foreach ($variablesToCheckInQuery as $variable) {
                                        if ($variablesResponse[0][$variable] == null) {
                                            $validationString = "";
                                            break;
                                        } else {
                                            $validationString = str_replace($variable, $variablesResponse[0][$variable], $validationString);
                                        }
                                    }
                                }
                            }

                            if ($validationString == "") {
                                $countPending++;
                                $pendingTasksHTML .= "<td class='pendingStyle tdTasks'>" . $task["TASK_NAME"] . "</td>";
                            } else {
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
                            $pendingTasksHTML .= "<td class='pendingStyle tdTasks'>" . $task["TASK_NAME"] . "</td>";
                        }
                    }
                }

                $groupTaskHTML .= $completedTasksHTML . $inProgressTasksHTML . $pendingTasksHTML . $neverRoutedTasksHTML;
                $groupTaskHTML .= "</tr>";
                $groupTaskHTML .= "</table>";
                $groupTaskHTML .= "</td>";

                $totalTasks = $countCompleted + $countInProgress + $countPending + $countNeverRouted;
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countCompleted . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countInProgress . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countPending . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "<td align='center' width='5%'>" . $countNeverRouted . "/" . $totalTasks . "</td>";
                $groupTaskHTML .= "</tr>";
            }

            $groupTaskHTML .= "</table>";
            $dashboardCasesArray[] = [$caseNumber, $groupTaskHTML, $case["SHOW_ICON_EDIT_TARGET"], $case["PE_TARGET_CLOSE_DATE"]];
        }
    }
}
$end_time = microtime(true);
$total_time_seconds = $end_time - $start_time;
$minutes = floor($total_time_seconds / 60);
$seconds = floor($total_time_seconds) % 60;
$milliseconds = round(($total_time_seconds - floor($total_time_seconds)) * 1000, 2);
$responseMessage = "Total response time: " . $minutes . " minutes, " . $seconds . " seconds, and " . $milliseconds . " milliseconds";
return [
    "responseMessage" => $responseMessage,
    "draw" => $data['draw'],
    "recordsFiltered" => $dashboardTotalRows,
    "recordsTotal" => $dashboardTotalRows,
    "data" => $dashboardCasesArray
];
?>