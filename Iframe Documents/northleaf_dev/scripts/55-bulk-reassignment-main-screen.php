<?php 
/**********************************
 * Bulk Reassignment - Main Screen
 *
 * by Cinthia Romero
 *********************************/
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Cinthia Romero
 */ 
function callApiUrlGuzzle($url, $method, $sendData)
{
    global $apiToken, $apiHost;
    $headers = [
        "Content-Type" => "application/json",
        "Accept" => "application/json",
        'Authorization' => 'Bearer ' . $apiToken
    ];
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    $request = new Request($method, $url, $headers, json_encode($sendData));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    $executionResponse = json_decode($res, true);
    return $executionResponse;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Cinthia Romero
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$bulkReassignmentSettingsCollectionID = getenv('BULK_REASSIGNMENT_SETTINGS_COLLECTION_ID');

//Set initial values
$currentUser = empty($data["currentUserId"]) ? "''" : $data["currentUserId"];
$filterProcess = empty($data["filterProcess"]) ? "''" : $data["filterProcess"];
$filterUser = empty($data["filterUser"]) ? "''" : $data["filterUser"];

//Check if user is Admin
$userCanSeeAllCases = "NO";
$queryUserIsAdmin = "SELECT is_administrator
                     FROM users
                     WHERE id = '" . $currentUser . "'";
$userIsAdminResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryUserIsAdmin));
if (!empty($userIsAdminResponse[0]["is_administrator"]) && $userIsAdminResponse[0]["is_administrator"] == 1) {
    $userCanSeeAllCases = "YES";
}
if ($userCanSeeAllCases == "NO") {
    //Get Dashboard Settings
    $queryBulkReassignmentSettings = "SELECT data->>'$.BRS_ALL_CASES_GROUP.id' AS BRS_ALL_CASES_GROUP
                                      FROM collection_" . $bulkReassignmentSettingsCollectionID . "
                                      ORDER BY id DESC 
                                      LIMIT 1";
    $bulkReassignmentSettingsResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBulkReassignmentSettings));
    if (!empty($bulkReassignmentSettingsResponse[0]["BRS_ALL_CASES_GROUP"])) {
        //Check if current user is able to see all cases of all users
        $groupToSeeAllCases = json_decode($dashboardSettingsResponse[0]["DASHBOARD_VIEW_ALL_CASES_GROUP"], true);
        $queryBelongsToGroup = "SELECT member_id 
                                FROM group_members 
                                WHERE member_id = " . $currentUser . "
                                    AND group_id = " . $bulkReassignmentSettingsResponse[0]["BRS_ALL_CASES_GROUP"];
        $userBelongsToGroupResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryBelongsToGroup));
        if (!empty($userBelongsToGroupResponse[0]["member_id"])) {
            $userCanSeeAllCases = "YES";
        }
    }
}
//Get libraries ids
$sqlLibraries = "SELECT ME.id, 
                        ME.file_name, 
                        ME.mime_type
                 FROM media AS ME
                 WHERE ME.disk = 'public' AND ME.mime_type = 'text/plain' 
                     AND (ME.file_name LIKE '%.css%' OR ME.file_name LIKE '%.js%')
                 ORDER BY ME.created_at DESC";
