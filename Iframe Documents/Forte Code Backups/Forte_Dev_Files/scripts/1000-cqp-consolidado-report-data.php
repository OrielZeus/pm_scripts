<?php 

/*****************************************
* get data for PsTools Report Seacrh in table
*
* by Diego Tapia
* edited by Cristian Ferrufino
*****************************************/

// Set filter for sql query
$filter = "";
$pmql = "";
$pmqlArray = [];
$pmqlor = [];

// Set a pmql search string
if (isset($data['CQP_SEARCH']) && $data['CQP_SEARCH'] != "" && $data['CQP_SEARCH'] != null) {

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
        $part = str_replace("'", "\'", str_replace('\"', '"', strtolower($part)));

        // Array to store OR conditions for this fragment
        $pmqlor = [];

        // Add LIKE filters for multiple fields (case-insensitive)
        $pmqlor[] = "LOWER(data->>'$.CQP_TYPE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INSURED_NAME') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INCEPTION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COUNTRY') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_CARGO_CURRENT_STATUS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(updated_at) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURANCE_BROKER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COMMODITIES_PROFILE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MIN_DEP') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TOTAL_TTP_GWD') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TOTAL_FORTE_SHARE') LIKE \"%{$part}%\"";

        // Conditional field (Reason logic based on quote and broker status)
        $pmqlor[] = "LOWER(
            CASE 
                WHEN data->>'$.CQP_QUOTE' = 'NO' THEN data->>'$.CQP_REASON'
                WHEN data->>'$.CQP_QUOTE' = 'YES' AND data->>'$.CQP_BROKER_STATUS' = 'CLOSE' 
                    THEN data->>'$.CQP_NOT_QUOTE_REASON'
                ELSE '' 
            END
        ) LIKE \"%{$part}%\"";

        // Conditional field (Comments logic based on status and quote)
        $pmqlor[] = "LOWER(
            CASE 
                WHEN data->>'$.CQP_STATUS' = 'DECLINED' THEN data->>'$.CQP_ADOBE_DECLINED_COMMENT'
                WHEN data->>'$.CQP_QUOTE' = 'NO' THEN data->>'$.CQP_COMMENTS'
                WHEN data->>'$.CQP_QUOTE' = 'YES' AND data->>'$.CQP_BROKER_STATUS' = 'CLOSE' 
                    THEN data->>'$.CQP_NOT_QUOTE_COMMENTS'
                ELSE '' 
            END
        ) LIKE \"%{$part}%\"";

        // Each part must match at least one field → OR inside, AND outside
        $pmqlArray[] = "(" . implode(" OR ", $pmqlor) . ")";
    }
}

// ----------------------
// Date filters
// ----------------------

// Filter: start date (greater than or equal)
if (isset($data['START_DATE']) && $data['START_DATE'] != "" && $data['START_DATE'] != null) {
    $pmqlArray[] = "data->>'$.CQP_INCEPTION_DATE' >= '" . $data['START_DATE'] . "'";
}

// Filter: end date (less than or equal)
if (isset($data['END_DATE']) && $data['END_DATE'] != "" && $data['END_DATE'] != null) {
    $pmqlArray[] = "data->>'$.CQP_INCEPTION_DATE' <= '" . $data['END_DATE'] . "'";
}

// ----------------------
// Build final PMQL filter
// ----------------------

if (count($pmqlArray) > 0) {
    // Combine all conditions with AND
    $pmql = implode(" AND ", $pmqlArray);

    // Append to main SQL filter
    $filter = " and " . $pmql;
}

// ----------------------
// Final query
// ----------------------

// Count total matching records from process_requests
$requestsSql = "SELECT count(id) as total
    FROM process_requests
    WHERE name = 'Cargo Quotation Process'" . $filter;

// Get requests
$requestsTotal = getSqlData("POST", $requestsSql)[0]["total"];

