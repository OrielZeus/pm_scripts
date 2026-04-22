<?php 
/*  
 *  POC - Custom Grid
 *
 *  by Telmo Chiri
 *  modify by Jhon Chacolla
 */
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables


$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
//$collectionmaster = getCollectionIdMaster2(['IN_ASSET_CLASS', 'IN_DEAL', 'IN_EXPENSE_FUND_MANAGER', 'IN_EXPENSE_ACTIVITY', 'IN_EXPENSE_CORP_ENTITY', 'IN_EXPENSE_MANDATES'], $apiUrl, 'IN_MASTER_COLLECTION_ID');

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

$itemsData = getAllExpenseByRequest ($apiUrl, $requestId);
$items = transformData($itemsData);

$discrepancyAmount = $data['IN_INVOICE_DISCREPANCY'];
$isDiscrepancyMode = 'false';


// Get dropdowns data source
$accountList = getExpenseAccount(getCollectionId('IN_EXPENSE_ACCOUNT', $apiUrl), $apiUrl);
$activityRecList = getActivityList(getCollectionId('IN_EXPENSE_ACTIVITY', $apiUrl), $apiUrl, 'Recoverable');

$activityNoRecList = getActivityList(getCollectionId('IN_EXPENSE_ACTIVITY', $apiUrl), $apiUrl, 'Non-Recoverable');
$teamRoutingRecList = getTeamRouting(getCollectionId('IN_ASSET_CLASS', $apiUrl), $apiUrl, 'Recoverable');
$teamRoutingNoRecList = getTeamRouting(getCollectionId('IN_ASSET_CLASS', $apiUrl), $apiUrl, 'Non-Recoverable');

$teamRoutingFullList = getTeamRouting(getCollectionId('IN_ASSET_CLASS', $apiUrl), $apiUrl, '');
$teamRoutingFullLabel = array_column($teamRoutingFullList, 'LABEL');

$mandateList = [];
$fundManagerList = [];
$dealList = [];

foreach($teamRoutingFullLabel as $key => $team){
	// Get Deal List
	$dealList[$team] = getDealList(getCollectionId('IN_DEAL', $apiUrl), $apiUrl, $team);
	// Get Fund Manager List
	$fundManagerList[$team] = getFundManagerList(getCollectionId('IN_EXPENSE_FUND_MANAGER', $apiUrl), $apiUrl, $team);
	// Get MandateList
	switch($team){
		case 'Infrastructure':
			$mandateList[$team] = getMandateList(getCollectionId('IN_EXPENSE_MANDATES', $apiUrl), $apiUrl, 'INFRA');
			break;
		case 'Private Credit':
			$mandateList[$team] = getMandateList(getCollectionId('IN_EXPENSE_MANDATES', $apiUrl), $apiUrl, 'PC');
			break;
		case 'Private Equity':
			$mandateList[$team] = getMandateList(getCollectionId('IN_EXPENSE_MANDATES', $apiUrl), $apiUrl, 'PE');
			break;
		case 'Corporate':
			$mandateList[$team] = getMandateList(getCollectionId('IN_EXPENSE_MANDATES', $apiUrl), $apiUrl, 'CORP');
			break;
	}
}

$corpEntityList = getCorpEntityList(getCollectionId('IN_EXPENSE_CORP_ENTITY', $apiUrl), $apiUrl);
$corpProjList = getExpenseCorpProject(getCollectionId('IN_EXPENSE_CORP_PROJ', $apiUrl), $apiUrl);
$expenseDefaultRules = getExpenseDefault(getCollectionId('IN_EXPENSE_DEFAULTS', $apiUrl), $apiUrl);
//return $expenseDefaultRules;

//Generate table header
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

	<style>
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
		.multiselect.is-invalid-field>.multiselect__tags {
			border: 1px solid #E50130;
		}
		[v-cloak] { display: none; }
	</style>