$getLibraries = callApiUrlGuzzle($apiHost . $apiSql, "POST", encodeSql($sqlLibraries));
$libraries = [];
foreach($getLibraries as $library ) {
    $libraries[$library['file_name']] = $library['id'];
}
//Generate table header
$html = "";
$html .= "<head>";
//Add css
$cssToLoad = [
    'bootstrap.min.css',
    'jquery.dataTables.min.css',
    'cssLibraryNorthleaf_4.css',
    'jquery.toast.min.css'
];
foreach($cssToLoad as $css){
    $html .= $libraries[$css] ? "<link rel='stylesheet' type='text/css' href='/storage/" . $libraries[$css] . "/$css'/>" : "<meta name='$css' content='Library not loaded'>";
}
//Add js
$jsToLoad = [
    'jquery-1.12.4.js',
    'jquery.dataTables.min.js',
    'dataTables.bootstrap.min.js',
    'bootstrap.js',
    'checkboxes.js',
    'jquery.blockUI.js'
];
foreach($jsToLoad as $js){
    $html .= $libraries[$js] ? "<script type='text/javascript' src='/storage/" . $libraries[$js] . "/$js'></script>" : "<meta name='$js' content='Library not loaded'>";
}
$html .= "</head>";
$html .= "<body class='bodyStyle'>";
$html .= "<!-- Modal error -->";
$html .= "<div id='alertMessage'></div>";
$html .= "<!--Modal for reassignment different tasks-->";
$html .= "<div class = 'modal fade' id='reassignmentModalDifferentTasks'>";
$html .= "<div class='modal-dialog'>";
$html .= "<div class='modal-content'>";
$html .= "<div class='modal-header modalTitleStyle'>";
$html .= "<center><b>REASSIGNMENT INFORMATION</b></center>";
$html .= "</div>";
$html .= "<div class='modal-body' id='modalDifferentTasksBody'>";
$html .= "</div>";
$html .= "<div class='modal-footer'>";
$html .= "<|button type='button' class='btn btn-primary' onclick='confirmReassignment(\"DIFFERENT_TASKS\");'>Reassign</|button>";
$html .= " <|button type='button' class='btn btn-secondary' onclick='cancelReassignment(\"DIFFERENT_TASKS\");'>Close</|button>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "<!--Modal for reassignment same task-->";
$html .= "<div class = 'modal fade' id='reassignmentModalSameTask'>";
$html .= "<div class='modal-dialog'>";
$html .= "<div class='modal-content'>";
$html .= "<div class='modal-header modalTitleStyle'>";
$html .= "<center><b>REASSIGNMENT INFORMATION</b></center>";
$html .= "</div>";
$html .= "<div class='modal-body'>";
$html .= "<div class='row'>";
$html .= "<|input type='text' style='display:none' id='selectedRowsSameTask'/>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12 ' style='display:flex;'>";
$html .= "<div class='col-sm-2 col-md-2 col-lg-2' style='text-align: right'>";
$html .= "";
$html .= "<span class='textlabel'>User:&nbsp;<span style='color:red;'>*</span></span>";
$html .= "";
$html .= "</div>";
$html .= "<div class='col-sm-10 col-md-10 col-lg-10' style='padding-right:0;' >";
$html .= "<|select class='form-control' id='userListSameTask' name='userListSameTask'>";
$html .= "<|option value=''>- Select -</|option>";
$html .= "</|select>";
$html .= "</div>";
$html .= "</div>";
$html .= "<br>";
$html .= "<br>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12 ' style='display:flex;'>";
$html .= "<div class='col-sm-2 col-md-2 col-lg-2' style='text-align: right'>";
$html .= "";
$html .= "<span class='textlabel'>Reason:&nbsp;<span style='color:red;'>*</span></span>";
$html .= "";
$html .= "</div>";
$html .= "<div class='col-sm-10 col-md-10 col-lg-10' style='padding-right:0;' >";
$html .= "<|textarea class='form-control' rows='3' id='reassignmentReasonSameTask'></|textarea>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "<div class='modal-footer'>";
$html .= "<|button type='button' class='btn btn-primary' onclick='confirmReassignment(\"SAME_TASK\");'>Reassign</|button>";
$html .= "<|button type='button' class='btn btn-secondary' onclick='cancelReassignment(\"SAME_TASK\");'>Close</|button>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= " <!-- Dashboard -->";
$html .= "<div class='dataTables_wrapper'>";
$html .= "<div class='row'>";
$html .= "<div class='col-md-12'>";
$html .= "<table id='tableCases' width='100%'>";
$html .= "<thead>";
$html .= "<tr>";
$html .= "<th>AppUid-TaskUid</th>";
$html .= "<th>Case #</th>";
$html .= "<th>Case Title</th>";
$html .= "<th>Task</th>";
$html .= "<th>Current<span>&nbsp;</span>User</th>";
$html .= "</tr>";
$html .= "</thead>";
$html .= "</table>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "</body>";

