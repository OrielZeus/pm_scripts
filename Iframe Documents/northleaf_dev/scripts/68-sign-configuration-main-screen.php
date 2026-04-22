<?php

/**********************************
 * Sign Configuration - Main Screen
 *
 * by Elmer Orihuela
 *********************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

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
foreach ($getLibraries as $library) {
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
foreach ($cssToLoad as $css) {
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
foreach ($jsToLoad as $js) {
    $html .= $libraries[$js] ? "<script type='text/javascript' src='/storage/" . $libraries[$js] . "/$js'></script>" : "<meta name='$js' content='Library not loaded'>";
}

$html .= "<style>
/*#tableSignature_filter { display: none; }*/
</style>";
$html .= "<style>
.btn-uploadfile {
    background-color: #991a33 !important;
    border-color: #991a33 !important;
    border-radius: 4px 4px 4px 4px !important;
    color: white !important;
    width: 80%;
}
</style>";

$html .= "<link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css\" rel=\"stylesheet\">";
$html .= "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css\">";
$html .= "</head>";
$html .= "<body class='bodyStyle'>";
$html .= "<!-- Modal error -->";
$html .= "<div id='alertMessage'></div>";
$html .= "<!--Modal for Upload Signature-->";
$html .= "<div class = 'modal fade' id='modalSignatureConfig'>";
$html .= "<div class='modal-dialog'>";
$html .= "<div class='modal-content'>";
$html .= "<div class='modal-header modalTitleStyle'>";
$html .= "<center><b>Upload Signature</b></center>";
$html .= "</div>";
$html .= "<div class='modal-body'>";
$html .= "<div class='row'>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12 ' style='display:flex;'>";
$html .= "<div class='col-sm-2 col-md-2 col-lg-2' style='text-align: right'>";
$html .= "<span class='textlabel' htmlFor='file'><strong>Select signature image to upload:&nbsp;<span style='color:red;'>*</strong></span>";
$html .= "</div>";
$html .= "<div class='col-sm-10 col-md-10 col-lg-10' style='padding-right:0;' >";
//$html .= "<|input type='file' accept='.png, .jpg, .jpeg' class='btn btn-uploadfile' id='fileInput' rows='3'>";
$html .= "<|input type='file' id='uploadData' uploadchanged='0' accept='.png, .jpg, .jpeg' name='uploadData' style='display: none;' onchange='uploadChange(this)' />";
$html .= "<|input type='button' class='btn btn-uploadfile' value='Choose File' onclick='document.getElementById(\"uploadData\").click();' />";
$html .= "<div id='fileListView' style='display:none'></div>";

//$html .= "<|input type='file' id='fileInput' uploadchanged='0' accept='.png, .jpg, .jpeg' name='fileInput' style='display: none;' onchange='uploadChange(this)'><|input type='button' class='btn btn-uploadfile' value='Choose File' onclick='document.getElementById('fileInput').click();'>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";

$html .= "<div class='row' style='margin-top: 20px;'>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12'>";
$html .= "<div style='border-bottom: 1px solid #B22222; color: #B22222;'>";
$html .= "Funding Approval Configuration (SS.01)";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
//Funding Section
$html .= "<div class='row' style='margin-top: 20px;'>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12' style='display:flex;'>";
$html .= "<div class='col-sm-10 col-md-10 col-lg-10' style='text-align: left;'>";
$html .= "<strong>Is the signature of this user required for funding approval?</strong>";
$html .= "</div>";
$html .= "<div class='col-sm-2 col-md-2 col-lg-2' style='padding-right:0;'>";
$html .= "<|input type='checkbox' id='fundingApprovalRequired' onclick='toggleTypeOfSigner()'>";
$html .= "</div>";
$html .= "</div>";
$html .= "<div class='col-sm-12 col-md-12 col-lg-12' id='typeOfSignerContainer' style='display:none; margin-top: 10px;'>";
$html .= "<div class='col-sm-4 col-md-4 col-lg-4' style='text-align: left;padding-left: 30px;'>";
$html .= "<strong>Type of Signer</strong> <span style='color:red;'>*</span>";
$html .= "</div>";
$html .= "<div class='col-sm-8 col-md-8 col-lg-8' style='padding-right:0;'>";
$html .= "<ul class='radio-list' style='list-style-type: none; padding: 0;'>";
$html .= "<li><|input type='radio' id='primary' name='typeOfSigner' value='Primary'> Primary</li>";
$html .= "<li><|input type='radio' id='secondary' name='typeOfSigner' value='Secondary'> Secondary</li>";
$html .= "<li><|input type='radio' id='both' name='typeOfSigner' value='Both'> Both</li>";
$html .= "</ul>";
$html .= '<div id="signerError" style="display: none; color: #B22222;"><small>Type of signer is required. Please select one.</small></div>';
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";