</head>
<body>
	<div id="app">
		<div v-cloak>
			<!-- Componente BootstrapVue -->
			<div v-if="items.length > 0">
				<!-- <h2>Dinamic Table</h2> -->
				<div class="table-responsive" id="iframe-table-container" style="max-height: 800px;">
						<!-- <p>IN_INVOICE_PRE_TAX: {{IN_INVOICE_PRE_TAX}}</p>
						<p>IN_INVOICE_TAX_TOTAL: {{IN_INVOICE_TAX_TOTAL}}</p>
						<p>IN_INVOICE_TOTAL: {{IN_INVOICE_TOTAL}}</p>
						<p>IN_INVOICE_PRE_TAX_PERCENTAGE: {{IN_INVOICE_PRE_TAX_PERCENTAGE}}</p>
						<p>IN_INVOICE_TAX_TOTAL_PERCENTAGE: {{IN_INVOICE_TAX_TOTAL_PERCENTAGE}}</p>
						<p>IN_INVOICE_TOTAL_PERCENTAGE: {{IN_INVOICE_TOTAL_PERCENTAGE}}</p>
						<p>IN_INVOICE_CURRENCY: {{IN_INVOICE_CURRENCY}}</p> -->
					<table class="table table-bordered table-striped w-auto mb-4">
						<thead class="thead-light">
							<tr class="">
								<th></th>
								<th></th>
								<th></th>
								<th style="width:200px"></th>
								<th style="width:200px"></th>
								<th style="width:200px"></th>
								<th class="pm-bg-primary pm-text-white text-center">
									{{ IN_INVOICE_CURRENCY }}
									<!-- <p class="text-info small">IN_INVOICE_CURRENCY</p> -->
								</th>
								<th class="pm-bg-primary pm-text-white text-center">
									{{ formatNumber(IN_INVOICE_PRE_TAX) }}
									<!-- <p class="text-info small">IN_INVOICE_PRE_TAX</p> -->
								</th>
								<th class="pm-bg-primary pm-text-white text-center">
									{{ formatNumber(IN_INVOICE_TAX_TOTAL) }}
									<!-- <p class="text-info small">IN_INVOICE_TAX_TOTAL</p> -->
								</th>
								<th class="pm-bg-primary pm-text-white text-center">
									{{ formatNumber(IN_INVOICE_TOTAL) }}
									<!-- <p class="text-info small">IN_INVOICE_TOTAL</p> -->
								</th>
								<th></th>
								<th style="width:200px">
									<!-- <p class="text-info small">IN_INVOICE_TAX_TOTAL_PERCENTAGE: {{IN_INVOICE_TAX_TOTAL_PERCENTAGE}}</p> -->
								</th>
								<th style="width:200px">
									<!-- <p class="text-info small">IN_INVOICE_PRE_TAX_PERCENTAGE: {{IN_INVOICE_PRE_TAX_PERCENTAGE}}</p> -->
								</th>
								<th style="width:200px">
									<!-- <p class="text-info small">IN_INVOICE_TOTAL_PERCENTAGE: {{IN_INVOICE_TOTAL_PERCENTAGE}}</p> -->
								</th>
								<th style="width:200px"></th>
								<th style="width:200px"></th>
								<th style="width:200px"></th>
							</tr>
							<tr class="head-table">
								<th></th>
								<th></th>
								<th>#</th>
								<th style="width:200px">Expense Description</th>
								<th style="width:200px">Non-Rec / Rec</th>
								<th style="width:200px">Team Routing/Asset Class</th>
								<th style="width:200px">Account</th>
								<th>Pre Tax Amount</th>
								<th>Tax Amount</th>
								<th>Total Amount</th>
								<th>% of Total Invoice Amount</th>
								<th style="width:200px">Corporate Project</th>
								<th style="width:200px">Deal</th>
								<th style="width:200px">Fund Manager</th>
								<th style="width:200px">Mandate</th>
								<th style="width:200px">Activity</th>
								<th style="width:200px">Corporate Entity</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(item, index) in items" :key="item.IN_EXPENSE_ROW_NUMBER" 
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
								cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) > 0)
								}"
							>
								<td>
									<|button class="btn btn-danger btn-sm" @click="deleteRow(index)"><span class="fa fa-trash" style="font-size:150%;"></|button>
								</td>
								<td>
									<|button class="btn btn-secondary btn-sm" @click="splitRow(index)"><span class="fa fa-code-fork" style="font-size:150%;"></|button>
								</td>
								<td>{{ index + 1 }}</td>
								<td style="min-width:300px">
										<!-- :class="{\'form-control\':true, \'is-invalid\': !(item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid) }" -->
									<|textarea 
										v-model="item.IN_EXPENSE_DESCRIPTION"
										class="form-control"
										:disabled="viewMode" 
										name="" 
										rows="1" 
										cols="10"
										:maxlength="descriptionLimit"
									>
									</|textarea>
									<!-- <p class="text-danger">{{item.IN_EXPENSE_ROW_NUMBER}}</p> -->
									<!-- <p class="text-danger">{{item.IN_EXPENSE_TEAM_ROW_INDEX}}</p> -->
									<!-- <p class="text-danger">{{item.IN_EXPENSE_CASE_ID}}</p> -->
									<!-- <p class="text-info">{{item.IN_EXPENSE_ROW_ID}}</p> -->
								</td>
								<td style="min-width:200px">
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
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid}"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages"></error-message>
									</p>
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
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_TEAM_ROUTING.isDisabled"
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
										@input="onChangeAccount(index, $event)"
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
										:class="{\'form-control\':true, \'is-invalid\': cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) <= 0}"
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
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isDisabled"
										:class="{\'is-invalid-field\': item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid !== true}"
										@input="updateDeal(index, $event)"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_PROJECT_DEAL.messages"></error-message>
									</p>
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
										:disabled="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isDisabled"
										:class="{\'is-invalid-field\': !item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid}"
										@input="updateFundManager(index, $event)"
									>
									</multiselect>
									<p v-if="!item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.isValid">
										<error-message :messageslist="item.IN_EXPENSE_VALIDATION.IN_EXPENSE_FUND_MANAGER.messages"></error-message>
									</p>
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
										@input="onChangeActivity(index, $event)"
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
							</tr>
						</tbody>
						<tfoot class="tfoot-light">
							<tr>
								<td colspan="6"></td>
								<td><strong>Total</strong></td>
								<td class="matching" 
									v-bind:class="cleanNumber(IN_INVOICE_PRE_TAX) == cleanNumber(IN_TOTAL_PRE_TAX_AMOUNT) ? \'equal\' : \'different\'"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatNumber(IN_TOTAL_PRE_TAX_AMOUNT) }}
									<!-- <p class="text-info small">IN_TOTAL_PRE_TAX_AMOUNT: {{cleanNumber(IN_INVOICE_PRE_TAX)}} == {{cleanNumber(IN_TOTAL_PRE_TAX_AMOUNT)}}</p> -->
								</td>
								<td class="matching" 
									v-bind:class="cleanNumber(IN_INVOICE_TAX_TOTAL) == cleanNumber(IN_TOTAL_HST) ? \'equal\' : \'different\'"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatNumber(IN_TOTAL_HST) }}
									<!-- <p class="text-info small">IN_TOTAL_HST: {{cleanNumber(IN_INVOICE_TAX_TOTAL)}} == {{cleanNumber(IN_TOTAL_HST)}}</p> -->
								</td>
								<td class="matching" 
									v-bind:class="cleanNumber(IN_INVOICE_TOTAL) == cleanNumber(IN_TOTAL_TOTAL) ? \'equal\' : \'different\'"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatNumber(IN_TOTAL_TOTAL) }}
									<|input v-show="false" v-model="cleanNumber(IN_TOTAL_TOTAL)" id="iframe-totalFinalAmount" class="form-control">
									<!-- <p class="text-info small">IN_TOTAL_TOTAL: {{cleanNumber(IN_INVOICE_TOTAL)}} == {{cleanNumber(IN_TOTAL_TOTAL)}}</p> -->
								</td>
								<td :class="{matching:true, equal: IN_TOTAL_PERCENTAGE_TOTAL==100, different: IN_TOTAL_PERCENTAGE_TOTAL!=100}"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatPercentageNumber(IN_TOTAL_PERCENTAGE_TOTAL) }}
									<!-- <p class="text-info small">IN_TOTAL_PERCENTAGE_TOTAL: {{IN_TOTAL_PERCENTAGE_TOTAL}}</p> -->
								</td>
								<td colspan="6"></td>
							</tr>
							<tr v-if="IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT != 0 || IN_OUTSTANDING_TOTAL_HST != 0 || IN_OUTSTANDING_TOTAL_HST != 0 || IN_OUTSTANDING_TOTAL != 0 ">
								<td colspan="6"></td>
								<td class="text-danger"><strong>Discrepancy</strong></td>
								<td class="matching"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatNumber(IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT) }}
									<!-- <|input v-show="false" v-model="IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT" id="iframe-outstandigTotal" class="form-control"> -->
									<!-- <p class="text-info small">IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT:  {{IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT}}</p> -->
								</td>
								<td class="matching"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatNumber(IN_OUTSTANDING_TOTAL_HST) }}
									<!-- <|input v-show="false" v-model="IN_OUTSTANDING_TOTAL_HST" id="iframe-outstandigTotal" class="form-control"> -->
									<!-- <p class="text-info small">IN_OUTSTANDING_TOTAL_HST:  {{IN_OUTSTANDING_TOTAL_HST}}</p> -->
								</td>
								<td class="matching"
									style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatNumber(IN_OUTSTANDING_TOTAL) }}
									<!-- <|input v-show="false" v-model="IN_OUTSTANDING_TOTAL" id="iframe-outstandigTotal" class="form-control"> -->
									<!-- <p class="text-info small">IN_OUTSTANDING_TOTAL:  {{IN_OUTSTANDING_TOTAL}}</p> -->
								</td>
								<td class="matching" style="text-align: right;padding-right: 1.3em;"
								>
									{{ formatPercentageNumber(IN_OUTSTANDING_PERCENTAGE) }}
									<!-- <|input v-show="false" v-model="IN_OUTSTANDING_PERCENTAGE" id="iframe-outstandigPercentage" class="form-control"> -->
									<!-- <p class="text-info small">IN_OUTSTANDING_PERCENTAGE: {{IN_OUTSTANDING_PERCENTAGE}}</p> -->
								</td>
								<td colspan="6"></td>
							</tr>
						</tfoot>
					</table>
				</div>
				<!-- Hidden fields-->
				<div style="display:none;">
					<|input id="iframe-items" v-model="JSON.stringify(prepareItems)" class="form-control" readonly  v-show="false">
										
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
			<div class="alert alert-warning" role="alert" v-if="items.length <= 0">
				No records found. To add a new record, please click the "Add Item" button.
			</div>
			<div class="alert alert-danger" role="alert" v-if="!isGridValid || gridError.error || fixDiscrepancyError || !isValidAmounts || IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT != 0 || IN_OUTSTANDING_TOTAL_HST != 0 || IN_OUTSTANDING_TOTAL != 0 || IN_OUTSTANDING_PERCENTAGE != 0">
				<ul>
					<li v-if="fixDiscrepancyError">Please you need to fix the discrepancy error, return to edit mode.</li>
					<li v-if="!isValidAmounts">All pre-tax amount values must be greater than zero.</li>
					<li v-if="!isGridValid">There are missing some required field on the Expense Table.</li>
					<li v-if="IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT != 0">The Total Pre Tax Amount does not match the Invoice Pre Tax Amount.</li>
					<li v-if="IN_OUTSTANDING_TOTAL_HST != 0">The Tax Amount does not match the Invoice Tax (only HST/GST/VAT).</li>
					<li v-if="IN_OUTSTANDING_TOTAL != 0">The Total Amount does not match the Invoice Total Amount.</li>
					<li v-if="IN_OUTSTANDING_PERCENTAGE != 0">The Total Percentage must be equal to 100%.</li>
					
					<li v-for="message in gridError.messages">
						{{ message }}
					</li>
				</ul>
			</div>
			<div style="margin-bottom:50px;">
				<|button class="btn btn-dark" @click="addRow" v-if="!isDiscrepancyMode"><span class="fa fa-plus-circle"></span> Add Item</|button>
				<|button class="btn btn-success" @click="fixDiscrepancy" v-if="!isDiscrepancyMode && showDiscrepancyButton"><span class="fa fa-wrench"></span> Fix Discrepancy</|button>
				<|button class="btn btn-primary" @click="goEditMode" v-if="isDiscrepancyMode"><span class="fa fa-edit"></span> Edit Mode</|button>
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

				totalPercentage: 100,
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

				items: " . json_encode($items) . ",
				deletedRows: [],
				//Dropdown data sources
				IN_EXPENSE_ACCOUNT_VALUES: " . json_encode($accountList) . ",
				IN_EXPENSE_CORP_PROJ_VALUES: " . json_encode($activityRecList) . ", //missing collection


				teamRoutingRecList: " . json_encode($teamRoutingRecList) . ",
				teamRoutingNoRecList: " . json_encode($teamRoutingNoRecList) . ",

				dealList: " . json_encode($dealList) . ",
				fundManagerList: " . json_encode($fundManagerList) . ",
				mandateList: " . json_encode($mandateList) . ",
				corpProjList: " . json_encode($corpProjList) . ",

				IN_EXPENSE_NR_VALUES: ['Recoverable', 'Non-Recoverable'],
				IN_EXPENSE_CORP_ENTITY_VALUES: " . json_encode($corpEntityList) . ",
				activityRecList: "  . json_encode($activityRecList) . ",
				activityNoRecList: "  . json_encode($activityNoRecList) . ",
				expenseDefaultRules: "  . json_encode($expenseDefaultRules) . ",
				errorMessages: {
					'required': 'This field is required.',
					'stringLength': 'The content must not exceed 60 characters.',
					'atLeastOneRequired': 'You must select at least one of these fields.',
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
				descriptionLimit:255
			},
			methods: {
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
				evaluateRow(index) {
					let item = this.items[index];
        			// Limpiar los valores de comas antes del cálculo
					let preTax = this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
					//let hst = this.cleanNumber(item.IN_EXPENSE_HST);
					let taxTotalPercentage = this.cleanNumber(this.IN_INVOICE_TAX_TOTAL_PERCENTAGE);
					let invoicePreTaxPercentage = this.cleanNumber(this.IN_INVOICE_PRE_TAX_PERCENTAGE);

					// Calc IN_EXPENSE_HST => ((IN_EXPENSE_PRETAX_AMOUNT * IN_INVOICE_TAX_TOTAL_PERCENTAGE) / IN_INVOICE_PRE_TAX_PERCENTAGE)
					let hst = ((preTax * taxTotalPercentage) / invoicePreTaxPercentage);
					item.IN_EXPENSE_HST = isNaN(hst) ? '0.00' : this.formatNumber(hst);

					// Recalcular el porcentaje de la factura
					let itemPercentage = (parseFloat(this.cleanNumber(item.IN_EXPENSE_TOTAL)) * this.totalPercentage) / this.IN_INVOICE_TOTAL;
					//let rowPercentage = Math.round((itemPercentage + Number.EPSILON) * 100) / 100;
					//item.IN_EXPENSE_PERCENTAGE_TOTAL = this.formatNumber(rowPercentage);

					//item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION = this.isValidDescription(index, item.IN_EXPENSE_DESCRIPTION);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.isValid = this.isValidValue(item.IN_EXPENSE_NR);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_NR.messages.required = this.errorMessages.required;
					this.calculateFinalTotals();
				},
				isValidDescription(index, description) {
					const maxLength = 60;
					let item = this.items[index];
					let messages = [];
					let isValidValue = true;

					// Check if description is a string
					if (description == undefined || description == null || description == '') {
						isValidValue = false;
						messages.push('Description is required.');
					}
					// Check if description is null, undefined, or an empty string
					else if (!description.trim()) {
						isValidValue = false;
						messages.push('Description cannot be empty.');
					}
					// Check if description length exceeds max length
					else if (description.length > maxLength) {
						isValidValue = false;
						messages.push('Description must be ${maxLength} characters or less.');
					}

					//item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION.isValid
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_DESCRIPTION = {
						isValid: isValidValue,
						messages: messages
					};
				},
				addRow() {
					this.items.push(
						{
							IN_EXPENSE_CASE_ID: this.IN_REQUEST_ID,
        					IN_EXPENSE_ROW_ID: this.generateSecureUID(),
							IN_EXPENSE_ROW_NUMBER: this.items.length + 1,
							IN_EXPENSE_TEAM_ROW_INDEX: this.items.length + 1,
							IN_EXPENSE_NR: '',
							IN_EXPENSE_ACCOUNT: null,
							IN_EXPENSE_CORP_PROJ: null,
							IN_EXPENSE_MANDATE: '',
							IN_EXPENSE_PRETAX_AMOUNT: '0.00',
							IN_EXPENSE_HST: '0.00',
							IN_EXPENSE_TOTAL: '0.00',
							IN_EXPENSE_PERCENTAGE_TOTAL: '0.00%',
							IN_EXPENSE_NR: null,
							IN_EXPENSE_TEAM_ROUTING: null,
							IN_EXPENSE_PROJECT_DEAL: null,
							IN_EXPENSE_FUND_MANAGER: null,
							IN_EXPENSE_MANDATE: null,
							IN_EXPENSE_ACTIVITY: null,
							IN_EXPENSE_CORP_ENTITY: null,
							IN_EXPENSE_VALIDATION: {
								IN_EXPENSE_DESCRIPTION: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_ACCOUNT: { isValid: false, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} },
								IN_EXPENSE_CORP_PROJ: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_PRETAX_AMOUNT: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_HST: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_TOTAL: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_PERCENTAGE: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_PERCENTAGE_TOTAL: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_NR: { isValid: false, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} },
								IN_EXPENSE_TEAM_ROUTING: { isValid: false, isDisabled: false, isRequired: true, messages: {required: this.errorMessages.required} },
								IN_EXPENSE_PROJECT_DEAL: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_FUND_MANAGER: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_MANDATE: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_ACTIVITY: { isValid: true, isDisabled: false, isRequired: false, messages: {} },
								IN_EXPENSE_CORP_ENTITY: { isValid: true, isDisabled: false, isRequired: false, messages: {} }
							}
						}
					)
					this.calculateFinalTotals();
					this.areAllValid() ;
				},
				deleteRow(index) {
					this.deletedRows.push(this.items[index].IN_EXPENSE_ROW_ID);
					this.items.splice(index, 1);
					this.items = this.items.map((item, index) => ({ ...item, IN_EXPENSE_ROW_NUMBER: index+1, IN_EXPENSE_TEAM_ROW_INDEX: index + 1 }));
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
					clonedItem.IN_EXPENSE_PERCENTAGE_TOTAL = '0.00';
					// Workaround to break the dependency between the original and the copy
					let stringItem =(JSON.stringify(clonedItem));
					let decodedItem = JSON.parse(stringItem);
					
					// Insert the copy
					this.items.splice(index + 1, 0, decodedItem);
					
					// Adjust the row number
					this.items.forEach((item, idx) => {
						item.IN_EXPENSE_ROW_NUMBER = idx + 1;
						item.IN_EXPENSE_TEAM_ROW_INDEX = idx + 1;
					});
				},
				// Elimina comas de una cadena de texto antes de convertirla a número
				cleanNumber(value, isNegative = false) {
					if(!value){
						return 0;
					}
					let stringValue = value.toString();
					return isNegative ? parseFloat(stringValue.replace(/[%\,]/g, '')) : parseFloat(stringValue.replace(/[%\-,]/g, ''));
				},
				calculateFinalTotals() {
					let actualTotalFinalPreTax = 0;
					let actualTotalFinalHST = 0;
					let actualTotalFinalAmount = 0;
					let actualTotalFinalPercentage = 0;
					let allPretaxValid = true;
					
					if(Array.isArray(this.items)){
						this.items.forEach(item => {
							allPretaxValid = allPretaxValid && this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT) > 0;
							actualTotalFinalPreTax += this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
							actualTotalFinalHST += this.cleanNumber(item.IN_EXPENSE_HST);
							actualTotalFinalAmount += this.cleanNumber(item.IN_EXPENSE_TOTAL);
							actualTotalFinalPercentage += this.cleanNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL);
						});
					}

					this.IN_TOTAL_PRE_TAX_AMOUNT = this.cleanNumber(actualTotalFinalPreTax).toFixed(2);
					this.IN_TOTAL_HST = this.cleanNumber(actualTotalFinalHST).toFixed(2);
					this.IN_TOTAL_TOTAL = this.cleanNumber(actualTotalFinalAmount).toFixed(2);
					this.IN_TOTAL_PERCENTAGE_TOTAL = this.cleanNumber(Math.round((actualTotalFinalPercentage + Number.EPSILON) * 100) / 100).toFixed(2);

					// Outstanding
					this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT = (this.cleanNumber((this.cleanNumber(this.IN_INVOICE_PRE_TAX) - this.cleanNumber(this.IN_TOTAL_PRE_TAX_AMOUNT)), true)).toFixed(2);
					this.IN_OUTSTANDING_TOTAL_HST = (this.cleanNumber((this.cleanNumber(this.IN_INVOICE_TAX_TOTAL) - this.cleanNumber(this.IN_TOTAL_HST)), true)).toFixed(2);
					this.IN_OUTSTANDING_TOTAL = (this.cleanNumber((this.cleanNumber(this.IN_INVOICE_TOTAL) - this.cleanNumber(this.IN_TOTAL_TOTAL)), true)).toFixed(2);   
					this.IN_OUTSTANDING_PERCENTAGE = (this.cleanNumber((100 - this.cleanNumber(this.IN_TOTAL_PERCENTAGE_TOTAL)), true)).toFixed(2);

					let outStandingPreTax = Math.abs(this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT);
					let outStandingHst = Math.abs(this.IN_OUTSTANDING_TOTAL_HST);
					let outStandingTotal = Math.abs(this.IN_OUTSTANDING_TOTAL);
					let outStandingPercentage = Math.abs(this.IN_OUTSTANDING_PERCENTAGE);

					this.isValidAmounts = allPretaxValid;
					this.fixDiscrepancyError = false;
					this.showDiscrepancyButton = this.shouldShowButton(outStandingPreTax, outStandingHst, outStandingTotal, outStandingPercentage, this.discrepancyAmount) && allPretaxValid;
				},
				areAllValid() {
					let isRowValid = true;
					this.items.forEach(item => {
						Object.values(item.IN_EXPENSE_VALIDATION).forEach(validation => {
							if(validation != undefined){
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

						// Calc IN_EXPENSE_PERCENTAGE_TOTAL = ((IN_EXPENSE_TOTAL  *  IN_INVOICE_TOTAL_PERCENTAGE)  /  IN_INVOICE_TOTAL)
						let total = this.cleanNumber(item[field]);
						let invoiceTotalPercentage = this.cleanNumber(this.IN_INVOICE_TOTAL_PERCENTAGE);
						let invoiceTotal = this.cleanNumber(this.IN_INVOICE_TOTAL);
						let percentageTotal = ((total * invoiceTotalPercentage) / invoiceTotal);
						item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(percentageTotal) ? '0.00%' : this.formatPercentageNumber(percentageTotal);
						
						// Calc IN_EXPENSE_PRETAX_AMOUNT = ((IN_EXPENSE_PERCENTAGE_TOTAL  *  IN_INVOICE_PRE_TAX)  /  IN_INVOICE_TOTAL_PERCENTAGE) 
						let invoicePreTax = this.cleanNumber(this.IN_INVOICE_PRE_TAX);
						let pretaxAmount = ((percentageTotal * invoicePreTax) / invoiceTotalPercentage);
						item['IN_EXPENSE_PRETAX_AMOUNT'] = isNaN(pretaxAmount) ? '0.00' : this.formatNumber(pretaxAmount);

						item[field] = this.formatNumber(this.cleanNumber(item[field]));
						this.evaluateRow(index);
					} else {
						this.calculateFinalTotals();
					}
				},
				onBlurPercentageTotal(index, field) {
					if(!this.isDiscrepancyMode) {
						// Set format field on blur
						let item = this.items[index];

						// Get the previous values and get the percentage_total
						let prevpretax = this.cleanNumber(item['IN_EXPENSE_PRETAX_AMOUNT']);
						let prevTotal = this.cleanNumber(item['IN_EXPENSE_TOTAL']);
						let prevExpensePercentageTotal = (prevTotal * this.cleanNumber(this.IN_INVOICE_TOTAL_PERCENTAGE) / this.cleanNumber(this.IN_INVOICE_TOTAL));


						// Calc IN_EXPENSE_PRETAX_AMOUNT = ((IN_EXPENSE_PERCENTAGE_TOTAL  *  IN_INVOICE_PRE_TAX)  /  IN_INVOICE_TOTAL_PERCENTAGE)
						let percentageTotal = this.cleanNumber(item['IN_EXPENSE_PERCENTAGE_TOTAL']);
						let invoicePreTax = this.cleanNumber(this.IN_INVOICE_PRE_TAX);
						let invoiceTotalPercentage = this.cleanNumber(this.IN_INVOICE_TOTAL_PERCENTAGE);
						let preTaxAmount = ((percentageTotal * invoicePreTax) / invoiceTotalPercentage);
						//item['IN_EXPENSE_PRETAX_AMOUNT'] = isNaN(preTaxAmount) ? '0.00' : this.formatNumber(this.cleanNumber(preTaxAmount));

						// Calc IN_EXPENSE_TOTAL = ((IN_EXPENSE_PRETAX_AMOUNT  *  IN_INVOICE_TOTAL_PERCENTAGE)  /  IN_INVOICE_PRE_TAX_PERCENTAGE)
						//let preTaxAmountOld = this.cleanNumber(item['IN_EXPENSE_PRETAX_AMOUNT']);
						let invoicePreTaxPercentage = this.cleanNumber(this.IN_INVOICE_PRE_TAX_PERCENTAGE);
						let total = ((preTaxAmount * invoiceTotalPercentage)/ invoicePreTaxPercentage);
						//item['IN_EXPENSE_TOTAL'] = isNaN(total) ? '0.00' : this.formatNumber(total);


						// Calc the posible value of percentage_total
						// Temp cacl Calc IN_EXPENSE_PERCENTAGE_TOTAL = ((IN_EXPENSE_TOTAL  *  IN_INVOICE_TOTAL_PERCENTAGE)  /  IN_INVOICE_TOTAL)
						let tempExpensePercentageTotal = (total * invoiceTotalPercentage / this.cleanNumber(this.IN_INVOICE_TOTAL));

						// Replace values if the change represent a change in the precentage_total
						if (this.cleanNumber(prevExpensePercentageTotal.toFixed(2)) != this.cleanNumber(item[field])) {
							item['IN_EXPENSE_TOTAL'] = isNaN(total) ? '0.00' : this.formatNumber(total);
							item['IN_EXPENSE_PRETAX_AMOUNT'] = isNaN(preTaxAmount) ? '0.00' : this.formatNumber(this.cleanNumber(preTaxAmount));
						}
						
						item[field] = this.formatPercentageNumber(this.cleanNumber(item[field]));
						this.evaluateRow(index);
					} else {
						this.calculateFinalTotals();
					}
				},
				onBlurPreTaxAmount(index, field) {
					if(!this.isDiscrepancyMode) {
						// Set format field on blur
						let item = this.items[index];

						// Calc IN_EXPENSE_TOTAL = ((IN_EXPENSE_PRETAX_AMOUNT  *  IN_INVOICE_TOTAL_PERCENTAGE)  /  IN_INVOICE_PRE_TAX_PERCENTAGE)
						let pretaxAmount = this.cleanNumber(item[field]);
						let invoiceTotalPercentage = this.cleanNumber(this.IN_INVOICE_TOTAL_PERCENTAGE);
						let invoicePreTaxPercentage = this.cleanNumber(this.IN_INVOICE_PRE_TAX_PERCENTAGE);

						let total = ((pretaxAmount * invoiceTotalPercentage)/ invoicePreTaxPercentage);

						item['IN_EXPENSE_TOTAL'] = isNaN(total) ? '0.00' : this.formatNumber(total);

						// Calc IN_EXPENSE_PERCENTAGE_TOTAL = ((IN_EXPENSE_TOTAL  *  IN_INVOICE_TOTAL_PERCENTAGE)  /  IN_INVOICE_TOTAL)
						let invoiceTotal = this.cleanNumber(this.IN_INVOICE_TOTAL);
						let percentageTotal = ((total * invoiceTotalPercentage)) / invoiceTotal;
						item['IN_EXPENSE_PERCENTAGE_TOTAL'] = isNaN(percentageTotal) ? '0.00%' : this.formatPercentageNumber(percentageTotal);

						item[field] = this.formatNumber(this.cleanNumber(item[field]));
						this.evaluateRow(index);
						
					} else {
						this.calculateFinalTotals();
					}
				},
				onFieldBlur(index, field) {
					// Formatear el campo solo cuando se pierde el foco
					let item = this.items[index];
					item[field] = this.formatNumber(this.cleanNumber(item[field]));
					this.evaluateRow(index);  // recalcular totales después del formato
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
							item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT= { isValid: true, isDisabled: false, isRequired: false, messages: {} };
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
				onChangeAccount(index, value){
					let item = this.items[index];
					if(this.isValidValue(value)){
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid = true;
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.messages = {};
					} else {
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.isValid = this.isValidValue(value);
						item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACCOUNT.messages = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					}
					this.areAllValid();
				},
				onChangeActivity(index, value){
					let item = this.items[index];
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.isValid = this.isValidValue(value);
					item.IN_EXPENSE_VALIDATION.IN_EXPENSE_ACTIVITY.messages = this.isValidValue(value) ? {} : {required: this.errorMessages.required};
					this.areAllValid();
				},
				onChangeCorpEntity(index, value){
					let item = this.items[index];
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
				fixDiscrepancy(){
					this.isDiscrepancyMode = true;
					this.fixDiscrepancyError = false;
					
					let itemsSize = this.items.length;
					let allPretaxValid = true;

					let fixedPreTax = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT']) + this.cleanNumber(this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT, true);
					let fixedHst = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_HST']) + this.cleanNumber(this.IN_OUTSTANDING_TOTAL_HST, true);
					let fixedTotal = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_TOTAL']) + this.cleanNumber(this.IN_OUTSTANDING_TOTAL, true);
					let fixedPercentangeTotal = this.cleanNumber(this.items[itemsSize-1]['IN_EXPENSE_PERCENTAGE_TOTAL']) + this.cleanNumber(this.IN_OUTSTANDING_PERCENTAGE, true);
					

					this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT'] = this.formatNumber(fixedPreTax);
					this.items[itemsSize-1]['IN_EXPENSE_HST'] = this.formatNumber(fixedHst);
					this.items[itemsSize-1]['IN_EXPENSE_TOTAL'] = this.formatNumber(fixedTotal);
					this.items[itemsSize-1]['IN_EXPENSE_PERCENTAGE_TOTAL'] = this.formatPercentageNumber(fixedPercentangeTotal);

					if (
						this.items[itemsSize-1]['IN_EXPENSE_PRETAX_AMOUNT'] < 0 ||
						//this.items[itemsSize-1]['IN_EXPENSE_HST'] < 0 ||
						this.items[itemsSize-1]['IN_EXPENSE_TOTAL'] < 0 ||
						this.items[itemsSize-1]['IN_EXPENSE_PERCENTAGE_TOTAL'] < 0
					) {
						this.fixDiscrepancyError = true
					}
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

					this.IN_TOTAL_PRE_TAX_AMOUNT = this.cleanNumber(currentTotalFinalPreTax, true).toFixed(2);
					this.IN_TOTAL_HST = this.cleanNumber(currentTotalFinalHST, true).toFixed(2);
					this.IN_TOTAL_TOTAL = this.cleanNumber(currentTotalFinalAmount, true).toFixed(2);
					this.IN_TOTAL_PERCENTAGE_TOTAL = this.cleanNumber((Math.round((currentTotalFinalPercentage + Number.EPSILON) * 100) / 100), true).toFixed(2);
					
					// Outstanding
					this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT = (this.cleanNumber((this.cleanNumber(this.IN_INVOICE_PRE_TAX) - this.cleanNumber(this.IN_TOTAL_PRE_TAX_AMOUNT)), true)).toFixed(2);
					this.IN_OUTSTANDING_TOTAL_HST = (this.cleanNumber((this.cleanNumber(this.IN_INVOICE_TAX_TOTAL) - this.cleanNumber(this.IN_TOTAL_HST)), true)).toFixed(2);
					this.IN_OUTSTANDING_TOTAL = (this.cleanNumber((this.cleanNumber(this.IN_INVOICE_TOTAL) - this.cleanNumber(this.IN_TOTAL_TOTAL)), true)).toFixed(2);   
					this.IN_OUTSTANDING_PERCENTAGE = (this.cleanNumber((100 - this.cleanNumber(this.IN_TOTAL_PERCENTAGE_TOTAL)), true)).toFixed(2);				
				},
				goEditMode(){
					this.isDiscrepancyMode = false;
					let itemsSize = this.items.length
					this.onBlurPreTaxAmount(itemsSize - 1, 'IN_EXPENSE_PRETAX_AMOUNT');
				}
			},
			mounted() {
				if(Array.isArray(this.items)) {
					this.items.forEach((item, index) => {
						item.IN_EXPENSE_ROW_ID = this.isValidValue(item.IN_EXPENSE_ROW_ID) ? item.IN_EXPENSE_ROW_ID : this.generateSecureUID();
						item.IN_EXPENSE_PRETAX_AMOUNT = this.formatNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
						item.IN_EXPENSE_HST = this.formatNumber(item.IN_EXPENSE_HST);
						item.IN_EXPENSE_TOTAL = this.formatNumber(item.IN_EXPENSE_TOTAL);
						item.IN_EXPENSE_PERCENTAGE_TOTAL = this.formatPercentageNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL);

						this.evaluateRow(index);
					});
					this.areAllValid();
				} else {
					this.items = [];
				}
				
				
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
								// IN_EXPENSE_TRANSACTION_COMMENTS: item.IN_EXPENSE_TRANSACTION_COMMENTS,
								// IN_EXPENSE_OFFICE_ID: item.IN_EXPENSE_OFFICE_ID,
								// IN_EXPENSE_OFFICE_LABEL: item.IN_EXPENSE_OFFICE_LABEL,
								// IN_EXPENSE_DEPARTMENT_ID: item.IN_EXPENSE_DEPARTMENT_ID,
								// IN_EXPENSE_DEPARTMENT_LABEL: item.IN_EXPENSE_DEPARTMENT_LABEL,
								// IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION: item.IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION,
								// IN_EXPENSE_INVESTRAN_HST_DESCRIPTION: item.IN_EXPENSE_INVESTRAN_HST_DESCRIPTION,
								// IN_EXPENSE_COMPANY_ID: item.IN_EXPENSE_COMPANY_ID,
								// IN_EXPENSE_COMPANY_LABEL: item.IN_EXPENSE_COMPANY_LABEL,
								// IN_EXPENSE_GL_CODE: item.IN_EXPENSE_GL_CODE,
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
						this.isValidAmounts &&
						this.IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT == 0 &&
						this.IN_OUTSTANDING_TOTAL_HST == 0 &&
						this.IN_OUTSTANDING_TOTAL == 0 &&
						this.IN_OUTSTANDING_PERCENTAGE == 0
					);
					const parentButton = window.parent.document.querySelector('[selector=\"auxiliarGridValidation\"] > div > button');
					if (parentButton) {
						parentButton.click(); // Click on parent button
					} else {
						console.error('Button not found');
					}
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


function stringToFloat($string) {
	if(empty($string)){
		return 0;
	}
    // Remove commas and convert to float
    $float = floatval(str_replace(',', '', $string));
    // Format to two decimal places and return as float
	return empty($float) ? 0 : $float ;
}

/*
 * Get activity list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @param (String) $recoverableOption (Recoverable, Non-Recoverable)
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
function getTeamRouting($collectionId, $apiUrl, $recoverableOption) {
	$query  = '';
	$query .= "SELECT TEAM_ASSET.data->>'$.ASSET_ID' AS ID, ";
	$query .= "TEAM_ASSET.data->>'$.ASSET_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS TEAM_ASSET ";
	$query .= "WHERE TEAM_ASSET.data->>'$.DEAL_STATUS' = 'Active' ";
	$query .= empty($recoverableOption) ? "" : "AND TEAM_ASSET.data->>'$.ASSET_TYPE' = '" . $recoverableOption . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}

/*
 * Get activity list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @param (String) $recoverableOption Default 'Recoverable' (Recoverable, Non-Recoverable)
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
function getActivityList($collectionId, $apiUrl, $recoverableOption = 'Recoverable') {
	$query  = '';
	$query .= "SELECT data->>'$.NL_ACTIVITY_SYSTEM_ID_ACTG' AS ID, ";
	$query .= "data->>'$.ACTIVITY_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS ACTIVITY ";
	$query .= "WHERE ACTIVITY.data->>'$.ACTIVITY_STATUS' = 'Active' ";
	$query .= "AND ACTIVITY.data->>'$.ACTIVITY_TYPE' IN ('" . $recoverableOption . "','Both') ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}

/*
 * Get Corp Entity list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
function getCorpEntityList($collectionId, $apiUrl) {
	$query  = '';
	$query .= "SELECT data->>'$.NL_COMPANY_SYSTEM_ID_ACTG' AS ID, ";
	$query .= "data->>'$.EXPENSE_CORPORATE_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS CORP ";
	$query .= "WHERE CORP.data->>'$.EXPENSE_CORPORATE_STATUS' = 'Active' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}

/*
 * Get Deal list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 * modify by Jhon Chacolla
 */
function getDealList($collectionId, $apiUrl, $assetLabel) {
	$query  = '';
	$query .= "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, ";
	$query .= "data->>'$.DEAL_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS DEAL ";
	$query .= "WHERE DEAL.data->>'$.DEAL_STATUS' = 'Active' ";
	$query .= empty($assetLabel) ? "" : "AND DEAL.data->>'$.DEAL_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}

/*
 * Get Fund Manager list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
function getFundManagerList($collectionId, $apiUrl, $assetLabel) {
	$query  = '';
	$query .= "SELECT data->>'$.DEAL_SYSTEM_ID_DB' AS ID, ";
	$query .= "data->>'$.FUND_MANAGER_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS FUND_MANAGER ";
	$query .= "WHERE FUND_MANAGER.data->>'$.FUND_MANAGER_STATUS' = 'Active' ";
	$query .= empty($assetLabel) ? "" : "AND FUND_MANAGER.data->>'$.FUNDMANAGER_ASSETCLASS.ASSET_LABEL' = '" . $assetLabel . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}

function getMandateList($collectionId, $apiUrl, $assetLabel) {
	$query  = '';
	$query .= "SELECT data->>'$.MANDATE_SYSTEM_ID_ACTG' AS ID, ";
	$query .= "data->>'$.MANDATE_LABEL' AS LABEL ";
	$query .= "FROM collection_" . $collectionId . " AS MANDATE ";
	$query .= "WHERE MANDATE.data->>'$.MANDATE_STATUS' = 'Active' ";
	$query .= empty($assetLabel) ? "" : "AND MANDATE.data->>'$.MANDATE_ASSETCLASS' = '" . $assetLabel . "' ";
	$query .= "ORDER BY LABEL ASC";
	$response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
	return $response;
}

/**
 * Retrieve expense corps options from a specified collection by its ID.
 *
 * @param int $ID - The ID of the collection.
 * @param string $apiUrl - The API URL for making the request.
 * @return array - An array of collection records with 'ID' and 'LABEL' keys, or an empty array if no records are found.
 *
 * by Favio Mollinedo
 * adapted copy from getExpenseActivityOptions
 */
function getExpenseCorpOptions($ID, $apiUrl)
{
    // Prepare SQL query to fetch records for the collection using its ID
    $sQCollectionsId = "SELECT data->>'$.MANDATE_SYSTEM_ID_ACTG' AS ID,
                        data->>'$.MANDATE_LABEL' AS LABEL
                        FROM collection_" . $ID . " AS EXPENSE
                        WHERE EXPENSE.data->>'$.MANDATE_ASSETCLASS' = 'CORP'
                        ORDER BY LABEL ASC";

    // Send API request to fetch collection records, return an empty array if none are found
    $collectionRecords = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    // Return the records fetched from the API call
    return $collectionRecords;
}

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
function getExpenseCorpProject($collectionId, $apiUrl) 
{
    $query = '';
    $query .= 'SELECT CAST(ID.data->>"$.NL_COMPANY_SYSTEM_ID_ACTG" AS UNSIGNED) AS ID, ';
    $query .= 'ID.data->>"$.EXPENSE_CORPORATE_LABEL" AS LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ID ';
    $query .= 'WHERE JSON_UNQUOTE(ID.data->"$.NL_CORPPROJ_STATUS") = "Active" ';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
function getExpenseAccount($collectionId, $apiUrl) 
{
    $query = '';
    $query .= 'SELECT data->>"$.NL_ACCOUNT_SYSTEM_ID_ACTG" AS ID, ';
    $query .= 'data->>"$.ACCOUNT_LABEL" AS LABEL ';
    $query .= 'FROM collection_' . $collectionId . ' AS ACCOUNT ';
    $query .= 'WHERE data->"$.ACCOUNT_STATUS" = "Active" ';
    $query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}

/*
 * Get Northleaf Invoice Expense Corp Proj list from collection
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Manuel Monroy
 */
function getExpenseDefault($collectionId, $apiUrl) 
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
}

/*
 * Get all expense records by request from EXPENSE_TABLE
 * @param (String) $apiUrl
 * @param (int) $requestId
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
function getAllExpenseByRequest ($apiUrl, $requestId) {
    $query  = "";
    $query .= "SELECT * ";
    $query .= "FROM EXPENSE_TABLE ";
    $query .= "WHERE IN_EXPENSE_CASE_ID = '" . $requestId . "'";
	$query .= "ORDER BY IN_EXPENSE_ROW_NUMBER ASC ; ";
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));
    return $response;
}

/*
 * Format Expense Data lo load into grid
 * @param (Array) $expenseList
 * @return (Array) $newExpense - Formated array
 *
 * by Jhon Chacolla
 */
function transformData($expenseList) {
	$newExpense = [];
	foreach($expenseList as $key => $expense){
		//die(var_dump($expense['IN_EXPENSE_VALIDATION']));
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
		if(empty($expenseValidation)) {
			$expenseValidation = [
				"IN_EXPENSE_DESCRIPTION" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_ACCOUNT" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
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
					"isValid" => false,
					"isDisabled" => false,
					"isRequired" => true,
					"messages" => (object)["required" => 'This Field is required.']
				],
				"IN_EXPENSE_PROJECT_DEAL" => [
					"isValid" => true,
					"isDisabled" => false,
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
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_ACTIVITY" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				],
				"IN_EXPENSE_CORP_ENTITY" => [
					"isValid" => true,
					"isDisabled" => false,
					"isRequired" => false,
					"messages" => (object)[]
				]
			];
		}

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
			'IN_EXPENSE_OFFICE' => $expenseOffice,
			'IN_EXPENSE_DEPARTMENT' => $expenseDepartment,
			'IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION' => $expense['IN_EXPENSE_INVESTRAN_EXP_DESCRIPTION'],
			'IN_EXPENSE_INVESTRAN_HST_DESCRIPTION' => $expense['IN_EXPENSE_INVESTRAN_HST_DESCRIPTION'],
			'IN_EXPENSE_COMPANY' => $expenseCompany,
			'IN_EXPENSE_GL_CODE' => $expense['IN_EXPENSE_GL_CODE'],
			"IN_EXPENSE_VALIDATION" => $expenseValidation

			
		];
	}
	return $newExpense;
}

/*
 * Get Discrepancy
 * @param (String) $collectionId
 * @param (String) $apiUrl
 * @return (Array) $response
 *
 * by Jhon Chacolla
 */
function getDiscrepancy($collectionId, $apiUrl) 
{
    $query = '';
    $query .= 'SELECT data->"$.IN_EXPENSE_DISCREPANCY" AS DISCREPANCY ';
    $query .= 'FROM collection_' . $collectionId . ' AS DIS ';
    //$query .= 'WHERE data->"$.ACCOUNT_STATUS" = "Active" ';
    //$query .= 'ORDER BY LABEL';
    $response = callApiUrlGuzzle($apiUrl, "POST", encodeSql($query));

    return $response;
}