//Set datatable javascript
$html .= "
<script type='text/javascript'>
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
            request.CURRENT_USER = " . $currentUser . ";
            request.USER_CAN_SEE_ALL_CASES = '" . $userCanSeeAllCases . "';
            request.FILTER_PROCESS = " . $filterProcess . ";
            request.FILTER_USER = " . $filterUser . ";
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
        
// Register an API method that will empty the pipelined data, forcing an Ajax fetch on the next draw (i.e. `table.clearPipeline().draw()`)
$.fn.dataTable.Api.register('clearPipeline()', function () {
    return this.iterator('table', function (settings) {
        settings.clearCache = true;
    });
});


/**
 * Show Modal Different Nodes
 *
 * @param selectedRows
 * @return none
 *
 * by Cinthia Romero
 */
function showModalDifferentNodes(selectedRows)
{
    //Get Users per Case
    $.ajax({
        type: 'POST',
        url: '" . $apiHost . "/pstools/script/bulk-reassignment-get-users-list',
        data: {
            'data': JSON.stringify({
                selectedRows: selectedRows,
                differentNodes: 'YES'
            })
        },
        success: function (response) {
            var html = '<|input type=\'text\' style=\'display:none\' id=\'selectedRowsDifferentTasks\'/>';
            html += '<div>';
            html += '<div style=\'width:15%; float: left\'>';
            html += '<|label className=\'control-label\' htmlFor=\'reassignmentReasonDifferentTasks\'>';
            html += '<span className=\'textlabel\'>Reason:&nbsp;<span style=\'color:red;\'>*</span></span>';
            html += '</|label>';
            html += '</div>';
            html += '<div style=\'width:85%; float: left\'>';
            html += '<|textarea className=\'form-control\' rows=\'3\' id=\'reassignmentReasonDifferentTasks\' style=\'width: 100%\'></|textarea>';
            html += '</div>';
            html += '</div>';
            html += '<br>';
            html += '<br>';
            html += '<div>';
            html += '<table width=\'100%\' cellpadding=\'2\' cellspacing=\'2\'>';
            html += '<tr>';
            html += '<td colspan=\'5\'>&nbsp;</td>';
            html += '</tr>';
            html += '<tr>';
            html += '<th></th>';
            html += '<th width=\'10%\' style=\'border-bottom: 1px solid black\'><b>Case #</b></th>';
            html += '<th width=\'60%\' style=\'border-bottom: 1px solid black\'><b>Current Task</b></th>';
            html += '<th width=\'30%\' style=\'border-bottom: 1px solid black\'><b>Reassign User&nbsp;<span style=\'color:red;\'>*</span></b></th>';
            html += '</tr>';
            for (var caseSelected = 0; caseSelected < Object.keys(response).length; caseSelected++) {
                html += '<tr>';
                html += '<td colspan=\'5\' style=\'font-size: 5px;\'>&nbsp;</td>';
                html += '</tr>';
                html += '<tr>';
                html += '<td><|input type=\'text\' style=\'display:none\' id=\'delegationId_' + caseSelected + '\' value=\'' + response[caseSelected]['DELEGATION_ID'] + '\'/></td>';
                html += '<td width=\'10%\' style=\'text-align: center;\'>' + response[caseSelected]['CASE_NUMBER'] + '</td>';
                html += '<td width=\'60%\'>' + response[caseSelected]['TASK_TITLE'] + '</td>';
                html += '<td width=\'30%\' style=\'text-align: center;\'>';
                html += '<|select className=\'form-control\' style=\'width: 90%\' id=\'userList_' + caseSelected + '\' name=\'userList_' + caseSelected + '\'>';
                html += '<|option value=\'\'>- Select -</|option>';
                $.each(response[caseSelected]['USERS_TO_REASSIGN'], function (a, user) {
                    html += '<|option value=\'' + user['USER_ID'] + '\'>' + user['USER_FULLNAME'] + '</|option>';
                });
                html += '</|select>';
                html += '</td>';
                html += '</tr>';
            }
            html += '</table>';
            html += '</div>';
            $('#modalDifferentTasksBody').html(html);
            $('#selectedRowsDifferentTasks').val(JSON.stringify(selectedRows));
            $('#reassignmentModalDifferentTasks').modal({
                backdrop: 'static',
                keyboard: false
            });
        }
    });
}