$html .= "</div>";
$html .= "<div class='modal-footer'>";
$html .= "<|button type='button' class='btn btn-primary' onclick='uploadSignature();'>Confirm</|button>";
$html .= "<|button type='button' class='btn btn-secondary' onclick='closeSignatureModal();'>Close</|button>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= "</div>";
$html .= " <!-- Dashboard -->";
$html .= "<div class='dataTables_wrapper'>";
$html .= "<div class='row'>";
$html .= "<div class='col-md-12'>";
$html .= "<table id='tableSignature' width='100%'>";
$html .= "<thead>";
$html .= "<tr>";
$html .= "<th></th>";
$html .= "<th>First Name</th>";
$html .= "<th>Last Name</th>";
$html .= "<th>Status</th>";
$html .= "<th>Signature Configured</th>";
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
 * Show Modal Different Nodes
 *
 * @param selectedRows
 * @return none
 *
 * by Telmo Chiri
 */
function showSignatureConfig(selectedRows)
{
    dataCase = selectedRows;
    $(\"#fileListView\").css(\"display\", \"hide\");    
    //document.getElementById('fileInput').value = '';
    document.getElementById('primary').checked = false;
    document.getElementById('secondary').checked = false;
    document.getElementById('both').checked = false;
    document.getElementById('fundingApprovalRequired').checked = false;

    $('#modalSignatureConfig').modal({
        backdrop: 'static',
        keyboard: false
    });
    document.getElementById('fundingApprovalRequired').checked = dataCase.fundingApprovalRequired;
    document.getElementById('typeOfSignerContainer').style.display = dataCase.fundingApprovalRequired ? 'flex' : 'none';
    
    if (dataCase.typeOfSigner === 'Primary') {
        document.getElementById('primary').checked = true;
    } else if (dataCase.typeOfSigner === 'Secondary') {
        document.getElementById('secondary').checked = true;
    } else if (dataCase.typeOfSigner === 'Both') {
        document.getElementById('both').checked = true;
    }
    
    // Extract the PNG file name from the URL
    var signatureUrl = dataCase.SIGNATURE_URL;
    var fileName = signatureUrl ? signatureUrl.substring(signatureUrl.lastIndexOf('/') + 1) : '';
    //var fileName = signatureUrl ? signatureUrl.split('/').pop() : '';    
    // Build the HTML to display the file name
    var fileViewHtml = '<div>';
    fileViewHtml += '<div>';
    fileViewHtml += '<span>' + fileName + '</span>';
    fileViewHtml += '</div>';
    fileViewHtml += '</div>';
    // Display the file name in the file list view
    $(\"#fileListView\").html(fileViewHtml);
    $(\"#fileListView\").css(\"display\", \"block\");
}

/**
 * Close Signature Modal
 *
 * @return none
 *
 * by Elmer Orihuela
 */
function closeSignatureModal() {
    $('#modalSignatureConfig').modal('hide');
    $('[name=\"typeOfSigner\"]:checked').attr('checked', false);
    // Clear the file input
    clearFileInput();
}

/**
 * Upload Signature
 *
 * @return none
 *
 * modified by Elmer Orihuela
 */
function uploadSignature() {
    if (!validateFundingApprovalAndSigner()) {
        // If validation fails, do not proceed with the file upload
        return;
    }
    var fileInput = document.getElementById('uploadData');
    var file = fileInput.files.length > 0 ? fileInput.files[0] : null;
    var fileType = file ? file.type : null;
    var idUser = 'username'; // Replace this with the actual username

    var fundingApprovalRequired = document.getElementById('fundingApprovalRequired').checked;
    var typeOfSigner = document.querySelector('input[name=\"typeOfSigner\"]:checked')?.value ?? null;
    
    let signatureData = ''; // Variable to store the signature if it exists
    if (file) {
        var reader = new FileReader();
        reader.onloadend = function() {
            signatureData = reader.result.replace('data:', '').replace(/^.+,/, '');
            sendPayload();
        };
        reader.readAsDataURL(file);
    } else {
        sendPayload();
    }

    function sendPayload() {
        // Adjust the value of typeOfSigner depending on fundingApprovalRequired
        if (!fundingApprovalRequired) {
            typeOfSigner = ''; // If funding approval is not required, clear typeOfSigner
        }

        var payload = {
            data: {
                idUser: idUser,
                signature: file ? signatureData : '', // Send signature if file exists; otherwise, send an empty string
                fileType: fileType ? fileType : '', // Send file type if file exists; otherwise, send an empty string
                dataCase: dataCase,
                fundingApprovalRequired: fundingApprovalRequired,
                typeOfSigner: typeOfSigner ? typeOfSigner : '' // If funding approval is not required, this will be empty
            }
        };

        console.log('Payload:', payload); // Check the payload before sending

        fetch('https://northleaf.dev.cloud.processmaker.net/api/1.0/pstools/script/sign-configuration-upload-file', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload),
            mode: 'cors' // Set mode to 'cors'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error uploading file');
            }
            console.log('File uploaded successfully');

            let tableSignature = $('#tableSignature').DataTable();
            tableSignature.clearPipeline().draw();

            // Clear the file input after successful upload
            clearFileInput();

            return response.json();
        })
        .then(data => {
            console.log('Server Response:', data);
            $('#modalSignatureConfig').modal('hide'); // Hide the modal after upload
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

        function clearFileInput() {
        var newFileInput = document.createElement('input');
        newFileInput.type = 'file';
        newFileInput.accept = fileInput.accept;
        newFileInput.className = fileInput.className;
        newFileInput.id = fileInput.id;
        newFileInput.onchange = fileInput.onchange; // Copy events if they exist

        // Ensure the new file input remains hidden
        newFileInput.style.display = 'none';

        fileInput.parentNode.replaceChild(newFileInput, fileInput);
    }
}

// Validation function to show or hide the error message
function validateFundingApprovalAndSigner() {
    var fundingApprovalRequired = document.getElementById('fundingApprovalRequired').checked;
    var typeOfSigner = document.querySelector('input[name=\"typeOfSigner\"]:checked')?.value ?? null;
    var errorDiv = document.getElementById('signerError');

    // Check if fundingApprovalRequired is checked and typeOfSigner is not selected
    if (fundingApprovalRequired && !typeOfSigner) {
        // Show the error div
        errorDiv.style.display = 'block';
        return false; // Return false to indicate validation failed
    } else {
        // Hide the error div
        errorDiv.style.display = 'none';
        return true; // Return true if validation passed
    }
}

// Add event listeners to the relevant form elements
document.getElementById('fundingApprovalRequired').addEventListener('change', validateFundingApprovalAndSigner);
var typeOfSignerRadios = document.querySelectorAll('input[name=\"typeOfSigner\"]');
typeOfSignerRadios.forEach(function(radio) {
    radio.addEventListener('change', validateFundingApprovalAndSigner);
});

// Initial validation check when the page loads
validateFundingApprovalAndSigner();

function toggleTypeOfSigner() {
    var fundingApprovalRequired = document.getElementById('fundingApprovalRequired').checked;
    var typeOfSignerContainer = document.getElementById('typeOfSignerContainer');
    if (fundingApprovalRequired) {
        typeOfSignerContainer.style.display = 'flex';
    } else {
        typeOfSignerContainer.style.display = 'none';
    }
}

$(document).ready(function() {
    $('#errorAlert').hide();
    
    // DataTables initialisation
    function renderMyData(data, row) {
        let dataSelect = JSON.stringify(data);
        
        return '<|button name=\'signature_'+row.USER_ID+'\' onclick=\'showSignatureConfig('+dataSelect+')\'class=\'btn btn-primary\'><i class=\"fas fa-upload\"></i>';
    }  
    var tableSignature = $('#tableSignature').DataTable({
        order: [1, 'desc'],
        pagination: true,
        pageLength: 10,
        processing: true,
        serverSide: true,
        scrollCollapse: true,
        ajax: $.fn.dataTable.pipeline({
            url: '" . $apiHost . "/pstools/script/sign-configuration-get-data',
            pages: 1 // number of pages to cache
        }),
        columns: [
            {
                data: null,
                ordenable: false,
                render: (data, type, row) => renderMyData(data, row)
            },                       
            {data: 'FIRST_NAME'},
            {data: 'LAST_NAME'},
            {data: 'STATUS'},
            {
                data: 'CONFIGURED',
                render: (data, type, row) => {
                    return data 
                        ? '<i class=\"fa fa-check\" style=\"color:green\"></i>'
                        : '<i class=\"fa fa-close\" style=\"color:red\"></i>';
                }
            },
            {
                data: null,
                render: (data, type, row) => {
                    return '<|button name=\'copy_'+row.USER_ID+'\' onclick=\"copyToClipboard(\'' + row.SIGNATURE_BASE64 + '\')\" class=\"btn btn-primary\"><i class=\"fa fa-copy\"></i>';
                },
            },
        ],
        columnDefs: [
            /*{
                'targets': [0],
                'bSortable': false,
                'checkboxes': {
                    'selectRow': true
                }
            },*/
            {
                'visible': true,
                'bSortable': true
            },
        ],
        'lengthMenu': [[10, 25, 50, 100, 500], [10, 25, 50, 100, 500]]
    });
    $('#tableSignature_filter').html('<|label>Search: <|input id=\'searchDataTable\' type=\'search\' class=\'form-control input-sm\' placeholder=\'\'></|label>');
    $('#searchDataTable').on('keypress',function(e) {
        if(e.which == 13) {
            let tableSignature = $('#tableSignature').DataTable();
            tableSignature.clearPipeline().draw();
        }
    });
});
function copyToClipboard(text) {
        navigator.clipboard.writeText(text)
            .then(() => {
                console.log('Texto copiado al portapapeles: ' + text);
                // Aquí puedes mostrar un mensaje de éxito al usuario si lo deseas
            })
            .catch(err => {
                console.error('Error al copiar al portapapeles: ', err);
                // Aquí puedes manejar errores, como mostrar un mensaje de error al usuario
            });
    }
</script>";
$html .= "<script>";
$html .= "function uploadChange(uploadObject) {";
$html .= "    $(\"#\" + uploadObject.id).attr(\"uploadchanged\", \"1\");";
$html .= "    var extensionsAccepted = [\"PNG\", \"JPG\", \"JPEG\"];";
$html .= "    var uploadedFileList = uploadObject.files;";
$html .= "    var fileName = uploadedFileList[0][\"name\"];";
$html .= "    var fileSize = ((uploadedFileList[0][\"size\"] / 1024) / 1024).toFixed(4);"; // Convert image size to MB
$html .= "    var fileExtension = \"\";";
$html .= "    if (fileName.lastIndexOf(\".\") > 0) {";
$html .= "        fileExtension = fileName.substring(fileName.lastIndexOf(\".\") + 1, fileName.length).toUpperCase();";
$html .= "    }";
$html .= "    if (fileSize <= 2) {";
$html .= "        if (extensionsAccepted.includes(fileExtension)) {";
$html .= "            var fileViewHtml = '<div>';";
$html .= "            fileViewHtml += '<div>';";
$html .= "            fileViewHtml += '<span>' + fileName + '</span>';";
$html .= "            fileViewHtml += '</div>';";
$html .= "            fileViewHtml += '</div>';";
$html .= "            $(\"#fileListView\").html(fileViewHtml);";
$html .= "            $(\"#fileListView\").css(\"display\", \"block\");";
$html .= "        } else {";
$html .= "            uploadObject.value = \"\";";
$html .= "            $(\"#fileListView\").html(\"\");";
$html .= "            $(\"#fileListView\").css(\"display\", \"none\");";
$html .= "            $(\"#alertMessage\").html(\"<p>Only files with the following extensions are allowed: <b>.png, .jpg, .jpeg</b></p>\");";
$html .= "            $(\"#alertMessage\").fadeTo(2000, 500).slideUp(500, function () {";
$html .= "                $(\"#alertMessage\").slideUp(500);";
$html .= "            });";
$html .= "        }";
$html .= "    } else {";
$html .= "        uploadObject.value = \"\";";
$html .= "        $(\"#fileListView\").html(\"\");";
$html .= "        $(\"#fileListView\").css(\"display\", \"none\");";
$html .= "        $(\"#alertMessage\").html(\"<p>Only files which its size not exceed 2MB are allowed</p>\");";
$html .= "        $(\"#alertMessage\").fadeTo(2000, 500).slideUp(500, function () {";
$html .= "            $(\"#alertMessage\").slideUp(500);";
$html .= "        });";
$html .= "    }";
$html .= "}";
$html .= "</script>";
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];

/**
 * Call Api Url Guzzle
 *
 * @param string $url
 * @param string $method
 * @param array sendData
 * @return array $executionResponse
 *
 * by Telmo Chiri
 */
function callApiUrlGuzzle(string $url, string $requestType, array $postdata = [], bool $contentFile = false)
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv("API_TOKEN");
    }
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken

    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, $url, $headers, json_encode($postdata));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        $res = json_decode($res, true);
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}

/**
 * Encode SQL
 *
 * @param string $query
 * @return array $encodedQuery
 *
 * by Telmo Chiri
 */
function encodeSql($query)
{
    $encodedQuery = [
        "SQL" => base64_encode($query)
    ];
    return $encodedQuery;
}