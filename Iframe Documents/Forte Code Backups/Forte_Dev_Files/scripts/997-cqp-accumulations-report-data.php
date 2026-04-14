<?php 

/*****************************************
* get data for PsTools Report Seacrh in table
*
* by Diego Tapia
*****************************************/

// Get columns configured for report
$confColumns = array_column($data["columns"], "CQP_REINSURER");

// Set filter for sql query
$filter = "";
$pmql = "";
$pmqlArray = [];
$pmqlor = [];

// Set a pmql search string (multi-fragment: split by backslash "\")
if (isset($data['CQP_SEARCH']) && $data['CQP_SEARCH'] !== "" && $data['CQP_SEARCH'] !== null) {

    // Convert input to lowercase for case-insensitive search
    $input = strtolower($data['CQP_SEARCH']);

    // Split input ONLY by backslash "\" and remove empty values
    $parts = array_filter(explode("\\", $input));

    // Loop through each search fragment
    foreach ($parts as $part) {

        // Normalize and escape input:
        // - Convert to lowercase
        // - Replace escaped quotes
        // - Escape single quotes to avoid SQL issues
        $part = str_replace("'", "\\'", str_replace('\"', '"', strtolower($part)));

        // OR conditions for this fragment
        $pmqlor = [];

        // ===== JSON fields (via data->>) =====
        $pmqlor[] = "LOWER(data->>'$.CQP_INCEPTION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_EXPIRATION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INSURED_NAME') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_EEL') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_AGG') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURER_FORTE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TOTAL_FORTE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MARKETS') LIKE \"%{$part}%\"";

        // ===== Plain columns (not JSON) =====
        $pmqlor[] = "LOWER(CQP_CITY) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_COUNTRY) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_MARKETS) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_ADDRESS) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_COORDINATES) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_STOCK_AVERAGE_EXP) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_STOCK_MAX_EXP) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_MANZANA) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_AREA) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(CQP_LOCATION_DETAILS) LIKE \"%{$part}%\"";

        // Each part must match at least one field → OR inside, AND outside
        $pmqlArray[] = "(" . implode(" OR ", $pmqlor) . ")";
    }
}

// ----------------------
// Date filters (AND)
// ----------------------
if (isset($data['START_DATE']) && $data['START_DATE'] !== "" && $data['START_DATE'] !== null) {
    $pmqlArray[] = "data->>'$.CQP_INCEPTION_DATE' >= '" . $data['START_DATE'] . "'";
}

if (isset($data['END_DATE']) && $data['END_DATE'] !== "" && $data['END_DATE'] !== null) {
    $pmqlArray[] = "data->>'$.CQP_INCEPTION_DATE' <= '" . $data['END_DATE'] . "'";
}

// ----------------------
// Build final PMQL filter
// ----------------------
if (count($pmqlArray) > 0) {
    $pmql = implode(" AND ", $pmqlArray);
    $filter = " and " . $pmql;
}


// Get requests
$columnsSql = "";

