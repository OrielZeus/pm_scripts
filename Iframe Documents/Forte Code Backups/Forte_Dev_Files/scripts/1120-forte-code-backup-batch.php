<?php
/**
 * Forte — paged backup API (Guzzle REST or Pro Service Tools SQL). Avoids loading all assets at once (128MB executor limit).
 *
 * Register as PSTools slug e.g. forte-code-backup-batch or forte_code_backup_batch.
 *
 * Request JSON (main fields):
 *   action: "counts" | "list" | "export_chunk" | "export_all"  (default: counts)
 *   target: "dev" | "prod"
 *   mode:   "api" | "sql"  (default: api)
 *   entity: "scripts" | "screens" | "executors" — required for list / export_chunk
 *   page + per_page  OR  DataTables: draw, start, length
 *
 * counts — lightweight totals only (3 small HTTP calls or 3 COUNT queries).
 * list — one page of thin rows for DataTables (no full script code in API path; SQL list omits code).
 * export_chunk — full file bodies for ONE page only (per_page capped at 50).
 * export_all — scripts or screens only: walks every page; prefers .zip (ZipArchive), else .tar.gz (zlib streaming).
 *   export_format: "tar_gz" (default) — POSIX ustar .tar + gzip; returns archive_base64 (no ZipArchive).
 *   export_format: "zip" — ZipArchive when available; fails with E_ZIP if extension missing.
 *   export_format: "auto" — try zip, then tar_gz.
 *   export_format: "json" — returns files[] (escaped newlines in JSON strings).
 *   export_destination: "browser" (default) — return archive_base64 for download.
 *   export_destination: "request" — POST one archive to POST /requests/{request_id}/files (needs request_id).
 *   request_file_data_name — variable name for the uploaded file (default FORTE_CODE_BACKUP_EXPORT); same name replaces previous file.
 *
 * Legacy return_format=nested_json / zip_base64 without action is rejected (use export_chunk per page).
 */

if (!isset($data) || !is_array($data)) {
    $data = [];
}

/**
 * PSTools / clients sometimes POST { "data": { "action": "list", ... } }. Core expects flat keys.
 * Control keys from nested objects override top-level so list/export always win.
 */
function forte_unnest_request_data(array &$data): void
{
    $controlKeys = ['action', 'entity', 'draw', 'start', 'length', 'page', 'per_page', 'mode', 'target', 'return_format', 'export_format', 'export_destination', 'request_id', 'request_file_data_name'];
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

forte_unnest_request_data($data);

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
 * JSON:API style { id, attributes: { ... } } → flat row for DataTables.
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
 * Exact total: GET page=1&per_page=1 — with per_page 1, meta.last_page equals global total rows.
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
    $name = str_replace('\\', '/', $name);
    $name = ltrim($name, '/');
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
    $path = str_replace('\\', '/', ltrim($path, '/'));
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
        $p = str_replace('\\', '/', (string) ($f['path'] ?? ''));
        $p = ltrim($p, '/');
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
        $name = str_replace('\\', '/', (string) ($f['path'] ?? ''));
        $name = ltrim($name, '/');
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

// -----------------------------------------------------------------------------
// Entry
// -----------------------------------------------------------------------------

$legacyFormat = strtolower(trim((string) ($data['return_format'] ?? '')));
if ($legacyFormat !== '' && in_array($legacyFormat, ['nested_json', 'zip_base64'], true)) {
    $hasChunk = isset($data['action']) && $data['action'] !== '';
    if (!$hasChunk) {
        return [
            'success' => false,
            'error' => 'Full export in one call is disabled (executor memory limit). Use action=counts, action=list, or action=export_chunk with entity + paging.',
            'error_code' => 'E_DEPRECATED_FULL_EXPORT',
        ];
    }
}

$target = strtolower(trim((string) ($data['target'] ?? 'dev')));
if ($target !== 'prod') {
    $target = 'dev';
}
$mode = strtolower(trim((string) ($data['mode'] ?? 'api')));
$action = strtolower(trim((string) ($data['action'] ?? 'counts')));
if ($action === '') {
    $action = 'counts';
}
$rootFolder = $target === 'prod' ? 'forte_prod_files' : 'forte_dev_files';

try {
    [$host, $token] = forte_resolve_api($data, $target);
    if ($host === '' || $token === '') {
        return [
            'success' => false,
            'error' => 'API host or token missing for target "' . $target . '".',
            'error_code' => 'E_AUTH',
        ];
    }

    if ($action === 'counts') {
        if ($mode === 'sql') {
            $endpoint = rtrim($host, '/') . forte_resolve_sql_path($data);
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
            return [
                'success' => true,
                'action' => 'counts',
                'target' => $target,
                'mode' => $mode,
                'root_folder' => $rootFolder,
                'counts' => [
                    'scripts' => forte_sql_first_cnt($c1),
                    'screens' => forte_sql_first_cnt($c2),
                    'executors' => forte_sql_first_cnt($c3),
                ],
            ];
        }
        $tScripts = forte_api_probe_total($host, $token, '/scripts');
        $tScreens = forte_api_probe_total($host, $token, '/screens');
        $tExec = forte_api_probe_total($host, $token, '/script-executors');
        return [
            'success' => true,
            'action' => 'counts',
            'target' => $target,
            'mode' => $mode,
            'root_folder' => $rootFolder,
            'counts' => [
                'scripts' => $tScripts,
                'screens' => $tScreens,
                'executors' => $tExec,
            ],
        ];
    }

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
                    $name = str_replace('\\', '/', (string) ($f['path'] ?? ''));
                    $name = ltrim($name, '/');
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
} catch (\Throwable $e) {
    return [
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => 'E_EXCEPTION',
    ];
}