/**
 * Show Modal Cases Same Task
 *
 * @param selectedRows
 * @return none
 *
 * by Cinthia Romero
 */
function showModalCasesSameTask(selectedRows)
{
    $('#userListSameTask').find('option').remove();
    $('#selectedRowsSameTask').val(JSON.stringify(selectedRows));
    //Populate User Field
    $.ajax({
        type: 'GET',
        url: '" . $apiHost . "/pstools/script/bulk-reassignment-get-users-list',
        data: {
            'data': JSON.stringify({
                selectedRows: selectedRows,
                differentNodes: 'NO'
            })
        },
        success: function (response) {
            //Add empty option
            $('#userListSameTask').append($('<|option>', {
                value: '',
                text: '- Select -'
            }));
            $.each(response, function (a, user) {
                $('#userListSameTask').append($('<|option>', {
                    value: user.USER_ID,
                    text: user.USER_FULLNAME
                }));
            });
            $('#reassignmentModalSameTask').modal({
                backdrop: 'static',
                keyboard: false
            });
        }
    });
}

/**
 * Cancel Reassignment
 *
 * @param string reassignmentType
 * @return none
 *
 * by Cinthia Romero
 */
function cancelReassignment(reassignmentType)
{
    //Remove all error labels
    $('.pmdynaform-message-error').remove();
    //Check reassignment type
    if (reassignmentType == 'DIFFERENT_TASKS') {
        //Clean values
        $('#selectedRowsDifferentTasks').val('');
        $('#reassignmentReasonDifferentTasks').val('');
        //Remove red border
        $('#reassignmentReasonDifferentTasks').css('border-color', '#ccc');
        //Hide modal
        $('#reassignmentModalDifferentTasks').modal('hide');
    } else {
        //Clean values
        $('#userListSameTask').val('');
        $('#selectedRowsSameTask').val('');
        $('#reassignmentReasonSameTask').val('');
        //Remove red border
        $('#reassignmentReasonSameTask').css('border-color', '#ccc');
        $('#userListSameTask').css('border-color', '#ccc');
        //Hide modal
        $('#reassignmentModalSameTask').modal('hide');
    }
}

/**
 * Confirm Reassignment
 *
 * @param string reassignmentType
 * @return none
 *
 * by Cinthia Romero
 */
