<?php
/**
 * Generic Script Task: upload one file to any external API using Guzzle multipart/form-data.
 *
 * Why this script exists:
 * - Reusable upload helper for multiple processes/components.
 * - Avoids cURL and standardizes error handling for Script Tasks.
 * - Supports ProcessMaker file ids (download + forward) and local file paths.
 *
 * Expected input ($data) examples:
 * 1) Using ProcessMaker file id:
 *    [
 *      "target_url" => "https://example.com/ApiFileDev/File/Folder/UploadFiles",
 *      "file_id" => 12345,
 *      "file_field_name" => "Files",
 *      "form_fields" => [
 *        "NroDocumento" => "25000002",
 *        "TipoDocumento" => "OL",
 *        "processId" => "99",
 *        "user" => "hectorm"
 *      ]
 *    ]
 *
 * 2) Using local file path:
 *    [
 *      "target_url" => "https://example.com/upload",
 *      "file_path" => "C:/tmp/my-file.pdf",
 *      "file_field_name" => "Files",
 *      "file_name" => "my-file.pdf"
 *    ]
 *
 * Optional environment fallback:
 * - API_HOST / API_TOKEN only used when file source is ProcessMaker file_id.
 * - UPLOAD_API_URL, UPLOAD_API_TOKEN, UPLOAD_VERIFY_SSL, UPLOAD_TIMEOUT, UPLOAD_FILE_FIELD_NAME.
 */

if (!isset($data) || !is_array($data)) {
    $data = [];
}

$config = [
    'target_url' => trim((string) ($data['target_url'] ?? getenv('UPLOAD_API_URL') ?: '')),
    'target_method' => strtoupper(trim((string) ($data['target_method'] ?? 'POST'))),
    'target_token' => trim((string) ($data['target_token'] ?? getenv('UPLOAD_API_TOKEN') ?: '')),
    'timeout' => (int) ($data['timeout'] ?? getenv('UPLOAD_TIMEOUT') ?: 120),
    'verify_ssl' => upload_to_bool($data['verify_ssl'] ?? getenv('UPLOAD_VERIFY_SSL'), false),
    'file_field_name' => trim((string) ($data['file_field_name'] ?? getenv('UPLOAD_FILE_FIELD_NAME') ?: 'Files')),
    'form_fields' => isset($data['form_fields']) && is_array($data['form_fields']) ? $data['form_fields'] : [],
    'debug' => upload_to_bool($data['debug'] ?? false, false),
];

if ($config['target_url'] === '') {
    return upload_error('target_url is required.', 'E_TARGET_URL');
}
if (!in_array($config['target_method'], ['POST', 'PUT', 'PATCH'], true)) {
    return upload_error('target_method must be POST, PUT, or PATCH.', 'E_TARGET_METHOD');
}
if ($config['file_field_name'] === '') {
    return upload_error('file_field_name cannot be empty.', 'E_FILE_FIELD_NAME');
}
if ($config['timeout'] < 1) {
    $config['timeout'] = 120;
}

$resolvedFile = upload_resolve_source_file($data);
if (!$resolvedFile['ok']) {
    return upload_error($resolvedFile['error'], $resolvedFile['code'], $resolvedFile['detail'] ?? []);
}

$multipart = upload_build_multipart_payload(
    $config['form_fields'],
    $config['file_field_name'],
    $resolvedFile['content'],
    $resolvedFile['filename'],
    $resolvedFile['mime_type']
);

$headers = [
    'Accept' => 'application/json',
];
if ($config['target_token'] !== '') {
    $headers['Authorization'] = 'Bearer ' . $config['target_token'];
}

$requestOptions = [
    'verify' => $config['verify_ssl'],
    'timeout' => $config['timeout'],
    'http_errors' => false,
    'headers' => $headers,
    'multipart' => $multipart,
];

