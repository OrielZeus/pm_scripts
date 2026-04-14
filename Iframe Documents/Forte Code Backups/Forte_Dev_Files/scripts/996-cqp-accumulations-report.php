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

// Get dynamic column configuration
$DEFAULT_FORTE_COLOR = "D9B2FF";
$DEFAULT_COLUMN_COLOR = "CCCCCC";
$distributionCollectionId = getCollectionId('CQP_FORTE_CARGO_REINSURER', getEnv("API_HOST") . getEnv("API_SQL"));
$dynamicStyle = "";
$dynamicStyleList = [];
$dynamicColumns1 = "";
$dynamicColumns2 = "";
$columnsClass = [];
$columnsReport = [];
$url = $apiHost . "/collections/" . $distributionCollectionId . "/records";
$configurations = callApiUrlGuzzle($url . '?pmql=data.CQP_STATUS="ACTIVE"', "GET");
$baseColumns = 19;
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

foreach ($configurations["data"] as $key => $configuration) {

    $dynamicStyleList[] = "th.column-" . $configuration["data"]["CQP_ALIAS"] . " .dt-column-title";

    $dynamicStyle .= "
    th.column-" . $configuration["data"]["CQP_ALIAS"] . " {
        background-color:#" . $configuration["data"]["CQP_COLOR"] . " !important;
    }
    ";
    
    $columnsReport[] = [
        "name" => 'TOTAL ' . $configuration["data"]["CQP_ALIAS"] . ' SHARE %',
        "color" => $configuration["data"]["CQP_COLOR"]
    ];
    
    $dynamicColumns1 .= '
        { title: "TOTAL ' . $configuration["data"]["CQP_ALIAS"] . ' SHARE %", name: "", orderable: false ' . $percentageFormat .' },
    ';

    $columnsClass[$key] = [
        "className" => "column-" . $configuration["data"]["CQP_ALIAS"],
        "targets" => [$baseColumns]
    ];

    $baseColumns++;
}

// Default forte column calculation
$forteDefColumn = count($columnsClass);

$columnsClass[$forteDefColumn] = [
    "className" => "column-FORTE",
    "targets" => [$baseColumns]
];

$baseColumns++;

$columnsReport[] = [
    "name" => 'TOTAL FORTE SHARE %',
    "color" => $DEFAULT_FORTE_COLOR
];

$columnsReport[] = [
    "name" => 'STOCK MAX. EXP FORTE SHARE %',
    "color" => $DEFAULT_FORTE_COLOR
];

$columnsReport[] = [
    "name" => 'STOCK AVERAGE EXP FORTE SHARE %',
    "color" => $DEFAULT_FORTE_COLOR
];

$columnsClass[$forteDefColumn]["targets"][] = $baseColumns;
$baseColumns++;
$columnsClass[$forteDefColumn]["targets"][] = $baseColumns;
$baseColumns++;

foreach ($configurations["data"] as $key => $configuration) {

    $dynamicColumns2 .=  '
        { title: "STOCK MAX. EXP ' . $configuration["data"]["CQP_ALIAS"] . ' SHARE %", name: "", orderable: false ' . $moneyFormat  .' },
        { title: "STOCK AVERAGE EXP ' . $configuration["data"]["CQP_ALIAS"] . ' SHARE %", name: "", orderable: false ' . $moneyFormat  .' },
    ';
    
    $columnsReport[] = [
        "name" => 'STOCK MAX. EXP ' . $configuration["data"]["CQP_ALIAS"] . ' SHARE %',
        "color" => $configuration["data"]["CQP_COLOR"]
    ];
    
    $columnsReport[] = [
        "name" => 'STOCK AVERAGE EXP ' . $configuration["data"]["CQP_ALIAS"] . ' SHARE %',
        "color" => $configuration["data"]["CQP_COLOR"]
    ];

    $columnsClass[$key]["targets"][] = $baseColumns;
    $baseColumns++;
    $columnsClass[$key]["targets"][] = $baseColumns;
    $baseColumns++;

}

$columnsClass[count($columnsClass)] = [
    "className" => "column-LOCATION",
    "targets" => [6, 7, 8, 9, 10, 11, 12]
];

