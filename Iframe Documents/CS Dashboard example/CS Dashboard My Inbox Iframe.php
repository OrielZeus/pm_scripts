<?php

/**********************************
 * CS Dashboard - My Inbox (IFRAME Frontend)
 * STABLE VERSION
 * 
 * Notes:
 * - ASCII-safe markup for ProcessMaker iframe rendering
 * - One background init call loads KPIs and process list
 * By: Andres Garcia 
 **********************************/

error_reporting(0);
ini_set('display_errors', 0);

if (!isset($data) || !is_array($data)) $data = [];

$API_HOST      = $data['_env']['API_HOST'] ?? getenv('API_HOST');
$currentUserId = (int)($data['currentUserId'] ?? ($data['_request']['user_id'] ?? 0));
$backendUrl    = rtrim($API_HOST, '/') . '/pstools/script/cs-dashboard-my-inbox-sql';

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
  .card-draft{background:#eff6ff;border-color:#bfdbfe;}.card-draft.active{border-color:#3b82f6;background:#dbeafe;}.card-draft .kpi-right i{color:#2563eb;}
  .card-cancelled{background:#fff;border-color:#e5e7eb;}.card-cancelled.active{border-color:#6b7280;background:#f3f4f6;}.card-cancelled .kpi-right i{color:#4b5563;}
  .card-all{background:#fdfaef;border-color:#fceea6;}.card-all.active{border-color:#eab308;background:#fef08a;}.card-all .kpi-right i{color:#d97706;}
  .custom-badge{display:inline-flex;align-items:center;justify-content:center;padding:5px 12px;border-radius:6px;font-size:13px;font-weight:700;letter-spacing:.3px;white-space:nowrap;text-transform:capitalize;box-shadow:0 1px 2px rgba(0,0,0,0.05);}
  .badge-pending{background:#f59e0b;color:#fff;}.badge-completed{background:#49a24d;color:#fff;}.badge-cancelled{background:#cb4646;color:#fff;}.badge-returned{background:#facc15;color:#212529;}.badge-urgent{background:#dc2626;color:#fff;}.badge-draft{background:#3b82f6;color:#fff;}.badge-status-df{background:#6b7280;color:#fff;}
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
</style>
CSS;

$body = <<<'HTML'
<|div class="kpi-row">
  <|div class="kpi-card card-pending active" data-status="Pending">
    <|div class="kpi-left"><|div class="kpi-label">Pending<|/div><|div class="kpi-value" id="kpi-count-Pending">-<|/div><|/div>
    <|div class="kpi-right"><|i class="fas fa-tasks"><|/i><|/div>
  <|/div>
  <|div class="kpi-card card-draft" data-status="Draft">
    <|div class="kpi-left"><|div class="kpi-label">Drafts<|/div><|div class="kpi-value" id="kpi-count-Draft">-<|/div><|/div>
    <|div class="kpi-right"><|i class="fas fa-edit"><|/i><|/div>
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
    <|div class="col-md-3 mb-2"><|label for="task">Task<|/label><|input id="task" type="text" class="form-control" placeholder="task name..." /><|/div>
    <|div class="col-md-2 mb-2"><|label for="process">Process<|/label><|select id="process" class="form-control"><|option value="">Loading...<|/option><|/select><|/div>
    <|div class="col-md-2 mb-2">
      <|label for="case_status">Status<|/label>
      <|select id="case_status" class="form-control">
        <|option value="">All<|/option>
        <|option value="Pending" selected>Pending<|/option>
        <|option value="Draft">Draft<|/option>
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
        <|th>CASE #<|/th><|th>TITLE<|/th><|th>VIEW<|/th><|th>PROCESS<|/th><|th>TASK<|/th><|th>ASSIGNED TO<|/th><|th>STATUS<|/th><|th>CREATED<|/th>
      <|/tr>
    <|/thead>
    <|tbody><|/tbody>
  <|/table>
<|/div>

<|div id="csLoader">
  <|div class="cs-loader-box">
    <|div class="cs-spinner"><|/div>
    <|div class="cs-loader-title">Loading dashboard...<|/div>
    <|div class="cs-loader-text">Please wait while the My Inbox dashboard is being prepared.<|/div>
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
  var BACKEND="__BACKEND__", CURRENT_USER=__CURRENT_USER__, TAB="my_inbox";
  var loader=document.getElementById("csLoader"), dbg=document.getElementById("csDebug"), dtTable=null;
  var loaderTimeout=null, xlsxReady=false, xlsxLoading=false, xlsxQueue=[];

  function showLoader(){ $(loader).css("display","flex"); clearTimeout(loaderTimeout); loaderTimeout=setTimeout(hideLoader,20000); }
  function hideLoader(){ $(loader).hide(); clearTimeout(loaderTimeout); }
  function showDebug(t){ if(dbg){ dbg.style.display="block"; dbg.textContent=t; } }
  function clearDebug(){ if(dbg){ dbg.style.display="none"; dbg.textContent=""; } }
  function unwrap(r){ return (r&&r.output)?r.output:r; }
  function escHtml(s){ return String(s||"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"); }

  // Builds the shared payload used by table, KPI, and export requests.
  function buildBasePayload(){
    var p=$("#process").val()||""; if(p==="DIVIDER") p="";
    return {
      tab:TAB,currentUserId:CURRENT_USER,
      case_number:$("#case_number").val()||"",
      case_title:$("#case_title").val()||"",
      task:$("#task").val()||"",
      process:p,process_id:p,
      case_status:$("#case_status").val()||"",
      date_from:$("#date_from").val()||"",
      date_to:$("#date_to").val()||""
    };
  }

  function fmtDate(m){
    if(!m) return "";
    var p=String(m).split(" "); if(p.length<2) return m;
    var d=p[0].split("-"), t=p[1].split(":"); if(d.length!==3) return m;
    return d[2]+"/"+d[1]+"/"+d[0]+" "+(t[0]||"00")+":"+(t[1]||"00");
  }

  function applyKpis(k){ if(!k) return;
    $("#kpi-count-All").text(k.all!=null?k.all:0);
    $("#kpi-count-Pending").text(k.pending!=null?k.pending:0);
    $("#kpi-count-Draft").text(k.draft!=null?k.draft:0);
    $("#kpi-count-Cancelled").text(k.cancelled!=null?k.cancelled:0);
  }

  function setKpiPlaceholders(){ ["Pending","Draft","Cancelled","All"].forEach(function(id){ var el=document.getElementById("kpi-count-"+id); if(el) el.textContent="-"; }); }
  function syncKpiActiveCard(){ var s=$("#case_status").val()||""; $(".kpi-card").removeClass("active"); $('.kpi-card[data-status="'+s+'"]').addClass("active"); }

  // Loads the process dropdown from the backend init response.
  function fillProcessDropdown(list){
    var sel=document.getElementById("process"); if(!sel) return;
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
    var sel=document.getElementById("process"); if(!sel) return;
    sel.disabled=true; while(sel.options.length) sel.remove(0);
    var opt=document.createElement("option"); opt.value=""; opt.textContent="Loading..."; sel.appendChild(opt);
  }

  function setProcessFallbackState(){
    var sel=document.getElementById("process"); if(!sel) return;
    sel.disabled=false; while(sel.options.length) sel.remove(0);
    var opt=document.createElement("option"); opt.value=""; opt.textContent="All Processes"; sel.appendChild(opt);
  }

  // Background init call for process list and KPI counts.
  function loadInitOnly(){
    return $.ajax({
      url:BACKEND,type:"POST",contentType:"application/json",
      data:JSON.stringify({mode:"init",currentUserId:CURRENT_USER,tab:TAB}),
      timeout:20000
    }).done(function(resp){
      resp=unwrap(resp)||{};
      fillProcessDropdown(Array.isArray(resp.processes)?resp.processes:[]);
      applyKpis(resp.kpis||{});
    }).fail(function(){
      setProcessFallbackState();
    });
  }

  // Refreshes the KPI cards using the current filter values.
  function loadKpiCounts(){
    var payload=Object.assign({},buildBasePayload(),{mode:"kpi_counts"}); payload.case_status="";
    return $.ajax({
      url:BACKEND,type:"POST",contentType:"application/json",
      data:JSON.stringify(payload),timeout:20000,
      success:function(resp){ resp=unwrap(resp); applyKpis(resp&&resp.kpis?resp.kpis:resp); }
    });
  }

  function scheduleBackgroundLoads(){ setTimeout(function(){ loadInitOnly(); },80); }

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

  $(document).ready(function(){
    try{
      clearDebug(); showLoader(); setProcessLoadingState(); setKpiPlaceholders();

      dtTable=$("#casesTable").DataTable({
        processing:true,serverSide:true,searching:false,autoWidth:false,deferRender:true,
        pageLength:25,lengthMenu:[[25,50,100],[25,50,100]],order:[[7,"desc"]],
        dom:"<'row'<'col-sm-6'l><'col-sm-6'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",
        ajax:{
          url:BACKEND,type:"POST",contentType:"application/json",
          data:function(d){ return JSON.stringify(Object.assign({},buildBasePayload(),{draw:d.draw,start:d.start,length:d.length,order:d.order,columns:d.columns})); },
          dataSrc:function(json){ json=unwrap(json); if(!json){ hideLoader(); return []; } return Array.isArray(json.data)?json.data:[]; },
          error:function(xhr,st){ hideLoader(); if(st==="abort"||(xhr&&xhr.status===0)) return; showDebug("AJAX ERROR\nstatus="+(xhr?xhr.status:"n/a")); }
        },
        columns:[
          {data:"case_number",render:function(v,t,row){ if(t!=="display") return v; var u=row.open_url||""; return u?'<a href="'+u+'" target="_blank" style="font-weight:700;">'+String(v||"")+"</a>":String(v||""); }},
          {data:"case_title",render:function(v,t,row){ if(t!=="display") return v; var s=String(v||""); return escHtml(s.trim()||"Case #"+(row.case_number||row.request_id||"?")); }},
          {data:null,orderable:false,searchable:false,className:"text-center view-col-btn",render:function(_,t,row){
            if(t!=="display") return "";
            var pre=row.preview_url||"", rid=row.request_id||"";
            if(pre) return '<a href="#" class="btn btn-sm btn-outline-primary btn-view" data-kind="preview" data-url="'+String(pre).replace(/"/g,"&quot;")+'" data-title="Preview"><i class="fas fa-eye"></i> View</a>';
            if(rid) return '<a href="#" class="btn btn-sm btn-outline-secondary btn-view" data-kind="summary" data-request-id="'+String(rid).replace(/"/g,"&quot;")+'" data-title="'+String(row.case_title||"Request #"+rid).replace(/"/g,"&quot;")+'"><i class="fas fa-list"></i> Summary</a>';
            return '<span class="text-muted">-</span>';
          }},
          {data:"process_name",defaultContent:""},
          {data:"current_task",defaultContent:""},
          {data:"assigned_to",defaultContent:""},
          {data:"status_text",defaultContent:"",render:function(v,t){
            if(t!=="display") return v; if(!v) return "";
            var s=String(v),l=s.toLowerCase(),c="badge-status-df";
            if(l.includes("pending")||l.includes("progress")||l.includes("to do")){c="badge-pending";s="Pending";}
            else if(l.includes("draft")){c="badge-draft";s="Draft";}
            else if(l.includes("completed")||l.includes("concluded")){c="badge-completed";s="Completed";}
            else if(l.includes("error")){c="badge-urgent";s="Error";}
            else if(l.includes("cancel")){c="badge-cancelled";s="Cancelled";}
            else if(l.includes("return")){c="badge-returned";s="Returned";}
            return '<span class="custom-badge '+c+'">'+escHtml(s)+"</span>";
          }},
          {data:"created_at",defaultContent:"",render:function(v,t){ return t!=="display"?v:fmtDate(v); }}
        ],
        initComplete:function(){ hideLoader(); scheduleBackgroundLoads(); }
      });

      dtTable.on("preXhr.dt",function(){ showLoader(); });
      dtTable.on("xhr.dt",function(){ hideLoader(); });

      $(".kpi-card").on("click",function(){
        var st=$(this).attr("data-status")||"";
        $(".kpi-card").removeClass("active"); $(this).addClass("active");
        $("#case_status").val(st); dtTable.page("first").draw(false); loadKpiCounts();
      });

      $("#case_number,#case_title,#task").on("keydown",function(e){
        if(e.key==="Enter"){ e.preventDefault(); syncKpiActiveCard(); dtTable.page("first").draw(false); loadKpiCounts(); }
      });

      $("#btnSearch").on("click",function(){ syncKpiActiveCard(); dtTable.page("first").draw(false); loadKpiCounts(); });

      $("#btnClear").on("click",function(){
        $("#case_number,#case_title,#task,#date_from,#date_to").val("");
        $("#case_status").val("Pending");
        $(".kpi-card").removeClass("active");
        $(".kpi-card[data-status='Pending']").addClass("active");
        $("#process").val("");
        setKpiPlaceholders();
        dtTable.order([[7,"desc"]]); dtTable.page("first").draw(false); loadKpiCounts();
      });

      $("#btnRefresh").on("click",function(){ dtTable.page("first").draw(false); loadInitOnly(); });

      $("#btnExport").on("click",function(){
        ensureXlsx(function(){
          showLoader();
          $.ajax({
            url:BACKEND,method:"POST",contentType:"application/json",
            data:JSON.stringify(Object.assign({},buildBasePayload(),{draw:1,start:0,length:5000,order:dtTable.order(),columns:dtTable.settings().init().columns})),
            success:function(resp){
              hideLoader();
              resp=unwrap(resp);
              var rows=(resp&&Array.isArray(resp.data))?resp.data:[];
              try{
                var data=rows.map(function(r){ return{"Case Number":r.case_number||"","Case Title":r.case_title||"","Process":r.process_name||"","Task":r.current_task||"","Assigned To":r.assigned_to||"","Status":r.status_text||"","Created":r.created_at||""}; });
                var ws=XLSX.utils.json_to_sheet(data), wb=XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb,ws,"MyInbox");
                XLSX.writeFile(wb,"My_Inbox.xlsx");
              }catch(e){ showDebug("Export error:\n"+String(e)); }
            },
            error:function(xhr){ hideLoader(); showDebug("EXPORT ERROR\nstatus="+(xhr?xhr.status:"n/a")); }
          });
        });
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
