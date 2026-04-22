<?php 
/****************************  
 * PE - Dashboard Screen
 *
 * by Cinthia Romero 
 * Modified by Telmo Chiri
 * Modified by Elmer Orihuela
 * Modified by Favio Mollinedo
 ***************************/
 require_once("/Northleaf_PHP_Library.php");

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');

//Generate table header
$html = "";
$html .= "<head>";
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
foreach($getLibraries as $library ) {
    $libraries[$library['file_name']] = $library['id'];
}

//Generate table header
$html = "";
$html .= "<head>";
//Add css
$cssToLoad = [
    'bootstrap.min.css',
    'font-awesome.min.css',
    'jquery.dataTables.min.css',
    'cssLibraryNorthleaf_3.css'
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
    'jquery.blockUI.js',
    'moment.min.2.24.js'
];

foreach($jsToLoad as $js){
    $html .= $libraries[$js] ? "<script type='text/javascript' src='/storage/" . $libraries[$js] . "/$js'></script>" : "<meta name='$js' content='Library not loaded'>";
}
//$html .= "<link rel='stylesheet' type='text/css' href='https://getbootstrap.com/docs/3.3/dist/css/bootstrap.min.css' />";
$html .= "<style>
[type='date'] {
    position: relative;
    width: 100% !important; 
    height: 34px;
    color: white;
}
input[type='date']::-webkit-calendar-picker-indicator {
    color: rgba(0, 0, 0, 0);
    opacity: 1;
    display: block;
    background-image: url('data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjxzdmcgaWQ9Ikljb25zIiB2aWV3Qm94PSIwIDAgMjQgMjQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHN0eWxlPi5jbHMtMXtmaWxsOiMyMzIzMjM7fTwvc3R5bGU+PC9kZWZzPjxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTIwLDJIMTlWMWExLDEsMCwwLDAtMiwwVjJIN1YxQTEsMSwwLDAsMCw1LDFWMkg0QTQsNCwwLDAsMCwwLDZWMjBhNCw0LDAsMCwwLDQsNEgyMGE0LDQsMCwwLDAsNC00VjZBNCw0LDAsMCwwLDIwLDJabTIsMThhMiwyLDAsMCwxLTIsMkg0YTIsMiwwLDAsMS0yLTJWNkEyLDIsMCwwLDEsNCw0SDVWNUExLDEsMCwwLDAsNyw1VjRIMTdWNWExLDEsMCwwLDAsMiwwVjRoMWEyLDIsMCwwLDEsMiwyWiIvPjxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTE5LDdINUExLDEsMCwwLDAsNSw5SDE5YTEsMSwwLDAsMCwwLTJaIi8+PHBhdGggY2xhc3M9ImNscy0xIiBkPSJNNywxMkg1YTEsMSwwLDAsMCwwLDJIN2ExLDEsMCwwLDAsMC0yWiIvPjxwYXRoIGNsYXNzPSJjbHMtMSIgZD0iTTcsMTdINWExLDEsMCwwLDAsMCwySDdhMSwxLDAsMCwwLDAtMloiLz48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0xMywxMkgxMWExLDEsMCwwLDAsMCwyaDJhMSwxLDAsMCwwLDAtMloiLz48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0xMywxN0gxMWExLDEsMCwwLDAsMCwyaDJhMSwxLDAsMCwwLDAtMloiLz48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0xOSwxMkgxN2ExLDEsMCwwLDAsMCwyaDJhMSwxLDAsMCwwLDAtMloiLz48cGF0aCBjbGFzcz0iY2xzLTEiIGQ9Ik0xOSwxN0gxN2ExLDEsMCwwLDAsMCwyaDJhMSwxLDAsMCwwLDAtMloiLz48L3N2Zz4=');
    width: 18px;
    height: 18px;
    padding-right: 12px;
    border-width: thin;
    cursor: pointer;
}
[type='date']:before {
    position: absolute;
    top: 0px; left: 10px;
    content: attr(data-date);
    display: inline-block;
    color: black;
}
[type='date']::-webkit-datetime-edit, [type='date']::-webkit-inner-spin-button, [type='date']::-webkit-clear-button {
    display: none;
}
[type='date']::-webkit-calendar-picker-indicator {
    position: absolute;
    top: 3px;
    right: 0;
    color: black;
    opacity: 1;
}
a:link {
  color: #9B1012;
}
a:visited {
  color: #9B1012;
}
a:hover {
  color: #9B1012;
}
a:active {
  color: #9B1012;
}
.tableClass>tbody>tr>td,
.tableClass>thead>tr>th {
    padding-top: 4px;
    padding-bottom: 4px;
    padding-left: 6px;
    padding-right: 6px;
    font-size: 90%;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select:none;
    user-select:none;
}
.tableClass tr:hover {
    background: #e8e8e8;
    cursor: pointer;
}
.tableClass>thead {
    color: #FFF;
    background: #9B1012;
}
.tableClass>thead:hover,
.tableClass>thead>tr:hover {
    color: #FFF;
    background: #9B1012;
}
.radioStyle {
    accent-color: #84171A;
    margin-top: 1em;
    margin-bottom: 1em;
}
.loader {
  width: 16px;
  height: 16px;
  border: 3px solid #FFF;
  border-radius: 50%;
  display: block;
  position: relative;
  box-sizing: border-box;
  animation: rotation 1s linear infinite;
}
.loader::after {
  content: '';  
  box-sizing: border-box;
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 3px solid;
  border-color: #FF3D00 transparent;
}

