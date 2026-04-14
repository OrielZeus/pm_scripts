<?php 

/*****************************************
* get data for PsTools Report Seacrh in table
*
* by Diego Tapia
*****************************************/

// Set filter for sql query
$currency = $data["currency"] == "USD" ? "_USD_VALUE" : "";
$filter = "";
$requestsSql = "";
$sqlCount = "";
$pmql = "";
$pmqlArray = [];
$pmqlor = [];


// Set a pmql search string (multi-fragment: split by backslash "\")
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

        // OR conditions for this fragment
        $pmqlor = [];

        // ===== Add LIKE filters for multiple fields (case-insensitive) =====
        $pmqlor[] = "LOWER(data->>'$.id') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(market.CQP_REINSURER) LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_N_INSTALLMENTS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TRANSIT_USD_100') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MONTHS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TYPE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_PIVOT_TABLE_NUMBER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INSURED_NAME') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_ACTION') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_CEDANT') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COUNTRY') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURANCE_BROKER') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INCEPTION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_EXPIRATION_DATE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.dateToday') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_UNDERWRITING_YEAR') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_INCEPTION_DATE') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_INCEPTION_DATE') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_INTEREST') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_COMMODITIES_PROFILE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_EEL') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_AGG') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_USD_TOTAL') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_STORAGE_GWP_USD') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MIN_DEP') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURER') LIKE \"%{$part}%\""; // duplicate 
        $pmqlor[] = "LOWER(data->>'$.CQP_REINSURER_FORTE_SHARE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_BROKER_DEDUCTIONS') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_TAX_PERCENTAGE') LIKE \"%{$part}%\"";
        $pmqlor[] = "LOWER(data->>'$.CQP_MANAGEMENT_FEES') LIKE \"%{$part}%\"";

        // Each part must match at least one field → OR inside, AND outside
        $pmqlArray[] = "(" . implode(" OR ", $pmqlor) . ")";
    }
}

// ----------------------
// Date filters (AND)
// ----------------------
if (isset($data['START_DATE']) && $data['START_DATE'] != "" && $data['START_DATE'] != null) {
    $pmqlArray[] = "data->>'$.CQP_INCEPTION_DATE' >= '" . $data['START_DATE'] . "'";
}

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


