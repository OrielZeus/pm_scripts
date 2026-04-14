<?php

/*****************************************
* get data for PsTools Report table
*
* by Diego Tapia
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set Global Variables
$apiHost = getenv('API_HOST');

//Generate HTML Body
$html = "";
$html .= "<head>";
$html .= '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
$html .= '<script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>';
$html .= '<link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css">';
$html .= '<style>
    th {
        background-color:#363638 !important;
        min-width: 160px !important;
        text-align: left !important;
    }
    .dt-type-date, .dt-type-numeric {
        direction: rtl !important;
    }
    td {
        text-align: left !important;
    }
    .dt-column-header {
        color:white
    }
    .spinner {
        border: 4px solid rgba(0, 0, 0, 0.1);
        border-left-color: transparent;
        width: 36px;
        height: 36px;
        --bs-spinner-width: 2rem;
        --bs-spinner-height: 2rem;
        --bs-spinner-vertical-align: -0.125em;
        --bs-spinner-border-width: 0.25em;
        --bs-spinner-animation-speed: 0.75s;
        --bs-spinner-animation-name: spinner-border;
        border: var(--bs-spinner-border-width) solid currentcolor;
        border-right-color: transparent;
        display: inline-block;
        width: var(--bs-spinner-width);
        height: var(--bs-spinner-height);
        vertical-align: var(--bs-spinner-vertical-align);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        
        100% {
            transform: rotate(360deg);
        }
    }
</style>';
$html .= "</head>";

$html .= '<body>';

$html .= '<br>';
$html .= '<div style="width: 100%;text-align: center;"></div>';
$html .= '<table id="report" class="display" width="100%"></table>';

$html .= '
<script type="text/javascript">
    
    $(document).ready(function() {
        getData()
    });
    function getData() {
        const apiURL = "' . $apiHost . '/pstools/script/cqp_consolidado_data";
        let columnList = [
            { title: "Request ID", name: "id"},
            { title: "Line of Business", name: "CQP_TYPE"},
            { title: "Original Assured", name: "CQP_INSURED_NAME"},
            { title: "Month Inception", name: "CQP_INCEPTION_DATE"},
            { title: "Status", name: "CQP_STATUS"},
            { title: "Country", name: "CQP_COUNTRY"},
            { title: "Broker", name: "CQP_REINSURANCE_BROKER"},
            { title: "Classification Commodity Profile", name: "CQP_COMMODITIES_PROFILE"},
            { title: "Gwp Estim Usd 100%", name: "CQP_TOTAL_TTP_GWD", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Mindep (Usd) 100%", name: "CQP_MIN_DEP", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Forte Share %", name: "CQP_TOTAL_FORTE_SHARE", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return data + " %";
                    }
                    return "0.00 %";
                }
            },
            { title: "Comments", name: "CQP_COMMENTS"},
            { title: "Account Status", name: "CQP_ACCOUNT_STATUS"},
            { title: "Last Update (DD/MM/AA)", name: "updated_at"}
        ]

        let defSort = [
            {
                column: 0,
                dir: "desc",
                name: "id"
            }
        ]
        
        const table = new DataTable("#report", {
            columns: columnList,
            ajax: {
                url: apiURL,
                type: "GET",
                data: function(d) {
                    return {
                        "data": JSON.stringify({
                            CQP_SEARCH: \'' . addslashes($data["CQP_SEARCH"]) . '\',
                            END_DATE: \'' . addslashes($data["END_DATE"]) . '\',
                            START_DATE: \'' . addslashes($data["START_DATE"]) . '\',
                            start: d.start,
                            length: d.length,
                            draw: d.draw,
                            order: d.order.length == 0 ? defSort : d.order,
                            search: d.search
                        })
                    };
                },
                dataSrc: function(json) {
                    window.sqlQuery = json.sqlQuery
                    window.responseFormat = json.responseFormat
                    return json.data || json;
                }
            },
            serverSide: true, 
            searching: false,
            autoType: false,
            info: false,
            lengthChange: false,
            order: [[0, "desc"]],
            processing: true,
            language: {
                processing: \'Procesing...\'
            },
            initComplete: function() {
                setTimeout(function(){
                    window.parent.document.querySelector("#bulkIframe").style.height =
                        (70 + document.querySelector("#report_wrapper").offsetHeight) + "px";
                }, 200);
                this.api().columns([0]).visible(false);
            },
            drawCallback: function() {
                setTimeout(function(){
                    window.parent.document.querySelector("#bulkIframe").style.height =
                        (70 + document.querySelector("#report_wrapper").offsetHeight) + "px";
                }, 200);
            }
        });
    }
</script>';

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];