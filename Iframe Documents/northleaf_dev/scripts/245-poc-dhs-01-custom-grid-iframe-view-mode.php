<?php 
/*  
 *  POC - Custom Grid View Mode
 *
 *  by Jhon Chacolla
 */
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables


$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
$collectionmaster = getCollectionIdMaster2(['IN_ASSET_CLASS', 'IN_DEAL', 'IN_EXPENSE_FUND_MANAGER', 'IN_EXPENSE_ACTIVITY', 'IN_EXPENSE_CORP_ENTITY', 'IN_EXPENSE_MANDATES'], $apiUrl, 'IN_MASTER_COLLECTION_ID');

// Clean amounts data
$invoicePreTax = empty($data["IN_INVOICE_PRE_TAX"]) ? 0 : stringToFloat($data["IN_INVOICE_PRE_TAX"]);
$invoiceTaxTotal = empty($data["IN_INVOICE_TAX_TOTAL"]) ? 0 : stringToFloat($data["IN_INVOICE_TAX_TOTAL"]);
$invoiceTotal = empty($data["IN_INVOICE_TOTAL"]) ? 0 : stringToFloat($data["IN_INVOICE_TOTAL"]);

$invoicePreTaxPercentage = stringToFloat($data["IN_INVOICE_PRE_TAX_PERCENTAGE"]);
$invoiceTaxTotalPercentage = stringToFloat($data["IN_INVOICE_TAX_TOTAL_PERCENTAGE"]);
$invoiceTotalPercentage = stringToFloat($data["IN_INVOICE_TOTAL_PERCENTAGE"]);

$invoiceTotalPreTaxAmount = stringToFloat($data['IN_TOTAL_PRE_TAX_AMOUNT']);
$invoiceTotalHst = stringToFloat($data['IN_TOTAL_HST']);
$invoiceTotalTotal = stringToFloat($data['IN_TOTAL_TOTAL']);
$invoiceTotalPercentageTotal = stringToFloat($data['IN_TOTAL_PERCENTAGE_TOTAL']);

$invoiceOutstandingTotalPreTaxAmount = stringToFloat($data['IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT']);
$invoiceOutstandingTotalHst = stringToFloat($data['IN_OUTSTANDING_TOTAL_HST']);
$invoiceOutstandingTotal = stringToFloat($data['IN_OUTSTANDING_TOTAL']);
$invoiceOutstandingPercentage = stringToFloat($data['IN_OUTSTANDING_PERCENTAGE']);

$invoiceCurrency = (empty($data['IN_INVOICE_CURRENCY']) || $data['IN_INVOICE_CURRENCY'] == 'undefined'|| $data['IN_INVOICE_CURRENCY'] == 'null') ? 'USD' :  $data['IN_INVOICE_CURRENCY'];

//$items = $data['IN_EXPENSE_REQUEST']?? [];
$viewMode = $data["IN_CUSTOME_TABLE_VIEW_MODE"] ?? 'false';

$requestId = $data["IN_REQUEST_ID"];
$itemsData = getAllExpenseByRequest($apiUrl, $requestId);

$items = transformData($itemsData);

// Get dropdowns data source
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
	</style>
