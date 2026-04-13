<?php
$apiInstance = $api->requestFiles();
$requestId = intval($data['request_id'] ?? 5);
$files = [];

if ($requestId > 0) {
    $result = $apiInstance->getRequestFiles($requestId);
    foreach ($result->getData() as $file) {
        $files[] = [
            'id' => $file->getId(),
            'filename' => $file->getFileName(),
            'size' => $file->getSize(),
        ];
    }
}

return [
    "draw" => 1,
    "recordsTotal" => count($files),
    "recordsFiltered" => count($files),
    "data" => $files
];
