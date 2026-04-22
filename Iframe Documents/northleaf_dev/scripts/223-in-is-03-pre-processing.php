<?php 
$time_start = microtime(true); // //////////////////time execution
/***********************************************
 *  IS.03 Pre processing
 *  
 *  By Adriana Centellas
 *
 * Modified by Favio Mollinedo
 * Modified by Ana Castillo
 **********************************************/

require_once("/Northleaf_PHP_Library.php");

//Set Totals needed for IS.03 - Ana Castillo
//Convert values to number
$invoicePreTaxFormat = $data["IN_INVOICE_PRE_TAX"];
$invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
$invoiceTotalFormat = $data["IN_INVOICE_TOTAL"];
$invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
$invoiceTaxTotalFormat = $data["IN_INVOICE_TAX_TOTAL"];
$invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$baseURL = getenv('ENVIRONMENT_BASE_URL');
$requestId = $data["_request"]["id"];
$fileId = $data["IN_UPLOAD_PDF"];



$time_start1 = microtime(true); //////////////////time execution
$totalRecords = checkRecordsByRequestId($apiUrl, $requestId);
$time_end1 = microtime(true); //////////////////time execution
$execution_time = ($time_end1 - $time_start1); //////////////////time execution
$dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];//////////////////time execution
$dataTimeExec['IS.03 Pre processing'][] = 'query : ' . $execution_time;//////////////////time execution
$dataReturn['dataTimeExec'] = $dataTimeExec; //////////////////time execution

