<?php 

require_once 'vendor/autoload.php';

$pathToPdf = '/tmp/test.pdf';
$pdf = new \Spatie\PdfToImage\Pdf($pathToPdf);
$pdf->save('/tmp/test.jpg');

return [];