$requestsSql = "SELECT pr.id as id,
        pr.data->>'$.CQP_INCEPTION_DATE' as CQP_INCEPTION_DATE, 
        pr.data->>'$.CQP_EXPIRATION_DATE' as CQP_EXPIRATION_DATE, 
        pr.data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME, 
        cities.CQP_COUNTRY as CQP_COUNTRY,
        cities.CQP_CITY as CQP_CITY,
        addr.CQP_ADDRESS as CQP_ADDRESS,
        addr.CQP_COORDINATES as CQP_COORDINATES,
        addr.CQP_STOCK_AVERAGE_EXP as CQP_STOCK_AVERAGE_EXP,
        addr.CQP_STOCK_MAX_EXP as CQP_STOCK_MAX_EXP,
        addr.CQP_LOCATION_DETAILS as CQP_LOCATION_DETAILS,
        addr.CQP_LOCATION as CQP_LOCATION,
        addr.CQP_CRESTA_ZONE as CQP_CRESTA_ZONE,
        addr.CQP_EQ as CQP_EQ,
        addr.CQP_WIND as CQP_WIND,
        addr.CQP_FLOOD as CQP_FLOOD,
        addr.CQP_ACUMULACION as CQP_ACUMULACION,
        addr.CQP_NAT_CAT as CQP_NAT_CAT,
        CASE 
           WHEN cities.CQP_COUNTRY = 'PANAMÁ' THEN addr.CQP_MANZANA
           ELSE ''
        END AS CQP_MANZANA,
        CASE 
           WHEN cities.CQP_COUNTRY = 'PANAMÁ' THEN addr.CQP_AREA
           ELSE ''
        END AS CQP_AREA,
        pr.data->>'$.CQP_STORAGE_EEL' as CQP_STORAGE_EEL, 
        pr.data->>'$.CQP_STORAGE_AGG' as CQP_STORAGE_AGG, 
        pr.data->>'$.CQP_REINSURER_FORTE_SHARE' as CQP_REINSURER_FORTE_SHARE,
        pr.data->>'$.CQP_TOTAL_FORTE_SHARE' as CQP_TOTAL_FORTE_SHARE,
        pr.data->>'$.CQP_PERILS' as CQP_PERILS,
        addr.CQP_MARKETS as CQP_MARKETS
    FROM process_requests pr
    CROSS JOIN JSON_TABLE(
        pr.data->'$.CQP_CITIES',
        '$[*]' COLUMNS (
            city_index FOR ORDINALITY,
            CQP_CITY VARCHAR(100) PATH '$.CQP_CITY',
            CQP_COUNTRY VARCHAR(100) PATH '$.CQP_COUNTRY',
            CQP_ADDRESS_LIST JSON PATH '$.CQP_ADDRESS'
        )
    ) AS cities
    CROSS JOIN JSON_TABLE(
        cities.CQP_ADDRESS_LIST,
        '$[*]' COLUMNS (
            address_index FOR ORDINALITY,
            CQP_ADDRESS VARCHAR(500) PATH '$.ADDRESS',
            CQP_COORDINATES VARCHAR(200) PATH '$.COORDINATES',
            CQP_STOCK_MAX_EXP INT PATH '$.CQP_STOCK_MAX_EXP',
            CQP_STOCK_AVERAGE_EXP INT PATH '$.CQP_STOCK_AVERAGE_EXP',
            CQP_MARKETS JSON PATH '$.CQP_MARKETS',
            CQP_LOCATION_DETAILS JSON PATH '$.CQP_LOCATION',
            CQP_LOCATION VARCHAR(500) PATH '$.CQP_LOCATION.CQP_LOCATION',
            CQP_CRESTA_ZONE VARCHAR(500) PATH '$.CQP_LOCATION.CQP_CRESTA_ZONE',
            CQP_EQ VARCHAR(500) PATH '$.CQP_LOCATION.CQP_EQ',
            CQP_WIND VARCHAR(500) PATH '$.CQP_LOCATION.CQP_WIND',
            CQP_FLOOD VARCHAR(500) PATH '$.CQP_LOCATION.CQP_FLOOD',
            CQP_ACUMULACION VARCHAR(500) PATH '$.CQP_LOCATION.CQP_ACUMULACION',
            CQP_NAT_CAT VARCHAR(500) PATH '$.CQP_LOCATION.CQP_NAT_CAT',
            CQP_MANZANA VARCHAR(500) PATH '$.CQP_MANZANA',
            CQP_AREA VARCHAR(500) PATH '$.CQP_AREA'
        )
    ) AS addr
    where (name = 'Cargo Quotation - Parallel Process' or name = 'Cargo - Accumulations process') " . $filter . 
    " ORDER BY " . $data["order"][0]["name"] . " " . $data["order"][0]["dir"]; 
    
