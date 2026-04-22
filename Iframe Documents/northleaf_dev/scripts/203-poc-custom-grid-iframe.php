<?php 
/*  
 *  POC - Custom Grid
 *
 *  by Telmo Chiri
 */
require_once("/Northleaf_PHP_Library.php");
//Set Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');

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
	</style>
</head>
<body>
	<div id="app">
		<!-- Componente BootstrapVue -->
		<b-container>
			<b-row>
				<b-col>
					<h2>Dinamic Table</h2>
						<table class="table table-bordered">
								<thead class="thead-light">
									<tr>
										<th colspan="4"></th>
										<th class="sub-head-table">Total PreTax Amount</th>
										<th class="sub-head-table">Total HST</th>
										<th class="sub-head-table">Total Amount</th>
										<th></th>
										<th class="sub-head-table">Total Percentage</th>
									</tr>
									<tr>
										<td colspan="4"></td>
										<td>{{ formatNumber(totalPreTax) }}</td>
										<td>{{ formatNumber(totalHST) }}</td>
										<td>{{ formatNumber(totalAmount) }}</td>
										<td></td>
										<td>{{ formatNumber(totalPercentage) }}%</td>
									</tr>
									<tr class="head-table">
										<th></th>
										<th></th>
										<th>#</th>
										<th>Mandate</th>
										<th>PreTax Amount</th>
										<th>HST</th>
										<th>Total</th>
										<th>% of Expense Item</th>
										<th>% of Total Invoice</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="(item, index) in items" :key="index">
										<td>
											<|button class="btn btn-danger btn-sm" @click="deleteRow(index)"><span class="fa fa-trash" style="font-size:150%;"></|button>
										</td>
										<td>
											<|button class="btn btn-secondary btn-sm" @click="splitRow(index)"><span class="fa fa-code-fork" style="font-size:150%;"></|button>
										</td>
										<td>{{ index + 1 }}</td>
										<td>
											<!-- Mandates Dropdown -->
											<|select v-model="item.mandate" class="form-control">
											<|option value="">-Select-</|option>
											<|option value="Mandate 1">Mandate 1</|option>
											</|select>
										</td>
										<td>
											<|input 
												type="text" 
												class="form-control"
												placeholder="00,000.00" 
												v-model="item.preTaxAmount" 
												@input="onFieldInput(index, \'preTaxAmount\', $event)" 
												@blur="onFieldBlur(index, \'preTaxAmount\')"
											/>
										</td>
										<td>
											<|input type="text" 
												class="form-control" 
												v-model="item.hst" 
												@input="onFieldInput(index, \'hst\', $event)" 
												@blur="onFieldBlur(index, \'hst\')"
											/>
										</td>
										<td>{{ item.total }}</td>
										<td>100%</td>
										<td>
											<|input type="text" v-model="item.percentOfInvoice" class="form-control" @input="calculatePercentage(index)" />
										</td>
									</tr>
								</tbody>
								<tfoot class="tfoot-light">
									<tr>
										<th colspan="4"></th>
										<th class="sub-head-table">Total PreTax Amount</th>
										<th class="sub-head-table">Total HST</th>
										<th class="sub-head-table">Total Amount</th>
										<th></th>
										<th class="sub-head-table">Total Percentage</th>
									</tr>
									<tr>
										<td colspan="4"></td>
										<td class="matching" 
											v-bind:class="cleanNumber(totalPreTax) == cleanNumber(totalFinalPreTax) ? \'equal\' : \'different\'"
										>
											{{ totalFinalPreTax }}
										</td>
										<td class="matching" 
											v-bind:class="cleanNumber(totalHST) == cleanNumber(totalFinalHST) ? \'equal\' : \'different\'"
										>
											{{ totalFinalHST }}
										</td>
										<td class="matching" 
												v-bind:class="cleanNumber(totalAmount) == cleanNumber(totalFinalAmount) ? \'equal\' : \'different\'"
										>
											{{ totalFinalAmount }}
											<|input v-show="false" v-model="cleanNumber(totalFinalAmount)" id="iframe-totalFinalAmount" class="form-control">
										</td>
										<td></td>
										<td class="matching">
											{{ totalFinalPercentage }}%
										</td>
									</tr>
								</tfoot>
						</table>
						<div>
							<|input id="iframe-items" v-model="JSON.stringify(prepareItems)" class="form-control" readonly  v-show="true">
						</div>
						<|button class="btn btn-dark" @click="addRow"><span class="fa fa-plus-circle"></span> Add Item</|button>
				</b-col>
			</b-row>
		</b-container>
	</div>

	<!-- Add Vue.js from CDN -->
	<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
	
	<!-- Add BootstrapVue JS y sus dependencias from CDN -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-vue@2.21.2/dist/bootstrap-vue.min.js"></script>
	';
