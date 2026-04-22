<?php
/*************************
* Convert PDF to Image
*
* by Telmo Chiri
*************************/
// Get Data (PM Block)
$parentRequestId = $data['_parent']['request_id'];
$requestId = $parentRequestId;
$file_id = $data[$data['_parent']['config']['file_id']];
$image_type = $data['_parent']['config']['image_type'] ?? 'png';
$variable_output = $data['_parent']['config']['output_variable'];
$processing = $data['_parent']['config']['processing'];
if ($processing == 'FROM_VARIABLE') {
    $processing = $data[$data['_parent']['config']['processing_variable']] ?? 'COMBINE';
}
//Guzzle connection info
$pmheaders = [
    'Authorization' => 'Bearer ' . getenv('API_TOKEN'),
    'Accept'        => 'application/json',
];
$apiHost = getenv('API_HOST');
$client = new GuzzleHttp\Client(['verify' => false]);

// Get Media File info
$responseFile = $client->request('GET', "$apiHost/files/$file_id" , ["headers" => $pmheaders]);
$responseFile = json_decode($responseFile->getBody(), true);
// Define name of new image file
$name_new_img = 'IMG_' . $responseFile['name'];
// Get Url of PDF file
$pdfUrl = $responseFile['original_url'];
// Temporal Local File
$pdfFilePath = '/tmp/new_local_file.pdf'; // Path where the downloaded PDF will be saved locally
// Define Output Variable
$outConvert = null;
// Resolution
$resolution = 300;
//tlx try {
    // One Image per Page
    if ($processing == 'INDIVIDUAL') {
        // Download the PDF file locally
        if (!copy($pdfUrl, $pdfFilePath)) {
            throw new Exception('Could not download PDF file.');
        }
        // Create a new Imagick instance
        $pdf = new Imagick();
        $pdf->setResolution($resolution, $resolution);
        // Read the PDF file
        $pdf->readImage($pdfFilePath);
        // Number of pages in the PDF
        $pages = $pdf->getNumberImages();
        // Define Output Variable
        $outConvert = [];
        // Iterate over each page and save it as an image
        for ($i = 0; $i < $pages; $i++) {
            //Iterate by Page
            $pdf->setIteratorIndex($i);
            $pdf->setImageFormat($image_type);
            $imagePath = '/tmp/'. $name_new_img .'_' . ($i + 1) . '.' . $image_type;
            // Write per Page
            if ($pdf->writeImage($imagePath)) {
                // Upload new image file to request
                $res = $client->request('POST', $apiHost . "/requests/" . $requestId . "/files?data_name=" . $name_new_img .'_' . ($i + 1) , array(
                        "headers" => $pmheaders,
                        "multipart" => array(
                            array(
                                "Content-type" => "multipart/form-data",
                                "name"     => "file",
                                "contents" => fopen($imagePath, 'r'),
                                "filename" => $name_new_img .'_' . ($i + 1) . '.' . $image_type
                            )
                        ),
                    ));
                $res = json_decode($res->getBody(), true);
                //Get New File Upload Id
                if ($res['fileUploadId']) {
                    $outConvert[] = $res['fileUploadId'];
                }
            }
        }
        // Clean resources
        $pdf->clear();
        $pdf->destroy();
    }
    // One Image per Document
    if ($processing == 'COMBINE') {
        // Download the PDF file locally
        if (!copy($pdfUrl, $pdfFilePath)) {
            throw new Exception('Could not download PDF file.');
        }
        // Create a new Imagick instance
        $imagick = new Imagick();
        $imagick->setResolution($resolution, $resolution);
        // Read the PDF file
        $imagick->readImage($pdfFilePath);

        // Number of pages in the PDF
        $numPages = $imagick->getNumberImages();
        // Create a new blank image to contain all the pages
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight() * $numPages;

        //tlx
        //$width = 1080;
        //$height = 595;
        // Create a new Imagick instance for the Combined image
        $combinedImage = new Imagick();
        $combinedImage->newImage($width, $height, new ImagickPixel('white'));

        // Combine all PDF pages into a single image
        $offset = 0;
        foreach ($imagick as $page) {
            $combinedImage->compositeImage($page, Imagick::COMPOSITE_DEFAULT, 0, $offset);
            $offset += $page->getImageHeight();
        }
        // Set the output format
        $combinedImage->setImageFormat($image_type);
        // Set Temp url
        $imagePath = '/tmp/' . $name_new_img . '.' . $image_type;
        // Define Output Variable
        $outConvert = '';
        // Save the combined image
        if ($combinedImage->writeImage($imagePath)) {
            // Upload new image file to request
            $res = $client->request('POST', $apiHost . "/requests/" . $requestId . "/files?data_name=" . $name_new_img , array(
                    "headers" => $pmheaders,
                    "multipart" => array(
                        array(
                            "Content-type" => "multipart/form-data",
                            "name"     => "file",
                            "contents" => fopen($imagePath, 'r'),
                            "filename" => $name_new_img . '.' . $image_type
                        )
                    ),
                ));
            $res = json_decode($res->getBody(), true);
            //Get New File Upload Id
            if ($res['fileUploadId']) {
                $outConvert = $res['fileUploadId'];
            }
        }
        // Clean resources
        $imagick->clear();
        $imagick->destroy();
        $combinedImage->clear();
        $combinedImage->destroy();
    }

    return [$variable_output => $outConvert];
/*
} catch (ImagickException $e) {
    echo 'Error de Imagick: ' . $e->getMessage();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}*/