$invoiceExcelArray = !empty($data["IN_INVOICE_ARRAY"]) ? $data["IN_INVOICE_ARRAY"] : [];
if ($totalRecords[0]['TOTAL_ROWS'] <= 0 AND empty($data["IN_INVOICE_ARRAY"]) AND $data['IN_SAVE_SUBMIT'] != "SAVE" AND $data['IN_SAVE_SUBMIT'] != "SAVE_AND_CLOSE") {
    $vendorID = isset($data['vendorInformation'][0]) ? $data['vendorInformation'][0]['VENDOR_SYSTEM_ID_ACTG'] : '';
    if(!empty($vendorID)){
        $sqlVendorData = "SELECT 
                            VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.LABEL' AS EXPENSE_VENDOR_DEFAULT_ACCOUNT, 
                            VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.ID' AS EXPENSE_VENDOR_DEFAULT_ACCOUNT_ID,
                            VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_PROJECT.LABEL' AS EXPENSE_VENDOR_DEFAULT_PROJECT,
                            VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_PROJECT.ID' AS EXPENSE_VENDOR_DEFAULT_PROJECT_ID 
                            FROM collection_" . getCollectionId('IN_VENDORS', $apiUrl) . " AS VENDOR
                            WHERE VENDOR.data->>'$.VENDOR_SYSTEM_ID_ACTG' = '".$vendorID."'";
        $vendorData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendorData));
        if(count($vendorData) > 0 ){
            $defAccount = [
                "ID" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT_ID'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT_ID'] : '',
                "LABEL" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT'] : ''
            ];
            $defProject = [
                "ID" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT_ID'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT_ID'] : '',
                "LABEL" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT'] : ''
            ];
            $validAccount = (!empty($defAccount['ID'])) ? true : $validAccount;
        }
    }
    
    $validation = [
				"IN_EXPENSE_DESCRIPTION" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => true,
					"messages" => (object)["required" => 'This Field is required.']
				],
				"IN_EXPENSE_ACCOUNT" => [
					"isValid" => $validAccount,
					"isDisabled" => false,
					"isRequired" => true,
					"messages" => (object)["required" => 'This Field is required.']
				],
				"IN_EXPENSE_CORP_PROJ" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_PRETAX_AMOUNT" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_HST" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_TOTAL" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_PERCENTAGE" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_PERCENTAGE_TOTAL" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_NR" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => true,
					"messages" => (object)[]
				],
				"IN_EXPENSE_TEAM_ROUTING" => [
					"isValid" => true,
					"isDisabled" => true,
					"isRequired" => true,
					"messages" => (object)["required" => 'This Field is required.']
				],
				"IN_EXPENSE_PROJECT_DEAL" => [
					"isValid" => true,
					"isDisabled" => true,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_FUND_MANAGER" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_MANDATE" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => true,
					"messages" => (object)["required" => 'This Field is required.']
				],
				"IN_EXPENSE_ACTIVITY" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => true,
					"messages" => (object)["required" => 'This Field is required.']
				],
				"IN_EXPENSE_CORP_ENTITY" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				]
    ];
    $expenseData = [
        "IN_EXPENSE_CASE_ID" => $data["_request"]["id"],
        "IN_EXPENSE_ROW_ID" => generateUuidV4(),
        "IN_EXPENSE_DESCRIPTION" => empty($data['IN_INVOICE_TRANS_COMMENTS']) ? '' : $data['IN_INVOICE_TRANS_COMMENTS'],
        "IN_EXPENSE_ROW_NUMBER" => 1,
        "IN_EXPENSE_NR" => "Non-Recoverable",
        "IN_EXPENSE_TEAM_ROUTING" => ["ID" => "CORP", "LABEL" => "Corporate"],
        "IN_EXPENSE_MANDATE" => ["ID" => "0000", "LABEL" => "Non Recoverable"],
        "IN_EXPENSE_ACTIVITY" => ["ID" => "000", "LABEL" => "Unallocated"],
        "IN_EXPENSE_TEAM_ROW_INDEX" => 1,
        "IN_EXPENSE_PRETAX_AMOUNT" => $invoicePreTaxFormat,
        "IN_EXPENSE_HST" => $invoiceTaxTotalFormat,
        "IN_EXPENSE_TOTAL" => $invoiceTotalFormat,
        "IN_EXPENSE_PERCENTAGE" => 100,
        "IN_EXPENSE_PERCENTAGE_TOTAL" => "100",
        "IN_EXPENSE_VALIDATION" => base64_encode(json_encode($validation)),

    ];
    if(isset($defAccount)){
        $expenseData['IN_EXPENSE_ACCOUNT'] = $defAccount;
    }

    if(isset($defProject)){
        $expenseData['IN_EXPENSE_CORP_PROJ'] = $defProject;
    }
    $createExpences = createExpense($apiUrl, $expenseData, $requestId);
    //$allDatagrid[] = $expenseData;
}
else{
    //Get options for dropdowns

    $collectionName = "IN_EXPENSE_ACTIVITY";
    $collectionID = getCollectionId($collectionName, $apiUrl);

    //Get the manager selected by submiter and department
    $collectionName = "IN_APPROVER_DEPARTMENT";
    $collectionIdDepartment = getCollectionId($collectionName, $apiUrl);
    $collectionName = "IN_SUBMITTER_DEPARTMENT";
    $collectionIdSubmitter = getCollectionId($collectionName, $apiUrl);
    $idInitialUSer = $data['IN_DATA_INITIAL_USER']['ID'];
    if (!isset($data["IN_SUBMITTER_MANAGER"])) {
        $dataReturn["IN_SUBMITTER_MANAGER"] = getManagerAssigned($collectionIdSubmitter, $collectionIdDepartment, $apiUrl, $idInitialUSer);
    }

    //Get the comment logs by request
    $collectionNameLog = "IN_COMMENTS_LOG";
    $collectionIdLog = getCollectionId($collectionNameLog, $apiUrl);
    $commentLogData = getCommentsLog($collectionIdLog, $apiUrl, $requestId);
    $dataReturn["IN_COMMENT_LOG"] = $commentLogData;

    //$officeLocationOptions = getExpenseActivityOptions($collectionID, $apiUrl);
    $officeLocationNROptions = getNonRecoverableExpenseActivityOptions($collectionID, $apiUrl);
    $officeLocationROptions = getRecoverableExpenseActivityOptions($collectionID, $apiUrl);

    // Expenses and asset classes data collection - by Favio Mollinedo
    $collectionIEMName = "IN_EXPENSE_MANDATES";
    $collectionIACName = "IN_ASSET_CLASS";
    $collectionIEM = getCollectionId($collectionIEMName, $apiUrl);
    $collectionIAC = getCollectionId($collectionIACName, $apiUrl);
    $expenseMandateAssetOptions = getInExpenseMandateOption($collectionIEM, $apiUrl, $collectionIAC);
    //return getInExpenseMandateOption($collectionIEM, $apiUrl, $collectionIAC);

    //$dataReturn["IN_EXPENSE_ACTIVITY_VALUES"] = $officeLocationOptions;
    
    //comment by Daniel
    //$dataReturn["IN_EXPENSE_ACTIVITY_NON_RECOVERABLE"] = $officeLocationNROptions;
    //$dataReturn["IN_EXPENSE_ACTIVITY_RECOVERABLE"] = $officeLocationROptions;
    $dataReturn["IN_EXPENSE_ACTIVITY_NON_RECOVERABLE"] = null;
    $dataReturn["IN_EXPENSE_ACTIVITY_RECOVERABLE"] = null;
    
    
    // Get Validation rules
    $collectionIdEDV = getCollectionId('IN_EXPENSE_DEFAULTS', $apiUrl);
    $validationRules = getValidationRules($apiUrl, $collectionIdEDV);

    /*//Set Totals needed for IS.03 - Ana Castillo
    //Convert values to number
    $invoicePreTaxFormat = $data["IN_INVOICE_PRE_TAX"];
    $invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
    $invoiceTotalFormat = $data["IN_INVOICE_TOTAL"];
    $invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
    $invoiceTaxTotalFormat = $data["IN_INVOICE_TAX_TOTAL"];
    $invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);
    */
    
    // Excel Data Records - by Favio Mollinedo
    $excelData = [];
    $invoiceExcelArray = !empty($data["IN_INVOICE_ARRAY"]) ? $data["IN_INVOICE_ARRAY"] : [];
    //return $invoiceExcelArray;
    $invoiceExcelArrayExtended = determineAssetType($invoiceExcelArray, $expenseMandateAssetOptions);
    //return $invoiceExcelArrayExtended;
    //return $expenseDataArray;
    // Add a new record is expence table is empty costume table - Jhon Chacolla
//    $totalRecords = checkRecordsByRequestId($apiUrl, $requestId);
    //Corp Entity - by Favio Molliendo
    $collectionIECEName = "IN_EXPENSE_CORP_ENTITY";
    $collectionIECE = getCollectionId($collectionIECEName, $apiUrl);
    $expenseCorpEntityOptions = getInExpenseCorpEntityOptions($collectionIECE, $apiUrl);
    $invoiceExcelArrayExtended = checkCorporateEntity($invoiceExcelArrayExtended, $expenseCorpEntityOptions);
    //Activity - by Favio Molliendo
    $collectionIEAName = "IN_EXPENSE_ACTIVITY";
    $collectionIECA = getCollectionId($collectionIEAName, $apiUrl);
    $expenseActivityOptions = getExpenseActivityOptions($collectionIECA, $apiUrl);
    $invoiceExcelArrayExtended = checkActivity($invoiceExcelArrayExtended, $expenseActivityOptions);
    //Account - by Favio Molliendo
    $collectionIEACName = "IN_EXPENSE_ACCOUNT";
    $collectionIECAC = getCollectionId($collectionIEACName, $apiUrl);
    $expenseAccountOptions = getInExpenseAccountOptions($collectionIECAC, $apiUrl);
    $invoiceExcelArrayExtended = checkInAccount($invoiceExcelArrayExtended, $expenseAccountOptions);
    //Deal - by Favio Molliendo
    $collectionIEDName = "IN_DEAL";
    $collectionIED = getCollectionId($collectionIEDName, $apiUrl);
    $expenseDealOptions = getInExpenseDealOptions($collectionIED, $apiUrl);
    $invoiceExcelArrayExtended = checkInDeal($invoiceExcelArrayExtended, $expenseDealOptions);
    //Corp Project - by Favio Molliendo
    $collectionIECPName = "IN_EXPENSE_CORP_PROJ";
    $collectionIECP = getCollectionId($collectionIECPName, $apiUrl);
    $expenseCorpProjOptions = getInCorpProjectOptions($collectionIECP, $apiUrl);
    $invoiceExcelArrayExtended = checkInCorpProject($invoiceExcelArrayExtended, $expenseCorpProjOptions);
    //Corp Project - by Favio Molliendo
    $collectionIEFMName = "IN_EXPENSE_FUND_MANAGER";
    $collectionIEFM = getCollectionId($collectionIEFMName, $apiUrl);
    $expenseFundManagerOptions = getInFundManagerOptions($collectionIEFM, $apiUrl);
    $invoiceExcelArrayExtended = checkInFundManager($invoiceExcelArrayExtended, $expenseFundManagerOptions);
    //Department
    $queryDept = "SELECT data->>'$.NL_DEPARTMENT_SYSTEM_ID_DB' AS ID, data->>'$.DEPARTMENT_LABEL' AS LABEL
        FROM collection_" . getCollectionId('IN_EXPENSE_DEPARTMENT', $apiUrl) . " AS DEP 
        WHERE DEP.data->>'$.DEPARTMENT_STATUS' = 'Active' 
        ORDER BY LABEL ASC";
    $departmentList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryDept));
    $invoiceExcelArrayExtended = checkInDepartment($invoiceExcelArrayExtended, $departmentList);

    $queryOffice = "SELECT data->>'$.NL_OFFICE_SYSTEM_ID_DB' AS ID, data->>'$.OFFICE_LABEL' AS LABEL
                    FROM collection_" . getCollectionId('IN_EXPENSE_OFFICE', $apiUrl) . " AS OFFICE 
                    WHERE OFFICE.data->>'$.OFFICE_STATUS' = 'Active' 
                    ORDER BY LABEL ASC";
    $officeList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryOffice));
    $invoiceExcelArrayExtended = checkInOffice($invoiceExcelArrayExtended, $officeList);

    $queryDeal = "SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(DEAL.data, '$.DEAL_ASSETCLASS.ASSET_ID')) AS ASSET_ID,
                    JSON_UNQUOTE(JSON_EXTRACT(DEAL.data, '$.DEAL_ASSETCLASS.ASSET_LABEL')) AS ASSET_LABEL,  
                    data->>'$.DEAL_LABEL' AS DEAL_LABEL,
                    data->>'$.DEAL_SYSTEM_ID_DB' AS DEAL_ID
                    FROM collection_" . getCollectionId('IN_DEAL', $apiUrl) . " AS DEAL 
                    ORDER BY DEAL_LABEL ASC";
    $dealList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryDeal));
    //$allDatagrid = [];
    if ($totalRecords[0]['TOTAL_ROWS'] <= 0) {
        foreach($invoiceExcelArrayExtended as $index => $excelData){
            //Convert values to number
            //$invoicePreTaxFormat = $excelData["IN_EXPENSE_PRETAX_AMOUNT"];
            $invoicePreTaxFormatExcel = $excelData["IN_EXPENSE_TOTAL"] - $excelData["IN_EXPENSE_HST"];
            $invoicePreTaxExcel = (float) str_replace(",", "", $invoicePreTaxFormatExcel);
            $invoiceTotalFormatExcel = $excelData["IN_EXPENSE_TOTAL"];
            $invoiceTotalExcel = (float) str_replace(",", "", $invoiceTotalFormatExcel);
            $invoiceTaxTotalFormatExcel = $excelData["IN_EXPENSE_HST"];
            $invoiceTaxTotalExcel = (float) str_replace(",", "", $invoiceTaxTotalFormatExcel);
            // Round amounts for IS.03 - Jhon Chacolla
            $inInvoicePreTaxPercentage = (empty($invoicePreTaxExcel) || empty($invoiceTotal)) ? 0 : (($invoicePreTaxExcel * 100) / $invoiceTotal);
            $inInvoiceTaxTotalPercentage = (empty($invoiceTaxTotalExcel) || empty($invoiceTotal)) ? 0 :  (($invoiceTaxTotalExcel * 100) / $invoiceTotal);
            $inInvoiceTotalPercentage = ($inInvoicePreTaxPercentage + $inInvoiceTaxTotalPercentage);
            $excelData["IN_INVOICE_PRE_TAX_PERCENTAGE"] = $inInvoicePreTaxPercentage;
            $excelData["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = $inInvoiceTaxTotalPercentage;
            $excelData["IN_INVOICE_TOTAL_PERCENTAGE"] = $inInvoiceTotalPercentage;
            //return [$inInvoicePreTaxPercentage, $inInvoiceTaxTotalPercentage, $inInvoiceTotalPercentage];
            $assetType = '';
            if(!empty($excelData["ASSET_TYPE"])) {
                $assetType = strpos(strtoupper($excelData["ASSET_TYPE"]), 'NO') !== false ? 'Non-Recoverable' : 'Recoverable';
            }else{
                if(!empty($excelData["EXPENSE_FM_ASSET_TYPE"])){
                    $assetType = strpos(strtoupper($excelData["ASSET_TYPE"]), 'NO') !== false ? 'Non-Recoverable' : 'Recoverable';
                }
            }
            $expenseDataArray = [
                "IN_EXPENSE_CASE_ID" => $data["_request"]["id"],
                "IN_EXPENSE_ROW_ID" => generateUuidV4(),
                "IN_EXPENSE_DESCRIPTION" => empty($excelData["IN_EXPENSE_DESCRIPTION"]) ? '' : addslashes($excelData["IN_EXPENSE_DESCRIPTION"]),
                "IN_EXPENSE_NR" => $assetType,
                //"IN_EXPENSE_NR_LABEL" => $excelData["ASSET_TYPE"],
                "IN_EXPENSE_MANDATE" => [
                                        "ID" => empty($excelData["MANDATE_ID"]) ? '' : $excelData["MANDATE_ID"],
                                        "LABEL" => empty($excelData["MANDATE_LABEL"]) ? '' : $excelData["MANDATE_LABEL"]
                                        ],
                "IN_EXPENSE_TEAM_ROUTING" => [
                                        "ID" => empty($excelData["ASSET_ID"]) ? empty($excelData["EXPENSE_FM_ASSET_ID"]) ? '' : $excelData["EXPENSE_FM_ASSET_ID"] : $excelData["ASSET_ID"],
                                        "LABEL" => empty($excelData["ASSET_LABEL"]) ? empty($excelData["EXPENSE_FM_ASSET_LABEL"]) ? '' : $excelData["EXPENSE_FM_ASSET_LABEL"] : $excelData["ASSET_LABEL"]
                                        ],
                "IN_EXPENSE_ACTIVITY" => [
                                        "ID" => empty($excelData["ACTIVITY_ID"]) ? '' : $excelData["ACTIVITY_ID"],
                                        "LABEL" => empty($excelData["EXPENSE_ACTIVITY_LABEL"]) ? '' : $excelData["EXPENSE_ACTIVITY_LABEL"]
                                        ],
                "IN_EXPENSE_CORP_ENTITY" => [
                                        "ID" => empty($excelData["CORP_SYSTEM_ID_ACTG"]) ? '' : $excelData["CORP_SYSTEM_ID_ACTG"],
                                        "LABEL" => empty($excelData["EXPENSE_CORPORATE_LABEL"]) ? '' : $excelData["EXPENSE_CORPORATE_LABEL"]
                                        ],
                "IN_EXPENSE_ACCOUNT" => [
                                        "ID" => empty($excelData["ACCOUNT_ID"]) ? '' : $excelData["ACCOUNT_ID"],
                                        "LABEL" => empty($excelData["EXPENSE_ACCOUNT_LABEL"]) ? '' : $excelData["EXPENSE_ACCOUNT_LABEL"]
                                        ],
                "IN_EXPENSE_PROJECT_DEAL" => [
                                        "ID" => empty($excelData["DEAL_ID"]) ? '' : $excelData["DEAL_ID"],
                                        "LABEL" => empty($excelData["EXPENSE_DEAL_LABEL"]) ? '' : $excelData["EXPENSE_DEAL_LABEL"]
                                        ],
                "IN_EXPENSE_CORP_PROJ" => [
                                        "ID" => empty($excelData["CORP_PROJ_ID"]) ? '' : $excelData["CORP_PROJ_ID"],
                                        "LABEL" => empty($excelData["EXPENSE_CORP_PROJ_LABEL"]) ? '' : $excelData["EXPENSE_CORP_PROJ_LABEL"]
                                        ],
                "IN_EXPENSE_FUND_MANAGER" => [
                                        "ID" => empty($excelData["FUND_MANAGER_ID"]) ? '' : $excelData["FUND_MANAGER_ID"],
                                        "LABEL" => empty($excelData["EXPENSE_FUND_MANAGER_LABEL"]) ? '' : $excelData["EXPENSE_FUND_MANAGER_LABEL"]
                                        ],
                "IN_EXPENSE_DEPARTMENT" => [
                                        "ID" => empty($excelData["IN_EXPENSE_DEPARTMENT_ID"]) ? '' : $excelData["IN_EXPENSE_DEPARTMENT_ID"],
                                        "LABEL" => empty($excelData["IN_EXPENSE_DEPARTMENT_LABEL"]) ? '' : $excelData["IN_EXPENSE_DEPARTMENT_LABEL"]
                                        ],
                "IN_EXPENSE_OFFICE" => [
                                        "ID" => empty($excelData["IN_EXPENSE_OFFICE_ID"]) ? '' : $excelData["IN_EXPENSE_OFFICE_ID"],
                                        "LABEL" => empty($excelData["IN_EXPENSE_OFFICE_LABEL"]) ? '' : $excelData["IN_EXPENSE_OFFICE_LABEL"]
                                        ],
                "IN_EXPENSE_ROW_NUMBER" => $index+1,
                "IN_EXPENSE_TEAM_ROW_INDEX" => $index+1,
                "IN_EXPENSE_PRETAX_AMOUNT" => number_format(($invoiceTotalExcel - $invoiceTaxTotalExcel), 2, '.', ''),
                "IN_EXPENSE_HST" => number_format($invoiceTaxTotalFormatExcel, 2, '.', ''),
                "IN_EXPENSE_TOTAL" => number_format($invoiceTotalFormatExcel, 2, '.', ''),
                "IN_EXPENSE_PERCENTAGE" => 100,
                "IN_EXPENSE_PERCENTAGE_TOTAL" => number_format($inInvoiceTotalPercentage, 2, '.', ''),
            ];
            if($expenseDataArray['IN_EXPENSE_MANDATE']['ID'] == '' AND $expenseDataArray['IN_EXPENSE_FUND_MANAGER']['ID'] == '' AND $expenseDataArray['IN_EXPENSE_CORP_PROJ']['ID'] == ''){
                if(!empty($expenseDataArray['IN_EXPENSE_PROJECT_DEAL']['ID'])){
                    $key = array_search($expenseDataArray['IN_EXPENSE_PROJECT_DEAL']['LABEL'], array_column($dealList, 'DEAL_LABEL'));
                    if($key){
                        $expenseDataArray['IN_EXPENSE_TEAM_ROUTING']['ID']    = $dealList[$key]['ASSET_ID'];
                        $expenseDataArray['IN_EXPENSE_TEAM_ROUTING']['LABEL'] = $dealList[$key]['ASSET_LABEL'];                    
                    }

                }
            }
            //$allDatagrid[] = $expenseDataArray;
            $newRow = setValidation($validationRules, $expenseDataArray);
            $createExpences = createExpense($apiUrl, $newRow, $requestId);
        }
    }

    // Add a new record is expence table is empty costume table - Jhon Chacolla
    $totalRecords = checkRecordsByRequestId($apiUrl, $requestId);
    $validAccount = false;
    /*if ($totalRecords[0]['TOTAL_ROWS'] <= 0) {
        $vendorID = isset($data['vendorInformation'][0]) ? $data['vendorInformation'][0]['VENDOR_SYSTEM_ID_ACTG'] : '';
        if(!empty($vendorID)){
            $sqlVendorData = "SELECT 
                                VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.LABEL' AS EXPENSE_VENDOR_DEFAULT_ACCOUNT, 
                                VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_ACCOUNT.ID' AS EXPENSE_VENDOR_DEFAULT_ACCOUNT_ID,
                                VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_PROJECT.LABEL' AS EXPENSE_VENDOR_DEFAULT_PROJECT,
                                VENDOR.data->>'$.EXPENSE_VENDOR_DEFAULT_PROJECT.ID' AS EXPENSE_VENDOR_DEFAULT_PROJECT_ID 
                                FROM collection_" . getCollectionId('IN_VENDORS', $apiUrl) . " AS VENDOR
                                WHERE VENDOR.data->>'$.VENDOR_SYSTEM_ID_ACTG' = '".$vendorID."'";
            $vendorData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sqlVendorData));
            if(count($vendorData) > 0 ){
                $defAccount = [
                    "ID" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT_ID'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT_ID'] : '',
                    "LABEL" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_ACCOUNT'] : ''
                ];
                $defProject = [
                    "ID" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT_ID'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT_ID'] : '',
                    "LABEL" => (isset($vendorData[0]) AND isset($vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT'])) ? $vendorData[0]['EXPENSE_VENDOR_DEFAULT_PROJECT'] : ''
                ];
                $validAccount = (!empty($defAccount['ID'])) ? true : $validAccount;
            }
        }
        
        $validation = [
                    "IN_EXPENSE_DESCRIPTION" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => true,
                        "messages" => (object)["required" => 'This Field is required.']
                    ],
                    "IN_EXPENSE_ACCOUNT" => [
                        "isValid" => $validAccount,
                        "isDisabled" => false,
                        "isRequired" => true,
                        "messages" => (object)["required" => 'This Field is required.']
                    ],
                    "IN_EXPENSE_CORP_PROJ" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_PRETAX_AMOUNT" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_HST" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_TOTAL" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_PERCENTAGE" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_PERCENTAGE_TOTAL" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_NR" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => true,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_TEAM_ROUTING" => [
                        "isValid" => true,
                        "isDisabled" => true,
                        "isRequired" => true,
                        "messages" => (object)["required" => 'This Field is required.']
                    ],
                    "IN_EXPENSE_PROJECT_DEAL" => [
                        "isValid" => true,
                        "isDisabled" => true,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_FUND_MANAGER" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ],
                    "IN_EXPENSE_MANDATE" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => true,
                        "messages" => (object)["required" => 'This Field is required.']
                    ],
                    "IN_EXPENSE_ACTIVITY" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => true,
                        "messages" => (object)["required" => 'This Field is required.']
                    ],
                    "IN_EXPENSE_CORP_ENTITY" => [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ]
        ];
        $expenseData = [
            "IN_EXPENSE_CASE_ID" => $data["_request"]["id"],
            "IN_EXPENSE_ROW_ID" => generateUuidV4(),
            "IN_EXPENSE_DESCRIPTION" => empty($data['IN_INVOICE_TRANS_COMMENTS']) ? '' : $data['IN_INVOICE_TRANS_COMMENTS'],
            "IN_EXPENSE_ROW_NUMBER" => 1,
            "IN_EXPENSE_NR" => "Non-Recoverable",
            "IN_EXPENSE_TEAM_ROUTING" => ["ID" => "CORP", "LABEL" => "Corporate"],
            "IN_EXPENSE_MANDATE" => ["ID" => "0000", "LABEL" => "Non Recoverable"],
            "IN_EXPENSE_ACTIVITY" => ["ID" => "000", "LABEL" => "Unallocated"],
            "IN_EXPENSE_TEAM_ROW_INDEX" => 1,
            "IN_EXPENSE_PRETAX_AMOUNT" => $invoicePreTaxFormat,
            "IN_EXPENSE_HST" => $invoiceTaxTotalFormat,
            "IN_EXPENSE_TOTAL" => $invoiceTotalFormat,
            "IN_EXPENSE_PERCENTAGE" => 100,
            "IN_EXPENSE_PERCENTAGE_TOTAL" => "100",
            "IN_EXPENSE_VALIDATION" => base64_encode(json_encode($validation)),

        ];
        if(isset($defAccount)){
            $expenseData['IN_EXPENSE_ACCOUNT'] = $defAccount;
        }

        if(isset($defProject)){
            $expenseData['IN_EXPENSE_CORP_PROJ'] = $defProject;
        }
        $createExpences = createExpense($apiUrl, $expenseData, $requestId);
        //$allDatagrid[] = $expenseData;
    }*/
}

