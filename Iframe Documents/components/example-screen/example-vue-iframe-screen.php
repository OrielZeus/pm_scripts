<?php
/*
 * Minimal Vue iframe for Iframe Documents/components — mirrors northleaf pstools patterns.
 *
 * Reference: northleaf_dev/scripts/740-poc-is-03-custom-grid-iframe-faster.php
 * Return shape: PSTOOLS_RESPONSE_HTML + <| / </| placeholders (stripped below).
 *
 * Incoming data:
 * - ProcessMaker injects $data when run as script.
 * - IframeGridHtml passes JSON via ?data= (see screen computed IframeGridHtml).
 */

$payload = [];
if (!empty($data) && is_array($data)) {
    $payload = $data;
}
if (!empty($_GET['data'])) {
    $decoded = json_decode(urldecode($_GET['data']), true);
    if (is_array($decoded)) {
        $payload = array_merge($payload, $decoded);
    }
}

$exampleTitle = isset($payload['ExampleTitle']) ? (string) $payload['ExampleTitle'] : '';

// Demo rows — replace with real grid logic in production scripts.
$initialRowsJs = json_encode([
    ['id' => 1, 'label' => 'Demo row A', 'preTax' => 100, 'hst' => 13, 'total' => 113],
    ['id' => 2, 'label' => 'Demo row B', 'preTax' => 50, 'hst' => 6.5, 'total' => 56.5],
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$exampleTitleJs = json_encode($exampleTitle, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Example Vue iframe</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
	<style>[v-cloak]{display:none}</style>
</head>
<body>
<div id="app" class="container py-3" v-cloak>
	<h5 class="mb-3">{{ exampleTitle || 'Vue iframe example' }}</h5>
	<p class="text-muted small">Hidden inputs mirror northleaf iframe-* IDs for parent screen calcs.</p>
	<table class="table table-sm table-bordered">
		<thead><tr><th>Label</th><th>Pre-tax</th><th>HST</th><th>Line total</th></tr></thead>
		<tbody>
			<tr v-for="row in rows" :key="row.id">
				<td>{{ row.label }}</td>
				<td>{{ row.preTax }}</td>
				<td>{{ row.hst }}</td>
				<td>{{ row.total }}</td>
			</tr>
		</tbody>
	</table>

	<!-- Publish-back: parent reads #iframe-psTools contents -->
	<|input id="iframe-EXAMPLE_ROWS_JSON" :value="exampleRowsJson" class="form-control" readonly v-show="false">
	<|input id="iframe-IN_TOTAL_HST" :value="formatNum(sumHst)" class="form-control" readonly v-show="false">
	<|input id="iframe-IN_OUTSTANDING_TOTAL" :value="formatNum(outstandingTotal)" class="form-control" readonly v-show="false">
</div>
<script>
(function () {
	var initialTitle = {$exampleTitleJs};
	var initialRows = {$initialRowsJs};

	new Vue({
		el: '#app',
		data: function () {
			return {
				exampleTitle: initialTitle,
				rows: Array.isArray(initialRows) ? initialRows : []
			};
		},
		computed: {
			exampleRowsJson: function () {
				try {
					return JSON.stringify(this.rows);
				} catch (e) {
					return '[]';
				}
			},
			sumHst: function () {
				return this.rows.reduce(function (acc, r) {
					return acc + (parseFloat(r.hst) || 0);
				}, 0);
			},
			outstandingTotal: function () {
				// Demo: outstanding = sum of line totals (replace with real business logic).
				return this.rows.reduce(function (acc, r) {
					return acc + (parseFloat(r.total) || 0);
				}, 0);
			}
		},
		methods: {
			formatNum: function (n) {
				var v = parseFloat(n);
				return isNaN(v) ? '0' : String(v);
			}
		}
	});
})();
</script>
</body>
</html>
HTML;

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html),
];
