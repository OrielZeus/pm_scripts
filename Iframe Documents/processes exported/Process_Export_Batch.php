<?php
/**
 * PSTools batch: process export (BPMN + related assets) plus Forte-style scripts/screens/executors (list + chunked export).
 *
 * Register slug e.g. process-export-batch — single URL: api_base + /pstools/script/{slug}
 *
 * Connection: api_base + bearer_token only (OAuth bearer from the UI / request). api_token is not used for auth.
 * Host may still be filled from _env / Forte when api_base is empty (see forte_resolve_api).
 *
 * Request JSON:
 *   action: counts | list | manifest | export_process | export_chunk | export_all  (default: counts)
 *   target: dev | prod — Forte env resolution when api_base/token not set on the request
 *   mode: api | sql  (default: api)
 *   entity: processes | scripts | screens | executors — list + Forte exports (default processes for list)
 *   process_id — manifest / export_process only
 *   include_subprocesses, max_subprocess_depth — process export
 *   page + per_page  OR  DataTables: draw, start, length
 *
 * Dependency discovery (aligned with ProcessMaker core):
 *   - BPMN: pm:screenRef, pm:interstitialScreenRef, pm:scriptRef, pm:configEmail (ABE), callActivity subprocess
 *   - Process row: cancel_screen_id, request_detail_screen_id
 *   - Screens: nested FormNestedScreen in config (ScreensInScreen), watchers script_id (ScriptsInScreen)
 *
 * export_process:
 *   export_format: auto | zip | tar_gz | json
 *   export_destination: browser | request
 *   request_id, request_file_data_name — same as Forte batch when destination=request
 *   export_root_slug — optional override for asset archive root folder name (default: tenant from api_base host,
 *       e.g. northleaf.dev.cloud… → northleaf_dev; production host without ".dev." → northleaf).
 *
 * Import / apply to another environment is not implemented here; use PM native import or extend later.
 */

if (!isset($data) || !is_array($data)) {
    $data = [];
}

function pexp_unnest_request_data(array &$data): void
{
    $controlKeys = ['action', 'draw', 'start', 'length', 'page', 'per_page', 'mode', 'process_id',
        'include_subprocesses', 'max_subprocess_depth', 'export_format', 'export_destination',
        'request_id', 'request_file_data_name', 'api_base', 'bearer_token', 'api_token', 'api_sql_path',
        'entity', 'target', 'export_root_slug', 'return_format', 'api_host_dev', 'api_token_dev', 'api_host_prod', 'api_token_prod',
        'production_api_host', 'production_api_token', 'PM_PROD_API_HOST', 'PM_PROD_API_TOKEN'];
    foreach (['data', 'payload', 'formData', 'body'] as $nest) {
        if (!isset($data[$nest]) || !is_array($data[$nest])) {
            continue;
        }
        foreach ($data[$nest] as $k => $v) {
            if (in_array((string) $k, $controlKeys, true)) {
                $data[$k] = $v;
                continue;
            }
            if (!array_key_exists($k, $data)) {
                $data[$k] = $v;
            }
        }
    }
}

pexp_unnest_request_data($data);

const PEXP_PM_NS = 'http://processmaker.com/BPMN/2.0/Schema.xsd';
const PEXP_LIST_MAX = 100;
const PEXP_MAX_SCREENS = 400;
const PEXP_EXPORT_MAX_BYTES = 104857600;

function pexp_resolve_connection(array $data): array
{
    $host = $data['api_base'] ?? $data['api_host'] ?? $data['API_HOST'] ?? null;
    $env = $data['_env'] ?? [];
    if (!is_array($env)) {
        $env = [];
    }
    if ($host === null || $host === '') {
        $host = $env['API_HOST'] ?? $env['api_base'] ?? null;
    }
    if ($host === null || $host === '') {
        $g = getenv('API_HOST');
        $host = ($g !== false && $g !== '') ? $g : '';
    }
    $host = rtrim(trim((string) $host), '/');

    // Single auth credential: bearer_token (connection / Postman). Do not use api_token or API_TOKEN env as bearer.
    $token = $data['bearer_token'] ?? null;
    if ($token === null || $token === '') {
        $token = $env['bearer_token'] ?? null;
    }
    $token = trim((string) $token);

    return [$host, $token];
}

/**
 * api_base + bearer_token first; if host is still empty, Forte may supply host only (forte_resolve_api).
 * Token is never taken from Forte / api_token — only bearer_token (+ optional _env.bearer_token).
 *
 * @return array{0:string,1:string}
 */
function pexp_enrich_connection(array $data): array
{
    [$host, $token] = pexp_resolve_connection($data);
    if ($host !== '' && $token !== '') {
        return [$host, $token];
    }
    $target = strtolower(trim((string) ($data['target'] ?? 'dev')));
    [$fh, $ft] = forte_resolve_api($data, $target);
    unset($ft);
    if ($host === '') {
        $host = $fh;
    }
    return [$host, $token];
}

function pexp_clamp_int($v, int $min, int $max): int
{
    $n = (int) $v;
    if ($n < $min) {
        $n = $min;
    }
    if ($n > $max) {
        $n = $max;
    }
    return $n;
}

/**
 * @return array{0:int,1:int} page, per_page
 */
function pexp_parse_paging(array $data, int $defaultPerPage, int $maxPerPage): array
{
    $drawStyle = isset($data['start']) || isset($data['length']);
    if ($drawStyle) {
        $start = max(0, (int) ($data['start'] ?? 0));
        $length = pexp_clamp_int($data['length'] ?? $defaultPerPage, 1, $maxPerPage);
        $page = (int) floor($start / $length) + 1;
        return [$page, $length];
    }
    $page = pexp_clamp_int($data['page'] ?? 1, 1, 100000);
    $perPage = pexp_clamp_int($data['per_page'] ?? $defaultPerPage, 1, $maxPerPage);
    return [$page, $perPage];
}

function pexp_flatten_api_row(?array $row): array
{
    if (!is_array($row)) {
        return [];
    }
    if (isset($row['attributes']) && is_array($row['attributes'])) {
        $out = [];
        if (array_key_exists('id', $row)) {
            $out['id'] = $row['id'];
        }
        foreach ($row['attributes'] as $ak => $av) {
            $out[$ak] = $av;
        }
        return $out;
    }
    return $row;
}

function pexp_api_resolve_total(array $json, array $meta, array $chunk, int $perPage, int $page): int
{
    foreach (['total', 'Total'] as $tk) {
        if (isset($meta[$tk]) && is_numeric($meta[$tk])) {
            return max(0, (int) $meta[$tk]);
        }
    }
    if (isset($json['total']) && is_numeric($json['total'])) {
        return max(0, (int) $json['total']);
    }
    $lp = (int) ($meta['last_page'] ?? 0);
    $pp = (int) ($meta['per_page'] ?? $perPage);
    $cp = (int) ($meta['current_page'] ?? $page);
    if ($pp < 1) {
        $pp = $perPage;
    }
    if ($lp > 0 && $pp > 0) {
        if ($cp >= $lp) {
            return max(0, ($lp - 1) * $pp + count($chunk));
        }
        return max(count($chunk), ($cp - 1) * $pp + count($chunk));
    }
    $tp = (int) ($meta['total_pages'] ?? 0);
    if ($tp > 0 && $pp > 0 && $cp >= $tp) {
        return max(0, ($tp - 1) * $pp + count($chunk));
    }
    return count($chunk);
}

function pexp_api_probe_total(string $host, string $token, string $path, array $extraQuery = []): int
{
    $p = pexp_api_get_one_page($host, $token, $path, 1, 1, $extraQuery, false);
    $meta = $p['meta'] ?? [];
    if (isset($meta['total']) && is_numeric($meta['total'])) {
        return max(0, (int) $meta['total']);
    }
    $lp = (int) ($meta['last_page'] ?? 0);
    $pp = (int) ($meta['per_page'] ?? 1);
    return ($lp > 0 && $pp === 1) ? max(0, $lp) : (int) ($p['raw_total'] ?? 0);
}

function pexp_api_get_one_page(string $host, string $token, string $path, int $page, int $perPage, array $extraQuery = [], bool $probeTotal = false): array
{
    if (!class_exists('\GuzzleHttp\Client')) {
        throw new \RuntimeException('GuzzleHttp\Client is required.');
    }
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 120]);
    $q = array_merge(['page' => $page, 'per_page' => $perPage], $extraQuery);
    $qs = http_build_query($q);
    $url = $host . $path . (strpos($path, '?') !== false ? '&' : '?') . $qs;
    try {
        $res = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
    } catch (\Throwable $e) {
        return [
            'data' => [],
            'meta' => ['http_error' => $e->getMessage()],
            'raw_total' => 0,
            'http_status' => 0,
        ];
    }
    $code = $res->getStatusCode();
    $json = json_decode($res->getBody()->getContents(), true);
    unset($res);
    if (!is_array($json)) {
        return ['data' => [], 'meta' => ['http_status' => $code], 'raw_total' => 0, 'http_status' => $code];
    }
    $chunk = $json['data'] ?? [];
    if (!is_array($chunk)) {
        $chunk = [];
    }
    foreach ($chunk as $i => $cell) {
        $chunk[$i] = pexp_flatten_api_row(is_array($cell) ? $cell : []);
    }
    $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
    $hasExplicit = isset($meta['total']) || isset($meta['Total'])
        || (isset($json['total']) && is_numeric($json['total']));
    $total = pexp_api_resolve_total($json, $meta, $chunk, $perPage, $page);
    if ($total < count($chunk)) {
        $total = count($chunk);
    }
    if (!$hasExplicit && $probeTotal) {
        $total = pexp_api_probe_total($host, $token, $path, $extraQuery);
        if ($total < count($chunk)) {
            $total = count($chunk);
        }
    }
    return ['data' => $chunk, 'meta' => $meta, 'raw_total' => $total, 'http_status' => $code];
}

function pexp_http_get_raw(string $host, string $token, string $path): array
{
    if (!class_exists('\GuzzleHttp\Client')) {
        return ['ok' => false, 'body' => '', 'status' => 0, 'error' => 'GuzzleHttp\Client is required.'];
    }
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 180]);
    $url = rtrim($host, '/') . $path;
    try {
        $res = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/xml, text/xml, */*',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        $code = $res->getStatusCode();
        $body = (string) $res->getBody()->getContents();
        return ['ok' => $code >= 200 && $code < 300, 'body' => $body, 'status' => $code, 'error' => null];
    } catch (\Throwable $e) {
        return ['ok' => false, 'body' => '', 'status' => 0, 'error' => $e->getMessage()];
    }
}

function pexp_http_get_json(string $host, string $token, string $path): array
{
    if (!class_exists('\GuzzleHttp\Client')) {
        return ['ok' => false, 'json' => null, 'status' => 0, 'error' => 'GuzzleHttp\Client is required.'];
    }
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 180]);
    $url = rtrim($host, '/') . $path;
    try {
        $res = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        $code = $res->getStatusCode();
        $json = json_decode((string) $res->getBody()->getContents(), true);
        return ['ok' => $code >= 200 && $code < 300, 'json' => is_array($json) ? $json : null, 'status' => $code, 'error' => null];
    } catch (\Throwable $e) {
        return ['ok' => false, 'json' => null, 'status' => 0, 'error' => $e->getMessage()];
    }
}

/**
 * @return array{screen_ids:int[],script_ids:int[],subprocess_ids:int[]}
 */
function pexp_bpmn_collect_ids(string $bpmn): array
{
    $screens = [];
    $scripts = [];
    $subs = [];
    if (trim($bpmn) === '') {
        return ['screen_ids' => [], 'script_ids' => [], 'subprocess_ids' => []];
    }
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    if (!@$dom->loadXML($bpmn)) {
        return ['screen_ids' => [], 'script_ids' => [], 'subprocess_ids' => []];
    }
    $pmNs = PEXP_PM_NS;
    $xp = new \DOMXPath($dom);
    $xp->registerNamespace('pm', $pmNs);
    $xp->registerNamespace('bpmn', 'http://www.omg.org/spec/BPMN/20100524/MODEL');

    foreach ($xp->query('//*') ?: [] as $el) {
        if (!$el instanceof \DOMElement) {
            continue;
        }
        $sid = $el->getAttributeNS($pmNs, 'screenRef');
        if ($sid !== '' && ctype_digit((string) $sid)) {
            $screens[(int) $sid] = true;
        }
        $iid = $el->getAttributeNS($pmNs, 'interstitialScreenRef');
        if ($iid !== '' && ctype_digit((string) $iid)) {
            $screens[(int) $iid] = true;
        }
        $rid = $el->getAttributeNS($pmNs, 'scriptRef');
        if ($rid !== '' && ctype_digit((string) $rid)) {
            $scripts[(int) $rid] = true;
        }
        $cfgEmail = $el->getAttributeNS($pmNs, 'configEmail');
        if ($cfgEmail !== '') {
            $j = json_decode($cfgEmail, true);
            if (is_array($j)) {
                foreach (['screenEmailRef', 'screenCompleteRef'] as $k) {
                    if (!empty($j[$k]) && is_numeric($j[$k])) {
                        $screens[(int) $j[$k]] = true;
                    }
                }
            }
        }
    }

    foreach ($xp->query('//bpmn:callActivity | //*[local-name()="callActivity"]') ?: [] as $el) {
        if (!$el instanceof \DOMElement) {
            continue;
        }
        $ce = $el->getAttribute('calledElement');
        if (preg_match('/ProcessId-(\d+)/i', $ce, $m)) {
            $subs[(int) $m[1]] = true;
        }
        $cfg = $el->getAttributeNS($pmNs, 'config');
        if ($cfg !== '') {
            $j = json_decode($cfg, true);
            if (is_array($j) && !empty($j['processId']) && is_numeric($j['processId'])) {
                $subs[(int) $j['processId']] = true;
            }
        }
    }

    return [
        'screen_ids' => array_keys($screens),
        'script_ids' => array_keys($scripts),
        'subprocess_ids' => array_keys($subs),
    ];
}

function pexp_walk_config_for_nested_screens($config, array &$outIds): void
{
    if (!is_array($config)) {
        return;
    }
    if (isset($config['component']) && $config['component'] === 'FormNestedScreen' && !empty($config['config']['screen'])) {
        $sid = $config['config']['screen'];
        if (is_numeric($sid)) {
            $outIds[(int) $sid] = true;
        }
    }
    foreach ($config as $v) {
        if (is_array($v)) {
            pexp_walk_config_for_nested_screens($v, $outIds);
        }
    }
}

function pexp_walk_watchers_for_scripts($watchers, array &$scriptIds): void
{
    if (!is_array($watchers)) {
        return;
    }
    $visit = function ($item) use (&$visit, &$scriptIds): void {
        if (is_array($item)) {
            if (!empty($item['script_id'])) {
                $sid = $item['script_id'];
                if (is_string($sid) && strpos($sid, 'data_source') === 0) {
                    // data source package refs — skip
                } elseif (is_string($sid) && preg_match('/script-(\d+)/i', $sid, $m)) {
                    $scriptIds[(int) $m[1]] = true;
                } elseif (is_numeric($sid)) {
                    $scriptIds[(int) $sid] = true;
                }
            }
            foreach ($item as $child) {
                if (is_array($child)) {
                    $visit($child);
                }
            }
        }
    };
    $visit($watchers);
}

function pexp_lower_row_keys(?array $r): array
{
    if (!is_array($r)) {
        return [];
    }
    $o = [];
    foreach ($r as $k => $v) {
        $o[strtolower((string) $k)] = $v;
    }
    return $o;
}

function pexp_slugify(string $title): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $title);
    if ($t === false) {
        $t = $title;
    }
    $t = strtolower((string) $t);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    $t = trim((string) $t, '-');
    return $t !== '' ? $t : 'asset';
}