//Get options for dropdowns Manager
$idGropuManagers = 41;
$dataReturn["IN_LIST_GROUP_MANAGERS"] = getUserGroupManagers($idGropuManagers, $apiUrl);


// Round amounts for IS.03 - Jhon Chacolla
$dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] = (empty($invoiceTotal) || empty($invoiceTotal)) ? 0 : (($invoicePreTax * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = (empty($invoiceTaxTotal) || empty($invoiceTotal)) ? 0 :  (($invoiceTaxTotal * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TOTAL_PERCENTAGE"] = ($dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] +  $dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"]);

$dataReturn["IN_REQUEST_ID"] = $data["_request"]["id"];



//Get user permissions
$currentUserID = $data["IN_CURRENT_USER"]["id"];
$submitterDepartmentCollectionID = getCollectionId("IN_SUBMITTER_DEPARTMENT", $apiUrl);
$departmentsCollectionID = getCollectionId("IN_EXPENSE_DEPARTMENT", $apiUrl);
$submitterDepartment = getSubmitterDepartments($departmentsCollectionID, $submitterDepartmentCollectionID, $currentUserID, $apiUrl);


//Clear variables
$dataReturn["IN_SAVE_SUBMIT"] = null;
$dataReturn["fakeSaveCloseButton"] = null;
$dataReturn["saveButtonFake"] = null;
$dataReturn["submitButtonFake"] = null;
$dataReturn["validateForm"] = null;
$dataReturn["saveForm"] = null;
$dataReturn["saveFormClose"] = null;
$dataReturn["validation"] = null;
// Clean Action Variables  DHS.01
$dataReturn["IN_SUBMITTER_MANAGER_ACTION"] = null;
$dataReturn["IN_COMMENT_SUBMITTER"] = null;
// Clean Action Variables  DHS.02
$dataReturn["IN_SUBMITTER_MANAGER_EDIT_ACTION"] = null;
$dataReturn["IN_COMMENT_MANAGER_EDIT"] = null;

$dataReturn['IN_INVOICE_DISCREPANCY'] = empty($data['IN_INVOICE_TOTAL']) ? 0 : round((($data['IN_INVOICE_TOTAL'] * 0.1) / 100), 2);

//$dataReturn['IN_CASE_TITLE'] = $data['IN_INVOICE_VENDOR_LABEL'] . ' - ' . $data['IN_INVOICE_NUMBER'] . ' - ' . $data['IN_INVOICE_DATE'];

$dataReturn['CAN_BYPASS'] = $submitterDepartment;

//Set flag to not IS.02 - Ana
$dataReturn['IN_FLAG_IS02'] = false;
//Set flag to not show Excel Rows Number - Ana
$dataReturn['IN_SHOW_EXCEL_NUMBER'] = false;

//HTML to URL of PDF on additional Documents - Ana added
$tableForAdditionalFiles = "";
if (isset($data["IN_ADDITIONAL_FILES"]) && is_array($data["IN_ADDITIONAL_FILES"])) {
    $tableForAdditionalFiles = '<table width="100%" class="table">';
    foreach ($data["IN_ADDITIONAL_FILES"] as &$item) {
        $item["url"] = !empty($item["file"]) ? getFileDataLink($apiHost, $item["file"]) : null;
        $fileLink = getFileDataLink($apiHost, $item["file"]);
        $fileInfo = pathinfo($fileLink);

        $tableForAdditionalFiles .= '<tr> ';
        $tableForAdditionalFiles .= '<td class=""> ';
        $tableForAdditionalFiles .= '<a href="' . $fileLink . '" download="' . getLastSegment($fileLink) . '"><i class="fas fa-file-download"></i> <span style="text-decoration: underline;"> Download</span></a> ';
        $tableForAdditionalFiles .= '</td>';
        $tableForAdditionalFiles .= '<td>';
        $tableForAdditionalFiles .= getLastSegment($fileLink) ;
        $tableForAdditionalFiles .= '</td>';
        $tableForAdditionalFiles .= '<td>';
        
        if(strtoupper($fileInfo['extension']) == 'PDF') {
            $tableForAdditionalFiles .= '<p>';
            $tableForAdditionalFiles .= '<a class="btn btn-primary" draggable="false" href="' . $fileLink . '" target="_blank" rel="noopener">Preview file</a>';
            $tableForAdditionalFiles .= '</p>';
        }
        $tableForAdditionalFiles .= '</td>';
        $tableForAdditionalFiles .= '</tr>';
    }
    unset($item);
    $tableForAdditionalFiles.= "</table>";
}
$dataReturn["IN_ADDITIONAL_FILES_TABLE"] = $tableForAdditionalFiles;

//$dataReturn["ALL_DATA_GRID"] = json_encode($allDatagrid);

$time_end = microtime(true); //////////////////time execution
$execution_time = ($time_end - $time_start); //////////////////time execution
//$dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];//////////////////time execution
$dataTimeExec['IS.03 Pre processing'][] = $execution_time;//////////////////time execution
$dataReturn['dataTimeExec'] = $dataTimeExec; //////////////////time execution

return $dataReturn;

 
/**
 * Retrieve activity options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Adriana Centellas
 */
function getExpenseActivityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.ACTIVITY_LABEL' AS LABEL
                        FROM collection_" . $ID;

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve non recoverable activity options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * adapted copy from getExpenseActivityOptions
 */
function getNonRecoverableExpenseActivityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.ACTIVITY_LABEL' AS LABEL
                        FROM collection_" . $ID . " AS EXPENSE
                        WHERE EXPENSE.data->>'$.ACTIVITY_TYPE' IN ('Non-recoverable','Both')
                        ORDER BY LABEL ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve recoverable activity options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * adapted copy from getExpenseActivityOptions
 */
function getRecoverableExpenseActivityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.ACTIVITY_LABEL' AS LABEL
                        FROM collection_" . $ID . " AS EXPENSE
                        WHERE EXPENSE.data->>'$.ACTIVITY_TYPE' IN ('Recoverable','Both')
                        ORDER BY LABEL ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Generate the full URL for a PDF file within a request.
 *
 * @param object $api - The API instance to interact with the files.
 * @param int $requestId - The ID of the request that contains the file.
 * @param int $fileId - The ID of the file to retrieve.
 * @param string $instanceURL - The base URL of the instance.
 * @return string - The full URL to access the PDF file.
 *
 * by Adriana Centellas
 */
function getFileURLforPDF($api, $requestId, $fileId, $instanceURL) 
{
    // Access the files API instance
    $apiInstance = $api->files();

    // Retrieve the file details using the file ID
    $file = $apiInstance->getFileById($fileId);

    // Extract the file name from the file object
    $filename = $file->getFileName();

    // Construct and return the full URL for the file
    return $instanceURL . "requests/" . $requestId . "/files/" . $filename;
    return urlencode($instanceURL . "requests/" . $requestId . "/files/" . $filename);
}

/**
 * Get the managers assigned to a specific user group from the collection.
 *
 * @param int $groupId - The ID of the Managers Group.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of records from the Managers group with ID, FULLNAME, and EMAIL.
 *
 * by Manuel Monroy 
 */
function getUserGroupManagers($groupId, $apiUrl)
{
    // Check if the provided group ID is not empty
    if (!empty($groupId)) {
        // Define the SQL query to fetch user details (ID, email, and full name) from the active members of the specified group
        $queryGroupUsers = "SELECT U.id AS ID, U.email AS EMAIL, 
                                CONCAT(U.firstname, ' ', U.lastname) AS FULL_NAME
                            FROM users AS U
                            INNER JOIN group_members AS G
                                ON G.member_id = U.id
                            WHERE G.group_id = " . $groupId . " AND U.status = 'ACTIVE' AND U.deleted_at IS NULL
                            ORDER BY U.lastname ASC";  // Order by user's last name
        
        // Execute the query using the API to fetch the group members
        $groupUsersResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryGroupUsers));
    }

    // Return the response containing the group members (managers)
    return $groupUsersResponse;
}