$requestsColumns = "SELECT id as id,
    market.*,
    data->>'$.CQP_TYPE' as CQP_TYPE,
    data->>'$.CQP_CONTRACT' as CQP_CONTRACT,
    data->>'$.CQP_PIVOT_TABLE_NUMBER' as CQP_PIVOT_TABLE_NUMBER,
    data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME,
    CASE 
        WHEN data->>'$.CQP_RENEWAL_CARGO' = 'YES' THEN 'RENEWAL'
        ELSE 'NEW'
    END as CQP_ACTION, 
    data->>'$.CQP_CEDANT' as CQP_CEDANT,
    data->>'$.CQP_COUNTRY.COUNTRY' as CQP_COUNTRY,
    data->>'$.CQP_REINSURANCE_BROKER.COMPANY_NAME' as CQP_REINSURANCE_BROKER,
    data->>'$.CQP_INCEPTION_DATE' as CQP_INCEPTION_DATE,
    data->>'$.CQP_EXPIRATION_DATE' as CQP_EXPIRATION_DATE,
    data->>'$.dateToday' as dateToday,
    data->>'$.CQP_UNDERWRITING_YEAR' as CQP_UNDERWRITING_YEAR,
    data->>'$.CQP_INTEREST' as CQP_INTEREST,
    data->>'$.CQP_COMMODITIES_PROFILE' as CQP_COMMODITIES_PROFILE,
    data->>'$.CQP_STORAGE_EXPOSURE[0].CQP_STORAGE_EEL" . $currency . "' as CQP_STORAGE_EEL,
    data->>'$.CQP_STORAGE_EXPOSURE[0].CQP_STORAGE_AGG" . $currency . "' as CQP_STORAGE_AGG,
    data->>'$.CQP_USD_TOTAL' as CQP_USD_TOTAL,
    data->>'$.CQP_STORAGE_GWP_USD' as CQP_STORAGE_GWP_USD,
    data->>'$.CQP_TAX_PERCENTAGE" . $currency . "' as CQP_TAX_PERCENTAGE,
    data->>'$.CQP_MANAGEMENT_FEES' as CQP_MANAGEMENT_FEES,
    data->>'$.CQP_MONTHS' as CQP_MONTHS,
    data->>'$.CQP_GWP_PRIMA_DE_AJUSTE" . $currency . "' as CQP_GWP_PRIMA_DE_AJUSTE,
    data->>'$.CQP_GWP_MINDEP_PLUS_AJUSTE" . $currency . "' as CQP_GWP_MINDEP_PLUS_AJUSTE,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_BROKER_DEDUCTIONS_USD' as CQP_BROKER_DEDUCTIONS,
    data->>'$.CQP_TRANSIT[0].CQP_TOTAL_TTP_GWD" . $currency . "' as CQP_TOTAL_TTP_GWD,
    data->>'$.CQP_TRANSIT[0].CQP_TOTAL_TTP_ANNUAL_USD" . $currency . "' as CQP_TOTAL_TTP_ANNUAL_USD,
    data->>'$.CQP_TRANSIT_USD_100" . $currency . "' as CQP_TRANSIT_USD_100,
    data->>'$.CQP_N_INSTALLMENTS' as CQP_N_INSTALLMENTS,
    data->>'$.CQP_GWP_TOTAL_EPI_100" . $currency . "' as CQP_GWP_TOTAL_EPI_100,
    data->>'$.CQP_STORAGE[0].CQP_TOTAL_INVENTORIES_GWP" . $currency . "' as CQP_TOTAL_INVENTORIES_GWP,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_MINDEP_USD" . $currency . "' as CQP_MINDEP_USD,
    data->>'$.CQP_SUMMARY_DETAILS[0].CQP_TAX_USD" . $currency . "' as CQP_TAX_USD_IF_APPLAY,
    data->>'$.TOTALS[0].CQP_UNDERWRITING_EXPENSES' as CQP_UNDERWRITING_EXPENSES,
    data->>'$.CQP_NWP_AT_100" . $currency . "' as CQP_NWP_AT_100,
    data->>'$.CQP_CURRENCY' as CQP_CURRENCY,
    data->>'$.CQP_FX_RATE' as CQP_FX_RATE";