$columnsClass[count($columnsClass)] = [
    "className" => "column-AREA",
    "targets" => [13, 14]
];

$columnsReport[] = [
    "name" => 'LIMITS 100% EEL',
    "color" => $DEFAULT_COLUMN_COLOR
];

$columnsReport[] = [
    "name" => 'CAT LIMIT AGG.',
    "color" => $DEFAULT_COLUMN_COLOR
];

$columnsClass[$forteDefColumn]["targets"][] = $baseColumns;
$baseColumns++;
$columnsClass[$forteDefColumn]["targets"][] = $baseColumns;
$baseColumns++;

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
    th.column-LOCATION {
        background-color:#E49EDD !important;
    }
    th.column-AREA {
        background-color:#FFFF00 !important;
    }
    th.column-FORTE {
        background-color:#' . $DEFAULT_FORTE_COLOR . ' !important;
    }
    ' . $dynamicStyle . '
    ' . implode(",", $dynamicStyleList) . ', th.column-FORTE .dt-column-title {
        color: black;
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
    th.column-LOCATION>.dt-column-header, th.column-AREA>.dt-column-header {
        color: black;
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
        const apiURL = "' . $apiHost . '/pstools/script/cqp_accumulations_data";
        window.startColumn = "S"
        window.headerConfiguration = ' . json_encode($columnsReport) . '
        window.collectionConf = ' . json_encode(array_column($configurations["data"], "data")) . '.map((element, index) => {
            return {
                CQP_REINSURER: element.CQP_REINSURER,
                CQP_ALIAS: element.CQP_ALIAS
            };
        });

        let columnList = [
            { title: "Request ID", name: "id"},
            { title: "FROM", name: "CQP_INCEPTION_DATE"},
            { title: "TO", name: "CQP_EXPIRATION_DATE"},
            { title: "ASSURED", name: "CQP_INSURED_NAME"},
            { title: "COUNTRY", name: "CQP_COUNTRY"},
            { title: "CITY", name: "CQP_CITY"},
            { title: "LOCATION", name: "CQP_LOCATION"},
            { title: "CRESTA ZONE", name: "CQP_CRESTA_ZONE"},
            { title: "EQ", name: "CQP_EQ"},
            { title: "WIND", name: "CQP_WIND"},
            { title: "FLOOD", name: "CQP_FLOOD"},
            { title: "Accumulation/concentration", name: "CQP_ACUMULACION"},
            { title: "Clasificación NAT CAT (Munich Re style)", name: "CQP_NAT_CAT"},
            { title: "AREA", name: "CQP_AREA"},
            { title: "MANZANA", name: "CQP_MANZANA"},
            { title: "ADDRESS", name: "CQP_ADDRESS"},
            { title: "GEOGRAPHIC COORDINATES", name: "CQP_COORDINATES"},
            { title: "STOCK AVERAGE EXP.", name: "CQP_STOCK_AVERAGE_EXP" ' . $moneyFormat  .' },
            { title: "STOCK MAX EXP.", name: "CQP_STOCK_MAX_EXP" ' . $moneyFormat  .' },
            ' . $dynamicColumns1 . '
            { title: "TOTAL FORTE SHARE %", name: "CQP_REINSURER_FORTE_SHARE" ' . $percentageFormat  .' },
            { title: "STOCK MAX. EXP FORTE SHARE %", name: "CQP_MARKETS_FORTE_STOCK_MAX", orderable: false ' . $moneyFormat  .' },
            { title: "STOCK AVERAGE EXP FORTE SHARE %", name: "CQP_MARKETS_FORTE_STOCK_AVERAGE", orderable: false ' . $moneyFormat  .' },
            ' . $dynamicColumns2 . '
            { title: "LIMITS 100% EEL", name: "CQP_STORAGE_EEL" ' . $moneyFormat  .' },
            { title: "CAT LIMIT AGG.", name: "CQP_STORAGE_AGG" ' . $moneyFormat  .' }
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
            info: false,
            columnDefs: ' . json_encode($columnsClass) . ',
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