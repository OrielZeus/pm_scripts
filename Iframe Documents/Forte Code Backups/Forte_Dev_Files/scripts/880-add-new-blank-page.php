<?php 

use setasign\Fpdi\Fpdi;
use Symfony\Component\Filesystem\Filesystem;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use Smalot\PdfParser\Parser;

require 'vendor/autoload.php';
 $apiInstance = $api->requestFiles();

 
$newFile = getRequestFile();
convertPDF($newFile);
$resp =  Addpage($newFile);

return $resp;

function getRequestFile() {
    global $apiInstance, $data;
    $processRequestId = $data["_request"]["id"];
    $fileId = $data["file_test"];
    $file = $apiInstance->getRequestFilesById($processRequestId, $fileId);
    return $file->getPathname();
} 

function convertPDF($pathFile, $_version = '1.4') {
    $command = new GhostscriptConverterCommand();
    $filesystem = new Filesystem();

    $converter = new GhostscriptConverter($command, $filesystem);
    $converter->convert($pathFile, $_version);
}


function Addpage($pathFile){
    global $data, $apiInstance;
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($pathFile);

    // Import existing pages
    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);
    }

    // Add a NEW blank page
    $pdf->AddPage();
    // You can optionally add content to this new page:
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, '{{SigB_es_:signer1:signature}}
{{N_es_:signer1:fullname}} {{Dte_es_:signer1:date:format(dd/mm/yyyy):font(size=11)}}');
    $newFilename = '/tmp/' . $data["_request"]["id"] . 'output' . '_' . 1 . '.pdf';
    // Output the new PDF
    $pdf->Output('F', $newFilename);

    
    $filePath = $newFilename;
    
    $requestId = $data["_request"]["id"];
    $dataName = 'my_file'; // Name of the variable used in a request
    $file = $filePath; // Path to the file

    $newFile = $apiInstance->createRequestFile($requestId, $dataName, $file);
    return $newFile;
}