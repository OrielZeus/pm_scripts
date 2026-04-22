<?php 
/**********************************
 * IN - Upload Documents Processing
 *
 * by Helen Callisaya
 *********************************/
require_once("/Northleaf_PHP_Library.php");

//Get global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$urlSQL = $apiHost . $apiSql;

$client = new GuzzleHttp\Client(["verify" => false]);
$pmheaders = [
    "Authorization" => "Bearer " . $apiToken,
    "Accept"        => "application/json",
];

//Necessary variables
$fileId = $data['IN_UPLOAD_PDF']; 
$requestId = $data['_request']['id'];

//Get Name Document
$urlFileOld = $apiHost . '/files/' . $fileId;
$getDataFileOld = callApiUrlGuzzle($urlFileOld, 'GET', []);
$nameFileOld = $getDataFileOld['name'];

//Use the pdftk library
use mikehaertl\pdftk\Pdf;

//Gets the file in a temporary path
$contentDocumentPdf = getContentFile($fileId);
$pathDocumentPdf = '/tmp/documentPdf.php';
file_put_contents($pathDocumentPdf, $contentDocumentPdf);

//Get pages from documents
$pdf = new Pdf($pathDocumentPdf);
$pdfInfo = $pdf->getData() ?? [];
$totalPages = $pdfInfo['NumberOfPages'] ?? 0;

//Check if the document has more than 4 pages.
if ($totalPages > 4) {
    $dataName = "SplitDocument";
    $pdfSplit = new Pdf($pathDocumentPdf);
    $secondToLastPage = $totalPages - 1;
    $pathFinalPdf = '/tmp/SplitDocument ' . $nameFileOld . '.pdf';
    //Generate the new pdf document
    $pdfNew = $pdfSplit->cat([1, 2, $secondToLastPage, $totalPages])
                       ->saveAs($pathFinalPdf);
    //Save the new document in the requests
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
    curl_close($curl);
} else {
    $newDocument = $fileId;
}
$dataReturn = [];
$dataReturn['IN_INVOICE_VENDOR_DOCUMENT_SHORT'] = $newDocument;
return $dataReturn;

/* 
 * Gets the contents of the file 
 *
 * @param string $fileId
 * @param boolean $createdFile
 * @return string $filePath 
 *
 * by Helen Callisaya 
 */
function getContentFile($fileId, $createdFile = false) {
    global $pmheaders, $apiHost, $client;
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