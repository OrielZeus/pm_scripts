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
    th.column-dif {
        background-color:#D7E023 !important;
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
    .column-dif>.dt-column-header {
        color:black
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

$html .= '<o>';

$html .= '<br>';
$html .= '<div style="width: 100%;text-align: center;"></div>';
$html .= '<table id="report" class="display" width="100%"></table>';

$html .= '
<script type="text/javascript">
    
    $(document).ready(function() {
        getData()
    });
    function getData() {
        const apiURL = "' . $apiHost . '/pstools/script/cqp_production_data";
        let columnList = [
            { title: "Request ID", name: "id"} ,
            { title: "Line of Business", name: "CQP_TYPE"},
            { title: "Cover Note Forte No", name: "CQP_PIVOT_TABLE_NUMBER"},
            { title: "File No", name: "CQP_FILE_NO"},
            { title: "Contract", name: "CQP_CONTRACT"},
            { title: "Original Assured", name: "CQP_INSURED_NAME"},
            { title: "Type", name: "CQP_ACTION"},
            { title: "Reassured/Cedant", name: "CQP_CEDANT"},
            { title: "Country", name: "CQP_COUNTRY"},
            { title: "Reinsurance Broker", name: "CQP_REINSURANCE_BROKER"},
            { title: "Submission date", name: "CQP_SUBMITION_DATE"},
            { title: "Submission Month", name: "CQP_SUBMITION_DATE", orderable: false},
            { title: "Status", name: "CQP_CARGO_CURRENT_STATUS"},
            { title: "From", name: "CQP_INCEPTION_DATE"},
            { title: "To", name: "CQP_EXPIRATION_DATE"},
            { title: "Term (Number of Month)", name: "CQP_MONTHS"},
            { title: "UW Year", name: "dateToday"},
            { title: "Risk Attaching Month", name: "CQP_INCEPTION_DATE", orderable: false},
            { title: "Interest Assured", name: "CQP_INTEREST"},
            { title: "Clasification Commodity Profile", name: "CQP_COMMODITIES_PROFILE"},
            { title: "Currency", name: "CQP_CURRENCY"},
            { title: "Sum Insured (sendings)", name: "CQP_TOTAL_TTP_ANNUAL_USD", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Storage Limit", name: "CQP_STORAGE_AGG", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Rate", name: "CQP_COMBINED_RATE_USD", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return data + " %";
                    }
                    return "0.00 %";
                }
            },
            { title: "GWP Total EPI 100%", name: "CQP_GWP_TOTAL_EPI_100", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "GWP Mindep 100%", name: "CQP_MINDEP_USD", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "MRE SHARE %", name: "CQP_MRE_SHARE", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return data + " %";
                    }
                    return "0.00 %";
                }
            },
            { title: "GWP Total EPI MRE Share", name: "CQP_GWP_TOTAL_EPI_MRE_SHARE", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "GWP Mindep MRE Share", name: "CQP_GWP_MINDEP_MRE_SHARE", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Broker Deduction %", name: "CQP_BROKER_DEDUCTIONS", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return data + " %";
                    }
                    return "0.00 %";
                }
            },
            { title: "TAX % (IF APPLY)", name: "CQP_TAX_USD", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return data + " %";
                    }
                    return "0.00 %";
                }
            },
            { title: "NWP Total EPI MRE Share", name: "", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "NWP Mindep MRE Share", name: "CQP_TAX_PERCENTAGE", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "FORTE FEE %", name: "CQP_UNDERWRITING_EXPENSES", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return data + " %";
                    }
                    return "0.00 %";
                }
            },
            { title: "FORTE FEE USD", name: "CQP_FORTE_FEE_USD", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Net ceded premium to Mre", name: "CQP_NET_CEDED_PREMIUM", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Net ceded Mindep premium to Mre", name: "CQP_NET_CEDED_PREMIUM_MINDEP", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Payment", name: "CQP_N_INSTALLMENTS"},
            { title: "Deductible", name: "CQP_DEDUCTIBLE"},
            { title: "Premium per Installment", name: "CQP_PREMIUM_PER_INSTALLMENT", type: "string",
                render: function(data, type, row, meta) {
                    if (data && data != 0) {
                        return `$ ` + data;
                    }
                    return "$ 0.00";
                }
            },
            { title: "Underwriter", name: "CQP_UNDERWRITER_USER"}
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
                            CQP_SELECT_REINSURER: \'' . addslashes($data["CQP_SELECT_REINSURER"]) . '\',
                            START_DATE: \'' . addslashes($data["START_DATE"]) . '\',
                            END_DATE: \'' . addslashes($data["END_DATE"]) . '\',
                            start: d.start,
                            length: d.length,
                            draw: d.draw,
                            order: d.order.length == 0 ? defSort : d.order,
                            search: d.search,
                            currency: \'' .  $data["currency"] . '\'
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
            info: false,
            columnDefs: [{
                className: "column-dif",
                targets: [24, 25, 27, 28, 31, 32]
            }],
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