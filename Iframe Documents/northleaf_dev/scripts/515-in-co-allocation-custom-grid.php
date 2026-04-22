<?php 
/*  
 *  POC - Custom Grid
 *
 *  
 *  
 */
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
/*function getExpenseCorpProject($collectionId, $apiUrl) 
{
    $query = '';
    $query .= 'SELECT ID.data->>"$.NL_COMPANY_SYSTEM_ID_ACTG" AS ID, ';
    $query .= 'ID.data->>"$.EXPENSE_CORPORATE_LABEL" AS LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ID ';
    $query .= 'WHERE JSON_UNQUOTE(ID.data->"$.NL_CORPPROJ_STATUS") = "Active" ';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}
*/

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
/*function getExpenseAccount($collectionId, $apiUrl) 
{
    $query = '';
    $query .= 'SELECT data->>"$.NL_ACCOUNT_SYSTEM_ID_ACTG" AS ID,';
    $query .= 'data->>"$.ACCOUNT_LABEL" AS LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ACCOUNT ';
    $query .= 'WHERE data->"$.ACCOUNT_STATUS" = "Active" ';
    $query .= 'ORDER BY LABEL';

    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}*/

/*
 * Get activity list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @param (String) $recoverableOption (Recoverable, Non-recoverable)
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
/*function getTeamRouting($collectionId, $apiUrl, $recoverableOption) {
	$query  = '';
	$query .= "SELECT TEAM_ASSET.data->>'$.ASSET_ID' AS ID, ";
	$query .= "TEAM_ASSET.data->>'$.ASSET_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS TEAM_ASSET ";
	$query .= "WHERE TEAM_ASSET.data->>'$.DEAL_STATUS' = 'Active' ";
	$query .= empty($recoverableOption) ? "" : "AND TEAM_ASSET.data->>'$.ASSET_TYPE' = '" . $recoverableOption . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/

/*
 * Get Deal list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 * modify by Jhon Chacolla
 */
/*function getDealList($collectionId, $apiUrl, $assetLabel) {
	$query  = '';
	$query .= "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, ";
	$query .= "data->>'$.DEAL_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS DEAL ";
	$query .= "WHERE DEAL.data->>'$.DEAL_STATUS' = 'Active' ";
	$query .= empty($assetLabel) ? "" : "AND DEAL.data->>'$.DEAL_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/

/*
 * Get Fund Manager list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
/*function getFundManagerList($collectionId, $apiUrl, $assetLabel) {
	$query  = '';
	$query .= "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, ";
	$query .= "data->>'$.FUND_MANAGER_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS FUND_MANAGER ";
	$query .= "WHERE FUND_MANAGER.data->>'$.FUND_MANAGER_STATUS' = 'Active' ";
	$query .= empty($assetLabel) ? "" : "AND FUND_MANAGER.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/

/*
 * Get Corp Entity list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
/*function getCorpEntityList($collectionId, $apiUrl) {
	$query  = '';
	$query .= "SELECT data->>'$.NL_COMPANY_SYSTEM_ID_ACTG' AS ID, ";
	$query .= "data->>'$.EXPENSE_CORPORATE_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS CORP ";
	$query .= "WHERE CORP.data->>'$.EXPENSE_CORPORATE_STATUS' = 'Active' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/

/*
 * Get activity list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @param (String) $recoverableOption Default 'Recoverable' (Recoverable, Non-recoverable)
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
/*function getActivityList($collectionId, $apiUrl, $recoverableOption = 'Recoverable') {
	$query  = '';
	$query .= "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID, ";
	$query .= "data->>'$.ACTIVITY_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS ACTIVITY ";
	$query .= "WHERE ACTIVITY.data->>'$.ACTIVITY_STATUS' = 'Active' ";
	$query .= "AND ACTIVITY.data->>'$.ACTIVITY_TYPE' IN ('" . $recoverableOption . "','Both') ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
/*function getExpenseDefault($collectionId, $apiUrl) 
{
	$query  = "";
	$query .= "SELECT data->>'$.DEFAULT_REQUIRED' AS DEFAULT_REQUIRED, ";
	$query .= "data->>'$.DEFAULT_ID' AS DEFAULT_ID, ";
	$query .= "data->>'$.DEFAULT_TYPE' AS DEFAULT_TYPE, ";
	$query .= "data->>'$.DEFAULT_LABEL' AS DEFAULT_LABEL, ";
	$query .= "data->>'$.DEFAULT_STATUS' AS DEFAULT_STATUS, ";
	$query .= "data->>'$.DEFAULT_DISABLE' AS DEFAULT_DISABLE, ";
	$query .= "data->>'$.DEFAULT_VARIABLE' AS DEFAULT_VARIABLE ";
	$query .= "FROM collection_" . $collectionId . " ";
	$query .= "WHERE data->>'$.DEFAULT_STATUS' = 'Active'";

    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

	$businessRules = ['RECOVERABLE' => [], 'NON_RECOVERABLE' => []];
	foreach($response as $key => $value) {
		$value['DEFAULT_DISABLE'] = filter_var($value['DEFAULT_DISABLE'], FILTER_VALIDATE_BOOLEAN);
		if($value['DEFAULT_TYPE'] == 'Recoverable') {
			$businessRules['RECOVERABLE'][$value['DEFAULT_VARIABLE']] = $value;
		} else {
			$businessRules['NON_RECOVERABLE'][$value['DEFAULT_VARIABLE']] = $value;
		}
	}

    return $businessRules;
}*/

$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');

$requestId    = $data["IN_REQUEST_ID"];
$teamID       = $data["IN_TEAM_ID"];//'PE';
if($teamID == "CORPH"){
	$teamID = "";
}

$userID = $data['_user'];


//$mandateList  = getMandateList(getCollectionId('IN_EXPENSE_MANDATES', $apiUrl), $apiUrl, $teamID);
$querySubmitter = "SELECT json_extract(DEP.data, '$.SUBMITTER_OFFICE.NL_OFFICE_SYSTEM_ID_DB') as OFFICE_ID,
				json_extract(DEP.data, '$.SUBMITTER_OFFICE.OFFICE_LABEL') as OFFICE_NAME,
				json_extract(DEP.data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_ACTG') as DEPARMENT_ID,
				json_extract(DEP.data, '$.SUBMITTER_DEPARTMENT.DEPARTMENT_LABEL') as DEPARMENT_NAME
				FROM collection_" . getCollectionId('IN_SUBMITTER_DEPARTMENT', $apiUrl) . " AS DEP 
				WHERE json_extract(DEP.data, '$.SUBMITTER.id') = " . $userID;
$depAndOfficeData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySubmitter));

//$enabledRows  = getEnabledRows($apiUrl, $requestId, $teamID);
//$enabledRows  = transformData($enabledRows,$depAndOfficeData);
//$disabledRows = getDisabledRows($apiUrl, $requestId, $teamID);
//$disabledRows = transformData($disabledRows,false);

$viewMode     = 'false';

$totalPretax = 0;
$totalTax    = 0;
$totalTotal  = 0;

/*foreach($enabledRows as $row){
	$totalPretax += $row['IN_EXPENSE_PRETAX_AMOUNT'];
	$totalTax    += $row['IN_EXPENSE_HST'];
	$totalTotal  += $row['IN_EXPENSE_TOTAL'];
}*/


$discrepancyAmount = $data['IN_INVOICE_DISCREPANCY'];
$isDiscrepancyMode = (isset($data['IN_IS_DISCREPANCY']) AND $data['IN_IS_DISCREPANCY'] ) ? "true" : "false";
// Clean amounts data
$invoicePreTax = empty($data["IN_INVOICE_PRE_TAX"]) ? 0 : stringToFloat($data["IN_INVOICE_PRE_TAX"]);
$invoiceTaxTotal = empty($data["IN_INVOICE_TAX_TOTAL"]) ? 0 : stringToFloat($data["IN_INVOICE_TAX_TOTAL"]);
$invoiceTotal = empty($data["IN_INVOICE_TOTAL"]) ? 0 : stringToFloat($data["IN_INVOICE_TOTAL"]);
$invoicePreTaxPercentage = stringToFloat($data["IN_INVOICE_PRE_TAX_PERCENTAGE"]);
$invoiceTaxTotalPercentage = stringToFloat($data["IN_INVOICE_TAX_TOTAL_PERCENTAGE"]);
$invoiceTotalPercentage = stringToFloat($data["IN_INVOICE_TOTAL_PERCENTAGE"]);
$invoiceCurrency = (empty($data['IN_INVOICE_CURRENCY']) || $data['IN_INVOICE_CURRENCY'] == 'undefined'|| $data['IN_INVOICE_CURRENCY'] == 'null') ? 'USD' :  $data['IN_INVOICE_CURRENCY'];
$viewMode = $data["IN_CUSTOME_TABLE_VIEW_MODE"] ?? 'false';
$requestId = $data["IN_REQUEST_ID"];
$inTotalPreTaxAmountInitSubPe = stringToFloat($data['IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_PE']);
$inTotalHstInitSubPe = stringToFloat($data['IN_TOTAL_HST_INIT_SUB_PE']);
$inTotalTotalInitSubPe = stringToFloat($data['IN_TOTAL_TOTAL_INIT_SUB_PE']);
$inTotalPercentageTotalInitSubPe = stringToFloat($data['IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_PE']);
$inInvoicePreTaxPercentageInitSubPe  = stringToFloat($data['IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_PE']);
$inInvoiceTaxTotalPercentageInitSubPe  = stringToFloat($data['IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_PE']);
$inInvoiceTotalPercentageInitSubPe  = stringToFloat($data['IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_PE']);

$IN_IS_CO01_COH01_TASK = (!empty($data['IN_IS_CO01_COH01_TASK']) && $data['IN_IS_CO01_COH01_TASK']) ? "true" : "false";

//$teamRoutingRecList   = getTeamRouting(getCollectionId('IN_ASSET_CLASS', $apiUrl), $apiUrl, 'Recoverable');
//$teamRoutingNoRecList = getTeamRouting(getCollectionId('IN_ASSET_CLASS', $apiUrl), $apiUrl, 'Non-recoverable');
//$accountList          = getExpenseAccount(getCollectionId('IN_EXPENSE_ACCOUNT', $apiUrl), $apiUrl);
//$corpProjList         = getExpenseCorpProject(getCollectionId('IN_EXPENSE_CORP_PROJ', $apiUrl), $apiUrl);
$corpProjList         = [];
$accountList          = [];
$teamRoutingRecList   = [];
$teamRoutingNoRecList = [];
$dealList             = [];
$fundManagerList      = [];
$activityRecList      = [];
$activityNoRecList    = [];
$expenseDefaultRules  = [];

$corpEntityList       = [];
$corpProjList         = [];
$departmentList       = [];
$officeList           = [];
$mandateList          = [];
$enabledRows          = [];
$disabledRows         = [];

//$teamRoutingFullList = [];
//$teamRoutingFullLabel = [];


$querySubmitter = "SELECT json_extract(DEP.data, '$.SUBMITTER_OFFICE.NL_OFFICE_SYSTEM_ID_DB') as OFFICE_ID,
				json_extract(DEP.data, '$.SUBMITTER_OFFICE.OFFICE_LABEL') as OFFICE_NAME,
				json_extract(DEP.data, '$.SUBMITTER_DEPARTMENT.NL_DEPARTMENT_SYSTEM_ID_ACTG') as DEPARMENT_ID,
				json_extract(DEP.data, '$.SUBMITTER_DEPARTMENT.DEPARTMENT_LABEL') as DEPARMENT_NAME
				FROM collection_" . getCollectionId('IN_SUBMITTER_DEPARTMENT', $apiUrl) . " AS DEP 
				WHERE json_extract(DEP.data, '$.SUBMITTER.id') = " . $userID;
$depAndOfficeData = callApiUrlGuzzle($apiUrl, "POST", encodeSql($querySubmitter));




$query  = "SELECT TEAM_ASSET.data->>'$.ASSET_ID' AS ID, 
		TEAM_ASSET.data->>'$.ASSET_LABEL' AS LABEL,
		TEAM_ASSET.data->>'$.ASSET_TYPE' as TYPE
		FROM collection_" . getCollectionId('IN_ASSET_CLASS', $apiUrl) . " AS TEAM_ASSET 
		WHERE TEAM_ASSET.data->>'$.DEAL_STATUS' = 'Active' 
		-- AND TEAM_ASSET.data->>'$.ASSET_TYPE' = '" . $recoverableOption . "' 
		ORDER BY LABEL ASC";
$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
$response = array_values($response);
$teamRoutingRecList = array_filter($response, function($v, $k) {
	return $v['TYPE'] == 'Recoverable';
}, ARRAY_FILTER_USE_BOTH);
$teamRoutingNoRecList = array_filter($response, function($v, $k) {
	return $v['TYPE'] == 'Non-recoverable';
}, ARRAY_FILTER_USE_BOTH);
//$teamRoutingFullLabel = array_column($teamRoutingFullList, 'LABEL');
$mandateList     = [];
$fundManagerList = [];
$dealList        = [];
//$teamRoutingFullList = $response;

$dealQuery  = "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, 
		data->>'$.DEAL_LABEL' AS LABEL,
		DEAL.data->>'$.DEAL_ASSETCLASS.ASSET_LABEL' as ASSET
		FROM collection_" . getCollectionId('IN_DEAL', $apiUrl) . " AS DEAL 
		WHERE DEAL.data->>'$.DEAL_STATUS' = 'Active' 
		-- AND DEAL.data->>'$.DEAL_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' 
		ORDER BY LABEL ASC";
$resDeal = callApiUrlGuzzle($apiUrl, "POST", encodeSql($dealQuery));
$dealInfra = array_filter($resDeal, function($v, $k) {
	return $v['ASSET'] == 'Infrastructure';
}, ARRAY_FILTER_USE_BOTH);
$dealList['Infrastructure'] = array_values($dealInfra);
$dealCorp = array_filter($resDeal, function($v, $k) {
	return $v['ASSET'] == 'Corporate';
}, ARRAY_FILTER_USE_BOTH);
$dealList['Corporate'] = array_values($dealCorp);
$dealPC = array_filter($resDeal, function($v, $k) {
	return $v['ASSET'] == 'Private Credit';
}, ARRAY_FILTER_USE_BOTH);
$dealList['Private Credit'] = array_values($dealPC);
$dealPE = array_filter($resDeal, function($v, $k) {
	return $v['ASSET'] == 'Private Equity';
}, ARRAY_FILTER_USE_BOTH);
$dealList['Private Equity'] = array_values($dealPE);


$fundManQuery  = "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, 
		data->>'$.FUND_MANAGER_LABEL' AS LABEL,
		FUND_MANAGER.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' as ASSET
		FROM collection_" . getCollectionId('IN_EXPENSE_FUND_MANAGER', $apiUrl) . " AS FUND_MANAGER 
		WHERE FUND_MANAGER.data->>'$.FUND_MANAGER_STATUS' = 'Active' 
		-- AND FUND_MANAGER.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' 
		ORDER BY LABEL ASC";
$resFundMan = callApiUrlGuzzle($apiUrl, "POST", encodeSql($fundManQuery));
$fundManInfra = array_filter($resFundMan, function($v, $k) {
	return $v['ASSET'] == 'Infrastructure';
}, ARRAY_FILTER_USE_BOTH);
$fundManagerList['Infrastructure'] = array_values($fundManInfra);
$fundManCorp = array_filter($resFundMan, function($v, $k) {
	return $v['ASSET'] == 'Corporate';
}, ARRAY_FILTER_USE_BOTH);
$fundManagerList['Corporate'] = array_values($fundManCorp);
$fundManPC = array_filter($resFundMan, function($v, $k) {
	return $v['ASSET'] == 'Private Credit';
}, ARRAY_FILTER_USE_BOTH);
$fundManagerList['Private Credit'] = array_values($fundManPC);
$fundManPE = array_filter($resFundMan, function($v, $k) {
	return $v['ASSET'] == 'Private Equity';
}, ARRAY_FILTER_USE_BOTH);
$fundManagerList['Private Equity'] = array_values($fundManPE);

$expenseQuery  = "SELECT data->>'$.MANDATE_SYSTEM_ID_ACTG' AS ID, 
		data->>'$.MANDATE_LABEL' AS LABEL
		-- ,MANDATE.data->>'$.MANDATE_ASSETCLASS' as ASSET
		FROM collection_" . getCollectionId('IN_EXPENSE_MANDATES', $apiUrl) . " AS MANDATE 
		WHERE MANDATE.data->>'$.MANDATE_STATUS' = 'Active' 
		-- AND MANDATE.data->>'$.MANDATE_ASSETCLASS' = '" . $assetLabel . "' 
		ORDER BY LABEL ASC";