$requestsColumnsH = "SELECT id as id,
    market.*,
    data->>'$.CQP_TYPE' as CQP_TYPE,
    data->>'$.CQP_CONTRACT' as CQP_CONTRACT,
    data->>'$.CQP_PIVOT_TABLE_NUMBER' as CQP_PIVOT_TABLE_NUMBER,
    data->>'$.CQP_INSURED_NAME' as CQP_INSURED_NAME,
    CASE 
        WHEN data->>'$.CQP_RENEWAL_CARGO' = 'YES' THEN 'RENEWAL'
        ELSE 'NEW'
    END as CQP_ACTION, 
    data->>'$.CQP_CEDANT' as CQP_CEDANT,
    data->>'$.CQP_COUNTRY.COUNTRY' as CQP_COUNTRY,
    data->>'$.CQP_REINSURANCE_BROKER.COMPANY_NAME' as CQP_REINSURANCE_BROKER,
    data->>'$.CQP_INCEPTION_DATE' as CQP_INCEPTION_DATE,
    data->>'$.CQP_EXPIRATION_DATE' as CQP_EXPIRATION_DATE,
    '' as dateToday,
    data->>'$.CQP_UNDERWRITING_YEAR' as CQP_UNDERWRITING_YEAR,
    data->>'$.CQP_INTEREST' as CQP_INTEREST,
    data->>'$.CQP_COMMODITIES_PROFILE' as CQP_COMMODITIES_PROFILE,
    data->>'$.CQP_STORAGE_EEL' as CQP_STORAGE_EEL,
    data->>'$.CQP_STORAGE_AGG' as CQP_STORAGE_AGG,
    data->>'$.CQP_USD_TOTAL' as CQP_USD_TOTAL,
    data->>'$.CQP_STORAGE_GWP_USD' as CQP_STORAGE_GWP_USD,
    data->>'$.CQP_TAX_PERCENTAGE' as CQP_TAX_PERCENTAGE,
    data->>'$.CQP_MANAGEMENT_FEES' as CQP_MANAGEMENT_FEES,
    TRUNCATE(data->>'$.CQP_MONTHS', 0) as CQP_MONTHS,
    data->>'$.CQP_GWP_PRIMA_DE_AJUSTE' as CQP_GWP_PRIMA_DE_AJUSTE,
    data->>'$.CQP_GWP_MINDEP_PLUS_AJUSTE' as CQP_GWP_MINDEP_PLUS_AJUSTE,
    data->>'$.CQP_BROKER_DEDUCTIONS' as CQP_BROKER_DEDUCTIONS,
    data->>'$.CQP_TOTAL_TTP_GWD' as CQP_TOTAL_TTP_GWD,
    data->>'$.CQP_TOTAL_TTP_ANNUAL_USD' as CQP_TOTAL_TTP_ANNUAL_USD,
    data->>'$.CQP_TRANSIT_USD_100' as CQP_TRANSIT_USD_100,
    data->>'$.CQP_N_INSTALLMENTS' as CQP_N_INSTALLMENTS,
    data->>'$.CQP_GWP_TOTAL_EPI_100' as CQP_GWP_TOTAL_EPI_100,
    data->>'$.CQP_TOTAL_INVENTORIES_GWP' as CQP_TOTAL_INVENTORIES_GWP,
    data->>'$.CQP_MINDEP_USD' as CQP_MINDEP_USD,
    data->>'$.CQP_TAX_USD_IF_APPLAY' as CQP_TAX_USD_IF_APPLAY,
    data->>'$.CQP_UNDERWRITING_EXPENSES' as CQP_UNDERWRITING_EXPENSES,
    data->>'$.CQP_NWP_AT_100' as CQP_NWP_AT_100,
    data->>'$.CQP_CURRENCY' as CQP_CURRENCY,
    data->>'$.CQP_FX_RATE' as CQP_FX_RATE";

