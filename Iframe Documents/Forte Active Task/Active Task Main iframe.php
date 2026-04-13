<?php

/**********************************
 * Active Tasks - Main Screen
 * by Elmer Orihuela
 *********************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;

$currentUser = empty($data["currentUserId"]) ? "''" : $data["currentUserId"];

$html = "";
$html .= "<head>";
$html .= "<link href=\"https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css\" rel=\"stylesheet\">";
$html .= "<link href=\"https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css\" rel=\"stylesheet\">";
$html .= "<link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css\" rel=\"stylesheet\">";
$html .= "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.css'>";
$html .= "<style>
.table-actions { text-align: right !important; vertical-align: middle !important; }
.btn-action { margin: 0 5px; min-width:34px; }
</style>";
$html .= "</head>";

$html .= "<body class='bodyStyle'>";
$html .= "<div id='alertMessage'></div>";

/* ---- MODAL: DETAIL (ojo) ---- */
$html .= <<<MODAL
<div class="modal fade" id="modalTaskDetail" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:.5rem;overflow:hidden;">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Request Summary</h5>
        <|button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        <|/button>        
      </div>
      <div class="modal-body" id="modalTaskDetailBody"></div>
      <div class="modal-footer border-top">
        <|button type="button" class="btn btn-secondary" data-dismiss="modal">Close
        <|/button>
      </div>
    </div>
  </div>
</div>
MODAL;

/* ---- MODAL: CANCEL (basurero) ---- */
$html .= <<<MODAL
<div class="modal fade" id="modalCancelRequest" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius:.5rem;">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Caution!</h5>
        <|button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        <|/button>
      </div>
      <div class="modal-body">
        Are you sure you want to cancel this request?
      </div>
      <div class="modal-footer">
        <|button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel
        <|/button>
        <|button type="button" class="btn btn-danger" id="confirmCancelBtn">Confirm
        <|/button>
      </div>
    </div>
  </div>
</div>
MODAL;

/* ---- DataTable ---- */
$html .= "<div class='dataTables_wrapper'>";
$html .= "<div class='row'><div class='col-md-12'>";
$html .= "<table id='tableActiveTasks' width='100%' class='table table-striped table-bordered'>";
$html .= "<thead><tr>";
$html .= "<th>Request #</th>";
$html .= "<th>Quoted #</th>";
$html .= "<th>Client's name</th>";
$html .= "<th>Vessel Name</th>";
$html .= "<th>Status</th>";
$html .= "<th>Task</th>";
$html .= "<th>Assignee</th>";
$html .= "<th>Requestor's name</th>";
$html .= "<th class='table-actions'></th>"; // acciones a la derecha
$html .= "</tr></thead>";
$html .= "</table>";
$html .= "</div></div></div>";
$html .= "</body>";

/* ---- SCRIPTS ---- */
$html .= <<<SCRIPT
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-toast-plugin/1.3.2/jquery.toast.min.js"></script>

<script type='text/javascript'>
var cancelRequestId = null;

function formatCurrency(value) {
  const num = Number(value);
  return isNaN(num) ? '' : new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(num);
}

// Acciones ojo/basurero
function renderActions(data, type, row) {
  const summaryDataEncoded = btoa(unescape(encodeURIComponent(JSON.stringify(row.summaryData || {}))));
  const taskId = row.RESP_YQP_TASK_ID;
  const requestId = row.id;
  return `
    <|button type="button" class="btn btn-outline-primary btn-action btn-show-summary" data-summary="\${summaryDataEncoded}" title="Summary">
      <i class="fas fa-eye"></i>
    </|button>
    <|button type="button" class="btn btn-outline-success btn-action btn-play-task" data-task-id="\${taskId}"  title="Task">
      <i class="fas fa-play"></i>
    </|button>
    <|button type="button" class="btn btn-outline-danger btn-action btn-cancel-request" data-request-id="\${requestId}" title="Cancel">
      <i class="fas fa-trash"></i>    
    </|button>
  `;
}

