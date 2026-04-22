<?php 
/**********************************
 * Cancel Case - Main Screen
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set Global Variables
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

//Set initial values
$currentUser = $data["currentUserId"] ?? '';
$filterProcess = $data["filterProcess"] ?? '';
$filterUser = $data["filterUser"] ?? '';

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
$html .= "<style>
/*#tableCases_filter { display: none; }*/
</style>";
$html .= "</head>";
$html .= "<body class='bodyStyle'>";
$html .= "<!-- Modal error -->";
$html .= "<div id='alertMessage'></div>";
$html .= "<!--Modal for Cancel Case-->";
$html .= "<div class = 'modal fade' id='modalCancelCaseReason'>";
$html .= "<div class='modal-dialog'>";
$html .= "<div class='modal-content'>";
$html .= "<div class='modal-header modalTitleStyle'>";
$html .= "<center><b>Cancel Case</b></center>";
$html .= "</div>";
$html .= "<div class='modal-body'>";
$html .= "<div class='row'>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12' style='display:flex;'>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12 toConfirm' style='text-align:center;'>
            Are you sure to cancel the case <span id='caseTitleToCancel'></span>?
        </div>";
$html .= "<div class='col-xs-2 col-sm-2 col-md-2 col-lg-2 toComplete' style='text-align: right'>";
$html .= "<span class='textlabel' htmlFor='cancelCaseReason'>Reason:&nbsp;<span style='color:red;'>*</span></span>";
$html .= "</div>";
$html .= "<div class='col-xs-10 col-sm-10 col-md-10 col-lg-10 toComplete' style='padding-right:0;' >";
    $html .= "<|select name='reasonOptions' id='reasonOptions' class='form-control'>
                    <|option value=''>Select a reason</|option>
                    <|option value='Deal declined'>Deal declined</|option>
                    <|option value='Deal lost'>Deal lost</|option>
                    <|option value='Legal/tax issues'>Legal/tax issues</|option>
                    <|option value='Duplicate case'>Duplicate case</|option>
                    <|option value='Other'>Other</|option>
                </|select>
                <div id='reasonOptionsError'></div>";
    $html .= "<|textarea class='form-control' rows='3' id='cancelCaseReason' style='margin-top:1rem;'></|textarea>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "<div class='modal-footer toConfirm'>
                <|button type='button' class='btn btn-primary' onclick='confirmedToCancel();'>Yes</|button>
                <|button type='button' class='btn btn-secondary' onclick='closeCancelCase();'>No</|button>
            </div>";
$html .= "<div class='modal-footer toComplete'>";
$html .= "<|button type='button' class='btn btn-primary' onclick='confirmCancelCase();'>Cancel Case</|button>";
$html .= "<|button type='button' class='btn btn-secondary' onclick='closeCancelCase();'>Close</|button>";
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
$html .= "<th>Case #</th>";
$html .= "<th>Case Title</th>";
$html .= "<th>Task</th>";
$html .= "<th>Current User</th>";
$html .= "<th></th>";
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
var dataCase = [];
var filterSearch = '';
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
            request.CURRENT_USER = '" . $currentUser . "';
            request.FILTER_PROCESS = '" . $filterProcess . "';
            request.FILTER_USER = '" . $filterUser . "';
            request.FILTER_SEARCH = $('#searchDataTable').val();
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
 * Show Modal Cancel Case
 * @param selectedRows
 * @return none
 *
 * by Telmo Chiri
 */
function showModalCancelCase(selectedRows)
{
    dataCase = selectedRows;
    //Set Title
    $('#caseTitleToCancel').html(dataCase.CASE_TITLE);
    $('.toConfirm').show(); 
    $('.toComplete').hide();
    $('#cancelCaseReason').hide();
    $('#modalCancelCaseReason').modal({
        backdrop: 'static',
        keyboard: false
    });
}
/**
* Switch elements in modal
* @return none
*
* by Telmo Chiri
**/
function confirmedToCancel() {
    $('.toConfirm').hide();
    $('.toComplete').show();
}
/**
 * Close Cancel Case
 * @return none
 * by Telmo Chiri
 */