$resMandates = callApiUrlGuzzle($apiUrl, "POST", encodeSql($expenseQuery));
/*$mandateInfra = array_filter($resMandates, function($v, $k) {
	return $v['ASSET'] == 'INFRA';
}, ARRAY_FILTER_USE_BOTH);
$mandateList['Infrastructure'] = array_values($mandateInfra);*/
$mandateList['Infrastructure'] = array_values($resMandates); /// new requierment show all mandates 
/*$mandateCorp = array_filter($resMandates, function($v, $k) {
	return $v['ASSET'] == 'CORP';
}, ARRAY_FILTER_USE_BOTH);
$mandateList['Corporate'] = array_values($mandateCorp);*/
$mandateList['Corporate'] = array_values($resMandates); /// new requierment show all mandates 
/*$mandatePC = array_filter($resMandates, function($v, $k) {
	return $v['ASSET'] == 'PC';
}, ARRAY_FILTER_USE_BOTH);
$mandateList['Private Credit'] = array_values($mandatePC);*/
$mandateList['Private Credit'] = array_values($resMandates); /// new requierment show all mandates 
/*$mandatePE = array_filter($resMandates, function($v, $k) {
	return $v['ASSET'] == 'PE';
}, ARRAY_FILTER_USE_BOTH);
$mandateList['Private Equity'] = array_values($mandatePE);*/
$mandateList['Private Equity'] = array_values($resMandates); /// new requierment show all mandates 


$teamRoutingRecList = array_values($teamRoutingRecList);
$teamRoutingNoRecList = array_values($teamRoutingNoRecList);
//$teamRoutingFullList = array_values($teamRoutingFullList);






/*$queryDept = "SELECT data->>'$.NL_DEPARTMENT_SYSTEM_ID_ACTG' AS ID, data->>'$.DEPARTMENT_LABEL' AS LABEL
				FROM collection_" . getCollectionId('IN_EXPENSE_DEPARTMENT', $apiUrl) . " AS DEP 
				WHERE DEP.data->>'$.DEPARTMENT_STATUS' = 'Active' 
				ORDER BY LABEL ASC";
$departmentList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryDept));
*/
/*$queryOffice = "SELECT data->>'$.NL_OFFICE_SYSTEM_ID_DB' AS ID, data->>'$.OFFICE_LABEL' AS LABEL
				FROM collection_" . getCollectionId('IN_EXPENSE_OFFICE', $apiUrl) . " AS OFFICE 
				WHERE OFFICE.data->>'$.OFFICE_STATUS' = 'Active' 
				ORDER BY LABEL ASC";
$officeList = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryOffice));
*/

$copyCOmpanyID = isset($data['COPY_TEMP_COMPANY_ID']) ? $data['COPY_TEMP_COMPANY_ID'] : 0;
$summaryTotal = $data['IN_SUMMARY_TOTAL_GRID'];

/*$invoicePreTax   = $summaryTotal['PC']['IN_EXPENSE_PRETAX_AMOUNT'] + $summaryTotal['PE']['IN_EXPENSE_PRETAX_AMOUNT'] + $summaryTotal['INFRA']['IN_EXPENSE_PRETAX_AMOUNT'];
$invoiceTaxTotal = $summaryTotal['PC']['IN_EXPENSE_HST'] + $summaryTotal['PE']['IN_EXPENSE_HST'] + $summaryTotal['INFRA']['IN_EXPENSE_HST'];
$invoiceTotal    = $summaryTotal['PC']['IN_EXPENSE_TOTAL'] + $summaryTotal['PE']['IN_EXPENSE_TOTAL'] + $summaryTotal['INFRA']['IN_EXPENSE_TOTAL'];

if($teamID != 'CORP'){
	$invoicePreTax   += $summaryTotal['CORP']['IN_EXPENSE_PRETAX_AMOUNT'];
	$invoiceTaxTotal += $summaryTotal['CORP']['IN_EXPENSE_HST'];
	$invoiceTotal    += $summaryTotal['CORP']['IN_EXPENSE_TOTAL'];
}*/

$summaryTotal = $summaryTotal[$teamID];
$totalPretax = empty($summaryTotal["IN_EXPENSE_PRETAX_AMOUNT"]) ? 0 : stringToFloat($summaryTotal["IN_EXPENSE_PRETAX_AMOUNT"]);
$totalTax    = empty($summaryTotal["IN_EXPENSE_HST"]) ? 0 : stringToFloat($summaryTotal["IN_EXPENSE_HST"]);
$totalTotal  = empty($summaryTotal["IN_EXPENSE_TOTAL"]) ? 0 : stringToFloat($summaryTotal["IN_EXPENSE_TOTAL"]);

if($teamID == 'PE')
	$gridTitle = 'Invoice Private Equity Line Items';
if($teamID == 'INFRA')
	$gridTitle = 'Invoice Infrastructure Line Items';
if($teamID == 'PC')
	$gridTitle = 'Invoice Private Credit Line Items';
if($teamID == 'CORP')
	$gridTitle = 'Corporate Invoice Line Items';

$theadHtml = '<thead class="thead-light">
				<tr class="" style="background: white;">
					<th></th>
					__TD--TD__
					<th style="min-width:300px"></th>
					<th style="min-width:300px"></th>
					<th style="min-width:300px"></th>
					__TH--TH__
					<th></th>
					<th style="min-width:300px">
					</th>
					<th style="min-width:300px">
					</th>
					<th style="min-width:300px">
					</th>
					<th style="min-width:300px"></th>
					<th style="min-width:300px"></th>
					<th style="min-width:300px"></th>
					<th colspan="3" style="min-width:300px"></th>
				</tr>
				<tr class="head-table">
					__TD--TD__
					<th>#</th>
					<th style="min-width:300px">Expense Description</th>
					<th style="min-width:300px">Non-Rec / Rec</th>
					<th style="min-width:300px">Team Routing/Asset Class</th>
					<th style="min-width:300px">Account</th>
					<th style="min-width:150px">Pre Tax Amount</th>
					<th style="min-width:150px">Tax Amount</th>
					<th style="min-width:150px">Total Amount</th>
					<th style="min-width:150px">% of Total Amount</th>
					<th style="min-width:300px">Corporate Project</th>
					<th style="min-width:300px">Deal</th>
					<th style="min-width:300px">Fund Manager</th>
					<th style="min-width:300px">Mandate</th>
					<th style="min-width:300px">Activity</th>
					<th style="min-width:300px">Corporate Entity</th>
					<th style="min-width:300px">Department</th>
					<th style="min-width:300px">Office</th>
					<th style="min-width:300px">GL Code</th>
				</tr>
			</thead>';
$html = '<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">	
	<!-- Add Bootstrap 4 CSS y BootstrapVue CSS from CDN -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-vue@2.21.2/dist/bootstrap-vue.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<!-- Add Vue Multiselect from CDN -->
	<script src="https://unpkg.com/vue-multiselect@2.1.6"></script>
	<link rel="stylesheet" href="https://unpkg.com/vue-multiselect@2.1.6/dist/vue-multiselect.min.css">
	<!-- Add -v-mask from CDN -->
	<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/v-mask/dist/v-mask.min.js"></script>
	<script src="https://unpkg.com/axios/dist/axios.min.js"></script>

	<style>
		.multiselect__content-wrapper {
			width: auto !important;
			min-width: 100% !important;
			position: absolute;
			display: block;
			background: #fff;
			max-height: 240px;
			overflow: auto;
			border: 1px solid #e8e8e8;
			border-top: none;
			border-bottom-left-radius: 5px;
			border-bottom-right-radius: 5px;
			z-index: 50;
			-webkit-overflow-scrolling: touch
		}
		.head-table {
			background-color: #711425;
			color: #FFF;
		}
		.sub-head-table {
			background-color: #f3f3f3 !important;
		}
		.matching.different {
			background-color: #ffe0e0;
		}
		.matching.equal {
			background-color: #dcfcd4;	
		}
        .pm-form-select {
            border: 1px solid #dee2e6;
            border-radius: 0.45em;
            padding: 0.45em;
            width: 100%;
            background-color: #fff;
        }
		.pm-bg-primary {
			background-color: #711425 !important;
		}
		.pm-text-white {
			color:#fff !important;
		}
		.pm-text-primary {
			color:#711425;
		}
		.multiselect.is-invalid-field>.multiselect__tags {
			border: 1px solid #E50130;
		}
		[v-cloak] { display: none; }





				
		.table-responsive {
  position: relative;
  width:100%;
  z-index: 1;
  margin: auto;
  overflow: auto;
  height: 500px;
}
.table-responsive table {
  width: 100%;
  min-width: 1280px;
  margin: auto;
  border-collapse: separate;
  border-spacing: 0;
}
.table-wrap {
  position: relative;
}
/*.table-responsive th,
.table-responsive td {
  padding: 5px 10px;
  border: 1px solid #000;
  vertical-align: top;
  z-index : 999999 !important;
}*/

.table>thead {
    vertical-align: bottom;
	z-index : 999999 !important;
}

.table-responsive thead th {
  position: -webkit-sticky;
  position: sticky;
  top: 0;
}
.table-responsive thead:first-child {
  position: -webkit-sticky;
  position: sticky;
  top: 0;
}


/* safari and ios need the tfoot itself to be position:sticky also */
.table-responsive tfoot,
.table-responsive tfoot th,
.table-responsive tfoot td {
  position: -webkit-sticky;
  position: sticky;
  bottom: 0;
  z-index:4;
  background: white;
}

a:focus {
  background: red;
} /* testing links*/

/*th:first-child {
  position: -webkit-sticky;
  position: sticky;
  left: 0;
  z-index: 2;
  background: #ccc;
}*/
thead th:first-child,
tfoot th:first-child {
  z-index: 9999;
}


	</style>
</head>
<body>
	<div id="app">
		<div v-cloak>
			<!-- Componente BootstrapVue -->
			<div>
				<div class="table-responsive" id="iframe-table-container" style="max-height: 800px;" v-if="isValidValue(disabledItems) && disabledItems.length>0">
					<table class="table table-bordered table-striped w-auto mb-4" head-variant="light" bordered sticky-header>';	
$theadHtmlT = str_replace('__TH--TH__', '<th :class="!'.$IN_IS_CO01_COH01_TASK.' ? \'pm-bg-primary pm-text-white text-center\' : \'pm-text-white text-center\'"><span v-show="!'.$IN_IS_CO01_COH01_TASK.'" class="IN_INVOICE_CURRENCY_label">{{ IN_INVOICE_CURRENCY }}</span></th><th class="pm-bg-primary pm-text-white text-center">{{ formatNumber(IN_INVOICE_PRE_TAX) }}</th><th class="pm-bg-primary pm-text-white text-center">{{ formatNumber(IN_INVOICE_TAX_TOTAL) }}</th><th class="pm-bg-primary pm-text-white text-center">{{ formatNumber(IN_INVOICE_TOTAL) }}</th>', $theadHtml);									
$html .= str_replace('__TD--TD__', '', $theadHtmlT);
$html .=				'<tbody>							
							<tr v-for="(disabledItem, index) in disabledItems" :key="disabledItems.IN_EXPENSE_ROW_NUMBER" 
								:class="{\'table-danger\': 
								!(disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid &&
								disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.isValid &&
								disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.isValid
								)
								}"
							>
								<td>{{ index + 1 }}</td>
								<td style="min-width:300px">';
									if($IN_IS_CO01_COH01_TASK == "true") {
										$html.= '<|textarea 
											v-model="disabledItem.IN_EXPENSE_DESCRIPTION"
											:class="{\'form-control\':true, \'is-invalid\': !(disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid) }"
											name="" 
											rows="1" 
											cols="10"
											:maxlength="descriptionLimit"
											@input="onChangeDescription(index, disabledItem.IN_EXPENSE_DESCRIPTION)"
										>
										</|textarea>
										<p v-if="!(disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid)">
											<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.messages"></error-message>
										</p>';									
									} else {
										$html.= '{{disabledItem.IN_EXPENSE_DESCRIPTION}}';
									}									
								$html.= '</td>
								<td style="min-width:300px">
									{{disabledItem.IN_EXPENSE_NR}}
								</td>
								<td style="min-width:300px">
									<p v-if="disabledItem.IN_EXPENSE_TEAM_ROUTING && disabledItem.IN_EXPENSE_TEAM_ROUTING.LABEL">{{disabledItem.IN_EXPENSE_TEAM_ROUTING.LABEL}}</p>
								</td>
								<td style="min-width:300px">';
									if($teamID == 'CORP' OR $data["IN_TEAM_ID"] == 'CORPH'){
										$html.= '<multiselect 
													v-model="disabledItem.IN_EXPENSE_ACCOUNT"
													:options="IN_EXPENSE_ACCOUNT_VALUES"
													:searchable="true" 
													:close-on-select="true" 
													placeholder="Please select" 
													select-label="Select" 
													label="LABEL" 
													track-by="LABEL"
													@input="onChangeAccount(\'disabled\',index, $event)"
													:disabled="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isDisabled"
													:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid}"
												>
												</multiselect>
												<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid">
													<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.messages"></error-message>
												</p>';
									}
									else{
										$html.= '<p v-if="disabledItem.IN_EXPENSE_ACCOUNT && disabledItem.IN_EXPENSE_ACCOUNT.LABEL">{{disabledItem.IN_EXPENSE_ACCOUNT.LABEL}}</p>';
									}	
$html .='						</td>
								<td style=" text-align: right; min-width:150px;" class="text-right">
									{{formatNumber(disabledItem.IN_EXPENSE_PRETAX_AMOUNT) }}
								</td>
								<td style=" text-align: right; min-width:150px;">
									{{formatNumber(disabledItem.IN_EXPENSE_HST) }}
								</td>
								<td style=" text-align: right; min-width: 150px;">
									{{formatNumber(disabledItem.IN_EXPENSE_TOTAL) }}
								</td>
								<td style=" text-align: right; min-width: 150px;">
									{{formatPercentageNumber(disabledItem.IN_EXPENSE_PERCENTAGE_TOTAL) }}
								</td>
								<td style="min-width:300px">';
if($teamID == 'CORP' OR $data["IN_TEAM_ID"] == 'CORPH'){
	$html .='   					<!-- Corp Proj Dropdown -->
									<multiselect 
										v-model="disabledItem.IN_EXPENSE_CORP_PROJ"
										:options="corpProjList"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeProj(\'disabled\',index, $event)"
										:disabled="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isDisabled"
										:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isValid}"
									>
									</multiselect>
									<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isValid">
										<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.messages"></error-message>
									</p>';
}
else{
	$html.= '<p v-if="disabledItem.IN_EXPENSE_CORP_PROJ && disabledItem.IN_EXPENSE_CORP_PROJ.LABEL">{{disabledItem.IN_EXPENSE_CORP_PROJ.LABEL}}</p>';
}	
$html .='						</td>
								<td style="min-width:300px">
									<p v-if="disabledItem.IN_EXPENSE_PROJECT_DEAL && disabledItem.IN_EXPENSE_PROJECT_DEAL.LABEL">{{disabledItem.IN_EXPENSE_PROJECT_DEAL.LABEL}}</p>
								</td>
								<td style="min-width:300px">
									<p v-if="disabledItem.IN_EXPENSE_FUND_MANAGER && disabledItem.IN_EXPENSE_FUND_MANAGER.LABEL">{{disabledItem.IN_EXPENSE_FUND_MANAGER.LABEL}}</p>
								</td>
								<td style="min-width:300px">';
if($data["IN_TEAM_ID"] == 'CORPH'){
	$html .=' 						<!-- Mandates Dropdown -->
									<multiselect 
										v-model="disabledItem.IN_EXPENSE_MANDATE"
										:options="(disabledItem.IN_EXPENSE_TEAM_ROUTING && disabledItem.IN_EXPENSE_TEAM_ROUTING.LABEL)? mandateList[disabledItem.IN_EXPENSE_TEAM_ROUTING.LABEL] : []"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										:disabled="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isDisabled"
										:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid}"
										@input="updateMandateDisabled(index, $event)"
									>
									</multiselect>
									<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid">
										<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages"></error-message>
									</p>';
}
else{
	$html.= '<p v-if="disabledItem.IN_EXPENSE_MANDATE && disabledItem.IN_EXPENSE_MANDATE.LABEL">{{disabledItem.IN_EXPENSE_MANDATE.LABEL}}</p>';
}
$html .='						</td>
								<td style="min-width:300px">';
if($teamID == 'CORP' OR $data["IN_TEAM_ID"] == 'CORPH'){
	$html .='						<!-- Activity Dropdown -->
									<multiselect 
										v-model="disabledItem.IN_EXPENSE_ACTIVITY"
										:options="disabledItem.IN_EXPENSE_NR==\'Recoverable\' ? activityRecList : activityNoRecList"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeActivity(\'disabled\',index, $event)"
										:disabled="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isDisabled"
										:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid}"
									>
									</multiselect>
									<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid">
										<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.messages"></error-message>
									</p>';
}
else{
	$html.= '<p v-if="disabledItem.IN_EXPENSE_ACTIVITY && disabledItem.IN_EXPENSE_ACTIVITY.LABEL">{{disabledItem.IN_EXPENSE_ACTIVITY.LABEL}}</p>';
}
$html .='						</td>
								<td style="min-width:300px">';
if($data["IN_TEAM_ID"] == 'CORPH'){
	$html .='						<!-- Corp Entity Dropdown -->
									<multiselect 
										v-model="disabledItem.IN_EXPENSE_CORP_ENTITY"
										:options="IN_EXPENSE_CORP_ENTITY_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeCorpEntity(index, $event)"
										:disabled="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isDisabled"
										:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isValid}"
									>
									</multiselect>
									<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isValid">
										<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.messages"></error-message>
									</p>';
}
else{
	$html.= '<p v-if="disabledItem.IN_EXPENSE_CORP_ENTITY && disabledItem.IN_EXPENSE_CORP_ENTITY.LABEL">{{disabledItem.IN_EXPENSE_CORP_ENTITY.LABEL}}</p>';
}
$html .='						</td>
								<td style="min-width:300px">';