$requestsSql = "SELECT id,
    data->>'$.CQP_TYPE' as CQP_TYPE,
    data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME,
    data->>'$.CQP_INCEPTION_DATE' as CQP_INCEPTION_DATE,
    data->>'$.CQP_COUNTRY.COUNTRY' as CQP_COUNTRY,
    data->>'$.CQP_CARGO_CURRENT_STATUS' as CQP_STATUS,
    data->>'$.CQP_REINSURANCE_BROKER.COMPANY_NAME' as CQP_REINSURANCE_BROKER,
    data->>'$.CQP_COMMODITIES_PROFILE' as CQP_COMMODITIES_PROFILE,
    data->>'$.CQP_TRANSIT[0].CQP_TOTAL_TTP_GWD' as CQP_TOTAL_TTP_GWD,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_MINDEP_USD' as CQP_MIN_DEP,
    data->>'$.CQP_TOTAL_FORTE_SHARE' as CQP_TOTAL_FORTE_SHARE,
    CASE 
        WHEN data->>'$.CQP_QUOTE' = 'NO' THEN data->>'$.CQP_REASON'
        WHEN data->>'$.CQP_QUOTE' = 'YES' AND data->>'$.CQP_BROKER_STATUS' = 'CLOSE' THEN data->>'$.CQP_NOT_QUOTE_REASON'
        ELSE ''
    END as CQP_COMMENTS,
    CASE 
        WHEN data->>'$.CQP_STATUS' = 'DECLINED' THEN data->>'$.CQP_ADOBE_DECLINED_COMMENT'
        WHEN data->>'$.CQP_QUOTE' = 'NO' THEN data->>'$.CQP_COMMENTS'
        WHEN data->>'$.CQP_QUOTE' = 'YES' AND data->>'$.CQP_BROKER_STATUS' = 'CLOSE' THEN data->>'$.CQP_NOT_QUOTE_COMMENTS'
        ELSE ''
    END as CQP_ACCOUNT_STATUS,
    updated_at as updated_at
    FROM process_requests
    where name = 'Cargo Quotation Process'" . $filter . 
    " ORDER BY " . $data["order"][0]["name"] . " " . $data["order"][0]["dir"]; 

$requestsSqlLimit = $requestsSql . " limit " . $data["length"] . " OFFSET " . $data["start"];
$requests = getSqlData("POST", $requestsSqlLimit);
$response = [];

// Set variables with columns and format
$columnsFormat = [
    [
        "variable" => "id", 
        "format" => null
    ],
    [
        "variable" => "CQP_TYPE", 
        "format" => null
    ],
    [
        "variable" => "CQP_INSURED_NAME", 
        "format" => null
    ],
    [
        "variable" => "CQP_INCEPTION_DATE", 
        "format" => "date_month"
    ],
    [
        "variable" => "CQP_STATUS", 
        "format" => null
    ],
    [
        "variable" => "CQP_COUNTRY", 
        "format" => null
    ],
    [
        "variable" => "CQP_REINSURANCE_BROKER", 
        "format" => null
    ],
    [
        "variable" => "CQP_COMMODITIES_PROFILE", 
        "format" => null
    ],
    [
        "variable" => "CQP_TOTAL_TTP_GWD", 
        "format" => null
    ],
    [
        "variable" => "CQP_MIN_DEP", 
        "format" => null
    ],
    [
        "variable" => "CQP_TOTAL_FORTE_SHARE", 
        "format" => null
    ],
    [
        "variable" => "CQP_COMMENTS", 
        "format" => null
    ],
    [
        "variable" => "CQP_ACCOUNT_STATUS", 
        "format" => null
    ],
    [
        "variable" => "updated_at", 
        "format" => "DDMMAA"
    ]
];

// Format response array
foreach($requests as $request) {
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
                case 'DDMMAA':
                    $timestamp = strtotime($request[$column["variable"]]); //
                    $tempRow[] = date('d/m/Y', $timestamp);
                    break;
                default:
                    return $request[$column["variable"]];
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