$html .= "
	<!-- Vue.js Script -->
	<script>
		new Vue({
			el: '#app',
			data: {
				rawValue: '',
				totalPreTax: " . $data["totalPreTaxAmount"] . ",
				totalHST: " . $data["totalHSTAmount"] . ",
				totalAmount: " . $data["totalAmount"] . ",
				totalPercentage: 100,
				//Final Values
				totalFinalPreTax: 0,
				totalFinalHST: 0,
				totalFinalAmount: 0,
				totalFinalPercentage: 0,
				items: [
					{ mandate: 'Mandate 1', preTaxAmount: 100, hst: 50, total: 150, percentOfInvoice: 10 },
					{ mandate: '', preTaxAmount: 200, hst: 100, total: 300, percentOfInvoice: 20 },
					{ mandate: 'Mandate 1', preTaxAmount: 550, hst: 200, total: 750, percentOfInvoice: 50 },
					{ mandate: 'Mandate 1', preTaxAmount: 150, hst: 150, total: 300, percentOfInvoice: 20 }
				]
			},
			methods: {
				formatNumber(value) {
					return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });  
					//const number = value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
					if (isNaN(value)) return 0;  
					return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
					// Convierte el valor a número y lo formatea 
					const number = parseFloat(value);  
					if (isNaN(number)) return '';  
					return number.toLocaleString(undefined, {  
						minimumFractionDigits:0,  
						maximumFractionDigits:2 
					});
				},
				calculateTotals(index) {
					let item = this.items[index];
        			// Limpiar los valores de comas antes del cálculo
					let preTax = this.cleanNumber(item.preTaxAmount);
					let hst = this.cleanNumber(item.hst);

					// Calcular el total sumando preTaxAmount y hst
					item.total = preTax + hst;

					// Formatear preTaxAmount, hst y total después del cálculo
					item.preTaxAmount = this.formatNumber(preTax);
					item.hst = this.formatNumber(hst);
					item.total = this.formatNumber(item.total);

					// Recalcular el porcentaje de la factura
					let itemPercentage = (parseFloat(this.cleanNumber(item.total)) * this.totalPercentage) / this.totalAmount;
					console.log(parseFloat(this.cleanNumber(item.total)), ' *', this.totalPercentage,') / ', this.totalAmount);
					item.percentOfInvoice = Math.round((itemPercentage + Number.EPSILON) * 100) / 100;
					
					this.calculateFinalTotals();
				},
				calculatePercentage(index) {
					let item = this.items[index];
					// Calcular los nuevos valores de preTaxAmount y hst basados en el porcentaje del total
					let newPreTax = (item.percentOfInvoice * this.totalPreTax) / this.totalPercentage;
					let newHST = (item.percentOfInvoice * this.totalHST) / this.totalPercentage;

					// Actualizar los valores
					item.preTaxAmount = this.formatNumber(newPreTax);
					item.hst = this.formatNumber(newHST);
					item.total = this.formatNumber(newPreTax + newHST);

					this.calculateFinalTotals();
				},
				addRow() {
					this.items.push({ mandate: '', preTaxAmount: 0, hst: 0, total: 0, percentOfInvoice: 0 });
				},
				deleteRow(index) {
					this.items.splice(index, 1);
					this.calculateFinalTotals();
				},
				splitRow(index) {
					currentItem = this.items[index];
                    currentItem.mandate = currentItem.mandate;
                    currentItem.hst =  this.formatNumber(this.cleanNumber(currentItem.hst) / 2);
                    currentItem.preTaxAmount = this.formatNumber(this.cleanNumber(currentItem.preTaxAmount) / 2);
                    currentItem.total = this.formatNumber(this.cleanNumber(currentItem.total) / 2);
                    currentItem.percentOfInvoice = currentItem.percentOfInvoice / 2;
                    this.items[index] = currentItem;
                    // Check that the index is valid
                    if (index >= 0 && index < this.items.length) {
                        // Clone the object at the index position
                        const clonedItem = { ...this.items[index] };

                        // Insert the cloned object right after the original row.
                        this.items.splice(index + 1, 0, clonedItem);
                    } else {
                        console.error('Index out of range');
                    }
				},
				// Elimina comas de una cadena de texto antes de convertirla a número
				cleanNumber(value) {
					if (typeof value === 'string') {
						return parseFloat(value.replace(/,/g, ''));
					}
					return parseFloat(value) || 0;
				},
				calculateFinalTotals() {
					let actualTotalFinalPreTax = 0;
					let actualTotalFinalHST = 0;
					let actualTotalFinalAmount = 0;
					let actualTotalFinalPercentage = 0;

					this.items.forEach(item => {
						actualTotalFinalPreTax += this.cleanNumber(item.preTaxAmount);
						actualTotalFinalHST += this.cleanNumber(item.hst);
						actualTotalFinalAmount += this.cleanNumber(item.total);
						actualTotalFinalPercentage += (item.percentOfInvoice ? parseFloat(item.percentOfInvoice) : 0);
					});

					this.totalFinalPreTax = this.formatNumber(actualTotalFinalPreTax);
					this.totalFinalHST = this.formatNumber(actualTotalFinalHST);
					this.totalFinalAmount = this.formatNumber(actualTotalFinalAmount);
					this.totalFinalPercentage = Math.round((actualTotalFinalPercentage + Number.EPSILON) * 100) / 100;
				},
				onFieldBlur(index, field) {
					// Formatear el campo solo cuando se pierde el foco
					let item = this.items[index];
					item[field] = this.formatNumber(this.cleanNumber(item[field]));
					this.calculateTotals(index);  // recalcular totales después del formato
				},

				onFieldInput(index, field, event) {
					// Actualizar el valor en tiempo real sin formatear para evitar mover el cursor
					let item = this.items[index];
					item[field] = event.target.value;
				},
				formattedNumber(index, field) {
					let item = this.items[index];
					if (!item) {
						return '';
					}
					return item[field] ? this.formatNumber(item[field]) : '';
				}

			},
			mounted() {
				this.calculateFinalTotals();
			},
			computed: {
				formattedValue() {  
					// Formatea el valor crudo para mostrarlo 
					return this.rawValue ? this.formatNumber(this.rawValue) : '';
				},
				prepareItems() {
					let newItems = [];
					this.items.forEach(item => {
						newItems.push({ 
							mandate: item.mandate, 
							preTaxAmount: this.cleanNumber(item.preTaxAmount), 
							hst: this.cleanNumber(item.hst), 
							total: this.cleanNumber(item.total), 
							percentOfInvoice: item.percentOfInvoice
						});
					});
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