foreach ($data["columns"] as $key => $reinsurer) {
    
    if ($key != 0) {
        $requestsSql .= " UNION ALL ";
        $sqlCount .= " UNION ALL ";
    }

    $requestsSql .= $requestsColumns . 
    " FROM process_requests as " . $reinsurer["CQP_ALIAS"] . "
    CROSS JOIN JSON_TABLE(
        " . $reinsurer["CQP_ALIAS"] . ".data->'$.CQP_MARKETS',
        '$[*]' COLUMNS (
            market_index FOR ORDINALITY,
            taken BOOLEAN PATH '$.taken',
            CQP_REINSURER VARCHAR(100) PATH '$.CQP_REINSURER',
            CQP_USD VARCHAR(100) PATH '$.CQP_USD',
            CQP_REQUIRED VARCHAR(100) PATH '$.CQP_REQUIRED',
            CQP_N_MARKETS VARCHAR(100) PATH '$.CQP_N_MARKETS',
            CQP_FORTE_SHARE VARCHAR(100) PATH '$.CQP_FORTE_SHARE',
            CQP_MAXIMUN_CAP VARCHAR(100) PATH '$.CQP_MAXIMUN_CAP',
            CQP_REINSURANCE_DISTRIBUTION VARCHAR(100) PATH '$.CQP_REINSURANCE_DISTRIBUTION',
            CQP_GWP_TRANSIT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_TRANSIT_REINSURER_SHARE" . $currency . "',
            CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE" . $currency . "',
            CQP_GWP_STORAGE_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_STORAGE_REINSURER_SHARE" . $currency . "',
            CQP_GWP_TOTAL_EPI_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_TOTAL_EPI_REINSURER_SHARE" . $currency . "',
            CQP_GWP_MINDEP_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_MINDEP_REINSURER_SHARE" . $currency . "',
            CQP_BROKER_DEDUCTION_USD VARCHAR(100) PATH '$.CQP_BROKER_DEDUCTION_USD" . $currency . "',
            CQP_TAX VARCHAR(100) PATH '$.CQP_TAX" . $currency . "',
            CQP_NWP_TRANSIT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_NWP_TRANSIT_REINSURER_SHARE" . $currency . "',
            CQP_NWP_STORAGE_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_NWP_STORAGE_REINSURER_SHARE" . $currency . "',
            CQP_NWP_TOTAL_EPI_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_NWP_TOTAL_EPI_REINSURER_SHARE" . $currency . "',
            CQP_TRANSIT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_TRANSIT_REINSURER_SHARE" . $currency . "',
            CQP_STORAGE_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_STORAGE_REINSURER_SHARE" . $currency . "',
            CQP_BROKER_DEDUCTION_TOTAL VARCHAR(100) PATH '$.CQP_BROKER_DEDUCTION_TOTAL" . $currency . "',
            CQP_BROKER_DEDUCTION_ADJUSTMENT VARCHAR(100) PATH '$.CQP_BROKER_DEDUCTION_ADJUSTMENT" . $currency . "',
            CQP_CAT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_CAT_REINSURER_SHARE" . $currency . "',
            CQP_FORTE_FEE_USD VARCHAR(100) PATH '$.CQP_FORTE_FEE_USD" . $currency . "',
            CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE" . $currency . "',
            CQP_NWP_STORAGE_REINSURER_SHARE_EXC_TAX VARCHAR(100) PATH '$.CQP_NWP_STORAGE_REINSURER_SHARE_EXC_TAX" . $currency . "',
            CQP_TAX_TOTAL VARCHAR(100) PATH '$.CQP_TAX_TOTAL" . $currency . "',
            CQP_TAX_ADJUSTMENT VARCHAR(100) PATH '$.CQP_TAX_ADJUSTMENT" . $currency . "',
            CQP_NWP_MINDEP_REINSURER_SHARE  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_REINSURER_SHARE" . $currency . "',
            CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX" . $currency . "',
            CQP_NWP_ADJUSTMENT_REINSURER_SHARE  VARCHAR(100) PATH '$.CQP_NWP_ADJUSTMENT_REINSURER_SHARE" . $currency . "',
            CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX  VARCHAR(100) PATH '$.CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX" . $currency . "',
            CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE" . $currency . "',
            CQP_FORTE_FEE_TOTAL  VARCHAR(100) PATH '$.CQP_FORTE_FEE_TOTAL" . $currency . "',
            CQP_FORTE_FEE_ADJUSTMENT  VARCHAR(100) PATH '$.CQP_FORTE_FEE_ADJUSTMENT" . $currency . "',
            CQP_NET_CEDED_PREMIUM_MINDEP  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM_MINDEP" . $currency . "',
            CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT" . $currency . "',
            CQP_NET_CEDED_PREMIUM  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM" . $currency . "',
            CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX" . $currency . "',
            CQP_AUSTRAL_RETENTION  VARCHAR(100) PATH '$.CQP_AUSTRAL_RETENTION',
            CQP_AXA  VARCHAR(100) PATH '$.CQP_AXA'
        )
    ) AS market
    WHERE " . $reinsurer["CQP_ALIAS"] . ".name = 'Cargo Quotation Process' and data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN' " . $filter . "
    AND market.CQP_REINSURER = '" . $reinsurer["CQP_REINSURER"] . "'
    AND market.taken = TRUE"; 

    $search = [
        "Austral RE" => "AUSTRAL RE",
        "Echo RE" => "ECHO RE",
        "Scor" => "SCOR",
        "Munich RE" => "MUNICH RE"
    ];

    $requestsSql .= " UNION ALL " . $requestsColumnsH . 
    " FROM collection_" . $data["collection"]  . " as " . $reinsurer["CQP_ALIAS"] . "_H
    CROSS JOIN JSON_TABLE(
        " . $reinsurer["CQP_ALIAS"] . "_H.data->'$.CQP_MARKETS',
        '$[*]' COLUMNS (
            market_index FOR ORDINALITY,
            taken BOOLEAN PATH '$.taken',
            CQP_REINSURER VARCHAR(100) PATH '$.CQP_REINSURER',
            CQP_USD VARCHAR(100) PATH '$.CQP_USD',
            CQP_REQUIRED VARCHAR(100) PATH '$.CQP_REQUIRED',
            CQP_N_MARKETS VARCHAR(100) PATH '$.CQP_N_MARKETS',
            CQP_FORTE_SHARE VARCHAR(100) PATH '$.CQP_FORTE_SHARE',
            CQP_MAXIMUN_CAP VARCHAR(100) PATH '$.CQP_MAXIMUN_CAP',
            CQP_REINSURANCE_DISTRIBUTION VARCHAR(100) PATH '$.CQP_REINSURANCE_DISTRIBUTION',
            CQP_GWP_TRANSIT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_TRANSIT_REINSURER_SHARE',
            CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE',
            CQP_GWP_STORAGE_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_STORAGE_REINSURER_SHARE',
            CQP_GWP_TOTAL_EPI_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_TOTAL_EPI_REINSURER_SHARE',
            CQP_GWP_MINDEP_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_MINDEP_REINSURER_SHARE',
            CQP_BROKER_DEDUCTION_USD VARCHAR(100) PATH '$.CQP_BROKER_DEDUCTION_USD',
            CQP_TAX VARCHAR(100) PATH '$.CQP_TAX',
            CQP_NWP_TRANSIT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_NWP_TRANSIT_REINSURER_SHARE',
            CQP_NWP_STORAGE_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_NWP_STORAGE_REINSURER_SHARE',
            CQP_NWP_TOTAL_EPI_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_NWP_TOTAL_EPI_REINSURER_SHARE',
            CQP_TRANSIT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_TRANSIT_REINSURER_SHARE',
            CQP_STORAGE_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_STORAGE_REINSURER_SHARE',
            CQP_BROKER_DEDUCTION_TOTAL VARCHAR(100) PATH '$.CQP_BROKER_DEDUCTION_TOTAL',
            CQP_BROKER_DEDUCTION_ADJUSTMENT VARCHAR(100) PATH '$.CQP_BROKER_DEDUCTION_ADJUSTMENT',
            CQP_CAT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_CAT_REINSURER_SHARE',
            CQP_FORTE_FEE_USD VARCHAR(100) PATH '$.CQP_FORTE_FEE_USD',
            CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE VARCHAR(100) PATH '$.CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE',
            CQP_NWP_STORAGE_REINSURER_SHARE_EXC_TAX VARCHAR(100) PATH '$.CQP_NWP_STORAGE_REINSURER_SHARE_EXC_TAX',
            CQP_TAX_TOTAL VARCHAR(100) PATH '$.CQP_TAX_TOTAL',
            CQP_TAX_ADJUSTMENT VARCHAR(100) PATH '$.CQP_TAX_ADJUSTMENT',
            CQP_NWP_MINDEP_REINSURER_SHARE  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_REINSURER_SHARE',
            CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX',
            CQP_NWP_ADJUSTMENT_REINSURER_SHARE  VARCHAR(100) PATH '$.CQP_NWP_ADJUSTMENT_REINSURER_SHARE',
            CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX  VARCHAR(100) PATH '$.CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX',
            CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE',
            CQP_FORTE_FEE_TOTAL  VARCHAR(100) PATH '$.CQP_FORTE_FEE_TOTAL',
            CQP_FORTE_FEE_ADJUSTMENT  VARCHAR(100) PATH '$.CQP_FORTE_FEE_ADJUSTMENT',
            CQP_NET_CEDED_PREMIUM_MINDEP  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM_MINDEP',
            CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT',
            CQP_NET_CEDED_PREMIUM  VARCHAR(100) PATH '$.CQP_NET_CEDED_PREMIUM',
            CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX  VARCHAR(100) PATH '$.CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX',
            CQP_AUSTRAL_RETENTION VARCHAR(100) PATH '$.CQP_AUSTRAL_RETENTION',
            CQP_AXA VARCHAR(100) PATH '$.CQP_AXA'
        )
    ) AS market
    WHERE market.CQP_REINSURER = '" . $search[$reinsurer["CQP_REINSURER"]] . "'" . $filter; 

    $sqlCount .= "select id as id_" . $reinsurer["CQP_ALIAS"] . " FROM process_requests as " . $reinsurer["CQP_ALIAS"] . "
    CROSS JOIN JSON_TABLE(
        " . $reinsurer["CQP_ALIAS"] . ".data->'$.CQP_MARKETS',
        '$[*]' COLUMNS (
            market_index FOR ORDINALITY,
            taken BOOLEAN PATH '$.taken',
            CQP_REINSURER VARCHAR(100) PATH '$.CQP_REINSURER'
        )
    ) AS market
    WHERE " . $reinsurer["CQP_ALIAS"] . ".name = 'Cargo Quotation Process' and data->>'$.CQP_CARGO_CURRENT_STATUS' = 'BOUND/QUOTE TAKEN' " . $filter . "
    AND market.CQP_REINSURER = '" . $reinsurer["CQP_REINSURER"] . "'
    AND market.taken = TRUE
    UNION ALL
    select id as id_" . $reinsurer["CQP_ALIAS"] . "_H FROM collection_" . $data["collection"]  . " as " . $reinsurer["CQP_ALIAS"] . "_H
    CROSS JOIN JSON_TABLE(
        " . $reinsurer["CQP_ALIAS"] . "_H.data->'$.CQP_MARKETS',
        '$[*]' COLUMNS (
            market_index FOR ORDINALITY,
            taken BOOLEAN PATH '$.taken',
            CQP_REINSURER VARCHAR(100) PATH '$.CQP_REINSURER'
        )
    ) AS market
    WHERE market.CQP_REINSURER = '" . $search[$reinsurer["CQP_REINSURER"]] . "'" . $filter;
} 

