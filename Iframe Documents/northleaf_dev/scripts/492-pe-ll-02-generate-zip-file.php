<?php 
/**********************************
 * PE - LL.02 Generate Zip File
 *
 * by Adriana Centellas
 * modified by Telmo Chiri
 *********************************/
// CREATE ZIP FILE
$requestId = $data["_request"]["parent_request_id"] ?? ($data["_request"]["id"] ?? '');
$zipFileId = '';

// Initial Validation
if (empty($data["PE_AML_RESULTS_DOCUMENT"])) {
    return [
        "zipAMLDocuments" => "",
        "errorZipAMLCreation" => "No AML files are loaded"
    ];
}

// Guzzle connection info
$pmheaders = [
    'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
    'Accept'        => 'application/json',
];
$apiHost = getenv('API_HOST');
$client = new GuzzleHttp\Client(['verify' => false]);

$identifierDataName = "allAMLDocuments";
$zipFileName = $identifierDataName . ".zip";
$zipFilePath = '/tmp/' . $zipFileName;

// Create ZIP archive
$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    return [
        "zipAMLDocuments" => "",
        "errorZipCreation" => "Could not create zip file"
    ];
}

// Retrieve files from request
$apiInstance = $api->requestFiles();
$result = $apiInstance->getRequestFiles($requestId);
$newFiles = [];

foreach ($result->getData() as $file) {
    // Skip self-reference and filter only AML files
    if ($file['custom_properties']['data_name'] === 'PE_AML_RESULTS_DOCUMENT') {
        $newFiles[] = $file;
    }
}

// Add files to ZIP
foreach ($newFiles as $newFile) {
    try {
        $fileId = $newFile["id"];
        $fileName = $newFile["file_name"];
        $filePath = '/tmp/' . $fileName;

        $client->request('GET', $apiHost . "/files/$fileId/contents", [
            'headers' => $pmheaders,
            'sink' => $filePath
        ]);

        $zip->addFile($filePath, $fileName);
    } catch (Exception $ex) {
        return [
            "zipAMLDocuments" => "",
            "errorZipAMLCreation" => "Error adding file to zip: " . $ex->getMessage()
        ];
    }
}

$zip->close();

// Upload final ZIP
$res = $client->request('POST', $apiHost . "/requests/$requestId/files?data_name=$identifierDataName", [
    "headers" => $pmheaders,
    "multipart" => [
        [
            "Content-type" => "multipart/form-data",
            "name"     => "file",
            "contents" => file_get_contents($zipFilePath),
            "filename" => $zipFileName
        ]
    ],
]);

$res = json_decode($res->getBody(), true);
$zipFileId = $res["fileUploadId"];

return [
    "zipAMLDocuments" => $zipFileId,
    "errorZipAMLCreation" => ""
];