// DataTable y eventos
$(document).ready(function() {
  var table = $('#tableActiveTasks').DataTable({
    serverSide: true,
    ajax: {
      url: '{$apiHost}/pstools/script/active-tasks-get-data',
      type: 'POST',
      data: function(d) {
        d.CURRENT_USER = $currentUser;
        d.SEARCH = (d.search && d.search.value) ? d.search.value.trim() : '';
        if (d.order && d.order.length) {
          const colIdx = d.order[0].column;
          d.ORDER_BY = d.columns[colIdx].data; // p.ej. "YQP_CLIENT_NAME"
          d.ORDER_DIR = d.order[0].dir;        // "asc" | "desc"
        }
        return d;
      }
    },
    columns: [
      { data: 'id' },
      { data: 'YQP_QUOTE_NUMBER' },
      { data: 'YQP_CLIENT_NAME' },
      { data: 'YQP_INTEREST_ASSURED' },
      { data: 'YQP_STATUS' },
      { data: 'task' },
      { data: 'YQP_USER_FULLNAME' },
      { data: 'YQP_REQUESTOR_NAME' },
      { data: null, orderable: false, className: 'table-actions', render: renderActions }
    ],
    drawCallback: function() {
      // Enlaza eventos a botones
      $('.btn-show-summary').off('click').on('click', function() {
        var encoded = $(this).data('summary');
        showTaskDetailModal(encoded);
      });
      $('.btn-cancel-request').off('click').on('click', function() {
        var requestId = $(this).data('request-id');
        showCancelModal(requestId);
      });
      $('.btn-play-task').off('click').on('click', function() {
        var taskId = $(this).data('task-id');
        if (taskId) {
        window.parent.location.href = '/tasks/' + taskId + '/edit';
        }
      });
    }
  });
  $('#tableActiveTasks').on('preXhr.dt', function (e, settings, data) {
    console.log('Payload DataTables → servidor:', data);
  });
  // Confirmar cancelación
  $('#confirmCancelBtn').on('click', function() {
    var aToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI2IiwianRpIjoiOWUyY2EzMDk4ZTllZjdkODYwYmIzOGI3MjY2NzcyNWQyOTZlNWEzZmRhZDBmNmUyMTFjNjkwNGE5ZTI0NWZiNjljYWE4Y2U4YTIyMjM0ZTEiLCJpYXQiOjE3NTUwMjA5NDIuNzIwMDc0LCJuYmYiOjE3NTUwMjA5NDIuNzIwMDc4LCJleHAiOjE3ODY1NTY5NDIuNzA3ODkxLCJzdWIiOiIyIiwic2NvcGVzIjpbXX0.ZzDMvJEGBnO7Jj5pBwK9nPwKxT6OqROCxAuM3XJc_IjzHy7hOR0LgPWyBZ7oBdf2S6jAFAwnBIfAHcaepr-3rvfs9eH4ib9NlANbF92syZtbco1C1xhbLtAUytynQfJRPE1Y33o7a168wvSXzUBcsUhQQcvLOa7iavdAgKxuAu5W5upTMinEs6iJmy9nr47ejK1_cXaOtPCBpJsFTEc3rAXGEtUJB94rjWmZei9dgKk-Bn19qIsRSIhe1FnfnAaoOhM84ewcSoukZsp307ZtbESYBde2itLBbIx5v0vIlRsLUeIjKWUXpwjRQeSoPWHH-0ESBBZVjlciDXi-KcnENEdnvhcpw4OoOGJDZSmEgts-yL5qxlIrTg0cofSS9_F6mbPjgeTS4597pAVELz0oIfbd8A8S0Re-2DUgVasO5P8E7_5iP6_ntDtvockgFW5JCiupXJzFBnBBOg9BP2Kyln_tnVvIY1BGl3_AWYICs8IQk5p27fGZRG4JkKHR_WZfpGRzsDqno_Bka3izGktHtmU8HwTmm8WPzWkWuEaSFuctlCmTZ85_VkdVtG4Vz009Emp8rwE6Gyf4IXg7eWZXhJzMi0eG75PEDw4yjIdevvMGkF2ssSc3kSc_B6DUTk8A3q9c_T89AXN3PRstl5dHsREAOX35wqUrO-fKkrn9xvk';
    if (!cancelRequestId) return;
    fetch('{$apiHost}/requests/' + cancelRequestId, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + aToken },
      body: JSON.stringify({status: 'CANCELED'})
    })
    .then(response => {     
      if (!response.ok) throw new Error('Cancel failed');
      $('#modalCancelRequest').modal('hide');
      $('#tableActiveTasks').DataTable().ajax.reload(null, false);
      cancelRequestId = null;
      $.toast({ heading: 'Request cancelled', icon: 'success', position: 'top-right', hideAfter: 2000 });
    })
    .catch(() => {
      alert('Error canceling request');
    });
  });
});

// Mostrar modal detalle
function showTaskDetailModal(summaryDataEncoded) {
  var summaryData = {};
  try { summaryData = JSON.parse(decodeURIComponent(escape(atob(summaryDataEncoded)))); } catch(e){}
  var modalHtml = `
    <div class='container-fluid'>
      <div class='row'>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Quote Number</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_QUOTE_NUMBER || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Client Name</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_CLIENT_NAME || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Status</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_STATUS || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Vessel Name</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_INTEREST_ASSURED || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Country of Business</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_COUNTRY_BUSINESS || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Language</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_LANGUAGE || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Product</small>
          <p class='font-weight-bold mb-0'>\${summaryData.YQP_PRODUCT || ''}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Sum Insured Vessel</small>
          <p class='font-weight-bold mb-0'>\${formatCurrency(summaryData.YQP_SUM_INSURED_VESSEL)}</p>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Bound Status</small>
          <span class='badge badge-\${summaryData.BOUND_STATUS === "Already quoted this year" ? "success" : "danger"}'>
            \${summaryData.BOUND_STATUS || ''}
          </span>
        </div>
        <div class='col-sm-6 mb-3'>
          <small class='text-uppercase text-muted'>Renewal Status</small>
          <span class='badge badge-\${
            summaryData.RENEWAL_STATUS === "Within coverage period"   ? "success" :
            summaryData.RENEWAL_STATUS === "Eligible for renewal"     ? "info"    :
                                                                       "warning"
          }'>
            \${summaryData.RENEWAL_STATUS || ''}
          </span>
        </div>
      </div>
    </div>
  `;
  $('#modalTaskDetailBody').html(modalHtml);
  $('#modalTaskDetail').modal({backdrop: 'static', keyboard: false});
  $('#modalTaskDetail').modal('show');
}

// Mostrar modal cancelar
function showCancelModal(requestId) {
  cancelRequestId = requestId;
  $('#modalCancelRequest').modal({backdrop:'static', keyboard:false});
  $('#modalCancelRequest').modal('show');
}
</script>
SCRIPT;

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];