@keyframes rotation {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
} 
</style>";
$html .= "</head>";
$html .= "<body class='bodyStyle'>";
$html .= "<div class='dataTables_wrapper'>";
$html .= "<div class='row'>";
$html .= "<div class='col-md-12'>";
$html .= "<table id='tableCases' width='100%'>";
$html .= "<thead>";
$html .= "<tr>";
$html .= "<th>Case #</th>";
$html .= "<th></th>";
$html .= "<th>Edit<span>&nbsp;</span>Target Date</th>";
$html .= "<th>Documents</th>";
$html .= "<th>Re-approval</th>";
$html .= "</tr>";
$html .= "</thead>";
$html .= "</table>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "<!--Modal to edit target date-->
<div class = 'modal fade' id='editDateModal'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header modalTitleStyle'>
                <center><b>EDIT TARGET DATE</b></center>
            </div>
            <div class='modal-body'>
                <div class='row'>
                    <|input type='text' style='display:none' id='appUid'/>
                    <|input type='text' style='display:none' id='caseNumber'/>
                    <div class='col-sm-12 col-md-12 col-lg-12 ' style='display:flex;'>
                        <div class='col-sm-4 col-md-4 col-lg-4'>
                            <|label class='control-label' for='currentDate'>
                                <span class='textlabel'>Current target date</span>
                            </|label>
                        </div>
                        <div class='col-sm-8 col-md-8 col-lg-8' style='padding-right:0;' >
                            <div class='input-group' name='currentDate' id='currentDate'>
                                <|input type='text' class='form-control' disabled/>
                                <span class='input-group-addon'>
                                    <?xml version='1.0' ?><svg id='Icons' style='width:18px;' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><defs><style>.cls-1{fill:#232323;}</style></defs><path class='cls-1' d='M20,2H19V1a1,1,0,0,0-2,0V2H7V1A1,1,0,0,0,5,1V2H4A4,4,0,0,0,0,6V20a4,4,0,0,0,4,4H20a4,4,0,0,0,4-4V6A4,4,0,0,0,20,2Zm2,18a2,2,0,0,1-2,2H4a2,2,0,0,1-2-2V6A2,2,0,0,1,4,4H5V5A1,1,0,0,0,7,5V4H17V5a1,1,0,0,0,2,0V4h1a2,2,0,0,1,2,2Z'/><path class='cls-1' d='M19,7H5A1,1,0,0,0,5,9H19a1,1,0,0,0,0-2Z'/><path class='cls-1' d='M7,12H5a1,1,0,0,0,0,2H7a1,1,0,0,0,0-2Z'/><path class='cls-1' d='M7,17H5a1,1,0,0,0,0,2H7a1,1,0,0,0,0-2Z'/><path class='cls-1' d='M13,12H11a1,1,0,0,0,0,2h2a1,1,0,0,0,0-2Z'/><path class='cls-1' d='M13,17H11a1,1,0,0,0,0,2h2a1,1,0,0,0,0-2Z'/><path class='cls-1' d='M19,12H17a1,1,0,0,0,0,2h2a1,1,0,0,0,0-2Z'/><path class='cls-1' d='M19,17H17a1,1,0,0,0,0,2h2a1,1,0,0,0,0-2Z'/></svg>
                                </span>
                            </div>
                        </div>
                    </div>
                    <br>
                    <br>
                    <div class='col-sm-12 col-md-12 col-lg-12 ' style='display:flex;'>
                        <div class='col-sm-4 col-md-4 col-lg-4'>
                            <|label class='control-label' for='newDateGroup'>
                                <span class='textlabel'>New target date <span style='color:red;'>*</span></span>
                            </|label>
                        </div>
                        <div class='col-sm-8 col-md-8 col-lg-8' style='padding-right:0;' >
                            <|input type='date' class='form-control' name='newDate'  id='newDate' data-date='' data-date-format='MM/DD/YYYY' >
                        </div>
                    </div>
                    <br>
                    <br>
                    <div class='col-sm-12 col-md-12 col-lg-12 ' style='display:flex;'>
                        <div class='col-sm-4 col-md-4 col-lg-4'>
                            <|label class='control-label' for='reason'>
                                <span class='textlabel'>Reason <span style='color:red;'>*</span></span>
                            </|label>
                        </div>
                        <div class='col-sm-8 col-md-8 col-lg-8' style='padding-right:0;' >
                            <|textarea class='form-control' rows='3' id='reason'></|textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <|button type='button' class='btn btn-primary' onclick='editTargetDateConfirm();'>Edit</|button>
                <|button type='button' class='btn btn-secondary' onclick='cancelEdit();'>Close</|button>
            </div>
        </div>
    </div>
</div>";
$html .= "<!-- Modal error -->
<div class='alert alert-danger' id='errorAlert'></div>";
$html .= "<!--Modal File History-->
<div class = 'modal fade' id='fileHistoryModal'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header modalTitleStyle'>
                <center><b>Documents</b></center>
            </div>
            <div class='modal-body' id='fileHistoryBody'>
            </div>
            <div class='modal-footer'>
                <|button type='button' class='btn btn-secondary' onclick='closeFileHistory();'>Close</|button>
            </div>
        </div>
    </div>
</div>";
$html .= "<!--Modal Re-approval-->
<div class = 'modal fade' id='reapprovalModal'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <div class='modal-header modalTitleStyle'>
                <center><b>Re-approval</b></center>
            </div>
            <div class='modal-body' style='text-align:center;'>
                <|input type='text' style='display:none' id='caseNumberToReapproval'/>
                <|input type='text' style='display:none' id='dataToSaveReapproval'/>
                Are you sure to return the case <span id='case-selected' style='font-weight:bold;'></span> to the DT.01 task?<span style='color:red;'>*</span><br>
                <div id='content-re-approval-question'>
                    <|input type='radio' class='radioStyle' id='Yes-Reapproval' name='re-approval-question' value='YES' onclick='$(\"#content-re-approval\").show()'>
                    <|label for='Yes-Reapproval'>YES</|label>&nbsp;
                    <|input type='radio' class='radioStyle' id='No-Reapproval' name='re-approval-question' value='NO' onclick='closeModalReapproval();'>
                    <|label for='No-Reapproval'>NO</|label><br>
                </div>
                <div id='content-re-approval' style='display:none;'>
                    <div id='content-ic-approvel' style='border:2px solid #c9c9c9; border-radius:10px; padding:12px;'>
                        Do you want to change the amounts of the IC approval?<span style='color:red;'>*</span> &nbsp;
                        <span id='content-re-ic-approval-question'>
                            <|input type='radio' class='radioStyle' id='Yes-Change-IC' name='re-ic-approval-question' value='YES'>
                            <|label for='Yes-Change-IC'>YES</|label>&nbsp;
                            <|input type='radio' class='radioStyle' id='No-Change-IC' name='re-ic-approval-question' value='NO'>
                            <|label for='No-Change-IC'>NO</|label>
                        </span>
                    </div>
                    <br>
                    <p><small><i>If you have selected \"Yes\" for \"Do you want to change the amounts of the IC approval?\", please provide a clear reasoning as this will be shared with the IC members</i></small></p>
                    <div class='row'>
                        <div class='col-sm-12 col-md-12 col-lg-12 ' style='margin-top:8px;'>
                            <div class='col-sm-12 col-md-12 col-lg-12'>
                                <|label class='control-label' for='reason'>
                                    <span class='textlabel'>Reason<span style='color:red;'>*</span></span>
                                </|label>
                            </div>
                            <div class='col-sm-12 col-md-12 col-lg-12' style='padding-right:0;' >
                                <|textarea class='form-control' rows='3' id='reasonReapproval'></|textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='alert alert-danger' role='alert' id='alert-ic-reapproval' style='display:none;' >
                    The request cannot proceed because the current case has one or more pending tasks in the IC approval process
                </div>
                <div class='alert alert-danger' role='alert' id='alert-ic-reapproval-task' style='display:none;' >
                    The request cannot proceed because the IC approval process has one or more pending approvals
                </div>
            </div>
            <div class='modal-footer'>
                <|button type='button' class='btn btn-secondary' disabled id='spinner-reapproval' style='display:none;'><div class='loader'></div></|button>
                <|button type='button' class='btn btn-primary' onclick='reapprovalConfirm();' id='button-confirm-reapproval' >Confirm</|button>
                <|button type='button' class='btn btn-secondary' onclick='closeModalReapproval();'>Close</|button>
            </div>
        </div>
    </div>
</div>";
$html .= "</body>";

//Set datatable javascript
//var  redirectUrl = '" . $environmentBaseUrl . "requests/' + requestId + '/task/' + taskId + '/screen/' + nodeVersionId;
$html .= "
<script type='text/javascript'>
/**
 * Show Thread Dynaform
 *
 * @param string requestId
 * @param string taskId
 * @param string nodeVersionId
 * @return none
 *
 * by Cinthia Romero
 */
function showThreadDynaform(requestId, taskId, nodeVersionId)
{
    var  redirectUrl = '" . $environmentBaseUrl . "case-overview/preview-screen/' + requestId + '/task/' + taskId + '/screen/' + nodeVersionId;
    window.open(redirectUrl);
}
/**
 * Show Current User Tasks
 *
 * @param string requestId
 * @param string taskId
 * @return none
 *
 * by Elmer Orihuela
 */
function showCurrentUserTasks(requestId, taskId, nodeVersionId)
{
    var  redirectUrl = '" . $environmentBaseUrl . "tasks/' + taskId + '/edit';
    window.open(redirectUrl);
}

/**
 * Edit target date
 *
 * @param string appUid
 * @param int caseNumber
 * @param string targetDate
 * @return none
 *
 * by Cinthia Romero
 */
function editTargetDate(caseNumber, targetDate)
{
    $('#caseNumber').val(caseNumber);
    $('#currentDate input').val(changeFormatDate(targetDate));
    $('#newDate').val(targetDate);
    $('#newDate').attr('data-date', moment(targetDate, 'YYYY-MM-DD').format('MM/DD/YYYY'));
    $('#editDateModal').modal({
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
}
/**
 * Edit Target Date Confirm
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri
 */
function editTargetDateConfirm()
{
    var appUid = $('#appUid').val();
    var caseNumber = $('#caseNumber').val();
    var currentDate = $('#currentDate input').val();
    var newDate = $('#newDate').val();
    var reason = $('#reason').val();
    $('#newDate').css('border-color', '#ccc');
    $('#reason').css('border-color', '#ccc');
    
    if (newDate != '' && reason != '') {
        $.ajax({
            type: 'POST',
            url: '" . $apiHost . "/pstools/script/dashboard-edit-target-date',
            data: {
                'data': JSON.stringify({
                    caseNumber: caseNumber,
                    currentDate: currentDate,
                    newDate: newDate,
                    reason: reason,
                    currentUserId: '" . $data["currentUserId"] . "'
                })
            },
            beforeSend: function() {
                $.blockUI({ message: '<div id=\'loader\'><img src=\'/storage/" . $libraries['loadingIMG.gif'] . "/loadingIMG.gif\'></div>', baseZ: 1500});
            },
            success: function (response) {
                //Clean modal values
                $('#appUid').val('');
                $('#caseNumber').val('');
                $('#currentDate').val('');
                $('#newDate').val('');
                $('#reason').val('');
                $('#editDateModal').modal('hide');
                $('#tableCases').DataTable().ajax.reload();
                if (!response.status) {
                    $('#errorAlert').html('<p>' + response.errorMessage + '</p>');
                    $('#errorAlert').fadeTo(2000, 500).slideUp(500, function() {
                        $('#errorAlert').slideUp(500);
                    });
                } 
                var tableCases = $('#tableCases').DataTable();
                tableCases.clearPipeline().draw();
            },
            error: function (jqXhr, textStatus, errorMessage) {
                console.log('error======>', errorMessage);
            },
            complete: function() {
                $.unblockUI();
            }
        });
    } else {
        if (newDate == '') {
            $('#newDate').css('border-color', 'red');
        }
        if (reason == '') {
            $('#reason').css('border-color', 'red');
        }
    }
}
/**
 * Cancel Edit
 *
 * @param none
 * @return none
 *
 * by Cinthia Romero
 */
function cancelEdit()
{
    $('#appUid').val('');
    $('#caseNumber').val('');
    $('#newDate').val($('#currentDate input').val());
    $('#newDate').css('border-color', '#ccc');
    $('#reason').val('');
    $('#reason').css('border-color', '#ccc');
    $('#editDateModal').modal('hide');
}
/***
* Change Format Date
* by Telmo Chiri
***/
function changeFormatDate(date) {
    if (date != null) {
        let date_format = date.split('-');
        return date_format[1] + '/' + date_format[2] + '/' + date_format[0];
    }
    return '';
}

/**
 * Show File History
 *
 * @param int caseNumber
 * @return none
 *
 * by Telmo Chiri
 */
function showFileHistory(caseNumber)
{
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
/**
 * Close File History Modal
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri
 */
function closeFileHistory()
{
    $('#fileHistoryModal').modal('hide');
}
/**
 * Show Modal Re-approval
 *
 * @param int dataCase
 * @return none
 *
 * by Telmo Chiri
 */
function showModalReapproval(caseNumber, caseTitle, dataToSave)
{
    $('#caseNumberToReapproval').val(caseNumber);
    $('#dataToSaveReapproval').val(dataToSave);
    $('#reasonReapproval').val('');
    $('#case-selected').html(caseTitle);
    $('input[name=\"re-approval-question\"]').attr('checked', false);
    $('input[name=\"re-ic-approval-question\"]').attr('checked', false);
    $('#content-re-approval').hide();
    $('#reapprovalModal').modal({
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
}
/**
 * Close Re-approval Modal
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri
 * Modified by Favio Mollinedo
 */
function closeModalReapproval()
{
    $('#caseNumberToReapproval').val('');
    $('#dataToSaveReapproval').val('');
    $('#reasonReapproval').val('');
    $('input[name=\"re-approval-question\"]').attr('checked', false);
    $('input[name=\"re-ic-approval-question\"]').attr('checked', false);
    $('#content-re-approval').hide();
    $('#reasonReapproval').css('border', '1px solid #FFF');
    $('#content-re-approval-question').css('border', '1px solid #FFF');
    $('#content-re-ic-approval-question').css('border', '1px solid #FFF');
    $('#reapprovalModal').modal('hide');
    $('#alert-ic-reapproval').hide();
    $('#alert-ic-reapproval-task').hide();
    $('#spinner-reapproval').hide();
}
/**
 * Re-Approval Confirm
 *
 * @param none
 * @return none
 *
 * by Telmo Chiri
 */
function reapprovalConfirm()
{
    let caseNumber = $('#caseNumberToReapproval').val();
    let dataToSave = $('#dataToSaveReapproval').val();
    let reApproval = $('input[name=\"re-approval-question\"]:checked').val()
    let icApproval = $('input[name=\"re-ic-approval-question\"]:checked').val()
    let reason = $('#reasonReapproval').val();
    $('#reasonReapproval').css('border', '1px solid #FFF');
    $('#content-re-approval-question').css('border', '1px solid #FFF');
    $('#content-re-ic-approval-question').css('border', '1px solid #FFF');
    // Validations
    let ok = true;
    if (typeof(reApproval) == 'undefined') {
        ok = false;
        $('#content-re-approval-question').css('border', '1px solid red');
    }
    if (typeof(icApproval) == 'undefined') {
        ok = false;
        $('#content-re-ic-approval-question').css('border', '1px solid red');
    }
    if (reason == '') {
        ok = false;
        $('#reasonReapproval').css('border', '1px solid red');
    }
    if (caseNumber == '') {
        ok = false;
    }
    if (ok) {
        $.ajax({
            type: 'POST',
            url: '" . $apiHost . "/pstools/script/dashboard-re-approval',
            data: {
                'data': JSON.stringify({
                    caseNumber: caseNumber,
                    icReApproval: icApproval,
                    reason: reason,
                    currentUserId: '" . $data["currentUserId"] . "',
                    dataToSave: dataToSave
                })
            },
            beforeSend: function() {
                $.blockUI({ message: '<div id=\'loader\'><img src=\'/storage/" . $libraries['loadingIMG.gif'] . "/loadingIMG.gif\'></div>', baseZ: 1500});
            },
            success: function (response) {
                //Clean and close modal values
                closeModalReapproval();
                // Reload DataTable
                $('#tableCases').DataTable().ajax.reload();
                if (!response.status) {
                    $('#errorAlert').html('<p>' + response.errorMessage + '</p>');
                    $('#errorAlert').fadeTo(2000, 500).slideUp(500, function() {
                        $('#errorAlert').slideUp(500);
                    });
                } 
                var tableCases = $('#tableCases').DataTable();
                tableCases.clearPipeline().draw();
                
            },
            error: function (jqXhr, textStatus, errorMessage) {
                console.log('error======>', errorMessage);
            },
            complete: function() {
                $.unblockUI();
            }
        });
    }
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
                    console.log('here');
                    console.log(json);
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
    } );
} );

$(document).ready(function() {
    //Get window height
    var screenHeight = 768;
    if (typeof top.innerHeight != 'undefined') {
        screenHeight = top.innerHeight;
    }
    screenHeight = screenHeight - 350;
    document.documentElement.style.overflowY = 'hidden';
    var tableCases = $('#tableCases').DataTable({
        order: [0, 'desc'],
        scrollX: true,
        scrollY: screenHeight,
        pagination: true,
        pageLength: 10,
        processing: true,
        serverSide: true,
        scrollCollapse: true,
        ajax: $.fn.dataTable.pipeline({
            //url: '" . $apiHost . "/pstools/script/dashboard-get-data',
            url: '/api/1.0/execution/script/d8e64493-03dc-4de7-b3d1-e93bdd361140',
            pages: 1 // number of pages to cache
        }),
        columnDefs: [
            {
                'targets': [0],
                'visible': true,
                'class': 'classVerticalContent caseNumber',
                'width': '5%'
            },
            {
                'targets': [1],
                'visible': true,
                'width': '95%',
                'bSortable': false,
                'mRender': function (data, type, rowData) {
                    return data;
                }
            },
            {
                'targets': [2],
                'visible': true,
                'class': 'classVerticalContent ',
                'width': '6%',
                'bSortable': false,
                'mRender': function (data, type, rowData) {
                    var showIcon = '';
                    if (data == true) {
                        showIcon = '<span class=\'iconClass\' title=\'Edit Target Funding Date\' aria-hidden=\'true\' onclick=\'editTargetDate(\"'+rowData[0]+'\", \"'+rowData[3]+'\")\'><?xml version=\'1.0\' ?><svg class=\'feather feather-edit\' fill=\'none\' height=\'24\' stroke=\'currentColor\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' viewBox=\'0 0 24 24\' width=\'24\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7\'/><path d=\'M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z\'/></svg></span>';
                    }
                    return showIcon;
                }
            },
            {
                'targets': [3],
                'visible': true,
                'class': 'classVerticalContent ',
                'width': '6%',
                'bSortable': false,
                'mRender': function (data, type, rowData) {
                    let showIcon = '<span class=\'iconClass\' title=\'Show File History\' aria-hidden=\'true\' onclick=\'showFileHistory(\"'+rowData[0]+'\")\'><?xml version=\'1.0\' encoding=\'utf-8\'?><svg fill=\'#9B1012\' width=\'24px\' height=\'24px\' viewBox=\'0 0 20 20\' xmlns=\'http://www.w3.org/2000/svg\'><path d=\'M13.981 2H6.018s-.996 0-.996 1h9.955c0-1-.996-1-.996-1zm2.987 3c0-1-.995-1-.995-1H4.027s-.995 0-.995 1v1h13.936V5zm1.99 1l-.588-.592V7H1.63V5.408L1.041 6C.452 6.592.03 6.75.267 8c.236 1.246 1.379 8.076 1.549 9 .186 1.014 1.217 1 1.217 1h13.936s1.03.014 1.217-1c.17-.924 1.312-7.754 1.549-9 .235-1.25-.187-1.408-.777-2zM14 11.997c0 .554-.449 1.003-1.003 1.003H7.003A1.003 1.003 0 0 1 6 11.997V10h1v2h6v-2h1v1.997z\'/></svg></span>';
                    return showIcon;
                }
            },
            {
                'targets': [4],
                'visible': true,
                'class': 'classVerticalContent ',
                'width': '6%',
                'bSortable': false,
                'mRender': function (data, type, rowData) {
                    if (rowData[6] === true) { // USER_CAN_REAPPROVAL
                        let dataToSaveEncoded = btoa(rowData[5]);
                        let showIcon = '<span class=\'iconClass\' title=\'Reaproval Case\' aria-hidden=\'true\' onclick=\'showModalReapproval(\"'+rowData[0]+'\", \"'+rowData[4]+'\", \"'+dataToSaveEncoded+'\")\'><!DOCTYPE svg PUBLIC \'-//W3C//DTD SVG 1.1//EN\' \'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\'><svg fill=\'#9b1012\' height=\'24px\' width=\'24px\' version=\'1.1\' id=\'Filled_Icons\' xmlns=\'http://www.w3.org/2000/svg\' xmlns:xlink=\'http://www.w3.org/1999/xlink\' x=\'0px\' y=\'0px\' viewBox=\'0 0 24 24\' enable-background=\'new 0 0 24 24\' xml:space=\'preserve\'><g id=\'SVGRepo_bgCarrier\' stroke-width=\'0\'/><g id=\'SVGRepo_tracerCarrier\' stroke-linecap=\'round\' stroke-linejoin=\'round\'/><g id=\'SVGRepo_iconCarrier\'><g id=\'Rewind-Filled\'><path d=\'M1.98,12L15,2v5.71L23,2v20l-8-5.71V22L1.98,12z\'/></g></g></svg></span>';
                        return showIcon;
                    } else {
                        return '<span></span>';
                    }
                }
            }
        ],
        'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]],
        'drawCallback': function(settings) {
            $('thead th').removeClass('caseNumber');
        }
    });
    $('#errorAlert').hide();
    // Date Picker format
    $('#newDate').on('change', function() {
        this.setAttribute(
            'data-date',
            moment(this.value, 'YYYY-MM-DD')
            .format( this.getAttribute('data-date-format') )
        )
    }).trigger('change')

    $('input[name=\"re-ic-approval-question\"]').change(function() {
        $('#alert-ic-reapproval').hide();
        $('#alert-ic-reapproval-task').hide();
        $('#spinner-reapproval').show();
        $('#button-confirm-reapproval').show();
        const selectedValue = this.value;
        if (selectedValue == 'NO' || selectedValue == 'YES') {
            let caseNumber = $('#caseNumberToReapproval').val();
            $.ajax({
                type: 'POST',
                url: '" . $apiHost . "/pstools/script/dashboard-check-if-task-ic01-is-active',
                data: {
                    'data': JSON.stringify({
                        caseNumber: caseNumber
                    })
                },
                beforeSend: function() {
                    $('#button-confirm-reapproval').hide();
                },
                success: function (response) {
                    if(response.IC_active_task == 'NO') {
                        $('#button-confirm-reapproval').show();
                    }
                    if(response.IC_active_task == 'YES' && selectedValue == 'NO') {
                        $('#alert-ic-reapproval').show();
                    }
                    if(response.IC_active_task == 'YES' && selectedValue == 'YES') {
                        $('#button-confirm-reapproval').show();
                    }

                },
                error: function (jqXhr, textStatus, errorMessage) {
                    console.log('error======>', errorMessage);
                },
                complete: function() {
                    $('#spinner-reapproval').hide();
                }
            });
        } /*else if(this.value == 'YES') {
            $('#spinner-reapproval').hide();
        }*/
    });
});
</script>";
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];