if($teamID == 'CORP' OR $data["IN_TEAM_ID"] == 'CORPH'){
	$html .='						<!-- Department Dropdown -->
									<multiselect 
										v-model="disabledItem.IN_EXPENSE_DEPARTMENT"
										:options="IN_EXPENSE_DEPARTMENT_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeDepartmet(\'disabled\',index, $event)"
										:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_DEPARTMENT != \'\'}"
									>
									</multiselect>
									<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.isValid">
										<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.messages"></error-message>
									</p>';
}
else{
	$html.= '<p v-if="disabledItem.IN_EXPENSE_DEPARTMENT && disabledItem.IN_EXPENSE_DEPARTMENT.LABEL">{{disabledItem.IN_EXPENSE_DEPARTMENT.LABEL}}</p>';
}
$html .='						</td>
								<td style="min-width:300px">';
if($teamID == 'CORP' OR $data["IN_TEAM_ID"] == 'CORPH'){
	$html .='						<!-- Office Dropdown -->
									<multiselect 
										v-model="disabledItem.IN_EXPENSE_OFFICE"
										:options="IN_EXPENSE_OFIICE_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeOffice(\'disabled\',index, $event)"
										:class="{\'is-invalid-field\': !disabledItem.IN_EXPENSE_OFFICE != \'\'}"
									>
									</multiselect>
									<p v-if="!disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.isValid">
										<error-message :messageslist="disabledItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.messages"></error-message>
									</p>';
}
else{
	$html.= '<p v-if="disabledItem.IN_EXPENSE_OFFICE && disabledItem.IN_EXPENSE_OFFICE.LABEL">{{disabledItem.IN_EXPENSE_OFFICE.LABEL}}</p>';
}
$html .='						</td>
								<td style=" text-align: right; min-width:150px;">
									{{disabledItem.IN_EXPENSE_GL_CODE }}
								</td>
							</tr>
						</tbody>';
//if($data["IN_TEAM_ID"] == ''){
	$html .='			<tfoot class="tfoot-light">
							<tr>
								<td colspan="2" v-if="!isDiscrepancyMode && false"></td>
								<td colspan="4"></td>
								<td><strong>Total</strong></td>
								<td class="matching" 
								
									v-bind:class="formatNumber(IN_INVOICE_PRE_TAX) == formatNumber(IN_INVOICE_PRE_TAX) ? \'equal\' : \'different\'"
									style="text-align: right;">
									{{ formatNumber(sumPreTaxDis) }} 
								</td>
								<td class="matching" 
									v-bind:class="formatNumber(IN_INVOICE_TAX_TOTAL) == formatNumber(IN_INVOICE_TAX_TOTAL) ? \'equal\' : \'different\'"
									style="text-align: right;">
									{{ formatNumber(sumTaxDis) }}
								</td>
								<td class="matching" 
									v-bind:class="formatNumber(IN_INVOICE_TOTAL) == formatNumber(IN_INVOICE_TOTAL) ? \'equal\' : \'different\'"
									style="text-align: right;">
									{{ formatNumber(sumTotalDis) }}
									<|input v-show="false" v-model="cleanNumber(IN_TOTAL_TOTAL)" id="iframe-totalFinalAmount" class="form-control">
								</td>';
if($data["IN_TEAM_ID"] == ''){
	$html .='					<td :class="{matching:true, equal:true}"
									style="text-align: right;">
									{{ formatPercentageNumber(100) }}
								</td>';
}						
$html .='						<td colspan="9"></td>
							</tr>
						</tfoot>';
$html .='			</table>
				</div>

				<!-- <h2>Team Table</h2> -->
				<h4 class="pm-text-primary" v-if="items.length>0">' . $gridTitle . '</h4>
				<hr class="pm-text-primary">
				<div class="table-responsive" id="iframe-table-container" style="max-height: 800px;" v-if="items.length>0">
						<!-- <p>IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA: {{IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA}}</p>
						<p>IN_TOTAL_HST_INIT_SUB_INFRA: {{IN_TOTAL_HST_INIT_SUB_INFRA}}</p>
						<p>IN_TOTAL_TOTAL_INIT_SUB_INFRA: {{IN_TOTAL_TOTAL_INIT_SUB_INFRA}}</p>
						<p>IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA: {{IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA}}</p>
						<p>IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_INFRA: {{IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_INFRA}}</p>
						<p>IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA: {{IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA}}</p>
						<p>IN_INVOICE_CURRENCY: {{IN_INVOICE_CURRENCY}}</p> -->
					<table class="table table-bordered table-striped w-auto mb-4">';
$theadHtmlT = str_replace('__TH--TH__', '<th :class="!'.$IN_IS_CO01_COH01_TASK.' ? \'pm-bg-primary pm-text-white text-center\' : \'pm-text-white text-center\'"><span v-show="!'.$IN_IS_CO01_COH01_TASK.'" class="IN_INVOICE_CURRENCY_label">{{ IN_INVOICE_CURRENCY }}</span></th><th class="pm-bg-primary pm-text-white text-center">{{ formatNumber(IN_TOTAL_PRE_TAX) }}</th><th class="pm-bg-primary pm-text-white text-center">{{ formatNumber(IN_TOTAL_TAX) }}</th><th class="pm-bg-primary pm-text-white text-center">{{ formatNumber(IN_SUM_TOTAL) }}</th>', $theadHtml);
$html .= str_replace('__TD--TD__', '<th style="min-width:52px" v-if="!isDiscrepancyMode"></th><th style="min-width:52px" v-if="!isDiscrepancyMode"></th>', $theadHtmlT);