try {
    $clientClass = '\\GuzzleHttp\\Client';
    $client = new $clientClass();
    $response = $client->request($config['target_method'], $config['target_url'], $requestOptions);
    $statusCode = (int) $response->getStatusCode();
    $rawBody = (string) $response->getBody();
    $decodedBody = json_decode($rawBody, true);

    $result = [
        'success' => $statusCode >= 200 && $statusCode < 300,
        'status_code' => $statusCode,
        'target_url' => $config['target_url'],
        'target_method' => $config['target_method'],
        'file' => [
            'source' => $resolvedFile['source'],
            'filename' => $resolvedFile['filename'],
            'mime_type' => $resolvedFile['mime_type'],
            'bytes' => strlen($resolvedFile['content']),
        ],
        'response_json' => is_array($decodedBody) ? $decodedBody : null,
        'response_text' => is_array($decodedBody) ? null : $rawBody,
    ];

    if ($config['debug']) {
        $result['debug'] = [
            'form_fields' => $config['form_fields'],
            'used_headers' => array_keys($headers),
            'verify_ssl' => $config['verify_ssl'],
            'timeout' => $config['timeout'],
        ];
    }

    if (!$result['success']) {
        $result['error'] = 'Upload API returned non-success status.';
        $result['error_code'] = 'E_UPLOAD_HTTP';
    }

    return $result;
} catch (\Throwable $e) {
    return upload_error('Upload request failed: ' . $e->getMessage(), 'E_UPLOAD_EXCEPTION');
}

/**
 * Resolve source file as bytes + metadata.
 *
 * Source priority:
 * 1) file_content_base64 (direct content)
 * 2) file_path (local path)
 * 3) file_id (ProcessMaker file id via API_HOST/API_TOKEN)
 *
 * @param array<string,mixed> $data
 * @return array<string,mixed>
 */
function upload_resolve_source_file(array $data): array
{
    $fileContentBase64 = trim((string) ($data['file_content_base64'] ?? ''));
    if ($fileContentBase64 !== '') {
        $decoded = base64_decode($fileContentBase64, true);
        if ($decoded === false) {
            return ['ok' => false, 'code' => 'E_FILE_BASE64', 'error' => 'file_content_base64 is not valid base64.'];
        }
        if ($decoded === '') {
            return ['ok' => false, 'code' => 'E_FILE_EMPTY', 'error' => 'Decoded file content is empty.'];
        }
        $name = trim((string) ($data['file_name'] ?? 'upload.bin'));
        $mime = trim((string) ($data['mime_type'] ?? 'application/octet-stream'));
        return [
            'ok' => true,
            'source' => 'base64',
            'content' => $decoded,
            'filename' => $name !== '' ? $name : 'upload.bin',
            'mime_type' => $mime !== '' ? $mime : 'application/octet-stream',
        ];
    }

    $filePath = trim((string) ($data['file_path'] ?? ''));
    if ($filePath !== '') {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return [
                'ok' => false,
                'code' => 'E_FILE_PATH',
                'error' => 'file_path does not exist or is not readable.',
                'detail' => ['file_path' => $filePath],
            ];
        }
        $bytes = @file_get_contents($filePath);
        if (!is_string($bytes) || $bytes === '') {
            return [
                'ok' => false,
                'code' => 'E_FILE_READ',
                'error' => 'Could not read file_path content.',
                'detail' => ['file_path' => $filePath],
            ];
        }
        $name = trim((string) ($data['file_name'] ?? basename($filePath)));
        $mime = trim((string) ($data['mime_type'] ?? upload_guess_mime_type($filePath)));
        return [
            'ok' => true,
            'source' => 'path',
            'content' => $bytes,
            'filename' => $name !== '' ? $name : 'upload.bin',
            'mime_type' => $mime !== '' ? $mime : 'application/octet-stream',
        ];
    }

    $fileId = (int) ($data['file_id'] ?? 0);
    if ($fileId > 0) {
        return upload_load_processmaker_file($fileId, $data);
    }

    return [
        'ok' => false,
        'code' => 'E_FILE_SOURCE',
        'error' => 'Provide one file source: file_content_base64, file_path, or file_id.',
    ];
}

/**
 * Download ProcessMaker file content and metadata using API_HOST/API_TOKEN.
 *
 * @param int $fileId
 * @param array<string,mixed> $data
 * @return array<string,mixed>
 */
