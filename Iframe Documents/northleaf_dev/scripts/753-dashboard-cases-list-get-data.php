<?php
require_once("/Northleaf_PHP_Library.php");

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');

$client = new GuzzleHttp\Client(['verify' => false]);
$hostURL = getenv("HOST_URL");
$headers = [
    'Authorization' => 'Bearer ' .   getenv('API_TOKEN'),
    'Accept'        => 'application/json',
];

$mainProcessId = 16; //Private Equity Deal Closing Process

$orderType = strtoupper($data["order"][0]['dir']);
$orderBy = $data["columns"][$data["order"][0]['column']]['data'];
$pageSize = $data['length'];
$pageNumber = $data["draw"];
$start = $data['start'];
$search = $data["search"]["value"];
$dashboardTotalRows = 0;

function escapeString($stringToEvaluate)
{
    $escapedString = str_replace('"', "\"", $stringToEvaluate);
    $escapedString = str_replace("'", "\'", $escapedString);
    $escapedString = str_replace("\\", "\\\\", $escapedString);
    return $escapedString;
}

function groupByProcessIdAndName($items) {
    $grouped = [];

    foreach ($items as $item) {
        $key = $item['process_id'] . '::' . $item['name'];

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'process_id' => $item['process_id'],
                'name' => $item['name'],
                'tasks' => []
            ];
        }
        unset($item['process_id']);unset($item['name']);
        $grouped[$key]['tasks'][] = $item;
    }

    return array_values($grouped);
}

function getActiveTasksByCaseNumber($caseNumber) {
    global $apiSql, $apiHost;
    $query = "SELECT PRT.id, PRT.process_request_id, PRT.process_id, P.name, PRT.element_name, PRT.status
        FROM process_request_tokens PRT
        LEFT JOIN processes P ON P.id = PRT.process_id
        WHERE process_request_id in (SELECT id FROM process_requests WHERE case_number = $caseNumber)
        AND PRT.status = 'ACTIVE'
        AND PRT.element_type = 'task'";
    $queryResult = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($query));
    
    return groupByProcessIdAndName($queryResult);
}

function getHTMLTasks($processTasks) {
    $html = '';

    $html .= '<div>';
    foreach ($processTasks as $process) {
        $html .= '<div><span class="tooltipHeader">► '.$process['name'].'</span></div>';
        $html .= '<ul class="tooltipBody">';
        foreach ($process['tasks'] as $task) {
            $html .= '<li><a target="_blank" href="/tasks/'.$task['id'].'/edit">'.$task['element_name'].'</a></li>';
        }
        $html .= '</ul>';        
    }
    $html .= '</div>';

    return $html;
}

function getCasesListByProcessId($processId, $status=false) {
    //status: COMPLETED, ACTIVE, ERROR, CANCELED
    global $apiSql, $apiHost, $pageSize, $start, $dashboardTotalRows, $search, $orderBy, $orderType;
    $query = "SELECT case_number, case_title, process_id, initiated_at, completed_at, id as requestId, status, '' as current_task 
        FROM process_requests
        WHERE process_id = $processId";
    $query .= !$status ? " AND status NOT IN ('ERROR', 'CANCELED')" : " AND status = '$status'" ;  

    //Add search criteria in case the user has entered a value to search
    if (!empty($search)) {
        $search = escapeString($search);
        $query .= " AND (case_number = '" . $search . "' OR case_title LIKE '%" . $search . "%' OR status LIKE '%" . $search . "%')";
    }

    //Get total of cases 
    $casesListWithoutLimit = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($query));
    $dashboardTotalRows = count($casesListWithoutLimit);
    //
    $query .= " ORDER BY $orderBy $orderType";
    //$query .= " ORDER BY case_number DESC";
    $query .= " LIMIT $pageSize OFFSET $start";

    $queryResult = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($query));

    if(!$status || $status != 'COMPLETED') {
        foreach ($queryResult as &$caseData) {
            $caseData['tasks'] = getActiveTasksByCaseNumber($caseData['case_number']);
            $caseData['HTMLTasks'] = getHTMLTasks($caseData['tasks']);
            $caseData['moreThenOneTask'] = (!empty($caseData['tasks']) && count($caseData['tasks']) > 1) || (!empty($caseData['tasks'][0]['tasks']) && count($caseData['tasks'][0]['tasks']) > 1);
            $caseData['current_task'] = '<a target="_blank" href="/tasks/'.$caseData['tasks'][0]['tasks'][0]['id'].'/edit">'.$caseData['tasks'][0]['tasks'][0]['element_name'].'</a>';
            $caseData['completed_at'] = empty($caseData['completed_at']) ? '' : $caseData['completed_at'];
        }
    }    

    return $queryResult;
}

if(isset($data['action']) && $data['action'] == 'getData'){
    return [
        "draw" => $data['draw'],
        'data' => getCasesListByProcessId($mainProcessId),
        "recordsFiltered" => $dashboardTotalRows,
        "recordsTotal" => $dashboardTotalRows
    ];
}

return [];