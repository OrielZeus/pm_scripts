<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

// -----------------------------------------------------
// 1) CONFIGURACIONES INICIALES
// -----------------------------------------------------
/*
$data = [
    "getUserVariable" => "",
    "from" => "2024-10-01",
    "to" => "2025-01-29",
    "users" => "gina.park,sarah.kim,myung.yim,sunny.chong,myung.lee,minkyoung.oh,jiyeoun.kim,ilene.park,eunjoo.yi,youngsook.seo,lydia.kim,ethan.park,heesun.kim,hyejoo.jung,jinhee.park,hyunseon.park,rebekah.kim",
    "generateCSV" => false,
    "currentUser" => "413",
    "userIDBranch" => "413",
    "dynamicPmql" => "(initiated_at%20%3E%3D%20%222024-10-01%22)%20AND%20(initiated_at%20%3C%3D%20%222025-01-29%22)%20AND%20(name%20!%3D%20%22Acroforms%22)%20AND%20((requester%20%3D%20%22gina.park%22)%20OR%20(...)%20...)"
];
*/

$allowedProcesses = [
    7,
    8,
    9,
    10,
    11,
    12,
    14,
    15,
    16,
    17,
    18,
    19,
    20,
    21,
    41,
    49,
    50,
    51,
    54,
    55,
    56,
    57,
    58,
    59,
    60,
    64,
    65,
    66,
    71,
    74,
    77,
    80,
    83,
    86,
    89
];
$allowedProcessesStr = implode(",", $allowedProcesses);

// Tomamos las variables de fechas del array $data
$fromDate = $data['from'] ?? '2024-10-01';
$toDate   = $data['to']   ?? '2025-01-29';


// Tomamos la lista de usuarios (viene en un string separado por comas)
$userString = $data['users'] ?? '';
if (empty($fromDate) || empty($toDate) || empty($userString)) {
    // You can customize the HTML
    $html = "
    <div style='margin: 20px; padding: 10px; background-color: #f2dede; color: #a94442;'>
        <strong>Attention!</strong> 
        Please fill in the Date fields <em>(From / To)</em> and select one or more users to view the information.
    </div>
    ";

    // Return the HTML (or the type of response you normally use)
    return ['PSTOOLS_RESPONSE_HTML' => $html];
}
$usersArray = explode(',', $userString);

// Token y Host (puedes ajustarlo a tus variables de entorno)
$apiToken = getenv('API_TOKEN');
$apiHost  = getenv('API_HOST');
$urlPathPsToolsPackage = $apiHost . '/admin/package-proservice-tools/sql';

// -----------------------------------------------------
// 2) OBTENER IDs DE USUARIO
// -----------------------------------------------------
$usernames = implode("','", $usersArray);
$sqlGetUserIds = "SELECT id FROM users WHERE username IN ('$usernames')";
$getUserIdsResponse = apiGuzzle($urlPathPsToolsPackage, "POST", encodeSql($sqlGetUserIds));

$userIds = array_column($getUserIdsResponse, 'id');
if (empty($userIds)) {
    return ["error" => "No users found"];
}
$userIdsStr = implode(",", $userIds);

// -----------------------------------------------------
// 3) OBTENER MÉTRICAS GLOBALES (para las tarjetas)
//    Filtramos por fecha (initiated_at >= $fromDate y <= $toDate)
// -----------------------------------------------------
$sqlProcessRequests = "
    SELECT
        SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'ERROR' THEN 1 ELSE 0 END) AS errors,
        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = 'CANCELED' THEN 1 ELSE 0 END) AS canceled,
        COUNT(id) AS total_requests
    FROM process_requests
    WHERE user_id IN ($userIdsStr)
      AND process_id IN ($allowedProcessesStr)
      AND initiated_at >= '$fromDate'
      AND initiated_at <= '$toDate'
";
$processRequestsResponse = apiGuzzle($urlPathPsToolsPackage, "POST", encodeSql($sqlProcessRequests));

$inProgress    = $processRequestsResponse[0]['in_progress'] ?? 0;
$errors        = $processRequestsResponse[0]['errors'] ?? 0;
$countRecords  = $processRequestsResponse[0]['completed'] ?? 0;
$totalRequests = $processRequestsResponse[0]['total_requests'] ?? 0;
$canceled      = $processRequestsResponse[0]['canceled'] ?? 0;


