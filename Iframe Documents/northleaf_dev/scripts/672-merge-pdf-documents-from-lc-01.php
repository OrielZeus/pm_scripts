<?php 
/*  
 * Merge the PDF documents
 * By Ronald Nina
 * modified by Telmo Chiri
*/

$requestId = $data["_request"]["id"];
// Order in Pdfs to Merge
$attach_files = [
    [
        "type" => "PE_AML_RESULTS_DOCUMENT", 
        "id" => $data["PE_AML_RESULTS_DOCUMENT"] ?? null
    ]
];

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
    
    // New Path
    $newPdf = '/tmp/AML_searches.pdf';
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
            $compatibleFiles++;
        } catch (\Exception $e) {
            $noAttacheds[] = [
                    'file' => $file['file_id'],
                    'description' => "[ERROR] ".$e->getMessage()
                ];
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

            $dataName = 'MERGE_LC01_PDF';
            $newFile = $apiInstance->createRequestFile($requestId, $dataName, $newPdf);

            return [
                'MERGE_LC01_PDF' => $newFile->getFileUploadId(), 
                'NO_MERGED' => $noAttacheds
            ];
            
        } catch (\Exception $e) {
            throw new Exception("The final PDF file could not be saved to '$newPdf'. Details: " . $e->getMessage());
        }
    } else {
        throw new Exception("Process completed. No valid PDF files were found or processed from the provided list. No output file was generated.");
    }
} catch (\Error | \Exception $e) {
    return ['MERGE_LC01_PDF' => null, 
            'MERGE_LC01_PDF_ERROR' => $e->getMessage(),
            'NO_MERGED' => $noAttacheds
            ];
    
}