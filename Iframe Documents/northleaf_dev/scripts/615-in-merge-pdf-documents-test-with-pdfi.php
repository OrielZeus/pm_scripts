<?php 
/*  
 *  Welcome to ProcessMaker 4 Script Editor 
 *  To access Environment Variables use getenv("ENV_VAR_NAME") 
 *  To access Request Data use $data 
 *  To access Configuration Data use $config 
 *  To preview your script, click the Run button using the provided input and config data 
 *  Return an array and it will be merged with the processes data 
 *  Example API to retrieve user email by their ID $api->users()->getUserById(1)['email'] 
 *  API Documentation https://github.com/ProcessMaker/docker-executor-php/tree/master/docs/sdk 
 */
// Incluir el autoloader de Composer
require_once('vendor/autoload.php');

// Usar las clases necesarias con sus namespaces
use setasign\Fpdi\Tcpdf\Fpdi;

$requestId = $data["_request"]["id"];
// Order in Pdfs to Merge
$attach_files = [
    [
        "type" => "IN_FX_UPLOAD_FILE", 
        "id" => $data["IN_FX_UPLOAD_FILE"] ?? null
    ],
    [
        "type" => "IN_FINAL_PDF_ID",
        "id" => $data["IN_FINAL_PDF_ID"] ?? null
    ],
    [
        "type" => "IN_UPLOAD_PDF",
        "id" => $data["IN_UPLOAD_PDF"] ?? null
    ],
    [
        "type" => "IN_UPLOAD_EXCEL",
        "id" => $data["IN_UPLOAD_EXCEL"] ?? null
    ],
    [
        "type" => "IN_ADDITIONAL_FILES",
        "id" => $data["IN_ADDITIONAL_FILES"] ?? null
    ],
];
//return $attach_files;
$apiInstance = $api->requestFiles();
$apiIndividualInstance = $api->files();
try {
    $filesToMerge = [];
    $noAttacheds = [];
    $n = 1;
    $archivosProcesados = 0;
    foreach ($attach_files as $document) {
        $fileId = $document["id"];
        $type = $document["type"];
        // Attach PDF Generate
        if ($fileId) {
            $infoFile = $apiIndividualInstance->getFileById($fileId);
            $fileName = $infoFile->getFileName();
            
            $contentFile = $apiIndividualInstance->getFileContentsById($fileId);
            $fileContents = file_get_contents($contentFile->getPathname());
            // We create a new path for pdf
            $documentPath = '/tmp/file'. $n .'.pdf';
            file_put_contents($documentPath, $fileContents); 
            
            
            
            /*$filePath = $apiInstance->getRequestFilesById($requestId, $fileId);
            $documentPath = $filePath->getPathname();*/
            array_push($filesToMerge, [
                "file_name" => $fileName,
                "path" => $documentPath,
                "type" => $type
            ]);
            $n++;
        }
    }
//return $filesToMerge;
    // New Path
    $newPdf = '/tmp/Merged_Document.pdf';
    
    $pdf = new Fpdi();

    // Loop Path Files
    foreach($filesToMerge as $file) {
        if (!is_file($file['path'])) {
            array_push($noAttacheds, [$file['type'] => '[OMITTED] The file does not exist or is not a file']);
            continue; // Go to next file
        }
        $extension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            array_push($noAttacheds, [$file['type'] => "[OMITTED] La extensión no es '.pdf' (es '.$extension')"]);
            continue; // Go to next file
        }

        try {
            // Establecer el archivo fuente para FPDI
            $pageCount = $pdf->setSourceFile($file['path']);
            // Import each page from the current file
            for ($pagina = 1; $pagina <= $pageCount; $pagina++) {

                // Import the page
                $templateId = $pdf->importPage($pagina);
                // Get the size and Orientation of the imported page
                $size = $pdf->getTemplateSize($templateId);

                // Add a new page to the output document with the same size and orientation
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

                // Use (draw) the imported template on the new page
                $pdf->useTemplate($templateId);
            }
            $archivosProcesados++;
        } catch (\Exception $e) {
            // Exception of FPDI
            array_push($noAttacheds, [$file['type'] => "[ERROR] ".$e->getMessage()]);
            continue; // Go to the next file
        }
    }

    if ($archivosProcesados > 0) {
        try {
            // 'F' Save file
            $pdf->Output($newPdf, 'F');

            $dataName = 'MERGE_PDF';
            $newFile = $apiInstance->createRequestFile($requestId, $dataName, $newPdf);

            return ['MERGE_PDF' => $newFile->getFileUploadId(), 'NO_MERGED' => $noAttacheds];
            
        } catch (\Exception $e) {
            echo "\n¡Error Crítico! No se pudo guardar el archivo PDF final en '$archivoSalida'. Detalles: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\nProceso finalizado. No se encontraron o procesaron archivos PDF válidos de la lista proporcionada. No se generó ningún archivo de salida.\n";
    }
} catch (\Error | \Exception $e) {
    return [
        "status" => "error",
        "message" => $e->getMessage()
    ];
}