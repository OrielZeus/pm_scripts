<?php

/*****************************************
* get data for PsTools Report table
*
* by Diego Tapia
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
require_once("/CQP_Generic_Functions.php");

//Set Global Variables
$apiHost = getenv('API_HOST');

$distributionCollectionId = getCollectionId('CQP_FORTE_CARGO_REINSURER', getEnv("API_HOST") . getEnv("API_SQL"));
$originalRequestsCollectionID = getCollectionId("CQP_FORTE_CARGO_ORIGINAL_REQUESTS", getEnv("API_HOST") . getEnv("API_SQL"));
$url = $apiHost . "/collections/" . $distributionCollectionId . "/records";
$configurations = callApiUrlGuzzle($url, "GET");
$moneyFormat = ', type: "string",
    render: function(data, type, row, meta) {
        if (data && data != 0) {
            return `$ ` + data;
        }
        return "$ 0.00";
    }';
$percentageFormat = ', type: "string",
    render: function(data, type, row, meta) {
        if (data && data != 0) {
            return data + " %";
        }
        return "0.00 %";
    }';

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
    td.button-style {
        text-align: center !important
    }
    td.button-style button {
        border-radius: 4px;
        text-transform: uppercase;
        width: 30px;
        height: 30px;
        background-color: lightgrey;
        border-color: #363638;
    }
    .btn-action:hover {
    cursor: pointer;
    }
    th.column-dif-alt {
        background-color:#565685 !important;
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
    .button-FX {
        color: #fff;
        background-color: #555916 !important;
        border-color: #555916 !important;
        display: inline-block;
        font-weight: 400;
        text-align: center;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
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
        window.collectionConf = ' . json_encode(array_column($configurations["data"], "data")) . '.map((element, index) => {
            return {
                CQP_REINSURER: element.CQP_REINSURER,
                CQP_ALIAS: element.CQP_ALIAS
            };
        });

        // Set datatable structure
        const apiURL = "' . $apiHost . '/pstools/script/cqp_BDRX_data";
        let columnList = [
            { title: "Request ID", name: "id"},
            { 
                title: "Change FX rate",
                orderable: false,
                searchable: false,
                width: "100px !important",
                render: function(data, type, row, meta) {
                    return row[75] == "USD" ?  "" : `
                        <|button class="btn btn-sm btn-primary btn-action button-FX" 
                                data-currentFX="` + row[74] + `"
                                data-id="` + row[0] + `">
                            <|i class="fas fa-eye">$</|i>
                        </|button>
                    `;
                }
            },
            { title: "Reinsurer 2", name: "CQP_REINSURER"},
            { title: "Line of Business", name: "CQP_TYPE"},
            { title: "Status pivot", name: "CQP_PIVOT_TABLE_NUMBER"},
            { title: "Contract", name: "CQP_CONTRACT"},
            { title: "Assured", name: "CQP_INSURED_NAME"},
            { title: "Type 1", name: "CQP_ACTION"},
            { title: "Type 2", name: ""},
            { title: "Reinsured", name: "CQP_CEDANT"},
            { title: "Country", name: "CQP_COUNTRY"},
            { title: "Reinsurance Broker", name: "CQP_REINSURANCE_BROKER"},
            { title: "From", name: "CQP_INCEPTION_DATE"},
            { title: "To", name: "CQP_EXPIRATION_DATE"},
            { title: "Term (Number of Month)", name: "CQP_MONTHS"},
            { title: "Calendar year", name: "dateToday"},
            { title: "UIW FWK", name: "CQP_UNDERWRITING_YEAR"},
            { title: "Month Inception", name: "CQP_INCEPTION_DATE"},
            { title: "UW QUARTER", name: "CQP_INCEPTION_DATE"},
            { title: "Adjustment Date", name: ""},
            { title: "Adjustment Year", name: ""},
            { title: "Adjustment Month", name: ""},
            { title: "Adjustment Quarter", name: ""},
            { title: "Interest Assured", name: "CQP_INTEREST"},
            { title: "Clasification Commodity Profile", name: "CQP_COMMODITIES_PROFILE"},
            { title: "TOTAL ESTIMATED MOBILIZATIONS (Per period)", name: "CQP_TOTAL_TTP_ANNUAL_USD" ' . $moneyFormat  .' },
            { title: "Transit (USD) 100%", name: "CQP_TRANSIT_USD_100" ' . $moneyFormat  .' },
            { title: "Storage (USD) 100%", name: "CQP_STORAGE_EEL" ' . $moneyFormat  .' },
            { title: "CAT (USD) 100%", name: "CQP_STORAGE_AGG" ' . $moneyFormat  .' },
            { title: "GWP Transit 100%", name: "CQP_TOTAL_TTP_GWD" ' . $moneyFormat  .' },
            { title: "GWP Storage 100%", name: "CQP_TOTAL_INVENTORIES_GWP" ' . $moneyFormat  .' },
            { title: "GWP Total EPI 100%", name: "CQP_GWP_TOTAL_EPI_100" ' . $moneyFormat  .' },
            { title: "GWP Mindep 100%", name: "CQP_MINDEP_USD" ' . $moneyFormat  .' },
            { title: "GWP Prima do Ajuste 100%", name: "CQP_GWP_PRIMA_DE_AJUSTE" ' . $moneyFormat  .' },
            { title: "GWP Mindep+Ajuste 100%", name: "CQP_GWP_MINDEP_PLUS_AJUSTE" ' . $moneyFormat  .' },
            { title: "REINSURER 1", name: "CQP_REINSURER"},
            { title: "MRE SHARE %", name: "CQP_FORTE_SHARE" ' . $percentageFormat  .'},
            { title: "Austral Re Retention", name: "CQP_AUSTRAL_RETENTION" ' . $percentageFormat  .'},
            { title: "Axa Retro Share", name: "CQP_AXA" ' . $percentageFormat  .'},
            { title: "GWP Transit Reinsurer Share", name: "CQP_GWP_TRANSIT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "GWP Storage Reinsurer Share", name: "CQP_GWP_STORAGE_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "GWP Total Epi Reinsurer Share", name: "CQP_GWP_TOTAL_EPI_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "GWP Mindep Reinsurer Share", name: "CQP_GWP_MINDEP_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "GWP Adjustment Add Premium Reinsurer Share", name: "CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "GWP Mindep Plus Adjustment Reinsurer Share", name: "CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "Broker Deductions", name: "CQP_BROKER_DEDUCTIONS" ' . $percentageFormat  .' },
            { title: "Broker Deduction Usd", name: "CQP_BROKER_DEDUCTION_USD" ' . $moneyFormat  .' },
            { title: "Broker Deduction Adjustment", name: "CQP_BROKER_DEDUCTION_ADJUSTMENT" ' . $moneyFormat  .' },
            { title: "Broker Deduction Total", name: "CQP_BROKER_DEDUCTION_TOTAL" ' . $moneyFormat  .' },
            { title: "TAX USD (If Apply)", name: "CQP_TAX_USD_IF_APPLAY" ' . $percentageFormat  .' },
            { title: "TAX", name: "CQP_TAX" ' . $moneyFormat  .' },
            { title: "TAX ADJUSTMENT", name: "CQP_TAX_ADJUSTMENT" ' . $moneyFormat  .' },
            { title: "TAX TOTAL", name: "CQP_TAX_TOTAL" ' . $moneyFormat  .' },
            { title: "NWP AT 100", name: "CQP_NWP_AT_100" ' . $moneyFormat  .' },
            { title: "NWP Transit Reinsurer Share", name: "CQP_NWP_TRANSIT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "NWP Storage Reinsurer Share", name: "CQP_NWP_STORAGE_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "NWP Total Epi Reinsurer Share", name: "CQP_NWP_TOTAL_EPI_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "NWP Mindep Reinsurer Share", name: "CQP_NWP_MINDEP_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "NWP Mindep Reinsurer Share Exc Tax", name: "CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX" ' . $moneyFormat  .' },
            { title: "NWP Adjustment Reinsurer Share", name: "CQP_NWP_ADJUSTMENT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "NWP Adjustment Reinsurer Share Exc Tax", name: "CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX" ' . $moneyFormat  .' },
            { title: "NWP Mindep Plus Adjustment Reinsurer Share", name: "CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "NWP Mindep Plus Adjustment Reinsurer Share Exc Tax", name: "CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX" ' . $moneyFormat  .' },
            { title: "Forte Fee %", name: "CQP_UNDERWRITING_EXPENSES" ' . $percentageFormat  .' },
            { title: "Forte Fee Usd", name: "CQP_FORTE_FEE_USD" ' . $moneyFormat  .' },
            { title: "Forte Fee Adjustment", name: "CQP_FORTE_FEE_ADJUSTMENT" ' . $moneyFormat  .' },
            { title: "Forte Fee Total", name: "CQP_FORTE_FEE_TOTAL" ' . $moneyFormat  .' },
            { title: "Net Ceded Premium Mindep", name: "CQP_NET_CEDED_PREMIUM_MINDEP" ' . $moneyFormat  .' },
            { title: "Net Ceded Premium Mindep Adjustment", name: "CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT" ' . $moneyFormat  .' },
            { title: "Net Ceded Premium", name: "CQP_NET_CEDED_PREMIUM" ' . $moneyFormat  .' },
            { title: "Transit Reinsurer Share", name: "CQP_TRANSIT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "Storage Reinsurer Share", name: "CQP_STORAGE_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "Cat Reinsurer Share", name: "CQP_CAT_REINSURER_SHARE" ' . $moneyFormat  .' },
            { title: "N Installments", name: "CQP_N_INSTALLMENTS"}
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
                            columns: collectionConf,
                            CQP_SEARCH: \'' . addslashes($data["CQP_SEARCH"]) . '\',
                            END_DATE: \'' . addslashes($data["END_DATE"]) . '\',
                            START_DATE: \'' . addslashes($data["START_DATE"]) . '\',
                            start: d.start,
                            collection: ' . $originalRequestsCollectionID . ',
                            length: d.length,
                            draw: d.draw,
                            order: d.order.length == 0 ? defSort : d.order,
                            search: d.search,
                            currency: \'' .  $data["currency"] . '\'
                        })
                    };
                },
                dataSrc: function(json) {
                    window.tableOptions = true
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
                targets: [1, 6, 11, 29, 30, 31, 32, 33, 34, 35, 39, 40, 41, 42, 43, 44, 54, 55, 56, 57, 58, 59, 60, 61]
            },
            {
                className: "column-dif-alt",
                targets: [36, 45, 49, 62]
            },
            {
                className: "button-style",
                targets: [1]
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

        // Open modal in screen
        $(document).on("click", ".btn-action", function() {
            window.row = this
            window.parent.document.querySelector("[name=\'CQP_ROW_DATA\']").click()
            window.parent.document.querySelector(\'[data-cy="screen-field-CQP_RATE_POPUP"] button\').click()
        });

        // Refresh table information
        window.addEventListener("message", (event) => {
            if (event.data.update) {
                table.ajax.reload(null, false);
            }
        }, false);
    }
</script>';

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];