function pexp_language_extension(string $language): string
{
    $map = [
        'php' => 'php',
        'javascript' => 'js',
        'python' => 'py',
        'csharp' => 'cs',
        'java' => 'java',
        'javascript-ssr' => 'mjs',
        'lua' => 'lua',
        'r' => 'r',
    ];
    $k = strtolower(trim($language));
    return $map[$k] ?? 'txt';
}

/**
 * Normalize paths inside zip/tar: forward slashes only, relative (no leading / or \),
 * collapse repeats, strip ".." safely, remove NUL. Prevents broken multi-segment folder
 * trees on Windows extractors when separators or prefixes are inconsistent.
 */
function pexp_normalize_archive_entry_name(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $path = str_replace("\0", '', $path);
    $path = preg_replace('#^[\\/]+#', '', $path);
    $path = preg_replace('#/+#', '/', $path);
    $parts = explode('/', $path);
    $safe = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            if ($safe !== []) {
                array_pop($safe);
            }
            continue;
        }
        $safe[] = $part;
    }

    return implode('/', $safe);
}

/**
 * Extract hostname from API base URL (scheme optional).
 */
function pexp_extract_hostname_from_api_base(string $hostOrUrl): string
{
    $s = trim($hostOrUrl);
    if ($s === '') {
        return '';
    }
    if (strpos($s, '://') === false) {
        $s = 'https://' . $s;
    }
    $p = parse_url($s);
    $host = isset($p['host']) ? (string) $p['host'] : '';

    return strtolower($host);
}

/**
 * First DNS label (tenant), e.g. northleaf from northleaf.dev.cloud.processmaker.net.
 */
function pexp_tenant_label_from_hostname(string $hostname): string
{
    if ($hostname === '') {
        return '';
    }
    $labels = explode('.', $hostname);
    $first = isset($labels[0]) ? strtolower((string) $labels[0]) : '';
    if ($first === 'www' && isset($labels[1])) {
        $first = strtolower((string) $labels[1]);
    }

    return $first;
}

function pexp_hostname_implies_dev_environment(string $hostname): bool
{
    return $hostname !== '' && strpos($hostname, '.dev.') !== false;
}

/**
 * Slug for host label (northleaf → northleaf); allows underscores in override only via full export_root_slug.
 */
function pexp_slugify_host_label(string $label): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $label);
    if ($t === false) {
        $t = $label;
    }
    $t = strtolower((string) $t);
    $t = preg_replace('/[^a-z0-9_-]+/', '-', $t);
    $t = trim((string) $t, '-');

    return $t !== '' ? $t : '';
}

/**
 * Root folder name for scripts/screens/export_chunk/export_all archives (replaces forte_dev_files / forte_prod_files).
 *
 * Uses api_base hostname: tenant = first label; suffix _dev when hostname contains ".dev.".
 * Optional export_root_slug overrides auto-detection (already slug-safe string).
 *
 * @return non-empty-string
 */
function pexp_resolve_export_asset_root_folder(string $apiHost, array $data): string
{
    $override = isset($data['export_root_slug']) ? trim((string) $data['export_root_slug']) : '';
    if ($override !== '') {
        $clean = pexp_slugify_host_label(str_replace('\\', '/', $override));
        if ($clean !== '') {
            return $clean;
        }
    }

    $hostname = pexp_extract_hostname_from_api_base($apiHost);
    $tenant = pexp_slugify_host_label(pexp_tenant_label_from_hostname($hostname));
    if ($tenant === '') {
        return 'processmaker_export';
    }

    return pexp_hostname_implies_dev_environment($hostname) ? ($tenant . '_dev') : $tenant;
}

/**
 * Computed properties of type javascript → separate .js files (same idea as Forte batch).
 *
 * @return array<int,array{path:string,contents:string}>
 */
function pexp_screen_extract_computed_js(string $root, array $row): array
{
    $files = [];
    $id = $row['id'] ?? 'screen';
    $title = (string) ($row['title'] ?? 'screen');
    $base = $id . '-' . pexp_slugify($title);
    $computed = $row['computed'] ?? null;
    if (is_string($computed) && $computed !== '') {
        $decoded = json_decode($computed, true);
        $computed = is_array($decoded) ? $decoded : null;
    }
    if (!is_array($computed)) {
        return $files;
    }
    $j = 0;
    foreach ($computed as $c) {
        if (!is_array($c)) {
            continue;
        }
        $type = strtolower(trim((string) ($c['type'] ?? '')));
        if ($type !== 'javascript') {
            continue;
        }
        $body = $c['formula'] ?? $c['script'] ?? $c['value'] ?? '';
        if (!is_string($body)) {
            $body = is_scalar($body) ? (string) $body : json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        $name = (string) ($c['name'] ?? $c['property'] ?? $c['field'] ?? ('calc-' . $j));
        $safe = pexp_slugify($name);
        if ($safe === '') {
            $safe = 'calc-' . $j;
        }
        $path = $root . '/screens/computed/' . $base . '-' . $j . '-' . $safe . '.js';
        $files[] = ['path' => $path, 'contents' => $body];
        $j++;
    }
    return $files;
}

function pexp_ensure_list_of_rows($rows): array
{
    if (!is_array($rows)) {
        return [];
    }
    if ($rows === []) {
        return [];
    }
    if (isset($rows['__error__'])) {
        return [];
    }
    $keys = array_keys($rows);
    $indexed = $keys === range(0, count($rows) - 1);
    if (!$indexed) {
        return [$rows];
    }
    return $rows;
}

function pexp_sql_first_cnt($rows): int
{
    $rows = pexp_ensure_list_of_rows($rows);
    if ($rows === [] || !isset($rows[0]) || !is_array($rows[0])) {
        return 0;
    }
    $r = $rows[0];
    $r = pexp_lower_row_keys($r);
    foreach ($r as $k => $v) {
        if (stripos((string) $k, 'cnt') !== false || stripos((string) $k, 'count') !== false) {
            return (int) $v;
        }
    }
    return (int) ($r['cnt'] ?? 0);
}

function pexp_resolve_sql_path(array $data): string
{
    $chain = [$data['api_sql_path'] ?? null];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['API_SQL'] ?? null;
    }
    $chain[] = getenv('API_SQL') ?: null;
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $p = trim((string) $v);
        if ($p !== '') {
            return ($p[0] === '/') ? $p : '/' . $p;
        }
    }
    return '/admin/package-proservice-tools/sql';
}

function pexp_call_sql(string $endpoint, string $token, string $sql): array
{
    if ($endpoint === '' || $token === '') {
        return ['__error__' => 'SQL endpoint or bearer token is empty.'];
    }
    if (!class_exists('\GuzzleHttp\Client')) {
        return ['__error__' => 'GuzzleHttp\Client is required.'];
    }
    static $client = null;
    if ($client === null) {
        $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 180]);
    }
    try {
        $res = $client->request('POST', $endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode(['SQL' => base64_encode($sql)]),
        ]);
        $json = json_decode($res->getBody()->getContents(), true);
        unset($res);
        if (is_array($json) && isset($json['output']) && is_array($json['output'])) {
            return $json['output'];
        }
        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }
        if (is_array($json) && isset($json['result']) && is_array($json['result'])) {
            return $json['result'];
        }
        if (is_array($json) && isset($json['rows']) && is_array($json['rows'])) {
            return $json['rows'];
        }
        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return ['__error__' => $e->getMessage()];
    }
}

function pexp_sql_count_processes(): string
{
    return 'SELECT COUNT(*) AS cnt FROM processes WHERE deleted_at IS NULL';
}

function pexp_sql_list_processes_thin(int $offset, int $limit): string
{
    return 'SELECT id, name, status, updated_at FROM processes WHERE deleted_at IS NULL ORDER BY id ASC LIMIT '
        . (int) $limit . ' OFFSET ' . (int) $offset;
}

function pexp_sql_get_process_row_fixed(int $id): string
{
    return 'SELECT * FROM processes WHERE id = ' . (int) $id . ' AND deleted_at IS NULL LIMIT 1';
}

function pexp_sql_screen_full(int $id): string
{
    return 'SELECT * FROM screens WHERE id = ' . (int) $id . ' LIMIT 1';
}

function pexp_sql_script_full(int $id): string
{
    return 'SELECT s.id, s.`key`, s.title, s.description, s.language, s.code, s.timeout, '
        . 's.script_executor_id, s.script_category_id, s.run_as_user_id, '
        . 's.retry_attempts, s.retry_wait_time, s.created_at, s.updated_at, '
        . 'e.language AS executor_language, e.title AS executor_title, '
        . 'e.config AS executor_config, e.type AS executor_type '
        . 'FROM scripts s '
        . 'LEFT JOIN script_executors e ON e.id = s.script_executor_id '
        . 'WHERE s.id = ' . (int) $id . ' LIMIT 1';
}

function pexp_sql_executor_full(int $id): string
{
    return 'SELECT * FROM script_executors WHERE id = ' . (int) $id . ' LIMIT 1';
}

function pexp_build_zip_binary(array $files): ?string
{
    if (!class_exists('\ZipArchive')) {
        return null;
    }
    $uniq = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : str_replace('.', '', uniqid('', true));
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pexp_' . $uniq . '.zip';
    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        @unlink($zipPath);
        return null;
    }
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $name = pexp_normalize_archive_entry_name((string) ($f['path'] ?? ''));
        if ($name === '') {
            continue;
        }
        $zip->addFromString($name, (string) ($f['contents'] ?? ''));
    }
    $zip->close();
    if (!is_file($zipPath)) {
        return null;
    }
    $bin = file_get_contents($zipPath);
    @unlink($zipPath);
    return $bin === false ? null : $bin;
}

function pexp_ustar_header(string $name, int $size, int $mtime): string
{
    $name = pexp_normalize_archive_entry_name($name);
    if (strlen($name) > 99) {
        $name = substr($name, -99);
    }
    $h = str_repeat("\0", 512);
    $h = substr_replace($h, str_pad(substr($name, 0, 100), 100, "\0"), 0, 100);
    $oct = function (int $n, int $width): string {
        $s = decoct($n);
        if (strlen($s) >= $width) {
            $s = substr($s, 0, $width - 1);
        }
        return str_pad($s, $width - 1, '0', STR_PAD_LEFT) . "\0";
    };
    $h = substr_replace($h, $oct(0644, 8), 100, 8);
    $h = substr_replace($h, $oct(0, 8), 108, 8);
    $h = substr_replace($h, $oct(0, 8), 116, 8);
    $h = substr_replace($h, $oct($size, 12), 124, 12);
    $h = substr_replace($h, $oct($mtime, 12), 136, 12);
    $h = substr_replace($h, '        ', 148, 8);
    $h = substr_replace($h, '0', 156, 1);
    $h = substr_replace($h, 'ustar', 257, 5);
    $h = substr_replace($h, chr(0), 262, 1);
    $h = substr_replace($h, '00', 263, 2);
    $sum = 0;
    for ($i = 0; $i < 512; $i++) {
        $sum += ord($h[$i]);
    }
    $h = substr_replace($h, sprintf("%06o\0 ", $sum), 148, 8);
    return $h;
}