function confirmReassignment(reassignmentType)
{
    //Set error mesaage HTML
    var errorHTML = '<div class=\'pmdynaform-message-error\'>';
    errorHTML += '<span>This field is required.</span>';
    errorHTML += '</div>';

    //Remove all error labels
    $('.pmdynaform-message-error').remove();

    //Initialize values
    var allFieldsOk = true;
    var casesReassignmentData = [];
    var reassignmentReason = '';

    //Check reassignment type
    if (reassignmentType == 'DIFFERENT_TASKS') {
        //Remove red border
        $('#reassignmentReasonDifferentTasks').css('border-color', '#ccc');
        //Check if all tasks has a user selected
        var casesSelected = JSON.parse($('#selectedRowsDifferentTasks').val());
        for (var i = 0; i < Object.keys(casesSelected).length; i++) {
            var newUserSelected = $('#userList_' + i).val();
            if (newUserSelected == '') {
                $('#userList_' + i).css('border-color', 'red');
                $('#userList_' + i).parent().append(errorHTML);
                allFieldsOk = false;
            } else {
                var delegationInfo = casesSelected[i][0].split('_');
                var delegationOldUser = delegationInfo[3];
                var selectedUserPerTask = {
                    'CASE_NUMBER': casesSelected[i][1],
                    'DELEGATION_ID' : $('#delegationId_' + i).val(),
                    'OLD_USER': delegationOldUser,
                    'NEW_USER': newUserSelected,
                    'USER_LOGGED': " . $currentUser . "
                };
                casesReassignmentData.push(selectedUserPerTask);
                $('#userList_' + i).css('border-color', '#ccc');
            }
        }
        //Check if the user has entered a reason
        reassignmentReason = $('#reassignmentReasonDifferentTasks').val();
        if (reassignmentReason == '') {
            allFieldsOk = false;
            $('#reassignmentReasonDifferentTasks').css('border-color', 'red');
            $('#reassignmentReasonDifferentTasks').parent().append(errorHTML);
        }
    } else {
        //Remove red border
        $('#userListSameTask').css('border-color', '#ccc');
        $('#reassignmentReasonSameTask').css('border-color', '#ccc');
        //Verify correct values
        var userSelected = $('#userListSameTask').val();
        if (userSelected == '') {
            allFieldsOk = false;
            $('#userListSameTask').css('border-color', 'red');
            $('#userListSameTask').parent().append(errorHTML);
        } else {
            var casesSelected = JSON.parse($('#selectedRowsSameTask').val());
            for (var i = 0; i < Object.keys(casesSelected).length; i++) {
                var delegationInfo = casesSelected[i][0].split('_');
                var delegationId = delegationInfo[0];
                var delegationOldUser = delegationInfo[3];
                var selectedUserPerTask = {
                    'CASE_NUMBER': casesSelected[i][1],
                    'DELEGATION_ID' : delegationId,
                    'OLD_USER': delegationOldUser,
                    'NEW_USER': userSelected,
                    'USER_LOGGED': " . $currentUser . "
                };
                casesReassignmentData.push(selectedUserPerTask);
            }
        }
        reassignmentReason = $('#reassignmentReasonSameTask').val();
        if (reassignmentReason == '') {
            allFieldsOk = false;
            $('#reassignmentReasonSameTask').css('border-color', 'red');
            $('#reassignmentReasonSameTask').parent().append(errorHTML);
        }
    }
    //Check if all values have been correctly entered
    if (allFieldsOk) {
        $.blockUI({ message: '<div id=\'loader\'><img src=\'/storage/1394/loadingIMG.gif\'></div>', baseZ: 1500});
        $.ajax({
            type: 'POST',
            url: '" . $apiHost . "/pstools/script/bulk-reassignment-reassign-tasks',
             data: {
                'data': JSON.stringify({
                    casesToReassign: casesReassignmentData,
                    reassignmentReason: reassignmentReason
                })
            },
            success: function (response) {
                $.unblockUI();
                cancelReassignment(reassignmentType);
                var tableCases = $('#tableCases').DataTable();
                tableCases.clearPipeline().draw();
                tableCases.column(0).checkboxes.deselectAll();
    
                if (!response) {
                    $('#alertMessage').removeClass();
                    $('#alertMessage').addClass('alert alert-danger');
                    $('#alertMessage').html('<p>Something went wrong, please contact your system administrator</p>');
                    $('#alertMessage').fadeTo(2000, 500).slideUp(500, function() {
                        $('#alertMessage').slideUp(500);
                    });
                } else {
                    $('#alertMessage').removeClass();
                    $('#alertMessage').addClass('alert alert-success');
                    $('#alertMessage').html('<p>Selected cases were successfully reassigned</p>');
                    $('#alertMessage').fadeTo(2000, 500).slideUp(500, function() {
                        $('#alertMessage').slideUp(500);
                    });
                }
            }
        });
    }    
}

