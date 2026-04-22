<?php

/**********************************
 * PE - Send email to IC 02 Approvers
 *
 * by Telmo Chiri
 * modified by Adriana Centellas
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");
//Import Swift_SmtpTransport classes into the global namespace
require_once 'vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
//Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$apiToken = getenv("API_TOKEN");
$emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$smtpServerUrl = getenv('NORTHLEAF_SMTP_ADDRESS');
$smtpUser = getenv('NORTHLEAF_SMTP_USER');
$smtpPassword = getenv('NORTHLEAF_SMTP_PASSWORD');
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
$abePMBlockId = getenv('PE_IC02_ABE_PM_BLOCK_ID');
$abePMStartNode = getenv('PE_IC02_ABE_PM_BLOCK_START_NODE');

//Initialize Variables
$currentProcess = $data["_request"]["process_id"];
$requestId = $data['_request']['id'];
$parentRequestId = $data['_parent']['request_id'];
$caseNumberId = $data['_request']['case_number'];
$emailsStatus = array();
$responseData = array();
$statusSend = array();

//Approver User Info
$currentApproverEmail = $data["PE_IC_APPROVER_EMAIL"];
$currentApproverName = $data["PE_IC_APPROVER_NAME"];

//Check if current case belongs to PM Block
if (!empty($data["PE_PARENT_PROCESS_ID"])) {
    $currentProcess = $data["PE_PARENT_PROCESS_ID"];
}
// Get data from _parent node
$data = array_merge($data["_parent"] ?? [], $data);

//Get notification configuration for IC Approval
$getEmailConfiguration = "SELECT data->>'$.EMS_EMAIL_FROM' AS EMS_EMAIL_FROM,
                                 data->>'$.EMS_EMAIL_FROM_NAME' AS EMS_EMAIL_FROM_NAME,
                                 data->>'$.EMS_EMAIL_SUBJECT' AS EMS_EMAIL_SUBJECT,
                                 data->>'$.EMS_EMAIL_BODY' AS EMS_EMAIL_BODY  
                          FROM collection_" . $emailSettingsCollectionID . "
                          WHERE data->>'$.EMS_PROCESS_ID' = " . $currentProcess . "
                              AND data->>'$.EMS_EMAIL_TYPE' = 'IC02_APPROVAL'
                              AND data->>'$.EMS_EMAIL_STATUS' = 'Active'";
$emailConfigurationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getEmailConfiguration));

if (empty($emailConfigurationResponse["error_message"])) {
    //Initialize Email Variables
    $emailFrom = $emailConfigurationResponse[0]["EMS_EMAIL_FROM"];
    $emailFromName = $emailConfigurationResponse[0]["EMS_EMAIL_FROM_NAME"];
    $emailSubject = $emailConfigurationResponse[0]["EMS_EMAIL_SUBJECT"];
    $emailBody = $emailConfigurationResponse[0]["EMS_EMAIL_BODY"];
    
    //Add PDF Generated
    //tlx $documentPDFGenerated = $data["_parent"]["FRA_PDF"];
    $documentPDFGenerated = $data["FRA_PDF"];
    //Get document's names
    $getDocumentsInformation = "SELECT id AS FILE_ID,
                                        file_name AS FILE_NAME
                                FROM media
                                WHERE id = '" . $documentPDFGenerated . "'";
    $documentsInformationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getDocumentsInformation));
    //return [$documentPDFGenerated, $documentsInformationResponse];
    if (empty($documentsInformationResponse["error_message"])) {
        foreach ($documentsInformationResponse as $document) {
            $data["PE_PDF_GENERATED_IC02"] = $document["FILE_ID"];
            $data["PE_PDF_GENERATED_IC02_DOCUMENT_NAME"] = $document["FILE_NAME"];
        }
    }
    //Create the Transport
    $transport = (new Swift_SmtpTransport($smtpServerUrl, 587, 'tls'))
        ->setUsername($smtpUser)
        ->setPassword($smtpPassword);
    //Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);
    //Create a message
    $message = new Swift_Message();
    //Replace variables in subject
    $emailSubject = replaceVariables($emailSubject, $data);
    //Set email subject
    $message->setSubject($emailSubject);
    //Set email From Address"
    $message->setFrom([$emailFrom => $emailFromName]);
    
    //Check if body contains logo
    if (strpos($emailBody, '{NORTHLEAF_LOGO}') !== false) {
        $emailBody = str_replace("{NORTHLEAF_LOGO}", "<img src='" . $message->embed(Swift_Image::fromPath($environmentBaseUrl . $northleafLogo)) . "'  width='200'/>", $emailBody);
    }
    //Replace variables in body
    $emailBody = replaceVariables($emailBody, $data);
    //Attach documents
    $apiInstance = $api->requestFiles();
    // Attach PDF Generate
    //$requestId = 877;
    //$requestId = $parentRequestId;
    $file = $apiInstance->getRequestFilesById($parentRequestId, $data["PE_PDF_GENERATED_IC02"]);
    $documentPath = $file->getPathname();
    //return [$requestId, $file, $documentPath];
    $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($data["PE_PDF_GENERATED_IC02_DOCUMENT_NAME"]));

        $abeCaseCreatedId = $requestId;
        /***
        * Attach PDF File in new request
        ***/
        $fileIdParent = $documentPDFGenerated;
        //Get Size and Name of File
        $urlDocumentAttach = $apiHost . "/files/" . $fileIdParent;
        $getDocumentAttach = callApiUrlGuzzle($urlDocumentAttach, "GET", []);
        $sizeDocument = $getDocumentAttach['size'];
        $nameDocument = $getDocumentAttach['file_name'];
        $urlDocumentAttachContent = $apiHost . "/files/" . $fileIdParent . "/contents";
        //return $urlDocumentAttachContent;
        $getDocumentAttachContent = callApiUrlGuzzle($urlDocumentAttachContent, "GET", [], true);
        //return [1, $getDocumentAttachContent];
        //return ['..',$urlDocumentAttachContent, $getDocumentAttachContent];
        //Attach New File 
        $pathFinalPdf = "/tmp/" . $nameDocument;
        file_put_contents($pathFinalPdf, $getDocumentAttachContent);
        $urlUploadFile =  $apiHost . "/requests/$abeCaseCreatedId/files?data_name=$nameDocument";
        //return [$pathFinalPdf, $urlUploadFile];
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
        //return [$response, curl_error($curl)];
        if ($response !==  false) {
            $response = json_decode($response, true);
            $newFileUploadId = $response['fileUploadId'];
            //UPDATE DATA (FRA_PDF) ON NEW REQUEST
            /*
            $dataRequestUpdate['FRA_PDF'] = $newFileUploadId;
            $dataToUpdate['data'] = $dataRequestUpdate;
            $urlUpdateData = $apiHost . '/requests/' . $abeCaseCreatedId;
            $resUpdate = callApiUrlGuzzle($urlUpdateData, "PUT", $dataToUpdate);
            */
        }
        
        //Add button to call web entry
        $buttonsHtml = "<br><table width='100%'>";
        $buttonsHtml .= "<tr width='100%'>";
        $buttonsHtml .= "<td>";
        $buttonsHtml .= "<a style='color: #ffffff;background-color: #711426;border-color: #711426;border: 1px solid #711426 !important;font-weight: 400;text-transform:capitalize;text-decoration: none;padding: 5px;border-radius: 4px;' ";
        $buttonsHtml .= " href='" . $environmentBaseUrl . "webentry/request/" . $abeCaseCreatedId . "/IC02abeNode'>Confirm Signature</a>";
        $buttonsHtml .= "</td>";
        $buttonsHtml .= "</tr>";
        $buttonsHtml .= "</table>";
        $emailBodyWithButtons = $emailBody . $buttonsHtml;
        

        //Set Body
        $message->setBody($emailBodyWithButtons, 'text/html');
        //return [$data["PE_IC_APPROVER_EMAIL"], $data["PE_IC_APPROVER_NAME"]];
        $message->setTo($currentApproverEmail, $currentApproverName);
        // Send the message
        //if (1 == 2) {
        if (!$mailer->send($message)) {
            $statusSend = [
                "SEND_STATUS" => "ERROR",
                "SEND_MESSAGE" => "Error...". $mail->ErrorInfo
            ];
        } else {
            //Record on collection
            $arrayNote = [];
            $arrayNote['BODY'] = $emailBodyWithButtons;
            $arrayNote['DATE'] = date('Y-m-d');
            $arrayNote['SEND'] = true;
            $arrayNote['ATTACH'] = true;
            $arrayNote['DETAIL'] = null;
            $arrayNote['SUBJECT'] = $emailSubject;
            $arrayNote['TO_SEND'] = $currentApproverEmail;
            $arrayNote['EMAIL_FROM'] = $emailFrom;
            $arrayNote['CASE_NUMBER'] = $caseNumberId;
            $arrayNote['NODE_TASK_ID'] = "SS_01";
            $notificationLogcollectionId = getCollectionId("PE_NOTIFICATION_LOG", $apiUrl);
            $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
            $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
            //Record on DB
            $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumberId . ",
                                                        'SS_01',
                                                        '" . $emailFrom . "',
                                                        '" . $currentApproverEmail . "',
                                                        '" . $emailSubject . "',
                                                        true,
                                                        true,
                                                        ''
                                                    );";
            $responseInsertLog = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryInsert));

            $statusSend = [
                "SEND_STATUS" => "OK",
                "SEND_MESSAGE" => ""
            ];
        }
    
}
$dataReturn = $data;
$dataReturn['SEND_DETAIL_IC02'] = $statusSend;
$dataReturn['PE_CONFIRM_SIGNATURE_IC2'] = false;
return $dataReturn;