$html .=				'<tbody>
							<tr v-for="(item, index) in items" :key="index" 
								:class="{\'table-danger\': 
								!(item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PRETAX_AMOUNT.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_HST.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TOTAL.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PERCENTAGE.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PERCENTAGE_TOTAL.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.isValid &&
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.isValid 
								//&& cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) > 0
								)
								}"
							>
								<td v-if="!isDiscrepancyMode">
									<|button class="btn btn-danger btn-sm" @click="deleteRow(index)"><span class="fa fa-trash" style="font-size:150%;"></|button>
								</td>
								<td v-if="!isDiscrepancyMode">
									<|button class="btn btn-secondary btn-sm" @click="splitRow(index)"><span class="fa fa-code-fork" style="font-size:150%;"></|button>
								</td>
								<td>{{ index + 1 }}</td>
								<td style="min-width:300px">
									<|textarea 
										v-model="item.IN_EXPENSE_DESCRIPTION"
										:class="{\'form-control\':true, \'is-invalid\': !(item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid) }"
										name="" 
										rows="1" 
										cols="10"
										:maxlength="descriptionLimit"
										@input="onChangeDescription(index, item.IN_EXPENSE_DESCRIPTION)"
									>
									</|textarea>
									<p v-if="!(item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid)">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.messages"></error-message>
									</p>
									<!-- {{item.IN_EXPENSE_TEAM_ROW_INDEX}} -->
									<!-- <p class="text-danger">{{item.IN_EXPENSE_CASE_ID}}</p> -->
									<!-- <p class="text-info">{{item.IN_EXPENSE_ROW_ID}}</p> -->
								</td>
								<td style="min-width:300px">
									<!-- NR R Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_NR" 
										:options="IN_EXPENSE_NR_VALUES" 
										:searchable="true" 
										:close-on-select="true" 
										:show-labels="false"
										select-label="Select" 
										placeholder="Please select"
										@input="onChangeRecoverable(index, $event)"
										:disabled="true"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages"></error-message>
									</p>
									<!-- <p>{{item.IN_EXPENSE_VALIDATION}}</p> -->
								</td>
								<td style="min-width:300px">
									<!-- Team Routing -->
									<multiselect 
										v-model="item.IN_EXPENSE_TEAM_ROUTING"
										:options="item.IN_EXPENSE_NR==\'Recoverable\' ? teamRoutingRecList : teamRoutingNoRecList"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeTeamRouting(index, $event)"
										:disabled="true"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.messages"></error-message>
									</p>
									<!-- {{item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING}} -->
								</td>
								<td style="min-width:300px">
									<!-- Account Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_ACCOUNT"
										:options="IN_EXPENSE_ACCOUNT_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeAccount(\'enable\',index, $event)"
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.messages"></error-message>
									</p>
								</td>
								<td style="min-width:150px;">
									<|input 
										type="text" 
										:class="{\'form-control\':true}"
										placeholder="00,000.00" 
										v-model="item.IN_EXPENSE_PRETAX_AMOUNT"
										@focus="selectAllContent"
										@input="onFieldInput(index, \'IN_EXPENSE_PRETAX_AMOUNT\', $event)" 
										@blur="onBlurPreTaxAmount(index, \'IN_EXPENSE_PRETAX_AMOUNT\')"
										@keydown="handleKeyDown(index, \'IN_EXPENSE_PRETAX_AMOUNT\')"
										style="text-align: right;"
										:disabled="isDiscrepancyMode" 
									/>
									<!-- <p class="text-info small">IN_EXPENSE_PRETAX_AMOUNT</p> -->
								</td>
								<td style="min-width:150px;">
									<|input type="text" 
										class="form-control" 
										v-model="item.IN_EXPENSE_HST" 
										@focus="selectAllContent"
										@input="onFieldInput(index, \'IN_EXPENSE_HST\', $event)" 
										@blur="onFieldBlur(index, \'IN_EXPENSE_HST\')"
										@keydown="handleKeyDown(index, \'IN_EXPENSE_HST\')"
										style="text-align: right;"
										disabled
									/>
									<!-- <p class="text-info small">IN_EXPENSE_HST</p> -->
								</td>
								<td style="min-width: 150px;">
									<|input type="text" 
										class="form-control"
										placeholder="00,000.00" 
										v-model="item.IN_EXPENSE_TOTAL" 
										@focus="selectAllContent"
										@input="onFieldInput(index, \'IN_EXPENSE_TOTAL\', $event)" 
										@blur="onBlurTotal(index, \'IN_EXPENSE_TOTAL\')"
										@keydown="handleKeyDown(index, \'IN_EXPENSE_TOTAL\')"
										style="text-align: right;"
										:disabled="isDiscrepancyMode" 
									/>
									<!-- <p class="text-info small">IN_EXPENSE_TOTAL</p> -->
								</td>
								<!-- <td style="min-width: 150px;">
									{{formatNumber(100)}} %
								</td> -->
								<td style="min-width: 150px;">
									<|input 
										type="text" 
										class="form-control" 
										v-model="item.IN_EXPENSE_PERCENTAGE_TOTAL"
										@focus="selectAllContent"
										@input="onFieldInput(index, \'IN_EXPENSE_PERCENTAGE_TOTAL\', $event)"
										@blur="onBlurPercentageTotal(index, \'IN_EXPENSE_PERCENTAGE_TOTAL\')"
										@keydown="handleKeyDown(index, \'IN_EXPENSE_PERCENTAGE_TOTAL\')"
										style="text-align: right;"
										:disabled="isDiscrepancyMode" 
									/>
									<!-- <p class="text-info small">IN_EXPENSE_PERCENTAGE_TOTAL</p> -->
								</td>
								<td style="min-width:300px">
									<!-- Corp Proj Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_CORP_PROJ"
										:options="corpProjList"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeProj(\'enable\',index, $event)"
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ.messages"></error-message>
									</p>
								</td>
								<td style="min-width:300px">
									<!-- Deal Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_PROJECT_DEAL"
										:options="(item.IN_EXPENSE_TEAM_ROUTING && item.IN_EXPENSE_TEAM_ROUTING.LABEL)? dealList[item.IN_EXPENSE_TEAM_ROUTING.LABEL] : []"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										:disabled="true"
										@input="updateDeal(index, $event)"
									>
									</multiselect>
									<!--p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages"></error-message>
									</p-->
									<!-- {{item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL}} -->
								</td>
								<td style="min-width:300px">
									<!-- Fund Manager Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_FUND_MANAGER"
										:options="(item.IN_EXPENSE_TEAM_ROUTING && item.IN_EXPENSE_TEAM_ROUTING.LABEL)? fundManagerList[item.IN_EXPENSE_TEAM_ROUTING.LABEL] : []"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										:disabled="true"
										@input="updateFundManager(index, $event)"
									>
									</multiselect>
									<!--p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages"></error-message>
									</p-->
								</td>
								<td style="min-width:300px">
									<!-- Mandates Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_MANDATE"
										:options="(item.IN_EXPENSE_TEAM_ROUTING && item.IN_EXPENSE_TEAM_ROUTING.LABEL)? mandateList[item.IN_EXPENSE_TEAM_ROUTING.LABEL] : []"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid}"
										@input="updateMandate(index, $event)"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages"></error-message>
									</p>
								</td>
								<td style="min-width:300px">
									<!-- Activity Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_ACTIVITY"
										:options="item.IN_EXPENSE_NR==\'Recoverable\' ? activityRecList : activityNoRecList"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeActivity(\'enable\',index, $event)"
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.messages"></error-message>
									</p>
								</td>
								<td style="min-width:300px">
									<!-- Corp Entity Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_CORP_ENTITY"
										:options="IN_EXPENSE_CORP_ENTITY_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeCorpEntity(index, $event)"
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY.messages"></error-message>
									</p>
								</td>
								<td style="min-width:300px">
									<!-- Department Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_DEPARTMENT"
										:options="IN_EXPENSE_DEPARTMENT_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeDepartmet(\'enable\',index, $event)"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_DEPARTMENT != \'\'}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.messages"></error-message>
									</p>
								</td>
								<td style="min-width:300px">
									<!-- Office Dropdown -->
									<multiselect 
										v-model="item.IN_EXPENSE_OFFICE"
										:options="IN_EXPENSE_OFIICE_VALUES"
										:searchable="true" 
										:close-on-select="true" 
										placeholder="Please select" 
										select-label="Select" 
										label="LABEL" 
										track-by="LABEL"
										@input="onChangeOffice(\'enable\',index, $event)"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_OFFICE != \'\'}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.messages"></error-message>
									</p>
								</td>
								<td style=" text-align: right; min-width:150px;">
									{{item.IN_EXPENSE_GL_CODE }}
								</td>
							</tr>
						</tbody>
						<tfoot class="tfoot-light">
							<tr>
								<td colspan="2" v-if="!isDiscrepancyMode"></td>
								<td colspan="4"></td>
								<td><strong>Total</strong></td>
								<td class="matching" 
								
									v-bind:class="formatNumber(IN_TOTAL_PRE_TAX) == formatNumber(IN_TOTAL_PRE_TAX_AMOUNT) ? \'equal\' : \'different\'"
									style="text-align: right;padding-right: 1.3em;">
									{{ formatNumber(IN_TOTAL_PRE_TAX_AMOUNT) }}
								</td>
								<td class="matching" 
									v-bind:class="formatNumber(IN_TOTAL_TAX) == formatNumber(IN_TOTAL_HST) ? \'equal\' : \'different\'"
									style="text-align: right;padding-right: 1.3em;">
									{{ formatNumber(IN_TOTAL_HST) }}
								</td>
								<td class="matching" 
									v-bind:class="formatNumber(IN_SUM_TOTAL) == formatNumber(IN_TOTAL_TOTAL) ? \'equal\' : \'different\'"
									style="text-align: right;padding-right: 1.3em;">
									{{ formatNumber(IN_TOTAL_TOTAL) }}
									<|input v-show="false" v-model="cleanNumber(IN_TOTAL_TOTAL)" id="iframe-totalFinalAmount" class="form-control">
								</td>
								<td :class="{matching:true, equal: IN_TOTAL_PERCENTAGE_TOTAL== \'100.00\' , different: \'100.00\' !=IN_TOTAL_PERCENTAGE_TOTAL}"
									style="text-align: right;padding-right: 1.3em;">
									{{ formatPercentageNumber(IN_TOTAL_PERCENTAGE_TOTAL) }}
								</td>
								<td colspan="9"></td>
							</tr>
							<tr v-if="DISCREPANCY_IN_TOTAL_PRE_TAX != 0 || DISCREPANCY_IN_TOTAL_TAX != 0 || DISCREPANCY_IN_SUM_TOTAL != 0 ">
								<td colspan="2" v-if="!isDiscrepancyMode"></td>
								<td colspan="4"></td>
								<td class="text-danger"><strong>Discrepancy</strong></td>
								<td class="matching" style="text-align: right;padding-right: 1.3em;">
									{{ (DISCREPANCY_IN_TOTAL_PRE_TAX) }}
								</td>
								<td class="matching" style="text-align: right;padding-right: 1.3em;">
									{{ (DISCREPANCY_IN_TOTAL_TAX) }}
								</td>
								<td class="matching" style="text-align: right;padding-right: 1.3em;">
									{{ (DISCREPANCY_IN_SUM_TOTAL) }}
								</td>
								<td class="matching" style="text-align: right;padding-right: 1.3em;">
									{{ formatPercentageNumber(DISCREPANCY_IN_PERCENTAGE) }}
								</td>
								<td colspan="9"></td>
							</tr>
						</tfoot>
					</table>
				</div>
				<!-- Hidden fields-->
				<!-- <div style="display:none;"> -->
				<div>
					<|button id="btnGetComId" class="btn btn-success" @click="getComanyId" v-show="false"></|button>
					<|input id="iframe-items" v-model="JSON.stringify(prepareItems)" class="form-control" readonly  v-show="false">
					<|input id="iframe-items_2" v-model="JSON.stringify(prepareItemsDisabled)" class="form-control" readonly  v-show="false">
										
					<|input id="iframe-deleted-rows" v-model="JSON.stringify(deletedRows)" class="form-control" readonly  v-show="false">

					<|input id="iframe-IN_TOTAL_PRE_TAX_AMOUNT" v-model="cleanNumber(IN_TOTAL_PRE_TAX_AMOUNT)" class="form-control" readonly  v-show="false">
					<|input id="iframe-IN_TOTAL_HST" v-model="cleanNumber(IN_TOTAL_HST)" class="form-control" readonly  v-show="false">
					<|input id="iframe-IN_TOTAL_TOTAL" v-model="cleanNumber(IN_TOTAL_TOTAL)" class="form-control" readonly  v-show="false">
					<|input id="iframe-IN_TOTAL_PERCENTAGE_TOTAL" v-model="cleanNumber(IN_TOTAL_PERCENTAGE_TOTAL)" class="form-control" readonly  v-show="false">
					
					<|input id="iframe-IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT" v-model="cleanNumber(IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT, true)" class="form-control" readonly  v-show="false">
					<|input id="iframe-IN_OUTSTANDING_TOTAL_HST" v-model="cleanNumber(IN_OUTSTANDING_TOTAL_HST, true)" class="form-control" readonly  v-show="false">
					<|input id="iframe-IN_OUTSTANDING_TOTAL" v-model="cleanNumber(IN_OUTSTANDING_TOTAL)" class="form-control" readonly  v-show="false">
					<|input id="iframe-IN_OUTSTANDING_PERCENTAGE" v-model="cleanNumber(IN_OUTSTANDING_PERCENTAGE)" class="form-control" readonly  v-show="false">

					<|input id="iframe-IN_IS_VALID_CUSTOME_GRID" v-model="isValidCustomeGrid" class="form-control" readonly v-show="false">
				</div>
			</div>
			<div class="row" style="width: 99%;">
				<div style="width: 25%;">
					<div style="margin-bottom:50px;" v-if="items.length>0">
						<|button class="btn btn-dark" @click="addRow" v-if="!isDiscrepancyMode"><span class="fa fa-plus-circle"></span> Add Item</|button>
						<|button class="btn btn-success" @click="fixDiscrepancy" v-if="!isDiscrepancyMode && showDiscrepancyButton"><span class="fa fa-wrench"></span> Fix Discrepancy</|button>
						<|button class="btn btn-primary" @click="getEditMode" v-if="isDiscrepancyMode"><span class="fa fa-edit"></span> Edit Mode</|button>
					</div>
				</div>
				<br style="clear:both;"/>
				<div style="width: 70%;" v-if="items.length>0 || disabledItems.length>0">
					<!--div class="alert alert-danger" role="alert" v-if="!isGridValid || gridError.error || fixDiscrepancyError || !isValidAmounts || DISCREPANCY_IN_TOTAL_PRE_TAX != 0 || DISCREPANCY_IN_TOTAL_TAX != 0 || DISCREPANCY_IN_SUM_TOTAL != 0"-->
					<div class="alert alert-danger" role="alert" v-if="!isGridValid || gridError.error || fixDiscrepancyError || DISCREPANCY_IN_TOTAL_PRE_TAX != 0 || DISCREPANCY_IN_TOTAL_TAX != 0 || DISCREPANCY_IN_SUM_TOTAL != 0">
						<ul>
							<li v-if="this.INVALID_ROWS.length > 0">The sum of the allocations is not 100%, please review index(es) {{INVALID_ROWS}}.</li>
							<li v-if="fixDiscrepancyError">Please you need to fix the discrepancy error, return to edit mode.</li>
							<!--li v-if="!isValidAmounts">All pre-tax amount values must be greater than zero.</li-->
							<li v-if="!isGridValid">There are required fields missing on the Expense Table.</li>
							<li v-if="DISCREPANCY_IN_TOTAL_PRE_TAX != 0">The Total Pre Tax amount does not match the Invoice Pre Tax Amount.</li>
							<li v-if="DISCREPANCY_IN_TOTAL_TAX != 0">The Tax amount does not match the Invoice Tax (only HST/GST/VAT).</li>
							<li v-if="DISCREPANCY_IN_SUM_TOTAL != 0">The Final Total amount does not match the Invoice Total Amount.</li>
							<!--li v-if="IN_OUTSTANDING_PERCENTAGE != 0">The Total Percentage must be equal to {{ IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA }}%</li-->							
							<li v-for="message in gridError.messages">
								{{ message }}
							</li>
						</ul>
					</div>
				</div>
			</div>			
		</div>
	</div>

	<!-- Add Vue.js from CDN -->
	<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
	
	<!-- Add BootstrapVue JS y sus dependencias from CDN -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-vue@2.21.2/dist/bootstrap-vue.min.js"></script>
	';
$html .= "
	<!-- Vue.js Script -->
	<script>

	// Custom component to show error messages
		Vue.component('error-message', {
			props: ['messageslist'],
			template: '<div><ul><li class=\"text-danger small\" v-for=\"(message, index) in Object.values(messageslist)\" :key=\"index\">{{ message }}</li></ul></div>'
		});
	// As a plugin
		new Vue({
			el: '#app',
			components: {
				multiselect: window.VueMultiselect.default
			},
			data: {
				SUBMITTER_DATA : " . json_encode($depAndOfficeData) . ",
				companyId : '" . $copyCOmpanyID . "',
				teamRoutingRecList: " . json_encode($teamRoutingRecList) . ",
				teamRoutingNoRecList: " . json_encode($teamRoutingNoRecList) . ",
				IN_EXPENSE_ACCOUNT_VALUES: " . json_encode($accountList) . ",
				corpProjList: " . json_encode($corpProjList) . ",
				dealList: " . json_encode($dealList) . ",
				fundManagerList: " . json_encode($fundManagerList) . ",


				IN_EXPENSE_DEPARTMENT_VALUES: " . json_encode($departmentList) . ",
				IN_EXPENSE_OFIICE_VALUES: " . json_encode($officeList) . ",
				IN_EXPENSE_CORP_ENTITY_VALUES: " . json_encode($corpEntityList) . ",
				activityRecList: "  . json_encode($activityRecList) . ",
				activityNoRecList: "  . json_encode($activityNoRecList) . ",
				expenseDefaultRules: "  . json_encode($expenseDefaultRules) . ",

				DISCREPANCY_IN_TOTAL_PRE_TAX : 0,
				DISCREPANCY_IN_TOTAL_TAX : 0,
				DISCREPANCY_IN_SUM_TOTAL: 0,
				DISCREPANCY_IN_PERCENTAGE: 0,
				TOTALS_PER_ROW : [],
				INVALID_ROWS : [],
				CURRENT_IN_TOTAL_PRE_TAX : 0,
				CURRENT_IN_TOTAL_TAX : 0,
				CURRENT_IN_SUM_TOTAL : 0,
				IN_TOTAL_PRE_TAX : " . $totalPretax . ",
				IN_TOTAL_TAX : " . $totalTax . ",
				IN_SUM_TOTAL : " . $totalTotal . ",
				rawValue: '',
				viewMode: " . $viewMode . ",
				IN_REQUEST_ID: " . $requestId . ",
				IN_INVOICE_PRE_TAX: " . $invoicePreTax . ",
				IN_INVOICE_TAX_TOTAL: " . $invoiceTaxTotal . ",
				IN_INVOICE_TOTAL: " . $invoiceTotal . ",
				IN_INVOICE_PRE_TAX_PERCENTAGE: " . $invoicePreTaxPercentage . ",
				IN_INVOICE_TAX_TOTAL_PERCENTAGE: " . $invoiceTaxTotalPercentage . ",
				IN_INVOICE_TOTAL_PERCENTAGE: " . $invoiceTotalPercentage . ",
				IN_INVOICE_CURRENCY: '" . $invoiceCurrency . "',

				//PE
				IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_PE: " . $inTotalPreTaxAmountInitSubPe . ",
				IN_TOTAL_HST_INIT_SUB_PE: " . $inTotalHstInitSubPe . ",
				IN_TOTAL_TOTAL_INIT_SUB_PE: " . $inTotalTotalInitSubPe . ",
				IN_TOTAL_PERCENTAGE_TOTAL_INIT_SUB_PE: " . $inTotalPercentageTotalInitSubPe . ",
				IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_PE: " . $inInvoicePreTaxPercentageInitSubPe . ",
				IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_PE: " . $inInvoiceTaxTotalPercentageInitSubPe . ",
				IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_PE: " . $inInvoiceTotalPercentageInitSubPe . ",

				IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA: 0,
				IN_TOTAL_HST_INIT_SUB_INFRA: 0,
				IN_TOTAL_TOTAL_INIT_SUB_INFRA: 0,



				//Final Values
				IN_TOTAL_PRE_TAX_AMOUNT: 0,
				IN_TOTAL_HST: 0,
				IN_TOTAL_TOTAL: 0,
				IN_TOTAL_PERCENTAGE_TOTAL: 0,

				IN_OUTSTANDING_TOTAL_HST: 0,
                IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT: 0,
				IN_OUTSTANDING_TOTAL: 0,
				IN_OUTSTANDING_PERCENTAGE: 0,

				roundingDifference: 1,

				items: " . json_encode($enabledRows) . ",
				disabledItems: " . json_encode($disabledRows) . ",
				deletedRows: [],
				//Dropdown data sources
				
				mandateList: " . json_encode($mandateList) . ",
				
				IN_EXPENSE_NR_VALUES: ['Recoverable', 'Non-Recoverable'],
				errorMessages: {
					'required': 'This field is required',
					'stringLength': 'The content must not exceed 60 characters',
					'atLeastOneRequired': 'You must select at least one of these fields',
				},
				discrepancyAmount: " . $discrepancyAmount . ",
				isDiscrepancyMode: " . $isDiscrepancyMode . ",
				isValidAmounts: true,
				fixDiscrepancyError: false,
				showDiscrepancyButton: false,
				isGridValid: true,
				//isValidCustomeGrid: true,
				gridError: {
					error: false,
					messages: []
				},
				descriptionLimit:255,
				sumPreTaxDis : 0,
				sumTaxDis : 0,
				sumTotalDis : 0
			},
			methods: {
				async getInvoiceData(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'getInvoiceData',
							IN_REQUEST_ID : this.IN_REQUEST_ID
						}
					};
					axios.post(apiUrl,dataToSend)
					.then((response) => {
						const allItems     = response.data;
						allItems.forEach((item, idx) => {
							console.log(item);
							validationRules = JSON.parse(atob(item.IN_EXPENSE_VALIDATION));
							validationRules.IN_EXPENSE_DEPARTMENT = {
								'isValid' : true,
								'isDisabled' : false,
								'isRequired' : true,
								'messages' : {'required' : 'This Field is required.'}
							};
							validationRules.IN_EXPENSE_OFFICE = {
								'isValid' : true,
								'isDisabled' : false,
								'isRequired' : true,
								'messages' : {'required' : 'This Field is required.'}
							};
							validationRules.IN_EXPENSE_OFFICE = {
								'isValid' : true,
								'isDisabled' : false,
								'isRequired' : true,
								'messages' : {'required' : 'This Field is required.'}
							};
							validationRules.IN_EXPENSE_PROJECT_DEAL = {
								'isValid' : true,
								'isDisabled' : false,
								'isRequired' : true,
								'messages' : {'required' : 'This Field is required.'}
							};
							validationRules.IN_EXPENSE_FUND_MANAGER = {
								'isValid' : true,
								'isDisabled' : false,
								'isRequired' : true,
								'messages' : {'required' : 'This Field is required.'}
							};
							validationRules.IN_EXPENSE_MANDATE = {
								'isValid' : true,
								'isDisabled' : false,
								'isRequired' : true,
								'messages' : {'required' : 'This Field is required.'}
							};
							item.IN_EXPENSE_VALIDATION = validationRules;
							item.IN_EXPENSE_NR = item.IN_EXPENSE_NR_LABEL;
							item.IN_EXPENSE_CORP_PROJ = {
								ID:(item.IN_EXPENSE_CORP_PROJ_ID != undefined && item.IN_EXPENSE_CORP_PROJ_ID != 'null') ? item.IN_EXPENSE_CORP_PROJ_ID : '',
								LABEL:(item.IN_EXPENSE_CORP_PROJ_LABEL != undefined && item.IN_EXPENSE_CORP_PROJ_LABEL != 'null') ? item.IN_EXPENSE_CORP_PROJ_LABEL : ''};
							item.IN_EXPENSE_ACCOUNT = {
								ID:(item.IN_EXPENSE_ACCOUNT_ID != undefined && item.IN_EXPENSE_ACCOUNT_ID != 'null') ? item.IN_EXPENSE_ACCOUNT_ID : '',
								LABEL:(item.IN_EXPENSE_ACCOUNT_LABEL != undefined && item.IN_EXPENSE_ACCOUNT_LABEL != 'null') ? item.IN_EXPENSE_ACCOUNT_LABEL : ''};
							item.IN_EXPENSE_TEAM_ROUTING = {
								ID:(item.IN_EXPENSE_TEAM_ROUTING_ID != undefined && item.IN_EXPENSE_TEAM_ROUTING_ID != 'null') ? item.IN_EXPENSE_TEAM_ROUTING_ID : '',
								LABEL:(item.IN_EXPENSE_TEAM_ROUTING_LABEL != undefined && item.IN_EXPENSE_TEAM_ROUTING_LABEL != 'null') ? item.IN_EXPENSE_TEAM_ROUTING_LABEL : ''};
							item.IN_EXPENSE_PROJECT_DEAL = {
								ID:(item.IN_EXPENSE_PROJECT_DEAL_ID != undefined && item.IN_EXPENSE_PROJECT_DEAL_ID != 'null') ? item.IN_EXPENSE_PROJECT_DEAL_ID : '',
								LABEL:(item.IN_EXPENSE_PROJECT_DEAL_LABEL != undefined && item.IN_EXPENSE_PROJECT_DEAL_LABEL != 'null') ? item.IN_EXPENSE_PROJECT_DEAL_LABEL : ''};
							item.IN_EXPENSE_FUND_MANAGER = {
								ID:(item.IN_EXPENSE_FUND_MANAGER_ID != undefined && item.IN_EXPENSE_FUND_MANAGER_ID != 'null') ? item.IN_EXPENSE_FUND_MANAGER_ID : '',
								LABEL:(item.IN_EXPENSE_FUND_MANAGER_LABEL != undefined && item.IN_EXPENSE_FUND_MANAGER_LABEL != 'null') ? item.IN_EXPENSE_FUND_MANAGER_LABEL : ''};
							item.IN_EXPENSE_MANDATE = {
								ID:(item.IN_EXPENSE_MANDATE_ID != undefined && item.IN_EXPENSE_MANDATE_ID != 'null') ? item.IN_EXPENSE_MANDATE_ID : '',
								LABEL:(item.IN_EXPENSE_MANDATE_LABEL != undefined && item.IN_EXPENSE_MANDATE_LABEL != 'null') ? item.IN_EXPENSE_MANDATE_LABEL : ''};
							item.IN_EXPENSE_ACTIVITY = {
								ID:(item.IN_EXPENSE_ACTIVITY_ID != undefined && item.IN_EXPENSE_ACTIVITY_ID != 'null') ? item.IN_EXPENSE_ACTIVITY_ID : '',
								LABEL:(item.IN_EXPENSE_ACTIVITY_LABEL != undefined && item.IN_EXPENSE_ACTIVITY_LABEL != 'null') ? item.IN_EXPENSE_ACTIVITY_LABEL : ''};
							item.IN_EXPENSE_CORP_ENTITY = {
								ID:(item.IN_EXPENSE_CORP_ENTITY_ID != undefined && item.IN_EXPENSE_CORP_ENTITY_ID != 'null') ? item.IN_EXPENSE_CORP_ENTITY_ID : '',
								LABEL:(item.IN_EXPENSE_CORP_ENTITY_LABEL != undefined && item.IN_EXPENSE_CORP_ENTITY_LABEL != 'null') ? item.IN_EXPENSE_CORP_ENTITY_LABEL : ''};
							item.IN_EXPENSE_COMPANY = {
								ID:(item.IN_EXPENSE_COMPANY_ID != undefined && item.IN_EXPENSE_COMPANY_ID != 'null') ? item.IN_EXPENSE_COMPANY_ID : '',
								LABEL:(item.IN_EXPENSE_COMPANY_LABEL != undefined && item.IN_EXPENSE_COMPANY_LABEL != 'null') ? item.IN_EXPENSE_COMPANY_LABEL : ''};
							
							let depOffData = (this.SUBMITTER_DATA[0] != undefined && this.SUBMITTER_DATA[0] != 'null') ? this.SUBMITTER_DATA[0] : {};
							submitterDep = null;
							submitterOffice = null;

							if(depOffData.DEPARMENT_ID != undefined && depOffData.DEPARMENT_NAME != undefined){
								submitterDep = {
									ID : depOffData.DEPARMENT_ID.replace(/\"/g, ''),
									LABEL : depOffData.DEPARMENT_NAME.replace(/\"/g, '')
								}
							}
							if(depOffData.OFFICE_ID != undefined && depOffData.OFFICE_NAME != undefined){
								submitterOffice = {
									ID : depOffData.OFFICE_ID.replace(/\"/g, ''),
									LABEL : depOffData.OFFICE_NAME.replace(/\"/g, '')
								}
							}
							expenseOffice = (item.IN_EXPENSE_OFFICE_ID == '') ? null : {ID : item.IN_EXPENSE_OFFICE_ID, LABEL :item.IN_EXPENSE_OFFICE_LABEL};
							expenseDepartment = (item.IN_EXPENSE_DEPARTMENT_ID == '') ? null : {ID : item.IN_EXPENSE_DEPARTMENT_ID, LABEL :item.IN_EXPENSE_DEPARTMENT_LABEL};
							item.IN_EXPENSE_OFFICE = (expenseOffice != null ) ? expenseOffice : submitterOffice;
							item.IN_EXPENSE_DEPARTMENT = (expenseDepartment != null) ? expenseDepartment :  submitterDep;
							item.IN_EXPENSE_PRETAX_AMOUNT    = isNaN(item.IN_EXPENSE_PRETAX_AMOUNT.replace(/,/g, '')) ? 0 : (item.IN_EXPENSE_PRETAX_AMOUNT.replace(/,/g, ''));
							item.IN_EXPENSE_HST              = isNaN(item.IN_EXPENSE_HST.replace(/,/g, '')) ? 0 : (item.IN_EXPENSE_HST.replace(/,/g, ''));
							item.IN_EXPENSE_TOTAL            = isNaN(item.IN_EXPENSE_TOTAL.replace(/,/g, '')) ? 0 : (item.IN_EXPENSE_TOTAL.replace(/,/g, ''));
							item.IN_EXPENSE_PERCENTAGE_TOTAL = isNaN(item.IN_EXPENSE_PERCENTAGE_TOTAL.replace(/,/g, '')) ? 0 : (item.IN_EXPENSE_PERCENTAGE_TOTAL.replace(/,/g, ''));
							
						});
						this.items         = allItems.filter(item => item.IN_EXPENSE_TEAM_ROUTING_ID === '" . $teamID . "');
						this.disabledItems = allItems.filter(item => item.IN_EXPENSE_TEAM_ROUTING_ID !== '" . $teamID . "');

						this.items.forEach((item,idx) => {
							this.items[idx].IN_EXPENSE_PERCENTAGE_TOTAL = this.formatPercentageNumber(item.IN_EXPENSE_TOTAL * 100 / this.IN_SUM_TOTAL);
							this.items[idx].IN_EXPENSE_PRETAX_AMOUNT    = this.formatNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
							this.items[idx].IN_EXPENSE_HST              = this.formatNumber(item.IN_EXPENSE_HST);
							this.items[idx].IN_EXPENSE_TOTAL            = this.formatNumber(item.IN_EXPENSE_TOTAL);
							//this.items[idx].IN_EXPENSE_PERCENTAGE_TOTAL = this.formatPercentageNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL);
							this.generateGLCode('enable',idx,item);

							
						});
						let sumPreTaxDis = 0;
						let sumTaxDis = 0;
						let sumTotalDis = 0;

						this.disabledItems.forEach((item,idx) => {
							sumPreTaxDis += item.IN_EXPENSE_PRETAX_AMOUNT.replace(/,/g, '') * 1;
							sumTaxDis += item.IN_EXPENSE_HST.replace(/,/g, '') * 1;
							sumTotalDis += item.IN_EXPENSE_TOTAL.replace(/,/g, '') * 1;
							this.disabledItems[idx].IN_EXPENSE_PERCENTAGE_TOTAL = (item.IN_EXPENSE_TOTAL * 100 / this.IN_INVOICE_TOTAL);
						});
						this.sumPreTaxDis = sumPreTaxDis;
						this.sumTaxDis = sumTaxDis;
						this.sumTotalDis = sumTotalDis;
						
						this.calculateFinalTotals();

						const parentButton = window.parent.document.querySelector('[selector=\"readyScreen\"] > div > button');
						//parentButton.click();
						if (parentButton) {
							parentButton.click(); // Simular un clic en el botón
						}
						else{
							alert('no button');
						}





						this.showScreen = true;
						
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getAccountList(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'accountList'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.IN_EXPENSE_ACCOUNT_VALUES = response.data;
						
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getActivityRecList(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'activityRecList'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.activityRecList = response.data.activityRecList;
						this.activityNoRecList = response.data.activityNoRecList;
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getCorpProjList(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'corpProjList'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.corpProjList = response.data;
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getCorpEntityList(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'corpEntityList'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.IN_EXPENSE_CORP_ENTITY_VALUES = response.data;
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getExpenseDefault(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'expenseDefault'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.expenseDefaultRules = response.data;
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getDepartmentList(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'departmentList'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.IN_EXPENSE_DEPARTMENT_VALUES = response.data;
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},
				async getOfficeList(){
					const apiUrl = 'https://northleaf.dev.cloud.processmaker.net/api/1.0/execution/script/53d4ba8d-5e74-4b48-9bb6-6f243e8f3826';
					const dataToSend = {
						data: {
							action : 'officeList'
						}
					};
					await axios.post(apiUrl,dataToSend)
					.then((response) => {
						this.IN_EXPENSE_OFIICE_VALUES = response.data;
					})
					.catch((error) => {
						console.error('Error fetching data:', error);
					});
				},				
				handleKeyDown(index, field){
					if (event.key === 'Enter') {
						switch (field) {
							case 'IN_EXPENSE_PRETAX_AMOUNT':
								this.onBlurPreTaxAmount(index, field);
								break;
							case 'IN_EXPENSE_TOTAL':
								this.onBlurTotal(index, field);
								break;
							case 'IN_EXPENSE_PERCENTAGE_TOTAL':
								this.onBlurPercentageTotal(index, field);
								break;
						}
					}
				},
				formatNumber(value) {
					if(isNaN(value) || value == Infinity) {
						return '0.00';
					}
					let numberValue = parseFloat(value);
					return numberValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });  
				},
				formatPercentageNumber(value) {
					if(isNaN(value) || value == Infinity) {
						return '0.00';
					}
					let numberValue = parseFloat(value);
					let formatNumber = numberValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
					return formatNumber + '%';
				},
				evaluateRow(item) {
					//let item = this.items[index];
        			// Limpiar los valores de comas antes del cálculo
					let preTax = this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
					//let hst = this.cleanNumber(item.IN_EXPENSE_HST);
					let taxTotalPercentage = this.cleanNumber(this.IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_INFRA);
					let invoicePreTaxPercentage = this.cleanNumber(this.IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA);

					// Calc IN_EXPENSE_HST => ((IN_EXPENSE_PRETAX_AMOUNT * IN_INVOICE_TAX_TOTAL_PERCENTAGE_INIT_SUB_INFRA) / IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA)
					//let hst = ((preTax * taxTotalPercentage) / invoicePreTaxPercentage);
					//item.IN_EXPENSE_HST = isNaN(hst) ? '0.00' : this.formatNumber(hst);
					let newHST = this.cleanNumber(item.IN_EXPENSE_TOTAL) * this.cleanNumber(this.IN_INVOICE_TAX_TOTAL_PERCENTAGE) / 100;
					item.IN_EXPENSE_HST = isNaN(newHST) ? '0.00' : this.formatNumber(newHST);

					// Recalcular el porcentaje de la factura
					let itemPercentage = (parseFloat(this.cleanNumber(item.IN_EXPENSE_TOTAL)) * this.totalPercentage) / this.IN_TOTAL_TOTAL_INIT_SUB_INFRA;
					//let rowPercentage = Math.round((itemPercentage + Number.EPSILON) * 100) / 100;
					//item.IN_EXPENSE_PERCENTAGE_TOTAL = this.formatNumber(rowPercentage);

					/*item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid = this.isValidValue(item.IN_EXPENSE_DESCRIPTION);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid = this.isValidValue(item.IN_EXPENSE_NR);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages.required = this.errorMessages.required;
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.isValid = this.isValidValue(item.IN_EXPENSE_DEPARTMENT);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DEPARTMENT.messages = [this.errorMessages.required];
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.isValid = this.isValidValue(item.IN_EXPENSE_DEPARTMENT);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_OFFICE.messages = [this.errorMessages.required];*/
					this.calculateFinalTotals();
				},
				onChangeDescription(index, value){
					let item = this.items[index];
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid = this.isValidValue(value);
					this.areAllValid();
				},
				addRow() {
					let submitterOffice = null;
					let submitterDept   = null;
					let submitterData   = this.SUBMITTER_DATA;
					if(submitterData.length > 0 && submitterData[0] != undefined){
						submitterDept = {
							ID : submitterData[0]['DEPARMENT_ID'].replace(/\"/g, ''),
							LABEL : submitterData[0]['DEPARMENT_NAME'].replace(/\"/g, '')
						};
						submitterOffice = {
							ID : submitterData[0]['OFFICE_ID'].replace(/\"/g, ''),
							LABEL : submitterData[0]['OFFICE_NAME'].replace(/\"/g, '')
						};
					}
					this.items.push(
						{
							IN_EXPENSE_CASE_ID: this.IN_REQUEST_ID,
        					IN_EXPENSE_ROW_ID: this.generateSecureUID(),
							IN_EXPENSE_ROW_NUMBER: this.items.length + 1,
							IN_EXPENSE_TEAM_ROW_INDEX: this.items.length + 1,
							IN_EXPENSE_NR: 'Non-Recoverable',
							IN_EXPENSE_ACCOUNT: '',
							IN_EXPENSE_CORP_PROJ: null,
							IN_EXPENSE_PRETAX_AMOUNT: '0.00',
							IN_EXPENSE_HST: '0.00',
							IN_EXPENSE_TOTAL: '0.00',
							IN_EXPENSE_PERCENTAGE_TOTAL: '0.00%',
							IN_EXPENSE_TEAM_ROUTING: {ID: 'CORP', LABEL: 'Corporate'},
							IN_EXPENSE_PROJECT_DEAL: null,
							IN_EXPENSE_FUND_MANAGER: null,
							IN_EXPENSE_MANDATE: null,
							IN_EXPENSE_ACTIVITY: null,
							IN_EXPENSE_CORP_ENTITY: null,
							IN_EXPENSE_DEPARTMENT: submitterDept,
							IN_EXPENSE_OFFICE: submitterOffice,
							IN_EXPENSE_VALIDATION: {
								IN_EXPENSE_DESCRIPTION: { isValid: false, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} },
								IN_EXPENSE_ACCOUNT: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_CORP_PROJ: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_PRETAX_AMOUNT: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_HST: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_TOTAL: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_PERCENTAGE: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_PERCENTAGE_TOTAL: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_NR: { isValid: true, isDisabled: true, isRequired: true, messages: {} },
								IN_EXPENSE_TEAM_ROUTING: { isValid: true, isDisabled: true, isRequired: true, messages: {} },
								IN_EXPENSE_PROJECT_DEAL: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_FUND_MANAGER: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_MANDATE: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_ACTIVITY: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_CORP_ENTITY: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_DEPARTMENT: { isValid: true, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} },
								IN_EXPENSE_OFFICE: { isValid: true, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} }
							}
						}
					)
					this.onChangeRecoverable(this.items.length-1, 'Non-Recoverable')
					this.items[this.items.length-1]['IN_EXPENSE_TEAM_ROUTING'] = {ID: 'CORP', LABEL: 'Corporate'};
					this.onChangeTeamRouting(this.items.length-1, {ID: 'CORP', LABEL: 'Corporate'});
					this.calculateFinalTotals();
					this.areAllValid() ;
				},
				deleteRow(index) {
					this.deletedRows.push(this.items[index].IN_EXPENSE_ROW_ID);
					this.items.splice(index, 1);
					let indexNum = (this.items[index] != undefined) ? this.items[index].IN_EXPENSE_ROW_NUMBER : '';
					
					if(indexNum.includes('.')){
						let newIndex = 1;
						let rowId    = indexNum.split('.')[0];
						let rowSubId = indexNum.split('.')[1];
						this.items.forEach((item, idx) => {
							if(item.IN_EXPENSE_ROW_NUMBER.includes('.') && item.IN_EXPENSE_ROW_NUMBER.split('.')[0] == rowId){
								item.IN_EXPENSE_ROW_NUMBER = rowId + '.' + newIndex;
								newIndex++;
							}
						});

					}
					
					//this.items = this.items.map((item, index) => ({ ...item, IN_EXPENSE_TEAM_ROW_INDEX: index+1 }));
					if(this.items.length <= 0){
						this.addRow();
					}
					this.calculateFinalTotals();
					this.areAllValid() ;
				},
				splitRow(index) {
					let clonedItem = { ...this.items[index] };
					clonedItem.IN_EXPENSE_ROW_ID = this.generateSecureUID();
					clonedItem.IN_EXPENSE_PRETAX_AMOUNT = '0.00';
					clonedItem.IN_EXPENSE_HST = '0.00';
					clonedItem.IN_EXPENSE_TOTAL = '0.00';
					clonedItem.IN_EXPENSE_PERCENTAGE_TOTAL = '0.00%';
					// Workaround to break the dependency between the original and the copy
					let stringItem =(JSON.stringify(clonedItem));
					let decodedItem = JSON.parse(stringItem);
					
					// Insert the copy
					this.items.splice(index + 1, 0, decodedItem);
					let previewVal = '';
					let cloneSubID = clonedItem.IN_EXPENSE_ROW_NUMBER.toString();
					let cloneID    = cloneSubID.split('.')[0];
					let maxIndex = '';
					let subIndex = false;
					// Adjust the row number
					this.items.forEach((item, idx) => {
						maxIndex = (maxIndex == '') ? 1 : maxIndex * 1  + 1;
						if(subIndex && item.IN_EXPENSE_ROW_NUMBER.includes(cloneID + '.')){
							item.IN_EXPENSE_ROW_NUMBER = cloneID + '.' + maxIndex;
						}
						if(cloneID == item.IN_EXPENSE_ROW_NUMBER ){
							item.IN_EXPENSE_ROW_NUMBER = cloneID + '.' + maxIndex;
						}
						if(cloneSubID == item.IN_EXPENSE_ROW_NUMBER && !subIndex){
							maxIndex = cloneSubID.split('.')[1] * 1;
							subIndex = true;
						}
						//item.IN_EXPENSE_TEAM_ROW_INDEX = idx + 1;
					});
				},
				// Elimina comas de una cadena de texto antes de convertirla a número
				cleanNumber(value, isNegative = true) {
					if(!value){
						return 0;
					}
					let stringValue = value.toString();
					return isNegative ? parseFloat(stringValue.replace(/[%\,]/g, '')) : parseFloat(stringValue.replace(/[%\-,]/g, ''));
				},
				calculateFinalTotals() {
					let totalRows          = [];
					let invalidRows        = [];
					let percentPerRow      = [];
					let totalsPerRow       = [];
					let currentTotalPreTax = 0;
					let currentTotalTax    = 0;
					let currentTotalTotal  = 0;
					let currentTotalPerc   = 0;
					if(Array.isArray(this.items)){
						let rowId    = '';
						let rowSubId = '';
						this.items.forEach(item => {
							currentTotalPreTax += this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
							currentTotalTax    += this.cleanNumber(item.IN_EXPENSE_HST);
							currentTotalTotal  += this.cleanNumber(item.IN_EXPENSE_TOTAL);
							currentTotalPerc   += this.cleanNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL);
							if(totalsPerRow[rowId] == undefined){
								totalsPerRow[rowId] = [];
								totalsPerRow[rowId]['IN_EXPENSE_PRETAX_AMOUNT'] = 0;
								totalsPerRow[rowId]['IN_EXPENSE_HST'] = 0;
								totalsPerRow[rowId]['IN_EXPENSE_TOTAL'] = 0	
							}
							totalsPerRow[rowId]['IN_EXPENSE_PRETAX_AMOUNT'] += item.IN_EXPENSE_PRETAX_AMOUNT * 1;
							totalsPerRow[rowId]['IN_EXPENSE_HST'] += item.IN_EXPENSE_HST * 1;
							totalsPerRow[rowId]['IN_EXPENSE_TOTAL'] += item.IN_EXPENSE_TOTAL * 1;
						});
						if(this.items.length > 0){
							this.CURRENT_IN_TOTAL_PRE_TAX  = currentTotalPreTax;
							this.CURRENT_IN_TOTAL_TAX      = currentTotalTax;
							this.CURRENT_IN_SUM_TOTAL      = currentTotalTotal;
							this.IN_TOTAL_PERCENTAGE_TOTAL = currentTotalPerc.toFixed(2);
							this.DISCREPANCY_IN_TOTAL_PRE_TAX = this.formatNumber(this.IN_TOTAL_PRE_TAX - this.CURRENT_IN_TOTAL_PRE_TAX);
							this.DISCREPANCY_IN_TOTAL_TAX     = this.formatNumber(this.IN_TOTAL_TAX - this.CURRENT_IN_TOTAL_TAX);
							this.DISCREPANCY_IN_SUM_TOTAL     = this.formatNumber(this.IN_SUM_TOTAL - this.CURRENT_IN_SUM_TOTAL);
							this.DISCREPANCY_IN_PERCENTAGE    = 100 - this.IN_TOTAL_PERCENTAGE_TOTAL;

							this.INVALID_ROWS             = invalidRows.toString();
							//this.TOTALS_PER_ROW           = totalsPerRow;
						}
					}

					

					let currentTotalFinalPreTax = 0;
					let currentTotalFinalHST = 0;
					let currentTotalFinalAmount = 0;
					let currentTotalFinalPercentage = 0;
					//let validatePreTax = true;
					let allPretaxValid = true;
					// Sub Total Team
					let inTotalPreTaxAmountSubInfra = 0;
					let inTotalHstSubInfra = 0;
					let inTotalTotalSubInfra = 0;
					let inTotalPercentageTotalSubInfra = 0;

					let inTotalPreTaxAmountSubPc = 0;
					let inTotalHstSubPc = 0;
					let inTotalTotalSubPc = 0;
					let inTotalPercentageTotalSubPc = 0;

					let inTotalPreTaxAmountSubPe = 0;
					let inTotalHstSubPe = 0;
					let inTotalTotalSubPe = 0;
					let inTotalPercentageTotalSubPe = 0;

					let inTotalPreTaxAmountSubCorp = 0;
					let inTotalHstSubCorp = 0;
					let inTotalTotalSubCorp = 0;
					let inTotalPercentageTotalSubCorp = 0;
					
					if(Array.isArray(this.items)){
						this.items.forEach(item => {
							allPretaxValid = allPretaxValid && this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) > 0;
							//validatePreTax = validatePreTax && (this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) > 0);

							currentTotalFinalPreTax += this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
							currentTotalFinalHST += this.cleanNumber(item.IN_EXPENSE_HST);
							currentTotalFinalAmount += this.cleanNumber(item.IN_EXPENSE_TOTAL);
							currentTotalFinalPercentage += this.cleanNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL);
						});
					}

					// Totals 
					this.IN_TOTAL_PRE_TAX_AMOUNT = this.cleanNumber(currentTotalFinalPreTax).toFixed(2);
					this.IN_TOTAL_HST = this.cleanNumber(currentTotalFinalHST).toFixed(2);
					this.IN_TOTAL_TOTAL = this.cleanNumber(currentTotalFinalAmount).toFixed(2);
					//this.IN_TOTAL_PERCENTAGE_TOTAL = this.cleanNumber(Math.round((currentTotalFinalPercentage + Number.EPSILON) * 100) / 100).toFixed(2);

					// Outstanding
					this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT = (this.cleanNumber((this.cleanNumber(this.IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA) - this.cleanNumber(this.IN_TOTAL_PRE_TAX_AMOUNT)), true)).toFixed(2);
					this.IN_OUTSTANDING_TOTAL_HST = (this.cleanNumber((this.cleanNumber(this.IN_TOTAL_HST_INIT_SUB_INFRA) - this.cleanNumber(this.IN_TOTAL_HST)), true)).toFixed(2);
					this.IN_OUTSTANDING_TOTAL = (this.cleanNumber((this.cleanNumber(this.IN_TOTAL_TOTAL_INIT_SUB_INFRA) - this.cleanNumber(this.IN_TOTAL_TOTAL)), true)).toFixed(2);   
					//this.IN_OUTSTANDING_PERCENTAGE = (this.cleanNumber((this.IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA - this.cleanNumber(this.IN_TOTAL_PERCENTAGE_TOTAL)), true)).toFixed(2);
					this.IN_OUTSTANDING_PERCENTAGE = (this.cleanNumber((100 - this.cleanNumber(this.IN_TOTAL_PERCENTAGE_TOTAL)), true)).toFixed(2);
					

					let outStandingPreTax = Math.abs(this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT);
					let outStandingHst = Math.abs(this.IN_OUTSTANDING_TOTAL_HST);
					let outStandingTotal = Math.abs(this.IN_OUTSTANDING_TOTAL);
					let outStandingPercentage = Math.abs(this.IN_OUTSTANDING_PERCENTAGE);
					this.isValidAmounts = allPretaxValid;
					this.fixDiscrepancyError = false;
					if(this.DISCREPANCY_IN_TOTAL_PRE_TAX == 0 
						&& this.DISCREPANCY_IN_TOTAL_TAX == 0
						&& this.DISCREPANCY_IN_SUM_TOTAL == 0
						&& this.DISCREPANCY_IN_PERCENTAGE == 0){
							this.showDiscrepancyButton = false;
					}
					else{
						if(
							this.DISCREPANCY_IN_TOTAL_PRE_TAX > Math.abs(this.discrepancyAmount) *-1 && this.DISCREPANCY_IN_TOTAL_PRE_TAX < Math.abs(this.discrepancyAmount) *1
							&& this.DISCREPANCY_IN_TOTAL_TAX > Math.abs(this.discrepancyAmount) *-1 && this.DISCREPANCY_IN_TOTAL_TAX < Math.abs(this.discrepancyAmount) *1
							&& this.DISCREPANCY_IN_SUM_TOTAL > Math.abs(this.discrepancyAmount) *-1 && this.DISCREPANCY_IN_SUM_TOTAL < Math.abs(this.discrepancyAmount) *1
							&& this.DISCREPANCY_IN_PERCENTAGE > Math.abs(this.discrepancyAmount) *-1 && this.DISCREPANCY_IN_PERCENTAGE < Math.abs(this.discrepancyAmount) *1
						){
							this.showDiscrepancyButton = true;
						}
						else{	
							this.showDiscrepancyButton = false;
						}
					}
					
					//this.showDiscrepancyButton = this.shouldShowButton(outStandingPreTax, outStandingHst, outStandingTotal, outStandingPercentage, this.discrepancyAmount) && allPretaxValid;
				},
				areAllValid() {
					let isRowValid = true;
					this.items.forEach(item => {
						Object.values(item.IN_EXPENSE_VALIDATION).forEach(validation => {
							if(validation != undefined ){
								if(!validation.isValid){
									isRowValid = false;
									return;
								}
							}
						});
						if(!isRowValid){
							return;
						}
					});
					this.disabledItems.forEach(item => {
						Object.values(item.IN_EXPENSE_VALIDATION).forEach(validation => {
							if(validation != undefined ){
								if(!validation.isValid){
									isRowValid = false;
									return;
								}
							}
						});
						if(!isRowValid){
							return;
						}
					});
					this.isGridValid = isRowValid;
				},
				shouldShowButton(amount1, amount2, amount3, amount4, limit) {

					const amounts = [amount1, amount2, amount3, amount4];
					// If all amounts are equal to zero dont show the button
					if (amounts.every(amount => amount === 0)) {
						return false;
					}
					// If one discrepancy is greater than limit dont show the button
					if (amounts.some(amount => Math.abs(amount) > limit)) {
						return false;
					}
					// If at least one discrepancy is 0 < x <= limit show the button
					return amounts.some(amount => Math.abs(amount) > 0 && Math.abs(amount) <= limit);
				},
				onBlurTotal(index, field) {
					if(!this.isDiscrepancyMode) {
						// Set format field on blur
						let item = this.items[index];
						// Calc IN_EXPENSE_PERCENTAGE_TOTAL = ((IN_EXPENSE_TOTAL  *  IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA)  /  IN_TOTAL_TOTAL_INIT_SUB_INFRA)
						let total = this.cleanNumber(item[field]);
						let perTotal  = (total * 100) / this.cleanNumber(this.IN_SUM_TOTAL);
						item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(perTotal) ? '0.00%' : this.formatPercentageNumber(perTotal);
						let expTotal  = (this.cleanNumber(this.IN_TOTAL_PRE_TAX) * perTotal)/ 100;
						item['IN_EXPENSE_PRETAX_AMOUNT'] = isNaN(expTotal) ? '0.00' : this.formatNumber(expTotal);


						/*let invoiceTotalPercentage = this.cleanNumber(this.IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA);
						let invoiceTotal = this.cleanNumber(this.IN_TOTAL_TOTAL_INIT_SUB_INFRA);
						let percentageTotal = ((total * invoiceTotalPercentage) / invoiceTotal);
						item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(percentageTotal) ? '0.00%' : this.formatPercentageNumber(percentageTotal);
						
						// Calc IN_EXPENSE_PRETAX_AMOUNT = ((IN_EXPENSE_PERCENTAGE_TOTAL  *  IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA)  /  IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA) 
						let invoicePreTax = this.cleanNumber(this.IN_TOTAL_PRE_TAX_AMOUNT_INIT_SUB_INFRA);
						let pretaxAmount = ((percentageTotal * invoicePreTax) / invoiceTotalPercentage);
						item['IN_EXPENSE_PRETAX_AMOUNT'] = isNaN(pretaxAmount) ? '0.00' : this.formatNumber(pretaxAmount);
						*/
						item[field] = this.formatNumber(this.cleanNumber(item[field]));
						this.evaluateRow(item);
					} else {
						this.calculateFinalTotals();
					}
				},
				onBlurPercentageTotal(index, field) {
					if(!this.isDiscrepancyMode) {
						// Set format field on blur
						let item     = this.items[index];
						let totalPer = this.cleanNumber(item[field]);
						let totalRow = this.IN_SUM_TOTAL * totalPer / 100;
						let taxRow   = totalRow * this.cleanNumber(this.IN_INVOICE_TAX_TOTAL_PERCENTAGE) / 100;
						let preTaxR  = totalRow - taxRow;

						item['IN_EXPENSE_TOTAL'] = isNaN(totalRow) ? '0.00' : this.formatNumber(totalRow);
						item['IN_EXPENSE_HST'] = isNaN(taxRow) ? '0.00' : this.formatNumber(taxRow);
						item['IN_EXPENSE_PRETAX_AMOUNT'] = isNaN(preTaxR) ? '0.00' : this.formatNumber(preTaxR);						
						item[field] = this.formatPercentageNumber(this.cleanNumber(item[field]));
						this.evaluateRow(item);
						
					} else {
						this.calculateFinalTotals();
					}
				},
				onBlurPreTaxAmount(index, field) {
					if(!this.isDiscrepancyMode) {
						// Set format field on blur
						let item = this.items[index];

						// Calc IN_EXPENSE_TOTAL = ((IN_EXPENSE_PRETAX_AMOUNT  *  IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA)  /  IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA)
						
						let prevTotal = this.cleanNumber(item[field]);
						let perTotal  = (prevTotal * 100) / this.cleanNumber(this.IN_TOTAL_PRE_TAX);
						item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(perTotal) ? '0.00%' : this.formatPercentageNumber(perTotal);
						let expTotal  = (this.cleanNumber(this.IN_SUM_TOTAL) * perTotal)/ 100;
						item['IN_EXPENSE_TOTAL'] = isNaN(expTotal) ? '0.00' : this.formatNumber(expTotal);


						/*
						let prevExpensePercentageTotal = (prevTotal * 100 / this.cleanNumber(this.IN_TOTAL_TOTAL_INIT_SUB_INFRA));

						let pretaxAmount = this.cleanNumber(item[field]);
						let invoiceTotalPercentage = this.cleanNumber(this.IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA);
						let invoicePreTaxPercentage = this.cleanNumber(this.IN_INVOICE_PRE_TAX_PERCENTAGE_INIT_SUB_INFRA);

						let total = ((pretaxAmount * invoiceTotalPercentage)/ invoicePreTaxPercentage);

						item['IN_EXPENSE_TOTAL'] = isNaN(total) ? '0.00' : this.formatNumber(total);

						// Calc IN_EXPENSE_PERCENTAGE_TOTAL = ((IN_EXPENSE_TOTAL  *  IN_INVOICE_TOTAL_PERCENTAGE_INIT_SUB_INFRA)  /  IN_TOTAL_TOTAL_INIT_SUB_INFRA)
						/*let invoiceTotal = this.cleanNumber(this.IN_TOTAL_TOTAL_INIT_SUB_INFRA);
						let percentageTotal = ((total * invoiceTotalPercentage)) / invoiceTotal;
						//item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(percentageTotal) ? '0.00%' : this.formatPercentageNumber(percentageTotal);
						item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(percentageTotal) ? '0.00%' : this.formatPercentageNumber(percentageTotal);
						*/
						
						item[field] = this.formatNumber(this.cleanNumber(item[field]));
						this.evaluateRow(item);
					} else {
						this.calculateFinalTotals();
					}
				},
				onFieldBlur(index, field) {
					// Formatear el campo solo cuando se pierde el foco
					let item = this.items[index];
					item[field] = this.formatNumber(this.cleanNumber(item[field]));
					this.evaluateRow(item);  // recalcular totales después del formato
				},
				onFieldInput(index, field, event) {
					// Actualizar el valor en tiempo real sin formatear para evitar mover el cursor
					let item = this.items[index];
					item[field] = event.target.value;
				},
				selectAllContent(event) {
					event.target.select();
				},
				onChangeRecoverable(index, value){
					let item = this.items[index];
					if(this.isValidValue(value)){
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid = true;
						delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages.required;
					} else {
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid = false;
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages.required = this.errorMessages.required;;
					}
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isRequired = true;
					switch (value) {
						case 'Recoverable':
							// this.setBusinessRules(index, 'IN_EXPENSE_DESCRIPTION', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_NR', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_ACCOUNT', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_CORP_PROJ', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_PRETAX_AMOUNT', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_HST', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_TOTAL', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_PERCENTAGE', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_PERCENTAGE_TOTAL', 'RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_NR', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_TEAM_ROUTING', 'RECOVERABLE');
							if(item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_TEAM_ROUTING']['isDisabled']){
								item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_TEAM_ROUTING']['isValid']= true;
							}
							this.setBusinessRules(index, 'IN_EXPENSE_PROJECT_DEAL', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_FUND_MANAGER', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_MANDATE', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_ACTIVITY', 'RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_CORP_ENTITY', 'RECOVERABLE');
							
							if(
								!this.isValidValue(item.IN_EXPENSE_PROJECT_DEAL) && 
								!this.isValidValue(item.IN_EXPENSE_FUND_MANAGER) && 
								!this.isValidValue(item.IN_EXPENSE_MANDATE)
							){
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
								delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.required;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
								delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.required;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
								delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.required;
							}
							break;
						case 'Non-Recoverable':
							// this.setBusinessRules(index, 'IN_EXPENSE_DESCRIPTION', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_NR', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_ACCOUNT', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_CORP_PROJ', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_PRETAX_AMOUNT', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_HST', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_TOTAL', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_PERCENTAGE', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_PERCENTAGE_TOTAL', 'NON_RECOVERABLE');
							// this.setBusinessRules(index, 'IN_EXPENSE_NR', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_TEAM_ROUTING', 'NON_RECOVERABLE');
							
							this.setBusinessRules(index, 'IN_EXPENSE_PROJECT_DEAL', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_FUND_MANAGER', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_MANDATE', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_ACTIVITY', 'NON_RECOVERABLE');
							this.setBusinessRules(index, 'IN_EXPENSE_CORP_ENTITY', 'NON_RECOVERABLE');

							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = true;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isRequired = false;
							delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = true;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isRequired = false;
							delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.atLeastOneRequired;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = true;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isRequired = false;
							delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.atLeastOneRequired;

							break;
						default:
							// item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid = false;
							// item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages.required = this.errorMessages.required;

							item.IN_EXPENSE_DESCRIPTION = null;
							item.IN_EXPENSE_NR = null;
							item.IN_EXPENSE_ACCOUNT = null;
							item.IN_EXPENSE_CORP_PROJ = null;
							// item.IN_EXPENSE_PRETAX_AMOUNT = null;
							// item.IN_EXPENSE_HST = null;
							// item.IN_EXPENSE_TOTAL = null;
							// item.IN_EXPENSE_PERCENTAGE = null;
							// item.IN_EXPENSE_PERCENTAGE_TOTAL = null;
							item.IN_EXPENSE_TEAM_ROUTING = null;
							item.IN_EXPENSE_PROJECT_DEAL = null;
							item.IN_EXPENSE_FUND_MANAGER = null;
							item.IN_EXPENSE_MANDATE = null;
							item.IN_EXPENSE_ACTIVITY = null;
							item.IN_EXPENSE_CORP_ENTITY = null;

							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR= { isValid: false, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} };
							//item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_PROJ= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PRETAX_AMOUNT= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_HST= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TOTAL= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PERCENTAGE= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PERCENTAGE_TOTAL= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING= { isValid: false, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_CORP_ENTITY= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
							
							break;
					}
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.isRequired=true;
					this.areAllValid();
				},
				setBusinessRules(index, fieldName, ruleType) {
					let item = this.items[index];
					item['IN_EXPENSE_VALIDATION'][fieldName]['isRequired'] = this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_REQUIRED']==='true' || this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_REQUIRED']=== true;
					item['IN_EXPENSE_VALIDATION'][fieldName]['isDisabled'] = this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_DISABLE'];
					if(
						this.isValidValue(this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_ID']) &&
						this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_ID'] != 'null'
					){
						item[fieldName] = { 
								ID: this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_ID'],
								LABEL: this.expenseDefaultRules[ruleType][fieldName]['DEFAULT_LABEL'],
							};
					} else {
						item[fieldName] = '';
					}

					if(item['IN_EXPENSE_VALIDATION'][fieldName]['isDisabled']){
						item['IN_EXPENSE_VALIDATION'][fieldName]['isValid'] = true;
					} else {
						if(item['IN_EXPENSE_VALIDATION'][fieldName]['isRequired']){
							item['IN_EXPENSE_VALIDATION'][fieldName]['isValid'] = this.isValidValue(item[fieldName]);
							if(!item['IN_EXPENSE_VALIDATION'][fieldName]['isValid']){
								item['IN_EXPENSE_VALIDATION'][fieldName]['messages']['required'] = this.errorMessages.required;
							}
						} else {
							item['IN_EXPENSE_VALIDATION'][fieldName]['isValid'] = true;
						}
					}
				},
				updateDeal(index, value){
					let item = this.items[index];
					if(this.isValidValue(value)) {
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = true;
						delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired;
						switch (item.IN_EXPENSE_NR) {
							case 'Recoverable':
								// Emty IN_EXPENSE_FUND_MANAGER
								item.IN_EXPENSE_FUND_MANAGER = null;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = true;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isDisabled = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages = {};
								// Remove required for IN_EXPENSE_MANDATE
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = true;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isDisabled = false;
								delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.atLeastOneRequired;

								break;
							case 'Non-Recoverable':
								break;
							default:
								break;
						}
					} else {
						if(!this.isValidValue(item.IN_EXPENSE_MANDATE) && !this.isValidValue(item.IN_EXPENSE_PROJECT_DEAL)){
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
							
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
							
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
						}
					}
					this.areAllValid();
				},
				updateFundManager(index, value){
					let item = this.items[index];
					let routing = item['IN_EXPENSE_TEAM_ROUTING'];
					if(this.isValidValue(value)) {
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = true;
						delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.atLeastOneRequired;
						switch (item.IN_EXPENSE_NR) {
							case 'Recoverable':
								// Emty IN_EXPENSE_PROJECT_DEAL
								item.IN_EXPENSE_PROJECT_DEAL = null;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = true;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isDisabled = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages = {};
								// Emty IN_EXPENSE_MANDATE
								item.IN_EXPENSE_MANDATE = null;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = true;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isDisabled = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages = {};

								break;
							case 'Non-Recoverable':
								break;
							default:
								break;
						}
					} else {
						//this.onChangeRecoverable(index, item.IN_EXPENSE_NR);
						if(!this.isValidValue(item.IN_EXPENSE_MANDATE) && !this.isValidValue(item.IN_EXPENSE_PROJECT_DEAL)){
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
						}
					}
					this.areAllValid();
				},
				updateMandate(index, value){
					let item = this.items[index];
					this.generateGLCode('enable',index,item);
				},
				updateMandateDisabled(index, value){
					let item = this.disabledItems[index];
					if(this.isValidValue(value)) {
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = true;
						delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.required;
						
						switch (item.IN_EXPENSE_NR) {
							case 'Recoverable':
								// Emty IN_EXPENSE_FUND_MANAGER
								item.IN_EXPENSE_FUND_MANAGER = null;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = true;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isDisabled = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages = {};
								// Remove required for IN_EXPENSE_MANDATE
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = true;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isDisabled = false;
								delete item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired;

								break;
							case 'Non-Recoverable':
								break;
							default:
								break;
						}
					} else {
						if(item.IN_EXPENSE_NR == 'Recoverable'){
							if(!this.isValidValue(item.IN_EXPENSE_MANDATE) && !this.isValidValue(item.IN_EXPENSE_PROJECT_DEAL)){
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = false;
								item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.atLeastOneRequired = this.errorMessages.atLeastOneRequired;
							}
						} else {
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.isValid = false;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_MANDATE.messages.required = this.errorMessages.required;
						}
					}
					this.areAllValid();
					this.generateGLCode('disabled',index,item);
				},
				onChangeTeamRouting(index, value){
					let item = this.items[index];
					if(this.isValidValue(value)){
						let teamRoutingBk = value;
						let activityBk = item.IN_EXPENSE_ACTIVITY;
						let corpProejctBk = item.IN_EXPENSE_CORP_PROJ;
						this.onChangeRecoverable(index, item.IN_EXPENSE_NR);
						item.IN_EXPENSE_TEAM_ROUTING = teamRoutingBk;
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.isValid = true;
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.messages = {};
						if(item.IN_EXPENSE_NR == 'Recoverable'){
							item.IN_EXPENSE_ACTIVITY = activityBk;
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid = this.isValidValue(activityBk);
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.messages = this.isValidValue(activityBk) ? {} : {required: this.errorMessages.required};
							item.IN_EXPENSE_CORP_PROJ = corpProejctBk;
							// item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid = this.isValidValue(activityBk);
							// item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.messages = this.isValidValue(activityBk) ? {} : {required: this.errorMessages.required};
						}

					} else {
						this.onChangeRecoverable(index, item.IN_EXPENSE_NR);
					}
					this.areAllValid();
				},
				generateGLCode(parent,index,item){
					//let GLCode = 'CID'; //vendorInformation.0.EXPENSE_VENDOR_COMPANYCODE
					let GLCode = this.companyId;
					GLCode += (item.IN_EXPENSE_ACCOUNT == null || item.IN_EXPENSE_ACCOUNT.ID == undefined) ? '' :  '-' + item.IN_EXPENSE_ACCOUNT.ID;
					let expNR = '';
					if(item.IN_EXPENSE_NR != null){
						 if(item.IN_EXPENSE_NR == 'Recoverable'){
							expNR = 2;
						}
						if(item.IN_EXPENSE_NR == 'Non-Recoverable'){
							expNR = 1;
						}
						if(item.IN_EXPENSE_NR == 'Unallocated'){
							expNR = 0;
						}
					}
					GLCode += (expNR == '') ? '' :  '-' + expNR;
					GLCode += (item.IN_EXPENSE_MANDATE == null || item.IN_EXPENSE_MANDATE.ID == undefined) ? '-0000' :  '-' + item.IN_EXPENSE_MANDATE.ID;
					GLCode += (item.IN_EXPENSE_OFFICE == null || item.IN_EXPENSE_OFFICE.ID == undefined) ? '' :  '-' + item.IN_EXPENSE_OFFICE.ID;
					GLCode += (item.IN_EXPENSE_DEPARTMENT == null || item.IN_EXPENSE_DEPARTMENT.ID == undefined) ? '' : '-' + item.IN_EXPENSE_DEPARTMENT.ID;
					GLCode += (item.IN_EXPENSE_ACTIVITY == null || item.IN_EXPENSE_ACTIVITY.ID == undefined) ? '-000' : '-' +item.IN_EXPENSE_ACTIVITY.ID;
					GLCode += (item.IN_EXPENSE_CORP_PROJ == null || item.IN_EXPENSE_CORP_PROJ.ID == 'null' || item.IN_EXPENSE_CORP_PROJ.ID == undefined || item.IN_EXPENSE_CORP_PROJ.ID == '') ? '-00000' : '-' + item.IN_EXPENSE_CORP_PROJ.ID;
					if(parent != 'enable'){
						this.disabledItems[index].IN_EXPENSE_GL_CODE = GLCode;
					}
					else{
						this.items[index].IN_EXPENSE_GL_CODE = GLCode;
					}
				},
				onChangeProj(parent,index, value){
					let rowItem = [];
					if(parent != 'enable'){
						rowItem = this.disabledItems[index];
					}
					else{
						rowItem = this.items[index];
					}
					this.generateGLCode(parent,index,rowItem);
				},
				onChangeAccount(parent,index, value){
					let rowItem = [];
					if(parent != 'enable'){
						rowItem = this.disabledItems[index];
					}
					else{
						rowItem = this.items[index];
					}
					if(this.isValidValue(value)){
						rowItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid = true;
						rowItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.messages = {};
					} else {
						rowItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid = this.isValidValue(value);
						rowItem.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.messages = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					}
					this.areAllValid();
					this.generateGLCode(parent,index,rowItem);
				},
				onChangeActivity(parent,index, value){
					let item = [];
					if(parent != 'enable'){
						item = this.disabledItems[index];
					}
					else{
						item = this.items[index];
					}
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid = this.isValidValue(value);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.messages = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					this.areAllValid();
					this.generateGLCode(parent,index,item);
				},
				onChangeCorpEntity(index, value){
					//let item = this.items[index];
					let ruletype = (this.items[index]['IN_EXPENSE_NR'] == 'Recoverable') ? 'RECOVERABLE' : 'NON_RECOVERABLE';
					
					this.setBusinessRules(index, 'IN_EXPENSE_CORP_ENTITY', ruletype);
					this.items[index]['IN_EXPENSE_CORP_ENTITY'] = value;
					if(this.items[index]['IN_EXPENSE_VALIDATION']['IN_EXPENSE_CORP_ENTITY']['isRequired']){
						this.items[index]['IN_EXPENSE_VALIDATION']['IN_EXPENSE_CORP_ENTITY']['isValid'] = this.isValidValue(value);
						this.items[index]['IN_EXPENSE_VALIDATION']['IN_EXPENSE_CORP_ENTITY']['messages'] = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					} else {
						this.items[index]['IN_EXPENSE_VALIDATION']['IN_EXPENSE_CORP_ENTITY']['isValid'] = true;
						this.items[index]['IN_EXPENSE_VALIDATION']['IN_EXPENSE_CORP_ENTITY']['messages'] = {};
					}
					this.areAllValid();
				},
				onChangeDepartmet(parent,index, value){
					let item = [];
					if(parent != 'enable'){
						item = this.disabledItems[index];
					}
					else{
						item = this.items[index];
					}
					item['IN_EXPENSE_DEPARTMENT'] = value;
					if(item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_DEPARTMENT']['isRequired']){
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_DEPARTMENT']['isValid'] = this.isValidValue(value);
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_DEPARTMENT']['messages'] = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					} else {
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_DEPARTMENT']['isValid'] = true;
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_DEPARTMENT']['messages'] = {};
					}
					this.areAllValid();
					this.generateGLCode(parent,index,item);
				},
				onChangeOffice(parent,index, value){
					let item = [];
					if(parent != 'enable'){
						item = this.disabledItems[index];
					}
					else{
						item = this.items[index];
					}
					item['IN_EXPENSE_OFFICE'] = value;
					if(item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_OFFICE']['isRequired']){
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_OFFICE']['isValid'] = this.isValidValue(value);
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_OFFICE']['messages'] = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					} else {
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_OFFICE']['isValid'] = true;
						item['IN_EXPENSE_VALIDATION']['IN_EXPENSE_OFFICE']['messages'] = {};
					}
					this.areAllValid();
					this.generateGLCode(parent,index,item);
				},
				isValidValue(value){
					if (value == undefined || value == null || value == '' || value == [] || value == {}) {
						return false;
					}
					return true;
				},
				generateSecureUID() {
					const array = new Uint8Array(16);
					crypto.getRandomValues(array);

					// Convert to UUIDv4 format
					array[6] = (array[6] & 0x0f) | 0x40; // Ensure the version is 4
					array[8] = (array[8] & 0x3f) | 0x80; // Ensure the variant is RFC4122

					return [...array]
						.map((b, i) =>
						[4, 6, 8, 10].includes(i) ? '-' + b.toString(16).padStart(2, '0') : b.toString(16).padStart(2, '0')
						)
						.join('');
				},
				getComanyId(){
					setTimeout(() => {
						let idCompany = window.parent.document.querySelector('[selector=\"COPY_COMP_ID\"] > div > input').value;
						this.companyId = idCompany;
						//1st grid
						this.disabledItems.forEach((item, index) => {
							this.generateGLCode('disable',index,item);
						});
						//2nd grid
						this.items.forEach((item, index) => {
							this.generateGLCode('enable',index,item);
						});
					}, '500');
				},
				fixDiscrepancy(){
					this.isDiscrepancyMode = true;
					this.fixDiscrepancyError = false;
					
					let itemsSize = this.items.length;
					let allPretaxValid = true;
					let fixedPreTax = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT']) + this.cleanNumber(this.DISCREPANCY_IN_TOTAL_PRE_TAX, true);
					let fixedHst = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_HST']) + this.cleanNumber(this.DISCREPANCY_IN_TOTAL_TAX, true);
					let fixedTotal = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_TOTAL']) + this.cleanNumber(this.DISCREPANCY_IN_SUM_TOTAL, true);
					let fixedPercentangeTotal = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_PERCENTAGE_TOTAL']) + this.cleanNumber(this.DISCREPANCY_IN_PERCENTAGE, true);

					this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT'] = this.formatNumber(fixedPreTax);
					this.items[itemsSize-1]['IN_EXPENSE_HST'] = this.formatNumber(fixedHst);
					this.items[itemsSize-1]['IN_EXPENSE_TOTAL'] = this.formatNumber(fixedTotal);
					this.items[itemsSize-1]['IN_EXPENSE_PERCENTAGE_TOTAL'] = this.formatPercentageNumber(fixedPercentangeTotal);

					/*if (
						this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT'] < 0 ||
						//this.items[itemsSize-1]['IN_EXPENSE_HST'] < 0 ||
						this.items[itemsSize-1]['IN_EXPENSE_TOTAL'] < 0 ||
						this.items[itemsSize-1]['IN_EXPENSE_PERCENTAGE_TOTAL'] < 0
					) {
						this.fixDiscrepancyError = true
					}*/
					this.isValidAmounts = this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT'] > 0;


					let currentTotalFinalPreTax = 0;
					let currentTotalFinalHST = 0;
					let currentTotalFinalAmount = 0;
					let currentTotalFinalPercentage = 0;

					if(Array.isArray(this.items)){
						this.items.forEach(item => {
							currentTotalFinalPreTax += this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT, true);
							currentTotalFinalHST += this.cleanNumber(item.IN_EXPENSE_HST, true);
							currentTotalFinalAmount += this.cleanNumber(item.IN_EXPENSE_TOTAL, true);
							currentTotalFinalPercentage += this.cleanNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL, true);
							allPretaxValid = allPretaxValid && (this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) > 0);
						});
					}
					this.isValidAmounts = allPretaxValid;
					// Totals
					this.IN_TOTAL_PRE_TAX_AMOUNT = this.cleanNumber(currentTotalFinalPreTax, true).toFixed(2);
					this.IN_TOTAL_HST = this.cleanNumber(currentTotalFinalHST, true).toFixed(2);
					this.IN_TOTAL_TOTAL = this.cleanNumber(currentTotalFinalAmount, true).toFixed(2);
					this.IN_TOTAL_PERCENTAGE_TOTAL = this.cleanNumber((Math.round((currentTotalFinalPercentage + Number.EPSILON) * 100) / 100), true).toFixed(2);
					

					this.DISCREPANCY_IN_TOTAL_PRE_TAX = 0;
					this.DISCREPANCY_IN_TOTAL_TAX     = 0;
					this.DISCREPANCY_IN_SUM_TOTAL     = 0;
					this.DISCREPANCY_IN_PERCENTAGE    = 0;
				},
				getEditMode(){
					this.isDiscrepancyMode = false;
					let itemsSize = this.items.length
					this.onBlurPreTaxAmount(itemsSize - 1, 'IN_EXPENSE_PRETAX_AMOUNT');
				}
			},
			created() {
				this.getAccountList();
				this.getActivityRecList();
				this.getCorpProjList();
				this.getCorpEntityList();
				this.getExpenseDefault();
				this.getDepartmentList();
				this.getOfficeList();
			},
			mounted() {
				this.getInvoiceData();
			},
			computed: {
				prepareItems() {
					let newItems = [];
					if(Array.isArray(this.items)){
						this.items.forEach(item => {
							newItems.push({
								IN_EXPENSE_CASE_ID: item.IN_EXPENSE_CASE_ID,
								IN_EXPENSE_ROW_ID: item.IN_EXPENSE_ROW_ID, 
								IN_EXPENSE_ROW_NUMBER: item.IN_EXPENSE_ROW_NUMBER, 
								IN_EXPENSE_TEAM_ROW_INDEX: item.IN_EXPENSE_TEAM_ROW_INDEX,
								IN_EXPENSE_DESCRIPTION: item.IN_EXPENSE_DESCRIPTION,
								IN_EXPENSE_ACCOUNT: item.IN_EXPENSE_ACCOUNT,
								IN_EXPENSE_CORP_PROJ: item.IN_EXPENSE_CORP_PROJ,
								IN_EXPENSE_PRETAX_AMOUNT: this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT), 
								IN_EXPENSE_HST: this.cleanNumber(item.IN_EXPENSE_HST), 
								IN_EXPENSE_TOTAL: this.cleanNumber(item.IN_EXPENSE_TOTAL), 
								IN_EXPENSE_PERCENTAGE: this.cleanNumber(item.IN_EXPENSE_PERCENTAGE),
								IN_EXPENSE_PERCENTAGE_TOTAL: this.cleanNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL),
								IN_EXPENSE_NR: item.IN_EXPENSE_NR,
								IN_EXPENSE_TEAM_ROUTING: item.IN_EXPENSE_TEAM_ROUTING,
								IN_EXPENSE_PROJECT_DEAL: item.IN_EXPENSE_PROJECT_DEAL,
								IN_EXPENSE_FUND_MANAGER: item.IN_EXPENSE_FUND_MANAGER,
								IN_EXPENSE_MANDATE: item.IN_EXPENSE_MANDATE,
								IN_EXPENSE_ACTIVITY: item.IN_EXPENSE_ACTIVITY,
								IN_EXPENSE_CORP_ENTITY: item.IN_EXPENSE_CORP_ENTITY,
								IN_EXPENSE_OFFICE: item.IN_EXPENSE_OFFICE,
								IN_EXPENSE_DEPARTMENT: item.IN_EXPENSE_DEPARTMENT,
								// IN_EXPENSE_TRANSACTION_COMMENTS: item.IN_EXPENSE_TRANSACTION_COMMENTS,
								// IN_EXPENSE_OFFICE_ID: item.IN_EXPENSE_OFFICE_ID,
								// IN_EXPENSE_OFFICE_LABEL: item.IN_EXPENSE_OFFICE_LABEL,
								// IN_EXPENSE_DEPARTMENT_ID: item.IN_EXPENSE_DEPARTMENT_ID,
								// IN_EXPENSE_DEPARTMENT_LABEL: item.IN_EXPENSE_DEPARTMENT_LABEL,
								// IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION: item.IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION,
								// IN_EXPENSE_INVESTRAN_HST_DESCRIPTION: item.IN_EXPENSE_INVESTRAN_HST_DESCRIPTION,
								// IN_EXPENSE_COMPANY_ID: item.IN_EXPENSE_COMPANY_ID,
								// IN_EXPENSE_COMPANY_LABEL: item.IN_EXPENSE_COMPANY_LABEL,
								 IN_EXPENSE_GL_CODE: item.IN_EXPENSE_GL_CODE,
								IN_EXPENSE_VALIDATION: btoa(JSON.stringify(item.IN_EXPENSE_VALIDATION)),
								workaround: 'workaround'
							});
						});
					}
					return newItems;
				},
				prepareItemsDisabled(){
					let newItems = [];
					if(Array.isArray(this.disabledItems)){
						this.disabledItems.forEach(item => {
							newItems.push({
								IN_EXPENSE_CASE_ID: item.IN_EXPENSE_CASE_ID,
								IN_EXPENSE_ROW_ID: item.IN_EXPENSE_ROW_ID, 
								IN_EXPENSE_ROW_NUMBER: item.IN_EXPENSE_ROW_NUMBER, 
								IN_EXPENSE_TEAM_ROW_INDEX: item.IN_EXPENSE_TEAM_ROW_INDEX,
								IN_EXPENSE_DESCRIPTION: item.IN_EXPENSE_DESCRIPTION,
								IN_EXPENSE_ACCOUNT: item.IN_EXPENSE_ACCOUNT,
								IN_EXPENSE_CORP_PROJ: item.IN_EXPENSE_CORP_PROJ,
								IN_EXPENSE_PRETAX_AMOUNT: this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT), 
								IN_EXPENSE_HST: this.cleanNumber(item.IN_EXPENSE_HST), 
								IN_EXPENSE_TOTAL: this.cleanNumber(item.IN_EXPENSE_TOTAL), 
								IN_EXPENSE_PERCENTAGE: this.cleanNumber(item.IN_EXPENSE_PERCENTAGE),
								IN_EXPENSE_PERCENTAGE_TOTAL: this.cleanNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL),
								IN_EXPENSE_NR: item.IN_EXPENSE_NR,
								IN_EXPENSE_TEAM_ROUTING: item.IN_EXPENSE_TEAM_ROUTING,
								IN_EXPENSE_PROJECT_DEAL: item.IN_EXPENSE_PROJECT_DEAL,
								IN_EXPENSE_FUND_MANAGER: item.IN_EXPENSE_FUND_MANAGER,
								IN_EXPENSE_MANDATE: item.IN_EXPENSE_MANDATE,
								IN_EXPENSE_ACTIVITY: item.IN_EXPENSE_ACTIVITY,
								IN_EXPENSE_CORP_ENTITY: item.IN_EXPENSE_CORP_ENTITY,
								IN_EXPENSE_OFFICE: item.IN_EXPENSE_OFFICE,
								IN_EXPENSE_DEPARTMENT: item.IN_EXPENSE_DEPARTMENT,
								// IN_EXPENSE_TRANSACTION_COMMENTS: item.IN_EXPENSE_TRANSACTION_COMMENTS,
								// IN_EXPENSE_OFFICE_ID: item.IN_EXPENSE_OFFICE_ID,
								// IN_EXPENSE_OFFICE_LABEL: item.IN_EXPENSE_OFFICE_LABEL,
								// IN_EXPENSE_DEPARTMENT_ID: item.IN_EXPENSE_DEPARTMENT_ID,
								// IN_EXPENSE_DEPARTMENT_LABEL: item.IN_EXPENSE_DEPARTMENT_LABEL,
								// IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION: item.IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION,
								// IN_EXPENSE_INVESTRAN_HST_DESCRIPTION: item.IN_EXPENSE_INVESTRAN_HST_DESCRIPTION,
								// IN_EXPENSE_COMPANY_ID: item.IN_EXPENSE_COMPANY_ID,
								// IN_EXPENSE_COMPANY_LABEL: item.IN_EXPENSE_COMPANY_LABEL,
								IN_EXPENSE_GL_CODE: item.IN_EXPENSE_GL_CODE,
								IN_EXPENSE_VALIDATION: btoa(JSON.stringify(item.IN_EXPENSE_VALIDATION)),
								workaround: 'workaround'
							});
						});
					}
					return newItems;
				},
				isValidCustomeGrid() {
					let allElementsValid = true;
					allElementsValid = (
						this.isGridValid && 
						!this.gridError.error && 
						!this.fixDiscrepancyError && 
						//this.isValidAmounts &&
						this.DISCREPANCY_IN_TOTAL_PRE_TAX == 0 &&
						this.DISCREPANCY_IN_TOTAL_TAX == 0 &&
						this.DISCREPANCY_IN_SUM_TOTAL == 0 
						//&& this.DISCREPANCY_IN_PERCENTAGE == 0
					);
					//const parentButton = window.parent.document.getElementById('parentButton');
					const parentButton = window.parent.document.querySelector('[selector=\"auxiliarGridValidation\"] > div > button');
					//parentButton.click();
					if (parentButton) {
						parentButton.click(); // Simular un clic en el botón
					} else {
						console.error('No se encontró el botón en la ventana padre.');
					}
					//window.parent.document.querySelector('[selector=\"auxiliarGridValidation\"] > div > button').click();
					
					return allElementsValid;
				}
			}
		});
	</script>
</body>
</html>";

return [
		'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];


/*function getMandateList($collectionId, $apiUrl, $assetLabel) {
	//return [$collectionId, $apiUrl, $assetLabel];
	$query  = '';
	$query .= "SELECT data->>'$.MANDATE_SYSTEM_ID_ACTG' AS ID, ";
	$query .= "data->>'$.MANDATE_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS MANDATE ";
	$query .= "WHERE MANDATE.data->>'$.MANDATE_STATUS' = 'Active' ";
	$query .= empty($assetLabel) ? "" : "AND MANDATE.data->>'$.MANDATE_ASSETCLASS' = '" . $assetLabel . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/
/*
 * Get Northleaf Invoice Expense Enabled rows
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
/*function getEnabledRows($apiUrl, $requestId, $task){
	$query  = "";
	$query .= "SELECT * ";
	$query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
    $query .= "AND IN_EXPENSE_TEAM_ROUTING_ID = '" . $task . "'";
	$query .= " ORDER BY IN_EXPENSE_ROW_NUMBER  + 0";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/
/*
 * Get Northleaf Invoice Expense Disabled rows
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
/*function getDisabledRows($apiUrl, $requestId, $task){
	$query  = "";
	$query .= "SELECT * ";
	$query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
	$query .= "AND IN_EXPENSE_TEAM_ROUTING_ID != '" . $task . "'";
	$query .= "ORDER BY IN_EXPENSE_ROW_NUMBER  + 0";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}*/


/*
 * Format Expense Data lo load into grid
 * @param (Array) $expenseList
 * @return (Array) $newExpense - Formated array
 *
 * by Jhon Chacolla
 */
/*function transformData($expenseList,$depAndOfficeData) {
	if($depAndOfficeData){
		$depAndOfficeData = $depAndOfficeData[0];
		if(isset($depAndOfficeData['DEPARMENT_ID']) AND $depAndOfficeData['DEPARMENT_NAME']){
			$submitterDep = [
				"ID" => str_replace('"', '', stripslashes($depAndOfficeData['DEPARMENT_ID'])),
				"LABEL" => str_replace('"', '', stripslashes($depAndOfficeData['DEPARMENT_NAME']))

			];
		}
		if(isset($depAndOfficeData['OFFICE_ID']) AND $depAndOfficeData['OFFICE_NAME']){
			$submitterOffice = [
				"ID" => str_replace('"', '', stripslashes($depAndOfficeData['OFFICE_ID'])),
				"LABEL" => str_replace('"', '', stripslashes($depAndOfficeData['OFFICE_NAME']))

			];
		}
	}
	$newExpense = [];
	foreach($expenseList as $key => $expense){
		$submitterOfficeRow = null;
		$submitterDepRow = null;
		if(!empty($expense['IN_EXPENSE_OFFICE_ID']) AND !empty($expense['IN_EXPENSE_OFFICE_LABEL'])){
			$submitterOfficeRow = [
				"ID" => $expense['IN_EXPENSE_OFFICE_ID'],
				"LABEL" => $expense['IN_EXPENSE_OFFICE_LABEL']
			];
		}
		if(!empty($expense['IN_EXPENSE_DEPARTMENT_ID']) AND !empty($expense['IN_EXPENSE_DEPARTMENT_LABEL'])){
			$submitterDepRow = [
				"ID" => $expense['IN_EXPENSE_DEPARTMENT_ID'],
				"LABEL" => $expense['IN_EXPENSE_DEPARTMENT_LABEL']

			];
		}
		$expenseAccount = empty($expense['IN_EXPENSE_ACCOUNT_ID']) ? null : ['ID' => $expense['IN_EXPENSE_ACCOUNT_ID'], 'LABEL' => $expense['IN_EXPENSE_ACCOUNT_LABEL']];
		$expenseCorpProj = empty($expense['IN_EXPENSE_CORP_PROJ_ID']) ? null : ['ID' => $expense['IN_EXPENSE_CORP_PROJ_ID'], 'LABEL' => $expense['IN_EXPENSE_CORP_PROJ_LABEL']];
		$expenseTeamRouting = empty($expense['IN_EXPENSE_TEAM_ROUTING_ID']) ? null : ['ID' => $expense['IN_EXPENSE_TEAM_ROUTING_ID'], 'LABEL' => $expense['IN_EXPENSE_TEAM_ROUTING_LABEL']];
		$expenseProjectDeal = empty($expense['IN_EXPENSE_PROJECT_DEAL_ID']) ? null : ['ID' => $expense['IN_EXPENSE_PROJECT_DEAL_ID'], 'LABEL' => $expense['IN_EXPENSE_PROJECT_DEAL_LABEL']];
		$expensefundManager = empty($expense['IN_EXPENSE_FUND_MANAGER_ID']) ? null : ['ID' => $expense['IN_EXPENSE_FUND_MANAGER_ID'], 'LABEL' => $expense['IN_EXPENSE_FUND_MANAGER_LABEL']];
		$expenseMandate = empty($expense['IN_EXPENSE_MANDATE_ID']) ? null : ['ID' => $expense['IN_EXPENSE_MANDATE_ID'], 'LABEL' => $expense['IN_EXPENSE_MANDATE_LABEL']];
		$expenseActivity = empty($expense['IN_EXPENSE_ACTIVITY_ID']) ? null : ['ID' => $expense['IN_EXPENSE_ACTIVITY_ID'], 'LABEL' => $expense['IN_EXPENSE_ACTIVITY_LABEL']];
		$expenseCorpEntity = empty($expense['IN_EXPENSE_CORP_ENTITY_ID']) ? null : ['ID' => $expense['IN_EXPENSE_CORP_ENTITY_ID'], 'LABEL' => $expense['IN_EXPENSE_CORP_ENTITY_LABEL']];
		$expenseOffice = empty($expense['IN_EXPENSE_OFFICE_ID']) ? null : ['ID' => $expense['IN_EXPENSE_OFFICE_ID'], 'LABEL' => $expense['IN_EXPENSE_OFFICE_LABEL']];
		$expenseDepartment = empty($expense['IN_EXPENSE_DEPARTMENT_ID']) ? null : ['ID' => $expense['IN_EXPENSE_DEPARTMENT_ID'], 'LABEL' => $expense['IN_EXPENSE_DEPARTMENT_LABEL']];
		$expenseCompany = empty($expense['IN_EXPENSE_COMPANY_ID']) ? null : ['ID' => $expense['IN_EXPENSE_COMPANY_ID'], 'LABEL' => $expense['IN_EXPENSE_COMPANY_LABEL']];
		$expenseCompany = empty($expense['IN_EXPENSE_COMPANY_ID']) ? null : ['ID' => $expense['IN_EXPENSE_COMPANY_ID'], 'LABEL' => $expense['IN_EXPENSE_COMPANY_LABEL']];
		$expenseValidation = json_decode(base64_decode($expense['IN_EXPENSE_VALIDATION']), true);

		$newExpense[] = [
			'IN_EXPENSE_CASE_ID' => $expense['IN_EXPENSE_CASE_ID'],
			'IN_EXPENSE_ROW_ID' => $expense['IN_EXPENSE_ROW_ID'],
			'IN_EXPENSE_ROW_NUMBER' => $expense['IN_EXPENSE_ROW_NUMBER'],
			'IN_EXPENSE_TEAM_ROW_INDEX' => $expense['IN_EXPENSE_TEAM_ROW_INDEX'],
			'IN_EXPENSE_DESCRIPTION' => $expense['IN_EXPENSE_DESCRIPTION'],
			'IN_EXPENSE_ACCOUNT' => $expenseAccount,
			'IN_EXPENSE_CORP_PROJ' => $expenseCorpProj,
			'IN_EXPENSE_PRETAX_AMOUNT' => empty($expense['IN_EXPENSE_PRETAX_AMOUNT']) ? 0 : stringToFloat($expense['IN_EXPENSE_PRETAX_AMOUNT']),
			'IN_EXPENSE_HST' => empty($expense['IN_EXPENSE_HST']) ? 0 : stringToFloat($expense['IN_EXPENSE_HST']),
			'IN_EXPENSE_TOTAL' => empty($expense['IN_EXPENSE_TOTAL']) ? 0 : stringToFloat($expense['IN_EXPENSE_TOTAL']),
			'IN_EXPENSE_PERCENTAGE' => empty($expense['IN_EXPENSE_PERCENTAGE']) ? 0 : stringToFloat($expense['IN_EXPENSE_PERCENTAGE']),
			'IN_EXPENSE_PERCENTAGE_TOTAL' => empty($expense['IN_EXPENSE_PERCENTAGE_TOTAL']) ? 0 : stringToFloat($expense['IN_EXPENSE_PERCENTAGE_TOTAL']),
			'IN_EXPENSE_NR' => $expense['IN_EXPENSE_NR_LABEL'],
			'IN_EXPENSE_TEAM_ROUTING' => $expenseTeamRouting,
			'IN_EXPENSE_PROJECT_DEAL' => $expenseProjectDeal,
			'IN_EXPENSE_FUND_MANAGER' => $expensefundManager,
			'IN_EXPENSE_MANDATE' => $expenseMandate,
			'IN_EXPENSE_ACTIVITY' => $expenseActivity,
			'IN_EXPENSE_CORP_ENTITY' => $expenseCorpEntity,
			'IN_EXPENSE_TRANSACTION_COMMENTS' => $expense['IN_EXPENSE_TRANSACTION_COMMENTS'],
			//'IN_EXPENSE_OFFICE' => $expenseOffice,
			//'IN_EXPENSE_DEPARTMENT' => $expenseDepartment,
			'IN_EXPENSE_OFFICE' => (isset($submitterOfficeRow)) ? $submitterOfficeRow : $submitterOffice,
			'IN_EXPENSE_DEPARTMENT' => (isset($submitterDepRow)) ? $submitterDepRow : $submitterDep,
			'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION' => $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'],
			'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION' => $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'],
			'IN_EXPENSE_COMPANY' => $expenseCompany,
			'IN_EXPENSE_GL_CODE' => $expense['IN_EXPENSE_GL_CODE'],
			"IN_EXPENSE_VALIDATION" => $expenseValidation

			
		];
	}
	return $newExpense;
}*/


function stringToFloat($string) {
	if(empty($string)){
		return 0;
	}
    // Remove commas and convert to float
    $float = floatval(str_replace(',', '', $string));
    // Format to two decimal places and return as float
	return empty($float) ? 0 : $float ;
}