function pexp_ustar_one_entry(string $path, string $contents): string
{
    $path = pexp_normalize_archive_entry_name($path);
    if ($path === '') {
        return '';
    }
    $mtime = time();
    $header = pexp_ustar_header($path, strlen($contents), $mtime);
    $pad = (512 - (strlen($contents) % 512)) % 512;
    return $header . $contents . str_repeat("\0", $pad);
}

function pexp_build_ustar_tar(array $files): string
{
    $out = '';
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $p = pexp_normalize_archive_entry_name((string) ($f['path'] ?? ''));
        if ($p === '') {
            continue;
        }
        $out .= pexp_ustar_one_entry($p, (string) ($f['contents'] ?? ''));
    }
    $out .= str_repeat("\0", 1024);
    return $out;
}

function pexp_build_targz_binary(array $files): ?string
{
    if (!function_exists('gzencode')) {
        return null;
    }
    $tar = pexp_build_ustar_tar($files);
    if ($tar === '' || strlen($tar) <= 1024) {
        return null;
    }
    $gz = gzencode($tar, 6);
    unset($tar);
    return $gz === false ? null : $gz;
}

function pexp_build_archive_binary(array $files, string $mode): array
{
    $mode = strtolower(trim($mode));
    if ($mode === 'zip') {
        $z = pexp_build_zip_binary($files);
        return ($z !== null && $z !== '') ? [$z, 'zip'] : [null, 'zip'];
    }
    if ($mode === 'tar_gz' || $mode === 'tgz') {
        $t = pexp_build_targz_binary($files);
        return ($t !== null && $t !== '') ? [$t, 'tar_gz'] : [null, 'tar_gz'];
    }
    $z = pexp_build_zip_binary($files);
    if ($z !== null && $z !== '') {
        return [$z, 'zip'];
    }
    $t = pexp_build_targz_binary($files);
    return ($t !== null && $t !== '') ? [$t, 'tar_gz'] : [null, ''];
}

function pexp_pm_upload_request_file(string $host, string $token, int $requestId, string $fileBytes, string $uploadFilename, string $dataName): array
{
    if (!class_exists('\GuzzleHttp\Client')) {
        return ['success' => false, 'error' => 'GuzzleHttp\Client is required.'];
    }
    $url = rtrim($host, '/') . '/requests/' . $requestId . '/files';
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 300]);
    try {
        $res = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
            'query' => ['data_name' => $dataName],
            'multipart' => [
                [
                    'name' => 'file',
                    'filename' => $uploadFilename,
                    'contents' => $fileBytes,
                ],
            ],
        ]);
        $code = $res->getStatusCode();
        $body = (string) $res->getBody();
        $json = json_decode($body, true);
        if ($code >= 200 && $code < 300 && is_array($json) && isset($json['fileUploadId'])) {
            return ['success' => true, 'file_upload_id' => (int) $json['fileUploadId'], 'http_status' => $code];
        }
        $err = is_array($json) ? json_encode($json, JSON_UNESCAPED_UNICODE) : $body;
        return ['success' => false, 'error' => $err !== '' ? $err : ('HTTP ' . $code), 'http_status' => $code];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Fetch one screen row (API or SQL).
 *
 * @return array<string,mixed>|null
 */
function pexp_fetch_screen(string $host, string $token, string $mode, array $data, int $screenId): ?array
{
    if ($mode === 'sql') {
        $endpoint = rtrim($host, '/') . pexp_resolve_sql_path($data);
        $rows = pexp_call_sql($endpoint, $token, pexp_sql_screen_full($screenId));
        if (isset($rows['__error__'])) {
            return null;
        }
        $list = pexp_ensure_list_of_rows($rows);
        if ($list === [] || !isset($list[0]) || !is_array($list[0])) {
            return null;
        }
        return pexp_lower_row_keys(pexp_flatten_api_row($list[0]));
    }
    $path = '/screens/' . $screenId;
    $r = pexp_http_get_json($host, $token, $path);
    if (!$r['ok'] || !is_array($r['json'])) {
        return null;
    }
    $j = $r['json'];
    $row = isset($j['data']) && is_array($j['data']) ? $j['data'] : $j;
    $row = pexp_flatten_api_row(is_array($row) ? $row : []);
    return $row === [] ? null : pexp_lower_row_keys($row);
}

/**
 * @return array<string,mixed>|null
 */
function pexp_fetch_script(string $host, string $token, string $mode, array $data, int $scriptId): ?array
{
    if ($mode === 'sql') {
        $endpoint = rtrim($host, '/') . pexp_resolve_sql_path($data);
        $rows = pexp_call_sql($endpoint, $token, pexp_sql_script_full($scriptId));
        if (isset($rows['__error__'])) {
            return null;
        }
        $list = pexp_ensure_list_of_rows($rows);
        if ($list === [] || !isset($list[0]) || !is_array($list[0])) {
            return null;
        }
        return pexp_lower_row_keys(pexp_flatten_api_row($list[0]));
    }
    $path = '/scripts/' . $scriptId;
    $r = pexp_http_get_json($host, $token, $path);
    if (!$r['ok'] || !is_array($r['json'])) {
        return null;
    }
    $j = $r['json'];
    $row = isset($j['data']) && is_array($j['data']) ? $j['data'] : $j;
    $row = pexp_flatten_api_row(is_array($row) ? $row : []);
    return $row === [] ? null : pexp_lower_row_keys($row);
}

/**
 * @return array<string,mixed>|null
 */
function pexp_fetch_executor(string $host, string $token, string $mode, array $data, int $execId): ?array
{
    if ($execId < 1) {
        return null;
    }
    if ($mode === 'sql') {
        $endpoint = rtrim($host, '/') . pexp_resolve_sql_path($data);
        $rows = pexp_call_sql($endpoint, $token, pexp_sql_executor_full($execId));
        if (isset($rows['__error__'])) {
            return null;
        }
        $list = pexp_ensure_list_of_rows($rows);
        if ($list === [] || !isset($list[0]) || !is_array($list[0])) {
            return null;
        }
        return pexp_lower_row_keys(pexp_flatten_api_row($list[0]));
    }
    $path = '/script-executors/' . $execId;
    $r = pexp_http_get_json($host, $token, $path);
    if (!$r['ok'] || !is_array($r['json'])) {
        return null;
    }
    $j = $r['json'];
    $row = isset($j['data']) && is_array($j['data']) ? $j['data'] : $j;
    $row = pexp_flatten_api_row(is_array($row) ? $row : []);
    return $row === [] ? null : pexp_lower_row_keys($row);
}

/**
 * @return array{process:array<string,mixed>|null,bpmn:string,error?:string}
 */
function pexp_fetch_process_with_bpmn(string $host, string $token, string $mode, array $data, int $processId): array
{
    if ($mode === 'sql') {
        $endpoint = rtrim($host, '/') . pexp_resolve_sql_path($data);
        $rows = pexp_call_sql($endpoint, $token, pexp_sql_get_process_row_fixed($processId));
        if (isset($rows['__error__'])) {
            return ['process' => null, 'bpmn' => '', 'error' => (string) $rows['__error__']];
        }
        $list = pexp_ensure_list_of_rows($rows);
        if ($list === [] || !isset($list[0]) || !is_array($list[0])) {
            return ['process' => null, 'bpmn' => '', 'error' => 'Process not found in SQL.'];
        }
        $row = pexp_lower_row_keys(pexp_flatten_api_row($list[0]));
        $bpmn = (string) ($row['bpmn'] ?? '');
        unset($row['bpmn']);
        return ['process' => $row, 'bpmn' => $bpmn];
    }
    $gj = pexp_http_get_json($host, $token, '/processes/' . $processId);
    if (!$gj['ok'] || $gj['json'] === null) {
        return ['process' => null, 'bpmn' => '', 'error' => $gj['error'] ?? ('HTTP ' . (string) $gj['status'])];
    }
    $j = $gj['json'];
    $row = isset($j['data']) && is_array($j['data']) ? $j['data'] : $j;
    $row = pexp_flatten_api_row(is_array($row) ? $row : []);
    $row = pexp_lower_row_keys($row);
    $raw = pexp_http_get_raw($host, $token, '/processes/' . $processId . '/bpmn');
    if (!$raw['ok']) {
        return ['process' => $row, 'bpmn' => '', 'error' => $raw['error'] ?? 'Could not download BPMN.'];
    }
    return ['process' => $row, 'bpmn' => (string) $raw['body']];
}

/**
 * Resolve all screens (nested) and scripts (watchers + BPMN) for a set of root process IDs.
 *
 * @return array{
 *   screens: array<int,array<string,mixed>>,
 *   scripts: array<int,array<string,mixed>>,
 *   executors: array<int,array<string,mixed>>,
 *   subprocesses_tracked: int[],
 *   errors: string[]
 * }
 */
function pexp_resolve_dependencies(
    string $host,
    string $token,
    string $mode,
    array $data,
    array $rootProcessIds,
    bool $includeSubprocesses,
    int $maxSubDepth
): array {
    $screens = [];
    $scripts = [];
    $executors = [];
    $errors = [];
    $trackedSubs = [];
    $screenQueue = [];
    $processedScreens = [];
    $scriptIdsPending = [];
    $executorIdsPending = [];

    $enqueueScreen = function (int $id) use (&$screenQueue, &$processedScreens): void {
        if ($id < 1 || isset($processedScreens[$id])) {
            return;
        }
        if (!in_array($id, $screenQueue, true)) {
            $screenQueue[] = $id;
        }
    };

    $enqueueScript = function (int $id) use (&$scriptIdsPending): void {
        if ($id > 0) {
            $scriptIdsPending[$id] = true;
        }
    };

    $processQueue = array_values(array_unique(array_map('intval', $rootProcessIds)));
    $depthByPid = [];
    foreach ($processQueue as $pid) {
        $depthByPid[$pid] = 0;
    }

    $idx = 0;
    while ($idx < count($processQueue)) {
        $pid = $processQueue[$idx];
        $idx++;
        $d = $depthByPid[$pid] ?? 0;

        $pack = pexp_fetch_process_with_bpmn($host, $token, $mode, $data, $pid);
        if ($pack['process'] === null) {
            $errors[] = 'Process ' . $pid . ': ' . ($pack['error'] ?? 'not found');
            continue;
        }
        $bpmn = $pack['bpmn'];
        $procRow = $pack['process'];

        $refs = pexp_bpmn_collect_ids($bpmn);
        foreach ($refs['screen_ids'] as $sid) {
            $enqueueScreen((int) $sid);
        }
        foreach ($refs['script_ids'] as $rid) {
            $enqueueScript((int) $rid);
        }
        foreach (['cancel_screen_id', 'request_detail_screen_id'] as $ck) {
            if (!empty($procRow[$ck]) && is_numeric($procRow[$ck])) {
                $enqueueScreen((int) $procRow[$ck]);
            }
        }

        if ($includeSubprocesses && $d < $maxSubDepth) {
            foreach ($refs['subprocess_ids'] as $spid) {
                $spid = (int) $spid;
                if ($spid < 1 || $spid === $pid) {
                    continue;
                }
                $trackedSubs[$spid] = true;
                if (!isset($depthByPid[$spid])) {
                    $depthByPid[$spid] = $d + 1;
                    $processQueue[] = $spid;
                }
            }
        }
    }

    $iter = 0;
    while ($screenQueue !== [] && $iter < PEXP_MAX_SCREENS) {
        $iter++;
        $sid = array_shift($screenQueue);
        if ($sid === null || $sid < 1) {
            continue;
        }
        if (isset($processedScreens[$sid])) {
            continue;
        }
        if (count($processedScreens) >= PEXP_MAX_SCREENS) {
            $errors[] = 'Stopped: more than ' . PEXP_MAX_SCREENS . ' screens.';
            break;
        }
        $row = pexp_fetch_screen($host, $token, $mode, $data, $sid);
        if ($row === null) {
            $errors[] = 'Screen id ' . $sid . ' not found.';
            $processedScreens[$sid] = true;
            continue;
        }
        $processedScreens[$sid] = true;
        $screens[$sid] = $row;

        $cfg = $row['config'] ?? null;
        if (is_string($cfg) && $cfg !== '') {
            $cfg = json_decode($cfg, true);
        }
        $nested = [];
        if (is_array($cfg)) {
            pexp_walk_config_for_nested_screens($cfg, $nested);
        }
        foreach (array_keys($nested) as $nid) {
            $enqueueScreen((int) $nid);
        }

        $watch = $row['watchers'] ?? null;
        if (is_string($watch) && $watch !== '') {
            $watch = json_decode($watch, true);
        }
        $scr = [];
        if (is_array($watch)) {
            pexp_walk_watchers_for_scripts($watch, $scr);
        }
        foreach (array_keys($scr) as $scrid) {
            $enqueueScript((int) $scrid);
        }
    }

    foreach (array_keys($scriptIdsPending) as $scrid) {
        if (isset($scripts[$scrid])) {
            continue;
        }
        $srow = pexp_fetch_script($host, $token, $mode, $data, (int) $scrid);
        if ($srow === null) {
            $errors[] = 'Script id ' . $scrid . ' not found.';
            continue;
        }
        $scripts[(int) $scrid] = $srow;
        $eid = (int) ($srow['script_executor_id'] ?? 0);
        if ($eid > 0) {
            $executorIdsPending[$eid] = true;
        }
    }

    foreach (array_keys($executorIdsPending) as $eid) {
        if (isset($executors[$eid])) {
            continue;
        }
        $erow = pexp_fetch_executor($host, $token, $mode, $data, (int) $eid);
        if ($erow === null) {
            $errors[] = 'Executor id ' . $eid . ' not found.';
            continue;
        }
        $executors[(int) $eid] = $erow;
    }

    return [
        'screens' => $screens,
        'scripts' => $scripts,
        'executors' => $executors,
        'subprocesses_tracked' => array_keys($trackedSubs),
        'errors' => $errors,
    ];
}