function upload_load_processmaker_file(int $fileId, array $data): array
{
    $apiHost = rtrim((string) ($data['pm_api_host'] ?? getenv('API_HOST') ?: ''), '/');
    $apiToken = trim((string) ($data['pm_api_token'] ?? getenv('API_TOKEN') ?: ''));
    if ($apiHost === '' || $apiToken === '') {
        return [
            'ok' => false,
            'code' => 'E_PM_ENV',
            'error' => 'file_id requires ProcessMaker credentials (pm_api_host/pm_api_token or API_HOST/API_TOKEN).',
        ];
    }

    $clientClass = '\\GuzzleHttp\\Client';
    $client = new $clientClass([
        'verify' => upload_to_bool($data['pm_verify_ssl'] ?? false, false),
        'timeout' => (int) ($data['pm_timeout'] ?? 120),
        'http_errors' => false,
    ]);

    $metadataUrl = $apiHost . '/files/' . $fileId;
    $metadataRes = $client->request('GET', $metadataUrl, [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiToken,
        ],
    ]);

    if ($metadataRes->getStatusCode() >= 400) {
        return [
            'ok' => false,
            'code' => 'E_PM_FILE_METADATA',
            'error' => 'Unable to read ProcessMaker file metadata.',
            'detail' => [
                'status_code' => (int) $metadataRes->getStatusCode(),
                'file_id' => $fileId,
            ],
        ];
    }

    $metadataBody = json_decode((string) $metadataRes->getBody(), true);
    $fileName = trim((string) ($data['file_name'] ?? ($metadataBody['name'] ?? 'file-' . $fileId)));
    $mimeType = trim((string) ($data['mime_type'] ?? ($metadataBody['mime_type'] ?? 'application/octet-stream')));

    $contentUrl = $apiHost . '/files/' . $fileId . '/contents';
    $contentRes = $client->request('GET', $contentUrl, [
        'headers' => [
            'Accept' => 'application/octet-stream',
            'Authorization' => 'Bearer ' . $apiToken,
        ],
    ]);

    if ($contentRes->getStatusCode() >= 400) {
        return [
            'ok' => false,
            'code' => 'E_PM_FILE_CONTENT',
            'error' => 'Unable to download ProcessMaker file contents.',
            'detail' => [
                'status_code' => (int) $contentRes->getStatusCode(),
                'file_id' => $fileId,
            ],
        ];
    }

    $content = (string) $contentRes->getBody();
    if ($content === '') {
        return [
            'ok' => false,
            'code' => 'E_PM_FILE_EMPTY',
            'error' => 'ProcessMaker file content is empty.',
            'detail' => ['file_id' => $fileId],
        ];
    }

    return [
        'ok' => true,
        'source' => 'processmaker_file_id',
        'content' => $content,
        'filename' => $fileName !== '' ? $fileName : ('file-' . $fileId),
        'mime_type' => $mimeType !== '' ? $mimeType : 'application/octet-stream',
    ];
}

/**
 * Build multipart body with plain fields plus one file part.
 *
 * @param array<string,mixed> $fields
 * @return array<int,array<string,mixed>>
 */
function upload_build_multipart_payload(array $fields, string $fileFieldName, string $content, string $filename, string $mimeType): array
{
    $multipart = [];
    foreach ($fields as $name => $value) {
        $fieldName = trim((string) $name);
        if ($fieldName === '') {
            continue;
        }
        $multipart[] = [
            'name' => $fieldName,
            'contents' => is_scalar($value) ? (string) $value : json_encode($value),
        ];
    }

    $multipart[] = [
        'name' => $fileFieldName,
        'contents' => $content,
        'filename' => $filename,
        'headers' => [
            'Content-Type' => $mimeType,
        ],
    ];

    return $multipart;
}

/**
 * Convert common script values to bool.
 *
 * @param mixed $value
 */
function upload_to_bool($value, bool $default): bool
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    $normalized = strtolower(trim((string) $value));
    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return $default;
}

/**
 * Guess MIME type from a local path.
 */
function upload_guess_mime_type(string $path): string
{
    if (function_exists('mime_content_type')) {
        $detected = @mime_content_type($path);
        if (is_string($detected) && trim($detected) !== '') {
            return $detected;
        }
    }
    return 'application/octet-stream';
}

/**
 * Standardized error response.
 *
 * @param array<string,mixed> $detail
 * @return array<string,mixed>
 */
function upload_error(string $message, string $code, array $detail = []): array
{
    return [
        'success' => false,
        'error' => $message,
        'error_code' => $code,
        'detail' => $detail,
    ];
}
