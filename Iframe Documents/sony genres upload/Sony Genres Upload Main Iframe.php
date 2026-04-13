<?php
/**
 * Sony Genres Upload — main iframe (collection_23)
 *
 * Flow:
 *  1) Collection tab: rows { name, value, row_id, content }.
 *  2) Excel import: flexible headers — Country Local Genre Key vs "value", Local Genre Name vs name/content,
 *     Is Active Local Genre (info), optional Row Id vs collection row_id.
 *  3) Comparison: missing on either side, mismatches on value / row_id.
 *  4) Backup: PSTools export before apply.
 *  5) Strategy (implement in separate PSTools): replace all, adjust existing, or mix.
 *
 * Register PSTools slugs to match $pstoolsPaths. API_HOST must already include /api/1.0 (no duplicate in paths).
 */

$collectionId = 23;
$requestId = isset($data['request_id']) ? (int) $data['request_id'] : (int) ($data['requestId'] ?? 1);
$apiHost = rtrim(getenv('API_HOST') ?: '', '/');

$pstoolsPaths = [
    'get_collection' => '/pstools/script/sony-genres-get-collection-data',
    'export_backup' => '/pstools/script/sony-genres-export-backup',
];

$requestDataJson = json_encode($data ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$html = '';
$html .= '<head>';
$html .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">';
$html .= '<link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css" rel="stylesheet">';
$html .= '<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">';
$html .= '<style>
.genre-tabs .nav-link { font-weight: 500; }
.badge-soft { font-size: 0.75rem; }
#requestDataMonitor { max-height: 220px; overflow: auto; font-size: 0.8rem; background: #f8f9fa; border-radius: 6px; padding: 10px; }
.compare-table td { vertical-align: middle; font-size: 0.85rem; }
.step-num { display: inline-flex; width: 28px; height: 28px; border-radius: 50%; background: #007bff; color: #fff; align-items: center; justify-content: center; font-size: 0.85rem; margin-right: 8px; }
</style>';
$html .= '</head>';

$html .= '<body class="p-3">';
$html .= '<div class="container-fluid">';

$html .= '<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">';
$html .= '<h4 class="mb-0"><i class="fas fa-music text-primary"></i> Genres — Collection ' . (int) $collectionId . '</h4>';
$html .= '<span class="text-muted small">Request ID: <strong>' . ($requestId ?: '—') . '</strong></span>';
$html .= '</div>';

$html .= '<div class="card mb-3">';
$html .= '<div class="card-header py-2 d-flex justify-content-between align-items-center" data-toggle="collapse" data-target="#requestDataMonitorWrap" style="cursor:pointer">';
$html .= '<span><i class="fas fa-database mr-2"></i> Request data (<code>\$data</code>) — monitor</span>';
$html .= '<i class="fas fa-chevron-down"></i>';
$html .= '</div>';
$html .= '<div id="requestDataMonitorWrap" class="collapse show">';
$html .= '<div class="card-body p-2"><pre id="requestDataMonitor" class="mb-0"></pre></div>';
$html .= '</div></div>';

$html .= '<ul class="nav nav-tabs genre-tabs mb-3" role="tablist">';
$html .= '<li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-collection" role="tab">Collection</a></li>';
$html .= '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-import" role="tab">Import Excel</a></li>';
$html .= '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-compare" role="tab">Comparison</a></li>';
$html .= '<li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-apply" role="tab">Backup &amp; apply</a></li>';
$html .= '</ul>';

$html .= '<div class="tab-content">';

/* Tab: collection */
$html .= '<div class="tab-pane fade show active" id="tab-collection" role="tabpanel">';
$html .= '<p class="text-muted small">Current collection records (visible fields: name, value, row_id, content).</p>';
$html .= '<table id="tableGenres" class="table table-sm table-striped table-bordered" style="width:100%"><thead><tr>';
$html .= '<th>Record ID</th><th>name</th><th>value</th><th>row_id</th><th>content</th>';
$html .= '</tr></thead><tbody></tbody></table>';
$html .= '</div>';

/* Tab: import */
$html .= '<div class="tab-pane fade" id="tab-import" role="tabpanel">';
$html .= '<div class="row">';
$html .= '<div class="col-md-8">';
$html .= '<p class="text-muted small">Upload a .xlsx with columns: <strong>Country Local Genre Key</strong>, <strong>Local Genre Name</strong>, <strong>Is Active Local Genre</strong>. Optional: <strong>Row Id</strong> to match collection <code>row_id</code>.</p>';
$html .= '<div class="custom-file mb-2">';
$html .= '<|input type="file" class="custom-file-input" id="excelFile" accept=".xlsx,.xls">';
$html .= '<|label class="custom-file-label" for="excelFile">Choose Excel file…</|label>';
$html .= '</div>';
$html .= '<|button type="button" class="btn btn-primary" id="btnParseExcel"><i class="fas fa-file-excel"></i> Parse and prepare comparison</|button>';
$html .= '</div></div>';
$html .= '<div id="excelPreview" class="mt-3 small text-muted"></div>';
$html .= '</div>';

/* Tab: comparison */
$html .= '<div class="tab-pane fade" id="tab-compare" role="tabpanel">';
$html .= '<p class="text-muted small">Results: Excel rows missing in the collection (by key), collection-only rows, and matches with differences in <code>value</code> or <code>row_id</code>.</p>';
$html .= '<div id="compareSummary" class="mb-3"></div>';
$html .= '<div class="table-responsive"><table class="table table-sm compare-table table-bordered" id="compareTable"><thead><tr>';
$html .= '<th>Status</th><th>Key (Excel / value)</th><th>Excel name</th><th>In collection (value / row_id / name)</th>';
$html .= '</tr></thead><tbody></tbody></table></div>';
$html .= '</div>';

/* Tab: backup and apply */
$html .= '<div class="tab-pane fade" id="tab-apply" role="tabpanel">';
$html .= '<div class="mb-4">';
$html .= '<span class="step-num">1</span><strong>Backup</strong> — official collection export and attach to the request (if the API returns a download URL).';
$html .= '<div class="mt-2 ml-4">';
$html .= '<|button type="button" class="btn btn-warning btn-sm" id="btnBackup"><i class="fas fa-cloud-download-alt"></i> Run export / backup</|button>';
$html .= '<span id="backupStatus" class="ml-2 small"></span>';
$html .= '</div></div>';

$html .= '<div class="mb-4">';
$html .= '<span class="step-num">2</span><strong>Strategy</strong> (after validating on the Comparison tab)';
$html .= '<div class="ml-4 mt-2">';
$html .= '<div class="custom-control custom-radio mb-1">';
$html .= '<|input class="custom-control-input" type="radio" name="strategy" id="strReplace" value="replace">';
$html .= '<|label class="custom-control-label" for="strReplace">Replace all genres (truncate + import / recreate)</|label>';
$html .= '</div>';
$html .= '<div class="custom-control custom-radio mb-1">';
$html .= '<|input class="custom-control-input" type="radio" name="strategy" id="strAdjust" value="adjust" checked>';
$html .= '<|label class="custom-control-label" for="strAdjust">Keep existing rows and adjust only detected differences</|label>';
$html .= '</div>';
$html .= '<div class="custom-control custom-radio mb-1">';
$html .= '<|input class="custom-control-input" type="radio" name="strategy" id="strMix" value="mix">';
$html .= '<|label class="custom-control-label" for="strMix">Mix: add new rows + update existing + do not delete unlisted rows</|label>';
$html .= '</div>';
$html .= '<p class="text-muted small mt-2">Apply changes in a separate PSTools script using the collections API (POST/PUT/PATCH records, import, truncate per policy). This iframe prepares comparison and backup for that step.</p>';
$html .= '</div></div>';

$html .= '<div class="mb-2">';
$html .= '<span class="step-num">3</span><strong>Continue workflow</strong>';
$html .= '<div class="ml-4 mt-2">';
$html .= '<|button type="button" class="btn btn-success" id="btnApproveContinue"><i class="fas fa-check"></i> Approve and continue (wire to your apply PSTools)</|button>';
$html .= '</div></div>';
$html .= '</div>';

$html .= '</div>'; // end tab-content
$html .= '</div>'; // end container
$html .= '</body>';

$apiBaseJs = addslashes($apiHost);
$getPathJs = addslashes($pstoolsPaths['get_collection']);
$exportPathJs = addslashes($pstoolsPaths['export_backup']);

$html .= <<<SCRIPT
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

<script>
(function() {
  var CONFIG = {
    apiHost: "{$apiBaseJs}",
    requestId: {$requestId},
    collectionId: {$collectionId},
    pstools: {
      getCollection: "{$apiBaseJs}{$getPathJs}",
      exportBackup: "{$apiBaseJs}{$exportPathJs}"
    },
    requestData: {$requestDataJson}
  };

  var bearerToken = ''; // Optional: set JWT if PSTools require Authorization from the browser (see Upload Documents Script)

  function authHeaders() {
    var h = { 'Content-Type': 'application/json' };
    if (bearerToken) h['Authorization'] = 'Bearer ' + bearerToken;
    return h;
  }

  $('#requestDataMonitor').text(JSON.stringify(CONFIG.requestData, null, 2));

  var collectionRows = [];
  var excelRows = [];
  var lastCompare = null;

  function normalizeHeader(s) {
    return String(s || '').trim().toLowerCase().replace(/\\s+/g, '_');
  }

  function mapExcelRow(rowObj, headers) {
    var map = {};
    headers.forEach(function(h, i) {
      map[normalizeHeader(h)] = rowObj[h] !== undefined ? rowObj[h] : Object.values(rowObj)[i];
    });
    var key = map['country_local_genre_key'] || map['local_genre_key'] || map['genre_key'] || map['value'] || '';
    var name = map['local_genre_name'] || map['name'] || map['content'] || '';
    var active = map['is_active_local_genre'] ?? map['is_active'] ?? '';
    var rowId = map['row_id'] || map['rowid'] || '';
    return {
      key: String(key).trim(),
      name: String(name).trim(),
      active: active,
      row_id: String(rowId).trim()
    };
  }

  function loadCollection(cb) {
    $.ajax({
      url: CONFIG.pstools.getCollection,
      type: 'POST',
      headers: authHeaders(),
      data: JSON.stringify({}),
      success: function(res) {
        if (res.success === false) {
          alert('Failed to load collection: ' + (res.error || 'unknown'));
          return;
        }
        collectionRows = res.data || [];
        if (cb) cb();
      },
      error: function(xhr) {
        alert('HTTP error loading collection: ' + xhr.status);
      }
    });
  }

  var table = $('#tableGenres').DataTable({
    data: [],
    columns: [
      { data: 'record_id' },
      { data: 'name' },
      { data: 'value' },
      { data: 'row_id' },
      { data: 'content' }
    ],
    deferRender: true,
    pageLength: 25
  });

  loadCollection(function() {
    table.clear();
    table.rows.add(collectionRows);
    table.draw();
  });

  $('#excelFile').on('change', function() {
    var fn = this.files[0] ? this.files[0].name : 'Choose Excel file…';
    $(this).next('.custom-file-label').html(fn);
  });

  $('#btnParseExcel').on('click', function() {
    var f = document.getElementById('excelFile').files[0];
    if (!f) { alert('Please select an Excel file'); return; }
    var reader = new FileReader();
    reader.onload = function(e) {
      var data = new Uint8Array(e.target.result);
      var wb = XLSX.read(data, { type: 'array' });
      var sheet = wb.Sheets[wb.SheetNames[0]];
      var json = XLSX.utils.sheet_to_json(sheet, { defval: '' });
      if (!json.length) {
        $('#excelPreview').text('Sheet is empty or has no data.');
        excelRows = [];
        return;
      }
      var headers = Object.keys(json[0]);
      excelRows = json.map(function(row) { return mapExcelRow(row, headers); })
        .filter(function(r) { return r.key || r.name; });
      $('#excelPreview').html('Rows read: <strong>' + excelRows.length + '</strong>. Columns detected: ' + headers.map(function(h) { return '<code>' + h + '</code>'; }).join(', '));
      runCompare();
      $('a[href="#tab-compare"]').tab('show');
    };
    reader.readAsArrayBuffer(f);
  });

  function indexByValue(rows) {
    var ix = {};
    rows.forEach(function(r) {
      var k = String(r.value || '').trim();
      if (k) ix[k] = r;
    });
    return ix;
  }

  function runCompare() {
    if (!collectionRows.length) {
      loadCollection(function() { runCompare(); });
      return;
    }
    var byVal = indexByValue(collectionRows);
    var tbody = $('#compareTable tbody');
    tbody.empty();
    var onlyExcel = [], onlyColl = [], mismatch = [], ok = [];

    var excelKeys = {};
    excelRows.forEach(function(er) {
      if (!er.key) return;
      excelKeys[er.key] = true;
      var cr = byVal[er.key];
      if (!cr) {
        onlyExcel.push({ excel: er, coll: null });
        return;
      }
      var vMatch = String(cr.value || '').trim() === er.key;
      var ridMatch = !er.row_id || String(cr.row_id || '').trim() === er.row_id;
      var nameLoose = !er.name || String(cr.name || cr.content || '').toLowerCase() === er.name.toLowerCase();
      if (vMatch && ridMatch && nameLoose) ok.push({ excel: er, coll: cr });
      else mismatch.push({ excel: er, coll: cr, issues: {
        value: vMatch,
        row_id: ridMatch,
        name: nameLoose
      }});
    });

    collectionRows.forEach(function(cr) {
      var k = String(cr.value || '').trim();
      if (k && !excelKeys[k]) onlyColl.push(cr);
    });

    lastCompare = { onlyExcel: onlyExcel, onlyColl: onlyColl, mismatch: mismatch, ok: ok };

    function addRow(status, badgeClass, excel, coll, extra) {
      var chtml = coll
        ? ('value: <code>' + (coll.value || '') + '</code> · row_id: <code>' + (coll.row_id || '') + '</code> · name: ' + (coll.name || coll.content || ''))
        : '—';
      var ehtml = excel
        ? ('<code>' + excel.key + '</code> · ' + (excel.name || '') + (excel.row_id ? ' · row_id: ' + excel.row_id : ''))
        : '—';
      tbody.append(
        '<tr><td><span class="badge badge-' + badgeClass + '">' + status + '</span>' + (extra || '') + '</td>' +
        '<td>' + (excel ? '<code>' + excel.key + '</code>' : '—') + '</td>' +
        '<td>' + (excel ? excel.name : '—') + '</td>' +
        '<td>' + chtml + '</td></tr>'
      );
    }

    onlyExcel.forEach(function(x) { addRow('Excel only', 'warning', x.excel, null); });
    onlyColl.forEach(function(cr) { addRow('Collection only', 'secondary', null, cr); });
    mismatch.forEach(function(x) {
      var issues = [];
      if (!x.issues.row_id) issues.push('row_id');
      if (!x.issues.name) issues.push('name/content');
      addRow('Mismatch', 'danger', x.excel, x.coll, ' <small class="text-muted">(' + issues.join(', ') + ')</small>');
    });
    ok.slice(0, 50).forEach(function(x) { addRow('OK', 'success', x.excel, x.coll); });
    if (ok.length > 50) {
      tbody.append('<tr><td colspan="4" class="text-muted">… and ' + (ok.length - 50) + ' more OK rows omitted from the table</td></tr>');
    }

    $('#compareSummary').html(
      '<div class="row text-center">' +
      '<div class="col"><div class="border rounded p-2"><div class="h4 mb-0">' + onlyExcel.length + '</div><small>Excel only</small></div></div>' +
      '<div class="col"><div class="border rounded p-2"><div class="h4 mb-0">' + onlyColl.length + '</div><small>Collection only</small></div></div>' +
      '<div class="col"><div class="border rounded p-2"><div class="h4 mb-0 text-danger">' + mismatch.length + '</div><small>Mismatches</small></div></div>' +
      '<div class="col"><div class="border rounded p-2"><div class="h4 mb-0 text-success">' + ok.length + '</div><small>Matches</small></div></div>' +
      '</div>'
    );

    var allowContinue = (mismatch.length === 0) || $('#strReplace').is(':checked');
    $('#btnApproveContinue').prop('disabled', !allowContinue);
  }

  $('#btnApproveContinue').prop('disabled', true);

  $('input[name="strategy"]').on('change', function() {
    if (lastCompare) runCompare();
  });

  $('#btnBackup').on('click', function() {
    $('#backupStatus').text('Requesting export…');
    $.ajax({
      url: CONFIG.pstools.exportBackup,
      type: 'POST',
      headers: authHeaders(),
      data: JSON.stringify({
        request_id: CONFIG.requestId,
        attach_to_request: CONFIG.requestId > 0,
        filename: 'genres_backup_c' + CONFIG.collectionId + '.xlsx'
      }),
      success: function(res) {
        var msg = res.success ? 'Export triggered.' : 'Check API response.';
        if (res.download_hint) msg += ' URL: ' + res.download_hint;
        if (res.manual_download_pattern) msg += ' | Manual pattern: ' + res.manual_download_pattern;
        if (res.attached_file_id) msg += ' | Request file id: ' + res.attached_file_id;
        $('#backupStatus').html('<span class="text-success">' + msg + '</span>');
      },
      error: function(xhr) {
        $('#backupStatus').html('<span class="text-danger">Error ' + xhr.status + '</span>');
      }
    });
  });

  $('#btnApproveContinue').on('click', function() {
    var strat = $('input[name="strategy"]:checked').val();
    alert('Wire your apply PSTools here. Strategy: ' + strat + '. Request: ' + CONFIG.requestId);
  });

  $('a[data-toggle="tab"][href="#tab-compare"]').on('shown.bs.tab', function() {
    if (excelRows.length && collectionRows.length) runCompare();
  });
})();
</script>
SCRIPT;

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html),
];
