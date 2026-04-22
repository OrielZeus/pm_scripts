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

function getCasesListByProcessId($processId, $status=false) {
    //status: COMPLETED, ACTIVE, ERROR, CANCELED
    global $apiSql, $apiHost;
    $query = "SELECT case_number, case_title, process_id, initiated_at, completed_at, id as requestId, status, '' as current_task 
        FROM process_requests
        WHERE process_id = $processId";
    $query .= !$status ? " AND status NOT IN ('ERROR', 'CANCELED')" : " AND status = '$status'" ;   
    $query .= " ORDER BY case_number DESC";

    $queryResult = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($query));
    
    if(!$status || $status != 'COMPLETED') {
        foreach ($queryResult as &$caseData) {
            $caseData['tasks'] = getActiveTasksByCaseNumber($caseData['case_number']);
            $caseData['current_task'] = $caseData['tasks'][0]['tasks'][0]['element_name'];
        }
    }    

    return $queryResult;
}

if(isset($data['action']) && $data['action'] == 'getData'){
    return getCasesListByProcessId($mainProcessId, 'ACTIVE');
}

//Get libraries ids
$sqlLibraries = "SELECT ME.id, 
                        ME.file_name, 
                        ME.mime_type
                 FROM media AS ME
                 WHERE ME.disk = 'public' AND (ME.mime_type = 'text/plain' OR ME.mime_type = 'image/gif') 
                     AND (ME.file_name LIKE '%.css%' OR ME.file_name LIKE '%.js%' OR ME.file_name LIKE '%.gif%')
                 ORDER BY ME.created_at DESC";
$getLibraries = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($sqlLibraries));
$libraries = [];
foreach ($getLibraries as $library) {
    $libraries[$library['file_name']] = $library['id'];
}

$html = "";
$html .= "<head>";
$cssToLoad = [
    'bootstrap.min.css',
    'font-awesome.min.css',
    'jquery.dataTables.min.css',
    'cssLibraryNorthleaf_3.css'
];
foreach ($cssToLoad as $css) {
    $html .= $libraries[$css] ? "<link rel='stylesheet' type='text/css' href='/storage/" . $libraries[$css] . "/$css'/>" : "<meta name='$css' content='Library not loaded'>";
}
//$html .= "<link rel='stylesheet' type='text/css' href='https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.min.css'/>";

//Add js
$jsToLoad = [
    'jquery-1.12.4.js',
    'jquery.dataTables.min.js',
    'dataTables.bootstrap.min.js',
    'bootstrap.js',
    'jquery.blockUI.js',
    'moment.min.2.24.js'
];
foreach ($jsToLoad as $js) {
   $html .= $libraries[$js] ? "<script type='text/javascript' src='/storage/" . $libraries[$js] . "/$js'></script>" : "<meta name='$js' content='Library not loaded'>";
}
//$html .= "<script type='text/javascript' src='https://code.jquery.com/jquery-2.1.3.min.js'></script>";
//$html .= "<script type='text/javascript' src='https://cdn.datatables.net/2.3.2/js/dataTables.min.js'></script>";

