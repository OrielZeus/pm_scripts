<?php 

/*****************************************
* get data for PsTools Report Seacrh in table
*
* by Diego Tapia
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
        $part = str_replace("'", "\\'", str_replace('\"', '"', strtolower($part)));

        // Array to store OR conditions for this fragment
        $pmqlor = [];

        // ===== Add LIKE filters for multiple fields (case-insensitive) =====
        $pmqlor[] = "LOWER(data->>'$.CQP_TYPE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_UNDERWRITER_USER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_PREMIUM_PER_INSTALLMENT') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_N_INSTALLMENTS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COMBINED_RATE_USD') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_CONTRACT') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_PIVOT_TABLE_NUMBER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INSURED_NAME') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_CEDANT') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COUNTRY') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_EXPOSURE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURANCE_BROKER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_SUBMITION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TRANSIT') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_SUBMITION_DATE') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_INCEPTION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_EXPIRATION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_CARGO_CURRENT_STATUS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_EXPOSURE') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_MONTHS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_NWP_TOTAL_EPI_MRE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.dateToday') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INTEREST') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COMMODITIES_PROFILE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_CURRENCY') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_SUMMARY_DETAILS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_SUMMARY_DETAILS') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_SUMMARY_DETAILS') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_TOTAL_FORTE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TAX_PERCENTAGE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MANAGEMENT_FEES') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MRE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_GWP_TOTAL_EPI_MRE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_GWP_MINDEP_MRE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_NWP_MINDEP_MRE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MARKETS') LIKE \"%{$part}%\"";

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
// Exact match filter
// ----------------------
if (isset($data['CQP_SELECT_REINSURER']) && $data['CQP_SELECT_REINSURER'] != "" && $data['CQP_SELECT_REINSURER'] != null) {
    $pmqlArray[] = "data->>'$.CQP_INSURED_CODE' = '" . $data['CQP_SELECT_REINSURER'] . "'";
}

// ----------------------
// Build final PMQL filter
// ----------------------
if (count($pmqlArray) > 0) {
    $pmql = implode(" AND ", $pmqlArray);
    $filter = " and " . $pmql;
}


// Count the total results
$requestsSql = "SELECT count(id) as total
    FROM process_requests
    where name = 'Cargo Quotation Process' and data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN' " . $filter;
    
// Get requests
$requestsTotal = getSqlData("POST", $requestsSql)[0]["total"];
$currency = $data["currency"] == "USD" ? "_USD_VALUE" : "";

$requestsSql = "SELECT id as id,
    market.*,
    data->>'$.CQP_TYPE' as CQP_TYPE,
    data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME,
    CASE 
        WHEN data->>'$.CQP_RENEWAL_CARGO' = 'YES' THEN 'RENEWAL'
        ELSE 'NEW'
    END as CQP_ACTION,
    data->>'$.CQP_CEDANT' as CQP_CEDANT,
    data->>'$.CQP_FILE_NO' as CQP_FILE_NO,
    data->>'$.CQP_DEDUCTIBLE' as CQP_DEDUCTIBLE,
    data->>'$.CQP_N_INSTALLMENTS' as CQP_N_INSTALLMENTS,
    data->>'$.CQP_PIVOT_TABLE_NUMBER' as CQP_PIVOT_TABLE_NUMBER,
    data->>'$.CQP_CONTRACT' as CQP_CONTRACT,
    data->>'$.CQP_COUNTRY.COUNTRY' as CQP_COUNTRY,
    data->>'$.CQP_STORAGE_EXPOSURE[0].CQP_STORAGE_AGG" . $currency . "' as CQP_STORAGE_AGG,
    data->>'$.CQP_TRANSIT[0].CQP_TOTAL_TTP_ANNUAL_USD" . $currency . "' as CQP_TOTAL_TTP_ANNUAL_USD,
    data->>'$.CQP_REINSURANCE_BROKER.COMPANY_NAME' as CQP_REINSURANCE_BROKER,
    data->>'$.CQP_SUBMITION_DATE' as CQP_SUBMITION_DATE,
    data->>'$.CQP_GWP_TOTAL_EPI_100" . $currency . "' as CQP_GWP_TOTAL_EPI_100,
    data->>'$.CQP_SUBMITION_DATE' as CQP_SUBMITION_DATE,
    data->>'$.CQP_INCEPTION_DATE' as CQP_INCEPTION_DATE,
    data->>'$.CQP_EXPIRATION_DATE' as CQP_EXPIRATION_DATE,
    data->>'$.CQP_CARGO_CURRENT_STATUS' as CQP_CARGO_CURRENT_STATUS,
    data->>'$.CQP_STORAGE_EXPOSURE[0].CQP_STORAGE_EEL' as CQP_STORAGE_EEL,
    data->>'$.CQP_MONTHS' as CQP_MONTHS,
    data->>'$.dateToday' as dateToday,
    data->>'$.CQP_INTEREST' as CQP_INTEREST,
    data->>'$.CQP_COMMODITIES_PROFILE' as CQP_COMMODITIES_PROFILE,
    data->>'$.CQP_CURRENCY' as CQP_CURRENCY,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_MINDEP_USD" . $currency . "' as CQP_MINDEP_USD,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_BROKER_DEDUCTIONS_USD' as CQP_BROKER_DEDUCTIONS,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_TAX_USD' as CQP_TAX_USD,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_COMBINED_RATE_USD' as CQP_COMBINED_RATE_USD,
    data->>'$.CQP_TOTAL_FORTE_SHARE' as CQP_TOTAL_FORTE_SHARE,
    data->>'$.CQP_TAX_PERCENTAGE" . $currency . "'  as CQP_TAX_PERCENTAGE,
    data->>'$.CQP_MANAGEMENT_FEES' as CQP_MANAGEMENT_FEES,
    data->>'$.CQP_MRE_SHARE' as CQP_MRE_SHARE,
    data->>'$.CQP_UNDERWRITER_USER' as CQP_UNDERWRITER_USER,
    data->>'$.TOTALS[0].CQP_UNDERWRITING_EXPENSES' as CQP_UNDERWRITING_EXPENSES,
    data->>'$.CQP_MARKETS' as CQP_MARKETS
    FROM process_requests as MRE
    CROSS JOIN JSON_TABLE(
        MRE.data->'$.CQP_MARKETS',
        '$[*]' COLUMNS (
            market_index FOR ORDINALITY,
            taken BOOLEAN PATH '$.taken',
            CQP_REINSURER VARCHAR(100) PATH '$.CQP_REINSURER',
            CQP_NET_CEDED_PREMIUM_MINDEP  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM_MINDEP" . $currency . "',
            CQP_NET_CEDED_PREMIUM  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM" . $currency . "',
            CQP_FORTE_FEE_USD VARCHAR(100) PATH '$.CQP_FORTE_FEE_USD" . $currency . "',
            CQP_GWP_TOTAL_EPI_MRE_SHARE VARCHAR(100) PATH '$.CQP_GWP_TOTAL_EPI_REINSURER_SHARE" . $currency . "',
            CQP_GWP_MINDEP_MRE_SHARE VARCHAR(100) PATH '$.CQP_GWP_MINDEP_REINSURER_SHARE" . $currency . "',
            CQP_NWP_TOTAL_EPI_MRE_SHARE VARCHAR(100) PATH '$.CQP_NWP_TOTAL_EPI_REINSURER_SHARE" . $currency . "',
            CQP_NWP_MINDEP_MRE_SHARE  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_REINSURER_SHARE" . $currency . "',
            CQP_PREMIUM_PER_INSTALLMENT  VARCHAR(100) PATH '$.CQP_PREMIUM_PER_INSTALLMENT" . $currency . "'
        )
    ) AS market
    WHERE MRE.name = 'Cargo Quotation Process' and data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN'  " . $filter . "
    AND market.CQP_REINSURER = 'Munich RE'
     ORDER BY " . $data["order"][0]["name"] . " " . $data["order"][0]["dir"]; 

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
        "variable" => "CQP_PIVOT_TABLE_NUMBER", 
        "format" => null
    ],    
    [
        "variable" => "CQP_FILE_NO", 
        "format" => null
    ],    
    [
        "variable" => "CQP_CONTRACT", 
        "format" => null
    ],    
    [
        "variable" => "CQP_INSURED_NAME", 
        "format" => null
    ],    
    [
        "variable" => "CQP_ACTION", 
        "format" => null
    ],    
    [
        "variable" => "CQP_CEDANT", 
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
        "variable" => "CQP_SUBMITION_DATE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_SUBMITION_DATE", 
        "format" => "date_month"
    ],    
    [
        "variable" => "CQP_CARGO_CURRENT_STATUS", 
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
        "variable" => "CQP_MONTHS", 
        "format" => null
    ],    
    [
        "variable" => "CQP_INCEPTION_DATE", 
        "format" => "date_year"
    ],    
    [
        "variable" => "CQP_INCEPTION_DATE", 
        "format" => "date_month"
    ],    
    [
        "variable" => "CQP_INTEREST", 
        "format" => null
    ],    
    [
        "variable" => "CQP_COMMODITIES_PROFILE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_CURRENCY", 
        "format" => null
    ],    
    [
        "variable" => "CQP_TOTAL_TTP_ANNUAL_USD", 
        "format" => null
    ],    
    [
        "variable" => "CQP_STORAGE_AGG", 
        "format" => null
    ],    
    [
        "variable" => "CQP_COMBINED_RATE_USD", 
        "format" => null
    ],    
    [
        "variable" => "CQP_GWP_TOTAL_EPI_100", 
        "format" => null
    ],    
    [
        "variable" => "CQP_MINDEP_USD", 
        "format" => null
    ],    
    [
        "variable" => "CQP_MRE_SHARE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_GWP_TOTAL_EPI_MRE_SHARE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_GWP_MINDEP_MRE_SHARE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_BROKER_DEDUCTIONS", 
        "format" => null
    ],    
    [
        "variable" => "CQP_TAX_USD", 
        "format" => null
    ],    
    [
        "variable" => "CQP_NWP_TOTAL_EPI_MRE_SHARE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_NWP_MINDEP_MRE_SHARE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_UNDERWRITING_EXPENSES", 
        "format" => null
    ],    
    [
        "variable" => "CQP_FORTE_FEE_USD", 
        "format" => null
    ],    
    [
        "variable" => "CQP_NET_CEDED_PREMIUM", 
        "format" => null
    ],    
    [
        "variable" => "CQP_NET_CEDED_PREMIUM_MINDEP", 
        "format" => null
    ],    
    [
        "variable" => "CQP_N_INSTALLMENTS", 
        "format" => null
    ],    
    [
        "variable" => "CQP_DEDUCTIBLE", 
        "format" => null
    ],    
    [
        "variable" => "CQP_PREMIUM_PER_INSTALLMENT", 
        "format" => null
    ],    
    [
        "variable" => "CQP_UNDERWRITER_USER", 
        "format" => null
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
                case 'date_year':
                    $tempRow[] = date("Y", strtotime($request[$column["variable"]]));
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