/**
 * @param array<int,array<string,mixed>> $screens
 * @param array<int,array<string,mixed>> $scripts
 * @param array<int,array<string,mixed>> $executors
 * @return array<int,array{path:string,contents:string}>
 */
function pexp_build_file_list(string $root, array $processMeta, string $bpmn, array $screens, array $scripts, array $executors): array
{
    $files = [];
    $pid = (int) ($processMeta['id'] ?? 0);
    $pname = (string) ($processMeta['name'] ?? 'process');
    $base = $pid . '-' . pexp_slugify($pname);

    $metaCopy = $processMeta;
    if (isset($metaCopy['bpmn'])) {
        unset($metaCopy['bpmn']);
    }
    $files[] = ['path' => $root . '/process/' . $base . '.meta.json', 'contents' => json_encode($metaCopy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)];
    $files[] = ['path' => $root . '/process/' . $base . '.bpmn.xml', 'contents' => $bpmn];

    foreach ($screens as $sid => $row) {
        $title = (string) ($row['title'] ?? 'screen');
        $spath = $root . '/screens/' . (int) $sid . '-' . pexp_slugify($title) . '.json';
        $files[] = ['path' => $spath, 'contents' => json_encode($row, JSON_UNESCAPED_UNICODE)];
        foreach (pexp_screen_extract_computed_js($root, $row) as $cf) {
            $files[] = $cf;
        }
    }

    foreach ($scripts as $sid => $row) {
        $title = (string) ($row['title'] ?? 'script');
        $lang = (string) ($row['language'] ?? ($row['executor_language'] ?? 'php'));
        $ext = pexp_language_extension($lang);
        $sbase = (int) $sid . '-' . pexp_slugify($title);
        $code = (string) ($row['code'] ?? '');
        $files[] = ['path' => $root . '/scripts/' . $sbase . '.' . $ext, 'contents' => $code];
        $metaRow = $row;
        unset($metaRow['code'], $metaRow['executor_config']);
        $files[] = ['path' => $root . '/scripts/meta/' . $sbase . '.json', 'contents' => json_encode($metaRow, JSON_UNESCAPED_UNICODE)];
    }

    foreach ($executors as $eid => $row) {
        $title = (string) ($row['title'] ?? 'executor');
        $eb = $root . '/script_executors/' . (int) $eid . '-' . pexp_slugify($title);
        $recipe = isset($row['config']) ? (string) $row['config'] : '';
        if ($recipe !== '') {
            $files[] = ['path' => $eb . '.composer-recipe.txt', 'contents' => $recipe];
        }
        $metaRow = $row;
        unset($metaRow['config']);
        $files[] = ['path' => $eb . '.json', 'contents' => json_encode($metaRow, JSON_UNESCAPED_UNICODE)];
    }

    $manifest = [
        'export_version' => 1,
        'process_id' => $pid,
        'screen_ids' => array_keys($screens),
        'script_ids' => array_keys($scripts),
        'executor_ids' => array_keys($executors),
        'notes' => 'Screens include nested FormNestedScreen refs; scripts include BPMN scriptTask + watchers.',
    ];
    $files[] = ['path' => $root . '/manifest.json', 'contents' => json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)];

    return $files;
}


// --- Forte asset helpers (same API as Forte_Code_Backup_Batch) ---
const FORTE_EXPORT_CHUNK_MAX = 50;
const FORTE_LIST_MAX = 100;
/** API fetch page size for export_all scripts (full code rows). */
const FORTE_EXPORT_ALL_PER_PAGE_SCRIPTS = 15;
/** API/SQL fetch page size for export_all screens. */
const FORTE_EXPORT_ALL_PER_PAGE_SCREENS = 50;
/** Refuse browser download when the zip exceeds this (bytes). */
const FORTE_EXPORT_ALL_MAX_BYTES = 104857600;

/**
 * @return array{0:string,1:string} host, token
 */
function forte_resolve_api(array $data, string $target): array
{
    $target = strtolower($target) === 'prod' ? 'prod' : 'dev';
    $hostChain = [];
    $tokenChain = [];
    $env = $data['_env'] ?? [];
    if (!is_array($env)) {
        $env = [];
    }
    if ($target === 'prod') {
        $hostChain = [
            $data['api_host_prod'] ?? null,
            $data['production_api_host'] ?? null,
            $data['PM_PROD_API_HOST'] ?? null,
            $env['api_host_prod'] ?? null,
            $env['PM_PROD_API_HOST'] ?? null,
        ];
        foreach (['FORTE_PRODUCTION_API_HOST', 'PM_PROD_API_HOST', 'DLSU_PRODUCTION_API_HOST'] as $ev) {
            $g = getenv($ev);
            $hostChain[] = ($g !== false && $g !== '') ? $g : null;
        }
        $tokenChain = [
            $data['api_token_prod'] ?? null,
            $data['production_api_token'] ?? null,
            $data['PM_PROD_API_TOKEN'] ?? null,
            $env['api_token_prod'] ?? null,
            $env['PM_PROD_API_TOKEN'] ?? null,
        ];
        foreach (['FORTE_PRODUCTION_API_TOKEN', 'PM_PROD_API_TOKEN', 'DLSU_PRODUCTION_API_TOKEN'] as $ev) {
            $g = getenv($ev);
            $tokenChain[] = ($g !== false && $g !== '') ? $g : null;
        }
    } else {
        $hostChain = [
            $data['api_host_dev'] ?? null,
            $data['API_HOST'] ?? null,
            $env['API_HOST'] ?? null,
            $env['api_host_dev'] ?? null,
        ];
        foreach (['API_HOST', 'FORTE_DEV_API_HOST'] as $ev) {
            $g = getenv($ev);
            $hostChain[] = ($g !== false && $g !== '') ? $g : null;
        }
        $tokenChain = [
            $data['api_token_dev'] ?? null,
            $data['api_token'] ?? null,
            $env['API_TOKEN'] ?? null,
            $env['api_token_dev'] ?? null,
        ];
        foreach (['API_TOKEN', 'FORTE_DEV_API_TOKEN'] as $ev) {
            $g = getenv($ev);
            $tokenChain[] = ($g !== false && $g !== '') ? $g : null;
        }
    }
    $host = '';
    foreach ($hostChain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $host = rtrim(trim((string) $v), '/');
        if ($host !== '') {
            break;
        }
    }
    $token = '';
    foreach ($tokenChain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $token = trim((string) $v);
        if ($token !== '') {
            break;
        }
    }
    return [$host, $token];
}

function forte_slugify(string $title): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $title);
    if ($t === false) {
        $t = $title;
    }
    $t = strtolower((string) $t);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    $t = trim((string) $t, '-');
    return $t !== '' ? $t : 'asset';
}

function forte_language_extension(string $language): string
{
    $map = [
        'php' => 'php',
        'javascript' => 'js',
        'python' => 'py',
        'csharp' => 'cs',
        'java' => 'java',
        'javascript-ssr' => 'mjs',
        'lua' => 'lua',
        'r' => 'r',
    ];
    $k = strtolower(trim($language));
    return $map[$k] ?? 'txt';
}

function forte_clamp_int($v, int $min, int $max): int
{
    $n = (int) $v;
    if ($n < $min) {
        $n = $min;
    }
    if ($n > $max) {
        $n = $max;
    }
    return $n;
}

/**
 * Parse DataTables {start,length} or {page,per_page} into 1-based page and per_page.
 *
 * @return array{0:int,1:int} page, per_page
 */
function forte_parse_paging(array $data, int $defaultPerPage, int $maxPerPage): array
{
    $drawStyle = isset($data['start']) || isset($data['length']);
    if ($drawStyle) {
        $start = max(0, (int) ($data['start'] ?? 0));
        $length = forte_clamp_int($data['length'] ?? $defaultPerPage, 1, $maxPerPage);
        $page = (int) floor($start / $length) + 1;
        $perPage = $length;
        return [$page, $perPage];
    }
    $page = forte_clamp_int($data['page'] ?? 1, 1, 100000);
    $perPage = forte_clamp_int($data['per_page'] ?? $defaultPerPage, 1, $maxPerPage);
    return [$page, $perPage];
}

/**
 * Single GET page from PM API.
 *
 * @return array{data:array,meta:array,raw_total:int}
 */
/**
 * Same ordering as core index(): avoid ambiguous "title" when screens join categories; scripts join categories.
 *
 * @return array<string,string>
 */
function forte_api_list_query_defaults(string $entity): array
{
    if ($entity === 'screens') {
        return ['order_by' => 'screens.id', 'order_direction' => 'ASC'];
    }
    if ($entity === 'scripts') {
        return ['order_by' => 'scripts.id', 'order_direction' => 'ASC'];
    }
    return ['order_by' => 'id', 'order_direction' => 'ASC'];
}

/**
 * JSON:API style { id, attributes: { ... } } â†’ flat row for DataTables.
 *
 * @param array<string,mixed>|null $row
 * @return array<string,mixed>
 */
function forte_flatten_api_row(?array $row): array
{
    if (!is_array($row)) {
        return [];
    }
    if (isset($row['attributes']) && is_array($row['attributes'])) {
        $out = [];
        if (array_key_exists('id', $row)) {
            $out['id'] = $row['id'];
        }
        foreach ($row['attributes'] as $ak => $av) {
            $out[$ak] = $av;
        }
        return $out;
    }
    return $row;
}

/**
 * Lower-bound row count when the API omits meta.total (inexact; prefer forte_api_probe_total).
 *
 * @param array<string,mixed> $json
 * @param array<string,mixed> $meta
 * @param array<int,mixed> $chunk
 */
function forte_api_resolve_total(array $json, array $meta, array $chunk, int $perPage, int $page): int
{
    foreach (['total', 'Total'] as $tk) {
        if (isset($meta[$tk]) && is_numeric($meta[$tk])) {
            return max(0, (int) $meta[$tk]);
        }
    }
    if (isset($json['total']) && is_numeric($json['total'])) {
        return max(0, (int) $json['total']);
    }
    $lp = (int) ($meta['last_page'] ?? 0);
    $pp = (int) ($meta['per_page'] ?? $perPage);
    $cp = (int) ($meta['current_page'] ?? $page);
    if ($pp < 1) {
        $pp = $perPage;
    }
    if ($lp > 0 && $pp > 0) {
        if ($cp >= $lp) {
            return max(0, ($lp - 1) * $pp + count($chunk));
        }

        return max(count($chunk), ($cp - 1) * $pp + count($chunk));
    }
    $tp = (int) ($meta['total_pages'] ?? 0);
    if ($tp > 0 && $pp > 0 && $cp >= $tp) {
        return max(0, ($tp - 1) * $pp + count($chunk));
    }

    return count($chunk);
}

/**
 * Exact total: GET page=1&per_page=1 â€” with per_page 1, meta.last_page equals global total rows.
 *
 * @param array<string,string> $extraQuery
 */
function forte_api_probe_total(string $host, string $token, string $path, array $extraQuery = []): int
{
    $p = forte_api_get_one_page($host, $token, $path, 1, 1, $extraQuery, false);
    $meta = $p['meta'] ?? [];
    if (isset($meta['total']) && is_numeric($meta['total'])) {
        return max(0, (int) $meta['total']);
    }
    $lp = (int) ($meta['last_page'] ?? 0);
    $pp = (int) ($meta['per_page'] ?? 1);

    return ($lp > 0 && $pp === 1) ? max(0, $lp) : (int) ($p['raw_total'] ?? 0);
}

