<?php 
/**
* Compress all files in a request into a zip file
*
* modified by Telmo Chiri
* modified by Adriana Centellas
**/

$requestId = $data["_request"]["parent_request_id"] ?? ($data["_request"]["id"] ?? '');
$zipFileId = '';

// Guzzle connection info
$pmheaders = [
    'Authorization' => 'Bearer ' . getenv('API_TOKEN'),        
    'Accept'        => 'application/json',
];
$apiHost = getenv('API_HOST');
$client = new GuzzleHttp\Client(['verify' => false]);

$identifierDataName = "allDocuments";
$zipFileName = $identifierDataName . ".zip";
$zipFilePath = '/tmp/' . $zipFileName;

////////////////////////////////////////////////////////////////////////////
/// Create ZIP archive with all request files
////////////////////////////////////////////////////////////////////////////

$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    return [
        "zipDocuments" => "",
        "errorZipCreation" => "Could not open zip file for writing"
    ];
}

// Retrieve files attached to the request
$apiInstance = $api->requestFiles();
$result = $apiInstance->getRequestFiles($requestId);
$newFiles = [];

// Build exclusion list from PE_AML_RESULTS_DOCUMENT and zipAMLDocuments
$excludedFiles = [];

// Exclude array of files in PE_AML_RESULTS_DOCUMENT
if (isset($data["PE_AML_RESULTS_DOCUMENT"]) && is_array($data["PE_AML_RESULTS_DOCUMENT"])) {
    foreach ($data["PE_AML_RESULTS_DOCUMENT"] as $item) {
        if (is_array($item) && isset($item["file"])) {
            $excludedFiles[] = $item["file"];
        }
    }
}

// Exclude single file ID from zipAMLDocuments
if (isset($data["zipAMLDocuments"]) && is_numeric($data["zipAMLDocuments"])) {
    $excludedFiles[] = $data["zipAMLDocuments"];
}

// Filter out excluded files and the zip itself
foreach ($result->getData() as $file) {
    if (
        $zipFileId != $file['id'] &&
        strtolower($file['file_name']) !== strtolower($zipFileName) &&
        !in_array($file['id'], $excludedFiles)
    ) {
        $newFiles[] = $file;
    }
}


// Loop over the filtered files, download and add to the zip
foreach($newFiles as $newFile){
    try {            
        $fileId = $newFile["id"];
        $fileName = $newFile["file_name"];
        $filePath = '/tmp/' . $fileName;

        // Download each file to /tmp
        $res = $client->request('GET', $apiHost . "/files/$fileId/contents", [
            'headers' => $pmheaders,
            'sink' => $filePath
        ]);

        $fileSize = filesize($filePath);
        // Uncomment to debug individual file sizes
        // error_log("File $fileName => " . round($fileSize / (1024 * 1024), 2) . " MB");

        // Add the file to the zip archive
        $zip->addFile($filePath, $fileName);
    } 
    catch(\Exception $ex){
        return [
            "zipDocuments" => "",
            "errorZipCreation" => "Failed to add file to ZIP: " . $ex->getMessage()
        ];
    }
}

$zip->close(); // Finalize the zip

// Calculate zip file size after creation
$zipFileSize = filesize($zipFilePath);
// Uncomment to log final zip size
// error_log("ZIP final size: " . $zipFileSize . " bytes (" . round($zipFileSize / (1024 * 1024), 2) . " MB)");

////////////////////////////////////////////////////////////////////////////
/// Upload ZIP file to request
////////////////////////////////////////////////////////////////////////////

try {
    // FIX 2: Upload only once using fopen() instead of string path
    // The original script tried to upload a placeholder zip early with an incorrect "contents" usage
    // Also, it used file_get_contents which loads the full file into memory (not ideal for large files)
    // Using fopen creates a stream instead, more efficient and correct for Guzzle uploads

    $res = $client->request('POST', $apiHost . "/requests/$requestId/files?data_name=$identifierDataName", [
        "headers" => $pmheaders,
        "multipart" => [
            [
                "Content-type" => "multipart/form-data",
                "name"     => "file",
                "contents" => fopen($zipFilePath, 'r'), // Proper streaming upload
                "filename" => $zipFileName
            ]
        ],
    ]);

    $res = json_decode($res->getBody(), true);

    return [
        "zipDocuments" => $res["fileUploadId"],
        "errorZipCreation" => "",
        "zipSizeBytes" => $zipFileSize,
        "zipSizeMB" => round($zipFileSize / (1024 * 1024), 2) . " MB"
    ];
} catch (\Exception $ex) {
    return [
        "zipDocuments" => "",
        "errorZipCreation" => "Upload failed: " . $ex->getMessage()
    ];
}