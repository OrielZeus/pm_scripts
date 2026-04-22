<?php 
/*  
Merge the PDF documents
By Ronald Nina
Modified by Adriana Centellas
*/

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
    // [
    //     "type" => "IN_UPLOAD_EXCEL",
    //     "id" => $data["IN_UPLOAD_EXCEL"] ?? null
    // ],
    /*
    [
        "type" => "IN_DHS01_PDF_ID",
        "id" => $data["IN_DHS01_PDF_ID"] ?? null
    ],*/
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
    
    foreach ($attach_files as $document) {
        $fileId = $document["id"];
        $type = $document["type"];
        // Attach PDF Generate
        $documents = [];
        if (is_array($fileId)) {
            $documents = $fileId;
        } else {
            $documents[] = [
                'file' => $fileId
            ];
        }
        foreach ($documents as $doc) {
            $fileId = $doc['file'];
            if ($fileId) {
                $infoFile = $apiIndividualInstance->getFileById($fileId);
                $fileName = $infoFile->getFileName();
                
                $contentFile = $apiIndividualInstance->getFileContentsById($fileId);
                $fileContents = file_get_contents($contentFile->getPathname());

                $extension = end(explode('.', $fileName));
                // We create a new path for pdf
                $documentPath = '/tmp/file'. $n .'.' . $extension;
                file_put_contents($documentPath, $fileContents); 
                

                /*$filePath = $apiInstance->getRequestFilesById($requestId, $fileId);
                $documentPath = $filePath->getPathname();*/
                
                array_push($filesToMerge, [
                    "file_id" => $fileId,
                    "file_name" => $fileName,
                    "file_name_aux" => 'file'. $n .'.' . $extension,
                    "extension" => $extension,
                    "path" => $documentPath,
                    "type" => $type
                ]);
                $n++;
            }
        }
    }
    //return $filesToMerge;
    // New Path
    $invoiceNumber = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data["IN_INVOICE_NUMBER"]);
    $vendorName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data["vendorInformation"][0]["VENDOR_LABEL"]) . '_' . $invoiceNumber;
    $newPdf = "/tmp/{$vendorName}.pdf";
    $compatibleFiles = 0;
    $commandMerge = "pdftk ";
    // Loop Path Files
    foreach($filesToMerge as $file) {
        if (!is_file($file['path'])) {
            $noAttacheds[] =  [
                    'file' => $file['file_id'],
                    'description' => '[OMITTED] The file does not exist or is not a file'
                ];
            continue; // Go to next file
        }
        
        if ($file['extension'] !== 'pdf') {
            $noAttacheds[] = [
                'file' => $file['file_id'],
                'description' => "[OMITTED] The file extension doesn't '.pdf'($extension)"
            ];
            //array_push($noAttacheds, [$file['type'] => "[OMITTED] The file extension doesn't '.pdf'($extension)"]);
            continue; // Go to next file
        }

        try {
            $pathCompatible = "/tmp/comp_" . $file['file_name_aux'];
            
            $commandCompatiblePDF = "pdftk " . $file['path'] . " output $pathCompatible 2>&1";
            @exec($commandCompatiblePDF, $outputCompatible, $codeCompatible);
            if ($codeCompatible != 0) {
                $noAttacheds[] = [
                    'file' => $file['file_id'],
                    'description' => "[ERROR] Error in the convert the compatible PDF($codeCompatible) " . json_encode($outputCompatible)
                ];
               //array_push($noAttacheds, [$file['type'] => "[ERROR] Error in the convert the compatible PDF($codeCompatible)" . json_encode($outputCompatible)]); 
            }
            $file['path'] = $pathCompatible;
            $commandMerge .= $file['path'] . ' ';
            //return [$commandCompatiblePDF, $output, $code];
            $compatibleFiles++;
        } catch (\Exception $e) {
            $noAttacheds[] = [
                    'file' => $file['file_id'],
                    'description' => "[ERROR] ".$e->getMessage()
                ];
            //array_push($noAttacheds, [$file['type'] => "[ERROR] ".$e->getMessage()]);
            continue; 
        }

    }

    if ($compatibleFiles > 0) {
        try {
            $commandMerge .= 'cat output ' . $newPdf . ' 2>&1';
            @exec($commandMerge, $outputMerge, $codeMerge);
            if ($codeMerge != 0) {
                throw new Exception("[ERROR] Error in the merge the documents PDF error($codeMerge): " . json_encode($outputMerge));
                //array_push($noAttacheds, [$file['type'] => "[ERROR] Error in the merge the documents PDF error($codeMerge): " . json_encode($outputMerge)]); 
            }

            $dataName = 'MERGE_PDF';
            $newFile = $apiInstance->createRequestFile($requestId, $dataName, $newPdf);

            return ['MERGE_PDF' => $newFile->getFileUploadId(), 'NO_MERGED' => $noAttacheds];
            
        } catch (\Exception $e) {
            throw new Exception("The final PDF file could not be saved to '$newPdf'. Details: " . $e->getMessage());
        }
    } else {
        throw new Exception("Process completed. No valid PDF files were found or processed from the provided list. No output file was generated.");
    }
} catch (\Error | \Exception $e) {
    return ['MERGE_PDF' => null, 
            'MERGE_PDF_ERROR' => $e->getMessage(),
            'NO_MERGED' => $noAttacheds
            ];
    
}