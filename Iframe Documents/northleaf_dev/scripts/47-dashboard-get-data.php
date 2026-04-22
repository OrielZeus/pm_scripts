<?php 
/************************
 * Dashboard - Get Data
 *
 * by Cinthia Romero 
 * modified by Telmo Chiri
 * modified by Elmer Orihuela
 ***********************/
require_once("/Northleaf_PHP_Library.php");

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$dashboardCollectionID = getenv('DASHBOARD_COLLECTION_ID');
//Set initial Values
$dashboardCasesArray = array();
$currentUser = $data["CURRENT_USER"];
$currentUserLogged = $data["CURRENT_USER"];
$tasksAbleToBeDuplicated = array();
$tasksWithClone = array();
$tasksInLoop = array();
$selfServiceTasks = array();
$dashboardTotalRows = 0;
$tasksConditionsVariables = array(); //Case variables to get from process_requests
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
                                  data->>'$.DASHBOARD_EDIT_TARGET_CLOSE' AS DASHBOARD_EDIT_TARGET_CLOSE,
                                  data->>'$.DASHBOARD_TASKS_SETTINGS' AS DASHBOARD_TASKS_SETTINGS,
                                  data->>'$.DASHBOARD_NODE_INITIAL' AS DASHBOARD_NODE_INITIAL,
                                  data->>'$.DASHBOARD_GROUP_CAN_REAPPROVE' AS DASHBOARD_GROUP_CAN_REAPPROVE,
                                  data->>'$.DASHBOARD_USERS_CAN_REAPPROVE' AS DASHBOARD_USERS_CAN_REAPPROVE
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
    //Get the list of configured groups that can edit the Target Close
    $groupsEditTargetClose = json_decode($dashboardSettingsResponse[0]["DASHBOARD_EDIT_TARGET_CLOSE"], true);
    foreach ($tasksConfiguration as $taskConfiguration) {
        $taskGroupId = $taskConfiguration["DASHBOARD_TASK_GROUP"];
        $taskOrder = $taskConfiguration["DASHBOARD_TASK_ORDER"];
        $taskConfigurationSortedByGroup[$taskGroupId][$taskOrder] = array(
            "TASK_NODE" => $taskConfiguration["DASHBOARD_TASK_NODE"],
            "TASK_NAME" => $taskConfiguration["DASHBOARD_TASK_NAME"],
            "TASK_CONDITIONS_EXIST" => $taskConfiguration["DASHBOARD_TASK_CONDITIONS_REQUIRED"]
        );
        
        //Check if task has conditions to occure
        $validationString = "";
        if ($taskConfiguration["DASHBOARD_TASK_CONDITIONS_REQUIRED"] === true) {
            foreach ($taskConfiguration["DASHBOARD_TASK_CONDITIONS"] as $taskCondition) {
                $variableTobeEvaluated = trim($taskCondition["DASHBOARD_CONDITION_VARIABLE"]);
                //Check if variable is already considered in array of variables to obtain in query
                $variableToAddToQuery = "data->>'$." . $variableTobeEvaluated . "' AS " . $variableTobeEvaluated;
                if (array_search($variableToAddToQuery, $tasksConditionsVariables) === false) {
                    $tasksConditionsVariables[] = $variableToAddToQuery;
                }
                //Set condition elements
                $conditionToEvaluateVariable = $taskCondition["DASHBOARD_CONDITION_EVALUATE"];
                $valueToBeEvaluated = trim($taskCondition["DASHBOARD_CONDITION_VALUE"]); 
                $nextConditionExist = $taskCondition["DASHBOARD_CONDITION_NEXT_CONDITION"]; 
                //Form condition
                $validationString .= " '{" . $variableTobeEvaluated . "}' " . $conditionToEvaluateVariable . " '" . $valueToBeEvaluated . "'";
                if (!empty($nextConditionExist)) {
                    $validationString .= " " . $nextConditionExist;
                }
            }
        }
        $taskConfigurationSortedByGroup[$taskGroupId][$taskOrder]["TASK_VALIDATION_STRING"] = $validationString;

        if (in_array($taskGroupId, $groupsInUse) === false) {
            $groupsInUse[] = $taskGroupId;
        }
        //If node can be duplicated add to list to especial tasks
        if (empty($taskConfiguration["DASHBOARD_DUPLICATE_TASK_PER_USER"]) === false && $taskConfiguration["DASHBOARD_DUPLICATE_TASK_PER_USER"] === true) {
            $tasksAbleToBeDuplicated[] = $taskConfiguration["DASHBOARD_TASK_NODE"];
        }
        //If node is inside loop add to loop tasks array with its corresponding previous task
        if (empty($taskConfiguration["DASHBOARD_TASK_INSIDE_LOOP"]) === false && $taskConfiguration["DASHBOARD_TASK_INSIDE_LOOP"] === true) {
            $tasksInLoop[$taskConfiguration["DASHBOARD_TASK_NODE"]] = $taskConfiguration["DASHBOARD_TASK_BEFORE_LOOP_TASK_NODE"];
        }
        //If node has a clone task, add it to the the array $tasksWithClone with its corresponding clone node id
        if (empty($taskConfiguration["DASHBOARD_TASK_CLONED_NODE"]) === false && $taskConfiguration["DASHBOARD_TASK_CLONED_NODE"] === true) {
            $tasksWithClone[$taskConfiguration["DASHBOARD_TASK_NODE"]] = $taskConfiguration["DASHBOARD_TASK_CLONED_NODE_ID"];
        }
        //If node is self service add it to the self service tasks array
        if (empty($taskConfiguration["DASHBOARD_SELF_SERVICE_TASK"]) === false && $taskConfiguration["DASHBOARD_SELF_SERVICE_TASK"] === true) {
            $selfServiceTasks[$taskGroupId][] = $taskConfiguration["DASHBOARD_TASK_NODE"];
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
    //The user is authorized to edit target close
    $editionTargetCloseAuthorized = "YES";
    $userCanReapproveCases = "YES";
    $usersCanReapprove = [];
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
            //Add exception for self service tasks (OR)
            $selfServiceGroups = array_keys($selfServiceTasks);
            //Get the list of users assigned to the groups of self service tasks
            $queryGetSelfServiceTasksGroupUsers = "SELECT group_id,
                                                          member_id 
                                                   FROM group_members 
                                                   WHERE group_id IN ('" . implode("','", $selfServiceGroups) . "')";
            $selfServiceTasksGroupResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetSelfServiceTasksGroupUsers));
            $selfServiceGroupUsers = array();
            if (!empty($selfServiceTasksGroupResponse[0]["member_id"])) {
                foreach ($selfServiceTasksGroupResponse as $groupUser) {
                    $selfServiceGroupUsers[$groupUser["group_id"]][] = $groupUser["member_id"];
                }
            }
            //Prepare Additional Where
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
                    if (count($selfServiceGroupUsers) > 0) {
                        $queryCasesAdditionalWhere .= " OR IF((PRT.user_id = '' OR ISNULL(PRT.user_id)) AND (";
                        $groupIndex = 0;
                        foreach ($selfServiceGroupUsers as $group=>$selfServiceGroup) {
                            if ($groupIndex == 0) {
                                $queryCasesAdditionalWhere .= " (PRT.element_id IN ('" . implode("','", $selfServiceTasks[$group]) . "') AND " . $currentUser . " IN (" . implode(",", $selfServiceGroup) . "))";
                            } else {
                                $queryCasesAdditionalWhere .= " OR (PRT.element_id IN ('" . implode("','", $selfServiceTasks[$group]) . "') AND " . $currentUser . " IN (" . implode(",", $selfServiceGroup) . "))";
                            }
                        }
                        $queryCasesAdditionalWhere .= "), 1=1, 0=1)";
                    }
                    $queryCasesAdditionalWhere .= ")";
                    break;
            }
        }
        //Check if the current user is in the groups configured for editing target close
        $queryBelongsToGroups = "SELECT member_id 
                                FROM group_members 
                                WHERE member_id = " . $currentUser . "
                                    AND group_id IN ('" . implode("','", $groupsEditTargetClose) . "')";
        $userBelongsToGroupsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBelongsToGroups));
        if (!empty($userBelongsToGroupsResponse[0]["member_id"])) {
            $editionTargetCloseAuthorized = "YES";
        } else {
            $editionTargetCloseAuthorized = "NO";
        }
        // Check if the current user is in the group can reapproval cases
        $groupsCanReapprove = json_decode($dashboardSettingsResponse[0]["DASHBOARD_GROUP_CAN_REAPPROVE"], true);
        foreach ($groupsCanReapprove as $item) {
            $dis[] = $item['id'];
        }
        $idGroupsCanReapprove = implode(", ", $dis);

        $queryApproversGroup = "SELECT member_id 
                                FROM group_members 
                                WHERE member_id = " . $currentUser . "
                                    AND group_id IN ('" . $idGroupsCanReapprove . "')";
        $responseUserCanReapprove = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryApproversGroup));
        if (!empty($responseUserCanReapprove[0]["member_id"])) {
            $userCanReapproveCases = "YES";
        } else {
            $userCanReapproveCases = "NO";
        }
        // Get Array of User can re approve the case
        $usersCanReapprove = json_decode($dashboardSettingsResponse[0]["DASHBOARD_USERS_CAN_REAPPROVE"], true);
    }
    // Additional operations to obtain individual users who can Reapprove cases
    $extraVariablesData = [];
    $extraVariablesSql = [];
    $extra_fields_sql = "";
    if(!$case["USER_CAN_REAPPROVAL"] && count($usersCanReapprove) > 0)  {
        foreach ($usersCanReapprove as $caseVariableValue) {
            $extraVariablesData[] = $caseVariableValue["DASHBOARD_USER_CASE_VARIABLE"];
            $extraVariablesSql[] = "PR.data->>'$.". $caseVariableValue["DASHBOARD_USER_CASE_VARIABLE"] ."' AS ". $caseVariableValue["DASHBOARD_USER_CASE_VARIABLE"] ."";
        }
        if (count($extraVariablesSql) > 0) {
            $extra_fields_sql = "," . implode(",", $extraVariablesSql);
        }
    }

    //Get List of Cases in Progress
    $queryGetCasesList = "SELECT DISTINCT(PR.case_number) AS CASE_NUMBER,
                                 PR.case_title AS CASE_TITLE,
                                 PR.data->>'$.PE_TARGET_CLOSE_DATE' AS PE_TARGET_CLOSE_DATE,
                                 PR.data->>'$.PE_MANDATES' AS PE_MANDATES,
                                 PR.data->>'$.PE_REAPPROVAL_CASE' AS PE_REAPPROVAL_CASE
                          FROM process_requests AS PR
                          INNER JOIN process_request_tokens PRT
                              ON PRT.process_request_id IN (SELECT id 
														    FROM process_requests
														    WHERE case_number = PR.case_number)
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
    $countAllCasesSql = "SELECT COUNT(*) AS total_cases
                        FROM (" . $queryGetCasesList . ") AS subQuery;";
    $casesListWithoutLimit = callApiUrlGuzzle($apiUrl, "POST", encodeSql($countAllCasesSql));
    //$casesListWithoutLimit = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));
    $queryGetCasesList .= " ORDER BY " . $orderBy . " " . $orderType . "
                            LIMIT " . $pageSize . " OFFSET " . $start;
    $casesList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCasesList));
    //return [$casesListWithoutLimit[0]["total_cases"], $casesListWithoutLimit, $casesList];
    //Check that exist at least one case to draw
    if (!empty($casesList[0]["CASE_NUMBER"])) {
        $caseVariableValues = array();
        //Get Variable Values to evaluate tasks conditions
        if (count($tasksConditionsVariables) > 0) {
            //Get all case numbers to obtain case variables
            $caseNumberList = array_column($casesList, "CASE_NUMBER");
            $tasksConditionsVariablesQuery = ", " . implode(",", $tasksConditionsVariables);
            //Look for a way to obtain last data for each case
            //Query to obtain case variable values
            $queryGetCaseVariables = "SELECT case_number AS CASE_NUMBER" . $tasksConditionsVariablesQuery . " 
                                      FROM process_requests 
                                      INNER JOIN (
                                                  SELECT MAX(id) AS max_id
                                                  FROM process_requests 
                                                  WHERE case_number IN (" . implode(",", $caseNumberList) . ")
                                                      AND process_id IN (16, 26, 27)
                                                  GROUP BY case_number
                                                  ) Latest ON id = Latest.max_id";
            $caseVariableValuesResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGetCaseVariables));
            //return [$casesList, $caseVariableValuesResponse, $caseNumberList];
            if (!empty($caseVariableValuesResponse[0]["CASE_NUMBER"])) {
                foreach ($caseVariableValuesResponse as $caseVariableValue) {
                    $cloneCaseVariables = $caseVariableValue;
                    unset($cloneCaseVariables["CASE_NUMBER"]);
                    $caseVariableValues[$caseVariableValue["CASE_NUMBER"]] = $cloneCaseVariables;
                }
            }
        }
        //Get total of cases
        //$dashboardTotalRows = count($casesListWithoutLimit);
        $dashboardTotalRows = $casesListWithoutLimit[0]["total_cases"];
        //Get case completed and in progress threads
        $caseTasks = array();
        foreach ($casesList as $case) {
            $caseNumber = $case["CASE_NUMBER"];
            $queryCaseThreads = "SELECT *
                                FROM (SELECT PRT.id,
                                        PRT.element_id,
                                        PRT.process_request_id,
                                        PRT.version_id,
                                        PRT.user_id,
                                        PRT.status,
                                        IF(PRT.user_id = '' OR ISNULL(PRT.user_id), 
                                            'Unassigned',
                                             CONCAT (U.firstname, ' ', U.lastname)
                                        ) AS USER_FULL_NAME,
                                        PR.case_number,
                                        -- PR.data->>'$.PE_PARENT_CASE_NUMBER'
                                        PR.v_pe_parent_case_number
                                        ". $extra_fields_sql ."
                                 FROM process_request_tokens AS PRT
                                 INNER JOIN process_requests AS PR
                                     ON PR.id = PRT.process_request_id
                                 LEFT JOIN users U on U.id = PRT.user_id
                                 WHERE (PR.case_number = " . $caseNumber . "
                                         -- OR (PR.data->>'$.PE_PARENT_CASE_NUMBER' = '" . $caseNumber . "' AND PR.case_number IS NOT NULL))
                                         -- OR (PR.v_pe_parent_case_number = " . $caseNumber . " AND PR.case_number IS NOT NULL))
                                            OR (PR.v_pe_parent_case_number = " . $caseNumber . ")
                                        )
                                     AND PRT.element_type = 'task'
                                ) AS res
                                WHERE case_number is not null
                                ORDER BY id DESC";
            $casesThreadsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCaseThreads));
            // return [$queryGetCaseVariables, $queryCaseThreads];
            if (!empty($casesThreadsResponse[0]["element_id"])) {
                $caseTasks[$caseNumber]["CASE_TITLE"] = $case["CASE_TITLE"];
                $caseTasks[$caseNumber]["PE_TARGET_CLOSE_DATE"] = $case["PE_TARGET_CLOSE_DATE"];
                $caseTasks[$caseNumber]["PE_MANDATES"] = $case["PE_MANDATES"];
                $caseTasks[$caseNumber]["SHOW_ICON_EDIT_TARGET"] = ($editionTargetCloseAuthorized == 'NO') ? false : true;
                $caseTasks[$caseNumber]["USER_CAN_REAPPROVAL"] = ($userCanReapproveCases == 'NO') ? false : true;
                $caseTasks[$caseNumber]["PE_REAPPROVAL_CASE"] = $case["PE_REAPPROVAL_CASE"] ? 'YES' : 'NO';
                
                //Obtain Only Variables For Conditions
                if (count($caseVariableValues) > 0) {
                    $caseTasks[$caseNumber]["CASE_VARIABLES"] = $caseVariableValues[$caseNumber];
                }
                //Set array with node id as keys
                $nodesAlreadyConsidered = array();
                foreach ($casesThreadsResponse as $thread) {
                    $nodeId = $thread["element_id"];
                    $addNodeToCaseNodes = true;
                    if ($thread["status"] == 'ACTIVE' && ($nodeId == 'DT01' || $nodeId == 'DT02')) { $caseTasks[$caseNumber]["SHOW_ICON_EDIT_TARGET"] = false; }
                    //Check if node is in loop
                    if (array_key_exists($nodeId, $tasksInLoop) === true) {
                        //Get loop initiator task of current task
                        $initiatorNodeId = $tasksInLoop[$nodeId];
                        //Check if initiator task exist in array of thread already considered
                        if (array_key_exists($initiatorNodeId, $nodesAlreadyConsidered) === true) {
                            //Check if token id of current task is after token id of initiator task
                            if ($thread["id"] < $nodesAlreadyConsidered[$initiatorNodeId]) {
                                $addNodeToCaseNodes = false;
                            }
                        }
                    }
                    if ($addNodeToCaseNodes === true && array_key_exists($nodeId, $nodesAlreadyConsidered) === false) {
                        //Verify if node exist in the list of tasks that can be duplicated
                        if (in_array($nodeId, $tasksAbleToBeDuplicated) === true) {
                            $nodeId = $nodeId . $thread["user_id"];
                        }
                        $caseTasks[$caseNumber]["TASKS"][$nodeId] = array(
                            "STATUS" => $thread["status"],
                            "CURRENT_USER" => $thread["USER_FULL_NAME"],
                            "REQUEST_ID" => $thread["process_request_id"],
                            "NODE_VERSION_ID" => $thread["version_id"],
                            "REQUEST_TASK_ID" => $thread["id"],
                            "CURRENT_USER_ID" => $thread["user_id"],
                        );
                        $nodesAlreadyConsidered[$nodeId] = $thread["id"];
                    }
                    // Add individual users who can Reapprove cases
                    foreach($extraVariablesData as $nodeValue) {
                        if (!isset($caseTasks[$caseNumber][$nodeValue])) {
                            $caseTasks[$caseNumber][$nodeValue] = $thread[$nodeValue] ?? null;
                        }
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
                //Fix to order
                ksort($taskGroup);
                foreach ($taskGroup as $taskOrder => $task) {
                    $nodeExist = false;
                    $nodeToEvaluate = $task["TASK_NODE"];
                    $nodesToDraw = array();
                    //Verify if node exist in the list of tasks that can be duplicated
                    if (in_array($nodeToEvaluate, $tasksAbleToBeDuplicated) === true) {
                        //Get all keys of case task array
                        $caseKeys = array_keys($case["TASKS"]);
                        foreach ($caseKeys as $key) {
                            if (strpos($key, $nodeToEvaluate) === 0) {
                                $nodesToDraw[] = $key;
                                $nodeExist = true;
                            }
                        }
                    } else {
                        //Check if node has a clone
                        if (array_key_exists($nodeToEvaluate, $tasksWithClone)) {
                            $cloneTaskId = $tasksWithClone[$nodeToEvaluate];
                            //Check if clone task exist in case nodes
                            if (array_key_exists($cloneTaskId, $case["TASKS"])) {
                                //Replace original node information with cloned node information
                                $case["TASKS"][$nodeToEvaluate] = $case["TASKS"][$cloneTaskId];
                                $nodesToDraw[] = $nodeToEvaluate;
                                $nodeExist = true;
                            } else {
                                if (array_key_exists($nodeToEvaluate, $case["TASKS"])) {
                                    $nodesToDraw[] = $nodeToEvaluate;
                                    $nodeExist = true;
                                }
                            }
                        } else {
                            if (array_key_exists($nodeToEvaluate, $case["TASKS"])) {
                                $nodesToDraw[] = $nodeToEvaluate;
                                $nodeExist = true;
                            }
                        }
                    }
                    if ($nodeExist === true) {
                        //Draw in progress and completed rows
                        foreach ($nodesToDraw as $node) {
                            $threadInformation = $case["TASKS"][$node];
                            $currentUser = $threadInformation["CURRENT_USER"];
                            $requestId = $threadInformation["REQUEST_ID"];
                            $nodeversionID = $threadInformation["NODE_VERSION_ID"];
                            $requestTaskId = $threadInformation["REQUEST_TASK_ID"];
                            //Check if task is in progress
                            if ($case["TASKS"][$node]["STATUS"] == "ACTIVE") {
                                $countInProgress++;
                                if ($case["TASKS"][$node]["CURRENT_USER_ID"] == $currentUserLogged || (empty($case["TASKS"][$node]["CURRENT_USER_ID"]) && empty($selfServiceTasks[$groupId]) === false && array_search($node, $selfServiceTasks[$groupId]) !== false && empty($selfServiceGroupUsers[$groupId]) === false && array_search($currentUserLogged, $selfServiceGroupUsers[$groupId]) !== false)) {
                                    $inProgressTasksHTML .= "<td class='inProgressStyle tdTasks' onclick='showCurrentUserTasks(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                                } else {
                                    $inProgressTasksHTML .= "<td class='inProgressStyle tdTasks' onclick='showThreadDynaform(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                                }
                            } else {
                                $countCompleted++;
                                $completedTasksHTML .= "<td class='completeStyle tdTasks' onclick='showThreadDynaform(\"" . $requestId . "\", \"" . $requestTaskId . "\", \"" . $nodeversionID . "\");' title='" . $currentUser . "'>" . $task["TASK_NAME"] . "</td>";
                            }
                        }
                    } else {
                        //Check task conditions to define if task will be pending or the case will never go to the task in evaluation
                        $taskConditionExist = $task["TASK_CONDITIONS_EXIST"];
                        if ($taskConditionExist) {
                            //Replace Value of case data in task condition
                            $validationString = $task["TASK_VALIDATION_STRING"];
                            //Replace Case Variables with Values
                            foreach ($case["CASE_VARIABLES"] as $key=>$variableValue) {
                                //Check if variable has a value
                                if ($variableValue != null) {
                                    $validationString = str_replace("{" . $key . "}", $variableValue, $validationString);
                                }
                            }
                            //Check if all variables could be replaced
                            if (strpos($validationString, "{") !== false) {
                                $validationString = "";
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
            
            // If the current user is not part of any of the groups configured for this dashboard
            if(!$case["USER_CAN_REAPPROVAL"] && count($usersCanReapprove) > 0)  {
                $case["USER_CAN_REAPPROVAL_Detail"] = '/User=> '.$currentUserLogged;
                // We check if the current user is set to be able to reapprove cases
                foreach ($usersCanReapprove as $caseVariableValue) {
                    $variableUserCanReapprove = $caseVariableValue["DASHBOARD_USER_CASE_VARIABLE"];
                    $case["USER_CAN_REAPPROVAL_Detail"] .= '/'.$variableUserCanReapprove. '=>' . $case[$variableUserCanReapprove];
                    if ((int) $currentUserLogged == (int) $case[$variableUserCanReapprove]) {
                        $case["USER_CAN_REAPPROVAL"] = true;
                        break;
                    }
                }
            }
            
            $dashboardCasesArray[] = array(
                $caseNumber, //0
                $groupTaskHTML, //1
                $case["SHOW_ICON_EDIT_TARGET"], //2
                $case["PE_TARGET_CLOSE_DATE"], //3
                $case["CASE_TITLE"], //4
                $case["PE_MANDATES"], //5
                $case["USER_CAN_REAPPROVAL"], //6
                $case["USER_CAN_REAPPROVAL_Detail"], //7
                //$caseTasks, //8
                //$taskConfigurationSortedByGroup //9
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