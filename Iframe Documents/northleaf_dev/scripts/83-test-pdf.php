<?php
require_once __DIR__.'/vendor/autoload.php';

function generatePDF($pathOutput)
{
    // Create a new instance of HTML2PDF
    $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'Letter', 'en', true, 'UTF-8', [15, 15, 15, 15]);
    $northImage = "https://northleaf.dev.cloud.processmaker.net/storage/2311/NorthleafPdfLogo.png";
    // HTML content for the first page
    $page1 = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Northleaf - Page 1</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .content { font-size: 12px; }
            .list { color: red; margin-left: 20px; }
            .list li { margin-bottom: 5px; }
            .header-text { color: #711426; text-decoration: underline; padding-bottom: 5px; font-size: 24px; font-weight: bold;}
            .custom-style {
                font-size: 16px;
            }
       </style>
    </head>
    <body>
        <div class="header">
            <img src="' . $northImage . '" alt="himage">
            <p class="header-text">Portfolio Manager Preview and Authorization</p>
        </div>
        <div class="content">
            <p class="custom-style">Re: <strong>Commitment of up to 60.0 in (the "Investment")</strong></p>
            <p class="custom-style">The undersigned has reviewed the Investment Committee Presentation dated once 00, 1900 and the approval of the Investment Committee regarding the investment to be made by:</p>
            <ul class="list">
                <li class="custom-style">Northleaf Private Equity Holdings (Canada) VII LP</li>
                <li class="custom-style">Northleaf/LPP Private Equity Holdings II LP</li>
                <li class="custom-style">Northleaf CFO II Private Equity Collector Partnership</li>
                <li class="custom-style">Northleaf Secondary Partners Holdings (Canada) II LP</li>
                <li class="custom-style">Northleaf Secondary Partners Holdings (International) II LP</li>
                <li class="custom-style">Northleaf Capital Opportunities Holdings (Canada) Partnership</li>
                <li class="custom-style">Northleaf Private Credit Fund II LP</li>
                <li class="custom-style">Northleaf 0100 Private Equity Holdings LP</li>
                <li class="custom-style">Northleaf 1855 Private Equity Holdings LP</li>
                <li class="custom-style">Northleaf 1600 Secondary Holdings (CA/Canada) LP. (Class A)</li>
                <li class="custom-style">Northleaf Venture Catalyst Fund II LP</li>
                <li class="custom-style">Northleaf LPP Private Equity IV Partnership</li>
                <li class="custom-style">Northleaf Growth Fund Holdings (Canada) Partnership</li>
                <li class="custom-style">Northleaf 1855 Private Equity II LP. (Class A)</li>
                <li class="custom-style">Northleaf 1600 Secondary Holdings II LP. (Class A)</li>
                <li class="custom-style">Northleaf Venture Catalyst Fund (International) III LP</li>
                <li class="custom-style">Northleaf CFO Evergreen Private Equity Holdings LP (CFO IV 2015 Investment Pool)</li>
                <li class="custom-style">Northleaf 1600 Secondary Holdings II LP. (Class B)</li>
                <li class="custom-style">NSP II Collector Partnership</li>
                <li class="custom-style">Northleaf LPP Private Equity V Partnership</li>
                <li class="custom-style">Northleaf Private Equity Holdings (Canada) VIII Partnership</li>
            </ul>
            <br/>
            <p class="custom-style">(collectively, the <strong>"Funds"</strong>)</p>
            <br/>
            <p class="custom-style">Based on a review of the Investment Committee Presentation, the Investment Committee approval and any other factors considered relevant by the undersigned, as a registered Advising Representative of Northleaf Capital Partners (Canada) Ltd. or as the designated senior partner manager of the Funds, the undersigned authorizes the investment to be made by the Funds.</p>
            <div class="signature">
                <p class="custom-style">Signed by:</p>
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:left;width:50%;border-bottom: 1px solid;vertical-align: bottom;"><img src="https://northleaf.dev.cloud.processmaker.net/storage/1825/27_Mary_Jones.png" alt="Firma"
                            style="margin-right: 10px; height: 50px;"></td>
                        <td style="text-align:right;width:10%;vertical-align: bottom;font-size: 16px;"><p>Date:</p></td>
                        <td style="text-align:left;width:40%; border-bottom: 1px solid;vertical-align: bottom;font-size: 16px;"><p>06/06/2024</p></td>
                    </tr>
                </table>
                <p class="custom-style">Ian Carver, Managing Director</p>                
            </div>

        </div>
    </body>
    </html>
    ';

    // HTML content for the second page
    $page2 = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Northleaf - Page 2</title>
        <style>
            body { font-family: Arial, sans-serif; }
            .content { font-size: 12px; }
            .signature { margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class="content">
            <p>Based on a review of the Investment Committee Presentation, the Investment Committee approval and any other factors considered relevant by the undersigned, as a registered Advising Representative of Northleaf Capital Partners (Canada) Ltd. or as the designated senior partner manager of the Funds, the undersigned authorizes the investment to be made by the Funds.</p>
            <div class="signature">
                <p>Signed by:</p>
                <p>Ian Carver, Managing Director</p>
                <p>Stephen Foote, Managing Director</p>
                <p>Lance Lim, Director, Portfolio Strategy & Analytics</p>
                <p>(To be signed by one of the above individuals)</p>
                <p>Date: ______________________</p>
            </div>
        </div>
    </body>
    </html>
    ';

    // Set header and footer
    //$html2pdf->pdf->setHeader('|Portfolio Manager Preview and Authorization|');
    //$html2pdf->pdf->setFooter('|Page {PAGENO}|');

    // Create the PDF
    $html2pdf->writeHTML($page1);
    //$html2pdf->addPage();
    //$html2pdf->writeHTML($page2);

    // Save the PDF to the specified path
    $html2pdf->output($pathOutput, 'F');
}

// Data variables
$data = [
    '_request' => [
        'id' => 1  // Example request ID
    ]
];

// Set the output path for the PDF file
$pathOutput = '/tmp/Investment_Authorization.pdf';

// Generate the PDF
generatePDF($pathOutput);

// Continue with the API processing
$processRequestId = $data['_request']['id'];
$dataName = 'testPDF';
$data['pdfName'] = $dataName;
$apiInstance = $api->requestFiles();
$newFile = $apiInstance->createRequestFile($processRequestId, $dataName, $pathOutput);

$requestFiles = $api->requestFiles()->getRequestFiles($data['_request']['id'])['data'];
if (count($requestFiles) > 0) {
    foreach ($requestFiles as $requestFile) {
        $data[str_replace(' ', '', $requestFile['custom_properties']['data_name'])] = $requestFile['id'];
    }
}
return $data;
?>