function forte_api_get_one_page(string $host, string $token, string $path, int $page, int $perPage, array $extraQuery = [], bool $probeTotal = false): array
{
    if (!class_exists('\GuzzleHttp\Client')) {
        throw new \RuntimeException('GuzzleHttp\Client is required.');
    }
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 120]);
    $q = array_merge(['page' => $page, 'per_page' => $perPage], $extraQuery);
    $qs = http_build_query($q);
    $url = $host . $path . (strpos($path, '?') !== false ? '&' : '?') . $qs;
    try {
        $res = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
    } catch (\Throwable $e) {
        return [
            'data' => [],
            'meta' => ['http_error' => $e->getMessage()],
            'raw_total' => 0,
            'http_status' => 0,
        ];
    }
    $code = $res->getStatusCode();
    $json = json_decode($res->getBody()->getContents(), true);
    unset($res);
    if (!is_array($json)) {
        return ['data' => [], 'meta' => ['http_status' => $code], 'raw_total' => 0, 'http_status' => $code];
    }
    $chunk = $json['data'] ?? [];
    if (!is_array($chunk)) {
        $chunk = [];
    }
    foreach ($chunk as $i => $cell) {
        $chunk[$i] = forte_flatten_api_row(is_array($cell) ? $cell : []);
    }
    $meta = is_array($json['meta'] ?? null) ? $json['meta'] : [];
    $hasExplicit = isset($meta['total']) || isset($meta['Total'])
        || (isset($json['total']) && is_numeric($json['total']));
    $total = forte_api_resolve_total($json, $meta, $chunk, $perPage, $page);
    if ($total < count($chunk)) {
        $total = count($chunk);
    }
    if (!$hasExplicit && $probeTotal) {
        $total = forte_api_probe_total($host, $token, $path, $extraQuery);
        if ($total < count($chunk)) {
            $total = count($chunk);
        }
    }
    return ['data' => $chunk, 'meta' => $meta, 'raw_total' => $total, 'http_status' => $code];
}

function forte_strip_script_code(array $rows): array
{
    foreach ($rows as &$r) {
        if (is_array($r) && array_key_exists('code', $r)) {
            unset($r['code']);
        }
    }
    unset($r);
    return $rows;
}

function forte_strip_screen_heavy(array $rows): array
{
    foreach ($rows as &$r) {
        if (!is_array($r)) {
            continue;
        }
        foreach (['config', 'computed', 'watchers', 'translations', 'custom_css', 'content'] as $k) {
            if (array_key_exists($k, $r)) {
                unset($r[$k]);
            }
        }
    }
    unset($r);
    return $rows;
}

/**
 * Pro Service Tools / SQL may return one row as a single object, or rows as numeric vectors.
 *
 * @param mixed $rows
 * @return array<int,array<string,mixed>>
 */
function forte_ensure_list_of_rows($rows): array
{
    if (!is_array($rows)) {
        return [];
    }
    if ($rows === []) {
        return [];
    }
    if (isset($rows['__error__'])) {
        return [];
    }
    $keys = array_keys($rows);
    $indexed = $keys === range(0, count($rows) - 1);
    if (!$indexed) {
        return [$rows];
    }
    return $rows;
}

function forte_row_keys_all_numeric(array $row): bool
{
    if ($row === []) {
        return false;
    }
    foreach (array_keys($row) as $k) {
        if (is_int($k)) {
            continue;
        }
        if (is_string($k) && ctype_digit((string) $k)) {
            continue;
        }
        return false;
    }
    return true;
}

/**
 * @param array<int|string,mixed> $row
 * @return array<string,mixed>
 */
function forte_numeric_vector_to_assoc(array $row, array $cols): array
{
    $vals = array_values($row);
    $o = [];
    $n = min(count($cols), count($vals));
    for ($i = 0; $i < $n; $i++) {
        $o[$cols[$i]] = $vals[$i];
    }
    return $o;
}

/**
 * @param array<string,mixed>|null $r
 * @return array<string,mixed>
 */
function forte_lower_row_keys(?array $r): array
{
    if (!is_array($r)) {
        return [];
    }
    $o = [];
    foreach ($r as $k => $v) {
        $o[strtolower((string) $k)] = $v;
    }
    return $o;
}

/**
 * Column order must match forte_sql_list_*_thin SELECT lists.
 *
 * @return array<int,string>
 */
function forte_thin_column_order(string $entity): array
{
    if ($entity === 'scripts') {
        return ['id', 'title', 'language', 'updated_at', 'script_executor_id'];
    }
    if ($entity === 'screens') {
        return ['id', 'title', 'type', 'updated_at', 'created_at'];
    }
    return ['id', 'title', 'language', 'updated_at', 'created_at'];
}

/**
 * @param array<int,array<string,mixed>>|array<string,mixed> $rows
 * @return array<int,array<string,mixed>>
 */
function forte_normalize_list_rows_for_datatable($rows, string $entity): array
{
    if (!is_array($rows)) {
        $rows = [];
    }
    $rows = forte_ensure_list_of_rows($rows);
    $cols = forte_thin_column_order($entity);
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $row = forte_flatten_api_row($row);
        if (forte_row_keys_all_numeric($row)) {
            $row = forte_numeric_vector_to_assoc($row, $cols);
        } else {
            $row = forte_lower_row_keys($row);
        }
        $out[] = $row;
    }
    return $out;
}

/**
 * @param mixed $rows
 */
function forte_sql_first_cnt($rows): int
{
    $rows = forte_ensure_list_of_rows($rows);
    if ($rows === [] || !isset($rows[0]) || !is_array($rows[0])) {
        return 0;
    }
    $r = $rows[0];
    if (forte_row_keys_all_numeric($r)) {
        $v = array_values($r);
        return (int) ($v[0] ?? 0);
    }
    $r = forte_lower_row_keys($r);
    foreach ($r as $k => $v) {
        if (stripos((string) $k, 'cnt') !== false || stripos((string) $k, 'count') !== false) {
            return (int) $v;
        }
    }
    return (int) ($r['cnt'] ?? 0);
}

function forte_normalize_export_rows(array $rows): array
{
    $rows = forte_ensure_list_of_rows($rows);
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $out[] = forte_lower_row_keys(forte_flatten_api_row($row));
    }
    return $out;
}

/**
 * Export computed entries whose type is javascript (skip expression / plain calcs).
 *
 * @param array<string,mixed> $row Normalized export row (lower keys).
 *
 * @return array<int,array{path:string,contents:string}>
 */
function forte_screen_extract_computed_javascript(string $root, array $row): array
{
    $files = [];
    $id = $row['id'] ?? 'screen';
    $title = (string) ($row['title'] ?? 'screen');
    $base = $id . '-' . forte_slugify($title);
    $computed = $row['computed'] ?? null;
    if (is_string($computed) && $computed !== '') {
        $decoded = json_decode($computed, true);
        $computed = is_array($decoded) ? $decoded : null;
    }
    if (!is_array($computed)) {
        return $files;
    }
    $j = 0;
    foreach ($computed as $c) {
        if (!is_array($c)) {
            continue;
        }
        $type = strtolower(trim((string) ($c['type'] ?? '')));
        if ($type !== 'javascript') {
            continue;
        }
        $body = $c['formula'] ?? $c['script'] ?? $c['value'] ?? '';
        if (!is_string($body)) {
            $body = is_scalar($body) ? (string) $body : json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        $name = (string) ($c['name'] ?? $c['property'] ?? $c['field'] ?? ('calc-' . $j));
        $safe = forte_slugify($name);
        if ($safe === '') {
            $safe = 'calc-' . $j;
        }
        $path = $root . '/screens/computed/' . $base . '-' . $j . '-' . $safe . '.js';
        $files[] = ['path' => $path, 'contents' => $body];
        $j++;
    }

    return $files;
}

/**
 * @return array{meta:array,files:array<int,array{path:string,contents:string}>}
 */
function forte_build_files_for_chunk(string $root, string $entity, array $rows): array
{
    $files = [];
    $n = 0;
    if ($entity === 'executors') {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $row['id'] ?? 'x';
            $title = (string) ($row['title'] ?? 'executor');
            $base = $root . '/script_executors/' . $id . '-' . forte_slugify($title);
            $recipe = isset($row['config']) ? (string) $row['config'] : '';
            if ($recipe !== '') {
                $files[] = ['path' => $base . '.composer-recipe.txt', 'contents' => $recipe];
            }
            $metaRow = $row;
            unset($metaRow['config']);
            $files[] = ['path' => $base . '.json', 'contents' => json_encode($metaRow, JSON_UNESCAPED_UNICODE)];
            $n++;
        }
        return ['meta' => ['entity' => 'executors', 'rows' => $n], 'files' => $files];
    }
    if ($entity === 'screens') {
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = $row['id'] ?? 'screen';
            $title = (string) ($row['title'] ?? 'screen');
            $path = $root . '/screens/' . $id . '-' . forte_slugify($title) . '.json';
            $files[] = ['path' => $path, 'contents' => json_encode($row, JSON_UNESCAPED_UNICODE)];
            foreach (forte_screen_extract_computed_javascript($root, $row) as $cf) {
                $files[] = $cf;
            }
            $n++;
        }
        return ['meta' => ['entity' => 'screens', 'rows' => $n], 'files' => $files];
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = $row['id'] ?? 0;
        $title = (string) ($row['title'] ?? 'script');
        $lang = (string) ($row['language'] ?? ($row['executor_language'] ?? 'php'));
        $ext = forte_language_extension($lang);
        $base = $id . '-' . forte_slugify($title);
        $code = (string) ($row['code'] ?? '');
        $files[] = ['path' => $root . '/scripts/' . $base . '.' . $ext, 'contents' => $code];
        $metaRow = $row;
        unset($metaRow['code'], $metaRow['executor_config']);
        $files[] = ['path' => $root . '/scripts/meta/' . $base . '.json', 'contents' => json_encode($metaRow, JSON_UNESCAPED_UNICODE)];
        $n++;
    }
    return ['meta' => ['entity' => 'scripts', 'rows' => $n], 'files' => $files];
}

/**
 * One POSIX ustar 512-byte header (checksum + ustar magic).
 */
function forte_ustar_header(string $name, int $size, int $mtime): string
{
    $name = pexp_normalize_archive_entry_name($name);
    if (strlen($name) > 99) {
        $name = substr($name, -99);
    }
    $h = str_repeat("\0", 512);
    $h = substr_replace($h, str_pad(substr($name, 0, 100), 100, "\0"), 0, 100);
    $oct = function (int $n, int $width): string {
        $s = decoct($n);
        if (strlen($s) >= $width) {
            $s = substr($s, 0, $width - 1);
        }

        return str_pad($s, $width - 1, '0', STR_PAD_LEFT) . "\0";
    };
    $h = substr_replace($h, $oct(0644, 8), 100, 8);
    $h = substr_replace($h, $oct(0, 8), 108, 8);
    $h = substr_replace($h, $oct(0, 8), 116, 8);
    $h = substr_replace($h, $oct($size, 12), 124, 12);
    $h = substr_replace($h, $oct($mtime, 12), 136, 12);
    $h = substr_replace($h, '        ', 148, 8);
    $h = substr_replace($h, '0', 156, 1);
    $h = substr_replace($h, 'ustar', 257, 5);
    $h = substr_replace($h, chr(0), 262, 1);
    $h = substr_replace($h, '00', 263, 2);
    $sum = 0;
    for ($i = 0; $i < 512; $i++) {
        $sum += ord($h[$i]);
    }
    $h = substr_replace($h, sprintf("%06o\0 ", $sum), 148, 8);

    return $h;
}

/**
 * @param array<int,array{path:string,contents:string}> $files
 */
function forte_ustar_one_entry(string $path, string $contents): string
{
    $path = pexp_normalize_archive_entry_name($path);
    if ($path === '') {
        return '';
    }
    $mtime = time();
    $header = forte_ustar_header($path, strlen($contents), $mtime);
    $pad = (512 - (strlen($contents) % 512)) % 512;

    return $header . $contents . str_repeat("\0", $pad);
}

/**
 * @param array<int,array{path:string,contents:string}> $files
 */
function forte_build_ustar_tar(array $files): string
{
    $out = '';
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $p = pexp_normalize_archive_entry_name((string) ($f['path'] ?? ''));
        if ($p === '') {
            continue;
        }
        $out .= forte_ustar_one_entry($p, (string) ($f['contents'] ?? ''));
    }
    $out .= str_repeat("\0", 1024);

    return $out;
}

/**
 * @param array<int,array{path:string,contents:string}> $files
 */
function forte_build_targz_binary(array $files): ?string
{
    if (!function_exists('gzencode')) {
        return null;
    }
    $tar = forte_build_ustar_tar($files);
    if ($tar === '' || strlen($tar) <= 1024) {
        return null;
    }

    $gz = gzencode($tar, 6);
    unset($tar);

    return $gz === false ? null : $gz;
}

/**
 * Gzip a file on disk to another path (streaming when gzopen/gzwrite exist).
 */
function forte_gzip_file_streaming(string $sourcePath, string $destGzipPath): bool
{
    if (!is_readable($sourcePath)) {
        return false;
    }
    if (function_exists('gzopen') && function_exists('gzwrite')) {
        $in = @fopen($sourcePath, 'rb');
        if ($in === false) {
            return false;
        }
        $out = @gzopen($destGzipPath, 'wb9');
        if ($out === false) {
            fclose($in);

            return false;
        }
        while (!feof($in)) {
            $chunk = fread($in, 524288);
            if ($chunk === false) {
                break;
            }
            if ($chunk !== '' && gzwrite($out, $chunk) === false) {
                break;
            }
        }
        gzclose($out);
        fclose($in);

        return is_file($destGzipPath) && (int) filesize($destGzipPath) > 0;
    }
    if (!function_exists('gzencode')) {
        return false;
    }
    $raw = @file_get_contents($sourcePath);
    if ($raw === false) {
        return false;
    }
    $gz = gzencode($raw, 6);
    unset($raw);
    if ($gz === false) {
        return false;
    }
    $ok = @file_put_contents($destGzipPath, $gz) !== false;
    unset($gz);

    return $ok;
}