/*
 * Get the manager assigned to the submitter from Collection.
 *
 * @param int $colSubmitterID - The ID of the collection IN_SUBMITTER_DEPARTMENT
 * @param int $colDepartmentID - The ID of the collection IN_APPROVER_DEPARMENT
 * @param string $apiUrl - The API URL used to make the request.
 * @param int $userSubmitterId - The ID of the submitter (initial user).
 * @return object - Returns the manager object from the SUBMITTER_DEPARTMENT collection assigned to the user.
 *
 * by Manuel Monroy 
 * modified by Ana Castillo
*/
function getManagerAssigned($colSubmitterID, $colDepartmentID, $apiUrl, $userSubmitterId)
{
    //Get to which department is part the submitter
    $sQGetDepartment = "SELECT data->>'$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_ACTG' AS DEPARTMENT
                        FROM collection_" . $colSubmitterID . "
                        WHERE data->>'$.SUBMITTER.id' = " . $userSubmitterId;
    $rQGetDepartment = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQGetDepartment)) ?? [];
    $department = "";
    if (count($rQGetDepartment) > 0) {
        $department = $rQGetDepartment[0]["DEPARTMENT"];
    } else {
        return "";
    }

    //Get approver for the deparment of the submitter
    $sQGetApprover = "SELECT CAST(data->>'$.SUBMITTER_MANAGER.id' AS UNSIGNED) AS ID,
                             data->>'$.SUBMITTER_MANAGER.email' AS EMAIL,
                             data->>'$.SUBMITTER_MANAGER.fullname' AS FULL_NAME
                      FROM collection_" . $colDepartmentID . "
                      WHERE data->>'$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_DB' = '" . $department . "'
                      AND data->>'$.SUBMITTER_DEFAULT' = 'true'";
    $rQGetApprover = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQGetApprover)) ?? [];
    if (count($rQGetApprover) > 0) {
        return $rQGetApprover[0];
    } else {
        return "";
    }
}

