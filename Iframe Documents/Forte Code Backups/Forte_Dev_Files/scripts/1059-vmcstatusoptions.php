<?php
/**
 * vmcStatusOptions
 *
 * Return active checklist_status rows from collection 42
 * shaped like [{ "value": "...", "content": "..." }, ...]
 * so a  / radio group can bind without calling the connector per row.
 *
 * Collection 42 fields we care about:
 *   data.vmc_group_name      (should be "checklist_status")
 *   data.vmc_data_status     ("1" = active)
 *   data.vmc_key_id          (machine key like "OK", "NEEDS_ATTENTION")
 *   data.vmc_value_desc      (label to show user)
 */

const STATUS_COLLECTION_ID = 42;

function pm_base() {
  foreach ([getenv('PM_API_BASE'), getenv('API_HOST'), getenv('PM_API_HOST'),
            getenv('PM_API_HOST_2'), getenv('PM_API_HOST_3'), getenv('HOST_URL')] as $b) {
    if ($b && trim($b) !== '') return rtrim($b, '/');
  }
  // fallback, same style as other scripts
  return 'https://cosforms-dev.stonnington.vic.gov.au';
}

function headers_get() {
  $h = [
    'Accept: application/json',
    'User-Agent: VMC-StatusOptions/1.0',
    'X-Requested-With: XMLHttpRequest'
  ];
  $tok = getenv('PM_TOKEN');
  if ($tok && trim($tok) !== '') {
    $h[] = 'Authorization: Bearer '.$tok;
  }
  return $h;
}

function http_get_json($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => headers_get(),
    CURLOPT_CUSTOMREQUEST  => 'GET',
  ]);
  $res = curl_exec($ch);
  if ($res === false) {
    $e = curl_error($ch);
    curl_close($ch);
    throw new Exception("HTTP transport error: $e");
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code >= 400) {
    throw new Exception("API $code: $res");
  }
  $json = json_decode($res, true);
  return is_array($json) ? $json : [];
}

/**
 * Pull ALL records in collection 42 (like ListAll already does),
 * then locally filter with same PMQL that you had:
 *
 *   data.vmc_group_name = "checklist_status"
 *   AND data.vmc_data_status = "1"
 */
function list_all_records($collectionId, $per=500, $maxPages=50) {
  $all  = [];
  $base = pm_base();
  for ($page=1; $page <= $maxPages; $page++) {
    $url   = $base."/api/1.0/collections/{$collectionId}/records?page={$page}&per_page={$per}";
    $resp  = http_get_json($url);
    $chunk = isset($resp['data']) ? $resp['data'] : [];
    if (!$chunk) break;
    foreach ($chunk as $r) {
      $all[] = $r;
    }
    if (count($chunk) < $per) break;
  }
  return $all;
}

try {
  $rows = list_all_records(STATUS_COLLECTION_ID);

  // Apply PMQL filter in code
  $filtered = array_values(array_filter($rows, function($row){
    $d = isset($row['data']) ? $row['data'] : [];
    $groupName  = isset($d['vmc_group_name'])   ? trim((string)$d['vmc_group_name'])   : '';
    $dataStatus = isset($d['vmc_data_status'])  ? trim((string)$d['vmc_data_status'])  : '';
    return (
      strcasecmp($groupName, 'checklist_status') === 0 &&
      $dataStatus === '1'
    );
  }));

  // Shape into [{value, content}, ...]
  $options = array_map(function($row){
    $d = isset($row['data']) ? $row['data'] : [];
    $val = isset($d['vmc_key_id'])      ? trim((string)$d['vmc_key_id'])      : '';
    $txt = isset($d['vmc_value_desc'])  ? trim((string)$d['vmc_value_desc'])  : '';
    return [
      'value'   => $val,
      'content' => $txt,
    ];
  }, $filtered);

  // OPTIONAL: sort options so "OK" shows first, etc.
  // e.g. custom order: OK, NEEDS_ATTENTION, N/A
  $orderPref = ["OK", "NEEDS_ATTENTION", "N/A"];
  usort($options, function($a,$b) use ($orderPref){
    $ia = array_search($a['value'], $orderPref);
    $ib = array_search($b['value'], $orderPref);
    $ia = ($ia === false ? 999 : $ia);
    $ib = ($ib === false ? 999 : $ib);
    if ($ia == $ib) {
      return strcasecmp($a['content'],$b['content']);
    }
    return $ia <=> $ib;
  });

  // return to watcher
  return [
    'statusOptions' => $options,
  ];

} catch (Throwable $e) {
  // fail safe: return empty so dropdowns don't explode
  return [
    'statusOptions' => [],
    'error' => $e->getMessage()
  ];
}