/**
 * @param array<int,array{path:string,contents:string}> $files
 * @return array{0:?string,1:string} binary, kind (zip|tar_gz)
 */
function forte_build_archive_binary(array $files, string $mode): array
{
    $mode = strtolower(trim($mode));
    if ($mode === 'zip') {
        $z = forte_build_zip_binary($files);
        if ($z !== null && $z !== '') {
            return [$z, 'zip'];
        }

        return [null, 'zip'];
    }
    if ($mode === 'tar_gz' || $mode === 'tgz') {
        $t = forte_build_targz_binary($files);
        if ($t !== null && $t !== '') {
            return [$t, 'tar_gz'];
        }

        return [null, 'tar_gz'];
    }
    // auto: zip first, then tar.gz
    $z = forte_build_zip_binary($files);
    if ($z !== null && $z !== '') {
        return [$z, 'zip'];
    }
    $t = forte_build_targz_binary($files);
    if ($t !== null && $t !== '') {
        return [$t, 'tar_gz'];
    }

    return [null, ''];
}

/**
 * POST multipart file to ProcessMaker request files API (same as createRequestFile in UI).
 *
 * @return array{success:bool,file_upload_id?:int,error?:string,http_status?:int}
 */
function forte_pm_upload_request_file(string $host, string $token, int $requestId, string $fileBytes, string $uploadFilename, string $dataName): array
{
    if (!class_exists('\GuzzleHttp\Client')) {
        return ['success' => false, 'error' => 'GuzzleHttp\Client is required.'];
    }
    $url = rtrim($host, '/') . '/requests/' . $requestId . '/files';
    $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 300]);
    try {
        $res = $client->request('POST', $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
            'query' => ['data_name' => $dataName],
            'multipart' => [
                [
                    'name' => 'file',
                    'filename' => $uploadFilename,
                    'contents' => $fileBytes,
                ],
            ],
        ]);
        $code = $res->getStatusCode();
        $body = (string) $res->getBody();
        $json = json_decode($body, true);
        if ($code >= 200 && $code < 300 && is_array($json) && isset($json['fileUploadId'])) {
            return ['success' => true, 'file_upload_id' => (int) $json['fileUploadId'], 'http_status' => $code];
        }
        $err = is_array($json) ? json_encode($json, JSON_UNESCAPED_UNICODE) : $body;

        return ['success' => false, 'error' => $err !== '' ? $err : ('HTTP ' . $code), 'http_status' => $code];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Build a ZIP in a temp file from path+contents pairs; returns binary string or null on failure.
 *
 * @param array<int,array{path:string,contents:string}> $files
 */
function forte_build_zip_binary(array $files): ?string
{
    if (!class_exists('\ZipArchive')) {
        return null;
    }
    $uniq = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : str_replace('.', '', uniqid('', true));
    $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'forte_chunk_' . $uniq . '.zip';
    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        @unlink($zipPath);

        return null;
    }
    foreach ($files as $f) {
        if (!is_array($f)) {
            continue;
        }
        $name = pexp_normalize_archive_entry_name((string) ($f['path'] ?? ''));
        if ($name === '') {
            continue;
        }
        $zip->addFromString($name, (string) ($f['contents'] ?? ''));
    }
    $zip->close();
    if (!is_file($zipPath)) {
        return null;
    }
    $bin = file_get_contents($zipPath);
    @unlink($zipPath);
    return $bin === false ? null : $bin;
}

function forte_resolve_sql_path(array $data): string
{
    $chain = [$data['api_sql_path'] ?? null];
    $env = $data['_env'] ?? [];
    if (is_array($env)) {
        $chain[] = $env['API_SQL'] ?? null;
    }
    $chain[] = getenv('API_SQL') ?: null;
    foreach ($chain as $v) {
        if ($v === null || $v === '') {
            continue;
        }
        $p = trim((string) $v);
        if ($p !== '') {
            return ($p[0] === '/') ? $p : '/' . $p;
        }
    }
    return '/admin/package-proservice-tools/sql';
}

/**
 * @return array<int,array<string,mixed>>|array{__error__:string}
 */
function forte_call_sql(string $endpoint, string $token, string $sql): array
{
    if ($endpoint === '' || $token === '') {
        return ['__error__' => 'SQL endpoint or bearer token is empty.'];
    }
    if (!class_exists('\GuzzleHttp\Client')) {
        return ['__error__' => 'GuzzleHttp\Client is required.'];
    }
    static $client = null;
    if ($client === null) {
        $client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 180]);
    }
    try {
        $res = $client->request('POST', $endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode(['SQL' => base64_encode($sql)]),
        ]);
        $json = json_decode($res->getBody()->getContents(), true);
        unset($res);
        if (is_array($json) && isset($json['output']) && is_array($json['output'])) {
            return $json['output'];
        }
        if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
            return $json['data'];
        }
        if (is_array($json) && isset($json['result']) && is_array($json['result'])) {
            return $json['result'];
        }
        if (is_array($json) && isset($json['rows']) && is_array($json['rows'])) {
            return $json['rows'];
        }
        return is_array($json) ? $json : [];
    } catch (\Throwable $e) {
        return ['__error__' => $e->getMessage()];
    }
}

function forte_sql_where_scripts(): string
{
    return '(s.`key` IS NULL OR s.`key` = ' . "''" . ') '
        . 'AND COALESCE(s.is_template, 0) = 0 '
        . 'AND (s.asset_type IS NULL OR s.asset_type = ' . "''" . ')';
}

function forte_sql_where_screens(): string
{
    return 'COALESCE(is_template, 0) = 0 AND (asset_type IS NULL OR asset_type = ' . "''" . ')';
}

function forte_sql_count_scripts(): string
{
    return 'SELECT COUNT(*) AS cnt FROM scripts s WHERE ' . forte_sql_where_scripts();
}

function forte_sql_count_screens(): string
{
    return 'SELECT COUNT(*) AS cnt FROM screens WHERE ' . forte_sql_where_screens();
}

function forte_sql_count_executors(): string
{
    return 'SELECT COUNT(*) AS cnt FROM script_executors WHERE COALESCE(is_system, 0) = 0';
}

function forte_sql_list_scripts_thin(int $offset, int $limit): string
{
    return 'SELECT s.id, s.title, s.language, s.updated_at, s.script_executor_id '
        . 'FROM scripts s WHERE ' . forte_sql_where_scripts()
        . ' ORDER BY s.id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
}

function forte_sql_list_scripts_full(int $offset, int $limit): string
{
    return 'SELECT s.id, s.`key`, s.title, s.description, s.language, s.code, s.timeout, '
        . 's.script_executor_id, s.script_category_id, s.run_as_user_id, '
        . 's.retry_attempts, s.retry_wait_time, s.created_at, s.updated_at, '
        . 'e.language AS executor_language, e.title AS executor_title, '
        . 'e.config AS executor_config, e.type AS executor_type '
        . 'FROM scripts s '
        . 'LEFT JOIN script_executors e ON e.id = s.script_executor_id '
        . 'WHERE ' . forte_sql_where_scripts()
        . ' ORDER BY s.id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
}

function forte_sql_list_screens_thin(int $offset, int $limit): string
{
    return 'SELECT id, title, type, updated_at, created_at FROM screens WHERE '
        . forte_sql_where_screens() . ' ORDER BY id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
}

function forte_sql_list_screens_full(int $offset, int $limit): string
{
    return 'SELECT * FROM screens WHERE ' . forte_sql_where_screens()
        . ' ORDER BY id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
}

function forte_sql_list_executors_thin(int $offset, int $limit): string
{
    return 'SELECT id, title, language, updated_at, created_at FROM script_executors '
        . 'WHERE COALESCE(is_system, 0) = 0 ORDER BY id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
}

function forte_sql_list_executors_full(int $offset, int $limit): string
{
    return 'SELECT * FROM script_executors WHERE COALESCE(is_system, 0) = 0 '
        . 'ORDER BY id LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
}

function forte_entity_path(string $entity): ?string
{
    if ($entity === 'scripts') {
        return '/scripts';
    }
    if ($entity === 'screens') {
        return '/screens';
    }
    if ($entity === 'executors') {
        return '/script-executors';
    }
    return null;
}


/**
 * Forte-style list / export_chunk / export_all for scripts, screens, executors.
 *
 * @return array<string,mixed>
 */
