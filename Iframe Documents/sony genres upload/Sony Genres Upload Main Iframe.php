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
$defaultRequestId = 90235;
$requestId = isset($data['request_id']) ? (int) $data['request_id'] : (int) ($data['requestId'] ?? $defaultRequestId);
$apiHost = rtrim(getenv('API_HOST') ?: '', '/');

$pstoolsPaths = [
  'get_collection' => '/pstools/script/sony-genres-get-collection-data',
  'export_backup' => '/pstools/script/sony-genres-export-backup',
  'save_backup' => '/pstools/script/sony-genres-save-backup-to-request',
  'upload_file' => '/pstools/script/sony-genres-upload-file-to-request',
  'apply_collection' => '/pstools/script/sony-genres-apply-collection',
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
.dash-card { border-radius: 12px; border: none; color: #fff; padding: 1rem 1.1rem; box-shadow: 0 4px 14px rgba(0,0,0,.12); min-height: 92px; }
.dash-card .dash-num { font-size: 1.75rem; font-weight: 700; line-height: 1.2; }
.dash-card .dash-lbl { font-size: 0.8rem; opacity: 0.95; font-weight: 500; }
.dash-card.dash-new { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.dash-card.dash-update { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #333; }
.dash-card.dash-coll { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.dash-card.dash-ok { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
#compareFilterBar .btn { border-radius: 20px; margin-right: 6px; margin-bottom: 6px; }
#compareFilterBar .btn.active { box-shadow: inset 0 0 0 2px rgba(0,0,0,.2); font-weight: 600; }
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
$html .= '<p class="text-muted small">Columns: <strong>Country</strong>, <strong>Local Genre Key</strong> (maps to collection <code>value</code>), <strong>Local Genre Name</strong>, <strong>Is Active</strong>, optional <strong>Global Genre</strong>, optional <strong>Row Id</strong> (matches <code>row_id</code>).</p>';
$html .= '<div class="custom-file mb-2">';
$html .= '<|input type="file" class="custom-file-input" id="excelFile" accept=".xlsx,.xls">';
$html .= '<|label class="custom-file-label" for="excelFile">Choose Excel file…</|label>';
$html .= '</div>';
$html .= '<div class="btn-group flex-wrap mb-2" role="group">';
$html .= '<|button type="button" class="btn btn-primary" id="btnParseExcel"><i class="fas fa-file-excel"></i> Parse &amp; compare</|button>';
$html .= '<|button type="button" class="btn btn-outline-secondary" id="btnUploadExcelToRequest"><i class="fas fa-cloud-upload-alt"></i> Upload file to request</|button>';
$html .= '</div>';
$html .= '<p id="uploadFileStatus" class="small text-muted mb-0"></p>';
$html .= '</div></div>';
$html .= '<div id="excelPreview" class="mt-3 small text-muted"></div>';
$html .= '</div>';

/* Tab: comparison */
$html .= '<div class="tab-pane fade" id="tab-compare" role="tabpanel">';
$html .= '<p class="text-muted small">Matching: first by Excel <strong>Local Genre Key</strong> = collection <code>value</code>; if no hit, by normalized <strong>name</strong> vs collection <code>content</code>/<code>name</code> (e.g. &quot;Latin&quot; ↔ <code>la6131111</code>).</p>';
$html .= '<div id="compareSummary" class="mb-3"></div>';
$html .= '<div id="compareFilterBar" class="mb-2">';
$html .= '<span class="text-muted small mr-2">Filter:</span>';
$html .= '<|button type="button" class="btn btn-sm btn-outline-secondary active" data-cmp-filter="ALL">All</|button>';
$html .= '<|button type="button" class="btn btn-sm btn-outline-danger" data-cmp-filter="NEW">New</|button>';
$html .= '<|button type="button" class="btn btn-sm btn-outline-warning" data-cmp-filter="UPDATE">To update</|button>';
$html .= '<|button type="button" class="btn btn-sm btn-outline-success" data-cmp-filter="OK">Unchanged</|button>';
$html .= '<|button type="button" class="btn btn-sm btn-outline-primary" data-cmp-filter="COLL_ONLY">Collection only</|button>';
$html .= '</div>';
$html .= '<div class="table-responsive"><table class="table table-sm compare-table table-striped table-bordered nowrap" id="compareTable" style="width:100%"><thead><tr>';
$html .= '<th>Status</th><th>Match</th><th>Excel key</th><th>Excel</th><th>Collection</th>';
$html .= '</tr></thead><tbody></tbody></table></div>';
$html .= '</div>';

/* Tab: backup and apply */
$html .= '<div class="tab-pane fade" id="tab-apply" role="tabpanel">';
$html .= '<div class="mb-4">';
$html .= '<span class="step-num">1</span><strong>Backup</strong> — save current collection JSON into request variable <code>collection_backup</code>, then optional PM export.';
$html .= '<div class="mt-2 ml-4">';
$html .= '<|button type="button" class="btn btn-primary btn-sm" id="btnSaveJsonBackup"><i class="fas fa-save"></i> Save JSON backup to request</|button>';
$html .= '<span id="jsonBackupStatus" class="ml-2 small"></span>';
$html .= '</div>';
$html .= '<div class="mt-2 ml-4">';
$html .= '<|button type="button" class="btn btn-warning btn-sm" id="btnBackup"><i class="fas fa-cloud-download-alt"></i> Run collection export (XLSX job)</|button>';
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
$html .= '<|button type="button" class="btn btn-success" id="btnApproveContinue"><i class="fas fa-check"></i> Approve and apply</|button>';
$html .= '</div></div>';
$html .= '</div>';

$html .= '</div>'; // end tab-content
$html .= '</div>'; // end container
$html .= '</body>';

$apiBaseJs = addslashes($apiHost);
$getPathJs = addslashes($pstoolsPaths['get_collection']);
$exportPathJs = addslashes($pstoolsPaths['export_backup']);
$saveBackupPathJs = addslashes($pstoolsPaths['save_backup']);
$uploadFilePathJs = addslashes($pstoolsPaths['upload_file']);
$applyPathJs = addslashes($pstoolsPaths['apply_collection']);

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
      exportBackup: "{$apiBaseJs}{$exportPathJs}",
      saveBackup: "{$apiBaseJs}{$saveBackupPathJs}",
      uploadFile: "{$apiBaseJs}{$uploadFilePathJs}",
      applyCollection: "{$apiBaseJs}{$applyPathJs}"
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
  var compareDt = null;
  var compareFilter = 'ALL';

  function normalizeHeader(s) {
    return String(s || '').trim().toLowerCase().replace(/\\s+/g, '_');
  }

  function mapExcelRow(rowObj, headers) {
    var map = {};
    headers.forEach(function(h, i) {
      map[normalizeHeader(h)] = rowObj[h] !== undefined ? rowObj[h] : Object.values(rowObj)[i];
    });
    var key = map['local_genre_key'] || map['country_local_genre_key'] || map['genre_key'] || map['value'] || '';
    var name = map['local_genre_name'] || map['name'] || map['content'] || '';
    var active = map['is_active'] ?? map['is_active_local_genre'] ?? '';
    var rowId = map['row_id'] || map['rowid'] || '';
    var globalGenre = map['global_genre'] || map['global'] || '';
    return {
      key: String(key).trim(),
      name: String(name).trim(),
      active: active,
      row_id: String(rowId).trim(),
      global_genre: String(globalGenre).trim()
    };
  }

  function parseActiveJs(v) {
    if (v === true || v === false) return v;
    var s = String(v === undefined || v === null ? '' : v).trim().toLowerCase();
    return s === 'true' || s === '1' || s === 'yes' || s === 'si' || s === 'sí';
  }

  function collActive(cr) {
    var r = cr.raw || {};
    if (r.is_active !== undefined) return parseActiveJs(r.is_active);
    return true;
  }

  function collGlobal(cr) {
    var r = cr.raw || {};
    return String(r.global_genre || '').trim();
  }

  /** Lowercase, trim, collapse spaces, strip accents (Latin ↔ latin) */
  function normLabel(s) {
    var t = String(s === undefined || s === null ? '' : s).trim().toLowerCase();
    try {
      if (t.normalize) t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    } catch (e) {}
    t = t.replace(/\\s+/g, ' ');
    return t;
  }

  function collLabelsForIndex(cr) {
    var raw = cr.raw || {};
    var out = {};
    function add(s) {
      var n = normLabel(s);
      if (n) out[n] = true;
    }
    add(cr.content);
    add(cr.name);
    add(raw.content);
    add(raw.name);
    return Object.keys(out);
  }

  function indexByValue(rows) {
    var ix = {};
    rows.forEach(function(r) {
      var k = String(r.value || '').trim();
      if (k) ix[k] = r;
    });
    return ix;
  }

  function buildLabelIndex(collectionRows) {
    var labelIndex = {};
    collectionRows.forEach(function(cr) {
      collLabelsForIndex(cr).forEach(function(nl) {
        if (!labelIndex[nl]) labelIndex[nl] = [];
        labelIndex[nl].push(cr);
      });
    });
    return labelIndex;
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

  function fmtExcel(ex) {
    if (!ex) return '—';
    var g = ex.global_genre ? ' · global: ' + ex.global_genre : '';
    return 'name: ' + (ex.name || '') + ' · active: ' + parseActiveJs(ex.active) + g + (ex.row_id ? ' · row_id: ' + ex.row_id : '');
  }

  function fmtCollPlain(coll) {
    if (!coll) return '—';
    var g = collGlobal(coll) ? ' · global: ' + collGlobal(coll) : '';
    return 'value: ' + (coll.value || '') + ' · row_id: ' + (coll.row_id || '') + ' · ' +
      (coll.name || coll.content || '') + ' · active: ' + collActive(coll) + g;
  }

  function rowIssues(er, cr) {
    var vMatch = String(cr.value || '').trim() === String(er.key || '').trim();
    var ridMatch = !er.row_id || String(cr.row_id || '').trim() === String(er.row_id).trim();
    var nameLoose = !er.name || normLabel(cr.name || cr.content || '') === normLabel(er.name);
    var activeMatch = parseActiveJs(er.active) === collActive(cr);
    var globalMatch = String(er.global_genre || '').trim() === collGlobal(cr);
    return {
      value: vMatch,
      row_id: ridMatch,
      name: nameLoose,
      active: activeMatch,
      global_genre: globalMatch
    };
  }

  function runCompare() {
    if (!collectionRows.length) {
      loadCollection(function() { runCompare(); });
      return;
    }
    if (!excelRows.length) {
      $('#compareSummary').html('<p class="text-muted mb-0">Import an Excel file to run comparison.</p>');
      if ($.fn.DataTable.isDataTable('#compareTable')) {
        compareDt.clear().draw();
      }
      lastCompare = null;
      $('#btnApproveContinue').prop('disabled', true);
      return;
    }

    excelRows.forEach(function(er) { delete er._matchMeta; });

    var byVal = indexByValue(collectionRows);
    var labelIndex = buildLabelIndex(collectionRows);
    var usedCollIds = {};
    var onlyExcel = [], onlyColl = [], mismatch = [], ok = [];

    excelRows.forEach(function(er) {
      if (!er.key && !er.name) return;
      var cr = null;
      var matchType = null;
      if (er.key) {
        var byKey = byVal[er.key];
        if (byKey && !usedCollIds[byKey.record_id]) {
          cr = byKey;
          matchType = 'value';
        }
      }
      if (!cr && er.name) {
        var nl = normLabel(er.name);
        var pool = nl ? (labelIndex[nl] || []) : [];
        for (var pi = 0; pi < pool.length; pi++) {
          if (!usedCollIds[pool[pi].record_id]) {
            cr = pool[pi];
            matchType = 'label';
            break;
          }
        }
      }
      if (!cr) {
        onlyExcel.push({ excel: er, coll: null });
        return;
      }
      usedCollIds[cr.record_id] = true;
      er._matchMeta = { recordId: cr.record_id, matchType: matchType };
      var issues = rowIssues(er, cr);
      var allOk = issues.value && issues.row_id && issues.name && issues.active && issues.global_genre;
      if (allOk) ok.push({ excel: er, coll: cr, matchType: matchType });
      else mismatch.push({ excel: er, coll: cr, matchType: matchType, issues: issues });
    });

    collectionRows.forEach(function(cr) {
      var rid = cr.record_id;
      if (rid != null && !usedCollIds[rid]) onlyColl.push(cr);
    });

    lastCompare = { onlyExcel: onlyExcel, onlyColl: onlyColl, mismatch: mismatch, ok: ok };

    var rows = [];
    function pushRow(r) {
      rows.push(r);
    }

    onlyExcel.forEach(function(x) {
      pushRow({
        statusCode: 'NEW',
        statusOrder: 1,
        statusLabel: 'New',
        badgeClass: 'danger',
        matchType: '—',
        excelKey: x.excel.key ? '<code>' + x.excel.key + '</code>' : '—',
        excelDetail: fmtExcel(x.excel),
        collDetail: '—'
      });
    });
    onlyColl.forEach(function(cr) {
      pushRow({
        statusCode: 'COLL_ONLY',
        statusOrder: 2,
        statusLabel: 'Collection only',
        badgeClass: 'secondary',
        matchType: '—',
        excelKey: '—',
        excelDetail: '—',
        collDetail: fmtCollPlain(cr)
      });
    });
    mismatch.forEach(function(x) {
      var iss = x.issues;
      var parts = [];
      if (!iss.row_id) parts.push('row_id');
      if (!iss.name) parts.push('name');
      if (!iss.active) parts.push('is_active');
      if (!iss.global_genre) parts.push('global_genre');
      if (!iss.value) parts.push('value/key');
      var mt = x.matchType === 'value' ? 'By key' : (x.matchType === 'label' ? 'By name' : '—');
      pushRow({
        statusCode: 'UPDATE',
        statusOrder: 3,
        statusLabel: 'To update',
        badgeClass: 'warning',
        matchType: mt + (parts.length ? ' · ' + parts.join(', ') : ''),
        excelKey: x.excel.key ? '<code>' + x.excel.key + '</code>' : '—',
        excelDetail: fmtExcel(x.excel),
        collDetail: fmtCollPlain(x.coll)
      });
    });
    ok.forEach(function(x) {
      var mt = x.matchType === 'value' ? 'By key' : 'By name';
      pushRow({
        statusCode: 'OK',
        statusOrder: 4,
        statusLabel: 'OK',
        badgeClass: 'success',
        matchType: mt,
        excelKey: x.excel.key ? '<code>' + x.excel.key + '</code>' : '—',
        excelDetail: fmtExcel(x.excel),
        collDetail: fmtCollPlain(x.coll)
      });
    });

    $('#compareSummary').html(
      '<div class="row">' +
      '<div class="col-6 col-md-3 mb-2"><div class="dash-card dash-new"><div class="dash-num">' + onlyExcel.length + '</div><div class="dash-lbl"><i class="fas fa-plus-circle"></i> New (Excel only)</div></div></div>' +
      '<div class="col-6 col-md-3 mb-2"><div class="dash-card dash-update"><div class="dash-num">' + mismatch.length + '</div><div class="dash-lbl"><i class="fas fa-edit"></i> To update</div></div></div>' +
      '<div class="col-6 col-md-3 mb-2"><div class="dash-card dash-coll"><div class="dash-num">' + onlyColl.length + '</div><div class="dash-lbl"><i class="fas fa-database"></i> Collection only</div></div></div>' +
      '<div class="col-6 col-md-3 mb-2"><div class="dash-card dash-ok"><div class="dash-num">' + ok.length + '</div><div class="dash-lbl"><i class="fas fa-check-circle"></i> Unchanged</div></div></div>' +
      '</div>'
    );

    if ($.fn.DataTable.isDataTable('#compareTable')) {
      compareDt.clear();
      compareDt.rows.add(rows);
      compareDt.draw();
    } else {
      compareDt = $('#compareTable').DataTable({
        data: rows,
        columns: [
          {
            data: 'statusLabel',
            title: 'Status',
            render: function(d, t, row) {
              return '<span class="badge badge-' + row.badgeClass + '">' + row.statusLabel + '</span>';
            },
            createdCell: function(td, cellData, rowData) {
              $(td).attr('data-order', rowData.statusOrder);
            }
          },
          { data: 'matchType', title: 'Match', defaultContent: '—' },
          { data: 'excelKey', title: 'Excel key' },
          { data: 'excelDetail', title: 'Excel' },
          { data: 'collDetail', title: 'Collection' },
          { data: 'statusCode', visible: false, searchable: true }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[0, 'asc']],
        language: { search: 'Search table:' }
      });
      $('#compareTable_filter input').addClass('form-control form-control-sm');
    }

    applyCompareFilter();

    var strat = $('input[name="strategy"]:checked').val();
    var allowContinue = excelRows.length > 0;
    if (strat === 'adjust' && onlyExcel.length > 0) allowContinue = false;
    $('#btnApproveContinue').prop('disabled', !allowContinue);
  }

  function applyCompareFilter() {
    if (!compareDt) return;
    var api = compareDt;
    if (compareFilter === 'ALL') {
      api.column(5).search('');
    } else {
      api.column(5).search('^' + compareFilter + '$', true, false);
    }
    api.draw();
  }

  $('#compareFilterBar').on('click', 'button[data-cmp-filter]', function() {
    compareFilter = $(this).attr('data-cmp-filter') || 'ALL';
    $('#compareFilterBar button').removeClass('active');
    $(this).addClass('active');
    applyCompareFilter();
  });

  $('#btnApproveContinue').prop('disabled', true);

  $('input[name="strategy"]').on('change', function() {
    if (lastCompare) runCompare();
  });

  $('#btnSaveJsonBackup').on('click', function() {
    $('#jsonBackupStatus').text('Saving collection_backup…');
    $.ajax({
      url: CONFIG.pstools.saveBackup,
      type: 'POST',
      headers: authHeaders(),
      data: JSON.stringify({ request_id: CONFIG.requestId }),
      success: function(res) {
        if (res.success === false) {
          $('#jsonBackupStatus').html('<span class="text-danger">' + (res.error || 'Failed') + '</span>');
          return;
        }
        $('#jsonBackupStatus').html('<span class="text-success">Saved ' + (res.record_count || 0) + ' records to request data (collection_backup).</span>');
      },
      error: function(xhr) {
        $('#jsonBackupStatus').html('<span class="text-danger">HTTP ' + xhr.status + '</span>');
      }
    });
  });

  $('#btnUploadExcelToRequest').on('click', function() {
    var f = document.getElementById('excelFile').files[0];
    if (!f) { alert('Choose an Excel file first'); return; }
    $('#uploadFileStatus').text('Uploading…');
    var reader = new FileReader();
    reader.onload = function(ev) {
      var b64 = ev.target.result.split(',')[1];
      $.ajax({
        url: CONFIG.pstools.uploadFile,
        type: 'POST',
        headers: authHeaders(),
        data: JSON.stringify({
          request_id: CONFIG.requestId,
          filename: f.name,
          mimetype: f.type || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          filedata: b64,
          data_name: 'sony_genres_source_file'
        }),
        success: function(res) {
          if (res.success === false) {
            $('#uploadFileStatus').html('<span class="text-danger">' + (res.error || 'Upload failed') + '</span>');
            return;
          }
          $('#uploadFileStatus').html('<span class="text-success">Uploaded to request ' + CONFIG.requestId + '</span>');
        },
        error: function(xhr) {
          $('#uploadFileStatus').html('<span class="text-danger">HTTP ' + xhr.status + '</span>');
        }
      });
    };
    reader.readAsDataURL(f);
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
    if (!excelRows.length) {
      alert('Parse an Excel file first (Import tab).');
      return;
    }
    if (strat === 'replace') {
      if (!window.confirm('REPLACE will DELETE all genres in collection ' + CONFIG.collectionId + ' and recreate from this Excel. Continue?')) return;
    }
    if (strat === 'adjust' && lastCompare && lastCompare.onlyExcel.length) {
      alert('Strategy "Adjust only" cannot add new rows. Choose "Mix" for new genres or remove new rows from the file.');
      return;
    }
    var payload = {
      request_id: CONFIG.requestId,
      strategy: strat,
      excel_rows: excelRows.map(function(r) {
        var row = {
          key: r.key,
          name: r.name,
          active: r.active,
          row_id: r.row_id,
          global_genre: r.global_genre
        };
        if (r._matchMeta && r._matchMeta.recordId) {
          row.record_id = r._matchMeta.recordId;
        }
        return row;
      }),
      confirm_replace: strat === 'replace'
    };
    $('#btnApproveContinue').prop('disabled', true).text('Applying…');
    $.ajax({
      url: CONFIG.pstools.applyCollection,
      type: 'POST',
      headers: authHeaders(),
      data: JSON.stringify(payload),
      success: function(res) {
        $('#btnApproveContinue').prop('disabled', false).html('<i class="fas fa-check"></i> Approve and apply');
        if (res.success === false) {
          alert('Apply failed: ' + (res.error || JSON.stringify(res)));
          return;
        }
        var rep = res.report || {};
        alert('Done. Created: ' + (rep.created && rep.created.length) + ', updated: ' + (rep.updated && rep.updated.length) + ', deleted: ' + (rep.deleted && rep.deleted.length) + '. Reloading collection.');
        loadCollection(function() {
          table.clear();
          table.rows.add(collectionRows);
          table.draw();
          if (excelRows.length) runCompare();
        });
      },
      error: function(xhr) {
        $('#btnApproveContinue').prop('disabled', false).html('<i class="fas fa-check"></i> Approve and apply');
        alert('HTTP error: ' + xhr.status);
      }
    });
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
