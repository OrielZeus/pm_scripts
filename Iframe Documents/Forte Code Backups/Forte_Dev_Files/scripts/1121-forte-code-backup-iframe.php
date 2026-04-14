<?php
/**
 * Forte — paged backup UI (DataTables server-side + chunk export). Uses PSTools forte-code-backup-batch.
 * Markup uses <| … </| so ProcessMaker does not strip tags.
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

$apiHost = rtrim((string) ($env['API_HOST'] ?? getenv('API_HOST') ?: ''), '/');
$slug = preg_replace('/[^a-z0-9\-_]/', '', (string) ($data['pstools_slug_backup'] ?? 'forte-code-backup-batch'));
if ($slug === '') {
    $slug = 'forte-code-backup-batch';
}

$urlBatch = $apiHost !== '' ? ($apiHost . '/pstools/script/' . $slug) : '';

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
  .fcb-wrap { max-width: 1200px; margin: 0 auto; padding: 16px 18px 32px; }
  .fcb-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #2563eb 100%);
    color: #fff; border-radius: 14px; padding: 18px 20px; margin-bottom: 16px;
    box-shadow: 0 10px 30px rgba(15,23,42,.2);
  }
  .fcb-hero h1 { font-size: 1.25rem; font-weight: 700; margin: 0 0 6px; }
  .fcb-hero p { margin: 0; opacity: .9; font-size: .85rem; line-height: 1.45; }
  .fcb-card {
    background: #fff; border-radius: 12px; padding: 16px 18px;
    box-shadow: 0 2px 12px rgba(15,23,42,.06); border: 1px solid #e2e8f0; margin-bottom: 14px;
  }
  .fcb-card h2 { font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin: 0 0 10px; font-weight: 600; }
  .kpi { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: #0f172a; }
  .kpi-lbl { font-size: .68rem; text-transform: uppercase; color: #64748b; letter-spacing: .05em; margin-bottom: 4px; }
  .kpi-box { border-radius: 10px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; height: 100%; }
  .btn-entity.active { background: #2563eb !important; color: #fff !important; border-color: #2563eb !important; }
  pre.mini { max-height: 180px; overflow: auto; font-size: 10px; background: #0f172a; color: #e2e8f0; padding: 10px; border-radius: 8px; margin: 0; }
  .fcb-hint { font-size: .78rem; color: #64748b; }
  #tblPaged_wrapper .dataTables_processing { background: rgba(255,255,255,.92); border-radius: 8px; }
</style>
CSS;

$body = <<<'HTML'
<|div class="fcb-wrap">
  <|div class="fcb-hero">
    <|h1><|i class="fas fa-database mr-2"><|/i>Forte code backups<|/h1>
    <|p>Loads <|strong>one page at a time<|/strong> so the script executor does not run out of memory. Totals are quick; the grid uses your chosen <|strong>API<|/strong> or <|strong>SQL<|/strong> (Pro Service Tools). Export the <|strong>current page<|/strong> as a <|strong>.tar.gz<|/strong> or <|strong>.zip<|/strong> archive. <|strong>Export all scripts/screens<|/strong> builds one full archive: <|strong>.zip<|/strong> if php-zip is installed, otherwise a single <|strong>.tar.gz<|/strong> (zlib). You can also attach an archive to <|strong>this request<|/strong> via the API.<|/p>
  <|/div>

  <|div class="fcb-card">
    <|h2><|i class="fas fa-sliders-h mr-1"><|/i>Connection<|/h2>
    <|div class="row">
      <|div class="col-md-4 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1">Environment<|/label>
        <|div>
          <|label class="mr-3 mb-0"><|input type="radio" name="inpTarget" id="inpTargetDev" value="dev" checked /> Dev<|/label>
          <|label class="mb-0"><|input type="radio" name="inpTarget" id="inpTargetProd" value="prod" /> Prod<|/label>
        <|/div>
      <|/div>
      <|div class="col-md-4 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpMode">Mode<|/label>
        <|select id="inpMode" class="form-control form-control-sm">
          <|option value="api">API — REST (recommended)<|/option>
          <|option value="sql">SQL — Pro Service Tools<|/option>
        <|/select>
        <|div class="fcb-hint mt-1">If the table stays empty in SQL mode, try API — row shape varies by package version.<|/div>
      <|/div>
      <|div class="col-md-4 mb-2">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpProdHost">Prod API base (optional)<|/label>
        <|input type="text" id="inpProdHost" class="form-control form-control-sm" placeholder="https://tenant…/api/1.0" autocomplete="off" />
      <|/div>
    <|/div>
    <|div class="row">
      <|div class="col-md-6 mb-0">
        <|label class="small font-weight-bold text-secondary mb-1" for="inpProdToken">Prod bearer token (optional)<|/label>
        <|input type="password" id="inpProdToken" class="form-control form-control-sm" autocomplete="off" />
      <|/div>
      <|div class="col-md-6 mb-0">
        <|label class="small font-weight-bold text-secondary mb-1">PSTools batch URL<|/label>
        <|input type="text" class="form-control form-control-sm bg-light" readonly="readonly" value="__URL_BATCH_ESC__" />
      <|/div>
    <|/div>
  <|/div>

  <|div class="row mb-3">
    <|div class="col-md-4 mb-2">
      <|div class="kpi-box text-center">
        <|div class="kpi-lbl"><|i class="fas fa-code text-primary mr-1"><|/i>Scripts<|/div>
        <|div class="kpi text-primary" id="kpiScripts">—<|/div>
      <|/div>
    <|/div>
    <|div class="col-md-4 mb-2">
      <|div class="kpi-box text-center">
        <|div class="kpi-lbl"><|i class="fas fa-window-maximize text-info mr-1"><|/i>Screens<|/div>
        <|div class="kpi text-info" id="kpiScreens">—<|/div>
      <|/div>
    <|/div>
    <|div class="col-md-4 mb-2">
      <|div class="kpi-box text-center">
        <|div class="kpi-lbl"><|i class="fas fa-cogs text-secondary mr-1"><|/i>Executors<|/div>
        <|div class="kpi text-secondary" id="kpiExec">—<|/div>
      <|/div>
    <|/div>
  <|/div>

  <|div class="fcb-card">
    <|div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
      <|h2 class="mb-0"><|i class="fas fa-list mr-1"><|/i>Browse<|/h2>
      <|div>
        <|button type="button" class="btn btn-sm btn-primary" id="btnLoadCounts"><|i class="fas fa-sync-alt mr-1"><|/i>Refresh totals<|/button>
        <|button type="button" class="btn btn-sm btn-success" id="btnExportPage"><|i class="fas fa-file-archive mr-1"><|/i>Export this page<|/button>
        <|button type="button" class="btn btn-sm btn-outline-success" id="btnExportAllScripts" title="Full archive (.zip if php-zip exists, else .tar.gz)"><|i class="fas fa-download mr-1"><|/i>Export all scripts<|/button>
        <|button type="button" class="btn btn-sm btn-outline-success" id="btnExportAllScreens" title="Full archive (.zip if php-zip exists, else .tar.gz)"><|i class="fas fa-download mr-1"><|/i>Export all screens<|/button>
      <|/div>
    <|/div>
    <|div class="form-row align-items-end mb-2" id="fcbExportOpts">
      <|div class="col-md-4 mb-2 mb-md-0">
        <|label class="small font-weight-bold text-secondary mb-1" for="selArchiveKind">Archive<|/label>
        <|select id="selArchiveKind" class="form-control form-control-sm">
          <|option value="auto">Auto (.zip if available, else .tar.gz)<|/option>
          <|option value="tar_gz">.tar.gz only (no ZipArchive)<|/option>
          <|option value="zip">.zip only (needs php-zip)<|/option>
          <|option value="json">JSON (debug)<|/option>
        <|/select>
      <|/div>
      <|div class="col-md-8">
        <|div class="form-check mt-2 d-none" id="wrapAttachRequest">
          <|input class="form-check-input" type="checkbox" id="chkAttachRequest" />
          <|label class="form-check-label small" for="chkAttachRequest">
            Save archive to <|strong>this request<|/strong> (POST /requests/…/files). Uses variable name <|code>FORTE_CODE_BACKUP_EXPORT<|/code> — add a matching File Upload in the process or read the file from request files.
          <|/label>
        <|/div>
        <|div class="mt-1 d-none" id="wrapRequestIdOverride">
          <|label class="small font-weight-bold text-secondary mb-0" for="inpRequestId">Request ID (override)<|/label>
          <|input type="text" id="inpRequestId" class="form-control form-control-sm" placeholder="From current task if empty" />
        <|/div>
      <|/div>
    <|/div>
    <|p class="fcb-hint mb-2" id="lblPageHint"><|/p>
    <|div class="mb-2">
      <|span class="small font-weight-bold text-secondary mr-2">Entity<|/span>
      <|div class="btn-group btn-group-sm" role="group">
        <|button type="button" class="btn btn-outline-primary btn-entity active" data-entity="scripts" id="btnEntScr"><|i class="fas fa-code mr-1"><|/i>Scripts<|/button>
        <|button type="button" class="btn btn-outline-primary btn-entity" data-entity="screens" id="btnEntScn"><|i class="fas fa-window-maximize mr-1"><|/i>Screens<|/button>
        <|button type="button" class="btn btn-outline-primary btn-entity" data-entity="executors" id="btnEntExe"><|i class="fas fa-cogs mr-1"><|/i>Executors<|/button>
      <|/div>
    <|/div>
    <|div class="table-responsive border rounded" style="overflow-x:auto;">
      <|table id="tblPaged" class="table table-sm table-hover table-striped w-100 mb-0">
        <|thead class="thead-light"><|tr>
          <|th>ID<|/th><|th>Title<|/th><|th>Lang / Type<|/th><|th>Updated<|/th>
        <|/tr><|/thead><|tbody><|/tbody>
      <|/table>
    <|/div>
  <|/div>

  <|div id="alertBox"><|/div>
  <|div class="fcb-card mt-2">
    <|h2>Last response (errors)<|/h2>
    <|pre class="mini" id="outPre"><|/pre>
  <|/div>
<|/div>
HTML;

$body = str_replace('__URL_BATCH_ESC__', htmlspecialchars($urlBatch, ENT_QUOTES, 'UTF-8'), $body);

$script = <<<'JS'
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script>
(function(){
  var CONFIG = { url: "__URL_BATCH__", requestData: __REQUEST_DATA__ };
  var curEntity = "scripts";
  var tbl = null;

  function targetVal(){
    var v = $("input[name=inpTarget]:checked").val();
    return v === "prod" ? "prod" : "dev";
  }

  function passthroughFromScreen(){
    var rd = CONFIG.requestData || {};
    var o = {};
    if (rd.pstools_slug_backup) o.pstools_slug_backup = rd.pstools_slug_backup;
    if (rd.api_sql_path) o.api_sql_path = rd.api_sql_path;
    if (rd.api_host_prod) o.api_host_prod = rd.api_host_prod;
    if (rd.api_token_prod) o.api_token_prod = rd.api_token_prod;
    if (rd.request_file_data_name) o.request_file_data_name = rd.request_file_data_name;
    return o;
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
    o.target = targetVal();
    o.mode = $("#inpMode").val() || "api";
    var ph = $("#inpProdHost").val();
    if (ph && String(ph).trim()) o.api_host_prod = String(ph).trim().replace(/\/+$/,"");
    var pt = $("#inpProdToken").val();
    if (pt && String(pt).trim()) o.api_token_prod = String(pt).trim();
    return o;
  }

  function postJson(body, ajaxOpts){
    if (!CONFIG.url) return $.Deferred().reject("no url");
    return $.ajax($.extend({
      url: CONFIG.url,
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

  function loadCounts(){
    clearErr();
    postJson(mergePayload({ action: "counts" })).done(function(r){
      if (!r || r.success === false) { showErr(r && r.error ? r.error : "counts failed"); return; }
      var c = r.counts || {};
      $("#kpiScripts").text(c.scripts != null ? c.scripts : "—");
      $("#kpiScreens").text(c.screens != null ? c.screens : "—");
      $("#kpiExec").text(c.executors != null ? c.executors : "—");
    }).fail(function(xhr){
      showErr("HTTP error on counts");
      $("#outPre").text(xhr.responseText || String(xhr.status));
    });
  }

  function updatePageHint(){
    if (!tbl) return;
    var inf = tbl.page.info();
    var pages = inf.pages;
    if (!isFinite(pages) || pages < 1) pages = 1;
    $("#lblPageHint").text("Page " + (inf.page + 1) + " of " + pages + " · " + inf.length + " rows per page · " + inf.recordsTotal + " total · " + curEntity);
  }

  function initTable(){
    if (tbl) { tbl.destroy(); $("#tblPaged tbody").empty(); tbl = null; }
    tbl = $("#tblPaged").DataTable({
      processing: true,
      serverSide: true,
      searching: false,
      pageLength: 25,
      lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
      ajax: function (dtParams, callback, settings) {
        $.ajax({
          url: CONFIG.url,
          type: "POST",
          contentType: "application/json",
          processData: false,
          data: JSON.stringify(mergePayload({
            action: "list",
            entity: curEntity,
            draw: dtParams.draw,
            start: dtParams.start,
            length: dtParams.length
          })),
          dataType: "json"
        }).done(function (json) {
          if (!json || json.success === false) {
            showErr(json && json.error ? json.error : "List request failed");
            $("#outPre").text(JSON.stringify(json || {}, null, 2));
            callback({
              draw: dtParams.draw || 1,
              recordsTotal: 0,
              recordsFiltered: 0,
              data: []
            });
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
          callback({
            draw: dtParams.draw || 1,
            recordsTotal: 0,
            recordsFiltered: 0,
            data: []
          });
        });
      },
      columns: [
        { data: "id", defaultContent: "" },
        { data: "title", defaultContent: "" },
        {
          data: null,
          defaultContent: "",
          render: function (cell, type, row) {
            if (!row || typeof row !== "object") return "";
            var a = row.language || row.Language || "";
            var b = row.type || row.Type || "";
            if (a && b) return a + " / " + b;
            return a || b || "";
          }
        },
        { data: "updated_at", defaultContent: "" }
      ]
    });
    tbl.on("draw", updatePageHint);
  }

  function base64ToBlob(b64, mime){
    var binary = atob(b64);
    var n = binary.length;
    var u8 = new Uint8Array(n);
    for (var i = 0; i < n; i++) u8[i] = binary.charCodeAt(i);
    return new Blob([u8], { type: mime || "application/octet-stream" });
  }

  function exportCurrentPage(){
    clearErr();
    if (!tbl) { showErr("Table not ready"); return; }
    var inf = tbl.page.info();
    var fmt = $("#selArchiveKind").val() || "auto";
    var attach = $("#chkAttachRequest").is(":checked");
    var ridStr = String($("#inpRequestId").val() || "").trim();
    var rid = ridStr ? parseInt(ridStr, 10) : parseInt(requestIdFromScreen(), 10);
    var payload = {
      action: "export_chunk",
      entity: curEntity,
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
    }
    postJson(mergePayload(payload), { timeout: 600000 }).done(function(r){
      if (!r || r.success === false) { showErr(r && r.error ? r.error : "export failed"); $("#outPre").text(JSON.stringify(r||{}, null, 2)); return; }
      $("#outPre").text(JSON.stringify(sanitizeResponseForDebug(r), null, 2));
      if (r.export_destination === "request") {
        return;
      }
      var name = r.suggested_filename || ("chunk-" + curEntity + ".tar.gz");
      var blob;
      var b64 = r.archive_base64 || r.zip_base64;
      if (b64) {
        try {
          blob = base64ToBlob(b64, r.archive_mime || (String(name).indexOf(".zip") >= 0 ? "application/zip" : "application/gzip"));
        } catch (e) {
          showErr("Invalid archive payload from server");
          return;
        }
      } else if (r.files) {
        blob = new Blob([JSON.stringify(r, null, 2)], { type: "application/json" });
        if (!/\.json$/i.test(name)) name = String(name).replace(/\.(tar\.gz|zip)$/i, "") + ".json";
      } else {
        showErr("Export response missing archive_base64 and files");
        return;
      }
      var a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = name;
      a.click();
      setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
    }).fail(function(xhr){
      showErr("HTTP error on export");
      $("#outPre").text(xhr.responseText || String(xhr.status));
    });
  }

  $(".btn-entity").on("click", function () {
    var ent = $(this).data("entity");
    if (!ent) return;
    curEntity = ent;
    $(".btn-entity").removeClass("active");
    $(this).addClass("active");
    if (tbl) {
      tbl.page("first");
      tbl.ajax.reload(null, false);
    }
  });

  function exportAll(entity){
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
    }
    postJson(mergePayload(payload), { timeout: 600000 }).done(function(r){
      if (!r || r.success === false) { showErr(r && r.error ? r.error : "export_all failed"); $("#outPre").text(JSON.stringify(r||{}, null, 2)); return; }
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
      var blob;
      try {
        blob = base64ToBlob(b64, mime);
      } catch (e) {
        showErr("Invalid archive payload from server");
        return;
      }
      var a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = name;
      a.click();
      setTimeout(function(){ URL.revokeObjectURL(a.href); }, 2000);
    }).fail(function(xhr){
      showErr("HTTP error on export_all");
      $("#outPre").text(xhr.responseText || String(xhr.status));
    });
  }

  $("#btnLoadCounts").on("click", loadCounts);
  $("#btnExportPage").on("click", exportCurrentPage);
  $("#btnExportAllScripts").on("click", function(){ exportAll("scripts"); });
  $("#btnExportAllScreens").on("click", function(){ exportAll("screens"); });
  $("#selArchiveKind").on("change", function(){
    if ($(this).val() === "json") $("#chkAttachRequest").prop("checked", false);
  });
  (function initExportUi(){
    var rid = requestIdFromScreen();
    if (rid) {
      $("#wrapAttachRequest").removeClass("d-none");
      $("#wrapRequestIdOverride").removeClass("d-none");
      $("#inpRequestId").attr("placeholder", "Default: " + rid);
    }
  })();
  $("input[name=inpTarget], #inpMode").on("change", function(){
    if (tbl) { tbl.page("first"); tbl.ajax.reload(null, false); }
    loadCounts();
  });

  if (CONFIG.url) {
    initTable();
    loadCounts();
  } else {
    showErr("API_HOST missing — cannot call PSTools.");
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