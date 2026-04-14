<?php
/**
 * DLSU — Control panel iframe (Bootstrap + tabs + DataTables).
 * Returns PSTOOLS_RESPONSE_HTML for a Screen / Iframe control.
 *
 * Endpoints are always: {API_HOST}/pstools/script/{slug} (JSON scripts vs HTML iframe — see Endpoints tab).
 * Override slugs via $data: pstools_slug_config, pstools_slug_sync, pstools_slug_request_files, pstools_slug_iframe.
 * Production host/token: pass in screen $data (api_host_prod, production_api_token, …), _env, or env vars;
 * iframe "Run sync" fields override when filled. See DLSU/README.md.
 * Required server env on dev: API_HOST, API_TOKEN (used by backend PSTools only).
 */

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($data) || !is_array($data)) {
    $data = [];
}

/**
 * Same resolution order as DLSU_User_Group_Collection_Sync / Config_Status (production API base URL).
 */
function dlsu_iframe_resolve_prod_host(array $data): string
{
    $chain = [
        $data['api_host_prod'] ?? null,
        $data['production_api_host'] ?? null,
        $data['production_host'] ?? null,
        $data['PM_PROD_API_HOST'] ?? null,
    ];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['api_host_prod'] ?? null;
        $chain[] = $env['production_api_host'] ?? null;
        $chain[] = $env['PM_PROD_API_HOST'] ?? null;
        $chain[] = $env['DLSU_PRODUCTION_API_HOST'] ?? null;
    }
    foreach (['DLSU_PRODUCTION_API_HOST', 'PM_PROD_API_HOST', 'DLSU_PROD_API_HOST'] as $ev) {
        $g = getenv($ev);
        $chain[] = ($g !== false && $g !== '') ? $g : null;
    }
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $s = rtrim(trim((string) $v), '/');
        if ($s !== '') {
            return $s;
        }
    }
    return '';
}

function dlsu_iframe_prod_token_is_preconfigured(array $data): bool
{
    $chain = [
        $data['api_token_prod'] ?? null,
        $data['production_api_token'] ?? null,
        $data['production_token'] ?? null,
        $data['pm_prod_api_token'] ?? null,
        $data['PM_PROD_API_TOKEN'] ?? null,
    ];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['api_token_prod'] ?? null;
        $chain[] = $env['production_api_token'] ?? null;
        $chain[] = $env['PM_PROD_API_TOKEN'] ?? null;
        $chain[] = $env['DLSU_PRODUCTION_API_TOKEN'] ?? null;
    }
    foreach (['DLSU_PRODUCTION_API_TOKEN', 'PM_PROD_API_TOKEN', 'DLSU_PROD_API_TOKEN'] as $ev) {
        $g = getenv($ev);
        $chain[] = ($g !== false && $g !== '') ? $g : null;
    }
    foreach ($chain as $v) {
        if ($v !== null && trim((string) $v) !== '') {
            return true;
        }
    }
    return false;
}

$API_HOST = rtrim((string) ($data['_env']['API_HOST'] ?? getenv('API_HOST') ?: ''), '/');
$prodHostDefault = dlsu_iframe_resolve_prod_host($data);
$prodTokenPreconfigured = dlsu_iframe_prod_token_is_preconfigured($data);
$requestId = (int) ($data['request_id'] ?? $data['requestId'] ?? ($data['_request']['id'] ?? 0));

$slugConfig = preg_replace('/[^a-z0-9\-]/', '', (string) ($data['pstools_slug_config'] ?? 'dlsu-config-status'));
$slugSync = preg_replace('/[^a-z0-9\-]/', '', (string) ($data['pstools_slug_sync'] ?? 'dlsu-user-group-collection-sync'));
$slugFiles = preg_replace('/[^a-z0-9\-]/', '', (string) ($data['pstools_slug_request_files'] ?? 'dlsu-request-files'));
$slugIframe = preg_replace('/[^a-z0-9\-]/', '', (string) ($data['pstools_slug_iframe'] ?? 'dlsu-iframe-control-panel'));

if ($slugConfig === '') {
    $slugConfig = 'dlsu-config-status';
}
if ($slugSync === '') {
    $slugSync = 'dlsu-user-group-collection-sync';
}
if ($slugFiles === '') {
    $slugFiles = 'dlsu-request-files';
}
if ($slugIframe === '') {
    $slugIframe = 'dlsu-iframe-control-panel';
}

