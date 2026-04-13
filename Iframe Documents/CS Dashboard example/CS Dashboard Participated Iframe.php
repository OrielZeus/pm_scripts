<?php

/**********************************
 * CS Dashboard - Participated (IFRAME Frontend)
 * - Lazy XLSX load
 * - Priority column visible
 * By: Andres Garcia 
 **********************************/

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($data) || !is_array($data)) $data = [];

$API_HOST      = $data['_env']['API_HOST'] ?? getenv('API_HOST');
$currentUserId = (int)($data['currentUserId'] ?? ($data['_request']['user_id'] ?? 0));
$backendUrl    = rtrim($API_HOST, '/') . '/pstools/script/cs-dashboard-participated-sql';

$style = <<<'CSS'
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs4/1.13.7/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
  body{background:#f5f7fb;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:14px;margin:0;padding:0;}
  .kpi-row{display:flex;gap:16px;margin:10px 0 20px;flex-wrap:wrap;}
  .kpi-card{flex:1;min-width:160px;border-radius:12px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;cursor:pointer;transition:all .25s cubic-bezier(0.4,0,0.2,1);border:2px solid transparent;box-shadow:0 1px 3px rgba(0,0,0,0.05);background:#fff;user-select:none;}
  .kpi-card:hover{transform:translateY(-4px);box-shadow:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05);}
  .kpi-card.active{transform:scale(1.02);box-shadow:0 10px 20px rgba(0,0,0,0.08);}
  .kpi-left .kpi-label{font-size:14px;font-weight:500;margin-bottom:6px;color:#4b5563;}
  .kpi-left .kpi-value{font-size:32px;font-weight:700;line-height:1;color:#111827;}
  .kpi-right i{font-size:28px;opacity:.8;}
  .card-pending{background:#dcfce7;border-color:#bbf7d0;}.card-pending.active{border-color:#22c55e;background:#bbf7d0;}.card-pending .kpi-right i{color:#16a34a;}
  .card-completed{background:#eff6ff;border-color:#bfdbfe;}.card-completed.active{border-color:#3b82f6;background:#dbeafe;}.card-completed .kpi-right i{color:#2563eb;}
  .card-cancelled{background:#fff;border-color:#e5e7eb;}.card-cancelled.active{border-color:#6b7280;background:#f3f4f6;}.card-cancelled .kpi-right i{color:#4b5563;}
  .card-all{background:#fdfaef;border-color:#fceea6;}.card-all.active{border-color:#eab308;background:#fef08a;}.card-all .kpi-right i{color:#d97706;}
  .custom-badge{display:inline-flex;align-items:center;justify-content:center;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;letter-spacing:.3px;white-space:nowrap;text-transform:capitalize;box-shadow:0 1px 2px rgba(0,0,0,0.05);}
  .badge-pending{background:#f59e0b;color:#fff;}.badge-completed{background:#49a24d;color:#fff;}.badge-cancelled{background:#cb4646;color:#fff;}.badge-returned{background:#facc15;color:#212529;}.badge-urgent{background:#dc2626;color:#fff;}.badge-status-df{background:#6b7280;color:#fff;}
  .badge-low{background:#e2e8f0;color:#475569;}.badge-normal{background:#3b82f6;color:#fff;}.badge-high{background:#f97316;color:#fff;}.badge-prio-df{background:#f3f4f6;color:#4b5563;}
  .card-filters{background:#fff;border:1px solid #e5e9f0;border-radius:6px;padding:12px 14px;box-shadow:0 1px 2px rgba(0,0,0,0.05);}
  label{font-size:.86rem;margin-bottom:.25rem;color:#2b2b2b;font-weight:600;display:block;}
  input.form-control,select.form-control{font-size:.9rem;height:36px;padding:.25rem .5rem;}
  .toolbar{margin-top:14px;display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-bottom:12px;}
  .toolbar .right{display:flex;gap:8px;align-items:center;}
  table.dataTable thead th{background:#467BBA !important;color:#fff !important;border:none !important;font-weight:700;text-transform:uppercase;font-size:.82rem;}
  table.dataTable tbody td{vertical-align:middle;}
  #csLoader{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(248,250,252,.72);backdrop-filter:blur(1px);-webkit-backdrop-filter:blur(1px);z-index:9999;}
  .cs-loader-box{text-align:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;}
  .cs-spinner{width:42px;height:42px;margin:0 auto 14px auto;border:4px solid #dbeafe;border-top:4px solid #467BBA;border-radius:50%;animation:pmSpin 0.9s linear infinite;}
  .cs-loader-title{font-size:18px;font-weight:700;color:#1e293b;margin-bottom:6px;}
  .cs-loader-text{font-size:14px;color:#64748b;}
  @keyframes pmSpin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}
  #csDebug{display:none;background:#111827;color:#fff;padding:10px 12px;border-radius:8px;font-size:12px;margin-top:10px;white-space:pre-wrap;}
  .btn-row-left{display:flex;gap:10px;align-items:end;}
  .btn-row-left .btn{height:36px;display:inline-flex;align-items:center;gap:.5rem;}
  a.btn-view{cursor:pointer !important;pointer-events:auto !important;}
  .view-col-btn .btn{white-space:nowrap;}
  .preview-wrap{position:relative;width:100%;height:100%;background:#f8fafc;}
  .preview-overlay{position:absolute;inset:0;z-index:10;background:transparent;cursor:not-allowed;touch-action:none;pointer-events:auto;}
  .preview-badge{position:absolute;top:10px;right:10px;z-index:20;background:#111827;color:#fff;font-size:12px;font-weight:700;padding:6px 10px;border-radius:999px;opacity:.92;}
  .preview-badge i{font-size:12px;}
  #summaryWrap{height:100%;display:none;flex-direction:column;background:#f8fafc;}
  .sum-header-strip{background:#fff;border-bottom:1px solid #e2e8f0;padding:15px 25px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;}
  .sum-search-box{width:280px;position:relative;}
  .sum-search-box i{position:absolute;left:10px;top:11px;color:#94a3b8;}
  .sum-search-box input{padding-left:32px;border-radius:8px;border:1px solid #cbd5e1;}
  .sum-scroll{flex-grow:1;overflow-y:auto;padding:20px 25px;overscroll-behavior:contain;}
  .sum-section-title{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
  .sum-section-title::after{content:"";height:1px;background:#e2e8f0;flex-grow:1;}
  .sum-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:30px;}
  .sum-item{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;transition:all 0.2s;}
  .sum-item:hover{border-color:#467BBA;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);}
  .sum-item.full-width{grid-column:span 2;}
  .sum-label{font-size:12px;font-weight:600;color:#64748b;margin-bottom:4px;}
  .sum-value{font-size:14px;font-weight:700;color:#1e293b;white-space:pre-wrap;word-break:break-word;}
  .sum-val-empty{font-style:italic;color:#cbd5e1;font-weight:400;}
  .sum-item-highlight{background:#f0f9ff;border-color:#bae6fd;}
  pre.sum-json{background:#f1f5f9;color:#334155;padding:15px;border:1px solid #cbd5e1;border-radius:8px;font-size:12px;overflow-x:auto;}
</style>
CSS;

$body = <<<'HTML'
<|div class="kpi-row">
  <|div class="kpi-card card-pending active" data-status="Pending">
    <|div class="kpi-left"><|div class="kpi-label">Pending<|/div><|div class="kpi-value" id="kpi-count-Pending">-<|/div><|/div>
    <|div class="kpi-right"><|i class="fas fa-tasks"><|/i><|/div>
  <|/div>
  <|div class="kpi-card card-completed" data-status="Completed">
    <|div class="kpi-left"><|div class="kpi-label">Completed<|/div><|div class="kpi-value" id="kpi-count-Completed">-<|/div><|/div>
    <|div class="kpi-right"><|i class="fas fa-check-circle"><|/i><|/div>
  <|/div>
  <|div class="kpi-card card-cancelled" data-status="Cancelled">
    <|div class="kpi-left"><|div class="kpi-label">Cancelled<|/div><|div class="kpi-value" id="kpi-count-Cancelled">-<|/div><|/div>
    <|div class="kpi-right"><|i class="fas fa-ban"><|/i><|/div>
  <|/div>
  <|div class="kpi-card card-all" data-status="">
    <|div class="kpi-left"><|div class="kpi-label">All<|/div><|div class="kpi-value" id="kpi-count-All">-<|/div><|/div>
    <|div class="kpi-right"><|i class="fas fa-clipboard-list"><|/i><|/div>
  <|/div>
<|/div>

<|div class="card-filters">
  <|div class="row">
    <|div class="col-md-2 mb-2"><|label for="case_number">Case Number<|/label><|input id="case_number" type="text" class="form-control" placeholder="e.g. 2111" /><|/div>
    <|div class="col-md-3 mb-2"><|label for="case_title">Case Title<|/label><|input id="case_title" type="text" class="form-control" placeholder="contains..." /><|/div>
    <|div class="col-md-3 mb-2"><|label for="task_name">Task<|/label><|input id="task_name" type="text" class="form-control" placeholder="task name..." /><|/div>
    <|div class="col-md-2 mb-2"><|label for="process">Process<|/label><|select id="process" class="form-control"><|option value="">Loading...<|/option><|/select><|/div>
    <|div class="col-md-2 mb-2">
      <|label for="case_status">Status<|/label>
      <|select id="case_status" class="form-control">
        <|option value="">All<|/option>
        <|option value="Pending" selected>Pending<|/option>
        <|option value="Completed">Completed<|/option>
        <|option value="Cancelled">Cancelled<|/option>
      <|/select>
    <|/div>
  <|/div>
  <|div class="row mt-2">
    <|div class="col-md-3 mb-2"><|label for="date_from">From (Created)<|/label><|input id="date_from" type="date" class="form-control" /><|/div>
    <|div class="col-md-3 mb-2"><|label for="date_to">To (Created)<|/label><|input id="date_to" type="date" class="form-control" /><|/div>
    <|div class="col-md-6 d-flex align-items-end justify-content-end">
      <|div class="btn-row-left">
        <|button type="button" id="btnSearch" class="btn btn-primary"><|i class="fas fa-search"><|/i> Search<|/button>
        <|button type="button" id="btnClear" class="btn btn-outline-secondary"><|i class="fas fa-eraser"><|/i> Clear<|/button>
      <|/div>
    <|/div>
  <|/div>
<|/div>

<|div class="toolbar">
  <|div class="right">
    <|button type="button" id="btnExport" class="btn btn-outline-success btn-sm"><|i class="fas fa-file-excel"><|/i> Export<|/button>
    <|button type="button" id="btnRefresh" class="btn btn-outline-primary btn-sm"><|i class="fas fa-sync-alt"><|/i> Refresh<|/button>
  <|/div>
<|/div>

<|div id="csDebug"><|/div>

<|div class="mt-2">
  <|table id="casesTable" class="table table-sm table-hover table-bordered w-100">
    <|thead>
      <|tr>
        <|th>CASE #</th><|th>TITLE</th><|th>VIEW</th><|th>PROCESS</th>
        <|th>TASK</th><|th>ASSIGNED TO</th><|th>STATUS</th><|th>CREATED</th><|th>PRIORITY</th>
      <|/tr>
    <|/thead>
    <|tbody><|/tbody>
  <|/table>
<|/div>

<|div id="csLoader">
  <|div class="cs-loader-box">
    <|div class="cs-spinner"><|/div>
    <|div class="cs-loader-title">Loading dashboard...</div>
    <|div class="cs-loader-text">Please wait while the My Participations dashboard is being prepared.</div>
  <|/div>
<|/div>

<|div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <|div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
    <|div class="modal-content" style="height:90vh;display:flex;flex-direction:column;">
      <|div class="modal-header" style="flex-shrink:0;">
        <|h5 class="modal-title" id="previewTitle">Case Details</h5>
        <|button type="button" class="close" data-dismiss="modal"><|span>&times;</span>
      <|/div>
      <|div class="modal-body p-0" style="flex-grow:1;position:relative;overflow:hidden;min-height:0;">
        <|div class="preview-wrap" style="position:absolute;inset:0;background:#f8fafc;">
          <|div id="readOnlyBadge" class="preview-badge" style="display:none;"><|i class="fas fa-lock"></i> Read-only view</div>
          <|div id="previewOverlay" class="preview-overlay" style="display:none;"></div>
          <|iframe id="casePreviewFrame" src="" style="width:100%;height:100%;border:0;display:none;"></iframe>
          <|div id="summaryWrap" style="display:none;height:100%;flex-direction:column;">
            <|div class="sum-header-strip">
              <|div class="d-flex align-items-center gap-3">
                <|div class="sum-search-box">
                  <|i class="fas fa-search"></i>
                  <|input type="text" id="sumSearch" class="form-control form-control-sm" placeholder="Search field..." />
                </div>
              </div>
              <|div class="d-flex align-items-center gap-3">
                <|label class="sum-pretty mb-0 mr-3" style="cursor:pointer;"><|input type="checkbox" id="sumPretty" /> JSON mode
                <|button class="btn btn-sm btn-outline-primary" onclick="window.printSummary()"><|i class="fas fa-print"></i> Print
              </div>
            </div>
            <|div class="sum-scroll" id="sumScroll"><|div id="sumContent"></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
HTML;

$script = <<<'JS'
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.7/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs4/1.13.7/dataTables.bootstrap4.min.js"></script>
<script>
(function(){
  var BACKEND="__BACKEND__", CURRENT_USER=__CURRENT_USER__, TAB="participated";
  var loader=document.getElementById("csLoader"), dbg=document.getElementById("csDebug"), dtTable=null;
  var loaderTimeout=null, xlsxReady=false, xlsxLoading=false, xlsxQueue=[], lastSummaryPayload=null;
  var bootstrapCache=null;

  function showLoader(){ $(loader).css("display","flex"); clearTimeout(loaderTimeout); loaderTimeout=setTimeout(hideLoader,20000); }
  function hideLoader(){ $(loader).hide(); clearTimeout(loaderTimeout); }
  function showDebug(t){ if(dbg){ dbg.style.display="block"; dbg.textContent=t; } }
  function clearDebug(){ if(dbg){ dbg.style.display="none"; dbg.textContent=""; } }
  function unwrap(r){ return (r&&r.output)?r.output:r; }
  function escHtml(s){ return String(s||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"); }

  // Shared payload for bootstrap, table, KPI, and export calls.
  function buildBasePayload(){
    var p=$("#process").val()||"";
    if(p==="DIVIDER") p="";
    return {
      tab:TAB,currentUserId:CURRENT_USER,
      case_number:$("#case_number").val()||"",
      case_title:$("#case_title").val()||"",
      task_name:$("#task_name").val()||"",
      process:p,process_id:p,
      case_status:$("#case_status").val()||"",
      date_from:$("#date_from").val()||"",
      date_to:$("#date_to").val()||""
    };
  }

  function fmtDate(m){
    if(!m) return "";
    var p=String(m).split(" ");
    if(p.length<2) return m;
    var d=p[0].split("-"), t=p[1].split(":");
    if(d.length!==3) return m;
    return d[2]+"/"+d[1]+"/"+d[0]+" "+(t[0]||"00")+":"+(t[1]||"00");
  }

  function applyKpis(k){
    if(!k) return;
    $("#kpi-count-All").text(k.all!=null?k.all:0);
    $("#kpi-count-Pending").text(k.pending!=null?k.pending:0);
    $("#kpi-count-Completed").text(k.completed!=null?k.completed:0);
    $("#kpi-count-Cancelled").text(k.cancelled!=null?k.cancelled:0);
  }

  function setKpiPlaceholders(){
    ["Pending","Completed","Cancelled","All"].forEach(function(id){
      var el=document.getElementById("kpi-count-"+id);
      if(el) el.textContent="-";
    });
  }

  function syncKpiActiveCard(){
    var s=$("#case_status").val()||"";
    $(".kpi-card").removeClass("active");
    $('.kpi-card[data-status="'+s+'"]').addClass("active");
  }

  function fillProcessDropdown(list){
    var sel=document.getElementById("process");
    if(!sel) return;
    sel.disabled=true; while(sel.options.length) sel.remove(0);
    var optAll=document.createElement("option"); optAll.value=""; optAll.textContent="All Processes"; sel.appendChild(optAll);
    var optDiv=document.createElement("option"); optDiv.value="DIVIDER"; optDiv.textContent="----------"; optDiv.disabled=true; sel.appendChild(optDiv);
    (list||[]).forEach(function(p){
      if(!p) return;
      var id=String(p.process_id||""), name=String(p.process_name||"");
      if(!id||!name) return;
      var opt=document.createElement("option");
      opt.value=id; opt.textContent=name; sel.appendChild(opt);
    });
    sel.value=""; sel.disabled=false;
  }

  function setProcessLoadingState(){
    var sel=document.getElementById("process");
    if(!sel) return;
    sel.disabled=true; while(sel.options.length) sel.remove(0);
    var opt=document.createElement("option"); opt.value=""; opt.textContent="Loading..."; sel.appendChild(opt);
  }

  function setProcessFallbackState(){
    var sel=document.getElementById("process");
    if(!sel) return;
    sel.disabled=false; while(sel.options.length) sel.remove(0);
    var opt=document.createElement("option"); opt.value=""; opt.textContent="All Processes"; sel.appendChild(opt);
  }

  // Initial bootstrap loads KPIs and first table page in one request.
  function loadBootstrap(){
    var payload = Object.assign({}, buildBasePayload(), {
      mode: "bootstrap",
      draw: 1,
      start: 0,
      length: 25,
      order: [{ column: 7, dir: "desc" }],
      columns: [
        { data:"case_number" },
        { data:"case_title" },
        { data:null },
        { data:"process_name" },
        { data:"current_task" },
        { data:"assigned_to" },
        { data:"status_text" },
        { data:"created_at" },
        { data:"priority_text" }
      ]
    });
    return $.ajax({
      url:BACKEND, type:"POST", contentType:"application/json",
      data:JSON.stringify(payload), timeout:25000
    }).done(function(resp){
      resp = unwrap(resp) || {};
      fillProcessDropdown(Array.isArray(resp.processes) ? resp.processes : []);
      applyKpis(resp.kpis || {});
      bootstrapCache = resp.table || null;
    }).fail(function(){
      setProcessFallbackState();
      bootstrapCache = { draw:1, recordsTotal:0, recordsFiltered:0, data:[] };
    });
  }

  // Refreshes KPI cards using the current filters.
  function loadKpiCounts(){
    var payload=Object.assign({},buildBasePayload(),{mode:"kpi_counts"});
    payload.case_status="";
    return $.ajax({
      url:BACKEND,type:"POST",contentType:"application/json",
      data:JSON.stringify(payload),timeout:20000,
      success:function(resp){ resp=unwrap(resp); applyKpis(resp&&resp.kpis?resp.kpis:resp); }
    });
  }

  function ensureXlsx(cb){
    if(xlsxReady&&window.XLSX){ cb(); return; }
    xlsxQueue.push(cb); if(xlsxLoading) return;
    xlsxLoading=true;
    var s=document.createElement("script");
    s.src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js";
    s.onload=function(){ xlsxReady=true; xlsxLoading=false; while(xlsxQueue.length){ try{xlsxQueue.shift()();}catch(e){} } };
    s.onerror=function(){ xlsxLoading=false; while(xlsxQueue.length) xlsxQueue.shift(); showDebug("Error loading XLSX."); };
    document.head.appendChild(s);
  }

  function initOverlayScrollForwarding(){
    var overlay=document.getElementById("previewOverlay");
    var frame=document.getElementById("casePreviewFrame");
    if(!overlay||!frame) return;
    var px=0,py=0,rafId=null;
    function getWin(){ try{ return frame.contentWindow||null; }catch(e){ return null; } }
    function flush(){ rafId=null; var w=getWin(); if(!w){px=0;py=0;return;} var dx=px,dy=py; px=0;py=0; try{w.scrollBy({left:dx,top:dy,behavior:"auto"});}catch(e){} }
    function enq(dx,dy){ px+=dx;py+=dy; if(!rafId) rafId=requestAnimationFrame(flush); }
    function norm(e){ var dx=e.deltaX||0,dy=e.deltaY||0; if(e.deltaMode===1){dx*=16;dy*=16;}else if(e.deltaMode===2){dx*=window.innerWidth;dy*=window.innerHeight;} return{dx:dx,dy:dy}; }
    overlay.addEventListener("wheel",function(e){e.preventDefault();e.stopPropagation();var d=norm(e);enq(d.dx,d.dy);},{passive:false});
  }

  function renderSummary(payload){
    if($("#sumPretty").is(":checked")){
      return '<pre class="sum-json">'+escHtml(JSON.stringify(payload,null,2))+'</pre>';
    }
    var summary=(payload&&Array.isArray(payload.summary))?payload.summary:[];
    var labels=(payload&&payload.labelMap)?payload.labelMap:{};
    if(!summary.length) return '<div class="p-3 text-muted">No summary data available.</div>';
    var html='<div class="sum-section-title">Case Summary</div><div class="sum-grid">';
    var coreKeys=['case_number','status_text','assigned_to','created_at','process_name','case_title'];
    summary.forEach(function(item){
      var key=item.key||"";
      var label=labels[key]||key.replace(/_/g," ").toUpperCase();
      var val=item.value;
      if(val===true) val='<span class="text-success"><i class="fas fa-check-circle"></i> Yes</span>';
      else if(val===false) val='<span class="text-danger"><i class="fas fa-times-circle"></i> No</span>';
      else if(val===null||val===""||typeof val==="undefined") val='<span class="sum-val-empty">N/A</span>';
      else {
        var isJsonString=false;
        if(typeof val==="string" && (val.trim().startsWith("[") || val.trim().startsWith("{"))){
          try{ var parsed=JSON.parse(val); if(typeof parsed==="object" && parsed!==null){ val='<pre class="sum-json">'+escHtml(JSON.stringify(parsed,null,2))+'</pre>'; isJsonString=true; } }catch(e){}
        }
        if(!isJsonString){
          if(typeof val==="object") val=escHtml(JSON.stringify(val));
          else val=escHtml(String(val));
        }
      }
      var hi=coreKeys.includes(key)?"sum-item-highlight":"";
      var lg=(String(val).length>60 || String(val).indexOf('<pre')!==-1)?"full-width":"";
      html+='<div class="sum-item '+hi+' '+lg+'" data-label="'+escHtml(label.toLowerCase())+'"><div class="sum-label">'+escHtml(label)+'</div><div class="sum-value">'+val+"</div></div>";
    });
    return html+'</div>';
  }

  window.printSummary=function(){
    var c=document.getElementById("sumContent").innerHTML;
    var w=window.open("","","height=700,width=900");
    w.document.write("<html><head><title>Summary</title><style>body{font-family:sans-serif;padding:30px;color:#333}.sum-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.sum-item{border:1px solid #eaeaea;padding:12px;border-radius:4px}.full-width{grid-column:span 2}.sum-label{font-weight:bold;font-size:11px;color:#888;margin-bottom:4px}.sum-value{font-size:14px;white-space:pre-wrap;word-break:break-word}.sum-item-highlight{background:#f9f9f9;border-color:#ddd;} pre.sum-json{background:#f1f5f9;color:#334155;padding:15px;border:1px solid #cbd5e1;border-radius:8px;font-size:12px;overflow-x:auto;}</style></head><body><h2>"+$("#previewTitle").text()+"</h2>"+c+"</body></html>");
    w.document.close(); w.print();
  };

  function exportRowsToExcel(rows){
    try{
      var data=(rows||[]).map(function(r){
        return {
          "Case Number": r.case_number||"",
          "Case Title": r.case_title||"",
          "Process": r.process_name||"",
          "Task": r.current_task||"",
          "Assigned To": r.assigned_to||"",
          "Status": r.status_text||"",
          "Created": r.created_at||"",
          "Priority": r.priority_text||""
        };
      });
      var ws=XLSX.utils.json_to_sheet(data);
      var wb=XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb,ws,"Participated");
      XLSX.writeFile(wb,"Participated.xlsx");
    }catch(e){ showDebug("Export error:\n"+String(e)); }
  }

  function initTable(){
    dtTable=$("#casesTable").DataTable({
      processing:true, serverSide:true, searching:false, autoWidth:false, deferRender:true,
      pageLength:25, lengthMenu:[[25,50,100],[25,50,100]], order:[[7,"desc"]],
      dom:"<'row'<'col-sm-6'l><'col-sm-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",
      ajax:function(d, callback){
        if(bootstrapCache){
          var first=bootstrapCache;
          bootstrapCache=null;
          callback(first);
          return;
        }
        $.ajax({
          url:BACKEND, type:"POST", contentType:"application/json",
          data:JSON.stringify(Object.assign({},buildBasePayload(),{
            draw:d.draw,start:d.start,length:d.length,order:d.order,columns:d.columns
          })),
          timeout:25000,
          success:function(resp){ resp=unwrap(resp); callback(resp&&resp.data ? resp : {draw:d.draw,recordsTotal:0,recordsFiltered:0,data:[]}); },
          error:function(xhr,st){ hideLoader(); if(st!=="abort" && xhr&&xhr.status!==0) showDebug("AJAX ERROR\nstatus="+(xhr?xhr.status:"n/a")); callback({draw:d.draw,recordsTotal:0,recordsFiltered:0,data:[]}); }
        });
      },
      columns:[
        {data:"case_number",render:function(v,t,row){ if(t!=="display") return v; var u=row.open_url||""; return u?'<a href="'+u+'" target="_blank" style="font-weight:700;">'+String(v||"")+'</a>':String(v||""); }},
        {data:"case_title",render:function(v,t,row){ if(t!=="display") return v; var s=String(v||""); return escHtml(s.trim()||"Case #"+(row.case_number||row.request_id||"?")); }},
        {data:null,orderable:false,searchable:false,className:"text-center view-col-btn",render:function(_,t,row){
          if(t!=="display") return "";
          var pre=row.preview_url||"", rid=row.request_id||"";
          if(pre) return '<a href="#" class="btn btn-sm btn-outline-primary btn-view" data-kind="preview" data-url="'+String(pre).replace(/"/g,"&quot;")+'" data-title="Preview (read-only)"><i class="fas fa-eye"></i> View</a>';
          if(rid) return '<a href="#" class="btn btn-sm btn-outline-secondary btn-view" data-kind="summary" data-request-id="'+String(rid).replace(/"/g,"&quot;")+'" data-title="'+String(row.case_title||("Request #"+rid)).replace(/"/g,"&quot;")+'"><i class="fas fa-list"></i> Summary</a>';
          return '<span class="text-muted">-</span>';
        }},
        {data:"process_name",defaultContent:""},
        {data:"current_task",defaultContent:""},
        {data:"assigned_to",defaultContent:""},
        {data:"status_text",defaultContent:"",render:function(v,t){
          if(t!=="display") return v; if(!v) return "";
          var s=String(v),l=s.toLowerCase(),c="badge-status-df";
          if(l.includes("pending")||l.includes("progress")){c="badge-pending";s="Pending";}
          else if(l.includes("completed")||l.includes("concluded")){c="badge-completed";s="Completed";}
          else if(l.includes("error")){c="badge-urgent";s="Error";}
          else if(l.includes("cancel")){c="badge-cancelled";s="Cancelled";}
          else if(l.includes("return")){c="badge-returned";s="Returned";}
          return '<span class="custom-badge '+c+'">'+escHtml(s)+'</span>';
        }},
        {data:"created_at",defaultContent:"",render:function(v,t){ return t!=="display"?v:fmtDate(v); }},
        {data:"priority_text",defaultContent:"",render:function(v,t){
          if(t!=="display") return v; if(!v) return "";
          var s=String(v),l=s.toLowerCase(),c="badge-prio-df";
          if(l.includes("low")) c="badge-low";
          else if(l.includes("normal")) c="badge-normal";
          else if(l.includes("high")) c="badge-high";
          else if(l.includes("urgent")||l.includes("critical")) c="badge-urgent";
          return '<span class="custom-badge '+c+'">'+escHtml(s)+'</span>';
        }}
      ],
      initComplete:function(){ hideLoader(); }
    });
    dtTable.on("preXhr.dt",function(){ showLoader(); });
    dtTable.on("xhr.dt",function(){ hideLoader(); });

    $(".kpi-card").on("click",function(){
      var st=$(this).attr("data-status")||"";
      $(".kpi-card").removeClass("active"); $(this).addClass("active");
      $("#case_status").val(st); dtTable.page("first").draw(false); loadKpiCounts();
    });

    $("#case_number,#case_title,#task_name").on("keydown",function(e){
      if(e.key==="Enter"){ e.preventDefault(); syncKpiActiveCard(); dtTable.page("first").draw(false); loadKpiCounts(); }
    });

    $("#btnSearch").on("click",function(){ syncKpiActiveCard(); dtTable.page("first").draw(false); loadKpiCounts(); });

    $("#btnClear").on("click",function(){
      $("#case_number,#case_title,#task_name,#date_from,#date_to").val("");
      $("#case_status").val("Pending");
      $(".kpi-card").removeClass("active");
      $('.kpi-card[data-status="Pending"]').addClass("active");
      $("#process").val("");
      setKpiPlaceholders();
      dtTable.order([[7,"desc"]]); dtTable.page("first").draw(false); loadKpiCounts();
    });

    $("#btnRefresh").on("click",function(){
      showLoader();
      $.when(loadBootstrap()).always(function(){
        dtTable.page("first").draw(false);
      });
    });

    $("#btnExport").on("click",function(){
      ensureXlsx(function(){
        showLoader();
        $.ajax({
          url:BACKEND, method:"POST", contentType:"application/json",
          data:JSON.stringify(Object.assign({},buildBasePayload(),{draw:1,start:0,length:5000,order:dtTable.order(),columns:dtTable.settings().init().columns})),
          success:function(resp){ hideLoader(); resp=unwrap(resp); exportRowsToExcel((resp&&Array.isArray(resp.data))?resp.data:[]); },
          error:function(xhr){ hideLoader(); showDebug("EXPORT ERROR\nstatus="+(xhr?xhr.status:"n/a")); }
        });
      });
    });

    $(document).off("click",".btn-view").on("click",".btn-view",function(e){
      e.preventDefault(); e.stopPropagation();
      var kind=$(this).attr("data-kind")||"";
      if(kind==="preview"){
        var url=$(this).attr("data-url")||"";
        var title=$(this).attr("data-title")||"Preview (read-only)";
        $("#summaryWrap").css("display","none");
        $("#previewOverlay").css({"display":"block","pointer-events":"auto"});
        $("#casePreviewFrame").css("display","block").attr("src",url);
        $("#readOnlyBadge").css("display","flex");
        $("#previewTitle").text(title);
        $("#previewModal").modal("show");
        setTimeout(function(){ var ov=document.getElementById("previewOverlay"); if(ov) ov.focus(); },200);
        return;
      }
      if(kind==="summary"){
        var rid=$(this).attr("data-request-id")||"";
        var title2=$(this).attr("data-title")||("Request #"+rid);
        lastSummaryPayload=null;
        $("#sumPretty").prop("checked",false); $("#sumSearch").val("");
        $("#casePreviewFrame").css("display","none").attr("src","");
        $("#previewOverlay").css("display","none"); $("#readOnlyBadge").css("display","none");
        $("#summaryWrap").css("display","flex");
        $("#previewTitle").text("Summary: "+title2);
        $("#sumContent").html('<div class="p-5 text-center text-muted"><i class="fas fa-circle-notch fa-spin fa-2x"></i><br>Generating case summary...</div>');
        $("#previewModal").modal("show");
        showLoader();
        $.ajax({
          url:BACKEND, type:"POST", contentType:"application/json",
          data:JSON.stringify(Object.assign({},buildBasePayload(),{mode:"request_summary",request_id:Number(rid)})),
          timeout:15000,
          success:function(resp){ hideLoader(); lastSummaryPayload=unwrap(resp); if(!lastSummaryPayload||lastSummaryPayload.ok!==true){ $("#sumContent").html('<div class="p-3 text-danger">Could not load summary data.</div>'); return; } $("#sumContent").html(renderSummary(lastSummaryPayload)); },
          error:function(){ hideLoader(); $("#sumContent").html('<div class="p-3 <?php
/**
 * CS Dashboard - Participated SQL Backend
 * By: Andres Garcia 
 */

$apiHost = rtrim((string)getenv('API_HOST'), '/');
$apiSql  = getenv('API_SQL') ?: '/admin/package-proservice-tools/sql';
$sqlEndpoint = $apiHost . $apiSql;

if (!isset($data) || !is_array($data)) $data = [];

$mode = trim((string)($data['mode'] ?? ''));

$draw   = (int)($data['draw'] ?? 1);
$start  = max(0, (int)($data['start'] ?? 0));
$length = max(1, min(5000, (int)($data['length'] ?? 25)));

$caseNum   = trim((string)($data['case_number'] ?? ''));
$title     = trim((string)($data['case_title'] ?? ''));
$taskName  = trim((string)($data['task_name'] ?? ''));
$status    = trim((string)($data['case_status'] ?? ''));
$processId = trim((string)($data['process_id'] ?? ''));
$dateFrom  = trim((string)($data['date_from'] ?? ''));
$dateTo    = trim((string)($data['date_to'] ?? ''));

$currentUserId = (int)($data['currentUserId'] ?? ($data['_request']['user_id'] ?? 0));

function escLike($v){ return str_replace(["\\","'","%","_"], ["\\\\","\\'","\\%","\\_"], (string)$v); }
function escEq($v){ return str_replace("'", "\\'", (string)$v); }

/* Sends SQL to the ProService Tools endpoint and normalizes the response. */
function callSql($endpoint, $sql){
  static $token = null, $client = null;
  if ($token === null) $token = getenv("API_TOKEN");
  if ($client === null) {
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 25]);
  }
  try{
    $res = $client->request('POST', $endpoint, [
      'headers' => [
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer '.$token
      ],
      'body' => json_encode(["SQL"=>base64_encode($sql)])
    ]);
    $json = json_decode($res->getBody()->getContents(), true);
    if (is_array($json) && isset($json['output']) && is_array($json['output'])) return $json['output'];
    if (is_array($json) && isset($json['data'])   && is_array($json['data']))   return $json['data'];
    return is_array($json) ? $json : [];
  }catch(\Throwable $e){
    return [];
  }
}

/* Calls the ProcessMaker API directly for request summary data. */
function callApiGet($url){
  static $token = null, $client = null;
  if ($token === null) $token = getenv("API_TOKEN");
  if ($client === null) {
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 25]);
  }
  try{
    $res = $client->request('GET', $url, [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Bearer '.$token,
      ]
    ]);
    $json = json_decode($res->getBody()->getContents(), true);
    return is_array($json) ? $json : [];
  }catch(\Throwable $e){
    return [];
  }
}

/* Builds a label map from the summary screen config when available. */
function buildLabelMapFromScreenConfig($screenConfig){
  $map = [];
  $walk = function($node) use (&$map, &$walk){
    if ($node === null) return;
    if (is_array($node)) {
      foreach ($node as $k => $v) {
        if ($k === 'config' && is_array($v) && isset($v['name'])) {
          $name = (string)$v['name'];
          $label = isset($v['label']) && $v['label'] !== '' ? (string)$v['label'] : $name;
          $map[$name] = $label;
        }
        $walk($v);
      }
    }
  };
  $walk($screenConfig);
  return $map;
}

/* =================== PARTICIPATED SCOPE =================== */
$scopeWhere = "UPPER(COALESCE(P.status, '')) = 'ACTIVE'
  AND EXISTS (
    SELECT 1
    FROM process_request_tokens PT
    WHERE PT.process_request_id = PR.id
      AND PT.user_id = {$currentUserId}
  )";

/* =================== FILTER BUILD =================== */
/* Base filters without the status dropdown filter. */
$baseFilters = $scopeWhere;

if ($caseNum !== '') {
  $s = escLike($caseNum);
  $baseFilters .= " AND (CAST(PR.case_number AS CHAR) LIKE '%{$s}%' OR CAST(PR.id AS CHAR) LIKE '%{$s}%')";
}
if ($title !== '') {
  $s = escLike($title);
  $baseFilters .= " AND COALESCE(
    PR.data->>'$._request.case_title',
    PR.data->>'$.case_title',
    PR.data->>'$._request.caseTitle',
    PR.data->>'$.caseTitle',
    PR.data->>'$._request.case_title_formatted',
    PR.data->>'$.case_title_formatted',
    PR.data->>'$.title',
    PR.data->>'$.requestTitle',
    PR.data->>'$.Title',
    CONCAT('Case #', PR.case_number)
  ) LIKE '%{$s}%'";
}
if ($taskName !== '') {
  $s = escLike($taskName);
  $baseFilters .= " AND EXISTS (
    SELECT 1
    FROM process_request_tokens PTT
    WHERE PTT.process_request_id = PR.id
      AND PTT.status = 'ACTIVE'
      AND COALESCE(PTT.element_name, '') LIKE '%{$s}%'
  )";
}
if ($processId !== '') {
  $pid = (int)$processId;
  if ($pid > 0) $baseFilters .= " AND PR.process_id = {$pid}";
}
if ($dateFrom !== '') $baseFilters .= " AND PR.created_at >= '".escEq($dateFrom)."'";
if ($dateTo   !== '') $baseFilters .= " AND PR.created_at < DATE_ADD('".escEq($dateTo)."', INTERVAL 1 DAY)";

/* Status filter only applies to the table and specific KPI refresh calls. */
$statusFilter = '';
if ($status !== '') {
  $st = strtoupper($status);
  if ($st === 'PENDING') {
    $statusFilter = " AND PR.status IN ('ACTIVE','In Progress')";
  } elseif ($st === 'COMPLETED') {
    $statusFilter = " AND PR.status IN ('COMPLETED')";
  } elseif ($st === 'CANCELLED' || $st === 'CANCELED') {
    $statusFilter = " AND PR.status IN ('CANCELED','CANCELLED')";
  }
}

/* Full filters used by the table. */
$fullFilters = $baseFilters . $statusFilter;

/* =========================================================
 * 1) PROCESS LIST
 * ========================================================= */
if ($mode === 'process_list') {
  $rows = callSql($sqlEndpoint, "
    SELECT id AS process_id, name AS process_name
    FROM processes
    WHERE status='ACTIVE'
      AND deleted_at IS NULL
      AND (is_template=0 OR is_template IS NULL)
    ORDER BY name ASC
  ");
  $out = [];
  foreach ($rows as $p) {
    $id = (int)($p['process_id'] ?? 0);
    $name = (string)($p['process_name'] ?? '');
    if ($id > 0 && $name !== '') {
      $out[] = ['process_id' => $id, 'process_name' => $name];
    }
  }
  return ['data' => $out];
}

/* =========================================================
 * 2) INIT
 * Global KPI counts only, without status filters.
 * ========================================================= */
if ($mode === 'init') {
  $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ('COMPLETED') THEN 1 ELSE 0 END) AS completed_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE {$scopeWhere}
  ";
  $kpiRows = callSql($sqlEndpoint, $kpiSql);
  $r = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];
  return [
    'kpis' => [
      'all'       => (int)($r['all_count'] ?? 0),
      'pending'   => (int)($r['pending_count'] ?? 0),
      'completed' => (int)($r['completed_count'] ?? 0),
      'cancelled' => (int)($r['cancelled_count'] ?? 0),
    ]
  ];
}

/* =========================================================
 * 3) BOOTSTRAP
 * KPIs ignore the status filter, table respects it.
 * ========================================================= */
if ($mode === 'bootstrap') {
  $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ('COMPLETED') THEN 1 ELSE 0 END) AS completed_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE {$baseFilters}
  ";
  $kpiRows = callSql($sqlEndpoint, $kpiSql);
  $k = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];

  $totalSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$scopeWhere}";
  $filteredSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$fullFilters}";
  $totalRows = callSql($sqlEndpoint, $totalSql);
  $filteredRows = callSql($sqlEndpoint, $filteredSql);
  $total = (int)($totalRows[0]['total'] ?? 0);
  $filtered = (int)($filteredRows[0]['total'] ?? 0);

  $orderDir = 'DESC';
  $orderField = 'created_at';
  $allowedOrder = [
    'case_number'   => 'PR.case_number',
    'case_title'    => 'case_title',
    'process_name'  => 'process_name',
    'current_task'  => 'current_task',
    'assigned_to'   => 'assigned_to',
    'status_text'   => 'status_text',
    'created_at'    => 'PR.created_at',
    'priority_text' => 'priority_text',
  ];
  $ob = $allowedOrder[$orderField] ?? 'PR.created_at';

  $dataSql = "
    WITH ACTIVE_TOKEN AS (
      SELECT prt.process_request_id AS request_id, MAX(prt.id) AS token_id
      FROM process_request_tokens prt
      WHERE prt.status = 'ACTIVE'
      GROUP BY prt.process_request_id
    )
    SELECT
      PR.id AS request_id,
      PR.case_number AS case_number,
      P.id AS process_id,
      P.name AS process_name,
      COALESCE(
        PR.data->>'$._request.case_title',
        PR.data->>'$.case_title',
        PR.data->>'$._request.caseTitle',
        PR.data->>'$.caseTitle',
        PR.data->>'$._request.case_title_formatted',
        PR.data->>'$.case_title_formatted',
        PR.data->>'$.title',
        PR.data->>'$.requestTitle',
        PR.data->>'$.Title',
        CONCAT('Case #', PR.case_number)
      ) AS case_title,
      CT.id AS current_token_id,
      CT.element_name AS current_task,
      CONCAT(COALESCE(U.firstname,''), ' ', COALESCE(U.lastname,'')) AS assigned_to,
      CASE
        WHEN PR.status IN ('ACTIVE','In Progress') THEN 'Pending'
        WHEN PR.status IN ('COMPLETED') THEN 'Completed'
        WHEN PR.status IN ('CANCELED','CANCELLED') THEN 'Cancelled'
        WHEN PR.status IN ('RETURNED') THEN 'Returned'
        ELSE PR.status
      END AS status_text,
      PR.created_at AS created_at,
      COALESCE(
        PR.data->>'$.priority',
        PR.data->>'$.Priority',
        PR.data->>'$.casePriority',
        'Normal'
      ) AS priority_text
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    LEFT JOIN ACTIVE_TOKEN AT ON AT.request_id = PR.id
    LEFT JOIN process_request_tokens CT ON CT.id = AT.token_id
    LEFT JOIN users U ON U.id = CT.user_id
    WHERE {$fullFilters}
    ORDER BY {$ob} DESC
    LIMIT 0, 25
  ";

  $dataRows = callSql($sqlEndpoint, $dataSql);
  $uiBase = preg_replace('~/api/1\.0/?$~','', $apiHost);

  foreach ($dataRows as &$r) {
    $rid = $r['request_id'] ?? '';
    $tokenId = $r['current_token_id'] ?? '';
    $r['open_url'] = $uiBase . '/requests/' . rawurlencode((string)$rid);
    $r['preview_url'] = '';
    if (!empty($tokenId) && ($r['status_text'] ?? '') === 'Pending') {
      $r['preview_url'] = $uiBase . '/tasks/' . rawurlencode((string)$tokenId) . '/edit/preview?alwaysAllowEditing=1&disableInterstitial=1';
    }
  }
  unset($r);

  return [
    'kpis' => [
      'all'       => (int)($k['all_count'] ?? 0),
      'pending'   => (int)($k['pending_count'] ?? 0),
      'completed' => (int)($k['completed_count'] ?? 0),
      'cancelled' => (int)($k['cancelled_count'] ?? 0),
    ],
    'table' => [
      'draw'            => 1,
      'recordsTotal'    => $total,
      'recordsFiltered' => $filtered,
      'data'            => $dataRows,
    ]
  ];
}

/* =========================================================
 * 4) REQUEST SUMMARY
 * ========================================================= */
if ($mode === 'request_summary') {
  $rid = (int)($data['request_id'] ?? 0);
  if ($rid <= 0) return ['ok'=>false, 'message'=>'Missing request_id'];

  $url = rtrim($apiHost,'/') . '/requests/' . $rid;
  $resp = callApiGet($url);

  $apiSummary = (isset($resp['summary']) && is_array($resp['summary'])) ? $resp['summary'] : [];
  $apiScreenConfig = $resp['summary_screen'] ?? null;

  $labelMap = [];
  $usedScreenConfig = false;
  if (is_array($apiScreenConfig)) {
    $labelMap = buildLabelMapFromScreenConfig($apiScreenConfig);
    $usedScreenConfig = count($labelMap) > 0;
  }

  if (is_array($apiSummary) && count($apiSummary) > 0) {
    return [
      'ok' => true,
      'request_id' => $rid,
      'usedScreenConfig' => $usedScreenConfig,
      'labelMap' => $labelMap,
      'summary' => $apiSummary,
      'source' => 'api_summary'
    ];
  }

  $sql = "
    SELECT
      PR.id AS request_id,
      PR.case_number,
      PR.status,
      PR.created_at,
      P.name AS process_name,
      PR.data AS data_json
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE PR.id = {$rid}
      AND UPPER(COALESCE(P.status, '')) = 'ACTIVE'
    LIMIT 1
  ";

  $rows = callSql($sqlEndpoint, $sql);
  $row = (is_array($rows) && isset($rows[0])) ? $rows[0] : null;

  if (!$row) {
    return ['ok'=>false, 'message'=>'Request not found in SQL', 'request_id'=>$rid];
  }

  $rawJson = $row['data_json'] ?? '';
  $decoded = is_array($rawJson) ? $rawJson : (json_decode((string)$rawJson, true) ?: []);

  $pairs = [];
  $seen = [];

  $pushPair = function($k, $v) use (&$pairs, &$seen) {
    $k = (string)$k;
    if ($k === '' || isset($seen[$k])) return;
    $seen[$k] = true;
    if (is_bool($v)) $val = $v ? 'true' : 'false';
    elseif ($v === null) $val = '';
    elseif (is_scalar($v)) $val = (string)$v;
    else $val = json_encode($v, JSON_PRETTY_PRINT);
    $pairs[] = ['key'=>$k, 'value'=>$val];
  };

  $walk = function($node, $prefix, $depth) use (&$walk, $pushPair) {
    if ($depth > 4) return;
    if (is_array($node)) {
      $isAssoc = array_keys($node) !== range(0, count($node)-1);
      if (!$isAssoc && count($node) > 0 && is_array($node[0])) {
        $pushPair($prefix, json_encode($node, JSON_PRETTY_PRINT));
        return;
      }
      foreach ($node as $k => $v) {
        $key = $isAssoc ? (string)$k : ('['.$k.']');
        $path = $prefix === '' ? $key : ($prefix . '.' . $key);
        if (is_array($v)) {
          $allScalar = true;
          foreach ($v as $vv) {
            if (!is_scalar($vv) && $vv !== null && !is_bool($vv)) {
              $allScalar = false;
              break;
            }
          }
          if ($allScalar && count($v) <= 30) {
            $pushPair($path, json_encode($v));
          } else {
            $walk($v, $path, $depth+1);
          }
        } else {
          $pushPair($path, $v);
        }
      }
    } else {
      $pushPair($prefix ?: 'value', $node);
    }
  };

  $pushPair('case_title', $decoded['_request']['case_title'] ?? ($decoded['case_title'] ?? ''));
  $pushPair('case_number', $row['case_number'] ?? '');
  $pushPair('process_name', $row['process_name'] ?? '');
  $pushPair('status', $row['status'] ?? '');
  $pushPair('created_at', $row['created_at'] ?? '');

  $walk($decoded, '', 1);

  return [
    'ok' => true,
    'request_id' => $rid,
    'usedScreenConfig' => false,
    'labelMap' => ['case_title' => 'Case Title'],
    'summary' => $pairs,
    'source' => 'sql_data_fallback'
  ];
}

/* =========================================================
 * 5) KPI COUNTS
 * Used after KPI clicks or manual refresh.
 * The status filter is applied here because the frontend
 * already changed the dropdown to the selected status.
 * ========================================================= */
if ($mode === 'kpi_counts') {
  $kpiSql = "
    SELECT
      COUNT(*) AS all_count,
      SUM(CASE WHEN PR.status IN ('ACTIVE','In Progress') THEN 1 ELSE 0 END) AS pending_count,
      SUM(CASE WHEN PR.status IN ('COMPLETED') THEN 1 ELSE 0 END) AS completed_count,
      SUM(CASE WHEN PR.status IN ('CANCELED','CANCELLED') THEN 1 ELSE 0 END) AS cancelled_count
    FROM process_requests PR
    JOIN processes P ON P.id = PR.process_id
    WHERE {$fullFilters}
  ";
  $kpiRows = callSql($sqlEndpoint, $kpiSql);
  $r = (is_array($kpiRows) && isset($kpiRows[0])) ? $kpiRows[0] : [];
  return [
    'kpis' => [
      'all'       => (int)($r['all_count'] ?? 0),
      'pending'   => (int)($r['pending_count'] ?? 0),
      'completed' => (int)($r['completed_count'] ?? 0),
      'cancelled' => (int)($r['cancelled_count'] ?? 0),
    ]
  ];
}

/* =========================================================
 * 6) STANDARD TABLE
 * Uses all active filters, including status.
 * ========================================================= */
$orderColIdx = (int)($data['order'][0]['column'] ?? 7);
$orderDir    = strtolower((string)($data['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
$orderField  = (string)($data['columns'][$orderColIdx]['data'] ?? 'created_at');

$allowedOrder = [
  'case_number'   => 'PR.case_number',
  'case_title'    => 'case_title',
  'process_name'  => 'process_name',
  'current_task'  => 'current_task',
  'assigned_to'   => 'assigned_to',
  'status_text'   => 'status_text',
  'created_at'    => 'PR.created_at',
  'priority_text' => 'priority_text',
];
$ob = $allowedOrder[$orderField] ?? 'PR.created_at';

$totalSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$scopeWhere}";
$filteredSql = "SELECT COUNT(*) AS total FROM process_requests PR JOIN processes P ON P.id = PR.process_id WHERE {$fullFilters}";
$totalRows = callSql($sqlEndpoint, $totalSql);
$filteredRows = callSql($sqlEndpoint, $filteredSql);
$total = (int)($totalRows[0]['total'] ?? 0);
$filtered = (int)($filteredRows[0]['total'] ?? 0);

$dataSql = "
  WITH ACTIVE_TOKEN AS (
    SELECT prt.process_request_id AS request_id, MAX(prt.id) AS token_id
    FROM process_request_tokens prt
    WHERE prt.status = 'ACTIVE'
    GROUP BY prt.process_request_id
  )
  SELECT
    PR.id AS request_id,
    PR.case_number AS case_number,
    P.id AS process_id,
    P.name AS process_name,
    COALESCE(
      PR.data->>'$._request.case_title',
      PR.data->>'$.case_title',
      PR.data->>'$._request.caseTitle',
      PR.data->>'$.caseTitle',
      PR.data->>'$._request.case_title_formatted',
      PR.data->>'$.case_title_formatted',
      PR.data->>'$.title',
      PR.data->>'$.requestTitle',
      PR.data->>'$.Title',
      CONCAT('Case #', PR.case_number)
    ) AS case_title,
    CT.id AS current_token_id,
    CT.element_name AS current_task,
    CONCAT(COALESCE(U.firstname,''), ' ', COALESCE(U.lastname,'')) AS assigned_to,
    CASE
      WHEN PR.status IN ('ACTIVE','In Progress') THEN 'Pending'
      WHEN PR.status IN ('COMPLETED') THEN 'Completed'
      WHEN PR.status IN ('CANCELED','CANCELLED') THEN 'Cancelled'
      WHEN PR.status IN ('RETURNED') THEN 'Returned'
      ELSE PR.status
    END AS status_text,
    PR.created_at AS created_at,
    COALESCE(
      PR.data->>'$.priority',
      PR.data->>'$.Priority',
      PR.data->>'$.casePriority',
      'Normal'
    ) AS priority_text
  FROM process_requests PR
  JOIN processes P ON P.id = PR.process_id
  LEFT JOIN ACTIVE_TOKEN AT ON AT.request_id = PR.id
  LEFT JOIN process_request_tokens CT ON CT.id = AT.token_id
  LEFT JOIN users U ON U.id = CT.user_id
  WHERE {$fullFilters}
  ORDER BY {$ob} {$orderDir}
  LIMIT {$start}, {$length}
";

$dataRows = callSql($sqlEndpoint, $dataSql);
$uiBase = preg_replace('~/api/1\.0/?$~','', $apiHost);

foreach ($dataRows as &$r) {
  $rid = $r['request_id'] ?? '';
  $tokenId = $r['current_token_id'] ?? '';
  $r['open_url'] = $uiBase . '/requests/' . rawurlencode((string)$rid);
  $r['preview_url'] = '';
  if (!empty($tokenId) && ($r['status_text'] ?? '') === 'Pending') {
    $r['preview_url'] = $uiBase . '/tasks/' . rawurlencode((string)$tokenId) . '/edit/preview?alwaysAllowEditing=1&disableInterstitial=1';
  }
}
unset($r);

return [
  "draw" => $draw,
  "recordsTotal" => $total,
  "recordsFiltered" => $filtered,
  "data" => $dataRows
];
?>text-danger">Request failed.</div>'); }
        });
      }
    });

    $("#sumSearch").on("keyup",function(){ var v=$(this).val().toLowerCase(); $(".sum-item").each(function(){ $(this).toggle(($(this).data("label")||"").indexOf(v)>-1); }); });
    $("#sumPretty").on("change",function(){ if(!lastSummaryPayload) return; $("#sumContent").html(renderSummary(lastSummaryPayload)); var h=document.getElementById("sumScroll"); if(h) h.scrollTop=0; });
    $("#casePreviewFrame").on("load",function(){ try{ var d=this.contentWindow.document,s=d.createElement("div"); s.style.cssText="position:fixed;inset:0;z-index:2147483647;background:transparent;cursor:not-allowed;"; d.body.appendChild(s); $("#previewOverlay").css("pointer-events","none"); }catch(e){ $("#previewOverlay").css("pointer-events","auto"); } });
    $("#previewModal").on("hidden.bs.modal",function(){ $("#casePreviewFrame").attr("src",""); $("#sumContent").html(""); lastSummaryPayload=null; var h=document.getElementById("sumScroll"); if(h) h.scrollTop=0; });
  }

  $(document).ready(function(){
    try{
      clearDebug(); showLoader(); setProcessLoadingState(); setKpiPlaceholders();
      initOverlayScrollForwarding();
      loadBootstrap().always(function(){ initTable(); });
    }catch(err){ hideLoader(); showDebug("Critical error: "+String(err)); }
  });
})();
</script>
JS;

$html = $style . '<div class="container-fluid">' . $body . '</div>' . str_replace(
    ["__BACKEND__", "__CURRENT_USER__"],
    [addslashes($backendUrl), (string)$currentUserId],
    $script
);

return ['PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)];