function pexp_forte_asset_dispatch(array $data, string $host, string $token, string $mode, string $action, string $target, string $rootFolder): array
{
    $entity = strtolower(trim((string) ($data['entity'] ?? '')));
    $allowedForAction = $action === 'export_all'
        ? ['scripts', 'screens']
        : ['scripts', 'screens', 'executors'];
    if (!in_array($entity, $allowedForAction, true)) {
        return [
            'success' => false,
            'error' => $action === 'export_all'
                ? 'entity must be scripts or screens for export_all.'
                : 'entity must be scripts, screens, or executors for list/export_chunk.',
            'error_code' => 'E_ENTITY',
        ];
    }

    $path = forte_entity_path($entity);
    if ($path === null) {
        return ['success' => false, 'error' => 'Bad entity', 'error_code' => 'E_ENTITY'];
    }

    if ($action === 'export_all') {
        $useZip = class_exists('\ZipArchive');
        if (!$useZip && !function_exists('gzencode')) {
            return [
                'success' => false,
                'error' => 'export_all needs PHP ZipArchive (php-zip) or zlib (gzencode) for a .tar.gz fallback.',
                'error_code' => 'E_ARCHIVE',
            ];
        }
        $exportDest = strtolower(trim((string) ($data['export_destination'] ?? 'browser')));
        if ($exportDest !== 'request') {
            $exportDest = 'browser';
        }
        $perPage = $entity === 'scripts' ? FORTE_EXPORT_ALL_PER_PAGE_SCRIPTS : FORTE_EXPORT_ALL_PER_PAGE_SCREENS;
        $defaults = forte_api_list_query_defaults($entity);
        $uniq = function_exists('random_bytes') ? bin2hex(random_bytes(8)) : str_replace('.', '', uniqid('', true));
        $tmpDir = sys_get_temp_dir();
        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'forte_export_all_' . $uniq . '.zip';
        $tarPath = $tmpDir . DIRECTORY_SEPARATOR . 'forte_export_all_' . $uniq . '.tar';
        $gzPath = $tmpDir . DIRECTORY_SEPARATOR . 'forte_export_all_' . $uniq . '.tar.gz';
        $zip = null;
        $tf = null;
        if ($useZip) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return [
                    'success' => false,
                    'error' => 'Could not create temporary zip for export_all.',
                    'error_code' => 'E_ZIP',
                ];
            }
        } else {
            $tf = @fopen($tarPath, 'wb');
            if ($tf === false) {
                return [
                    'success' => false,
                    'error' => 'Could not create temporary tar file for export_all.',
                    'error_code' => 'E_ARCHIVE',
                ];
            }
        }
        $pageNum = 1;
        $fileEntries = 0;
        try {
            while (true) {
                if ($mode === 'sql') {
                    $offset = ($pageNum - 1) * $perPage;
                    $endpoint = rtrim($host, '/') . forte_resolve_sql_path($data);
                    if ($entity === 'scripts') {
                        $sql = forte_sql_list_scripts_full($offset, $perPage);
                    } else {
                        $sql = forte_sql_list_screens_full($offset, $perPage);
                    }
                    $rows = forte_call_sql($endpoint, $token, $sql);
                    if (isset($rows['__error__'])) {
                        if ($useZip) {
                            $zip->close();
                            @unlink($zipPath);
                        } else {
                            fclose($tf);
                            @unlink($tarPath);
                        }

                        return ['success' => false, 'error' => (string) $rows['__error__'], 'error_code' => 'E_SQL'];
                    }
                    if (!is_array($rows)) {
                        $rows = [];
                    }
                } else {
                    $pack = forte_api_get_one_page($host, $token, $path, $pageNum, $perPage, $defaults, false);
                    $rows = $pack['data'];
                }
                if (!is_array($rows)) {
                    $rows = [];
                }
                $n = count(forte_ensure_list_of_rows($rows));
                if ($n === 0) {
                    break;
                }
                $rows = forte_normalize_export_rows($rows);
                $built = forte_build_files_for_chunk($rootFolder, $entity, $rows);
                unset($rows);
                foreach ($built['files'] as $f) {
                    if (!is_array($f)) {
                        continue;
                    }
                    $name = pexp_normalize_archive_entry_name((string) ($f['path'] ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $body = (string) ($f['contents'] ?? '');
                    if ($useZip) {
                        $zip->addFromString($name, $body);
                    } else {
                        $block = forte_ustar_one_entry($name, $body);
                        if ($block !== '') {
                            fwrite($tf, $block);
                        }
                    }
                    $fileEntries++;
                }
                unset($built);
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
                if ($n < $perPage) {
                    break;
                }
                $pageNum++;
            }
        } catch (\Throwable $e) {
            if ($useZip) {
                $zip->close();
                @unlink($zipPath);
            } else {
                if (is_resource($tf)) {
                    fclose($tf);
                }
                @unlink($tarPath);
                @unlink($gzPath);
            }
            throw $e;
        }
        if ($fileEntries < 1) {
            if ($useZip) {
                $zip->close();
                @unlink($zipPath);
            } else {
                if (is_resource($tf)) {
                    fclose($tf);
                }
                @unlink($tarPath);
            }

            return [
                'success' => false,
                'error' => 'export_all produced no files.',
                'error_code' => 'E_EMPTY_EXPORT',
            ];
        }
        if ($useZip) {
            $zip->close();
            $workPath = $zipPath;
            $ext = '.zip';
            $mime = 'application/zip';
            $kind = 'zip';
            $exportFmtLabel = 'zip';
        } else {
            fwrite($tf, str_repeat("\0", 1024));
            fclose($tf);
            $tf = null;
            if (!forte_gzip_file_streaming($tarPath, $gzPath)) {
                @unlink($tarPath);
                @unlink($gzPath);

                return [
                    'success' => false,
                    'error' => 'Could not gzip temporary tar for export_all (check zlib).',
                    'error_code' => 'E_ARCHIVE',
                ];
            }
            @unlink($tarPath);
            $workPath = $gzPath;
            $ext = '.tar.gz';
            $mime = 'application/gzip';
            $kind = 'tar_gz';
            $exportFmtLabel = 'tar_gz';
        }
        if (!is_file($workPath)) {
            @unlink($workPath);

            return [
                'success' => false,
                'error' => 'export_all archive missing after build.',
                'error_code' => 'E_ARCHIVE',
            ];
        }
        $size = (int) filesize($workPath);
        if ($size > FORTE_EXPORT_ALL_MAX_BYTES) {
            @unlink($workPath);

            return [
                'success' => false,
                'error' => 'export_all archive exceeds ' . (int) (FORTE_EXPORT_ALL_MAX_BYTES / 1048576) . ' MiB. Export in smaller chunks with export_chunk or attach to request.',
                'error_code' => 'E_TOO_LARGE',
            ];
        }
        $archiveBin = file_get_contents($workPath);
        @unlink($workPath);
        if ($archiveBin === false || $archiveBin === '') {
            return [
                'success' => false,
                'error' => 'Could not read export_all archive.',
                'error_code' => 'E_ARCHIVE',
            ];
        }
        $uploadFilename = $rootFolder . '-all-' . $entity . '-' . gmdate('Ymd-His') . $ext;
        $dataNameRaw = (string) ($data['request_file_data_name'] ?? 'FORTE_CODE_BACKUP_EXPORT');
        $dataName = preg_replace('/[^A-Za-z0-9_]/', '_', $dataNameRaw);
        if ($dataName === '' || strlen($dataName) > 60) {
            $dataName = 'FORTE_CODE_BACKUP_EXPORT';
        }
        if ($exportDest === 'request') {
            $rid = $data['request_id'] ?? null;
            if (is_array($rid) && isset($rid['id'])) {
                $rid = $rid['id'];
            }
            $requestId = is_numeric($rid) ? (int) $rid : 0;
            if ($requestId < 1) {
                return [
                    'success' => false,
                    'error' => 'export_destination=request requires a positive request_id.',
                    'error_code' => 'E_REQUEST_ID',
                ];
            }
            $up = forte_pm_upload_request_file($host, $token, $requestId, $archiveBin, $uploadFilename, $dataName);
            unset($archiveBin);
            if (!$up['success']) {
                return [
                    'success' => false,
                    'error' => 'Request file upload failed: ' . ($up['error'] ?? 'unknown'),
                    'error_code' => 'E_UPLOAD',
                    'http_status' => $up['http_status'] ?? null,
                ];
            }

            return [
                'success' => true,
                'action' => 'export_all',
                'export_destination' => 'request',
                'export_format' => $exportFmtLabel,
                'target' => $target,
                'mode' => $mode,
                'root_folder' => $rootFolder,
                'entity' => $entity,
                'file_count' => $fileEntries,
                'archive_bytes' => $size,
                'request_id' => $requestId,
                'request_file_data_name' => $dataName,
                'file_upload_id' => $up['file_upload_id'],
                'uploaded_file_name' => $uploadFilename,
                'message' => 'Full export attached to the request (data name "' . $dataName . '").',
            ];
        }

        return [
            'success' => true,
            'action' => 'export_all',
            'export_destination' => 'browser',
            'export_format' => $exportFmtLabel,
            'target' => $target,
            'mode' => $mode,
            'root_folder' => $rootFolder,
            'entity' => $entity,
            'file_count' => $fileEntries,
            'archive_bytes' => $size,
            'suggested_filename' => $uploadFilename,
            'archive_kind' => $kind,
            'archive_mime' => $mime,
            'archive_base64' => base64_encode($archiveBin),
            'meta' => [
                'entity' => $entity,
                'pages_walked' => $pageNum,
                'rows_per_page' => $perPage,
                'archive_backend' => $useZip ? 'zip' : 'tar_gz',
            ],
        ];
    }

    $maxPer = $action === 'export_chunk' ? FORTE_EXPORT_CHUNK_MAX : FORTE_LIST_MAX;
    [$page, $perPage] = forte_parse_paging($data, 25, $maxPer);
    if ($mode === 'api' && $entity === 'scripts' && $action === 'export_chunk') {
        $perPage = min($perPage, 15);
    }
    $offset = ($page - 1) * $perPage;
    $draw = isset($data['draw']) ? (int) $data['draw'] : 0;
    if ($draw < 1) {
        $draw = 1;
    }

    if ($action === 'list') {
        $apiDiag = [];
        if ($mode === 'sql') {
            $endpoint = rtrim($host, '/') . forte_resolve_sql_path($data);
            if ($entity === 'scripts') {
                $sql = forte_sql_list_scripts_thin($offset, $perPage);
            } elseif ($entity === 'screens') {
                $sql = forte_sql_list_screens_thin($offset, $perPage);
            } else {
                $sql = forte_sql_list_executors_thin($offset, $perPage);
            }
            $rows = forte_call_sql($endpoint, $token, $sql);
            if (isset($rows['__error__'])) {
                return ['success' => false, 'error' => (string) $rows['__error__'], 'error_code' => 'E_SQL'];
            }
            if (!is_array($rows)) {
                $rows = [];
            }
            $cntSql = $entity === 'scripts' ? forte_sql_count_scripts() : ($entity === 'screens' ? forte_sql_count_screens() : forte_sql_count_executors());
            $cntRows = forte_call_sql($endpoint, $token, $cntSql);
            $total = forte_sql_first_cnt($cntRows);
        } else {
            // Same REST endpoints as core ScriptController / ScreenController / ScriptExecutorController index().
            $pack = forte_api_get_one_page($host, $token, $path, $page, $perPage, forte_api_list_query_defaults($entity), true);
            $rows = $pack['data'];
            if ($entity === 'scripts') {
                $rows = forte_strip_script_code($rows);
            } elseif ($entity === 'screens') {
                $rows = forte_strip_screen_heavy($rows);
            }
            $total = $pack['raw_total'];
            $apiDiag = [
                'api_http_status' => $pack['http_status'] ?? null,
                'api_http_error' => $pack['meta']['http_error'] ?? null,
            ];
        }
        $rows = forte_normalize_list_rows_for_datatable($rows, $entity);
        $total = (int) $total;
        return [
            'success' => true,
            'action' => 'list',
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $rows,
            'meta' => array_merge([
                'entity' => $entity,
                'page' => $page,
                'per_page' => $perPage,
                'target' => $target,
                'mode' => $mode,
                'root_folder' => $rootFolder,
                'resolved_action' => $data['action'] ?? null,
            ], $apiDiag),
        ];
    }

    if ($action === 'export_chunk') {
        $page = forte_clamp_int($page, 1, 100000);
        $perPage = forte_clamp_int($perPage, 1, FORTE_EXPORT_CHUNK_MAX);
        if ($mode === 'api' && $entity === 'scripts') {
            $perPage = min($perPage, 15);
        }
        $offset = ($page - 1) * $perPage;

        if ($mode === 'sql') {
            $endpoint = rtrim($host, '/') . forte_resolve_sql_path($data);
            if ($entity === 'scripts') {
                $sql = forte_sql_list_scripts_full($offset, $perPage);
            } elseif ($entity === 'screens') {
                $sql = forte_sql_list_screens_full($offset, $perPage);
            } else {
                $sql = forte_sql_list_executors_full($offset, $perPage);
            }
            $rows = forte_call_sql($endpoint, $token, $sql);
            if (isset($rows['__error__'])) {
                return ['success' => false, 'error' => (string) $rows['__error__'], 'error_code' => 'E_SQL'];
            }
            if (!is_array($rows)) {
                $rows = [];
            }
        } else {
            $pack = forte_api_get_one_page($host, $token, $path, $page, $perPage, forte_api_list_query_defaults($entity));
            $rows = $pack['data'];
        }
        if (!is_array($rows)) {
            $rows = [];
        }
        $rows = forte_normalize_export_rows($rows);
        $built = forte_build_files_for_chunk($rootFolder, $entity === 'executors' ? 'executors' : $entity, $rows);
        unset($rows);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        $exportFmt = strtolower(trim((string) ($data['export_format'] ?? 'tar_gz')));
        if (!in_array($exportFmt, ['json', 'zip', 'tar_gz', 'tgz', 'auto'], true)) {
            $exportFmt = 'tar_gz';
        }
        if ($exportFmt === 'tgz') {
            $exportFmt = 'tar_gz';
        }

        $exportDest = strtolower(trim((string) ($data['export_destination'] ?? 'browser')));
        if ($exportDest !== 'request') {
            $exportDest = 'browser';
        }

        $baseName = $rootFolder . '-' . $entity . '-p' . $page . '-n' . $perPage;
        if ($exportFmt === 'json') {
            return [
                'success' => true,
                'action' => 'export_chunk',
                'export_format' => 'json',
                'export_destination' => $exportDest,
                'target' => $target,
                'mode' => $mode,
                'root_folder' => $rootFolder,
                'entity' => $entity,
                'page' => $page,
                'per_page' => $perPage,
                'suggested_filename' => $baseName . '.json',
                'meta' => $built['meta'],
                'files' => $built['files'],
            ];
        }

        $filesList = $built['files'];
        if ($filesList === []) {
            return [
                'success' => false,
                'error' => 'No files in this page to export.',
                'error_code' => 'E_EMPTY_EXPORT',
            ];
        }

        $archiveMode = $exportFmt === 'auto' ? 'auto' : $exportFmt;
        [$archiveBin, $archiveKind] = forte_build_archive_binary($filesList, $archiveMode);
        unset($built['files'], $filesList);
        if ($archiveBin === null || $archiveBin === '' || $archiveKind === '') {
            return [
                'success' => false,
                'error' => 'Could not build archive (need zlib/gzencode for .tar.gz, or php-zip for .zip).',
                'error_code' => 'E_ARCHIVE',
            ];
        }

        $ext = $archiveKind === 'zip' ? '.zip' : '.tar.gz';
        $mime = $archiveKind === 'zip' ? 'application/zip' : 'application/gzip';
        $uploadFilename = $baseName . $ext;
        $dataNameRaw = (string) ($data['request_file_data_name'] ?? 'FORTE_CODE_BACKUP_EXPORT');
        $dataName = preg_replace('/[^A-Za-z0-9_]/', '_', $dataNameRaw);
        if ($dataName === '' || strlen($dataName) > 60) {
            $dataName = 'FORTE_CODE_BACKUP_EXPORT';
        }

        if ($exportDest === 'request') {
            $rid = $data['request_id'] ?? null;
            if (is_array($rid) && isset($rid['id'])) {
                $rid = $rid['id'];
            }
            $requestId = is_numeric($rid) ? (int) $rid : 0;
            if ($requestId < 1) {
                return [
                    'success' => false,
                    'error' => 'export_destination=request requires a positive request_id (current request id).',
                    'error_code' => 'E_REQUEST_ID',
                ];
            }
            $up = forte_pm_upload_request_file($host, $token, $requestId, $archiveBin, $uploadFilename, $dataName);
            unset($archiveBin);
            if (!$up['success']) {
                return [
                    'success' => false,
                    'error' => 'Request file upload failed: ' . ($up['error'] ?? 'unknown'),
                    'error_code' => 'E_UPLOAD',
                    'http_status' => $up['http_status'] ?? null,
                ];
            }

            return [
                'success' => true,
                'action' => 'export_chunk',
                'export_destination' => 'request',
                'export_format' => $archiveKind,
                'target' => $target,
                'mode' => $mode,
                'root_folder' => $rootFolder,
                'entity' => $entity,
                'page' => $page,
                'per_page' => $perPage,
                'meta' => $built['meta'],
                'request_id' => $requestId,
                'request_file_data_name' => $dataName,
                'file_upload_id' => $up['file_upload_id'],
                'uploaded_file_name' => $uploadFilename,
                'message' => 'Archive attached to the request. Use a File Download control or Files in the request with data name "' . $dataName . '".',
            ];
        }

        return [
            'success' => true,
            'action' => 'export_chunk',
            'export_destination' => 'browser',
            'export_format' => $archiveKind,
            'target' => $target,
            'mode' => $mode,
            'root_folder' => $rootFolder,
            'entity' => $entity,
            'page' => $page,
            'per_page' => $perPage,
            'suggested_filename' => $uploadFilename,
            'archive_kind' => $archiveKind,
            'archive_mime' => $mime,
            'archive_base64' => base64_encode($archiveBin),
            'meta' => $built['meta'],
        ];
    }

    return [
        'success' => false,
        'error' => 'Unknown action "' . $action . '". Use counts, list, export_chunk, or export_all.',
        'error_code' => 'E_ACTION',
    ];
}
// -----------------------------------------------------------------------------
// Entry
// -----------------------------------------------------------------------------

