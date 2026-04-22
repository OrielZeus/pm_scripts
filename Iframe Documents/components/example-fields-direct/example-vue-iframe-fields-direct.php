<?php
/*
 * Vue iframe — campos directos (nombre / email) para lectura desde la pantalla PM.
 *
 * Patrón northleaf: PSTOOLS_RESPONSE_HTML, placeholders <| para tags,
 * IDs iframe-* que el padre lee con $('#iframe-psTools').contents().find(...)
 *
 * Reference: northleaf_dev/scripts/740-poc-is-03-custom-grid-iframe-faster.php (hidden iframe-* publish-back).
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

$prefillName = isset($payload['PrefillFullName']) ? (string) $payload['PrefillFullName'] : '';
$prefillEmail = isset($payload['PrefillEmail']) ? (string) $payload['PrefillEmail'] : '';

$prefillNameJs = json_encode($prefillName, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$prefillEmailJs = json_encode($prefillEmail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Fields direct iframe</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
	<style>[v-cloak]{display:none}</style>
</head>
<body>
<div id="app" class="container py-3" v-cloak>
	<p class="text-muted small mb-3">Campos editables; el padre PM lee <code>#iframe-USER_NAME</code> y <code>#iframe-USER_EMAIL</code>.</p>
	<div class="mb-2">
		<label class="form-label" for="iframe-USER_NAME">Nombre</label>
		<|input type="text" class="form-control" id="iframe-USER_NAME" v-model.trim="userName" autocomplete="name">
	</div>
	<div class="mb-2">
		<label class="form-label" for="iframe-USER_EMAIL">Correo</label>
		<|input type="email" class="form-control" id="iframe-USER_EMAIL" v-model.trim="userEmail" autocomplete="email">
	</div>
</div>
<script>
(function () {
	var initialName = {$prefillNameJs};
	var initialEmail = {$prefillEmailJs};

	new Vue({
		el: '#app',
		data: function () {
			return {
				userName: initialName,
				userEmail: initialEmail
			};
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