/**
 * Reassign Cases
 *
 * @param none
 * @return none
 *
 * by Cinthia Romero
 */
function reassignCases() 
{
    //Get all selected rows
    var tableCases = $('#tableCases').DataTable();
    var selectedRows = [];
    tableCases.$('input[type=\'checkbox\']').each(function() {
        // If checkbox is checked
        if (this.checked) {
            var tr = $(this).closest('tr');
            rowSelected = tr.index();
            var rowSelectedData = tableCases.row(rowSelected).data();
            selectedRows.push(rowSelectedData);
        };
    });
    //Check if there is at least one row selected if not show error message
    if (selectedRows.length > 0) {
        //Check if all nodes are the same
        var nodeId = '';
        var differentNodes = 'NO';
        $.each(selectedRows, function (rowIndex, delegationId) {
            //Get only task node id
            var currentNode = delegationId[0].split('_')[2];
            //Check if current node is different from the previous one
            if (nodeId != '' && currentNode != nodeId) {
                differentNodes = 'YES';
            } else {
                nodeId = currentNode;
            }
        });
        if (differentNodes == 'YES') {
            showModalDifferentNodes(selectedRows);
        } else {
            showModalCasesSameTask(selectedRows);
        }
    } else {
        $('#alertMessage').removeClass();
        $('#alertMessage').addClass('alert alert-danger');
        $('#alertMessage').html('<p>Please select at least one row</p>');
        $('#alertMessage').fadeTo(2000, 500).slideUp(500, function() {
            $('#alertMessage').slideUp(500);
        });
    }
}
$(document).ready(function() {
    $('#errorAlert').hide();
    
    var tableCases = $('#tableCases').DataTable({
        order: [1, 'desc'],
        pagination: true,
        pageLength: 10,
        processing: true,
        serverSide: true,
        scrollCollapse: true,
        language: {
            emptyTable: 'No data available in table or you do not have permissions granted.',
            sProcessing: '<center><svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' preserveAspectRatio=\'xMidYMid\' width=\'200\' height=\'200\' style=\'shape-rendering: auto; display: block;\' xmlns:xlink=\'http://www.w3.org/1999/xlink\'><g><circle stroke-linecap=\'round\' fill=\'none\' stroke-dasharray=\'50.26548245743669 50.26548245743669\' stroke=\'#9b1012\' stroke-width=\'8\' r=\'32\' cy=\'50\' cx=\'50\'><animateTransform values=\'0 50 50;360 50 50\' keyTimes=\'0;1\' dur=\'1s\' repeatCount=\'indefinite\' type=\'rotate\' attributeName=\'transform\'></animateTransform></circle><g></g></g><!-- [ldio] generated by https://loading.io --></svg></center>'
        },
        ajax: $.fn.dataTable.pipeline({
            url: '" . $apiHost . "/pstools/script/bulk-reassignment-get-data',
            pages: 1 // number of pages to cache
        }),
        columnDefs: [
            {
                'targets': [0],
                'bSortable': false,
                'checkboxes': {
                    'selectRow': true
                }
            },
            {
                'targets': [1],
                'visible': true,
                'bSortable': true
            },
            {
                'targets': [2],
                'visible': true,
                'bSortable': true
            },
            {
                'targets': [3],
                'visible': true,
                'bSortable': true
            },
            {
                'targets': [4],
                'visible': true,
                'bSortable': true
            }
        ],
        'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]]
    });

    $('#tableCases').on('page.dt', function (e) {
        //Uncheck all rows when change the page
        tableCases.column(0).checkboxes.deselectAll();
    });
});
</script>";
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];