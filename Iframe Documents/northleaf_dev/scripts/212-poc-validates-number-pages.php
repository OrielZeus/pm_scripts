<?php 
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$client = new GuzzleHttp\Client(["verify" => false]);
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$urlSQL = $apiHost . $apiSql;
$pmheaders = [
    "Authorization" => "Bearer " . $apiToken,
    "Accept"        => "application/json",
];

//Id File
$fileId = $data['FILE1']; 
$requestId = $data['_request']['id'];

//Get Name Document
$urlFileOld = $apiHost . '/files/' . $fileId;
$getDataFileOld = apiGuzzle($urlFileOld, 'GET', []);
$dataFileOld = $getDataFileOld; //Data File
$nameFileOld = $dataFileOld['name'];

use mikehaertl\pdftk\Pdf;

$contentDocumentPdf = getFile($fileId);
$pathDocumentPdf = '/tmp/documentPdf.php';
file_put_contents($pathDocumentPdf, $contentDocumentPdf);

$pdf = new Pdf($pathDocumentPdf);
$pdfInfo = $pdf->getData() ?? [];
$totalPages = $pdfInfo['NumberOfPages'] ?? 0;

if ($totalPages > 4) {
    $dataName = "Split";
    $pdf2 = new Pdf($pathDocumentPdf);
    $after = $totalPages - 1;
    $pathFinalPdf = '/tmp/Split ' . $nameFileOld . '.pdf';
    $pdfNew = $pdf2->cat([1, 2, $after, $totalPages])
                   ->saveAs($pathFinalPdf);
    $urlUploadFile =  $apiHost . "/requests/$requestId/files?data_name=$dataName";
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $urlUploadFile,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array('file' => new \CURLFILE($pathFinalPdf)),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: multipart/form-data'
        ),
    ));

    $response = curl_exec($curl);
    if ($response !==  false) {
        $response = json_decode($response, true);
        $newDocument = $response['fileUploadId'];
    } else {
        $newDocument = $fileId;    
    }
} else {
    $newDocument = $fileId;
}
$dataReturn = [];
$dataReturn['FILE_SEND_IMAGE'] = $newDocument;

return $dataReturn;
/* 
 * Call Processmaker API with Guzzle
 *
 * @param (String) $url
 * @param (String) $requestType
 * @param (Array) $postfiles
 * @return (Array) $res
 *
 * by Elmer Orihuela 
 */
function apiGuzzle($url, $requestType, $postfiles, $contentFile = false)
{
    global $apiToken, $apiHost;
    $acceptType = $contentFile ? "'application/octet-stream'" : "application/json";
    
    // Set headers for the API request
    $headers = [
        "Accept" => $acceptType,
        'Authorization' => 'Bearer ' . $apiToken
    ];
    if ($contentFile === false) {
        $headers["Content-Type"] = "application/json";
    }
    
    // Create a Guzzle client
    $client = new Client([
        'base_uri' => $apiHost,
        'verify' => false,
    ]);
    // Create a Guzzle request
    $request = new Request($requestType, $url, $headers, json_encode($postfiles));
    try {
        // Send the request and wait for the response
        $res = $client->sendAsync($request)->wait();
        $res = $res->getBody()->getContents();
    } catch (Exception $exception) {
        // Handle any exceptions and capture the response body
        $responseBody = $exception->getResponse()->getBody(true);
        $res = $responseBody;
    }
    // Decode the response if it's not a file content
    if ($contentFile === false) {
        $res = json_decode($res, true);
    }
    return $res;
}

/*
 * Encode SQL
 *
 * @param (String) $string
 * @return (Array) $variablePut
 *
 * by Elmer Orihuela
 */
function encodeSql($string)
{
    // Encode the SQL query to base64
    $variablePut = [
        "SQL" => base64_encode($string)
    ];
    return $variablePut;
}

function getFile($fileId, $createdFile = false) {
    global $pmheaders,$apiHost, $client;
    $pmheaders['Accept'] = 'application/octet-stream';
    try {
        $res = $client->request("GET",  $apiHost . "/files/$fileId/contents", [
            "headers" => $pmheaders,
            'http_errors' => false
        ]);
        $response = $res->getBody()->getContents();

        if ($createdFile !== false) {
            //Create file in temporary folder
            $tempFolder = trim(sys_get_temp_dir() . PHP_EOL);
            $filePath = $tempFolder . DIRECTORY_SEPARATOR . $createdFile;
            $file = fopen($filePath, 'w');
            fwrite($file, $response);
            fclose($file);
            return $filePath;
        }
        return $response;
    } catch (Exception $e) {
        return null;
    }
}