<?php 
/*************************************
 * OFF - HR.02 Pre Processing
 *
 * by Adriana Centellas
 ***********************************/

require_once("/Northleaf_PHP_Library.php");

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Get collections IDs
$collectionLeaderName ="OFF_TASK_LEADER";
$collectionsLeaderID = getCollectionId($collectionLeaderName, $apiUrl);
$collectionTasksName ="OFF_ADDITIONAL_TASKS";
$collectionsTasksID = getCollectionId($collectionTasksName, $apiUrl);

//Task Code
$taskCode = "HR.02";

//Get Task Lead
$userTaskLead = getTaskLeadGroup($collectionsLeaderID, $taskCode, $apiUrl, $data["OFF_OFFICE_LOCATION"]["OFFICE_DESCRIPTION"]);

$dataReturn['OFF_HUMAN_RESOURCES_APPROVER'] = $userTaskLead;
$data['OFF_HUMAN_RESOURCES_APPROVER'] = $userTaskLead;
$dataReturn["OFF_SAVE_SUBMIT"] = "";

//Get Additional Tasks
$userTasks = getAdditionalTasks($collectionsTasksID, $apiUrl);

$dataReturn['OFF_ADDITIONAL_TASKS_FORMATTED'] = $userTasks;

//Send notification
$emailType = '';
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);

$dataReturn['OFF_SENT_DETAILS'] = $notificationSent;

/**
 * Retrieve additional tasks from a collection and return them as an HTML list.
 *
 * @param (String) $collectionId - The ID of the collection from which to retrieve tasks.
 * @param (String) $apiUrl - The API URL to use for querying the collection data.
 * @return (String) $htmlOutput - An HTML string representing the tasks in an list format.
 *
 * by Adriana Centellas
 */
function getAdditionalTasks($collectionId, $apiUrl)
{
    // Define SQL query to retrieve additional tasks for the user leader
    $sqlUserLeader = "SELECT 
                        ATS.data->>'$.OFF_ADDITIONAL_TASK' as OFF_ADDITIONAL_TASK
                      FROM collection_" . $collectionId . " as ATS";

    // Send API request to retrieve the tasks and decode the response, defaulting to an empty array if null
    $tasks = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlUserLeader)) ?? [];
    
    // Initialize HTML output for displaying tasks as an unordered list
    $htmlOutput = "<ul>\n";

    // Loop through each task and add it to the HTML output as a list item
    foreach ($tasks as $task) {
        $htmlOutput .= "<li>" . $task['OFF_ADDITIONAL_TASK'] . "</li>\n";
    }

    // Close the unordered list tag
    $htmlOutput .= "</ul>";

    // Return the formatted HTML output
    return $htmlOutput;
}

return $dataReturn;