$legacyFormat = strtolower(trim((string) ($data['return_format'] ?? '')));
if ($legacyFormat !== '' && in_array($legacyFormat, ['nested_json', 'zip_base64'], true)) {
    $hasChunk = isset($data['action']) && (string) $data['action'] !== '';
    if (!$hasChunk) {
        return [
            'success' => false,
            'error' => 'Full export in one call is disabled (executor memory limit). Use action=counts, list, export_chunk, or export_all with entity + paging.',
            'error_code' => 'E_DEPRECATED_FULL_EXPORT',
        ];
    }
}

$target = strtolower(trim((string) ($data['target'] ?? 'dev')));
if ($target !== 'prod') {
    $target = 'dev';
}

$action = strtolower(trim((string) ($data['action'] ?? 'counts')));
if ($action === '') {
    $action = 'counts';
}
$mode = strtolower(trim((string) ($data['mode'] ?? 'api')));
if ($mode !== 'sql') {
    $mode = 'api';
}

[$host, $token] = pexp_enrich_connection($data);
if ($host === '' || $token === '') {
    return [
        'success' => false,
        'error' => 'Set api_base (or rely on env/Forte for host) and bearer_token — OAuth bearer from the connection field only; api_token is not used.',
        'error_code' => 'E_AUTH',
    ];
}

$rootFolder = 'pm_process_export';
$rootFolderForte = pexp_resolve_export_asset_root_folder($host, $data);

try {
    if ($action === 'counts') {
        if ($mode === 'sql') {
            $endpoint = rtrim($host, '/') . pexp_resolve_sql_path($data);
            $c1 = forte_call_sql($endpoint, $token, forte_sql_count_scripts());
            if (isset($c1['__error__'])) {
                return ['success' => false, 'error' => (string) $c1['__error__'], 'error_code' => 'E_SQL'];
            }
            $c2 = forte_call_sql($endpoint, $token, forte_sql_count_screens());
            if (isset($c2['__error__'])) {
                return ['success' => false, 'error' => (string) $c2['__error__'], 'error_code' => 'E_SQL'];
            }
            $c3 = forte_call_sql($endpoint, $token, forte_sql_count_executors());
            if (isset($c3['__error__'])) {
                return ['success' => false, 'error' => (string) $c3['__error__'], 'error_code' => 'E_SQL'];
            }
            $c4 = pexp_call_sql($endpoint, $token, pexp_sql_count_processes());
            if (isset($c4['__error__'])) {
                return ['success' => false, 'error' => (string) $c4['__error__'], 'error_code' => 'E_SQL'];
            }
            return [
                'success' => true,
                'action' => 'counts',
                'target' => $target,
                'mode' => $mode,
                'root_folder' => $rootFolder,
                'asset_root_folder' => $rootFolderForte,
                'counts' => [
                    'scripts' => forte_sql_first_cnt($c1),
                    'screens' => forte_sql_first_cnt($c2),
                    'executors' => forte_sql_first_cnt($c3),
                    'processes' => pexp_sql_first_cnt($c4),
                ],
            ];
        }
        $tScripts = forte_api_probe_total($host, $token, '/scripts');
        $tScreens = forte_api_probe_total($host, $token, '/screens');
        $tExec = forte_api_probe_total($host, $token, '/script-executors');
        $tProc = pexp_api_probe_total($host, $token, '/processes');
        return [
            'success' => true,
            'action' => 'counts',
            'target' => $target,
            'mode' => $mode,
            'root_folder' => $rootFolder,
            'asset_root_folder' => $rootFolderForte,
            'counts' => [
                'scripts' => $tScripts,
                'screens' => $tScreens,
                'executors' => $tExec,
                'processes' => $tProc,
            ],
        ];
    }

    $entity = strtolower(trim((string) ($data['entity'] ?? 'processes')));
    if ($entity === '') {
        $entity = 'processes';
    }

    if ($action === 'list' && in_array($entity, ['scripts', 'screens', 'executors'], true)) {
        return pexp_forte_asset_dispatch($data, $host, $token, $mode, $action, $target, $rootFolderForte);
    }
    if ($action === 'export_chunk' && in_array($entity, ['scripts', 'screens', 'executors'], true)) {
        return pexp_forte_asset_dispatch($data, $host, $token, $mode, $action, $target, $rootFolderForte);
    }
    if ($action === 'export_all' && in_array($entity, ['scripts', 'screens'], true)) {
        return pexp_forte_asset_dispatch($data, $host, $token, $mode, $action, $target, $rootFolderForte);
    }

    if ($action === 'list' && $entity !== 'processes') {
        return [
            'success' => false,
            'error' => 'entity for list must be processes, scripts, screens, or executors.',
            'error_code' => 'E_ENTITY',
        ];
    }

    if ($action === 'list' && $entity === 'processes') {
        [$page, $perPage] = pexp_parse_paging($data, 25, PEXP_LIST_MAX);
        $draw = isset($data['draw']) ? (int) $data['draw'] : 0;
        if ($draw < 1) {
            $draw = 1;
        }
        $procQuery = ['order_by' => 'id', 'order_direction' => 'asc'];
        if ($mode === 'sql') {
            $endpoint = rtrim($host, '/') . pexp_resolve_sql_path($data);
            $offset = ($page - 1) * $perPage;
            $rows = pexp_call_sql($endpoint, $token, pexp_sql_list_processes_thin($offset, $perPage));
            if (isset($rows['__error__'])) {
                return ['success' => false, 'error' => (string) $rows['__error__'], 'error_code' => 'E_SQL'];
            }
            $list = [];
            foreach (pexp_ensure_list_of_rows($rows) as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $list[] = pexp_lower_row_keys(pexp_flatten_api_row($r));
            }
            $cntRows = pexp_call_sql($endpoint, $token, pexp_sql_count_processes());
            $total = isset($cntRows['__error__']) ? 0 : pexp_sql_first_cnt($cntRows);
        } else {
            $pack = pexp_api_get_one_page($host, $token, '/processes', $page, $perPage, $procQuery, true);
            $list = [];
            foreach ($pack['data'] as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $list[] = pexp_lower_row_keys($r);
            }
            $total = (int) $pack['raw_total'];
        }
        return [
            'success' => true,
            'action' => 'list',
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $list,
            'meta' => [
                'entity' => 'processes',
                'page' => $page,
                'per_page' => $perPage,
                'mode' => $mode,
                'target' => $target,
            ],
        ];
    }

    if ($action === 'export_chunk' || $action === 'export_all') {
        return [
            'success' => false,
            'error' => $action === 'export_all'
                ? 'entity must be scripts or screens for export_all.'
                : 'entity must be scripts, screens, or executors for export_chunk.',
            'error_code' => 'E_ENTITY',
        ];
    }

    $pidRaw = $data['process_id'] ?? null;
    if (is_array($pidRaw) && isset($pidRaw['id'])) {
        $pidRaw = $pidRaw['id'];
    }
    $processId = is_numeric($pidRaw) ? (int) $pidRaw : 0;
    if ($processId < 1) {
        return [
            'success' => false,
            'error' => 'process_id is required for manifest and export_process.',
            'error_code' => 'E_PROCESS_ID',
        ];
    }

    $includeSub = filter_var($data['include_subprocesses'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $maxDepth = pexp_clamp_int($data['max_subprocess_depth'] ?? 4, 0, 10);

    if ($action === 'manifest') {
        $dep = pexp_resolve_dependencies($host, $token, $mode, $data, [$processId], $includeSub, $maxDepth);
        $p = pexp_fetch_process_with_bpmn($host, $token, $mode, $data, $processId);
        return [
            'success' => true,
            'action' => 'manifest',
            'process_id' => $processId,
            'process' => $p['process'],
            'related' => [
                'screen_ids' => array_keys($dep['screens']),
                'script_ids' => array_keys($dep['scripts']),
                'executor_ids' => array_keys($dep['executors']),
                'subprocess_ids' => $dep['subprocesses_tracked'],
            ],
            'warnings' => $dep['errors'],
        ];
    }

    if ($action === 'export_process') {
        $pack = pexp_fetch_process_with_bpmn($host, $token, $mode, $data, $processId);
        if ($pack['process'] === null) {
            return [
                'success' => false,
                'error' => $pack['error'] ?? 'Process not found.',
                'error_code' => 'E_NOT_FOUND',
            ];
        }
        $bpmn = $pack['bpmn'];
        $procMeta = $pack['process'];

        $dep = pexp_resolve_dependencies($host, $token, $mode, $data, [$processId], $includeSub, $maxDepth);
        $files = pexp_build_file_list(
            $rootFolder,
            $procMeta,
            $bpmn,
            $dep['screens'],
            $dep['scripts'],
            $dep['executors']
        );

        $exportFmt = strtolower(trim((string) ($data['export_format'] ?? 'tar_gz')));
        if (!in_array($exportFmt, ['json', 'zip', 'tar_gz', 'tgz', 'auto'], true)) {
            $exportFmt = 'tar_gz';
        }
        if ($exportFmt === 'tgz') {
            $exportFmt = 'tar_gz';
        }

        $exportDest = strtolower(trim((string) ($data['export_destination'] ?? 'browser')));
        if ($exportDest !== 'request') {
            $exportDest = 'browser';
        }

        $slugName = pexp_slugify((string) ($procMeta['name'] ?? 'process'));
        $baseName = $rootFolder . '-process-' . $processId . '-' . $slugName;

        if ($exportFmt === 'json') {
            return [
                'success' => true,
                'action' => 'export_process',
                'export_format' => 'json',
                'export_destination' => $exportDest,
                'suggested_filename' => $baseName . '.json',
                'warnings' => $dep['errors'],
                'bundle' => [
                    'process' => $procMeta,
                    'bpmn' => $bpmn,
                    'screens' => $dep['screens'],
                    'scripts' => $dep['scripts'],
                    'executors' => $dep['executors'],
                    'subprocess_ids' => $dep['subprocesses_tracked'],
                ],
            ];
        }

        if ($files === []) {
            return ['success' => false, 'error' => 'No files to export.', 'error_code' => 'E_EMPTY'];
        }

        $archiveMode = $exportFmt === 'auto' ? 'auto' : $exportFmt;
        [$archiveBin, $archiveKind] = pexp_build_archive_binary($files, $archiveMode);
        if ($archiveBin === null || $archiveBin === '' || $archiveKind === '') {
            return [
                'success' => false,
                'error' => 'Could not build archive (need zlib gzencode or php-zip).',
                'error_code' => 'E_ARCHIVE',
            ];
        }
        $size = strlen($archiveBin);
        if ($size > PEXP_EXPORT_MAX_BYTES) {
            return [
                'success' => false,
                'error' => 'Archive exceeds size limit; reduce scope or use SQL mode.',
                'error_code' => 'E_TOO_LARGE',
            ];
        }

        $ext = $archiveKind === 'zip' ? '.zip' : '.tar.gz';
        $mime = $archiveKind === 'zip' ? 'application/zip' : 'application/gzip';
        $uploadFilename = $baseName . $ext;

        $dataNameRaw = (string) ($data['request_file_data_name'] ?? 'PM_PROCESS_EXPORT');
        $dataName = preg_replace('/[^A-Za-z0-9_]/', '_', $dataNameRaw);
        if ($dataName === '' || strlen($dataName) > 60) {
            $dataName = 'PM_PROCESS_EXPORT';
        }

        if ($exportDest === 'request') {
            $rid = $data['request_id'] ?? null;
            if (is_array($rid) && isset($rid['id'])) {
                $rid = $rid['id'];
            }
            $requestId = is_numeric($rid) ? (int) $rid : 0;
            if ($requestId < 1) {
                return [
                    'success' => false,
                    'error' => 'export_destination=request requires request_id.',
                    'error_code' => 'E_REQUEST_ID',
                ];
            }
            $up = pexp_pm_upload_request_file($host, $token, $requestId, $archiveBin, $uploadFilename, $dataName);
            unset($archiveBin);
            if (!$up['success']) {
                return [
                    'success' => false,
                    'error' => 'Upload failed: ' . ($up['error'] ?? ''),
                    'error_code' => 'E_UPLOAD',
                    'http_status' => $up['http_status'] ?? null,
                ];
            }
            return [
                'success' => true,
                'action' => 'export_process',
                'export_destination' => 'request',
                'export_format' => $archiveKind,
                'warnings' => $dep['errors'],
                'request_id' => $requestId,
                'request_file_data_name' => $dataName,
                'file_upload_id' => $up['file_upload_id'],
                'uploaded_file_name' => $uploadFilename,
            ];
        }

        return [
            'success' => true,
            'action' => 'export_process',
            'export_destination' => 'browser',
            'export_format' => $archiveKind,
            'warnings' => $dep['errors'],
            'suggested_filename' => $uploadFilename,
            'archive_kind' => $archiveKind,
            'archive_mime' => $mime,
            'archive_base64' => base64_encode($archiveBin),
        ];
    }

    return [
        'success' => false,
        'error' => 'Unknown action. Use counts, list, export_chunk, export_all, manifest, or export_process.',
        'error_code' => 'E_ACTION',
    ];
} catch (\Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'E_EXCEPTION',
    ];
}