$sqlDeliveryMethod = "
    SELECT
        SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(data, '$.deliveryMethod')) = 'Scan QR Code on Another Device' THEN 1 ELSE 0 END) AS qr_count,
        SUM(CASE WHEN JSON_UNQUOTE(JSON_EXTRACT(data, '$.deliveryMethod')) != 'Scan QR Code on Another Device' THEN 1 ELSE 0 END) AS email_count
        SUM(
            CASE 
                WHEN JSON_UNQUOTE(JSON_EXTRACT(data, '$.deliveryMethod')) IS NULL
                     OR JSON_UNQUOTE(JSON_EXTRACT(data, '$.deliveryMethod')) = '' 
                THEN 1 
                ELSE 0 
            END
        ) AS no_action_taken
    FROM process_requests
    WHERE user_id IN ($userIdsStr)
      AND process_id IN ($allowedProcessesStr)
      AND initiated_at >= '$fromDate'
      AND initiated_at <= '$toDate'
";
$deliveryMethodResp = apiGuzzle($urlPathPsToolsPackage, "POST", encodeSql($sqlDeliveryMethod));

$qrCount    = $deliveryMethodResp[0]['qr_count'] ?? 0;
$emailCount = $deliveryMethodResp[0]['email_count'] ?? 0;
$deliveryChartData = json_encode([
    ["name" => "QR Code", "y" => (int)$qrCount],
    ["name" => "Email", "y" => (int)$emailCount]
]);



// -----------------------------------------------------
// 4) OBTENER TODA LA INFORMACIÓN PARA LA TABLA (SIN PAGINACIÓN)
//    También filtramos por fecha
// -----------------------------------------------------
$sqlTable = "
    SELECT
        r.id AS request_id,
        r.case_number,
        p.name AS process_name,
        r.status,
        r.completed_at
    FROM process_requests r
    JOIN processes p ON p.id = r.process_id
    WHERE r.user_id IN ($userIdsStr)
      AND process_id IN ($allowedProcessesStr)
      AND r.initiated_at >= '$fromDate'
      AND r.initiated_at <= '$toDate'
    ORDER BY r.id DESC
";
$tableResp = apiGuzzle($urlPathPsToolsPackage, "POST", encodeSql($sqlTable));
$sqlByProcessAndDelivery = "
    SELECT
        p.name AS process_name,
        JSON_UNQUOTE(JSON_EXTRACT(r.data, '$.deliveryMethod')) AS delivery_method,
        COUNT(r.id) AS total_count
    FROM process_requests r
    JOIN processes p ON p.id = r.process_id
    WHERE r.user_id IN ($userIdsStr)
      AND process_id IN ($allowedProcessesStr)
      AND r.initiated_at >= '$fromDate'
      AND r.initiated_at <= '$toDate'
    GROUP BY p.name, delivery_method
    ORDER BY p.name, delivery_method
";
$byProcessDeliveryResp = apiGuzzle($urlPathPsToolsPackage, "POST", encodeSql($sqlByProcessAndDelivery));
$processNames = [];
$emailData = [];
$qrData = [];

foreach ($byProcessDeliveryResp as $row) {
    $pname = $row['process_name'];
    $method = $row['delivery_method'];
    $count = (int)$row['total_count'];

    if (!in_array($pname, $processNames)) {
        $processNames[] = $pname;
    }
    if ($method == 'Scan QR Code on Another Device') {
        $qrData[$pname] = $count;
    } else {
        $emailData[$pname] = $count;
    }
}

// Normaliza para todos los procesos (pone 0 si no hay data para ese método)
$emailSeries = [];
$qrSeries = [];
foreach ($processNames as $pname) {
    $emailSeries[] = $emailData[$pname] ?? 0;
    $qrSeries[] = $qrData[$pname] ?? 0;
}



// -----------------------------------------------------
// 5) OBTENER DATOS PARA EL GRÁFICO “REQUESTS BY PROCESS” (DRILLDOWN)
//    Igualmente, filtramos por fecha
// -----------------------------------------------------
$sqlByProcessAndStatus = "
    SELECT
        p.id AS process_id,
        p.name AS process_name,
        r.status,
        COUNT(r.id) AS total_count
    FROM process_requests r
    JOIN processes p ON p.id = r.process_id
    WHERE r.user_id IN ($userIdsStr)
      AND process_id IN ($allowedProcessesStr)
      AND r.initiated_at >= '$fromDate'
      AND r.initiated_at <= '$toDate'
    GROUP BY p.id, p.name, r.status
    ORDER BY p.id
";
$byProcessStatusResp = apiGuzzle($urlPathPsToolsPackage, "POST", encodeSql($sqlByProcessAndStatus));

