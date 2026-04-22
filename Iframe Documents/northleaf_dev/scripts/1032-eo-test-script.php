<?php
/**
 * Unified UI: process BPMN export + Forte-style asset backup (scripts/screens/executors: page export, export all, attach to request).
 * Uses PSTools slug eo---test-executor (Process_Export_Batch.php). Markup uses <| … </| for ProcessMaker.
 */

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($data) || !is_array($data)) {
    $data = [];
}

$env = $data['_env'] ?? [];
if (!is_array($env)) {
    $env = [];
}

$defaultBase = rtrim((string) ($env['API_HOST'] ?? getenv('API_HOST') ?: ''), '/');
$slug = preg_replace('/[^a-z0-9\-_]/', '', (string) ($data['pstools_slug_process_export'] ?? 'eo---test-executor'));
if ($slug === '') {
    $slug = 'eo---test-executor';
}

$urlBatch = $defaultBase !== '' ? ($defaultBase . '/pstools/script/' . $slug) : '';

$requestDataJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
if ($requestDataJson === false) {
    $requestDataJson = '{}';
}

$style = <<<'CSS'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  body { margin: 0; padding: 0; background: #f1f5f9; font-size: 14px; }
  .pex-wrap { max-width: 1200px; margin: 0 auto; padding: 16px 18px 32px; }
  .pex-hero {
    background: linear-gradient(135deg, #0c4a6e 0%, #155e75 50%, #0d9488 100%);
    color: #fff; border-radius: 14px; padding: 18px 20px; margin-bottom: 16px;
    box-shadow: 0 10px 30px rgba(15,23,42,.2);
  }
  .pex-hero h1 { font-size: 1.25rem; font-weight: 700; margin: 0 0 6px; }
  .pex-hero p { margin: 0; opacity: .92; font-size: .85rem; line-height: 1.45; }
  .pex-card {
    background: #fff; border-radius: 12px; padding: 16px 18px;
    box-shadow: 0 2px 12px rgba(15,23,42,.06); border: 1px solid #e2e8f0; margin-bottom: 14px;
  }
  .pex-card h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin: 0 0 10px; font-weight: 600; }
  .kpi { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: #0f172a; }
  .kpi-lbl { font-size: .68rem; text-transform: uppercase; color: #64748b; letter-spacing: .05em; margin-bottom: 4px; }
  .kpi-box { border-radius: 10px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; height: 100%; }
  pre.mini { max-height: 220px; overflow: auto; font-size: 10px; background: #0f172a; color: #e2e8f0; padding: 10px; border-radius: 8px; margin: 0; }
  .pex-hint { font-size: .78rem; color: #64748b; }
  #tblProcesses_wrapper .dataTables_processing { background: rgba(255,255,255,.92); border-radius: 8px; }
  table.dataTable tbody tr.selected { background-color: #e0f2fe !important; }
  .btn-forte.active { background: #0d9488 !important; color: #fff !important; border-color: #0d9488 !important; }
</style>
CSS;

$body = <<<'HTML'
<|div class="pex-wrap">
  <|div class="pex-hero">
    <|h1><|i class="fas fa-project-diagram mr-2"><|/i>Process export (BPMN + related assets)<|/h1>
    <|p>Set your <|strong>API base<|/strong> (Postman-style <|code>…/api/1.0<|/code>) and <|strong>bearer token<|/strong>. For <|strong>processes<|/strong>: BPMN + related assets and optional subprocess walk (<|strong>callActivity<|/strong>). For <|strong>scripts / screens / executors<|/strong>: same Forte flows as the legacy backup — page export, export all scripts/screens, attach archive to this request.<|/p>
  <|/div>

  <|div class="pex-card">
    <|h2><|i class="fas fa-plug mr-1"><|/i>Connection<|/h2>
    <|div class="form-row">
      <|div class="col-md-6 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpApiBase">API base URL (dev / default)<|/label>
        <|input type="text" id="inpApiBase" class="form-control form-control-sm" placeholder="https://tenant/api/1.0" autocomplete="off" value="__DEFAULT_BASE_ESC__" />
        <|div class="pex-hint mt-1">Used for REST, <|code>api_base<|/code> on the batch call, and PSTools URL when it matches your environment.<|/div>
      <|/div>
      <|div class="col-md-6 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpBearer">Bearer token (dev / default)<|/label>
        <|input type="password" id="inpBearer" class="form-control form-control-sm" placeholder="OAuth bearer" autocomplete="off" />
        <|div class="pex-hint mt-1">Sent as <|code>bearer_token<|/code> — this is the only token used for REST auth (not <|code>api_token<|/code>).<|/div>
      <|/div>
    <|/div>
    <|div class="form-row align-items-end">
      <|div class="col-md-4 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1">Environment (Forte)<|/label>
        <|div>
          <|label class="mr-3 mb-0"><|input type="radio" name="inpTarget" id="inpTargetDev" value="dev" checked /> Dev<|/label>
          <|label class="mb-0"><|input type="radio" name="inpTarget" id="inpTargetProd" value="prod" /> Prod<|/label>
        <|/div>
        <|div class="pex-hint mt-1">When API base/token are empty, the batch resolves host/token from <|code>_env<|/code> / optional prod fields below.<|/div>
      <|/div>
      <|div class="col-md-4 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpMode">Mode<|/label>
        <|select id="inpMode" class="form-control form-control-sm">
          <|option value="api">API — REST<|/option>
          <|option value="sql">SQL — Pro Service Tools<|/option>
        <|/select>
      <|/div>
      <|div class="col-md-4 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1">PSTools batch URL<|/label>
        <|input type="text" id="inpPstoolsUrl" class="form-control form-control-sm bg-light" readonly="readonly" value="__URL_BATCH_ESC__" />
      <|/div>
    <|/div>
    <|div class="form-row">
      <|div class="col-md-6 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpProdHost">Prod API base (optional)<|/label>
        <|input type="text" id="inpProdHost" class="form-control form-control-sm" placeholder="https://tenant…/api/1.0" autocomplete="off" />
      <|/div>
      <|div class="col-md-6 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpProdToken">Prod bearer token (optional)<|/label>
        <|input type="password" id="inpProdToken" class="form-control form-control-sm" autocomplete="off" />
      <|/div>
    <|/div>
    <|div class="form-row">
      <|div class="col-md-12 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="selEntity">Browse — entity<|/label>
        <|select id="selEntity" class="form-control form-control-sm">
          <|option value="processes">Processes (BPMN export)<|/option>
          <|option value="scripts">Scripts<|/option>
          <|option value="screens">Screens<|/option>
          <|option value="executors">Script executors<|/option>
        <|/select>
        <|div class="pex-hint mt-1">One PSTools batch: <|code>list<|/code>, <|code>export_chunk<|/code>, <|code>export_all<|/code> (scripts/screens), <|code>manifest<|/code> / <|code>export_process<|/code> for processes.<|/div>
      <|/div>
    <|/div>
  <|/div>

  <|div class="row mb-3">
    <|div class="col-6 col-md-3 mb-2">
      <|div class="kpi-box text-center h-100">
        <|div class="kpi-lbl"><|i class="fas fa-code text-primary mr-1"><|/i>Scripts<|/div>
        <|div class="kpi text-primary" id="kpiScripts">—<|/div>
      <|/div>
    <|/div>
    <|div class="col-6 col-md-3 mb-2">
      <|div class="kpi-box text-center h-100">
        <|div class="kpi-lbl"><|i class="fas fa-window-maximize text-success mr-1"><|/i>Screens<|/div>
        <|div class="kpi text-success" id="kpiScreens">—<|/div>
      <|/div>
    <|/div>
    <|div class="col-6 col-md-3 mb-2">
      <|div class="kpi-box text-center h-100">
        <|div class="kpi-lbl"><|i class="fas fa-cogs text-warning mr-1"><|/i>Executors<|/div>
        <|div class="kpi text-warning" id="kpiExecutors">—<|/div>
      <|/div>
    <|/div>
    <|div class="col-6 col-md-3 mb-2">
      <|div class="kpi-box text-center h-100">
        <|div class="kpi-lbl"><|i class="fas fa-stream text-info mr-1"><|/i>Processes<|/div>
        <|div class="kpi text-info" id="kpiProcesses">—<|/div>
      <|/div>
    <|/div>
  <|/div>

  <|div class="pex-card">
    <|div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
      <|h2 class="mb-0"><|i class="fas fa-list mr-1"><|/i><|span id="lblBrowseTitle">Browse<|/span><|/h2>
      <|div>
        <|button type="button" class="btn btn-sm btn-primary" id="btnLoadCounts"><|i class="fas fa-sync-alt mr-1"><|/i>Refresh totals<|/button>
      <|/div>
    <|/div>

    <|div id="wrapProcessOpts">
      <|div class="form-row mb-2">
        <|div class="col-md-4 mb-2">
          <|div class="form-check">
            <|input class="form-check-input" type="checkbox" id="chkSubprocess" checked />
            <|label class="form-check-label small" for="chkSubprocess">Include subprocesses (callActivity)<|/label>
          <|/div>
        <|/div>
        <|div class="col-md-4 mb-2">
          <|label class="small font-weight-bold text-secondary mb-0" for="inpSubDepth">Max subprocess depth<|/label>
          <|input type="number" id="inpSubDepth" class="form-control form-control-sm" min="0" max="10" value="4" />
        <|/div>
        <|div class="col-md-4 mb-2">
          <|label class="small font-weight-bold text-secondary mb-1" for="selProcessArchive">Process export archive<|/label>
          <|select id="selProcessArchive" class="form-control form-control-sm">
            <|option value="auto">Auto (.zip or .tar.gz)<|/option>
            <|option value="tar_gz">.tar.gz<|/option>
            <|option value="zip">.zip<|/option>
            <|option value="json">JSON bundle (debug)<|/option>
          <|/select>
        <|/div>
      <|/div>
      <|div class="mb-2">
        <|button type="button" class="btn btn-sm btn-outline-primary mr-1" id="btnManifest" disabled><|i class="fas fa-sitemap mr-1"><|/i>Preview manifest<|/button>
        <|button type="button" class="btn btn-sm btn-success" id="btnExport" disabled><|i class="fas fa-file-archive mr-1"><|/i>Export selected process<|/button>
      <|/div>
    <|/div>

    <|div id="wrapForteOpts" class="d-none border rounded p-2 mb-3 bg-light">
      <|div class="form-row align-items-end mb-2">
        <|div class="col-md-4 mb-2 mb-md-0">
          <|label class="small font-weight-bold text-secondary mb-1" for="selForteArchive">Forte archive (chunk / export all)<|/label>
          <|select id="selForteArchive" class="form-control form-control-sm">
            <|option value="auto">Auto (.zip if available, else .tar.gz)<|/option>
            <|option value="tar_gz">.tar.gz only<|/option>
            <|option value="zip">.zip only (needs php-zip)<|/option>
            <|option value="json">JSON (debug)<|/option>
          <|/select>
        <|/div>
        <|div class="col-md-8">
          <|div class="form-check mt-1 d-none" id="wrapAttachRequest">
            <|input class="form-check-input" type="checkbox" id="chkAttachRequest" />
            <|label class="form-check-label small" for="chkAttachRequest">
              Save archive to <|strong>this request<|/strong> (<|code>POST /requests/…/files<|/code>). Default data name <|code>FORTE_CODE_BACKUP_EXPORT<|/code> — match a File Upload or request files.
            <|/label>
          <|/div>
          <|div class="mt-1 d-none" id="wrapRequestIdOverride">
            <|label class="small font-weight-bold text-secondary mb-0" for="inpRequestId">Request ID (override)<|/label>
            <|input type="text" id="inpRequestId" class="form-control form-control-sm" placeholder="From current task if empty" />
          <|/div>
        <|/div>
      <|/div>
      <|div class="d-flex flex-wrap align-items-center mb-2">
        <|button type="button" class="btn btn-sm btn-success mr-1 mb-1" id="btnExportPage"><|i class="fas fa-file-archive mr-1"><|/i>Export this page<|/button>
        <|button type="button" class="btn btn-sm btn-outline-success mr-1 mb-1" id="btnExportAllScripts" title="Full archive (.zip or .tar.gz)"><|i class="fas fa-download mr-1"><|/i>Export all scripts<|/button>
        <|button type="button" class="btn btn-sm btn-outline-success mb-1" id="btnExportAllScreens" title="Full archive (.zip or .tar.gz)"><|i class="fas fa-download mr-1"><|/i>Export all screens<|/button>
      <|/div>
      <|p class="pex-hint mb-0" id="lblPageHint"><|/p>
    <|/div>

    <|p class="pex-hint mb-2" id="lblSelection"><|/p>
    <|div class="table-responsive border rounded" style="overflow-x:auto;">
      <|table id="tblProcesses" class="table table-sm table-hover table-striped w-100 mb-0">
        <|thead class="thead-light"><|tr id="trTableHead">
          <|th>ID<|/th><|th id="thCol2">Name<|/th><|th id="thCol3">Status<|/th><|th>Updated<|/th>
        <|/tr><|/thead><|tbody><|/tbody>
      <|/table>
    <|/div>
  <|/div>

  <|div id="alertBox"><|/div>
  <|div class="pex-card mt-2">
    <|h2>Last response<|/h2>
    <|pre class="mini" id="outPre"><|/pre>
  <|/div>
<|/div>
HTML;

$body = str_replace(
    ['__URL_BATCH_ESC__', '__DEFAULT_BASE_ESC__'],
    [
        htmlspecialchars($urlBatch, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($defaultBase, ENT_QUOTES, 'UTF-8'),
    ],
    $body
);

$script = <<<'JS'
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script>
(function(){
  var CONFIG = { pstoolsUrl: "__URL_BATCH__", requestData: __REQUEST_DATA__ };
  var tbl = null;
  var selectedId = null;

  function apiBase(){
    var v = $("#inpApiBase").val();
    return v && String(v).trim() ? String(v).trim().replace(/\/+$/,"") : "";
  }

  function bearer(){
    var t = $("#inpBearer").val();
    return t && String(t).trim() ? String(t).trim() : "";
  }

  function passthroughFromScreen(){
    var rd = CONFIG.requestData || {};
    var o = {};
    if (rd.pstools_slug_process_export) o.pstools_slug_process_export = rd.pstools_slug_process_export;
    if (rd.api_sql_path) o.api_sql_path = rd.api_sql_path;
    if (rd.request_file_data_name) o.request_file_data_name = rd.request_file_data_name;
    if (rd.api_host_prod) o.api_host_prod = rd.api_host_prod;
    if (rd.api_token_prod) o.api_token_prod = rd.api_token_prod;
    return o;
  }

  function targetVal(){
    var v = $("input[name=inpTarget]:checked").val();
    return v === "prod" ? "prod" : "dev";
  }

  function requestIdFromScreen(){
    var rd = CONFIG.requestData || {};
    if (rd._request && rd._request.id != null && rd._request.id !== "") return String(rd._request.id);
    if (rd.request_id != null && rd.request_id !== "") return String(rd.request_id);
    return "";
  }

  function mergePayload(extra){
    var o = passthroughFromScreen();
    if (extra) for (var k in extra) o[k] = extra[k];
    o.api_base = apiBase();
    o.bearer_token = bearer();
    o.mode = $("#inpMode").val() || "api";
    o.target = targetVal();
    var ph = $("#inpProdHost").val();
    if (ph && String(ph).trim()) o.api_host_prod = String(ph).trim().replace(/\/+$/,"");
    var pt = $("#inpProdToken").val();
    if (pt && String(pt).trim()) o.api_token_prod = String(pt).trim();
    var rd = CONFIG.requestData || {};
    if (rd._env && typeof rd._env === "object") o._env = rd._env;
    return o;
  }

  function listEntity(){
    return $("#selEntity").val() || "processes";
  }

  function isProcessEntity(){
    return listEntity() === "processes";
  }

  function isForteAssetEntity(){
    var e = listEntity();
    return e === "scripts" || e === "screens" || e === "executors";
  }

  function toggleEntityPanels(){
    if (isProcessEntity()) {
      $("#wrapProcessOpts").removeClass("d-none");
      $("#wrapForteOpts").addClass("d-none");
      $("#lblBrowseTitle").text("Processes");
    } else {
      $("#wrapProcessOpts").addClass("d-none");
      $("#wrapForteOpts").removeClass("d-none");
      $("#lblBrowseTitle").text(
        listEntity() === "scripts" ? "Scripts" :
        listEntity() === "screens" ? "Screens" : "Script executors"
      );
    }
    var ex = listEntity() === "executors";
    $("#btnExportAllScripts, #btnExportAllScreens").toggleClass("d-none", ex);
  }

  function updatePageHint(){
    if (!tbl || !isForteAssetEntity()) {
      $("#lblPageHint").text("");
      return;
    }
    var inf = tbl.page.info();
    var pages = inf.pages;
    if (!isFinite(pages) || pages < 1) pages = 1;
    $("#lblPageHint").text(
      "Page " + (inf.page + 1) + " of " + pages + " · " + inf.length + " rows per page · " + inf.recordsTotal + " total · " + listEntity()
    );
  }

  function postBatch(body, ajaxOpts){
    var url = $("#inpPstoolsUrl").val() || CONFIG.pstoolsUrl;
    if (!url) return $.Deferred().reject("no pstools url");
    return $.ajax($.extend({
      url: url,
      type: "POST",
      contentType: "application/json",
      data: JSON.stringify(body || {}),
      dataType: "json"
    }, ajaxOpts || {}));
  }

  function sanitizeResponseForDebug(r){
    if (!r || typeof r !== "object") return r;
    var o = {};
    for (var k in r) {
      if (!Object.prototype.hasOwnProperty.call(r, k)) continue;
      if (k === "archive_base64" || k === "zip_base64") {
        var s = r[k];
        o[k] = (s != null && String(s).length) ? ("<omitted base64 len=" + String(s).length + " chars>") : s;
        continue;
      }
      o[k] = r[k];
    }
    return o;
  }

  function showErr(msg){
    $("#alertBox").html('<|div class="alert alert-danger py-2 small">' + (msg||"Error") + "<|/div>");
  }
  function clearErr(){ $("#alertBox").empty(); }

  function updatePstoolsUrl(){
    var b = apiBase();
    var rd = CONFIG.requestData || {};
    var slug = rd.pstools_slug_process_export || "eo---test-executor";
    if (b) $("#inpPstoolsUrl").val(b + "/pstools/script/" + slug);
    else $("#inpPstoolsUrl").val("");
  }

  function loadCounts(){
    clearErr();
    postBatch(mergePayload({ action: "counts" })).done(function(r){
      if (!r || r.success === false) { showErr(r && r.error ? r.error : "counts failed"); return; }
      var c = r.counts || {};
      $("#kpiScripts").text(c.scripts != null ? c.scripts : "—");
      $("#kpiScreens").text(c.screens != null ? c.screens : "—");
      $("#kpiExecutors").text(c.executors != null ? c.executors : "—");
      $("#kpiProcesses").text(c.processes != null ? c.processes : "—");
    }).fail(function(xhr){
      showErr("HTTP error on counts");
      $("#outPre").text(xhr.responseText || String(xhr.status));
    });
  }

  function selectionHint(){
    if (!isProcessEntity()) {
      $("#lblSelection").text("Select entity «Processes» to preview manifest and export BPMN.");
      $("#btnManifest, #btnExport").prop("disabled", true);
      return;
    }
    if (!selectedId) {
      $("#lblSelection").text("Click a row to select a process.");
      $("#btnManifest, #btnExport").prop("disabled", true);
    } else {
      $("#lblSelection").text("Selected process id: " + selectedId);
      $("#btnManifest, #btnExport").prop("disabled", false);
    }
  }

  function tableColumnsForEntity(ent){
    if (ent === "processes") {
      return [
        { data: "id", defaultContent: "" },
        { data: "name", defaultContent: "" },
        { data: "status", defaultContent: "" },
        { data: "updated_at", defaultContent: "" }
      ];
    }
    if (ent === "screens") {
      return [
        { data: "id", defaultContent: "" },
        { data: "title", defaultContent: "" },
        { data: "type", defaultContent: "" },
        { data: "updated_at", defaultContent: "" }
      ];
    }
    return [
      { data: "id", defaultContent: "" },
      { data: "title", defaultContent: "" },
      { data: "language", defaultContent: "" },
      { data: "updated_at", defaultContent: "" }
    ];
  }

  function applyTableHead(ent){
    if (ent === "processes") {
      $("#thCol2").text("Name");
      $("#thCol3").text("Status");
    } else {
      $("#thCol2").text("Title");
      $("#thCol3").text(ent === "screens" ? "Type" : "Language");
    }
  }

  function initTable(){
    if (tbl) { tbl.destroy(); $("#tblProcesses tbody").empty(); tbl = null; }
    var ent = listEntity();
    applyTableHead(ent);
    tbl = $("#tblProcesses").DataTable({
      processing: true,
      serverSide: true,
      searching: false,
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      ajax: function (dtParams, callback) {
        $.ajax({
          url: $("#inpPstoolsUrl").val() || CONFIG.pstoolsUrl,
          type: "POST",
          contentType: "application/json",
          processData: false,
          data: JSON.stringify(mergePayload({
            action: "list",
            entity: listEntity(),
            draw: dtParams.draw,
            start: dtParams.start,
            length: dtParams.length
          })),
          dataType: "json"
        }).done(function (json) {
          if (!json || json.success === false) {
            showErr(json && json.error ? json.error : "List failed");
            $("#outPre").text(JSON.stringify(json || {}, null, 2));
            callback({ draw: dtParams.draw || 1, recordsTotal: 0, recordsFiltered: 0, data: [] });
            return;
          }
          clearErr();
          var total = parseInt(json.recordsTotal, 10);
          if (!isFinite(total) || total < 0) total = 0;
          var rows = Array.isArray(json.data) ? json.data : [];
          var draw = parseInt(json.draw, 10);
          if (!isFinite(draw) || draw < 1) draw = dtParams.draw || 1;
          callback({
            draw: draw,
            recordsTotal: total,
            recordsFiltered: parseInt(json.recordsFiltered, 10) >= 0 ? parseInt(json.recordsFiltered, 10) : total,
            data: rows
          });
        }).fail(function (xhr) {
          showErr("HTTP error on list (" + xhr.status + ")");
          $("#outPre").text(xhr.responseText || "");
          callback({ draw: dtParams.draw || 1, recordsTotal: 0, recordsFiltered: 0, data: [] });
        });
      },
      columns: tableColumnsForEntity(ent)
    });
    tbl.on("draw", updatePageHint);
    $("#tblProcesses tbody").off("click").on("click", "tr", function(){
      if (!isProcessEntity()) return;
      var data = tbl.row(this).data();
      if (!data) return;
      $(tbl.rows().nodes()).removeClass("selected");
      $(this).addClass("selected");
      selectedId = data.id != null ? parseInt(data.id, 10) : null;
      if (!selectedId || selectedId < 1) selectedId = null;
      selectionHint();
    });
  }

  function base64ToBlob(b64, mime){
    var binary = atob(b64);
    var n = binary.length;
    var u8 = new Uint8Array(n);
    for (var i = 0; i < n; i++) u8[i] = binary.charCodeAt(i);
    return new Blob([u8], { type: mime || "application/octet-stream" });
  }

  $("#btnManifest").on("click", function(){
    if (!selectedId) return;
    clearErr();
    postBatch(mergePayload({
      action: "manifest",
      process_id: selectedId,
      include_subprocesses: $("#chkSubprocess").is(":checked"),
      max_subprocess_depth: parseInt($("#inpSubDepth").val(), 10) || 0
    }), { timeout: 300000 }).done(function(r){
      $("#outPre").text(JSON.stringify(sanitizeResponseForDebug(r), null, 2));
      if (!r || r.success === false) showErr(r && r.error ? r.error : "manifest failed");
    }).fail(function(xhr){ showErr("HTTP error"); $("#outPre").text(xhr.responseText||""); });
  });

  function exportFortePage(){
    clearErr();
    if (!tbl || !isForteAssetEntity()) {
      showErr("Choose Scripts, Screens, or Executors to export a page.");
      return;
    }
    var inf = tbl.page.info();
    var fmt = $("#selForteArchive").val() || "auto";
    var attach = $("#chkAttachRequest").is(":checked");
    var ridStr = String($("#inpRequestId").val() || "").trim();
    var rid = ridStr ? parseInt(ridStr, 10) : parseInt(requestIdFromScreen(), 10);
    var payload = {
      action: "export_chunk",
      entity: listEntity(),
      start: inf.start,
      length: inf.length,
      export_format: fmt,
      export_destination: attach ? "request" : "browser"
    };
    if (attach) {
      if (!rid || rid < 1) {
        showErr("Request ID missing — open this screen inside a running request or enter Request ID.");
        return;
      }
      payload.request_id = rid;
      payload.request_file_data_name = "FORTE_CODE_BACKUP_EXPORT";
    }
    postBatch(mergePayload(payload), { timeout: 600000 }).done(function(r){
      if (!r || r.success === false) {
        showErr(r && r.error ? r.error : "export failed");
        $("#outPre").text(JSON.stringify(r || {}, null, 2));
        return;
      }
      $("#outPre").text(JSON.stringify(sanitizeResponseForDebug(r), null, 2));
      if (r.export_destination === "request") return;
      var name = r.suggested_filename || ("chunk-" + listEntity() + ".tar.gz");
      var b64 = r.archive_base64 || r.zip_base64;
      if (b64) {
        try {
          var blob = base64ToBlob(b64, r.archive_mime || (String(name).indexOf(".zip") >= 0 ? "application/zip" : "application/gzip"));
          var a = document.createElement("a");
          a.href = URL.createObjectURL(blob);
          a.download = name;
          a.click();
          setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
        } catch (e) {
          showErr("Invalid archive payload from server");
        }
      } else if (r.files) {
        var blob = new Blob([JSON.stringify(r, null, 2)], { type: "application/json" });
        if (!/\.json$/i.test(name)) name = String(name).replace(/\.(tar\.gz|zip)$/i, "") + ".json";
        var a2 = document.createElement("a");
        a2.href = URL.createObjectURL(blob);
        a2.download = name;
        a2.click();
        setTimeout(function(){ URL.revokeObjectURL(a2.href); }, 2000);
      } else {
        showErr("Response missing archive_base64 and files");
      }
    }).fail(function(xhr){
      showErr("HTTP error on export");
      $("#outPre").text(xhr.responseText || String(xhr.status));
    });
  }

  function exportForteAll(entity){
    clearErr();
    var attach = $("#chkAttachRequest").is(":checked");
    var ridStr = String($("#inpRequestId").val() || "").trim();
    var rid = ridStr ? parseInt(ridStr, 10) : parseInt(requestIdFromScreen(), 10);
    var payload = {
      action: "export_all",
      entity: entity,
      export_destination: attach ? "request" : "browser"
    };
    if (attach) {
      if (!rid || rid < 1) {
        showErr("Request ID missing — open this screen inside a running request or enter Request ID.");
        return;
      }
      payload.request_id = rid;
      payload.request_file_data_name = "FORTE_CODE_BACKUP_EXPORT";
    }
    postBatch(mergePayload(payload), { timeout: 600000 }).done(function(r){
      if (!r || r.success === false) {
        showErr(r && r.error ? r.error : "export_all failed");
        $("#outPre").text(JSON.stringify(r || {}, null, 2));
        return;
      }
      $("#outPre").text(JSON.stringify(sanitizeResponseForDebug(r), null, 2));
      if (r.export_destination === "request") return;
      var defExt = (r.export_format === "tar_gz") ? ".tar.gz" : ".zip";
      var name = r.suggested_filename || ("export-all-" + entity + defExt);
      var b64 = r.archive_base64 || r.zip_base64;
      if (!b64) { showErr("export_all response missing archive_base64"); return; }
      var mime = r.archive_mime;
      if (!mime) {
        mime = (r.export_format === "tar_gz" || String(name).toLowerCase().indexOf(".tar.gz") >= 0)
          ? "application/gzip" : "application/zip";
      }
      try {
        var blob = base64ToBlob(b64, mime);
        var a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = name;
        a.click();
        setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
      } catch (e) {
        showErr("Invalid archive payload");
      }
    }).fail(function(xhr){
      showErr("HTTP error on export_all");
      $("#outPre").text(xhr.responseText || String(xhr.status));
    });
  }

  $("#btnExport").on("click", function(){
    if (!selectedId) return;
    clearErr();
    var fmt = $("#selProcessArchive").val() || "auto";
    postBatch(mergePayload({
      action: "export_process",
      process_id: selectedId,
      include_subprocesses: $("#chkSubprocess").is(":checked"),
      max_subprocess_depth: parseInt($("#inpSubDepth").val(), 10) || 0,
      export_format: fmt,
      export_destination: "browser"
    }), { timeout: 600000 }).done(function(r){
      $("#outPre").text(JSON.stringify(sanitizeResponseForDebug(r), null, 2));
      if (!r || r.success === false) { showErr(r && r.error ? r.error : "export failed"); return; }
      if (fmt === "json") {
        var blob = new Blob([JSON.stringify(r.bundle || r, null, 2)], { type: "application/json" });
        var name = r.suggested_filename || ("process-" + selectedId + ".json");
        var a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = name;
        a.click();
        setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
        return;
      }
      var b64 = r.archive_base64;
      if (!b64) { showErr("Missing archive_base64"); return; }
      var name = r.suggested_filename || ("process-" + selectedId + ".tar.gz");
      var mime = r.archive_mime || (String(name).indexOf(".zip") >= 0 ? "application/zip" : "application/gzip");
      var blob;
      try { blob = base64ToBlob(b64, mime); }
      catch (e) { showErr("Invalid base64"); return; }
      var a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = name;
      a.click();
      setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
    }).fail(function(xhr){ showErr("HTTP error on export"); $("#outPre").text(xhr.responseText||""); });
  });

  $("#btnLoadCounts").on("click", loadCounts);
  $("#btnExportPage").on("click", exportFortePage);
  $("#btnExportAllScripts").on("click", function(){ exportForteAll("scripts"); });
  $("#btnExportAllScreens").on("click", function(){ exportForteAll("screens"); });

  $("#inpApiBase").on("change blur", updatePstoolsUrl);

  $("#inpMode, input[name=inpTarget], #inpProdHost, #inpProdToken").on("change blur", function(){
    if (tbl) { tbl.page("first"); tbl.ajax.reload(null, false); }
    loadCounts();
  });

  $("#selForteArchive").on("change", function(){
    if ($(this).val() === "json") $("#chkAttachRequest").prop("checked", false);
  });

  $("#selEntity").on("change", function(){
    selectedId = null;
    toggleEntityPanels();
    initTable();
    selectionHint();
    updatePageHint();
  });

  (function initAttachRequestUi(){
    var rid = requestIdFromScreen();
    if (rid) {
      $("#wrapAttachRequest").removeClass("d-none");
      $("#wrapRequestIdOverride").removeClass("d-none");
      $("#inpRequestId").attr("placeholder", "Default: " + rid);
    }
  })();

  updatePstoolsUrl();
  toggleEntityPanels();
  initTable();
  selectionHint();
  updatePageHint();
  if ((apiBase() && bearer()) || $("#inpMode").val() === "sql") {
    loadCounts();
  } else {
    $("#outPre").text("Enter API base and bearer token, then Refresh total (API mode).");
  }
})();
</script>
JS;

$scriptOut = str_replace(
    ['__URL_BATCH__', '__REQUEST_DATA__'],
    [addslashes($urlBatch), $requestDataJson],
    $script
);

$html = $style . '<body>' . $body . '</body>' . $scriptOut;

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html),
];