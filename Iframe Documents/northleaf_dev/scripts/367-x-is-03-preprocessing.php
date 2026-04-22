<?php 
/***********************************************
 *  IS.03 Pre processing
 *  
 *  By Adriana Centellas
 *
 * Modified by Favio Mollinedo
 * Modified by Ana Castillo
 **********************************************/

require_once("/Northleaf_PHP_Library.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

//Set initial values
$dataReturn = array();

//Get global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$baseURL = getenv('ENVIRONMENT_BASE_URL');
$requestId = $data["_request"]["id"];
$fileId = $data["IN_UPLOAD_PDF"];

//Get options for dropdowns

$collectionName = "IN_EXPENSE_ACTIVITY";
$collectionID = getCollectionId($collectionName, $apiUrl);

//Get options for dropdowns Manager
$idGropuManagers = 41;
$dataReturn["IN_LIST_GROUP_MANAGERS"] = getUserGroupManagers($idGropuManagers, $apiUrl);

//Get the manager selected by submiter and department
$collectionName = "IN_APPROVER_DEPARTMENT";
$collectionIdDepartment = getCollectionId($collectionName, $apiUrl);
$idInitialUSer = $data['IN_DATA_INITIAL_USER']['ID'];
$dataReturn["IN_SUBMITTER_MANAGER"] = getManagerAssigned($collectionIdDepartment, $apiUrl, $idInitialUSer);

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
$dataReturn["IN_EXPENSE_ACTIVITY_NON_RECOVERABLE"] = $officeLocationNROptions;
$dataReturn["IN_EXPENSE_ACTIVITY_RECOVERABLE"] = $officeLocationROptions;
// Get Validation rules
$collectionIdEDV = getCollectionId('IN_EXPENSE_DEFAULTS', $apiUrl);
$validationRules = getValidationRules($apiUrl, $collectionIdEDV);
//return $validationRules;
//$filesList = getFileData($apiUrl, 1234);
//return $filesList;
//$test = setCellRowValidation($validationRules, 'Non-Recoverable', 'IN_EXPENSE_TEAM_ROUTING', ["ID" => '123',"LABEL" => 'test test']);
//return $test;
//URL of PDF
$dataReturn["IN_PDF_URL"] = !empty($fileId) ? getFileURLforPDF($api, $requestId, $fileId, $baseURL) : null;
$fileData = $baseURL .getFileDataLink($apiUrl,$requestId, $fileId);
//return $fileData;
// return $instanceURL . "requests/" . $requestId . "/files/" . $filename;
$test_abc = callApiUrlGuzzle($apiHost .'/files/' . $fileId, 'GET');
return $test_abc;
$testFile = LocalApi::get('files/' . $fileId );
return $testFile;
return $dataReturn["IN_PDF_URL"];

$tableForAdditionalFiles = "";
if (isset($data["IN_ADDITIONAL_FILES"]) && is_array($data["IN_ADDITIONAL_FILES"])) {
    $tableForAdditionalFiles = "<table>";
    foreach ($data["IN_ADDITIONAL_FILES"] as &$item) {
        $item["url"] = !empty($item["file"]) 
            ? getFileURLforPDF($api, $requestId, $item["file"], $baseURL) 
            : null;
        $tableForAdditionalFiles .= "
<tr>
    <td class=\"button-as-links\">
        <a href=\"" . getFileURLforPDF($api, $requestId, $item['file'], $baseURL) . "\" download=\"invoicesample.pdf\">Download</a>
    </td>
    <td>
        " . getLastSegment(getFileURLforPDF($api, $requestId, $item['file'], $baseURL)) . "
    </td>
    <td>
        <p>
            <a class=\"btn btn-primary\" draggable=\"false\" href=\"" . getFileURLforPDF($api, $requestId, $item['file'], $baseURL) . "\" target=\"_blank\" rel=\"noopener\">Preview file</a>
        </p>
    </td>
</tr>";
    }
    unset($item);
    $tableForAdditionalFiles.= "</table>";
    $dataReturn["IN_ADDITIONAL_FILES"] = $data["IN_ADDITIONAL_FILES"];
    $dataReturn["IN_ADDITIONAL_FILES_TABLE"] = $tableForAdditionalFiles;
}
return 'jijijijijijij';
//Set Totals needed for IS.03 - Ana Castillo
//Convert values to number
$invoicePreTaxFormat = $data["IN_INVOICE_PRE_TAX"];
$invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
$invoiceTotalFormat = $data["IN_INVOICE_TOTAL"];
$invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
$invoiceTaxTotalFormat = $data["IN_INVOICE_TAX_TOTAL"];
$invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);
// Round amounts for IS.03 - Jhon Chacolla
$dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] = (empty($invoiceTotal) || empty($invoiceTotal)) ? 0 : (($invoicePreTax * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = (empty($invoiceTaxTotal) || empty($invoiceTotal)) ? 0 :  (($invoiceTaxTotal * 100) / $invoiceTotal);
$dataReturn["IN_INVOICE_TOTAL_PERCENTAGE"] = ($dataReturn["IN_INVOICE_PRE_TAX_PERCENTAGE"] +  $dataReturn["IN_INVOICE_TAX_TOTAL_PERCENTAGE"]);

$dataReturn["IN_REQUEST_ID"] = $data["_request"]["id"];

// Excel Data Records - by Favio Mollinedo
$excelData = [];
$invoiceExcelArray = !empty($data["IN_INVOICE_ARRAY"]) ? $data["IN_INVOICE_ARRAY"] : [];
$invoiceExcelArrayExtended = determineAssetType($invoiceExcelArray, $expenseMandateAssetOptions);
//return $invoiceExcelArrayExtended;
//return $expenseDataArray;
// Add a new record is expence table is empty costume table - Jhon Chacolla
// deleteRow($apiUrl, $requestId);
// return 'deleted';
$totalRecords = checkRecordsByRequestId($apiUrl, $requestId);
//return $totalRecords;
$test = [];
if ($totalRecords[0]['TOTAL_ROWS'] <= 0) {
    foreach($invoiceExcelArrayExtended as $index => $excelData){
        //Convert values to number
        //$invoicePreTaxFormat = $excelData["IN_EXPENSE_PRETAX_AMOUNT"];
        $invoicePreTaxFormat = $excelData["IN_EXPENSE_TOTAL"] - $excelData["IN_EXPENSE_HST"];
        $invoicePreTax = (float) str_replace(",", "", $invoicePreTaxFormat);
        $invoiceTotalFormat = $excelData["IN_EXPENSE_TOTAL"];
        $invoiceTotal = (float) str_replace(",", "", $invoiceTotalFormat);
        $invoiceTaxTotalFormat = $excelData["IN_EXPENSE_HST"];
        $invoiceTaxTotal = (float) str_replace(",", "", $invoiceTaxTotalFormat);
        // Round amounts for IS.03 - Jhon Chacolla
        $inInvoicePreTaxPercentage = (empty($invoiceTotal) || empty($invoiceTotal)) ? 0 : (($invoicePreTax * 100) / $invoiceTotal);
        $inInvoiceTaxTotalPercentage = (empty($invoiceTaxTotal) || empty($invoiceTotal)) ? 0 :  (($invoiceTaxTotal * 100) / $invoiceTotal);
        $inInvoiceTotalPercentage = ($inInvoicePreTaxPercentage + $inInvoiceTaxTotalPercentage);
        $excelData["IN_INVOICE_PRE_TAX_PERCENTAGE"] = $inInvoicePreTaxPercentage;
        $excelData["IN_INVOICE_TAX_TOTAL_PERCENTAGE"] = $inInvoiceTaxTotalPercentage;
        $excelData["IN_INVOICE_TOTAL_PERCENTAGE"] = $inInvoiceTotalPercentage;
        $expenseDataArray = [
            "IN_EXPENSE_CASE_ID" => $data["_request"]["id"],
            "IN_EXPENSE_ROW_ID" => generateUuidV4(),
            "IN_EXPENSE_DESCRIPTION" => empty($excelData["IN_EXPENSE_DESCRIPTION"]) ? '' : $excelData["IN_EXPENSE_DESCRIPTION"],
            "IN_EXPENSE_NR" => empty($excelData["ASSET_TYPE"]) ? '' : $excelData["ASSET_TYPE"],
            //"IN_EXPENSE_NR_LABEL" => $excelData["ASSET_TYPE"],
            "IN_EXPENSE_MANDATE" => [
                                    "ID" => empty($excelData["MANDATE_ID"]) ? '' : $excelData["MANDATE_ID"],
                                    "LABEL" => empty($excelData["MANDATE_LABEL"]) ? '' : $excelData["MANDATE_LABEL"]
                                    ],
            "IN_EXPENSE_TEAM_ROUTING" => [
                                    "ID" => empty($excelData["ASSET_ID"]) ? '' : $excelData["ASSET_ID"],
                                    "LABEL" => empty($excelData["ASSET_LABEL"]) ? '' : $excelData["ASSET_LABEL"]
                                    ],
            "IN_EXPENSE_ROW_NUMBER" => $index+1,
            "IN_EXPENSE_TEAM_ROW_INDEX" => $index+1,
            "IN_EXPENSE_PRETAX_AMOUNT" => number_format(($invoiceTotal - $invoiceTaxTotal), 2, '.', ''),
            "IN_EXPENSE_HST" => number_format($invoiceTaxTotalFormat, 2, '.', ''),
            "IN_EXPENSE_TOTAL" => number_format($invoiceTotalFormat, 2, '.', ''),
            "IN_EXPENSE_PERCENTAGE" => 100,
            "IN_EXPENSE_PERCENTAGE_TOTAL" => ($invoiceTotalFormat == 0) ? 0 : (($invoiceTotalFormat * $inInvoiceTotalPercentage) / $invoiceTotalFormat),
        ];
        // $validationData = [
        //     "IN_EXPENSE_DESCRIPTION" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_DESCRIPTION', $expenseDataArray['IN_EXPENSE_DESCRIPTION']),
        //     "IN_EXPENSE_ACCOUNT" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_ACCOUNT', $expenseDataArray['IN_EXPENSE_ACCOUNT']),
        //     "IN_EXPENSE_CORP_PROJ" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_CORP_PROJ', $expenseDataArray['IN_EXPENSE_CORP_PROJ']),
        //     "IN_EXPENSE_PRETAX_AMOUNT" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_PRETAX_AMOUNT', $expenseDataArray['IN_EXPENSE_PRETAX_AMOUNT']),
        //     "IN_EXPENSE_HST" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_HST', $expenseDataArray['IN_EXPENSE_HST']),
        //     "IN_EXPENSE_TOTAL" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_TOTAL', $expenseDataArray['IN_EXPENSE_TOTAL']),
        //     "IN_EXPENSE_PERCENTAGE" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_PERCENTAGE', $expenseDataArray['IN_EXPENSE_PERCENTAGE']),
        //     "IN_EXPENSE_PERCENTAGE_TOTAL" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_PERCENTAGE_TOTAL', $expenseDataArray['IN_EXPENSE_PERCENTAGE_TOTAL']),
        //     "IN_EXPENSE_NR"  => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_NR', $expenseDataArray['IN_EXPENSE_NR']),
        //     "IN_EXPENSE_TEAM_ROUTING" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_TEAM_ROUTING', $expenseDataArray['IN_EXPENSE_TEAM_ROUTING']),
        //     "IN_EXPENSE_PROJECT_DEAL" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_PROJECT_DEAL', $expenseDataArray['IN_EXPENSE_PROJECT_DEAL']),
        //     "IN_EXPENSE_FUND_MANAGER" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_FUND_MANAGER', $expenseDataArray['IN_EXPENSE_FUND_MANAGER']),
        //     "IN_EXPENSE_MANDATE" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_MANDATE', $expenseDataArray['IN_EXPENSE_MANDATE']),
        //     "IN_EXPENSE_ACTIVITY" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_ACTIVITY', $expenseDataArray['IN_EXPENSE_ACTIVITY']),
        //     "IN_EXPENSE_CORP_ENTITY" => (object) setCellRowValidation($validationRules,$expenseDataArray['IN_EXPENSE_NR'], 'IN_EXPENSE_CORP_ENTITY', $expenseDataArray['IN_EXPENSE_CORP_ENTITY'])
        // ];
        // if($expenseDataArray['IN_EXPENSE_NR'] == 'Recoverable'){
        //     if( 
        //         (empty($expenseDataArray['IN_EXPENSE_PROJECT_DEAL']) || empty($expenseDataArray['IN_EXPENSE_PROJECT_DEAL']['ID'])) &&
        //         (empty($expenseDataArray['IN_EXPENSE_FUND_MANAGER']) || empty($expenseDataArray['IN_EXPENSE_FUND_MANAGER']['ID'])) &&
        //         (empty($expenseDataArray['IN_EXPENSE_MANDATE']) || empty($expenseDataArray['IN_EXPENSE_MANDATE']['ID']))
        //     ){
        //         $validationData['IN_EXPENSE_PROJECT_DEAL']->isValid = false;
        //         $validationData['IN_EXPENSE_PROJECT_DEAL']->messages = (object)['atLeastOneRequired' => 'You must select at least one of these fields'];
        //         $validationData['IN_EXPENSE_FUND_MANAGER']->isValid = false;
        //         $validationData['IN_EXPENSE_FUND_MANAGER']->messages = (object)['atLeastOneRequired' => 'You must select at least one of these fields'];
        //         $validationData['IN_EXPENSE_MANDATE']->isValid = false;
        //         $validationData['IN_EXPENSE_MANDATE']->messages = (object)['atLeastOneRequired' => 'You must select at least one of these fields'];
        //     }
        // }
        //$expenseDataArray["IN_EXPENSE_VALIDATION"] = base64_encode(json_encode($validationData));

        $newRow = setValidation($validationRules, $expenseDataArray);
        $createExpences = createExpense($apiUrl, $newRow, $requestId);
    }
}

//return $expenseDataArray;
// Add a new record is expence table is empty costume table - Jhon Chacolla
// deleteRow($apiUrl, $requestId);
// return 'deleted';
$totalRecords = checkRecordsByRequestId($apiUrl, $requestId);

if ($totalRecords[0]['TOTAL_ROWS'] <= 0) {
    $validation = [
				"IN_EXPENSE_DESCRIPTION" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_ACCOUNT" => [
					"isValid" => false,
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
        "IN_EXPENSE_NR" => "Non Recoverable",
        "IN_EXPENSE_TEAM_ROUTING" => ["ID" => "CORP", "LABEL" => "Corporate"],
        "IN_EXPENSE_MANDATE" => ["ID" => "0000", "LABEL" => "Non Recoverable"],
        "IN_EXPENSE_ACTIVITY" => ["ID" => "000", "LABEL" => "Unallocated"],
        "IN_EXPENSE_TEAM_ROW_INDEX" => 1,
        "IN_EXPENSE_PRETAX_AMOUNT" => $invoicePreTaxFormat,
        "IN_EXPENSE_HST" => $invoiceTaxTotalFormat,
        "IN_EXPENSE_TOTAL" => $invoiceTotalFormat,
        "IN_EXPENSE_PERCENTAGE" => 100,
        "IN_EXPENSE_PERCENTAGE_TOTAL" => "100",
        "IN_EXPENSE_VALIDATION" => base64_encode(json_encode($validation))
    ];

    $createExpences = createExpense($apiUrl, $expenseData, $requestId);
}

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

$dataReturn['IN_CASE_TITLE'] = $data['IN_INVOICE_VENDOR_LABEL'] . ' - ' . $data['IN_INVOICE_NUMBER'] . ' - ' . $data['IN_INVOICE_DATE'];

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
    return $file;
    // return [
    //     'model_id' => $file->getModelId(),
    //     'data_name' => $file->getCustomProperties()['data_name'],
    //     'ftftftftftf'
    // ];

    // Construct and return the full URL for the file
    return $instanceURL . "requests/" . $requestId . "/files/" . $filename;
    return urlencode($instanceURL . "requests/" . $requestId . "/files/" . $filename);
}

/**
 * Retrieves the last segment of a given URL.
 * 
 * @param string $url The URL from which to extract the last segment.
 * @return string The last segment of the URL, or an empty string if the URL is invalid.
 */
function getLastSegment($url) {
    // Ensure the URL is a valid string
    if (!is_string($url) || empty($url)) {
        return ''; // Return an empty string for invalid input
    }

    // Remove trailing slash, if any
    $url = rtrim($url, '/');

    // Get the last segment after the final "/"
    return basename($url);
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
 * @param int $ID - The ID of the collection.
 * @param int $userSubmitterId - The ID of the submitter (initial user).
 * @param string $apiUrl - The API URL used to make the request.
 * @return object - Returns the manager object from the SUBMITTER_DEPARTMENT collection assigned to the user.
 *
 * by Manuel Monroy 
*/
function getManagerAssigned($ID, $apiUrl, $userSubmitterId)
{
    // Initialize the SQL query string
    $queryCollectionDeal = '';
    
    // Select fields from the JSON in the collection, extracting the manager details
    $queryCollectionDeal .= 'SELECT ';
    $queryCollectionDeal .= 'CAST(JSON_UNQUOTE(ID.data->"$.SUBMITTER_MANAGER.id") AS UNSIGNED) AS ID, '; // Convert the manager's ID to an integer
    $queryCollectionDeal .= 'JSON_UNQUOTE(ID.data->"$.SUBMITTER_MANAGER.email") AS EMAIL, '; // Extract manager's email
    $queryCollectionDeal .= 'JSON_UNQUOTE(ID.data->"$.SUBMITTER_MANAGER.fullname") AS FULL_NAME '; // Extract manager's full name
    
    // From the specific collection based on the given ID
    $queryCollectionDeal .= 'FROM collection_' . $ID . ' AS ID ';
    
    // Apply the filter where the submitter's ID in the department matches the provided userSubmitterId
    $queryCollectionDeal .= 'WHERE JSON_UNQUOTE(ID.data->"$.SUBMITTER_DEPARTMENT.USER_SUBMITTER.id") = "' . $userSubmitterId . '"';
    $queryCollectionDeal .= ' AND JSON_UNQUOTE(ID.data->"$.SUBMITTER_DEFAULT") = "true"';

    // Make the API request and get the collection records
    // If no results are found, return an empty array
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionDeal)) ?? [];

    // Return the first record (manager details) if available, or an empty array if no records exist
    return !empty($collectionRecords) && is_array($collectionRecords) ? $collectionRecords[0] : [];
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
    $query .= "SELECT COUNT(*) AS TOTAL_ROWS ";
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
 * Ass new values to the invoice array according the mandates.
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
            // Check the IN_EXPENSE_NR_LABEL
            $label = $item['IN_EXPENSE_NR_LABEL'];
            if ($label === 'R' || $label === 'Recoverable') {
                $item['ASSET_TYPE'] = 'Recoverable';
            } elseif ($label === 'NR' || $label === 'Non-recoverable') {
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

function getValidationRules($apiUrl, $collectionId) {
    $query  = "";
    $query .= "SELECT data->>'$.DEFAULT_ID' AS DEFAULT_ID, ";
    $query .= "data->>'$.DEFAULT_TYPE' AS DEFAULT_TYPE, ";
    $query .= "data->>'$.DEFAULT_LABEL' AS DEFAULT_LABEL, ";
    $query .= "data->>'$.DEFAULT_STATUS' AS DEFAULT_STATUS, ";
    $query .= "data->>'$.DEFAULT_DISABLE' AS DEFAULT_DISABLE, ";
    $query .= "data->>'$.DEFAULT_REQUIRED' AS DEFAULT_REQUIRED, ";
    $query .= "data->>'$.DEFAULT_VARIABLE' AS DEFAULT_VARIABLE ";
    $query .= "FROM collection_" .  $collectionId . " ";
    //$query .= "WHERE data->>'$.DEFAULT_TYPE' = '" . $type . "'; ";
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

function setCellRowValidation($arrayValidation, $type, $column, $cellValue) {
    $validationRule = array_values(array_filter($arrayValidation, function($rule)use ($type, $column){
        return $rule['DEFAULT_TYPE'] == $type && $rule['DEFAULT_VARIABLE'] == $column;
    }));
     //return $validationRule;
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

// function getFileData($apiUrl, $fileId) {
//     // Prepare SQL query to fetch records for the collection using its ID
//     $query  = "";
//     $query .= "SELECT * ";
//     $query .= "FROM media";


//     // Send API request to fetch collection records, return an empty array if none are found
//     $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query)) ?? [];

//     // Return the records fetched from the API call
//     return $collectionRecords;
// }

function setValidation($arrayValidation, $expenseRow){
    $nr = $expenseRow['IN_EXPENSE_NR'];
    $validations = [];
    foreach($expenseRow as $key => $value){
        $configColumns = ['IN_EXPENSE_CASE_ID', 'IN_EXPENSE_ROW_ID', 'IN_EXPENSE_ROW_NUMBER', 'IN_EXPENSE_TEAM_ROW_INDEX'];
        if(!in_array($key, $configColumns)){
            $validationRule = array_values(array_filter($arrayValidation, function($rule) use ($nr, $key){
                return $rule['DEFAULT_TYPE'] == $nr && $rule['DEFAULT_VARIABLE'] == $key;
            }));

            if(empty($validationRule)){
                if($key == 'IN_EXPENSE_NR'){
                    $validations[$key] = [
                        "isValid" => !empty($expenseRow[$key]),
                        "isDisabled" => false,
                        "isRequired" => true,
                        "messages" => (object)['required' => 'This field is required']
                    ];
                } else {
                    $validations[$key] = [
                        "isValid" => true,
                        "isDisabled" => false,
                        "isRequired" => false,
                        "messages" => (object)[]
                    ];
                }
            } else {
                if(!empty($validationRule[0]['DEFAULT_ID']) && empty($expenseRow[$key]['ID'])){
                    $expenseRow[$key]['ID'] = $validationRule[0]['DEFAULT_ID'];
                    $expenseRow[$key]['LABEL'] = $validationRule[0]['DEFAULT_LABEL'];
                }
                $isvalidValue = true;
                if($validationRule[0]['DEFAULT_REQUIRED']){
                    $isvalidValue = !(empty($expenseRow[$key]) || empty($expenseRow[$key]['ID']));
                }
                $validations[$key] = [
                    "isValid" => $isvalidValue,
                    "isDisabled" => $validationRule[0]['DEFAULT_DISABLE'],
                    "isRequired" => $validationRule[0]['DEFAULT_REQUIRED'],
                    "messages" => $validationRule[0]['DEFAULT_REQUIRED'] ? (object)['required' => 'This field is required'] : (object)[],
                ];
            }
        }
    }
    $expenseRow['IN_EXPENSE_VALIDATION'] =  base64_encode(json_encode($validations));
    return $expenseRow;
}

function getFileDataLink($apiUrl, $requestId, $fileId) {
    $query  = "SELECT * ";
    $query .= "FROM media ";
    $query .= "WHERE id = " . $fileId;

    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query)) ?? [];
    return empty($response) ? '#' : "requests/" . $requestId . "/files/" . rawurlencode($response[0]['name']) . '.pdf';
}




class LocalApi
{
    private function getClient()
    {
        return new Client([
            'curl' => [CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0],
            'allow_redirects' => false,
            'cookies' => true,
            'verify' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    public static function request($method, $url, $params = [], $data = null)
    {
        $queryString = http_build_query($params);
        $queryString = urldecode($queryString);
        $url = getenv('HOST_URL') . "/api/1.0/{$url}?{$queryString}";
        if ($data) {
            $request = new Request($method, $url, [], json_encode($data));
        } else {
            $request = new Request($method, $url);
        }


        try {
            $response = self::getClient()->send($request);
            $content = $response->getBody()->getContents();
            return json_decode($content);
        } catch (Exception $e) {
            return false;
            throw $e;
        }
    }

    public static function getFile($idFile)
    {
        $client = new Client([
            'headers' => ['Authorization' => 'Bearer ' . getenv('API_TOKEN')]
        ]);
        $responseFile = $client->request('GET', getenv('API_HOST') . '/files/' . $idFile . '/contents');
        //return $client->request('GET', getenv('API_HOST') . '/files/' . $idFile . '/contents');

        return $responseFile->getBody()->getContents();
    }

    public static function uploadFileToRequest($requestId, $fileName)
    {
        try {
            $client = new Client(['verify' => false]);

            $headers = [
                'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
                'Accept' => 'application/json',
            ];
            $request = $client->request('POST', getenv('API_HOST') . '/requests/' . $requestId . '/files?data_name=' . substr($fileName, 0, -4), [
                'headers' => $headers,
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen('/tmp/' . $fileName, 'r')
                    ],
                ]
            ]);
            $fileUploaded = json_decode($request->getBody()->getContents());
            return $fileUploaded->fileUploadId;
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public static function get($url, $params = [])
    {
        $response = self::request('GET', $url, $params);
        $response = json_encode($response);
        return json_decode($response, true);
    }

    public static function post($url, $data)
    {
        $response = self::request('POST', $url, [], $data);
        return $response;
    }

    public static function delete($url)
    {
        $response = self::request('DELETE', $url, [], $data);
        return $response;
    }

    public static function put($url, $data)
    {
        $response = self::request('PUT', $url, [], $data);
        return $response;
    }
}