$html .= " <style>
    table {
      border-collapse: collapse;
      width: 100%;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 6px 10px;
      text-align: center;
    }
    th {
      background-color: #7a0c21;
      color: white;
    }
    .status {
      padding: 3px 8px;
      border-radius: 10px;
      font-size: 12px;
      color: white;
    }
    .in-progress {
        //background-color: #4caf50;
        --tw-bg-opacity: 1;
        background-color: rgb(220 252 231 / var(--tw-bg-opacity));
        --tw-text-opacity: 1;
        color: rgb(34 197 94 / var(--tw-text-opacity))
    }
    .completed {
        //background-color: #2196f3;
        --tw-bg-opacity: 1;
        background-color: rgb(219 234 254 / var(--tw-bg-opacity));
        --tw-text-opacity: 1;
            color: rgb(59 130 246 / var(--tw-text-opacity));
    }
    .circle-dots {
        width: 24px;
        height: 24px;
        background:#ececec;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        float: right;
    }
    .dots {
        height: 34px;
        font-size: 20px;
        color: #6b676778;
        cursor: pointer;
    }
    .tooltipContainer {
        display:none;
        width: 500px;
        border: 2px solid #c8c8c8;
        background-color: #ffffff;
        opacity: 1;
        text-align: justify;
        color: #7a0c21;
        border-radius: 10px;
        padding: 10px;
        position: fixed;
        z-index: 1;
        font-size: 14px;
    }

    .tooltipContainer .tooltipHeader{
        font-style: italic;
        --tw-text-opacity: 1;
        color: rgb(156 163 175 / var(--tw-text-opacity));
    }

    .tooltipContainer .tooltipBody{
        --tw-text-opacity: 1;
        color: rgb(107 114 128 / var(--tw-text-opacity));
    }

    .tooltipBody a{
        cursor: pointer;
        //text-decoration: none;
        background-color: transparent;
    }

    .dataTables_wrapper{
        margin-top: 20px !important;
    }

    @media (min-width: 768px) {
        .modal-xl {
            width: 90%;
            max-width:1200px;
        }
    }
    .modalContainer {
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(73, 94, 109, 0.5) transparent;
    }

    .tasksTable {
        font-size: small !important;
    }

    .nav-tabs {
        border-bottom: 1px solid #711426;
    }

    .nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover{
        border: 1px solid #711426;
        border-bottom-color: transparent;
        background-color: #711426;
        color: #FFF;
    }

    .noTasksMsg {
        text-align: center;
        margin: 35px;    
    }

    .refreshMainTable {
        float: right;
        margin-right: 10px;
        border: 1px solid;
        width: 30px;
        text-align: center;
    }

    table.dataTable thead .sorting_asc {
        background-image: url('/storage/1396/sort_asc2.png') !important;
    }

    table.dataTable thead .sorting_desc {
        background-image: url('/storage/1395/sort_desc2.png') !important;
    }

    table.dataTable thead .sorting {
        background-image: url('/storage/19077/sort_both.png') !important;
    }
  </style>";
$html .= "</head>";

$html .= "<body class='bodyStyle'>";
$html .= "<div class='dataTables_wrapper'>";
$html .= "<table id='mainTable' class='display' width='100%'>
    <thead>
        <tr>
            <th>Case #</th>
            <th>Case Title</th>
            <th>Current Task</th>
            <th>Status</th>
            <th>Started</th>
            <th>Completed</th>
            <th>Tasks</th>
            <th>Documents</th>
        </tr>
    </thead>    
</table>";
$html .= "</div></body>";

$html .= "<!--Modal File History-->
<div class = 'modal fade modalContainer' id='fileHistoryModal'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header modalTitleStyle'>
                <center><b>Documents</b></center>
            </div>
            <div class='modal-body' id='fileHistoryBody'>
            </div>
            <div class='modal-footer'>
                <|button type='button' class='btn btn-secondary' onclick='closeModal(this);'>Close</|button>
            </div>
        </div>
    </div>
</div>";

$html .= "<!--Modal Task History-->
<div class = 'modal fade modalContainer' id='taskHistoryModal'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header modalTitleStyle'>
                <center><b>Task</b></center>
            </div>
            <div class='modal-body' id='taskHistoryBody'>
                <div class='container' style='display: contents;'>
                    <ul class='nav nav-tabs tabsHeader'>
                        <li class='active'><a class='defaultTab' data-toggle='tab' href='#inProgressTasks'>In Progress</a></li>
                        <li><a data-toggle='tab' href='#completedTasks'>Completed & Form</a></li>
                    </ul>
                    <div class='tab-content'>
                        <div id='inProgressTasks' class='tab-pane fade in active'>
                        </div>
                        <div id='completedTasks' class='tab-pane fade'>
                        </div>
                    </div>
                </div>         
            </div>
            <div class='modal-footer'>
                <|button type='button' class='btn btn-secondary' onclick='closeModal(this);'>Close</|button>
            </div>
        </div>
    </div>