$requestsSqlLimit = $requestsSql . " limit " . $data["length"] . " OFFSET " . $data["start"];
$requests = getSqlData("POST", $requestsSqlLimit);
$response = [];

// Count the total results
$count = "SELECT COUNT(*) as total_rows FROM (" . $requestsSql . ") AS union_result;";
$requestsTotal = getSqlData("POST", $count)[0]["total_rows"];

// Set variables with columns and format
$columnsFormat = [
    [
        "variable" => "id", 
        "format" => null
    ],
    [
        "variable" => "CQP_INCEPTION_DATE",
        "format" => null
    ],
    [
        "variable" => "CQP_EXPIRATION_DATE",
        "format" => null
    ],
    [
        "variable" => "CQP_INSURED_NAME",
        "format" => null
    ],
    [
        "variable" => "CQP_COUNTRY",
        "format" => null
    ],
    [
        "variable" => "CQP_CITY",
        "format" => null
    ],
    [
        "variable" => "CQP_LOCATION",
        "format" => null
    ],
    [
        "variable" => "CQP_CRESTA_ZONE",
        "format" => null
    ],
    [
        "variable" => "CQP_EQ",
        "format" => null
    ],
    [
        "variable" => "CQP_WIND",
        "format" => null
    ],
    [
        "variable" => "CQP_FLOOD",
        "format" => null
    ],
    [
        "variable" => "CQP_ACUMULACION",
        "format" => null
    ],
    [
        "variable" => "CQP_NAT_CAT",
        "format" => null
    ],
    [
        "variable" => "CQP_MANZANA",
        "format" => null
    ],
    [
        "variable" => "CQP_AREA",
        "format" => null
    ],
    [
        "variable" => "CQP_ADDRESS",
        "format" => null
    ],
    [
        "variable" => "CQP_COORDINATES",
        "format" => null
    ],
    [
        "variable" => "CQP_STOCK_AVERAGE_EXP",
        "format" => null
    ],
    [
        "variable" => "CQP_STOCK_MAX_EXP",
        "format" => null
    ]
];

// Set dinamic columns
$headerList = [];

foreach($data["columns"] as $conf) {
    $columnsFormat[] = [
        "variable" => "CQP_MARKETS_" . $conf["CQP_ALIAS"] . "_TOTAL_SHARE", 
        "format" => null
    ];

    $headerList[] = [
        "variable" => "CQP_MARKETS_" . $conf["CQP_ALIAS"] . "_STOCK_AVERAGE", 
        "format" => null
    ];

    $headerList[] = [
        "variable" => "CQP_MARKETS_" . $conf["CQP_ALIAS"] . "_STOCK_MAX", 
        "format" => null
    ];
}

$columnsFormat[] = [
    "variable" => "CQP_TOTAL_FORTE_SHARE", 
    "format" => null
];

$columnsFormat[] = [
    "variable" => "CQP_MARKETS_FORTE_STOCK_AVERAGE", 
    "format" => null
];

$columnsFormat[] = [
    "variable" => "CQP_MARKETS_FORTE_STOCK_MAX", 
    "format" => null
];

$columnsFormat = array_merge($columnsFormat, $headerList);

$columnsFormat[] = [
    "variable" => "CQP_MARKETS_FORTE_EEL_LIMIT", 
    "format" => null
];

$columnsFormat[] = [
    "variable" => "CQP_MARKETS_FORTE_CAT_LIMIT", 
    "format" => null
];

