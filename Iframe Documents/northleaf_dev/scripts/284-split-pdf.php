<?php 
/**
 * This scirpt split a PDF file to create a new version of 3 pages
 * Created by Daniel Aguilar
**/

// Import required classes from external libraries
use setasign\Fpdi\Fpdi;
use Symfony\Component\Filesystem\Filesystem;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use Smalot\PdfParser\Parser;

// Start execution timer
$time_start = microtime(true);

$apiInstanceRequest = $api->requestFiles(); // API instance for request files
$apiInstance = $api->files();               // API instance for file operations

// Get the path of the uploaded PDF file
$pdfPath = getPdfPath($data["IN_UPLOAD_PDF"]);

// Open the PDF file and read the first line to detect its version
$fh = fopen($pdfPath, 'rb');
$line = fgets($fh);
fclose($fh);

// Extract PDF version using regex
preg_match('/%PDF-(\d\.\d)/', $line, $matches);
$version = $matches[1] ?? null;

try {
    // Convert PDF to version 1.4 if needed
    // if($version != null AND $version > 1.4){
        convertPDF($pdfPath);
    // }
} catch (ExceptionType $e) {
    // Handle conversion exception
} finally {
    // Initialize FPDI to manipulate PDF pages
    $pdf = new Fpdi();
    $pdfFile = $pdfPath;
    $totalPages = $pdf->setSourceFile($pdfFile);

    // If PDF has more than 4 pages, extract specific ones
    if($totalPages > 4){
        $selectedPages = [1,2,3, $totalPages]; // First 3 pages + last page
        foreach ($selectedPages as $page) {
            $template = $pdf->importPage($page);          // Import page
            $size = $pdf->getTemplateSize($template);     // Get page size
            $width = $size['width'];
            $height = $size['height'];

            // Determine orientation (Landscape or Portrait)
            if ($width > $height) {
                $orientation = 'L';
            } else {
                $orientation = 'P';
            }

            // Add page with correct orientation and apply template
            $pdf->addPage($orientation,'letter');
            $pdf->useTemplate($template);
        }

        // Save new PDF file
        $pdf->Output('new_'.$data['_request']['id'].'.pdf','F');

        // Register new file in API
        $dataName = "NEW_IN_UPLOAD_PDF"; 
        $newFile = $apiInstanceRequest->createRequestFile(
            $data['_request']['id'], 
            $dataName, 
            'new_'.$data['_request']['id'].'.pdf'
        );

        // End execution timer and record time
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        $dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];
        $dataTimeExec['Split PDF'][] = $execution_time;

        // Return results
        return [
            "dataTimeExec" => $dataTimeExec,
            "NEW_IN_UPLOAD_PDF" => $newFile->getFileUploadId(),
            "IN_IS_DISCREPANCY" => "false"
        ];
    }
    else {
        // If PDF has 4 or fewer pages, return original file
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        $dataTimeExec = (isset($data['dataTimeExec'])) ? $data['dataTimeExec'] : [];
        $dataTimeExec['Split PDF'][] = $execution_time;

        return [
            "dataTimeExec" => $dataTimeExec,
            "NEW_IN_UPLOAD_PDF" => $data["IN_UPLOAD_PDF"],
            "IN_IS_DISCREPANCY" => "false"
        ];
    }
}

// Function to retrieve PDF file path from API and move it to /tmp
function getPdfPath($pdfId){
    global $apiInstance, $data;
    $file = $apiInstance->getFileById($pdfId);
    $fileName = 'new_'.$data['_request']['id'].'.pdf';
    $file = $apiInstance->getFileContentsById($pdfId);
    rename($file->getPathname(), '/tmp/'.$fileName);
    chmod('/tmp/'.$fileName, 777); // Set permissions
    $fileContents = $file->getPathname();
    return '/tmp/'.$fileName;
}

// Function to convert PDF to version 1.4 using Ghostscript
function convertPDF($path) {
    $command = new GhostscriptConverterCommand();
    $filesystem = new Filesystem();

    $converter = new GhostscriptConverter($command, $filesystem);
    $converter->convert($path,'1.4');
}