/**
 * Get the comment logs from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - An array of collection records with keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * 
 */
function getCommentsLog($ID, $apiUrl, $requestId)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT LOG.data->>'$.IN_CL_REQUEST_ID' AS REQUEST_ID,
                        LOG.data->>'$.IN_CL_USER' AS IN_COMMENT_USER,
                        LOG.data->>'$.IN_CL_USER_ID' AS IN_COMMENT_USER_ID,
                        LOG.data->>'$.IN_CL_ROLE' AS IN_COMMENT_ROLE,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_APPROVAL', 'null'), '') AS IN_COMMENT_APPROVAL,
                        IFNULL(NULLIF(LOG.data->>'$.IN_CL_COMMENT_SAVED', 'null'), '') AS IN_COMMENT_SAVED,
                        LOG.data->>'$.IN_CL_DATE' AS IN_COMMENT_DATE
                        FROM collection_" . $ID . " AS LOG
                        WHERE LOG.data->>'$.IN_CL_REQUEST_ID' = " . $requestId . " 
                        ORDER BY IN_COMMENT_DATE ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Insert a new record into custome EXPENCE_TABLE.
 *
 * @param string $apiUrl - The API URL for making the request.
 * @param array $expense - The data to save.
 * @param int $requestId - The ID of the request.
 * @return array - An empty array.
 *
 * by Jhon Chacolla
 * 
 */
