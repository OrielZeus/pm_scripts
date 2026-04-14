<?php

return [];

/*  
 * Initializes CQP quotation variables with default values
 *
 * @param none
 * @return array Default CQP data structure
 *
 * by Adriana Centellas
 */
/*
return [

"CQP_TOTAL_FORTE_SHARE" => null,

"CQP_TRANSIT_EXPOSURE" => [[
    "form_html_viewer" => null,
    "CQP_MLI_USD" => null,
    "CQP_MLI_HISTORICAL" => 0,
    "CQP_MLE_USD" => 0,
    "CQP_MLE_HISTORICAL" => 0,
    "CQP_MLD_USD" => null,
    "CQP_MLD_HISTORICAL" => 0,
    "CQP_MLI_TOTAL_FORTE_USD" => 0,
    "CQP_MLI_VARIATION" => 0,
    "CQP_MLE_TOTAL_FORTE_USD" => 0,
    "CQP_MLE_VARIATION" => 0,
    "CQP_MLD_TOTAL_FORTE_USD" => 0,
    "CQP_MLD_VARIATION" => 0,
    "CQP_TRANSIT_MAX_USD" => 0
]],

"CQP_STORAGE_EXPOSURE" => [[
    "form_html_viewer" => null,
    "CQP_STORAGE_EEL" => 0,
    "CQP_HISTORICAL_EEL" => 0,
    "CQP_STORAGE_AGG" => 0,
    "CQP_HISTORICAL_AGG" => 0,
    "CQP_TOTAL_FORTE_USD_EEL" => 0,
    "CQP_VARIATION_EEL" => 0,
    "CQP_TOTAL_FORTE_USD_AGG" => 0,
    "CQP_VARIATION_AGG" => 0
]],

"CQP_TRANSIT" => [[
    "form_html_viewer" => null,

    "CQP_TOTAL_TTP_GROSS_RATE" => null,
    "CQP_TOTAL_TTP_ANNUAL_USD" => 0,
    "CQP_TOTAL_TTP_GWD" => 0,
    "CQP_TOTAL_TTP_HISTORICAL" => 0,
    "CQP_TOTAL_TTP_VARIATION" => 0,

    "CQP_TOTAL_EXPORTS_ANNUAL_USD" => 0,
    "CQP_TOTAL_EXPORTS_HISTORICAL" => 0,
    "CQP_TOTAL_EXPORTS_CIF_PERCENTAGE" => 0,
    "CQP_EXPORTS_FORTE_NET_RATE" => null,
    "CQP_TOTAL_EXPORTS_CIF_GROSS_RATE" => null,
    "CQP_TOTAL_EXPORTS_FOB_PERCENTAGE" => 0,
    "CQP_TOTAL_EXPORTS_FOB_GROSS_RATE" => null,
    "CQP_TOTAL_EXPORTS_OI_PERCENTAGE" => 0,
    "CQP_TOTAL_EXPORTS_OI_GROSS_RATE" => null,
    "CQP_TOTAL_EXPORTS_PERCENTAGE" => 0,
    "CQP_TOTAL_EXPORTS_GROSS_RATE" => 0,
    "CQP_TOTAL_EXPORTS_GWD" => 0,
    "CQP_TOTAL_EXPORTS_VARIATION" => 0,
    "CQP_TOTAL_EXPORTS_CIF_ANNUAL_USD" => 0,
    "CQP_TOTAL_EXPORTS_CIF_GWD" => 0,
    "CQP_TOTAL_EXPORTS_FOB_ANNUAL_USD" => 0,
    "CQP_TOTAL_EXPORTS_FOB_GWD" => 0,
    "CQP_TOTAL_EXPORTS_OI_ANNUAL_USD" => 0,
    "CQP_TOTAL_EXPORTS_OI_GWD" => 0,

    "CQP_EXPORTS_VALIDATION" => [
        "isValid" => true,
        "message" => ""
    ],

    "CQP_TOTAL_DOMESTIC_ANNUAL_USD" => 0,
    "CQP_TOTAL_DOMESTIC_HISTORICAL" => 0,
    "CQP_TOTAL_DOMESTIC_RDA_PERCENTAGE" => 0,
    "CQP_DOMESTIC_FORTE_NET_RATE" => null,
    "CQP_TOTAL_DOMESTIC_RDA_GROSS_RATE" => 0,
    "CQP_TOTAL_DOMESTIC_RDT_PERCENTAGE" => 0,
    "CQP_TOTAL_DOMESTIC_RDT_GROSS_RATE" => 0,
    "CQP_TOTAL_DOMESTIC_PERCENTAGE" => 0,
    "CQP_TOTAL_DOMESTIC_GROSS_RATE" => null,
    "CQP_TOTAL_DOMESTIC_GWD" => 0,
    "CQP_TOTAL_DOMESTIC_VARIATION" => 0,
    "CQP_TOTAL_DOMESTIC_RDA_ANNUAL_USD" => 0,
    "CQP_TOTAL_DOMESTIC_RDA_GWD" => 0,
    "CQP_TOTAL_DOMESTIC_RDT_ANNUAL_USD" => 0,
    "CQP_TOTAL_DOMESTIC_RDT_GWD" => 0,

    "CQP_DOMESTIC_VALIDATION" => [
        "isValid" => true,
        "message" => ""
    ],

    "CQP_TOTAL_IMPORTS_ANNUAL_USD" => 0,
    "CQP_TOTAL_IMPORTS_HISTORICAL" => 0,
    "CQP_TOTAL_IMPORTS_CIF_PERCENTAGE" => null,
    "CQP_FORTE_NET_RATE_IMPORTS" => null,
    "CQP_TOTAL_IMPORTS_CIF_GROSS_RATE" => null,
    "CQP_TOTAL_IMPORTS_FOB_PERCENTAGE" => 0,
    "CQP_TOTAL_IMPORTS_FOB_GROSS_RATE" => null,
    "CQP_TOTAL_IMPORTS_OI_PERCENTAGE" => 0,
    "CQP_TOTAL_IMPORTS_OI_GROSS_RATE" => 0,
    "CQP_TOTAL_IMPORTS_PERCENTAGE" => 0,
    "CQP_TOTAL_IMPORTS_GROSS_RATE" => null,
    "CQP_TOTAL_IMPORTS_GWD" => 0,
    "CQP_TOTAL_IMPORTS_VARIATION" => 0,
    "CQP_TOTAL_IMPORTS_CIF_ANNUAL_USD" => 0,
    "CQP_TOTAL_IMPORTS_CIF_GWD" => 0,
    "CQP_TOTAL_IMPORTS_FOB_ANNUAL_USD" => 0,
    "CQP_TOTAL_IMPORTS_FOB_GWD" => 0,
    "CQP_TOTAL_IMPORTS_OI_ANNUAL_USD" => 0,
    "CQP_TOTAL_IMPORTS_OI_GWD" => 0,

    "CQP_IMPORTS_VALIDATION" => [
        "isValid" => true,
        "message" => ""
    ]
]],

"CQP_TURNOVER_DATES" => null,

"CQP_STORAGE" => [[
    "form_html_viewer" => null,
    "CQP_MAXIMUM_INVENTORIES_USD" => 0,
    "CQP_INVENTORIES_FORTE_NET_RATE" => null,
    "CQP_MAXIMUM_INVENTORIES_GROSS_RATE" => 0,
    "CQP_SELECTED_STORAGE_MAX" => false,
    "CQP_MAXIMUM_INVENTORIES_HISTORICAL_TERMS" => 0,
    "CQP_AVERAGE_INVENTORIES_USD" => 0,
    "CQP_AVERAGE_INVENTORIES_GROSS_RATE" => null,
    "CQP_SELECTED_STORAGE_AVE" => false,
    "CQP_AVERAGE_INVENTORIES_HISTORICAL_TERMS" => 0,
    "CQP_MAXIMUM_INVENTORIES_GWP" => 0,
    "CQP_MAXIMUM_INVENTORIES_VARIATION" => 0,
    "CQP_AVERAGE_INVENTORIES_GWP" => 0,
    "CQP_AVERAGE_INVENTORIES_VARIATION" => 0,
    "CQP_TOTAL_STORAGE_PREMIUM_USD" => 0,
    "CQP_TOTAL_STORAGE_PREMIUM_VARIATION" => 0,
    "CQP_STORAGE_VALIDATION" => [
        "valid" => true,
        "message" => ""
    ],
    "CQP_STORAGE_CHECKBOXES_VALIDATION" => [
        "valid" => false,
        "message" => "You must select only one option: either Maximum Storage or Average Storage, not both or none."
    ]
]],

"CQP_NUMBER_OF_PERIODS" => null,

"CQP_SUMMARY_DETAILS" => [[
    "form_html_viewer" => null,
    "CQP_TOTAL_ESTIMATED_HISTORICAL" => null,
    "CQP_ESTIMATED_USD" => null,
    "CQP_ESTIMATED_HISTORICAL" => null,
    "CQP_MINDEP_DROPDOWN" => null,
    "CQP_FORTE_LINE_SUPPORT_HISTORICAL" => null,
    "CQP_BROKER_DEDUCTIONS_USD" => null,
    "CQP_BROKER_DEDUCTIONS_HISTORICAL" => null,
    "CQP_UNDERWRITING_EXPENSES_USD" => "15",
    "CQP_UNDERWRITING_EXPENSES_HISTORICAL" => null,
    "CQP_TAX_USD" => null,
    "CQP_TAX_HISTORICAL" => null,
    "CQP_RISK_MANAGEMENT_FEES_USD" => null,
    "CQP_PROFIT_USD" => null,
    "CQP_TOTAL_ESTIMATED_USD" => 0,
    "CQP_TOTAL_ESTIMATED_VARIATION" => 0,
    "CQP_ESTIMATED_VARIATION" => 0,
    "CQP_COMBINED_RATE_USD" => null,
    "CQP_COMBINED_RATE_VARIATION" => 0,
    "CQP_MINDEP_USD" => 0,
    "CQP_MINDEP_VARIATION" => 0,
    "CQP_FORTE_LINE_SUPPORT_USD" => 0,
    "CQP_FORTE_LINE_SUPPORT_VARIATION" => 0,
    "CQP_FORTE_GWP_USD" => 0,
    "CQP_FORTE_GWP_VARIATION" => 0,
    "CQP_BROKER_DEDUCTIONS_VARIATION" => 0,
    "CQP_FORTE_GWP_USD_BROKER" => 0,
    "CQP_FORTE_GWP_VARIATION_BROKER" => null,
    "CQP_UNDERWRITING_EXPENSES_VARIATION" => null,
    "CQP_NET_PREMIUM_USD" => 0,
    "CQP_NET_PREMIUM_VARIATION" => 0,
    "CQP_TAX_VARIATION" => 0,
    "CQP_LOSS_RATIO_USD" => null,
    "CQP_TOTAL_COMBINED_RATIO_USD" => 15,
    "CQP_TOTAL_COMBINED_RATIO_USD_VALIDATION" => [
        "isValid" => true,
        "message" => ""
    ],
    "CQP_COMBINED_RATE_HISTORICAL" => null,
    "CQP_MINDEP_HISTORICAL" => 0,
    "CQP_FORTE_GWP_HISTORICAL" => 0,
    "CQP_FORTE_GWP_HISTORICAL_BROKER" => 0,
    "CQP_NET_PREMIUM_HISTORICAL" => 0
]],

"CQP_UNDERWRITING_ARGUMENTS" => [[ "CQP_ARGUMENT" => "" ]],

"CQP_SUBMIT" => null,
"IN_SUBMIT" => null,

"CQP_SUMMARY_CLAIMS" => [],
"ROWS" => [],

"CQP_TRANSIT_EXPOSURE_VALIDATION" => [
    "isValid" => false,
    "message" => "At least one exposure value must be completed."
],

"CQP_TRANSIT_VALIDATION" => [
    "isValid" => false,
    "message" => "At least one of the following fields must have a value different from 0 or null: Imports, Exports, or Domestic Annual USD."
],

"CQP_STORAGE_FILLED" => false,
"CQP_SUMMARY_CLAIMS_ENCODED" => "W10=",
"CQP_PERIOD_ROWS_ENCODED" => "W10=",
"CQP_PERIOD_DROPDOWN_DISABLED" => null,

"GENERAL_VISIBILITY_RULE" => false,

"CQP_TYPE" => null,
"CQP_COUNTRY" => null,
"CQP_CURRENCY" => "USD",
"CQP_CEDANT" => null,
"CQP_REINSURANCE_BROKER" => null,
"CQP_INCEPTION_DATE" => null,
"CQP_ADD_INCEPTION_DATE_NORMAL" => 12,
"CQP_ADD_INCEPTION_DATE_SPECIAL" => null,
"CQP_SPECIAL_CASE" => false,
"CQP_SPECIAL_CASE_REASON" => null,
"CQP_INTEREST" => null,
"CQP_COMMODITIES_PROFILE" => null,
"CQP_DAYS" => null,
"CQP_MONTHS" => null,
"CQP_ADD_INCEPTION_DATE" => 12,
"CQP_UNDERWRITING_YEAR" => "",

"CQP_COMMODITIES_FILTERED" => [],

"AS_IF" => [[
    "form_html_viewer" => null,
    "CQP_BROKERAGE_COMISSION" => "27.5",
    "CQP_UNDERWRITING_EXPENSES" => "15",
    "CQP_CLAIM_USD" => 0,
    "CQP_FROM" => "",
    "CQP_TO" => "",
    "CQP_GWP_USD" => 0,
    "CQP_TAX" => 0,
    "CQP_FORTE_SHARE" => 0,
    "CQP_NWP" => 0,
    "CQP_LOSS_RADIO" => null,
    "CQP_COMBINED_RADIO" => 42.5
]],

"TOTALS" => [[
    "form_html_viewer" => null,
    "CQP_CLAIM_USD" => 0,
    "CQP_GWP_USD" => 0,
    "CQP_FORTE_SHARE" => 0,
    "CQP_NWP" => 0,
    "CQP_LOSS_RADIO" => 0,
    "CQP_BROKERAGE_COMISSION" => 27.5,
    "CQP_TAX" => 0,
    "CQP_UNDERWRITING_EXPENSES" => 15,
    "CQP_COMBINED_RADIO" => 42.5
]],

"CQP_SUMMARY_CLAIMS_LAST" => [[
    "CQP_FREQUENCY_STORAGE_TOTAL" => 0,
    "CQP_TOTAL_CLAIMS_STORAGE_TOTAL" => 0,
    "CQP_FREQUENCY_TRANSIT_TOTAL" => 0,
    "CQP_TOTAL_CLAIMS_TRANSIT_TOTAL" => 0,
    "CQP_TOTAL_CLAIMS_COMBINED_TOTAL" => 0,
    "CQP_FREQUENCY_STORAGE_AVERAGE" => 0,
    "CQP_TOTAL_CLAIMS_STORAGE_AVERAGE" => 0,
    "CQP_FREQUENCY_TRANSIT_AVERAGE" => 0,
    "CQP_TOTAL_CLAIMS_TRANSIT_AVERAGE" => 0,
    "CQP_TOTAL_CLAIMS_COMBINED_AVERAGE" => 0
]],

"CQP_FREQUENCY_STORAGE_VALIDATION" => [
    "isValid" => true,
    "message" => ""
],

"CQP_STORAGE_JUMP" => true,
"CQP_STORAGE_VALIDATION" => true,
"CQP_STORAGE_CHECKBOXES_VALIDATION" => false,
"CQP_SUMMARY_DETAILS_VALID" => true,

"CQP_MARKETS" => [],

"CQP_MARKETS_TOTALS" => [[
    "CQP_MAXIMUN_CAP_TOTAL" => 0,
    "CQP_REINSURANCE_DISTRIBUTION_TOTAL" => 0,
    "CQP_USD_TOTAL" => 0,
    "CQP_FORTE_SHARE_TOTAL" => 0
]],

"CQP_REINSURANCE_DISTRIBUTION_VALIDATION" => [
    "details" => [],
    "expectedList" => [],
    "isValid" => true,
    "message" => "",
    "takenCount" => 0
],

"CQP_MARKETS_TAKEN_VALIDATION" => [
    "details" => [
        "You must select at least one reinsurer as taken.<br>"
    ],
    "isValid" => false,
    "message" => "There are validation errors for taken and/or required reinsurers.<br>",
    "takenCount" => 0,
    "requiredCount" => 0
],

"CQP_EXPORTS_VALIDATION" => true,
"CQP_DOMESTIC_VALIDATION" => true,
"CQP_IMPORTS_VALIDATION" => true

];*/