function closeCancelCase()
{
    //Remove all error labels
    $('.pmdynaform-message-error').remove();
    $('#reasonOptions option').removeAttr('selected');
    $('#reasonOptions').css('border-color', '#ccc');
    $('#cancelCaseReason').hide();
    $('#cancelCaseReason').val('');
    //Remove red border
    $('#cancelCaseReason').css('border-color', '#ccc');
    //Clean Title
    $('#caseTitleToCancel').html('');
    //Hide modal
    $('#modalCancelCaseReason').modal('hide');
}

/**
 * Confirm Cancel Case
 * @return none
 *
 * by Telmo Chiri
 */
function confirmCancelCase()
{
    //Set error mesaage HTML
    let errorHTML = '<div class=\'pmdynaform-message-error\'>';
    errorHTML += '<span>This field is required.</span>';
    errorHTML += '</div>';

    //Remove all error labels
    $('.pmdynaform-message-error').remove();

    //Initialize values
    let allFieldsOk = true;
    let cancelCaseReason = '';

    //Remove red border
    $('#reasonOptions').css('border-color', '#ccc');
    $('#cancelCaseReason').css('border-color', '#ccc');
    //Check if the user has entered a reason
    reasonOptions = $('#reasonOptions').val();
    if (reasonOptions == '') {
        allFieldsOk = false;
        $('#reasonOptions').css('border-color', 'red');
        $('#reasonOptionsError').html(errorHTML);
    }
    cancelCaseReason = $('#cancelCaseReason').val();
    if (reasonOptions == 'Other' && cancelCaseReason == '') {
        allFieldsOk = false;
        $('#cancelCaseReason').css('border-color', 'red');
        $('#cancelCaseReason').parent().append(errorHTML);
    }
    
    //Check if all values have been correctly entered
    if (allFieldsOk) {
        let cancelCaseFinalReason = (reasonOptions == 'Other') ? cancelCaseReason : reasonOptions;
        $.ajax({
            type: 'POST',
            url: '" . $apiHost . "/pstools/script/cancel-case-cancel-requests',
             data: {
                'data': JSON.stringify({
                    dataCase: dataCase,
                    cancelCaseReason: cancelCaseFinalReason,
                    currentUserId: '" . $currentUser . "'
                })
            },
            beforeSend: function() {
                $.blockUI({ message: '<div id=\'loader\'><img src=\'/storage/1394/loadingIMG.gif\'></div>', baseZ: 1500});
            },
            success: function (response) {
                closeCancelCase();
                var tableCases = $('#tableCases').DataTable();
                tableCases.clearPipeline().draw();
                if (!response.status) {
                    $('#alertMessage').removeClass();
                    $('#alertMessage').addClass('alert alert-danger');
                    $('#alertMessage').html('<p>Something went wrong, please contact your system administrator</p>');
                    $('#alertMessage').fadeTo(2000, 500).slideUp(500, function() {
                        $('#alertMessage').slideUp(500);
                    });
                } else {
                    $('#alertMessage').removeClass();
                    $('#alertMessage').addClass('alert alert-success');
                    $('#alertMessage').html('<p>Case ' + dataCase.CASE_NUMBER + ' were successfully cancel</p>');
                    $('#alertMessage').fadeTo(2000, 500).slideUp(500, function() {
                        $('#alertMessage').slideUp(500);
                    });
                }
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

$(document).ready(function() {
    $('#errorAlert').hide();
    // DataTables initialisation
    function renderButton(data, row) {
        let dataSelect = JSON.stringify(data);
        return '<|button name=\'cancel_'+row.REQUEST_ID+'\' onclick=\'showModalCancelCase('+dataSelect+')\' class=\'btn btn-primary\'><svg xmlns=\'http://www.w3.org/2000/svg\' xmlns:xlink=\'http://www.w3.org/1999/xlink\' version=\'1.1\' width=\'18\' height=\'18\' viewBox=\'0 0 256 256\' xml:space=\'preserve\'><defs></defs><g style=\'stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;\' transform=\'translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)\' ><path d=\'M 68.842 90 H 21.158 c -4.251 0 -7.696 -3.446 -7.696 -7.696 v -52.09 h 63.077 v 52.09 C 76.538 86.554 73.092 90 68.842 90 z\' style=\'stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(255,255,255); fill-rule: nonzero; opacity: 1;\' transform=\' matrix(1 0 0 1 0 0) \' stroke-linecap=\'round\' /><path d=\'M 78.321 22.213 H 11.679 c -2.209 0 -4 -1.791 -4 -4 s 1.791 -4 4 -4 h 66.643 c 2.209 0 4 1.791 4 4 S 80.53 22.213 78.321 22.213 z\' style=\'stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(255,255,255); fill-rule: nonzero; opacity: 1;\' transform=\' matrix(1 0 0 1 0 0) \' stroke-linecap=\'round\' /><path d=\'M 57.815 22.213 h -25.63 c -2.209 0 -4 -1.791 -4 -4 V 7.696 C 28.185 3.453 31.637 0 35.881 0 h 18.238 c 4.244 0 7.696 3.453 7.696 7.696 v 10.517 C 61.815 20.422 60.024 22.213 57.815 22.213 z M 36.185 14.213 h 17.63 V 8 h -17.63 V 14.213 z\' style=\'stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(255,255,255); fill-rule: nonzero; opacity: 1;\' transform=\' matrix(1 0 0 1 0 0) \' stroke-linecap=\'round\' /><path d=\'M 54.784 78.235 c -2.209 0 -4 -1.791 -4 -4 V 44.976 c 0 -2.209 1.791 -4 4 -4 s 4 1.791 4 4 v 29.259 C 58.784 76.444 56.993 78.235 54.784 78.235 z\' style=\'stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(82,14,27); fill-rule: nonzero; opacity: 1;\' transform=\' matrix(1 0 0 1 0 0) \' stroke-linecap=\'round\' /><path d=\'M 35.216 78.235 c -2.209 0 -4 -1.791 -4 -4 V 44.976 c 0 -2.209 1.791 -4 4 -4 s 4 1.791 4 4 v 29.259 C 39.216 76.444 37.425 78.235 35.216 78.235 z\' style=\'stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(82,14,27); fill-rule: nonzero; opacity: 1;\' transform=\' matrix(1 0 0 1 0 0) \' stroke-linecap=\'round\' /></g></svg></|button>';
    }
    var tableCases = $('#tableCases').DataTable({
        order: [0, 'desc'],
        pagination: true,
        pageLength: 10,
        processing: true,
        serverSide: true,
        scrollCollapse: true,
        ajax: $.fn.dataTable.pipeline({
            url: '" . $apiHost . "/pstools/script/cancel-case-get-data',
            pages: 1 // number of pages to cache
        }),
        columns: [
            {data: 'CASE_NUMBER'},
            {data: 'CASE_TITLE'},
            {data: 'TASK_TITLE'},
            {data: 'USER_FULL_NAME'},
            {
                data: null,
                ordenable: false,
                render: (data, type, row) => renderButton(data, row)
            },
        ],
        columnDefs: [
            {
                targets: [0, 1, 2, 3],
                visible: true,
                bSortable: true
            },
            {
                targets: 4,
                visible: true,
                bSortable: false
            },
        ],
        'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]]
    });
    $('#tableCases_filter').html('<|label>Search: <|input id=\'searchDataTable\' type=\'search\' class=\'form-control input-sm\' placeholder=\'Case Number\'></|label>');
    $('#searchDataTable').on('keypress',function(e) {
        if(e.which == 13) {
            let tableCases = $('#tableCases').DataTable();
            tableCases.clearPipeline().draw();
        }
    });

    $('#reasonOptions').on('change', function() {
        if (this.value != '') {
            $('#reasonOptions').css('border-color', '#ccc');
            $('#reasonOptionsError').html('');
            $('#cancelCaseReason').hide();
            if (this.value == 'Other') {
                $('#cancelCaseReason').show();
            }
        } else {
            $('#cancelCaseReason').hide();
        }
    });
});
</script>";
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];