$urlConfig = $API_HOST !== '' ? $API_HOST . '/pstools/script/' . $slugConfig : '';
$urlSync = $API_HOST !== '' ? $API_HOST . '/pstools/script/' . $slugSync : '';
$urlFiles = $API_HOST !== '' ? $API_HOST . '/pstools/script/' . $slugFiles : '';
$urlIframe = $API_HOST !== '' ? $API_HOST . '/pstools/script/' . $slugIframe : '';

$requestDataJson = json_encode($data ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

/**
 * PSTools endpoints: always {API_HOST}/pstools/script/{slug} — not static files.
 * Response types: JSON (script return array) vs HTML (PSTOOLS_RESPONSE_HTML for Screen iframe).
 */
$dlsuEsc = static function (string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$endpointsTableBody = '';
$endpointRows = [
    ['Configuration status', $slugConfig, 'JSON (POST body → JSON)', $urlConfig],
    ['User / group / collection sync', $slugSync, 'JSON (POST body → JSON)', $urlSync],
    ['Request files listing', $slugFiles, 'JSON (POST body → JSON)', $urlFiles],
    ['This control panel (Screen iframe)', $slugIframe, 'HTML (PSTOOLS_RESPONSE_HTML)', $urlIframe],
];
foreach ($endpointRows as $row) {
    $endpointsTableBody .= '<tr><td>' . $dlsuEsc($row[0]) . '</td><td><code>' . $dlsuEsc($row[1]) . '</code></td><td>' . $dlsuEsc($row[2]) . '</td><td class="text-break"><small>' . $dlsuEsc($row[3]) . '</small></td></tr>';
}

$style = <<<'CSS'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  body{background:#f0f4f8;font-size:14px;margin:0;padding:0;}
  .dlsu-header{background:linear-gradient(135deg,#1e3a5f 0%,#2d5a87 100%);color:#fff;padding:14px 18px;border-radius:0 0 12px 12px;}
  .dlsu-kpi{display:flex;flex-wrap:wrap;gap:10px;margin:12px 0;}
  .dlsu-kpi .card{min-width:140px;flex:1;border:none;box-shadow:0 2px 8px rgba(0,0,0,.08);border-radius:10px;}
  .dlsu-kpi .card-body{padding:12px 14px;}
  .dlsu-kpi .num{font-size:1.6rem;font-weight:700;line-height:1;}
  .dlsu-kpi .lbl{font-size:.75rem;text-transform:uppercase;color:#64748b;letter-spacing:.04em;}
  .nav-tabs .nav-link{font-weight:600;color:#475569;}
  .nav-tabs .nav-link.active{color:#1e3a5f;border-color:#dee2e6 #dee2e6 #fff;}
  .badge-sync{background:#16a34a;}
  .badge-nosync{background:#dc2626;}
  pre#reqMonitor{max-height:240px;overflow:auto;font-size:11px;background:#0f172a;color:#e2e8f0;border-radius:8px;padding:10px;}
  #logPanel{max-height:280px;overflow:auto;font-size:12px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px;}
  .log-line{border-bottom:1px solid #f1f5f9;padding:6px 4px;}
  .log-line.err{color:#b91c1c;}
  .log-line.ok{color:#15803d;}
  table.dataTable thead th{background:#1e3a5f!important;color:#fff!important;font-size:.78rem;}
  .alert-soft{background:#fff8e6;border:1px solid #fcd34d;color:#92400e;}
  .loader-overlay{position:fixed;inset:0;background:rgba(255,255,255,.75);display:none;align-items:center;justify-content:center;z-index:9999;}
</style>
CSS;

$body = <<<'HTML'
<|div class="loader-overlay" id="dlsuLoader"><|div class="text-center"><|div class="spinner-border text-primary" role="status"><|/div><|div class="mt-2 small text-muted">Working…<|/div><|/div><|/div>

<|div class="dlsu-header d-flex flex-wrap justify-content-between align-items-center">
  <|div>
    <|h5 class="mb-0"><|i class="fas fa-sync-alt mr-2"><|/i> DLSU — Dev / Prod alignment<|/h5>
    <|div class="small mt-1 opacity-90">Visibility for configuration, sync status, and request files<|/div>
  <|/div>
  <|div class="text-right small">
    <|div>Request ID: <|strong id="hdrRequestId">—<|/strong><|/div>
    <|div class="mt-1" id="hdrEnvLine"><|/div>
    <|div class="mt-1 text-white-50" id="hdrProdLine" style="max-width:420px;margin-left:auto;"><|small>Prod API: <|span id="hdrProdHost">—<|/span><|/small><|/div>
  <|/div>
<|/div>

<|div class="container-fluid px-3 py-2">
  <|div id="dlsuAlerts"><|/div>

  <|div class="dlsu-kpi" id="kpiRow">
    <|div class="card"><|div class="card-body">
      <|div class="lbl">Prod users<|/div><|div class="num text-primary" id="kpiProdUsers">—<|/div>
    <|/div><|/div>
    <|div class="card"><|div class="card-body">
      <|div class="lbl">Dev users<|/div><|div class="num text-info" id="kpiDevUsers">—<|/div>
    <|/div><|/div>
    <|div class="card"><|div class="card-body">
      <|div class="lbl">Missing on dev<|/div><|div class="num text-danger" id="kpiMissing">—<|/div>
    <|/div><|/div>
    <|div class="card"><|div class="card-body">
      <|div class="lbl">Group diffs<|/div><|div class="num text-warning" id="kpiGroupDiff">—<|/div>
    <|/div><|/div>
    <|div class="card"><|div class="card-body">
      <|div class="lbl">Collection gap<|/div><|div class="num text-secondary" id="kpiCollGap">—<|/div>
    <|/div><|/div>
  <|/div>

  <|ul class="nav nav-tabs mt-2" role="tablist">
    <|li class="nav-item"><|a class="nav-link active" data-toggle="tab" href="#tab-dash">Dashboard<|/a><|/li>
    <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#tab-cfg">Configuration log<|/a><|/li>
    <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#tab-sync">Run sync<|/a><|/li>
    <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#tab-detail">Synced / not synced<|/a><|/li>
    <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#tab-files">Endpoints &amp; files<|/a><|/li>
    <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#tab-data">Request data<|/a><|/li>
  <|/ul>

  <|div class="tab-content border border-top-0 bg-white p-3 rounded-bottom mb-3">

    <|div class="tab-pane fade show active" id="tab-dash">
      <|p class="text-muted small mb-2">Last run summary. Use <strong>Run sync<|/strong> to refresh.<|/p>
      <|div id="dashSummary" class="small"><|/div>
      <|div class="mt-3"><|h6 class="text-muted">Activity log (this browser)<|/h6><|div id="logPanel"><|/div><|/div>
    <|/div>

    <|div class="tab-pane fade" id="tab-cfg">
      <|p class="text-muted small">Environment validation from PSTools <code>dlsu-config-status<|/code>. No secrets shown in full.<|/p>
      <|div id="cfgIssues"><|/div>
      <|pre class="small bg-light p-2 rounded border" id="cfgEcho"><|/pre>
    <|/div>

    <|div class="tab-pane fade" id="tab-sync">
      <|div class="row">
        <|div class="col-md-4 mb-2">
          <|label>Action<|/label>
          <|select id="inpAction" class="form-control form-control-sm">
            <|option value="report" selected>report (read-only)<|/option>
            <|option value="apply">apply (writes to dev)<|/option>
          <|/select>
        <|/div>
        <|div class="col-md-4 mb-2">
          <|label>Scope<|/label>
          <|select id="inpScope" class="form-control form-control-sm">
            <|option value="all" selected>all<|/option>
            <|option value="users">users<|/option>
            <|option value="groups">groups<|/option>
            <|option value="collections">collections<|/option>
          <|/select>
        <|/div>
        <|div class="col-md-4 mb-2">
          <|label>Password (apply + new users only)<|/label>
          <|input type="password" id="inpPass" class="form-control form-control-sm" placeholder="from env or override" autocomplete="off" />
        <|/div>
      <|/div>
      <|div class="row border-top pt-2 mt-1">
        <|div class="col-md-6 mb-2">
          <|label>Production API base URL<|/label>
          <|input type="text" id="inpProdHost" class="form-control form-control-sm" placeholder="https://prod.example.com/api/1.0" autocomplete="off" />
          <|small class="text-muted">Same as <code>api_host_prod<|/code> / <code>production_api_host<|/code>. Filled here overrides request/env when non-empty.<|/small>
        <|/div>
        <|div class="col-md-6 mb-2">
          <|label>Production bearer token<|/label>
          <|input type="password" id="inpProdToken" class="form-control form-control-sm" placeholder="" autocomplete="off" />
          <|small class="text-muted" id="lblProdTokenHint"><|/small>
        <|/div>
      <|/div>
      <|button type="button" class="btn btn-primary btn-sm" id="btnRunSync"><|i class="fas fa-play"><|/i> Run PSTools sync<|/button>
      <|button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="btnRefreshCfg"><|i class="fas fa-redo"><|/i> Re-check configuration<|/button>
      <|p class="text-muted small mt-3 mb-0">Backend URL: <code id="lblSyncUrl"><|/code><|/p>
    <|/div>

    <|div class="tab-pane fade" id="tab-detail">
      <|ul class="nav nav-pills mb-2 small">
        <|li class="nav-item"><|a class="nav-link active" data-toggle="tab" href="#sub-miss">Users missing on dev<|/a><|/li>
        <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#sub-grp">Group mismatches<|/a><|/li>
        <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#sub-coll">Collections<|/a><|/li>
        <|li class="nav-item"><|a class="nav-link" data-toggle="tab" href="#sub-json">Full JSON<|/a><|/li>
      <|/ul>
      <|div class="tab-content">
        <|div class="tab-pane fade show active" id="sub-miss">
          <|table id="tblMissing" class="table table-sm table-striped w-100"><|thead><|tr><|th>username<|/th><|th>email<|/th><|th>status<|/th><|/tr><|/thead><|tbody><|/tbody><|/table>
        <|/div>
        <|div class="tab-pane fade" id="sub-grp">
          <|table id="tblGrp" class="table table-sm table-striped w-100"><|thead><|tr><|th>user<|/th><|th>prod groups<|/th><|th>dev groups<|/th><|/tr><|/thead><|tbody><|/tbody><|/table>
        <|/div>
        <|div class="tab-pane fade" id="sub-coll">
          <|div id="collDetail" class="small text-muted"><|/div>
        <|/div>
        <|div class="tab-pane fade" id="sub-json">
          <|pre id="fullJsonOut" class="small bg-dark text-light p-2 rounded" style="max-height:360px;overflow:auto;"><|/pre>
        <|/div>
      <|/div>
    <|/div>

    <|div class="tab-pane fade" id="tab-files">
      <|p class="text-muted small mb-2">
        Integration uses <strong>PM PSTools script URLs<|/strong> only — pattern
        <code>{API_HOST}/pstools/script/{slug}<|/code>. Runtime calls are not loaded from static repo documents.
        Typical scripts return <strong>JSON<|/strong>; the Screen iframe returns <code>PSTOOLS_RESPONSE_HTML<|/code> (<strong>HTML<|/strong>).
      <|/p>
      <|h6 class="text-secondary">Endpoints (this API host)<|/h6>
      <|table class="table table-sm table-bordered w-100 mb-4"><|thead><|tr>
        <|th>What<|/th><|th>Slug<|/th><|th>Response<|/th><|th>Full URL<|/th>
      <|/tr><|/thead><|tbody>
__DLSU_ENDPOINTS_ROWS__
      <|/tbody><|/table>
      <|h6 class="text-secondary">Request attachments<|/h6>
      <|div id="filesAlert"><|/div>
      <|table id="tblFiles" class="table table-sm table-striped w-100"><|thead><|tr><|th>id<|/th><|th>name<|/th><|th>created<|/th><|th>size<|/th><|th>where<|/th><|/tr><|/thead><|tbody><|/tbody><|/table>
    <|/div>

    <|div class="tab-pane fade" id="tab-data">
      <|p class="text-muted small">Raw <code>\$data<|/code> passed into this iframe PSTools script (merge with sync payload).<|/p>
      <|pre id="reqMonitor"><|/pre>
    <|/div>
  <|/div>
<|/div>
HTML;

$body = str_replace('__DLSU_ENDPOINTS_ROWS__', $endpointsTableBody, $body);

$script = <<<'JS'
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script>
(function(){
  var CONFIG = {
    apiHost: "__API_HOST__",
    requestId: __REQUEST_ID__,
    pstools: { config: "__URL_CONFIG__", sync: "__URL_SYNC__", files: "__URL_FILES__" },
    requestData: __REQUEST_DATA__,
    prodHostDefault: "__PROD_HOST_DEFAULT__",
    prodTokenPreconfigured: __PROD_TOKEN_PRE__
  };
  var LSKEY = "dlsu_sync_log_v1";
  var lastResp = null;

  function showLoader(v){ $("#dlsuLoader").css("display", v ? "flex" : "none"); }

  function appendLog(line, isErr){
    var t = new Date().toISOString();
    var el = $('<|div class="log-line"/>').addClass(isErr ? "err" : "ok").text(t + " — " + line);
    $("#logPanel").prepend(el);
  }

  function loadLocalLog(){
    try {
      var raw = localStorage.getItem(LSKEY);
      if(!raw) return;
      var a = JSON.parse(raw);
      if(!Array.isArray(a)) return;
      $("#logPanel").empty();
      a.slice(-40).reverse().forEach(function(x){
        var el = $('<|div class="log-line"/>').addClass(x.err ? "err" : "ok").text((x.t||"") + " — " + (x.msg||""));
        $("#logPanel").append(el);
      });
    } catch(e){}
  }

  function saveLog(msg, err){
    try {
      var a = JSON.parse(localStorage.getItem(LSKEY) || "[]");
      if(!Array.isArray(a)) a = [];
      a.push({ t: new Date().toISOString(), msg: msg, err: !!err });
      while(a.length > 80) a.shift();
      localStorage.setItem(LSKEY, JSON.stringify(a));
    } catch(e){}
    appendLog(msg, err);
  }

  function postPSTools(url, body){
    return $.ajax({
      url: url,
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify(body || {}),
      dataType: "json"
    });
  }

  function renderAlertsFromConfig(resp){
    $("#dlsuAlerts .dlsu-cfg-alert").remove();
    if(!resp || !resp.issues) return;
    resp.issues.forEach(function(is){
      var sev = is.severity === "error" ? "danger" : "warning";
      $("#dlsuAlerts").append(
        '<|div class="alert alert-'+sev+' py-2 small mb-1 dlsu-cfg-alert" role="alert"><|strong>'+(is.code||"")+"<|/strong> "+(is.message||"")+"<|/div>"
      );
    });
    if(resp.configuration_ok === false){
      $("#dlsuAlerts").prepend('<|div class="alert alert-soft py-2 small mb-2 dlsu-cfg-alert"><|i class="fas fa-exclamation-triangle mr-1"><|/i> Environment is not fully configured — sync may fail until hosts/tokens are set (env or request data).<|/div>');
    }
  }

  function renderCfgTab(resp){
    $("#cfgIssues").empty();
    if(!resp) return;
    renderAlertsFromConfig(resp);
    if(resp.issues && resp.issues.length){
      var h = "<|h6 class='mt-2'>Configuration issues (detail)<|/h6><|ul class='small'>";
      resp.issues.forEach(function(i){ h += "<|li><|code>"+(i.code||"")+"<|/code> — "+(i.message||"")+"<|/li>"; });
      h += "<|/ul>";
      $("#cfgIssues").html(h);
    } else {
      $("#cfgIssues").html("<|p class='text-success small mb-0'><|i class='fas fa-check'><|/i> No blocking issues.<|/p>");
    }
    $("#cfgEcho").text(JSON.stringify(resp.configuration_echo || {}, null, 2));
  }

  function applyKpisFromResponse(r){
    if(!r) return;
    var sc = r.summary_counts || {};
    $("#kpiProdUsers").text(sc.prod_users != null ? sc.prod_users : "—");
    $("#kpiDevUsers").text(sc.dev_users != null ? sc.dev_users : "—");
    $("#kpiMissing").text(sc.missing_users_on_dev != null ? sc.missing_users_on_dev : "—");
    $("#kpiGroupDiff").text(sc.group_membership_diffs != null ? sc.group_membership_diffs : "—");
    $("#kpiCollGap").text(sc.collection_missing_on_dev != null ? sc.collection_missing_on_dev : "—");
    var ok = r.success !== false && !r.error;
    $("#dashSummary").html(
      "<|p class='mb-1'><|span class='badge badge-"+(ok?"success":"danger")+"'>"+(ok?"OK":"FAILED")+"<|/span> "+
      " action=<|code>"+(r.action||"")+"<|/code> scope=<|code>"+(r.scope||"")+"<|/code><|/p>"+
      "<|p class='text-muted mb-0 small'>configuration_echo.request_id: "+(r.configuration_echo && r.configuration_echo.request_id != null ? r.configuration_echo.request_id : "—")+"<|/p>"
    );
  }

  function fillDetailTables(r){
    lastResp = r;
    $("#fullJsonOut").text(JSON.stringify(r, null, 2));

    var miss = (r.users_missing_on_dev) || [];
    if($.fn.DataTable.isDataTable("#tblMissing")) $("#tblMissing").DataTable().destroy();
    $("#tblMissing tbody").empty();
    miss.forEach(function(u){
      $("#tblMissing tbody").append("<|tr><|td>"+esc(u.username)+"<|/td><|td>"+esc(u.email)+"<|/td><|td>"+esc(u.status)+"<|/td><|/tr>");
    });
    if(miss.length) $("#tblMissing").DataTable({ paging: miss.length > 15, pageLength: 15, searching: true });

    var gd = (r.group_membership_diffs) || [];
    if($.fn.DataTable.isDataTable("#tblGrp")) $("#tblGrp").DataTable().destroy();
    $("#tblGrp tbody").empty();
    gd.forEach(function(x){
      $("#tblGrp tbody").append("<|tr><|td>"+esc(x.username)+"<|/td><|td>"+esc(JSON.stringify(x.prod_groups))+"<|/td><|td>"+esc(JSON.stringify(x.dev_groups))+"<|/td><|/tr>");
    });
    if(gd.length) $("#tblGrp").DataTable({ paging: gd.length > 15, pageLength: 15 });

    var c = r.collections || null;
    if(c && !c.error){
      $("#collDetail").html(
        "<|p>Prod rows: <strong>"+(c.prod_row_count||0)+"<|/strong> — Dev rows: <strong>"+(c.dev_row_count||0)+"<|/strong> — Missing on dev: <strong>"+(c.missing_on_dev||0)+"<|/strong><|/p>"+
        "<|p class='small'>Match key: <|code>"+esc(c.match_key||"")+"<|/code><|/p>"
      );
    } else {
      $("#collDetail").text(c && c.error ? ("Collection error: "+c.error) : "No collection data in this response (set collection IDs / scope).");
    }
  }

  function esc(s){ return String(s==null?"":s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }

  function loadRequestFiles(){
    $("#filesAlert").empty();
    if(!CONFIG.requestId || CONFIG.requestId <= 0){
      $("#filesAlert").html('<|div class="alert alert-warning py-2 small">Request ID not set — cannot list PM attachments. Pass request_id in screen data.<|/div>');
      return;
    }
    if(!CONFIG.pstools.files){
      $("#filesAlert").html('<|div class="alert alert-danger py-2 small">Files PSTools URL missing.<|/div>');
      return;
    }
    showLoader(true);
    postPSTools(CONFIG.pstools.files, { request_id: CONFIG.requestId })
      .done(function(resp){
        showLoader(false);
        if(!resp.success){
          var nf = (resp.error_code === "E_NOT_FOUND");
          $("#filesAlert").html('<|div class="alert '+(nf?"alert-warning":"alert-danger")+' py-2 small">'+(resp.error||"Error")+"<|/div>");
          if($.fn.DataTable.isDataTable("#tblFiles")) $("#tblFiles").DataTable().destroy();
          $("#tblFiles tbody").empty();
          return;
        }
        var rows = resp.data || [];
        if($.fn.DataTable.isDataTable("#tblFiles")) $("#tblFiles").DataTable().destroy();
        $("#tblFiles tbody").empty();
        rows.forEach(function(f){
          $("#tblFiles tbody").append("<|tr><|td>"+esc(f.id)+"<|/td><|td>"+esc(f.name)+"<|/td><|td>"+esc(f.created_at)+"<|/td><|td>"+esc(f.size)+"<|/td><|td><|small>"+esc(f.location)+"<|/small><|/td><|/tr>");
        });
        $("#tblFiles").DataTable({ paging: rows.length > 10, pageLength: 10 });
      })
      .fail(function(xhr){
        showLoader(false);
        $("#filesAlert").html('<|div class="alert alert-danger py-2 small">HTTP error listing files<|/div>');
      });
  }

  function mergePayload(extra){
    var o = {};
    try { o = JSON.parse(JSON.stringify(CONFIG.requestData || {})); } catch(e){}
    if(extra) for(var k in extra) o[k] = extra[k];
    var ph = $("#inpProdHost").val();
    if(ph && String(ph).trim()) o.api_host_prod = String(ph).trim().replace(/\/+$/,"");
    var pt = $("#inpProdToken").val();
    if(pt && String(pt).trim()) o.api_token_prod = String(pt).trim();
    return o;
  }

  function runConfigCheck(){
    if(!CONFIG.pstools.config){ $("#cfgIssues").html("<|p class='text-danger'>Config PSTools URL empty — set API_HOST.<|/p>"); return; }
    showLoader(true);
    postPSTools(CONFIG.pstools.config, mergePayload({}))
      .done(function(resp){ showLoader(false); renderCfgTab(resp); saveLog("Configuration check: "+(resp.configuration_ok?"OK":"issues"), !resp.configuration_ok); })
      .fail(function(){ showLoader(false); renderCfgTab(null); $("#cfgIssues").html("<|p class='text-danger'>Config PSTools call failed (network or 404).<|/p>"); saveLog("Configuration check failed", true); });
  }

  function runSync(){
    if(!CONFIG.pstools.sync){ alert("Sync PSTools URL empty"); return; }
    var pl = mergePayload({
      action: $("#inpAction").val(),
      scope: $("#inpScope").val()
    });
    var pw = $("#inpPass").val();
    if(pw) pl.new_user_password_for_apply = pw;
    showLoader(true);
    postPSTools(CONFIG.pstools.sync, pl)
      .done(function(resp){
        showLoader(false);
        applyKpisFromResponse(resp);
        fillDetailTables(resp);
        var ok = resp && resp.success !== false && !resp.error;
        saveLog("Sync "+(pl.action||"")+"/"+(pl.scope||"")+": "+(ok?"OK":"FAIL"), !ok);
        if(resp && resp.error) $("#dlsuAlerts").prepend('<|div class="alert alert-danger py-2 small">'+esc(resp.error)+"<|/div>");
      })
      .fail(function(xhr){
        showLoader(false);
        saveLog("Sync HTTP failure", true);
        $("#dlsuAlerts").prepend('<|div class="alert alert-danger py-2 small">Sync request failed (check slug and PSTools script).<|/div>');
      });
  }

  $("#hdrRequestId").text(CONFIG.requestId > 0 ? CONFIG.requestId : "—");
  $("#hdrEnvLine").text(CONFIG.apiHost ? ("Dev API: " + CONFIG.apiHost) : "API_HOST missing");
  $("#hdrProdHost").text(CONFIG.prodHostDefault || "not set");
  if(CONFIG.prodTokenPreconfigured){
    $("#lblProdTokenHint").text("Production token may already be set via environment or request data — leave blank to use it, or paste to override.");
  } else {
    $("#lblProdTokenHint").text("Paste production bearer token here if it is not set on the script executor or in request data.");
  }
  if(CONFIG.prodHostDefault) $("#inpProdHost").val(CONFIG.prodHostDefault);
  $("#reqMonitor").text(JSON.stringify(CONFIG.requestData, null, 2));
  $("#lblSyncUrl").text(CONFIG.pstools.sync || "—");
  loadLocalLog();

  $("#btnRunSync").on("click", runSync);
  $("#btnRefreshCfg").on("click", runConfigCheck);

  $('a[data-toggle="tab"][href="#tab-files"]').on("shown.bs.tab", function(){ loadRequestFiles(); });
  $('a[data-toggle="tab"][href="#tab-cfg"]').on("shown.bs.tab", function(){ runConfigCheck(); });

  runConfigCheck();
})();
</script>
JS;

$scriptOut = str_replace(
    ['__API_HOST__', '__REQUEST_ID__', '__URL_CONFIG__', '__URL_SYNC__', '__URL_FILES__', '__REQUEST_DATA__', '__PROD_HOST_DEFAULT__', '__PROD_TOKEN_PRE__'],
    [
        addslashes($API_HOST),
        (string) max(0, $requestId),
        addslashes($urlConfig),
        addslashes($urlSync),
        addslashes($urlFiles),
        $requestDataJson,
        addslashes($prodHostDefault),
        $prodTokenPreconfigured ? 'true' : 'false',
    ],
    $script
);

$html = $style . '<body class="p-0">' . $body . '</body>' . $scriptOut;

/* Screen iframes expect PSTOOLS_RESPONSE_HTML only (see CS Dashboard / Sony examples). */
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html),
];