</div>";

$html .= "<!--Modal Preview Task-->
<div class = 'modal fade modalContainer' id='previewAndPrintModal'>
    <div class='modal-dialog modal-xl'>
        <div class='modal-content'>
            <div class='modal-header modalTitleStyle'>
                <center><b>Task</b></center>
            </div>
            <div class='modal-body' id='previewAndPrintBody'>
                <div id='loadingContainer'></div>
                <div id='iframeContainer'></div>
            </div>
            <div class='modal-footer'>
                <|button type='button' class='btn btn-secondary' onclick='closeModal(this);'>Close</|button>
            </div>
        </div>
    </div>
</div>";

$html .= "
<script type='text/javascript'>
    function showFileHistory(caseNumber)
    {
        $('#fileHistoryModal .modalTitleStyle').find('b').text('Documents Case #'+caseNumber);
        $('#fileHistoryBody').html('');
        $('#fileHistoryModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        //Verify if browser is internet explorer
        var ms_ie = false;
        var ua = window.navigator.userAgent;
        var old_ie = ua.indexOf('MSIE');
        var new_ie = ua.indexOf('Trident/');

        if ((old_ie > -1) || (new_ie > -1)) {
            ms_ie = true;
        }
        //Remove class fade only if browser is internet explorer
        if (ms_ie) {
            $('.modal').removeClass('fade');
        }
        let request = {
            'case_number': (caseNumber ?? '')
        };
        let newData = {'data': JSON.stringify(request)}
        $.ajax( {
            'type':     'post',
            'url':      '".$apiHost."/pstools/script/get-file-history',
            'data':     newData,
            'dataType': 'json',
            'cache':    false,
            'beforeSend': function (xhr){
                $('#fileHistoryBody').html(`<center><svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' preserveAspectRatio=\'xMidYMid\' width=\'200\' height=\'200\' style=\'shape-rendering: auto; display: block; background: rgb(255, 255, 255);\' xmlns:xlink=\'http://www.w3.org/1999/xlink\'><g><circle stroke-linecap=\'round\' fill=\'none\' stroke-dasharray=\'50.26548245743669 50.26548245743669\' stroke=\'#9b1012\' stroke-width=\'8\' r=\'32\' cy=\'50\' cx=\'50\'>
                    <animateTransform values=\'0 50 50;360 50 50\' keyTimes=\'0;1\' dur=\'1s\' repeatCount=\'indefinite\' type=\'rotate\' attributeName=\'transform\'></animateTransform>
                    </circle><g></g></g><!-- [ldio] generated by https://loading.io --></svg></center>`);
            },
            'success':  function ( json ) { 
                if (typeof json['PE_HISTORY_FILE'] !== 'undefinded' && json['PE_HISTORY_FILE'].length > 0) {
                    let body = `<table width='100%' border='1' class='tableClass' style='border-color: #e3e3e3;'>
                        <thead><th>User</th>
                        <th>Document</th>
                        <th>Task</th>
                    </thead>`;
                    json['PE_HISTORY_FILE'].forEach(file => {
                        body += `<tr>`;
                        body += `<td>`+ file.USER_NAME +`</td>`;
                        body += `<td><a href='`+ file.URL +`' download>`+ file.FILE_NAME +`</a></td>`;
                        body += `<td>`+ file.TASK_NAME +`</td>`;
                        body += `</tr>`;
                    });
                    body += `</table>`;
                    $('#fileHistoryBody').html(body);
                } else {
                    $('#fileHistoryBody').html(`<center>No files were found for this case</center>`);
                }
            },
            'complete': function(){
            }
        } );
    }

    function formatDateTime(date) {
        const specificDate = new Date(date);
        const year = specificDate.getFullYear();
        const month = String(specificDate.getMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        const day = String(specificDate.getDate()).padStart(2, '0');
        const hours = String(specificDate.getHours()).padStart(2, '0');
        const minutes = String(specificDate.getMinutes()).padStart(2, '0');
        const seconds = String(specificDate.getSeconds()).padStart(2, '0');

        return year+'-'+month+'-'+day+' '+hours+':'+minutes+':'+seconds;
    }

    function previewAndPrint(taskId) {
        $('#previewAndPrintModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        //Verify if browser is internet explorer
        var ms_ie = false;
        var ua = window.navigator.userAgent;
        var old_ie = ua.indexOf('MSIE');
        var new_ie = ua.indexOf('Trident/');

        if ((old_ie > -1) || (new_ie > -1)) {
            ms_ie = true;
        }
        //Remove class fade only if browser is internet explorer
        if (ms_ie) {
            $('.modal').removeClass('fade');
        }

        $.ajax( {
            'type':     'get',
            'url':      '".$apiHost."/tasks/'+taskId,
            'dataType': 'json',
            'cache':    false,
            'headers': {
                'accept': 'application/json',
                'authorization': 'Bearer ".getenv('API_TOKEN')."'
            },
            'beforeSend': function (xhr){
                $('#previewAndPrintBody #loadingContainer').html(`<center><svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' preserveAspectRatio=\'xMidYMid\' width=\'200\' height=\'200\' style=\'shape-rendering: auto; display: block; background: rgb(255, 255, 255);\' xmlns:xlink=\'http://www.w3.org/1999/xlink\'><g><circle stroke-linecap=\'round\' fill=\'none\' stroke-dasharray=\'50.26548245743669 50.26548245743669\' stroke=\'#9b1012\' stroke-width=\'8\' r=\'32\' cy=\'50\' cx=\'50\'>
                    <animateTransform values=\'0 50 50;360 50 50\' keyTimes=\'0;1\' dur=\'1s\' repeatCount=\'indefinite\' type=\'rotate\' attributeName=\'transform\'></animateTransform>
                    </circle><g></g></g><!-- [ldio] generated by https://loading.io --></svg></center>`);
            },
            'success':  function ( json ) { 
                $('#previewAndPrintModal .modalTitleStyle').find('b').text('Task #'+taskId+' - '+json.element_name);
                let taskPreviewURL = '/requests/'+json.process_request_id+'/task/'+taskId+'/screen/'+json.version_id;
                let taskIframe = $('<iframe>', {
                    src: taskPreviewURL,
                    id: 'previewAndPrintIframe',
                    frameborder: 0,
                    scrolling: 'auto',
                    style: 'width: 100%; height: 600px; display:none'
                });

                $('#previewAndPrintBody #iframeContainer').html(taskIframe);

                $('#previewAndPrintIframe').on('load', function() {
                    var iframe = $('#previewAndPrintIframe'); 
                    var iframeDoc = iframe.contents();
                    iframeDoc.find('#sidebar').hide(); 
                    iframeDoc.find('#navbar').parent().hide();
                    
                    $('#previewAndPrintBody #loadingContainer').html('');
                    $('#previewAndPrintIframe').show();
                }); 

                
            }
        });
    }

    function showTasksHistory(caseNumber)
    {
        $('#taskHistoryModal .modalTitleStyle').find('b').text('Tasks Case #'+caseNumber);

        $('#inProgressTasks').html('');
        $('#completedTasks').html('');

        $('#taskHistoryBody .defaultTab').trigger('click');        

        $('#taskHistoryModal').modal({
            backdrop: 'static',
            keyboard: false
        });
        //Verify if browser is internet explorer
        var ms_ie = false;
        var ua = window.navigator.userAgent;
        var old_ie = ua.indexOf('MSIE');
        var new_ie = ua.indexOf('Trident/');

        if ((old_ie > -1) || (new_ie > -1)) {
            ms_ie = true;
        }
        //Remove class fade only if browser is internet explorer
        if (ms_ie) {
            $('.modal').removeClass('fade');
        }

        $('#taskHistoryBody #inProgressTasks').html(`<center><svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' preserveAspectRatio=\'xMidYMid\' width=\'200\' height=\'200\' style=\'shape-rendering: auto; display: block; background: rgb(255, 255, 255);\' xmlns:xlink=\'http://www.w3.org/1999/xlink\'><g><circle stroke-linecap=\'round\' fill=\'none\' stroke-dasharray=\'50.26548245743669 50.26548245743669\' stroke=\'#9b1012\' stroke-width=\'8\' r=\'32\' cy=\'50\' cx=\'50\'>
                    <animateTransform values=\'0 50 50;360 50 50\' keyTimes=\'0;1\' dur=\'1s\' repeatCount=\'indefinite\' type=\'rotate\' attributeName=\'transform\'></animateTransform>
                    </circle><g></g></g><!-- [ldio] generated by https://loading.io --></svg></center>`);
        $.when(           
            $.ajax( {
                'type':     'get',
                'url':      '".$apiHost."/tasks-by-case',
                'data':     {
                    'case_number': (caseNumber ?? ''),
                    'status': 'ACTIVE',
                    'order_direction': 'asc',
                    'page': 1,
                    'per_page': 100
                },
                'dataType': 'json',
                'cache':    false,
                'headers': {
                    'accept': 'application/json',
                    'authorization': 'Bearer ".getenv('API_TOKEN')."'
                }
            }),
            $.ajax( {
                'type':     'get',
                'url':      '".$apiHost."/tasks-by-case',
                'data':     {
                    'case_number': (caseNumber ?? ''),
                    'status': 'CLOSED',
                    'order_direction': 'asc',
                    'page': 1,
                    'per_page': 100
                },
                'dataType': 'json',
                'cache':    false,
                'headers': {
                    'accept': 'application/json',
                    'authorization': 'Bearer ".getenv('API_TOKEN')."'
                }
            })
        ).then(function(response1, response2) {
            //ACTIVE TASKS
            let responseData = response1[0].data;
            //console.log('Data from request 1', responseData); 
            let htmlTabs = `<h4 class='noTasksMsg'>No Active Tasks</h4>`;
            if(Array.isArray(responseData) && responseData.length > 0){
                htmlTabs = ``;
                htmlTabs += `<table id='inProgressTable' class='tasksTable'>
                    <thead>
                        <tr>
                            <th>Task #</th>
                            <th>Task Name</th>
                            <th>Process</th>
                            <th>Assigned</th>
                            <th>Due Date</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>`;

                for (let i = 0; i < responseData.length; i++) {
                    let fullName = responseData[i].user ? responseData[i].user.fullname : 'unassigned';
                    htmlTabs += `<tr>`;
                        htmlTabs += `<td>`+ responseData[i].id +`</td>`;
                        htmlTabs += `<td>`+ responseData[i].element_name +`</td>`;
                        htmlTabs += `<td>`+ responseData[i].process.name +`</td>`;
                        htmlTabs += `<td>`+ fullName +`</td>`;
                        htmlTabs += `<td>`+ formatDateTime(responseData[i].due_at) +`</td>`;
                        htmlTabs += `<td><a target='_blank' href='/tasks/`+ responseData[i].id +`/edit' class='iconClass' title='Open Task' style='text-decoration: none;'>⮺</a></td>`;
                    htmlTabs += `</tr>`;                    
                }

                htmlTabs += `</tbody>
                </table>`;
            }            
            $('#inProgressTasks').html(htmlTabs);
            $('#inProgressTable').DataTable({
                columnDefs: [{
                    'bSortable': false,
                    'targets': [5]
                }]
            });

            //COMPLETED TASKS
            let responseData2 = response2[0].data;
            //console.log('Data from request 2:', responseData2); 
            htmlTabs = `<h4 class='noTasksMsg'>No Active Tasks</h4>`;
            if(Array.isArray(responseData2) && responseData2.length > 0){
                htmlTabs = ``;
                htmlTabs += `<table id='completedTable' class='tasksTable'>
                    <thead>
                        <tr>
                            <th>Task #</th>
                            <th>Task Name</th>
                            <th>Process</th>
                            <th>Assigned</th>
                            <th>Completed</th>
                            <th>Due Date</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>`;

                for (let i = 0; i < responseData2.length; i++) {
                    let fullName = responseData2[i].user ? responseData2[i].user.fullname : 'unassigned';
                    htmlTabs += `<tr>`;
                        htmlTabs += `<td>`+ responseData2[i].id +`</td>`;
                        htmlTabs += `<td>`+ responseData2[i].element_name +`</td>`;
                        htmlTabs += `<td>`+ responseData2[i].process.name +`</td>`;
                        htmlTabs += `<td>`+ fullName +`</td>`;
                        htmlTabs += `<td>`+ formatDateTime(responseData2[i].completed_at) +`</td>`;
                        htmlTabs += `<td>`+ formatDateTime(responseData2[i].due_at) +`</td>`;
                        htmlTabs += `<td><span class='iconClass' title='Preview and Print' aria-hidden='true' onclick='previewAndPrint(`+ responseData2[i].id +`)'>&#128065;</span></td>`;
                    htmlTabs += `</tr>`;                    
                }

                htmlTabs += `</tbody></table>`;
            }            
            $('#completedTasks').html(htmlTabs);
            $('#completedTable').DataTable({
                columnDefs: [{
                    'bSortable': false,
                    'targets': [6]
                }]
            });

        }).fail(function(error) {
            console.error('One or more AJAX requests failed:', error);
        });
    }

    function closeModal(modalId)
    {
        $(modalId).closest('.modalContainer').modal('hide');
    }

    //Function to allow differed datatable loading
    $.fn.dataTable.pipeline = function ( opts ) {
        // Configuration options
        var conf = $.extend( {
            pages: 1, // number of pages to cache
            url: '',      // script url
            data: null,   // function or object with parameters to send to the server
            method: 'GET' // Ajax HTTP method
        }, opts );
            
        // Private variables for storing the cache
        var cacheLower = -1;
        var cacheUpper = null;
        var cacheLastRequest = null;
        var cacheLastJson = null;
            
        return function (request, drawCallback, settings) {
            var ajax          = false;
            var requestStart  = request.start;
            var drawStart     = request.start;
            var requestLength = request.length;
            var requestEnd    = requestStart + requestLength;
                    
            if ( settings.clearCache ) {
                // API requested that the cache be cleared
                ajax = true;
                settings.clearCache = false;
            } else if ( cacheLower < 0 || requestStart < cacheLower || requestEnd > cacheUpper ) {
                // outside cached data - need to make a request
                ajax = true;
            } else if (JSON.stringify(request.order) !== JSON.stringify( cacheLastRequest.order ) ||
                    JSON.stringify( request.columns ) !== JSON.stringify( cacheLastRequest.columns ) ||
                    JSON.stringify( request.search )  !== JSON.stringify( cacheLastRequest.search )) {
                // properties changed (ordering, columns, searching)
                ajax = true;
            }
                    
            // Store the request for checking next time around
            cacheLastRequest = $.extend( true, {}, request );
            
            if (ajax) {
                //Need data from the server
                if (requestStart < cacheLower) {
                    requestStart = requestStart - (requestLength*(conf.pages-1));
                    if ( requestStart < 0 ) {
                        requestStart = 0;
                    }
                }
                        
                cacheLower = requestStart;
                cacheUpper = requestStart + (requestLength * conf.pages);
            
                request.start = requestStart;
                request.length = requestLength*conf.pages;
            
                //Provide the same `data` options as DataTables.
                if ($.isFunction(conf.data)) {
                    //As a function it is executed with the data object as an arg for manipulation. If an object is returned, it is used as the data object to submit
                    var d = conf.data( request );
                    if (d) {
                        $.extend( request, d );
                    }
                } else if ($.isPlainObject(conf.data)) {
                    // As an object, the data given extends the default
                    $.extend( request, conf.data );
                }

                //Send Additional Parameters to Script to Obtain Data
                request.CURRENT_USER = " . $data["currentUserId"] . ";
                let newData = {'data': JSON.stringify(request)} // Send all parameters in data key to be used as variable $data in the obtain data script
                settings.jqXHR = $.ajax( {
                    'type': conf.method,
                    'url': conf.url,
                    'data': newData,
                    'dataType': 'json',
                    'cache': false,
                    'success': function (json) {
                        let d = parent.document.getElementsByName('url_csv')[0];
                        if (d) { 
                            d.value = json.url ? json.url : ''; 
                        }
                                
                        cacheLastJson = $.extend(true, {}, json);
            
                        if (cacheLower != drawStart) {
                            json.data.splice( 0, drawStart-cacheLower );
                        }
                        json.data.splice( requestLength, json.data.length );
                                
                        drawCallback( json );
                    }
                } );
            } else {
                json = $.extend(true, {}, cacheLastJson);
                json.draw = request.draw; // Update the echo for each response
                json.data.splice( 0, requestStart-cacheLower );
                json.data.splice( requestLength, json.data.length );
            
                drawCallback(json);
            }
        }
    };

    function refreshMainTable () {
        $('#mainTable').DataTable().search('').draw();
    }

    $(document).ready( function () {
        //Get window height
        var screenHeight = 768;
        if (typeof top.innerHeight != 'undefined') {
            screenHeight = top.innerHeight;
        }
        screenHeight = screenHeight - 380;
        //document.documentElement.style.overflowY = 'hidden';

        var dtable = $('#mainTable').DataTable({
            order: [0, 'desc'],
            scrollY: screenHeight,
            pagination: true,
            pageLength: 25,
            processing: true,
            serverSide: true,
            scrollCollapse: true,
            oLanguage: {
                sProcessing: '<center><svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' preserveAspectRatio=\'xMidYMid\' width=\'200\' height=\'200\' style=\'shape-rendering: auto; display: block;\' xmlns:xlink=\'http://www.w3.org/1999/xlink\'><g><circle stroke-linecap=\'round\' fill=\'none\' stroke-dasharray=\'50.26548245743669 50.26548245743669\' stroke=\'#9b1012\' stroke-width=\'8\' r=\'32\' cy=\'50\' cx=\'50\'><animateTransform values=\'0 50 50;360 50 50\' keyTimes=\'0;1\' dur=\'1s\' repeatCount=\'indefinite\' type=\'rotate\' attributeName=\'transform\'></animateTransform></circle><g></g></g><!-- [ldio] generated by https://loading.io --></svg></center>'
            },
            ajax: $.fn.dataTable.pipeline({
                url: '" . $apiHost . "/pstools/script/dashboard-cases-list-get-data',
                pages: 1, // number of pages to cache
                'data': {
                    'action': 'getData'
                }
            }),         
            columns: [
                { data: 'case_number' },
                { data: 'case_title' },
                { 
                    data: 'current_task',
                    'bSortable': false,
                    'searchable':false,
                    render: function (data, type, row, meta) {
                        if (type === 'display') {
                            if (row['current_task']) {
                                let tasks = '<span>'+row['current_task']+'</span>';
                                if(row['moreThenOneTask']){
                                    //tasks += '<div class=\'tooltipContainer\'>'+row['HTMLTasks']+'</div>';
                                    tasks += '<div class=\'circle-dots showTooltip\'><div class=\'tooltipContainer\'><center><p>Current Tasks Case #'+row['case_number']+'</p></center>'+row['HTMLTasks']+'</div> <span class=\'dots\'> ... <span></div>';
                                }
                                return tasks;
                            }
                        }
                        return data;
                    }
                },
                { 
                    data: 'status',
                    render: function (data, type, row, meta) {
                        if (type === 'display') {
                            let caseStatus = (row['status'] == 'ACTIVE') ? 'status in-progress' : 'status completed';
                            return '<span class=\"'+caseStatus+ '\">'+row['status']+'</span>';
                        }
                        return data;
                    }
                },
                { data: 'initiated_at' },
                { data: 'completed_at' },
                {
                    'class': 'classVerticalContent ',
                    'width': '6%',
                    'bSortable': false, 
                    'searchable':false,
                    render: function (data, type, row, meta) {
                        if (type === 'display') {
                            return '<span class=\'iconClass\' title=\'Tasks\' aria-hidden=\'true\' onclick=\'showTasksHistory(\"'+row['case_number']+'\")\'><svg class=\'w-6 h-6 text-gray-800 dark:text-white\' aria-hidden=\'true\' xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' fill=\'none\' viewBox=\'0 0 24 24\'><path stroke=\'currentColor\' stroke-linecap=\'round\' stroke-width=\'2\' d=\'M9 8h10M9 12h10M9 16h10M4.99 8H5m-.02 4h.01m0 4H5\'/></svg></span>';
                        }
                        return data; 
                    }
                },
                { 
                    'class': 'classVerticalContent ',
                    'width': '6%',
                    'bSortable': false,
                    'searchable':false,
                    render: function (data, type, row, meta) {
                        // 'data' is the cell's data
                        // 'type' is the type of render (display, filter, sort, type)
                        // 'row' is the full data object/array for the current row
                        // 'meta' contains information about the cell and column
                        if (type === 'display') {
                            return '<span class=\'iconClass\' title=\'Show File History\' aria-hidden=\'true\' onclick=\'showFileHistory(\"'+row['case_number']+'\")\'><?xml version=\'1.0\' encoding=\'utf-8\'?><svg fill=\'#9B1012\' width=\'24px\' height=\'24px\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M13.981 2H6.018s-.996 0-.996 1h9.955c0-1-.996-1-.996-1zm2.987 3c0-1-.995-1-.995-1H4.027s-.995 0-.995 1v1h13.936V5zm1.99 1l-.588-.592V7H1.63V5.408L1.041 6C.452 6.592.03 6.75.267 8c.236 1.246 1.379 8.076 1.549 9 .186 1.014 1.217 1 1.217 1h13.936s1.03.014 1.217-1c.17-.924 1.312-7.754 1.549-9 .235-1.25-.187-1.408-.777-2zM14 11.997c0 .554-.449 1.003-1.003 1.003H7.003A1.003 1.003 0 0 1 6 11.997V10h1v2h6v-2h1v1.997z\'/></svg></span>';
                        }
                        return data; // Return original data for other types
                    }
                }
            ],
            columnDefs: [{
                'defaultContent': '-',
                'targets': '_all'
            }],
            'drawCallback': function(settings) {
                $('.showTooltip').on('click', function(){
                    $(this).parent().find('.tooltipContainer').show('blind');
                }); 

                $(document).mouseup(function(e) 
                {
                    var container = $('.tooltipContainer');
                    if (!container.is(e.target) && container.has(e.target).length === 0) 
                    {
                        container.hide('blind');
                    }
                });
            },
            'initComplete': function(settings) {
                $('#mainTable_filter').parent().append(`<span class='iconClass refreshMainTable' title='Refresh List' onclick='refreshMainTable()'>⭮<span>`);
            }

        });         

        $('.dataTables_filter input')
        .unbind() 
        .bind('input', function(e) { 
            if(this.value.length >= 3 || e.keyCode == 13) {
                dtable.search(this.value).draw();
            }
            if(this.value == '') {
                dtable.search('').draw(); 
                //dtable.search(this.value).draw();
            }
            return;
        });
        /*
        $('.refreshMainTable').click(function(){
            dtable.ajax.reload();
            dtable.search('').draw(); 
        });*/
                    
    });
</script>";

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
    //'PSTOOLS_RESPONSE_HTML' => $html
];