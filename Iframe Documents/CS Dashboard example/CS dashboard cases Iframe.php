<?php

/**********************************
 * CS Dashboard - All Cases (IFRAME Frontend)
 * By: Andres Garcia 
 **********************************/

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($data) || !is_array($data)) $data = [];

$API_HOST      = $data['_env']['API_HOST'] ?? getenv('API_HOST');
$currentUserId = (int)($data['currentUserId'] ?? ($data['_request']['user_id'] ?? 0));
$backendUrl    = rtrim($API_HOST, '/') . '/pstools/script/cs-dashboard-cases-sql';

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

  .card-pending{background:#dcfce7;border-color:#bbf7d0;}
  .card-pending.active{border-color:#22c55e;background:#bbf7d0;}
  .card-pending .kpi-right i{color:#16a34a;}

  .card-completed{background:#eff6ff;border-color:#bfdbfe;}
  .card-completed.active{border-color:#3b82f6;background:#dbeafe;}
  .card-completed .kpi-right i{color:#2563eb;}

  .card-cancelled{background:#fff;border-color:#e5e7eb;}
  .card-cancelled.active{border-color:#6b7280;background:#f3f4f6;}
  .card-cancelled .kpi-right i{color:#4b5563;}

  .card-all{background:#fdfaef;border-color:#fceea6;}
  .card-all.active{border-color:#eab308;background:#fef08a;}
  .card-all .kpi-right i{color:#d97706;}

  .custom-badge{display:inline-flex;align-items:center;justify-content:center;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;letter-spacing:.3px;white-space:nowrap;text-transform:capitalize;box-shadow:0 1px 2px rgba(0,0,0,0.05);}
  .badge-pending{background:#f59e0b;color:#fff;}
  .badge-completed{background:#49a24d;color:#fff;}
  .badge-cancelled{background:#cb4646;color:#fff;}
  .badge-returned{background:#facc15;color:#212529;}
  .badge-urgent{background:#dc2626;color:#fff;}
  .badge-status-df{background:#6b7280;color:#fff;}

  .badge-low{background:#e2e8f0;color:#475569;}
  .badge-normal{background:#3b82f6;color:#fff;}
  .badge-high{background:#f97316;color:#fff;}
  .badge-prio-df{background:#f3f4f6;color:#4b5563;}

  .card-filters{background:#fff;border:1px solid #e5e9f0;border-radius:6px;padding:12px 14px;box-shadow:0 1px 2px rgba(0,0,0,0.05);}
  label{font-size:.86rem;margin-bottom:.25rem;color:#2b2b2b;font-weight:600;display:block;}
  input.form-control,select.form-control{font-size:.9rem;height:36px;padding:.25rem .5rem;}

  .toolbar{margin-top:14px;display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-bottom:12px;}
  .toolbar .right{display:flex;gap:8px;align-items:center;}

  table.dataTable thead th{background:#467BBA !important;color:#fff !important;border:none !important;font-weight:700;text-transform:uppercase;font-size:.82rem;}
  table.dataTable tbody td{vertical-align:middle;}

  #csLoader{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    background:rgba(248,250,252,.72);
    backdrop-filter:blur(1px);
    -webkit-backdrop-filter:blur(1px);
    z-index:9999;
  }
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
  .preview-badge{
    position:absolute;top:10px;right:10px;z-index:20;background:#111827;color:#fff;
    font-size:12px;font-weight:700;padding:6px 10px;border-radius:999px;opacity:.92;
  }
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
    <|div class="col-md-2 mb-2">
      <|label for="case_number">Case Number<|/label>
      <|input id="case_number" type="text" class="form-control" placeholder="e.g. 2111" />
    <|/div>

    <|div class="col-md-3 mb-2 case-title-col">
      <|label for="case_title">Case Title<|/label>
      <|input id="case_title" type="text" class="form-control" placeholder="contains..." />
    <|/div>

    <|div class="col-md-3 mb-2">
      <|label for="task_name">Task<|/label>
      <|input id="task_name" type="text" class="form-control" placeholder="task name..." />
    <|/div>

    <|div class="col-md-2 mb-2">
      <|label for="process">Process<|/label>
      <|select id="process" class="form-control"><|option value="">Loading...<|/option><|/select>
    <|/div>

    <|div class="col-md-2 mb-2">
      <|label for="case_status">Status<|/label>
      <|select id="case_status" class="form-control">
        <|option value="">All<|/option>
        <|option value="Pending" selected>Pending<|/option>
        <|option value="Completed">Completed<|/option>
        <|option value="Cancelled">Cancelled<|/option>
        <|option value="Error">Error<|/option>
      <|/select>
    <|/div>
  <|/div>

  <|div class="row mt-2">
    <|div class="col-md-3 mb-2">
      <|label for="date_from">From (Created)<|/label>
      <|input id="date_from" type="date" class="form-control" />
    <|/div>

    <|div class="col-md-3 mb-2">
      <|label for="date_to">To (Created)<|/label>
      <|input id="date_to" type="date" class="form-control" />
    <|/div>

    <|div class="col-md-3 mb-2 d-flex align-items-end">
      <|div class="btn-row-left">
        <|button id="btnSearch" class="btn btn-primary"><|i class="fas fa-search"><|/i> Search<|/button>
        <|button id="btnClear" class="btn btn-outline-secondary"><|i class="fas fa-eraser"><|/i> Clear<|/button>
      <|/div>
    <|/div>

    <|div class="col-md-3 mb-2 d-flex align-items-end justify-content-end">
      <|small style="color:#6b7280;">Press Enter in Number/Title/Task to search<|/small>
    <|/div>
  <|/div>
<|/div>

<|div class="toolbar">
  <|div class="right">
    <|button id="btnExport" class="btn btn-outline-success btn-sm"><|i class="fas fa-file-excel"><|/i> Export<|/button>
    <|button id="btnRefresh" class="btn btn-outline-primary btn-sm"><|i class="fas fa-sync-alt"><|/i> Refresh<|/button>
  <|/div>
<|/div>

<|div id="csDebug"><|/div>

<|div class="mt-2">
  <|table id="casesTable" class="table table-sm table-hover table-bordered w-100">
    <|thead>
      <|tr>
        <|th>CASE #<|/th>
        <|th>TITLE<|/th>
        <|th>VIEW<|/th>
        <|th>PROCESS<|/th>
        <|th>TASK<|/th>
        <|th>ASSIGNED TO<|/th>
        <|th>STATUS<|/th>
        <|th>CREATED<|/th>
        <|th>PRIORITY<|/th>
      <|/tr>
    <|/thead>
    <|tbody><|/tbody>
  <|/table>
<|/div>

<|div id="csLoader">
  <|div class="cs-loader-box">
    <|div class="cs-spinner"><|/div>
    <|div class="cs-loader-title">Loading dashboard...<|/div>
    <|div class="cs-loader-text">Please wait while the All Cases tab is being prepared.<|/div>
  <|/div>
<|/div>

<|div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <|div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
    <|div class="modal-content" style="height:90vh; display:flex; flex-direction:column;">
      <|div class="modal-header" style="flex-shrink:0;">
        <|h5 class="modal-title" id="previewTitle">Case Details<|/h5>
        <|button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <|span aria-hidden="true">&times;<|/span>
        <|/button>
      <|/div>

      <|div class="modal-body p-0" style="flex-grow:1; position:relative; overflow:hidden; min-height:0;">
        <|div class="preview-wrap" style="position:absolute; inset:0; background:#f8fafc;">
          <|div id="readOnlyBadge" class="preview-badge" style="display:none;"><|i class="fas fa-lock"><|/i> Read-only view<|/div>
          <|div id="previewOverlay" class="preview-overlay" style="display:none;"><|/div>
          <|iframe id="casePreviewFrame" src="" style="width:100%; height:100%; border:0; display:none;"><|/iframe>

          <|div id="summaryWrap" style="display:none; height:100%; flex-direction:column;">
            <|div class="sum-header-strip">
              <|div class="d-flex align-items-center gap-3">
                <|div class="sum-search-box">
                  <|i class="fas fa-search"><|/i>
                  <|input type="text" id="sumSearch" class="form-control form-control-sm" placeholder="Search field..." />
                <|/div>
              <|/div>
              <|div class="d-flex align-items-center gap-3">
                <|label class="sum-pretty mb-0 mr-3" style="cursor:pointer;">
                   <|input type="checkbox" id="sumPretty" /> JSON mode
                <|/label>
                <|button class="btn btn-sm btn-outline-primary" onclick="window.printSummary()"><|i class="fas fa-print"><|/i> Print<|/button>
              <|/div>
            <|/div>

            <|div class="sum-scroll" id="sumScroll">
              <|div id="sumContent"><|/div>
            <|/div>
          <|/div>
        <|/div>
      <|/div>
    <|/div>
  <|/div>
<|/div>
HTML;

$script = <<<'JS'
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net/1.13.7/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.net-bs4/1.13.7/dataTables.bootstrap4.min.js"></script>
<script>
(function(){
  var BACKEND="__BACKEND__", CURRENT_USER=__CURRENT_USER__, TAB="all_cases";
  var loader=document.getElementById("csLoader"), dbg=document.getElementById("csDebug"), dtTable=null;
  var loaderTimeout=null, xlsxReady=false, xlsxLoading=false, xlsxQueue=[], lastSummaryPayload=null;
  var boot=null;
  var hasSearched = false;

  function showLoader(){ $(loader).css("display","flex"); clearTimeout(loaderTimeout); loaderTimeout=setTimeout(hideLoader,20000); }
  function hideLoader(){ $(loader).hide(); clearTimeout(loaderTimeout); }
  function showDebug(t){ if(dbg){ dbg.style.display="block"; dbg.textContent=t; } }
  function clearDebug(){ if(dbg){ dbg.style.display="none"; dbg.textContent=""; } }
  function unwrap(r){ return (r&&r.output)?r.output:r; }
  function escHtml(s){ return String(s||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"); }

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

    sel.disabled=true;
    while(sel.options.length) sel.remove(0);

    var optAll=document.createElement("option");
    optAll.value="";
    optAll.textContent="All Processes";
    sel.appendChild(optAll);

    var optDiv=document.createElement("option");
    optDiv.value="DIVIDER";
    optDiv.textContent="----------";
    optDiv.disabled=true;
    sel.appendChild(optDiv);

    (list||[]).forEach(function(p){
      if(!p) return;
      var id=String(p.process_id||""), name=String(p.process_name||"");
      if(!id||!name) return;
      var opt=document.createElement("option");
      opt.value=id;
      opt.textContent=name;
      sel.appendChild(opt);
    });

    sel.value="";
    sel.disabled=false;
  }

  function setProcessLoadingState(){
    var sel=document.getElementById("process");
    if(!sel) return;
    sel.disabled=true;
    while(sel.options.length) sel.remove(0);
    var opt=document.createElement("option");
    opt.value="";
    opt.textContent="Loading...";
    sel.appendChild(opt);
  }

  function setProcessFallbackState(){
    var sel=document.getElementById("process");
    if(!sel) return;
    sel.disabled=false;
    while(sel.options.length) sel.remove(0);
    var opt=document.createElement("option");
    opt.value="";
    opt.textContent="All Processes";
    sel.appendChild(opt);
  }

  // Bootstrap only loads KPI data. Records stay empty until the first search.
  function loadBootstrap(){
    var payload = Object.assign({}, buildBasePayload(), { mode:"bootstrap" });

    return $.ajax({
      url:BACKEND,
      type:"POST",
      contentType:"application/json",
      data:JSON.stringify(payload),
      timeout:25000
    }).done(function(resp){
      resp=unwrap(resp)||{};
      boot={
        kpis: resp.kpis || {all:0,pending:0,completed:0,cancelled:0},
        table: {draw:1,recordsTotal:0,recordsFiltered:0,data:[]}
      };
      applyKpis(boot.kpis);
    }).fail(function(){
      boot={table:{draw:1,recordsTotal:0,recordsFiltered:0,data:[]},kpis:{all:0,pending:0,completed:0,cancelled:0}};
    });
  }

  function loadProcessListDeferred(){
    if (window.requestIdleCallback) {
      requestIdleCallback(fetchProcesses, { timeout: 1800 });
    } else {
      setTimeout(fetchProcesses, 400);
    }
  }

  function fetchProcesses(){
    $.ajax({
      url:BACKEND,
      type:"POST",
      contentType:"application/json",
      data:JSON.stringify({mode:"process_list"}),
      timeout:20000
    }).done(function(resp){
      resp=unwrap(resp);
      var list=[];
      if (Array.isArray(resp)) list=resp;
      else if (resp && Array.isArray(resp.data)) list=resp.data;
      fillProcessDropdown(list);
    }).fail(function(){
      setProcessFallbackState();
    });
  }

  function loadKpiCounts(){
    if(!hasSearched) return;
    var payload=Object.assign({},buildBasePayload(),{mode:"kpi_counts"});
    payload.case_status="";
    return $.ajax({
      url:BACKEND,
      type:"POST",
      contentType:"application/json",
      data:JSON.stringify(payload),
      timeout:20000,
      success:function(resp){
        resp=unwrap(resp);
        applyKpis(resp&&resp.kpis?resp.kpis:resp);
      }
    });
  }

  function ensureXlsx(cb){
    if(xlsxReady&&window.XLSX){ cb(); return; }
    xlsxQueue.push(cb);
    if(xlsxLoading) return;
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
    if($("#sumPretty").is(":checked")) return '<pre class="sum-json">'+escHtml(JSON.stringify(payload,null,2))+'</pre>';

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
          try{
            var parsed=JSON.parse(val);
            if(typeof parsed==="object" && parsed!==null){
              val='<pre class="sum-json">'+escHtml(JSON.stringify(parsed,null,2))+'</pre>';
              isJsonString=true;
            }
          }catch(e){}
        }
        if(!isJsonString){
          if(typeof val==="object") val=escHtml(JSON.stringify(val));
          else val=escHtml(String(val));
        }
      }

      var hi=coreKeys.includes(key)?"sum-item-highlight":"";
      var lg=(String(val).length>60 || String(val).indexOf('<pre')!==-1)?"full-width":"";

      html+='<div class="sum-item '+hi+' '+lg+'" data-label="'+escHtml(label.toLowerCase())+'">';
      html+='<div class="sum-label">'+escHtml(label)+'</div>';
      html+='<div class="sum-value">'+val+'</div>';
      html+='</div>';
    });

    return html+'</div>';
  }

  window.printSummary=function(){
    var c=document.getElementById("sumContent").innerHTML;
    var w=window.open("","","height=700,width=900");
    w.document.write("<html><head><title>Summary</title><style>body{font-family:sans-serif;padding:30px;color:#333}.sum-grid{display:grid;grid-template-columns:1fr 1fr;gap:15px}.sum-item{border:1px solid #eaeaea;padding:12px;border-radius:4px}.full-width{grid-column:span 2}.sum-label{font-weight:bold;font-size:11px;color:#888;margin-bottom:4px}.sum-value{font-size:14px;white-space:pre-wrap;word-break:break-word}.sum-item-highlight{background:#f9f9f9;border-color:#ddd;} pre.sum-json{background:#f1f5f9;color:#334155;padding:15px;border:1px solid #cbd5e1;border-radius:8px;font-size:12px;overflow-x:auto;}</style></head><body><h2>"+$("#previewTitle").text()+"</h2>"+c+"</body></html>");
    w.document.close();
    w.print();
  };

  function exportRowsToExcel(rows){
    try{
      var data=(rows||[]).map(function(r){
        return {
          "Case Number":r.case_number||"",
          "Case Title":r.case_title||"",
          "Process":r.process_name||"",
          "Task":r.current_task||"",
          "Assigned User":r.assigned_to||"",
          "Status":r.status_text||"",
          "Created":r.created_at||"",
          "Priority":r.priority_text||""
        };
      });
      var ws=XLSX.utils.json_to_sheet(data);
      var wb=XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb,ws,"AllCases");
      XLSX.writeFile(wb,"AllCases.xlsx");
    }catch(e){
      showDebug("Export error:\n"+String(e));
    }
  }

  function initTableFromBootstrap(){
    dtTable=$("#casesTable").DataTable({
      processing:true,
      serverSide:true,
      searching:false,
      autoWidth:false,
      deferRender:true,
      pageLength:25,
      lengthMenu:[[25,50,100],[25,50,100]],
      order:[[7,"desc"]],
      deferLoading:[0,0],
      data:[],
      dom:"<'row'<'col-sm-6'l><'col-sm-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",
      ajax:function(d, callback){
        if(!hasSearched){
          callback({draw:d.draw,recordsTotal:0,recordsFiltered:0,data:[]});
          return;
        }

        $.ajax({
          url:BACKEND,
          type:"POST",
          contentType:"application/json",
          data:JSON.stringify(Object.assign({},buildBasePayload(),{
            draw:d.draw,start:d.start,length:d.length,order:d.order,columns:d.columns
          })),
          timeout:25000,
          success:function(resp){
            resp=unwrap(resp);
            callback(resp&&resp.data ? resp : {draw:d.draw,recordsTotal:0,recordsFiltered:0,data:[]});
          },
          error:function(xhr,st){
            hideLoader();
            if(st==="abort"||(xhr&&xhr.status===0)) return;
            showDebug("AJAX ERROR\nstatus="+(xhr?xhr.status:"n/a"));
            callback({draw:d.draw,recordsTotal:0,recordsFiltered:0,data:[]});
          }
        });
      },
      language:{
        emptyTable:"Click Search to load records.",
        zeroRecords:"No records found."
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
      initComplete:function(){
        hideLoader();
        loadProcessListDeferred();
      }
    });

    dtTable.on("preXhr.dt",function(){
      if(hasSearched) showLoader();
    });
    dtTable.on("xhr.dt",function(){ hideLoader(); });

    $(".kpi-card").on("click",function(){
      var st=$(this).attr("data-status")||"";
      $(".kpi-card").removeClass("active");
      $(this).addClass("active");
      $("#case_status").val(st);
      hasSearched = true;
      dtTable.page("first").draw(false);
      loadKpiCounts();
    });

    $("#case_number,#case_title,#task_name").on("keydown",function(e){
      if(e.key==="Enter"){
        e.preventDefault();
        hasSearched = true;
        syncKpiActiveCard();
        dtTable.page("first").draw(false);
        loadKpiCounts();
      }
    });

    $("#btnSearch").on("click",function(){
      hasSearched = true;
      syncKpiActiveCard();
      dtTable.page("first").draw(false);
      loadKpiCounts();
    });

    $("#btnClear").on("click",function(){
      $("#case_number,#case_title,#task_name,#date_from,#date_to").val("");
      $("#case_status").val("Pending");
      $(".kpi-card").removeClass("active");
      $('.kpi-card[data-status="Pending"]').addClass("active");
      $("#process").val("");
      setKpiPlaceholders();
      hasSearched = false;
      dtTable.clear().draw();
    });

    $("#btnRefresh").on("click",function(){
      if(!hasSearched) return;
      showLoader();
      loadProcessListDeferred();
      dtTable.page("first").draw(false);
      loadKpiCounts();
    });

    $("#btnExport").on("click",function(){
      if(!hasSearched) return;
      ensureXlsx(function(){
        showLoader();
        $.ajax({
          url:BACKEND,
          method:"POST",
          contentType:"application/json",
          data:JSON.stringify(Object.assign({},buildBasePayload(),{
            draw:1,start:0,length:5000,order:dtTable.order(),columns:dtTable.settings().init().columns
          })),
          success:function(resp){
            hideLoader();
            resp=unwrap(resp);
            exportRowsToExcel((resp&&Array.isArray(resp.data))?resp.data:[]);
          },
          error:function(xhr){
            hideLoader();
            showDebug("EXPORT ERROR\nstatus="+(xhr?xhr.status:"n/a"));
          }
        });
      });
    });

    $(document).off("click",".btn-view").on("click",".btn-view",function(e){
      e.preventDefault();
      e.stopPropagation();

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

        setTimeout(function(){
          var ov=document.getElementById("previewOverlay");
          if(ov) ov.focus();
        },200);
        return;
      }

      if(kind==="summary"){
        var rid=$(this).attr("data-request-id")||"";
        var title2=$(this).attr("data-title")||("Request #"+rid);

        lastSummaryPayload=null;
        $("#sumPretty").prop("checked",false);
        $("#sumSearch").val("");

        $("#casePreviewFrame").css("display","none").attr("src","");
        $("#previewOverlay").css("display","none");
        $("#readOnlyBadge").css("display","none");

        $("#summaryWrap").css("display","flex");
        $("#previewTitle").text("Summary: "+title2);
        $("#sumContent").html('<div class="p-5 text-center text-muted"><i class="fas fa-circle-notch fa-spin fa-2x"></i><br>Generating case summary...</div>');
        $("#previewModal").modal("show");

        showLoader();
        $.ajax({
          url:BACKEND,
          type:"POST",
          contentType:"application/json",
          data:JSON.stringify(Object.assign({},buildBasePayload(),{mode:"request_summary",request_id:Number(rid)})),
          timeout:15000,
          success:function(resp){
            hideLoader();
            lastSummaryPayload=unwrap(resp);
            if(!lastSummaryPayload||lastSummaryPayload.ok!==true){
              $("#sumContent").html('<div class="p-3 text-danger">Could not load summary data.</div>');
              return;
            }
            $("#sumContent").html(renderSummary(lastSummaryPayload));
          },
          error:function(){
            hideLoader();
            $("#sumContent").html('<div class="p-3 text-danger">Request failed.</div>');
          }
        });
      }
    });

    $("#sumSearch").on("keyup",function(){
      var v=$(this).val().toLowerCase();
      $(".sum-item").each(function(){
        var label=$(this).data("label")||"";
        $(this).toggle(label.indexOf(v)>-1);
      });
    });

    $("#sumPretty").on("change",function(){
      if(!lastSummaryPayload) return;
      $("#sumContent").html(renderSummary(lastSummaryPayload));
      var h=document.getElementById("sumScroll");
      if(h) h.scrollTop=0;
    });

    $("#casePreviewFrame").on("load",function(){
      try{
        var d=this.contentWindow.document;
        var s=d.createElement("div");
        s.style.cssText="position:fixed;inset:0;z-index:2147483647;background:transparent;cursor:not-allowed;";
        d.body.appendChild(s);
        $("#previewOverlay").css("pointer-events","none");
      }catch(e){
        $("#previewOverlay").css("pointer-events","auto");
      }
    });

    $("#previewModal").on("hidden.bs.modal",function(){
      $("#casePreviewFrame").attr("src","");
      $("#sumContent").html("");
      lastSummaryPayload=null;
      var h=document.getElementById("sumScroll");
      if(h) h.scrollTop=0;
    });
  }

  $(document).ready(function(){
    try{
      clearDebug();
      showLoader();
      setProcessLoadingState();
      setKpiPlaceholders();
      initOverlayScrollForwarding();

      loadBootstrap().always(function(){
        initTableFromBootstrap();
      });

    }catch(err){
      hideLoader();
      showDebug("Critical error: "+String(err));
    }
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