function createExpense($apiUrl, $expense, $requestId) {
    $query  = '';
    $query .= 'INSERT INTO EXPENSE_TABLE (';
    $query .= 'IN_EXPENSE_CASE_ID, ';
    $query .= 'IN_EXPENSE_ROW_ID, ';
    $query .= 'IN_EXPENSE_ROW_NUMBER, ';
    $query .= 'IN_EXPENSE_TEAM_ROW_INDEX, ';
    $query .= 'IN_EXPENSE_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_ACCOUNT_ID, ';
    $query .= 'IN_EXPENSE_ACCOUNT_LABEL, ';
    $query .= 'IN_EXPENSE_CORP_PROJ_ID, ';
    $query .= 'IN_EXPENSE_CORP_PROJ_LABEL, ';
    $query .= 'IN_EXPENSE_PRETAX_AMOUNT, ';
    $query .= 'IN_EXPENSE_HST, ';
    $query .= 'IN_EXPENSE_TOTAL, ';
    $query .= 'IN_EXPENSE_PERCENTAGE, ';
    $query .= 'IN_EXPENSE_PERCENTAGE_TOTAL, ';
    $query .= 'IN_EXPENSE_NR_ID, ';
    $query .= 'IN_EXPENSE_NR_LABEL, ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_ID, ';
    $query .= 'IN_EXPENSE_TEAM_ROUTING_LABEL, ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_ID, ';
    $query .= 'IN_EXPENSE_PROJECT_DEAL_LABEL, ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_ID, ';
    $query .= 'IN_EXPENSE_FUND_MANAGER_LABEL, ';
    $query .= 'IN_EXPENSE_MANDATE_ID, ';
    $query .= 'IN_EXPENSE_MANDATE_LABEL, ';
    $query .= 'IN_EXPENSE_ACTIVITY_ID, ';
    $query .= 'IN_EXPENSE_ACTIVITY_LABEL, ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_ID, ';
    $query .= 'IN_EXPENSE_CORP_ENTITY_LABEL, ';
    $query .= 'IN_EXPENSE_TRANSACTION_COMMENTS, ';
    $query .= 'IN_EXPENSE_OFFICE_ID, ';
    $query .= 'IN_EXPENSE_OFFICE_LABEL, ';
    $query .= 'IN_EXPENSE_DEPARTMENT_ID, ';
    $query .= 'IN_EXPENSE_DEPARTMENT_LABEL, ';
    $query .= 'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION, ';
    $query .= 'IN_EXPENSE_COMPANY_ID, ';
    $query .= 'IN_EXPENSE_COMPANY_LABEL, ';
    $query .= 'IN_EXPENSE_VALIDATION, ';
    $query .= 'IN_EXPENSE_GL_CODE) ';
    
    $query .= 'VALUES (';
    $query .= "'" . $requestId . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ROW_ID'] . "', ";
    $query .= $expense['IN_EXPENSE_ROW_NUMBER'] . ", ";
    $query .= $expense['IN_EXPENSE_TEAM_ROW_INDEX'] . ", ";
    $query .= "'" . $expense['IN_EXPENSE_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACCOUNT']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_PROJ']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PRETAX_AMOUNT'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_HST'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PERCENTAGE_TOTAL'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_NR'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TEAM_ROUTING']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_PROJECT_DEAL']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_FUND_MANAGER']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_MANDATE']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_ACTIVITY']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_CORP_ENTITY']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_TRANSACTION_COMMENTS'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_OFFICE']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_DEPARTMENT']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY']['ID']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_COMPANY']['LABEL']  . "', ";
    $query .= "'" . $expense['IN_EXPENSE_VALIDATION'] . "', ";
    $query .= "'" . $expense['IN_EXPENSE_GL_CODE'] . "'";
    $query .= ');';
    //return $query;
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}
/**
 * Get total of rows by request ID from EXPENCE_TABLE.
 * @param string $apiUrl - The API URL for making the request.
 * @param int $requestId - The ID of the request.
 * @return array - Total records.
 * by Jhon Chacolla
 * 
 */
function checkRecordsByRequestId($apiUrl, $requestId){
    $query  = "";
    $query .= "SELECT COUNT(IN_EXPENSE_CASE_ID) AS TOTAL_ROWS ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "';";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    
    return $response;
}

/**
 * Generates a standart UUID with 32 characters
 * @return string - A UUID string
 * by Jhon Chacolla
 */
function generateUuidV4() {
    $data = random_bytes(16);
    
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); 
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); 
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function deleteRow($apiUrl, $requestId){
    $query  = "";
    $query .= "DELETE FROM EXPENSE_TABLE ";
    $query .= "WHERE EXPENSE_TABLE.IN_EXPENSE_CASE_ID = '" . $requestId ."'";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}

/**
 * Retrieve invoice expense mandate from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @param string $assetID - The ID of the collection.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getInExpenseMandateOption($ID, $apiUrl, $assetID)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT IEM.data->>'$.MANDATE_LABEL' AS MANDATE_LABEL,
                               IEM.data->>'$.MANDATE_SYSTEM_ID_ACTG' AS MANDATE_SYSTEM_ID,
                               IEM.data->>'$.MANDATE_ASSETCLASS' AS ASSETCLASS,
                               IAC.data->>'$.ASSET_ID' AS ASSET_ID,
                               IAC.data->>'$.ASSET_TYPE' AS ASSET_TYPE,
                               IAC.data->>'$.ASSET_LABEL' AS ASSET_LABEL
                        FROM collection_" . $ID . " AS IEM
                        LEFT JOIN collection_" . $assetID . " AS IAC
                        ON IAC.data->>'$.ASSET_ID' = IEM.data->>'$.MANDATE_ASSETCLASS'
                        WHERE IEM.data->>'$.MANDATE_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Get new values to the invoice array according the mandates.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the mandates and assets.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function determineAssetType($dataArray, $referenceArray) {
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_NR_LABEL'])) {
            // Check the IN_EXPENSE_NR_LABEL extracted on Excel and convert it to capital letters
            $label = strtoupper($item['IN_EXPENSE_NR_LABEL']);
            if ($label === 'R' || $label === 'RECOVERABLE') {
                $item['ASSET_TYPE'] = 'Recoverable';
            } elseif ($label === 'NR' || $label === 'NON-RECOVERABLE') {
                $item['ASSET_TYPE'] = 'Non-recoverable';
            }
        } elseif (isset($item['IN_EXPENSE_MANDATE_LABEL'])) {
            // Search by the query built MANDATE
            $mandate = trim($item['IN_EXPENSE_MANDATE_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['MANDATE_LABEL'], $mandate) !== false) {
                    $item['ASSET_TYPE'] = $reference['ASSET_TYPE'];
                    $item['MANDATE_ID'] = $reference['MANDATE_SYSTEM_ID'];
                    $item['MANDATE_LABEL'] = $reference['MANDATE_LABEL'];
                    $item['ASSET_ID'] = $reference['ASSET_ID'];
                    $item['ASSET_LABEL'] = $reference['ASSET_LABEL'];
                    break;
                }
            }
        } else {
            // If there are no IN_EXPENSE_NR_LABEL, MANDATE
            $item['ASSET_TYPE'] = "";
        }
        if (isset($item['IN_EXPENSE_MANDATE_LABEL'])) {
            $mandate = trim($item['IN_EXPENSE_MANDATE_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['MANDATE_LABEL'], $mandate) !== false) {
                    $item['MANDATE_ID'] = $reference['MANDATE_SYSTEM_ID'];
                    $item['MANDATE_LABEL'] = $reference['MANDATE_LABEL'];
                    $item['ASSET_ID'] = $reference['ASSET_ID'];
                    $item['ASSET_LABEL'] = $reference['ASSET_LABEL'];
                    break;
                }
            }
        }
    }

    return $dataArray;
}

/**
 * Retrieve Expense CORP ENTITY from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getInExpenseCorpEntityOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT IECE.data->>'$.id' AS ID,
                               IECE.data->>'$.EXPENSE_CORPORATE_LABEL' AS EXPENSE_CORPORATE_LABEL,
                               IECE.data->>'$.NL_COMPANY_SYSTEM_ID_DB' AS NL_COMPANY_SYSTEM_ID_DB,
                               IECE.data->>'$.EXPENSE_CORPORATE_STATUS' AS EXPENSE_CORPORATE_STATUS,
                               IECE.data->>'$.NL_COMPANY_SYSTEM_ID_ACTG' AS NL_COMPANY_SYSTEM_ID_ACTG
                        FROM collection_" . $ID . " AS IECE
                        WHERE IECE.data->>'$.EXPENSE_CORPORATE_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve Expense ACCOUNT from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getInExpenseAccountOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT IEAC.data->>'$.NL_ACCOUNT_SYSTEM_ID_ACTG' AS ID,
                               IEAC.data->>'$.ACCOUNT_LABEL' AS ACCOUNT_LABEL,
                               IEAC.data->>'$.NL_ACCOUNT_SYSTEM_ID_DB' AS NL_ACCOUNT_SYSTEM_ID_DB,
                               IEAC.data->>'$.ACCOUNT_STATUS' AS ACCOUNT_STATUS
                        FROM collection_" . $ID . " AS IEAC
                        WHERE IEAC.data->>'$.ACCOUNT_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Retrieve Expense Deal from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getInExpenseDealOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT IED.data->>'$.DEAL_SYSTEM_ID_DB' AS ID,
                               IED.data->>'$.DEAL_LABEL' AS DEAL_LABEL,
                               IED.data->>'$.DEAL_STATUS' AS DEAL_STATUS
                        FROM collection_" . $ID . " AS IED
                        WHERE IED.data->>'$.DEAL_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve Expense Corp Project from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getInCorpProjectOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT IECP.data->>'$.NL_COMPANY_SYSTEM_ID_ACTG' AS ID,
                               IECP.data->>'$.EXPENSE_CORPORATE_LABEL' AS CORP_PROJ_LABEL,
                               IECP.data->>'$.NL_CORPPROJ_STATUS' AS CORP_PROJ_DEAL_STATUS
                        FROM collection_" . $ID . " AS IECP
                        WHERE IECP.data->>'$.NL_CORPPROJ_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}
/**
 * Retrieve Fund Manager from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 */
function getInFundManagerOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT IEFM.data->>'$.DEAL_SYSTEM_ID_DB' AS ID,
                               IEFM.data->>'$.FUND_MANAGER_LABEL' AS FUND_MANAGER_LABEL,
                               IEFM.data->>'$.FUND_MANAGER_STATUS' AS FUND_MANAGER_STATUS,
                               IEFM.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_ID' AS ASSET_ID,
                               IEFM.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_TYPE' AS ASSET_TYPE,
                               IEFM.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' AS ASSET_LABEL
                        FROM collection_" . $ID . " AS IEFM
                        WHERE IEFM.data->>'$.FUND_MANAGER_STATUS' = 'Active'";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/**
 * Get new values to the invoice array according the corporate entity.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the corporate entities.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function checkCorporateEntity($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_CORP_ENTITY_LABEL'])) {
            //return $item;
            $corpEntity = trim($item['IN_EXPENSE_CORP_ENTITY_LABEL']);
            preg_match('/^Due \d+ from \(to\)\s*/', $corpEntity, $matches);
            //return $matches;
            if (!empty($matches)) {
                $cleanCorp = str_replace($matches[0], '', $corpEntity);
            } else {
                $cleanCorp = $corpEntity; // If not, keep the data
            }
            //return $cleanCorp;
            foreach ($referenceArray as $reference) {
                if (stripos($reference['EXPENSE_CORPORATE_LABEL'], $cleanCorp) !== false) {
                    $item['CORP_ID'] = $reference['ID'];
                    $item['EXPENSE_CORPORATE_LABEL'] = $reference['EXPENSE_CORPORATE_LABEL'];
                    $item['CORP_SYSTEM_ID_DB'] = $reference['NL_COMPANY_SYSTEM_ID_DB'];
                    $item['CORP_SYSTEM_ID_ACTG'] = $reference['NL_COMPANY_SYSTEM_ID_ACTG'];
                    break;
                }
            }
            //return $item;
        }
    }
    return $dataArray;
}
/**
 * Get new values to the invoice array according the activity.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the activities.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function checkActivity($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_ACTIVITY_LABEL'])) {
            $activity = trim($item['IN_EXPENSE_ACTIVITY_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['LABEL'], $activity) !== false) {
                    $item['ACTIVITY_ID'] = $reference['ID'];
                    $item['EXPENSE_ACTIVITY_LABEL'] = $reference['LABEL'];
                    break;
                }
            }
        }
    }
    return $dataArray;
}
/**
 * Get new values to the invoice array according the account.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the accounts.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function checkInAccount($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_ACCOUNT_LABEL'])) {
            $inAccount = trim($item['IN_EXPENSE_ACCOUNT_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['ACCOUNT_LABEL'], $inAccount) !== false) {
                    $item['ACCOUNT_ID'] = $reference['ID'];
                    $item['EXPENSE_ACCOUNT_LABEL'] = $reference['ACCOUNT_LABEL'];
                    break;
                }
            }
        }
    }
    return $dataArray;
}
/**
 * Get new values to the invoice array according the deal.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the deals.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function checkInDeal($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_PROJECT_DEAL_LABEL'])) {
            $inDeal = trim($item['IN_EXPENSE_PROJECT_DEAL_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['DEAL_LABEL'], $inDeal) !== false) {
                    $item['DEAL_ID'] = $reference['ID'];
                    $item['EXPENSE_DEAL_LABEL'] = $reference['DEAL_LABEL'];
                    break;
                }
            }
        }
    }
    return $dataArray;
}
/**
 * Get new values to the invoice array according the corp project.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the corp projects.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function checkInCorpProject($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_CORP_PROJ_LABEL'])) {
            $inCorpProj = trim($item['IN_EXPENSE_CORP_PROJ_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['CORP_PROJ_LABEL'], $inCorpProj) !== false) {
                    $item['CORP_PROJ_ID'] = $reference['ID'];
                    $item['EXPENSE_CORP_PROJ_LABEL'] = $reference['CORP_PROJ_LABEL'];
                    break;
                }
            }
        }
    }
    return $dataArray;
}
/**
 * Get new values to the invoice array according the fund manager.
 *
 * @param array $dataArray - Invoice array.
 * @param array $referenceArray - Array with the corp fund managers.
 * @return array - An array with asset types and mandate ids.
 *
 * by Favio Mollinedo
 */
function checkInFundManager($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_FUND_MANAGER_LABEL'])) {
            $inFundManager = trim($item['IN_EXPENSE_FUND_MANAGER_LABEL']);
            foreach ($referenceArray as $reference) {
                if (stripos($reference['FUND_MANAGER_LABEL'], $inFundManager) !== false) {
                    $item['FUND_MANAGER_ID'] = $reference['ID'];
                    $item['EXPENSE_FUND_MANAGER_LABEL'] = $reference['FUND_MANAGER_LABEL'];
                    $item['EXPENSE_FM_ASSET_ID'] = $reference['ASSET_ID'];
                    $item['EXPENSE_FM_ASSET_TYPE'] = $reference['ASSET_TYPE'];
                    $item['EXPENSE_FM_ASSET_LABEL'] = $reference['ASSET_LABEL'];
                    break;
                }
            }
        }
    }
    return $dataArray;
}

function checkInDepartment($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_DEPARTMENT_LABEL'])) {
            $inDept = trim($item['IN_EXPENSE_DEPARTMENT_LABEL']);
            $key = array_search($inDept, array_column($referenceArray, 'LABEL'));
            if($key){
                $item['IN_EXPENSE_DEPARTMENT_ID'] = $referenceArray[$key]['ID'];
            }
        }
    }
    return $dataArray;
}

function checkInOffice($dataArray, $referenceArray){
    foreach ($dataArray as &$item) {
        if (isset($item['IN_EXPENSE_OFFICE_LABEL'])) {
            $inDept = trim($item['IN_EXPENSE_OFFICE_LABEL']);
            $key = array_search($inDept, array_column($referenceArray, 'LABEL'));
            if($key){
                $item['IN_EXPENSE_OFFICE_ID'] = $referenceArray[$key]['ID'];
            }
        }
    }
    return $dataArray;
}


