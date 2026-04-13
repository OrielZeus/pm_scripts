<?php
$requestId = $data['request_id'] ?? 5;

$html = <<<HTML
<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .table-actions { text-align: center; vertical-align: middle; }
  </style>
</head>

<body class="p-4">
  <div class="container-fluid">
    <h3 class="mb-4">Uploaded Documents</h3>

    <!-- Upload form -->
    
      <div class="custom-file mb-2">
        <|input type="file" class="custom-file-input" id="inputGroupFile01">
        <|label class="custom-file-label" for="inputGroupFile01">Choose document</|label>
      </div>
        <|button id="btnUpload" class="btn btn-primary">Upload</|button>
    

    <hr/>

    <!-- DataTable -->
    <table id="uploadedDocumentsTable" class="table table-bordered table-striped" style = "with:100%">
      <thead class="thead-light">
        <tr>
          <th>ID</th>
          <th>Document Name</th>
          <th>Type</th>
          <th>Size (KB)</th>
          <th class="table-actions">Actions</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</body>
HTML;

$html .= <<<SCRIPTS
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
  var requestId = {$requestId};
  var atoken = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI0IiwianRpIjoiNWE3NDVhYzk1MTBlNmE2MjI1ZTI3ZThmOTBiMDg1Y2E0MjQzOTE5ZjkzNDYyMmRlYzI1NTExM2FhNDMxZWU3YjAxNTQxM2M1ZjA2ZDk3ZjUiLCJpYXQiOjE3NTk0Mjg0MjguMDY0MzM1LCJuYmYiOjE3NTk0Mjg0MjguMDY0MzM3LCJleHAiOjE3OTA5NjQ0MjguMDU5ODU5LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.alO7qvSpYDxZSxyi5dzSr0HUpis1ZZWd3Q-nN2gWHns9xVApptud-O7820llKJfsK-am6oVSVEKKnjwY3HhfZTS8Quopjun9ljr_qIJpZjJwHanx15dt5eWWzepDrruTJUuIvxO9k3htj5pDYk3hDY28lyPXg5OQJyoPlAIIl_tpA0cKgiTTY6zGhX4-s8ZAQaT1a-kv2QPRvgvH0ek1w4k5ctfUTJmwjiV1ijo_AI7_ZFolrqwP9rQZ5CNTXK2NVa330W04FZDcvQCTneFq5SPsgEy0rDXbsrRXHMzzpRZxMfqm6iatUzMbxMhP8aS3a6E3P4l8gjvoYLSmZjPbbOyO4COL3rLHTec3Wf478ntySpHVLRDVf-jx7NXKBp2bZpBmw7lJhG5m3h31OP9LKdie0Z2ARtQCu85JLXSB0z0j0LMHMacrZAd0ArdLHCiuw9eURRkNKXLaUHKzqDM2rTLv6ubF2mGQYVUc957gxpwOz0J9Tof5W_Lp263pcl4wqN5AlF1zUA9x3AtoBBcF09gdzYfRi93sOYb5_NLkXZyJXr2-9DZ34FFC8PlhtoQKnFM2FYU7dn7Kgu4DWiI10KN6EmVbBxIeRN10sO_YN5ClSMQIqASrLthAWV9vfN7Pi9gd-A7CbAoHnNLL8_aInRcfBwiihGCzyeM3vrJhwMU"; // Replace with dynamic token if needed

  // Render action buttons (Preview only if PDF)
  function renderActions(data, type, row) {
    var ext = row.filename.split('.').pop().toLowerCase();
    var previewBtn = '';
    if (ext === 'pdf') {
      previewBtn = '<|button class="btn btn-outline-primary btn-sm action-btn btn-preview" data-id="\${row.id}" title="Preview"><i class="fas fa-eye"></i><|/button>';
    }
    return `
      \${previewBtn}
      <|button class="btn btn-outline-success btn-sm action-btn btn-download" data-id="\${row.id}" title="Download"><i class="fas fa-download"></i><|/button>
      <|button class="btn btn-outline-danger btn-sm action-btn btn-delete" data-id="\${row.id}" title="Delete"><i class="fas fa-trash"></i><|/button>
    `;
  }
  // Initialize DataTable
  var table = $('#uploadedDocumentsTable').DataTable({
    ajax: {
      url: '/api/1.0/pstools/script/upload-files-get-data',
      type: 'POST',
      contentType: 'application/json',
      headers: {
        'Authorization': 'Bearer ' + atoken
      },
      data: function(d) {
        // Send only request_id, no pagination required
        return JSON.stringify({ request_id: requestId });
      },
      dataSrc: 'data'
    },
    columns: [
      { data: 'id' },
      { data: 'filename' },
      { 
        data: 'filename',
        render: function(data) {
          return data.split('.').pop().toUpperCase();
        }
      },
      { 
        data: 'size',
        render: function(data) {
          return (data / 1024).toFixed(2);
        }
      },
      { data: null, orderable: false, render: renderActions }
    ]
  });

  $('#inputGroupFile01').on('change', function() {
    var fileName = this.files[0]?.name || 'Choose document';
    $(this).next('.custom-file-label').html(fileName);
  });
  // Upload document as Base64
  $('#btnUpload').on('click', function(e) {
    console.log("click");
    e.preventDefault();
    const fileInput = document.getElementById('inputGroupFile01');
    if (!fileInput.files.length) {
      alert('Please select a document');
      return;
    }

    const documento = fileInput.files[0];
    const reader = new FileReader();

    // Convert file to Base64
    reader.onload = function(e) {
      const base64Data = e.target.result.split(',')[1]; // Extract Base64 only
      const payload = {
        request_id: requestId,
        filename: documento.name,
        mimetype: documento.type,
        filedata: base64Data // Base64 content
      };

      // Send payload to backend
      fetch('/api/1.0/pstools/script/upload-files-table', {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json', 
          'Authorization': 'Bearer ' + atoken 
        },
        body: JSON.stringify(payload)
      })
      .then(res => {
        console.log("res",res);
        //if (!res.ok) throw new Error('Upload failed');
        return res.json();
      })
      .then(() => {
        table.ajax.reload(); // Reload DataTable after upload
        //$('#uploadForm')[0].reset(); // Reset form
        $('.custom-file-label').text('Choose document'); // Reset label text
      })
      .catch(err => {
        console.error(err);
        alert('Upload failed');
      });
    };

    reader.readAsDataURL(documento); // Read file as Base64
  });
  // Delete action
  $('#uploadedDocumentsTable').on('click', '.btn-delete', function(){
    var fileId = $(this).data('id');
    if (confirm('Are you sure you want to delete this file?')) {
      fetch('/api/1.0/pstools/script/upload-file-delete', {
        method: 'POST',
        headers: { 'Content-Type':'application/json','Authorization':'Bearer ' + atoken },
        body: JSON.stringify({ request_id: requestId, file_id: fileId })
      })
      .then(res => res.json())
      .then(() => { table.ajax.reload(); })
      .catch(err => { console.error(err); alert('Delete failed'); });
    }
  });
});
</script>
SCRIPTS;

return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];
