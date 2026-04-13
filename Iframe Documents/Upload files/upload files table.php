<?php

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$data['request_id'] = 5;
if (!empty($data['filedata']) && !empty($data['request_id'])) {
    $requestId = intval($data['request_id']);
    $filename  = $data['filename'] ?? 'upload.tmp';
    $mimetype  = $data['mimetype'] ?? 'application/octet-stream';
    $filedata  = $data['filedata'];
    //change filename spaces with _
    $filenameSpaces = str_replace(' ', '_', $filename);
    // Dynamic variable name
    $dataName = "my_file_{$requestId}_id_{$filenameSpaces}";

    // Save file temporarily
    $tempPath = "/tmp/" . basename($filename);
    file_put_contents($tempPath, base64_decode($filedata));

    try {
        $apiInstance = $api->requestFiles();
        $newFile = $apiInstance->createRequestFile($requestId, $dataName, $tempPath);
        unlink($tempPath);

        return [
            'success' => true,
            'newFileId' => $newFile->getFileUploadId(),
            'filename' => $filename,
            'mimetype' => $mimetype,
            'dataName' => $dataName
        ];
    } catch (Exception $e) {
        return [
            'error' => 'Upload failed',
            'details' => $e->getMessage()
        ];
    }
}

return [
    'error' => 'Invalid payload: filedata or request_id missing'
];