</head>
<body>
    	<div id="app">
		<!-- Componente BootstrapVue -->
		<div v-if="items.length > 0">
			<!-- <h2>Dinamic Table</h2> -->
			<div class="table-responsive" id="iframe-table-container" style="max-height: 1500px;">
				    <!-- <p>IN_INVOICE_PRE_TAX: {{IN_INVOICE_PRE_TAX}}</p>
					<p>IN_INVOICE_TAX_TOTAL: {{IN_INVOICE_TAX_TOTAL}}</p>
					<p>IN_INVOICE_TOTAL: {{IN_INVOICE_TOTAL}}</p>
					<p>IN_INVOICE_PRE_TAX_PERCENTAGE: {{IN_INVOICE_PRE_TAX_PERCENTAGE}}</p>
					<p>IN_INVOICE_TAX_TOTAL_PERCENTAGE: {{IN_INVOICE_TAX_TOTAL_PERCENTAGE}}</p>
					<p>IN_INVOICE_TOTAL_PERCENTAGE: {{IN_INVOICE_TOTAL_PERCENTAGE}}</p>
					<p>IN_INVOICE_CURRENCY: {{IN_INVOICE_CURRENCY}}</p> -->
				<table class="table table-bordered table-striped w-auto">
					<thead class="thead-light">
						<tr class="">
							<th></th>
							<th style="display:none;"></th>
							<th></th>
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
	
						</tr>
						<tr class="head-table">
							<th style="display:none;"></th>
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
						<tr v-for="(item, index) in items" :key="index">
							<td style="display:none;">
							</td>
							<td>{{ index + 1 }}</td>
							<td style="min-width:300px">
								<|textarea 
									v-model="item.IN_EXPENSE_DESCRIPTION" 
									class="form-control"
									disabled 
									name="" 
									rows="1" 
									cols="10">
								</|textarea>
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
									:disabled="true"  
								>
								</multiselect>
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
									:disabled="true"  
								>
								</multiselect>
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
									:disabled="true" 
								>
								</multiselect>
							</td>
							<td style="min-width:150px;">
								<|input 
									type="text" 
									class="form-control"
									placeholder="00,000.00" 
									v-model="item.IN_EXPENSE_PRETAX_AMOUNT"
									style="text-align: right;"
									disabled  
								/>
								<!-- <p class="text-info small">IN_EXPENSE_PRETAX_AMOUNT</p>  -->
							</td>
							<td style="min-width:150px;">
								<|input type="text" 
									class="form-control" 
									v-model="item.IN_EXPENSE_HST"
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
									style="text-align: right;"
									disabled="true"  
								/>
								<!-- <p class="text-info small">IN_EXPENSE_TOTAL</p> -->
							</td>
							<td style="min-width: 150px;">
								<|input 
									type="text" 
									v-model="item.IN_EXPENSE_PERCENTAGE_TOTAL" 
									class="form-control" 
									style="text-align: right;"
									disabled  
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
									:disabled="true" 
								>
								</multiselect>
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
								>
								</multiselect>
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
								>
								</multiselect>
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
									:disabled="true"  
								>
								</multiselect>
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
									:disabled="true"  
								>
								</multiselect>
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
									:disabled="true"  
								>
								</multiselect>
							</td>
						</tr>
					</tbody>
					<tfoot class="tfoot-light">
						<tr>
							<td colspan="4"></td>
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
							<!--<td class="">
							</td>-->
							<td :class="{matching:true, equal: IN_TOTAL_PERCENTAGE_TOTAL==100, different: IN_TOTAL_PERCENTAGE_TOTAL!=100}"
								style="text-align: right;padding-right: 1.3em;"
							>
								{{ formatPercentageNumber(IN_TOTAL_PERCENTAGE_TOTAL) }}
								<!-- <p class="text-info small">IN_TOTAL_PERCENTAGE_TOTAL: {{IN_TOTAL_PERCENTAGE_TOTAL}}</p> -->
							</td>
							<td colspan="6"></td>
						</tr>
						<tr v-if="IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT != 0 || IN_OUTSTANDING_TOTAL_HST != 0 || IN_OUTSTANDING_TOTAL_HST != 0 || IN_OUTSTANDING_TOTAL != 0 ">
							<td colspan="4"></td>
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
							<!-- <td class="">
							</td>-->
							<td class="matching"
								style="text-align: right;padding-right: 1.3em;"
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

				<|input id="iframe-IN_TOTAL_PRE_TAX_AMOUNT" v-model="cleanNumber(IN_TOTAL_PRE_TAX_AMOUNT)" class="form-control" readonly  v-show="false">
				<|input id="iframe-IN_TOTAL_HST" v-model="cleanNumber(IN_TOTAL_HST)" class="form-control" readonly  v-show="false">
				<|input id="iframe-IN_TOTAL_TOTAL" v-model="cleanNumber(IN_TOTAL_TOTAL)" class="form-control" readonly  v-show="false">
				<|input id="iframe-IN_TOTAL_PERCENTAGE_TOTAL" v-model="cleanNumber(IN_TOTAL_PERCENTAGE_TOTAL)" class="form-control" readonly  v-show="false">

				<|input id="iframe-IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT" v-model="cleanNumber(IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT)" class="form-control" readonly  v-show="false">
				<|input id="iframe-IN_OUTSTANDING_TOTAL_HST" v-model="cleanNumber(IN_OUTSTANDING_TOTAL_HST)" class="form-control" readonly  v-show="false">
				<|input id="iframe-IN_OUTSTANDING_TOTAL" v-model="cleanNumber(IN_OUTSTANDING_TOTAL)" class="form-control" readonly  v-show="false">
				<|input id="iframe-IN_OUTSTANDING_PERCENTAGE" v-model="cleanNumber(IN_OUTSTANDING_PERCENTAGE)" class="form-control" readonly  v-show="false">
			</div>
		</div>
		<div class="alert alert-warning" role="alert" v-if="items.length <= 0">
			No records found. To add a new record, please click the "Add Item" button.
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
	// As a plugin
		//Vue.use(VueMask.VueMaskPlugin);
		new Vue({
			el: '#app',
			components: {
				multiselect: window.VueMultiselect.default
			},
			data: {
				rawValue: '',
				viewMode: " . $viewMode . ",
				IN_INVOICE_PRE_TAX: " . $invoicePreTax . ",
				IN_INVOICE_TAX_TOTAL: " . $invoiceTaxTotal . ",
				IN_INVOICE_TOTAL: " . $invoiceTotal . ",
				IN_INVOICE_PRE_TAX_PERCENTAGE: " . $invoicePreTaxPercentage . ",
				IN_INVOICE_TAX_TOTAL_PERCENTAGE: " . $invoiceTaxTotalPercentage . ",
				IN_INVOICE_TOTAL_PERCENTAGE: " . $invoiceTotalPercentage . ",
				IN_INVOICE_CURRENCY: '" . $invoiceCurrency . "',

                IN_TOTAL_PRE_TAX_AMOUNT: " . $invoiceTotalPreTaxAmount . ",
                IN_TOTAL_HST: " . $invoiceTotalHst . ",
                IN_TOTAL_TOTAL: " . $invoiceTotalTotal . ",
                IN_TOTAL_PERCENTAGE_TOTAL: " . $invoiceTotalPercentageTotal . ",
                
				IN_OUTSTANDING_TOTAL_PRE_TAX_AMOUNT: " . $invoiceOutstandingTotalPreTaxAmount . ",
                IN_OUTSTANDING_TOTAL_HST: " . $invoiceOutstandingTotalHst . ",
                IN_OUTSTANDING_TOTAL: " . $invoiceOutstandingTotal . ",
                IN_OUTSTANDING_PERCENTAGE: " . $invoiceOutstandingPercentage . ",

				totalPercentage: 100,
			
				items: " . json_encode($items) . ",
				//Dropdown data sources
				IN_EXPENSE_ACCOUNT_VALUES: " . json_encode($activityRecList) . ",
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
			},
			methods: {
				formatNumber(value) {
					if(isNaN(value) || value == Infinity) {
						return '0.00';
					}
					return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });  
				},
				formatPercentageNumber(value) {
					if(isNaN(value) || value == Infinity) {
						return '0.00';
					}
					let numberValue = parseFloat(value);
					let formatNumber = numberValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
					return formatNumber + '%';
				},
				cleanNumber(value) {
					if (typeof value === 'string') {
						return parseFloat(value.replace(/,/g, ''));
					}
					return parseFloat(value) || 0;
				}
			},
			mounted() {
				this.items.forEach((item, index) => {
                    item.IN_EXPENSE_PRETAX_AMOUNT = this.formatNumber(item.IN_EXPENSE_PRETAX_AMOUNT);
                    item.IN_EXPENSE_HST = this.formatNumber(item.IN_EXPENSE_HST);
                    item.IN_EXPENSE_TOTAL = this.formatNumber(item.IN_EXPENSE_TOTAL);
                    item.IN_EXPENSE_PERCENTAGE = this.formatNumber(item.IN_EXPENSE_PERCENTAGE);
                    item.IN_EXPENSE_PERCENTAGE_TOTAL = this.formatPercentageNumber(item.IN_EXPENSE_PERCENTAGE_TOTAL);
				});
				console.log(this.IN_TOTAL_PRE_TAX_AMOUNT, 'IN_TOTAL_PRE_TAX_AMOUNT--------------');
			},
			computed: {
				formattedValue() {  
					// Formatea el valor crudo para mostrarlo 
					return this.rawValue ? this.formatNumber(this.rawValue) : '';
				},
				prepareItems() {
					let newItems = [];
					if(Array.isArray(this.items)){
						this.items.forEach(item => {
							newItems.push({ 
								IN_EXPENSE_DESCRIPTION: item.IN_EXPENSE_DESCRIPTION,
								IN_EXPENSE_ACCOUNT: item.IN_EXPENSE_ACCOUNT,
								IN_EXPENSE_CORP_PROJ: item.IN_EXPENSE_CORP_PROJ,
								IN_EXPENSE_PRETAX_AMOUNT: this.cleanNumber(item.IN_EXPENSE_PRETAX_AMOUNT), 
								IN_EXPENSE_HST: this.cleanNumber(item.IN_EXPENSE_HST), 
								IN_EXPENSE_TOTAL: this.cleanNumber(item.IN_EXPENSE_TOTAL), 
								IN_EXPENSE_PERCENTAGE: this.cleanNumber(item.IN_EXPENSE_PERCENTAGE),
								IN_EXPENSE_PERCENTAGE_TOTAL: item.IN_EXPENSE_PERCENTAGE_TOTAL,
								IN_EXPENSE_PROJECT_DEAL: item.IN_EXPENSE_PROJECT_DEAL,
								IN_EXPENSE_FUND_MANAGER: item.IN_EXPENSE_FUND_MANAGER,
								IN_EXPENSE_MANDATE: item.IN_EXPENSE_MANDATE,
								IN_EXPENSE_NR: item.IN_EXPENSE_NR,
								IN_EXPENSE_ACTIVITY: item.IN_EXPENSE_ACTIVITY,
								IN_EXPENSE_CORP_ENTITY: item.IN_EXPENSE_CORP_ENTITY,
								IN_EXPENSE_TEAM_ROUTING: item.IN_EXPENSE_TEAM_ROUTING
							});
						});
					}
					return newItems;
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

/**
 * Get IDs of collections with the Master collection
 *
 * @param (Array) $collectionNames
 * @param (String) $apiUrl
 * @param (String) $environmentName
 * @return (Array) $aCollections
 *
 * by Ana Castillo
 * modified by Helen Callisaya
 */
function getCollectionIdMaster2($collectionNames, $apiUrl, $environmentName = 'IN_MASTER_COLLECTION_ID')
{
    //Set Master Collection ID
    $masterCollectionID = getenv($environmentName);

    //Add semicolon with all fields of the array
    $collectionName = array_map(function($item) {
        return '"' . $item . '"';
    }, $collectionNames);

    //Merge all values of the array with commas
    $collections = implode(", ", $collectionName);

    //Get Collections IDs
    $sQCollectionsId = "SELECT data->>'$.COLLECTION_ID' AS ID,
                               data->>'$.COLLECTION_NAME' AS COLLECTION_NAME
                        FROM collection_" . $masterCollectionID . "
                        WHERE data->>'$.COLLECTION_NAME' IN (" . $collections . ")";
    $collectionsInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($sQCollectionsId)) ?? [];

    //Set array to get the name with the ID
    $aCollections = array();
    if (count($collectionsInfo) > 0) {
        foreach ($collectionsInfo as $item) {
            $aCollections[$item['COLLECTION_NAME']] = $item['ID'];
        }
    }

    return $aCollections;
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

		$newExpense[] = [
			'IN_EXPENSE_CASE_ID' => $expense['IN_EXPENSE_CASE_ID'],
			'IN_EXPENSE_ROW_ID' => $expense['IN_EXPENSE_ROW_ID'],
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

			"IN_EXPENSE_VALIDATION" => [
				"IN_EXPENSE_DESCRIPTION" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_ACCOUNT" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_CORP_PROJ" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_PRETAX_AMOUNT" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_HST" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_TOTAL" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_PERCENTAGE" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_PERCENTAGE_TOTAL" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_NR" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_TEAM_ROUTING" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_PROJECT_DEAL" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_FUND_MANAGER" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_MANDATE" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_ACTIVITY" => [
					"isValid" => true,
					"messages" => []
				],
				"IN_EXPENSE_CORP_ENTITY" => [
					"isValid" => true,
					"messages" => []
				]
			]
		];
	}
	return $newExpense;
}