// 5a) Armamos estructura de datos para Highcharts Drilldown
$processData = [];
foreach ($byProcessStatusResp as $row) {
    $pid   = $row['process_id'];
    $pname = $row['process_name'];
    $st    = $row['status'];
    $cnt   = (int)$row['total_count'];

    if (!isset($processData[$pid])) {
        $processData[$pid] = [
            'name'     => $pname,
            'total'    => 0,
            'statuses' => []
        ];
    }
    $processData[$pid]['total'] += $cnt;
    $processData[$pid]['statuses'][$st] = $cnt;
}

// 5b) Construimos las series para Highcharts
$seriesData      = [];
$drilldownSeries = [];
foreach ($processData as $pid => $pdata) {
    $processName = $pdata['name'];
    $total       = $pdata['total'];
    $drilldownId = "process_" . $pid;

    $seriesData[] = [
        'name'      => $processName,
        'y'         => $total,
        'drilldown' => $drilldownId
    ];

    // Arma la data [ [ 'In Progress', 5 ], [ 'Errors', 3 ], ... ]
    $drillData = [];
    foreach ($pdata['statuses'] as $st => $count) {
        switch ($st) {
            case 'ACTIVE':
                $stLabel = 'In Progress';
                break;
            case 'ERROR':
                $stLabel = 'Errors';
                break;
            case 'COMPLETED':
                $stLabel = 'Completed';
                break;
            default:
                $stLabel = $st;
        }
        $drillData[] = [$stLabel, $count];
    }

    $drilldownSeries[] = [
        'id'   => $drilldownId,
        'name' => $processName,
        'data' => $drillData,
    ];
}

// -----------------------------------------------------
// 6) CONSTRUIR EL HTML CON EL ORDEN DESEADO
// -----------------------------------------------------
$html = '
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/drilldown.js"></script>
</head>
<body>

<div class="container mt-6">

    <!-- TARJETAS al inicio -->
    <div class="row">
        <!-- Empty Section 1 -->
        <div class="col-md-1"></div>
        <!-- In Progress -->
        <div class="col-md-2 d-flex align-items-stretch mb-2">
            <div class="card w-100 text-white" style="background-color: #78A641;">
                <div class="card-body d-flex">
                    <div class="m-auto text-center">
                        <i class="fas fa-clipboard-list fa-2x mb-2"></i>
                        <h5 class="mb-0">' . $inProgress . '</h5>
                        <small>In Progress</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Errors -->
        <div class="col-md-2 d-flex align-items-stretch mb-2">
            <div class="card w-100 text-white" style="background-color: #D63A3A;">
                <div class="card-body d-flex">
                    <div class="m-auto text-center">
                        <i class="fas fa-times fa-2x mb-2"></i>
                        <h5 class="mb-0">' . $errors . '</h5>
                        <small>Errors</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Canceled -->
        <div class="col-md-2 d-flex align-items-stretch mb-2">
            <div class="card w-100 text-white" style="background-color: #D6A03A;">
                <div class="card-body d-flex">
                    <div class="m-auto text-center">
                        <i class="fas fa-ban fa-2x mb-2"></i>
                        <h5 class="mb-0">' . $canceled . '</h5>
                        <small>Canceled</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Completed -->
        <div class="col-md-2 d-flex align-items-stretch mb-2">
            <div class="card w-100 text-white" style="background-color: #12A2A8;">
                <div class="card-body d-flex">
                    <div class="m-auto text-center">
                        <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                        <h5 class="mb-0">' . $countRecords . '</h5>
                        <small>Completed</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Requests -->
        <div class="col-md-2 d-flex align-items-stretch mb-2">
            <div class="card w-100 text-white" style="background-color: #1F83B4;">
                <div class="card-body d-flex">
                    <div class="m-auto text-center">
                        <i class="fas fa-clipboard fa-2x mb-2"></i>
                        <h5 class="mb-0">' . $totalRequests . '</h5>
                        <small>Total Requests</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Empty Section-->
        <div class="col-md-1">
            
        </div>
    </div>
    <div class="mt-5" id="chart-delivery-by-process" style="height: 400px;"></div>

    <!-- CHART DE REQUESTS BY PROCESS (DRILLDOWN) ABAJO DE LAS TARJETAS -->
    <div class="mt-4" id="chart-by-process" style="height: 500px;"></div>

    <!-- TABLA AL FINAL -->
    <div class="mt-4" id="requests-table">
        <h5>Request Details</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>Request ID</th>
                        <th>Case #</th>
                        <th>Process</th>
                        <th>Status</th>
                        <th>Completed</th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody>
';