$requestsSqlLimit = $requestsSql . " ORDER BY " . $data["order"][0]["name"] . " " . $data["order"][0]["dir"] . " limit " . $data["length"] . " OFFSET " . $data["start"];
$requests = getSqlData("POST", $requestsSqlLimit);

// Count the total results
$count = "SELECT COUNT(*) as total_rows FROM (" . $sqlCount . ") AS union_result;";
$requestsTotal = getSqlData("POST", $count)[0]["total_rows"];

// Set variables with columns and format
$response = [];
$columnsFormat = [
    [
        "variable" => "id", 
        "format" => null
    ],
    [
        "variable" => "", 
        "format" => null
    ],
    [
        "variable" => "CQP_REINSURER",
        "format" => "fixName"
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
        "variable" => "CQP_UNDERWRITING_YEAR",
        "format" => null
    ],
    [
        "variable" => "",
        "format" => null
    ],
    [
        "variable" => "CQP_INCEPTION_DATE",
        "format" => "date_month"
    ],
    [
        "variable" => "CQP_INCEPTION_DATE",
        "format" => "quarter_date"
    ],
    [
        "variable" => "",
        "format" => null
    ],
    [
        "variable" => "",
        "format" => null
    ],
    [
        "variable" => "",
        "format" => null
    ],
    [
        "variable" => "",
        "format" => null
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
        "variable" => "CQP_TOTAL_TTP_ANNUAL_USD", 
        "format" => null
    ],
    [
        "variable" => "CQP_TRANSIT_USD_100",
        "format" => null
    ],
    [
        "variable" => "CQP_STORAGE_EEL",
        "format" => null
    ],
    [
        "variable" => "CQP_STORAGE_AGG",
        "format" => null
    ],
    [
        "variable" => "CQP_TOTAL_TTP_GWD",
        "format" => null
    ],
    [
        "variable" => "CQP_TOTAL_INVENTORIES_GWP",
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
        "variable" => "CQP_GWP_PRIMA_DE_AJUSTE",
        "format" => null
    ],
    [ 
        "variable" => "CQP_GWP_MINDEP_PLUS_AJUSTE", 
        "format" => null 
    ], 
    [
        "variable" => "CQP_REINSURER",
        "format" => "fixName"
    ],
    [ 
        "variable" => "CQP_FORTE_SHARE", 
        "format" => null 
    ],
    [
        "variable" => "CQP_AUSTRAL_RETENTION",
        "format" => null
    ],
    [
        "variable" => "CQP_AXA",
        "format" => null
    ],
    [
        "variable" => "CQP_GWP_TRANSIT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_GWP_STORAGE_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_GWP_TOTAL_EPI_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_GWP_MINDEP_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_GWP_ADJUSTMENT_ADD_PREMIUM_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_GWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_BROKER_DEDUCTIONS",
        "format" => null
    ],
    [
        "variable" => "CQP_BROKER_DEDUCTION_USD",
        "format" => null
    ],
    [
        "variable" => "CQP_BROKER_DEDUCTION_ADJUSTMENT",
        "format" => null
    ],
    [
        "variable" => "CQP_BROKER_DEDUCTION_TOTAL",
        "format" => null
    ],
    [
        "variable" => "CQP_TAX_USD_IF_APPLAY",
        "format" => null
    ],
    [
        "variable" => "CQP_TAX",
        "format" => null
    ],
    [
        "variable" => "CQP_TAX_ADJUSTMENT",
        "format" => null
    ],
    [
        "variable" => "CQP_TAX_TOTAL",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_AT_100",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_TRANSIT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_STORAGE_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_TOTAL_EPI_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_MINDEP_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_MINDEP_REINSURER_SHARE_EXC_TAX",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_ADJUSTMENT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_ADJUSTMENT_REINSURER_SHARE_EXC_TAX",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_NWP_MINDEP_PLUS_ADJUSTMENT_REINSURER_SHARE_EXC_TAX",
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
        "variable" => "CQP_FORTE_FEE_ADJUSTMENT",
        "format" => null
    ],
    [
        "variable" => "CQP_FORTE_FEE_TOTAL",
        "format" => null
    ],
    [
        "variable" => "CQP_NET_CEDED_PREMIUM_MINDEP",
        "format" => null
    ],
    [
        "variable" => "CQP_NET_CEDED_PREMIUM_MINDEP_ADJUSTMENT",
        "format" => null
    ],
    [
        "variable" => "CQP_NET_CEDED_PREMIUM",
        "format" => null
    ],
    [
        "variable" => "CQP_TRANSIT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_STORAGE_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_CAT_REINSURER_SHARE",
        "format" => null
    ],
    [
        "variable" => "CQP_N_INSTALLMENTS",        
        "format" => null
    ],
    [
        "variable" => "CQP_FX_RATE",        
        "format" => null
    ],
    [
        "variable" => "CQP_CURRENCY",        
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
                case 'quarter_date':
                    $valMonth = (int)date("m", strtotime($request[$column["variable"]]));
                    
                    if ($valMonth <= 3) {
                        $result = "3rd";
                    } elseif ($valMonth <= 6) {
                        $result = "4th";
                    } elseif ($valMonth <= 9) {
                        $result = "1st";
                    } else {
                        $result = "2nd";
                    }
                    
                    $tempRow[] = $result;
                    break;
                case 'fixName':
                    $search = [
                        "AUSTRAL" => "Austral RE",
                        "ECHO RE" =>"Echo RE",
                        "SCOR" => "Scor",
                        "MUNICH RE" => "Munich RE",
                        "AUSTRAL RE" => "Austral RE",
                    ];

                    $searchText = $search[strtoupper($request[$column["variable"]])];
                    
                    if ($searchText == null) {
                        $searchText = $request[$column["variable"]];
                    }

                    $tempRow[] = $searchText;
                    break;
                default:
                    $tempRow[] = $column["variable"] == "" ? "" : $request[$column["variable"]];
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