// Format response array
foreach($requests as &$request) {
    if ($request["CQP_MARKETS"] != null && $request["CQP_MARKETS"] != "" && $request["CQP_MARKETS"] != "null") {
        $markets = json_decode($request["CQP_MARKETS"]);

        foreach ($markets as $market) {
            $confColumnSelect = array_search($market->CQP_REINSURER, $confColumns);

            if ($confColumnSelect !== false) {
                $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_TOTAL_SHARE"] = $market->CQP_FORTE_SHARE;
                $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_EEL_LIMIT"] = $market->CQP_EEL_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_CAT_LIMIT"] = $market->CQP_CAT_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_STOCK_MAX"] = $market->CAP_STOCK_MAX_EXP_REINSURER_SHARE;
                $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_STOCK_AVERAGE"] = $market->CQP_STOCK_AVERAGE_EXP_SCOR_SHARE;
                
                if ($data["columns"][$confColumnSelect]["CQP_ALIAS"] == "AUSTRAL") {
                    $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_RETENTION"] = $market->CQP_AUSTRAL_RETENTION;
                    $request["CQP_MARKETS_" . $data["columns"][$confColumnSelect]["CQP_ALIAS"] . "_RETRO_AXA"] = $market->CQP_AXA;
                }
            }

            if ($market->CQP_REINSURER == "FORTE") {
                $request["CQP_MARKETS_FORTE_TOTAL_SHARE"] = $market->CQP_FORTE_SHARE;
                $request["CQP_MARKETS_FORTE_EEL_LIMIT"] = $market->CQP_EEL_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_FORTE_CAT_LIMIT"] = $market->CQP_CAT_LIMIT_REINSURER_SHARE_USD;
                $request["CQP_MARKETS_FORTE_STOCK_MAX"] = $market->CAP_STOCK_MAX_EXP_REINSURER_SHARE;
                $request["CQP_MARKETS_FORTE_STOCK_AVERAGE"] = $market->CQP_STOCK_AVERAGE_EXP_SCOR_SHARE;
            }
        }
    }

    $tempRow = [];
    
    foreach ($columnsFormat as $key => $column) {
        if ($request[$column["variable"]] == "null" || $request[$column["variable"]] == null || $request[$column["variable"]] == "") {
            $tempRow[] = "";
        } elseif ($column["format"] == null) {
            $tempRow[] = $column["variable"] == "" ? "" : $request[$column["variable"]];
        } else {
            switch ($column["format"]) {
                case 'date_month':
                    $tempRow[] = date("m", strtotime($request[$column["variable"]]));
                    break;
                case 'date_year':
                    $tempRow[] = date("Y", strtotime($request[$column["variable"]]));
                    break;
                default:
                    $tempRow[] = $request[$column["variable"]];
                    break;
            }
        }
    }
    
    $response[] = $tempRow;
}

return [
    "draw" => $data["draw"],
    "recordsTotal" => $requestsTotal,
    "recordsFiltered" => $requestsTotal,
    "data" => $response,
    "sqlQuery" => $requestsSql,
    "responseFormat" => $columnsFormat
];


/* 
 * Call Processmaker API with Guzzle
 *
 * @param string $requestType
 * @param array $postdata
 * @param bool $contentFile
 * @return array $res 
 *
 * by Diego Tapia
 */
function getSqlData ($requestType, $postdata = [], bool $contentFile = false) {
    $headers = [
        "Accept" => $acceptType,
        "Authorization" => "Bearer " . getenv("API_TOKEN"),
        "Content-Type" => $contentFile ? "'application/octet-stream'" : "application/json"

    ];
    $client = new \GuzzleHttp\Client([
        'verify' => false,
    ]);
    $request = new \GuzzleHttp\Psr7\Request($requestType, getenv('API_HOST') . getenv('API_SQL'), $headers, json_encode(["SQL" => base64_encode($postdata)]));
    try {
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
        if ($contentFile === false) {
            $res = json_decode($res, true);
        }
    } catch (\GuzzleHttp\Exception\RequestException | \Exception | \Error | \Throwable $e) {
        $res = [
            'error_message' => $e->getMessage(),
            'error_detail' => base64_encode($e->getFile() . ":" . ($e->getLine() ?? '')),
            'error_response' => $e->getResponse() ?? ''
        ];
    }
    return $res;
}