function getValidationRules($apiUrl, $collectionId) 
{
    $query  = "";
    $query .= "SELECT data->>'$.DEFAULT_ID' AS DEFAULT_ID, ";
    $query .= "data->>'$.DEFAULT_TYPE' AS DEFAULT_TYPE, ";
    $query .= "data->>'$.DEFAULT_LABEL' AS DEFAULT_LABEL, ";
    $query .= "data->>'$.DEFAULT_STATUS' AS DEFAULT_STATUS, ";
    $query .= "data->>'$.DEFAULT_DISABLE' AS DEFAULT_DISABLE, ";
    $query .= "data->>'$.DEFAULT_REQUIRED' AS DEFAULT_REQUIRED, ";
    $query .= "data->>'$.DEFAULT_VARIABLE' AS DEFAULT_VARIABLE ";
    $query .= "FROM collection_" .  $collectionId . " ";
    $query .= "WHERE data->>'$.DEFAULT_STATUS' = 'Active'; ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return array_map(function($rule){
        return [
            'DEFAULT_ID' => $rule['DEFAULT_ID'] == 'null' ? null : $rule['DEFAULT_ID'],
            'DEFAULT_TYPE' => $rule['DEFAULT_TYPE'] == 'null' ? null : $rule['DEFAULT_TYPE'],
            'DEFAULT_LABEL' => $rule['DEFAULT_LABEL'] == 'null' ? null : $rule['DEFAULT_LABEL'],
            'DEFAULT_STATUS' => $rule['DEFAULT_STATUS'] == 'null' ? null : $rule['DEFAULT_STATUS'],
            'DEFAULT_DISABLE' => $rule['DEFAULT_DISABLE'] === 'true',
            'DEFAULT_REQUIRED' => $rule['DEFAULT_REQUIRED'] === 'true',
            'DEFAULT_VARIABLE' => $rule['DEFAULT_VARIABLE'] == 'null' ? null : $rule['DEFAULT_VARIABLE']
        ];
    }, $response);
}

function setCellRowValidation($arrayValidation, $type, $column, $cellValue) 
{
    $validationRule = array_values(array_filter($arrayValidation, function($rule)use ($type, $column){
        return $rule['DEFAULT_TYPE'] == $type && $rule['DEFAULT_VARIABLE'] == $column;
    }));
    if(empty($validationRule) || empty($type)){ 
        return [
            "isValid" => true,
            "isDisabled" => false,
            "isRequired" => false,
            "messages" => (object)[]
        ];
    } else {
        $isvalidValue = true;
        if($validationRule[0]['DEFAULT_REQUIRED']){
            $isvalidValue = !(empty($cellValue) || empty($cellValue['ID']));
        }

        return [
            "isValid" => $isvalidValue,
            "isDisabled" => $validationRule[0]['DEFAULT_DISABLE'],
            "isRequired" => $validationRule[0]['DEFAULT_REQUIRED'],
            "messages" => $validationRule[0]['DEFAULT_REQUIRED'] ? (object)['required' => 'This field is required'] : (object)[],
        ];
    }
    return $validationRule;
}

function getFileData($apiUrl, $fileId) {
    // Prepare SQL query to fetch records for the collection using its ID
    $query  = "";
    $query .= "SELECT * ";
    $query .= "FROM media";


    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

function setValidation($arrayValidation, $expenseRow){
    $nr = $expenseRow['IN_EXPENSE_NR'];
    $validations = [];
    $fields = ['IN_EXPENSE_DESCRIPTION','IN_EXPENSE_ACCOUNT','IN_EXPENSE_CORP_PROJ','IN_EXPENSE_PRETAX_AMOUNT','IN_EXPENSE_HST','IN_EXPENSE_TOTAL','IN_EXPENSE_PERCENTAGE','IN_EXPENSE_PERCENTAGE_TOTAL','IN_EXPENSE_NR','IN_EXPENSE_TEAM_ROUTING','IN_EXPENSE_PROJECT_DEAL','IN_EXPENSE_FUND_MANAGER','IN_EXPENSE_MANDATE','IN_EXPENSE_ACTIVITY','IN_EXPENSE_CORP_ENTITY'];
    foreach($fields as $key => $value){
        $validationRule = array_values(array_filter($arrayValidation, function($rule) use ($nr, $value){
            return $rule['DEFAULT_TYPE'] == $nr && $rule['DEFAULT_VARIABLE'] == $value;
        }));

        if(empty($validationRule)){
            if($value == 'IN_EXPENSE_NR'){
                $validations[$value] = [
                    "isValid" => !empty($expenseRow[$value]),
                    "isDisabled" => false,
                    "isRequired" => true,
                    "messages" => (object)['required' => 'This field is required']
                ];
            } else {
                $validations[$value] = [
                    "isValid" => true,
                    "isDisabled" => false,
                    "isRequired" => false,
                    "messages" => (object)[]
                ];
            }
        } else {
            if(!empty($validationRule[0]['DEFAULT_ID']) && empty($expenseRow[$value]['ID'])){
                $expenseRow[$value]['ID'] = $validationRule[0]['DEFAULT_ID'];
                $expenseRow[$value]['LABEL'] = $validationRule[0]['DEFAULT_LABEL'];
            }
            $isvalidValue = true;
            if($validationRule[0]['DEFAULT_REQUIRED']){
                $isvalidValue = !(empty($expenseRow[$value]) || empty($expenseRow[$value]['ID']));
            }
            $validations[$value] = [
                "isValid" => $isvalidValue,
                "isDisabled" => $validationRule[0]['DEFAULT_DISABLE'],
                "isRequired" => $validationRule[0]['DEFAULT_REQUIRED'],
                "messages" => $validationRule[0]['DEFAULT_REQUIRED'] ? (object)['required' => 'This field is required'] : (object)[],
            ];
        }
    }
    $expenseRow['IN_EXPENSE_VALIDATION'] =  base64_encode(json_encode($validations));
    return $expenseRow;
}

/*  
*  Retrieves the department IDs associated with a given submitter.
*  
*  @param int $collectionID  The ID of the collection being queried.
*  @param int $userID        The ID of the submitter.
*  @param string $apiUrl     The API URL for executing the query.
*  
*  @return mixed  The result of the API call containing submitter department IDs.
*  
*  by Adriana Centellas  
*/
function getSubmitterDepartments($collectionDepartmentID, $collectionSubDepID, $userID, $apiUrl)
{
    $querySubmitterDepartments = "SELECT ";
    $querySubmitterDepartments .= "JSON_UNQUOTE(JSON_EXTRACT(D.data, '$.CAN_BYPASS')) AS CAN_BYPASS ";
    $querySubmitterDepartments .= "FROM collection_".$collectionSubDepID." SD ";
    $querySubmitterDepartments .= "INNER JOIN collection_".$collectionDepartmentID." D ";
    $querySubmitterDepartments .= "ON JSON_UNQUOTE(JSON_EXTRACT(SD.data, '$.SUBMITTER_DEPARTMENT.id')) = D.id ";
    $querySubmitterDepartments .= "WHERE JSON_UNQUOTE(JSON_EXTRACT(SD.data, '$.SUBMITTER.id')) = ".$userID;
    $querySubmitterDepartments .= " group by JSON_UNQUOTE(JSON_EXTRACT(D.data, '$.CAN_BYPASS'))";
        
    $groupSubmitterDepartments = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySubmitterDepartments));

    return $groupSubmitterDepartments[0]["CAN_BYPASS"];
}
/**
 * Get get File Link.
 *
 * @param string $apiHost - PM API string.
 * @param string $fileId - File ID.
 * @return string - File link.
 *
 * by Jhon Chacolla
 */
function getFileDataLink($apiHost, $fileId) 
{
    if(empty($fileId)){ 
        return '';
    }
    $fileData = callApiUrlGuzzle($apiHost .'/files/' . $fileId, 'GET');
    return $fileData['original_url'];
}

/**
 * Retrieves the last segment of a given URL.
 * 
 * @param string $url The URL from which to extract the last segment.
 * @return string The last segment of the URL, or an empty string if the URL is invalid.
 */
function getLastSegment($url) 
{
    // Ensure the URL is a valid string
    if (!is_string($url) || empty($url)) {
        return ''; // Return an empty string for invalid input
    }

    // Remove trailing slash, if any
    $url = rtrim($url, '/');

    // Get the last segment after the final "/"
    return basename($url);
}