// Llenamos la tabla
if (!empty($tableResp)) {
    foreach ($tableResp as $row) {
        $rid   = htmlspecialchars($row["request_id"], ENT_QUOTES);
        $cnum  = htmlspecialchars($row["case_number"], ENT_QUOTES);
        $pname = htmlspecialchars($row["process_name"], ENT_QUOTES);
        $st    = htmlspecialchars($row["status"], ENT_QUOTES);

        $comp = $row["completed_at"]
            ? htmlspecialchars($row["completed_at"], ENT_QUOTES)
            : "In Progress";

        $html .= "
                    <tr>
                        <td>{$rid}</td>
                        <td>{$cnum}</td>
                        <td>{$pname}</td>
                        <td>{$st}</td>
                        <td>{$comp}</td>
                        <td>
                            <a title=\"Open Record\" 
                            href=\"https://hanmi-bank.cloud.processmaker.net/requests/{$rid}\" 
                            target=\"_self\" 
                            class=\"p-2 text-primary\">
                            <i class=\"fas fa-caret-square-right fa-lg fa-fw\"></i>
                            </a>
                        </td>
                    </tr>
        ";
    }
} else {
    $html .= '
                    <tr>
                        <td colspan="6" class="text-center">No requests found</td>
                    </tr>
    ';
}

$html .= '
                </tbody>
            </table>
        </div>
    </div>

</div> <!-- container -->

<script>
    // Solo dejamos el CHART DE REQUESTS BY PROCESS con DRILLDOWN

    const seriesData = ' . json_encode($seriesData) . ';
    const drilldownSeries = ' . json_encode($drilldownSeries) . ';

    Highcharts.chart("chart-by-process", {
        chart: { type: "column" },
        title: { text: "Requests by Process (Click to see status breakdown)" },
        xAxis: {
            type: "category",
            labels: { rotation: -45 }
        },
        yAxis: {
            title: { text: "Number of Requests" }
        },
        legend: { enabled: false },
        plotOptions: {
            series: {
                borderWidth: 0,
                dataLabels: {
                    enabled: true,
                    format: "{point.y}"
                }
            }
        },
        tooltip: {
            headerFormat: "<span style=\'font-size:11px\'>{series.name}</span><br>",
            pointFormat: "<span style=\'color:{point.color}\'>{point.name}</span>: <b>{point.y}</b><br/>"
        },
        series: [
            {
                name: "Processes",
                colorByPoint: true,
                data: seriesData
            }
        ],
        drilldown: {
            series: drilldownSeries
        }
    });
</script>';

$html .= "<script>
    Highcharts.chart('chart-delivery-by-process', {
        chart: {
            type: 'column'
        },
        title: {
            text: 'Requests by Process and Delivery Method'
        },
        xAxis: {
            categories: " . json_encode($processNames) . ",
            labels: { rotation: -45 }
        },
        yAxis: {
            min: 0,
            title: { text: 'Number of Requests' }
        },
        legend: { enabled: true },
        tooltip: {
            shared: true
        },
        plotOptions: {
            column: {
                grouping: true,
                dataLabels: {
                    enabled: true
                }
            }
        },
        series: [{
            name: 'Email',
            data: " . json_encode($emailSeries) . ",
            color: '#4e5ed6'
        }, {
            name: 'QR Code',
            data: " . json_encode($qrSeries) . ",
            color: '#36c2f5'
        }]
    });
</script>

</body>
";


// (Opcional) Reemplazo de delimitadores <| y </| si tu sistema lo requiere
$html = str_replace(['<|', '</|'], ['<', '</'], $html);

// Devolver la respuesta al motor (o a donde necesites)
return ["PSTOOLS_RESPONSE_HTML" => $html];

/* -------------------------------------------------------------------
 *   FUNCIONES AUXILIARES
 * -------------------------------------------------------------------
 */
function apiGuzzle(string $url, string $requestType, array $postdata = [])
{
    static $apiToken = null;
    if ($apiToken === null) {
        $apiToken = getenv('API_TOKEN');
    }
    $headers = [
        "Accept"        => "application/json",
        "Authorization" => "Bearer " . $apiToken,
        "Content-Type"  => "application/json",
    ];
    $client = new Client(['verify' => false]);
    $request = new Request($requestType, $url, $headers, json_encode($postdata));

    try {
        $res = $client->sendAsync($request)->wait();
        return json_decode($res->getBody()->getContents(), true);
    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}

function encodeSql($query)
{
    // Ajusta según requiera tu API
    return ["SQL" => base64_encode($query)];
}
