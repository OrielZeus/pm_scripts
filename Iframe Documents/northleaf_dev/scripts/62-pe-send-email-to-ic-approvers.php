<?php

/**********************************
 * PE - Send email to IC Approvers
 *
 * by Cinthia Romero
 * modified by Elmer Orihuela
 * modified by Adriana Centellas
 *********************************/
// Import Generic Functions
require_once("/Northleaf_PHP_Library.php");
//Import Swift_SmtpTransport classes into the global namespace
require_once 'vendor/autoload.php';

//Get Global Variables
$apiHost = getenv('API_HOST');
$apiSql = getenv('API_SQL');
$apiUrl = $apiHost . $apiSql;
$emailSettingsCollectionID = getenv('EMAIL_SETTINGS_COLLECTION_ID');
$masterCollectionID = getenv('PE_MASTER_COLLECTION_ID');
$smtpServerUrl = getenv('NORTHLEAF_SMTP_ADDRESS');
$smtpUser = getenv('NORTHLEAF_SMTP_USER');
$smtpPassword = getenv('NORTHLEAF_SMTP_PASSWORD');
$northleafLogo = getenv('NORTHLEAF_LOGO_PUBLIC_URL');
$environmentBaseUrl = getenv('ENVIRONMENT_BASE_URL');
$abePMBlockId = getenv('PE_ABE_PM_BLOCK_ID');
$abePMStartNode = getenv('PE_ABE_PM_BLOCK_START_NODE');

//Initialize Variables
$currentProcess = $data["_request"]["process_id"];
$requestId = $data['_request']['id'];
$parentRequestId = $data['_parent']['request_id'];
$caseNumberId = $data['_request']['case_number'];
$emailsStatus = array();
$statusSend = array();

//Approver User Info
$currentApproverEmail = $data["PE_IC_APPROVER_EMAIL"];
$currentApproverName =$data["PE_IC_APPROVER_NAME"];

//Check if current case belongs to PM Block
if (!empty($data["PE_PARENT_PROCESS_ID"])) {
    $currentProcess = $data["PE_PARENT_PROCESS_ID"];
}
$data = array_merge($data, $data["_parent"]);

//Get notification configuration for IC Approval
$getEmailConfiguration = "SELECT data->>'$.EMS_EMAIL_FROM' AS EMS_EMAIL_FROM,
                                 data->>'$.EMS_EMAIL_FROM_NAME' AS EMS_EMAIL_FROM_NAME,
                                 data->>'$.EMS_EMAIL_SUBJECT' AS EMS_EMAIL_SUBJECT,
                                 data->>'$.EMS_EMAIL_BODY' AS EMS_EMAIL_BODY  
                          FROM collection_" . $emailSettingsCollectionID . "
                          WHERE data->>'$.EMS_PROCESS_ID' = " . $currentProcess . "
                              AND data->>'$.EMS_EMAIL_TYPE' = 'IC_APPROVAL'
                              AND data->>'$.EMS_EMAIL_STATUS' = 'Active'";
$emailConfigurationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getEmailConfiguration));

if (empty($emailConfigurationResponse["error_message"])) {
    //Get Collections IDs
    $queryCollectionID = "SELECT data->>'$.COLLECTION_ID' AS ID
                          FROM collection_" . $masterCollectionID . "
                          WHERE data->>'$.COLLECTION_NAME' IN ('PE_MANDATE_IC_APPROVERS')";
    $collectionInfo = callApiUrlGuzzle($apiUrl, "POST", encodeSql($queryCollectionID));
    if (empty($collectionInfo["error_message"])) {
        //Initialize Email Variables
        $emailFrom = $emailConfigurationResponse[0]["EMS_EMAIL_FROM"];
        $emailFromName = $emailConfigurationResponse[0]["EMS_EMAIL_FROM_NAME"];
        $emailSubject = $emailConfigurationResponse[0]["EMS_EMAIL_SUBJECT"];
        $emailBody = $emailConfigurationResponse[0]["EMS_EMAIL_BODY"];
        //Remove Rows MOIC and IRR if PE_DEAL_TYPE is Primary
        if ($data["PE_DEAL_TYPE"] == "Primary") {
            $pattern = '/<tr id="remove_ini">.*?<\/tr id="remove_end">/s';
            $emailBody = preg_replace($pattern, '', $emailBody);
        }
        //Check file exist and get file names for supporting documentation uploads
        $documentsToObtainName = array();
        // Check DD Rec
        $uploadDDRec = "Yes";
        if ($data["PE_UPLOAD_DD_REC_NA"] === false) {
            $uploadDDRec = "No";
            // Assuming that PE_UPLOAD_DD_REC is an array, we loop through it
            foreach ($data["PE_UPLOAD_DD_REC"] as $fileInfo) {
                $documentsToObtainName[] = $fileInfo["file"];
            }
        }
        $data["PE_UPLOAD_DD_REC_NA_LABEL"] = $uploadDDRec;

        // Check Beat Up
        $uploadBeatUp = "Yes";
        if ($data["PE_UPLOAD_BEAT_UP_NA"] === false) {
            $uploadBeatUp = "No";
            foreach ($data["PE_UPLOAD_BEAT_UP"] as $fileInfo) {
                $documentsToObtainName[] = $fileInfo["file"];
            }
        }
        $data["PE_UPLOAD_BEAT_UP_NA_LABEL"] = $uploadBeatUp;

        // Check Black Hat
        $uploadBlackHat = "Yes";
        if ($data["PE_UPLOAD_BLACK_HAT_NA"] === false) {
            $uploadBlackHat = "No";
            foreach ($data["PE_UPLOAD_BLACK_HAT"] as $fileInfo) {
                $documentsToObtainName[] = $fileInfo["file"];
            }
        }
        $data["PE_UPLOAD_BLACK_HAT_NA_LABEL"] = $uploadBlackHat;

        // Add IC Presentation Document
        foreach ($data["PE_UPLOAD_IC_PRESENTATION"] as $fileInfo) {
            $documentsToObtainName[] = $fileInfo["file"];
        }


        // Get document's names
        $documentsToObtainName = implode(",", $documentsToObtainName);
        $getDocumentsInformation = "SELECT id AS FILE_ID,
                                   file_name AS FILE_NAME
                            FROM media
                            WHERE id IN (" . $documentsToObtainName . ")";
        $documentsInformationResponse = callApiUrlGuzzle($apiUrl, "POST", encodeSql($getDocumentsInformation));

        if (empty($documentsInformationResponse["error_message"])) {
            foreach ($documentsInformationResponse as $document) {
                if (in_array($document["FILE_ID"], array_column($data["PE_UPLOAD_IC_PRESENTATION"], 'file'))) {
                    $ICfiles[] = $document["FILE_NAME"];
                }

                if ($uploadDDRec == "No" && in_array($document["FILE_ID"], array_column($data["PE_UPLOAD_DD_REC"], 'file'))) {
                    $DDfiles[] = $document["FILE_NAME"];
                }

                if ($uploadBeatUp == "No" && in_array($document["FILE_ID"], array_column($data["PE_UPLOAD_BEAT_UP"], 'file'))) {
                    $BEATFiles[] = $document["FILE_NAME"];
                }

                if ($uploadBlackHat == "No" && in_array($document["FILE_ID"], array_column($data["PE_UPLOAD_BLACK_HAT"], 'file'))) {
                    $HATfiles[] = $document["FILE_NAME"];
                }
            }

        }

        $data["PE_UPLOAD_IC_PRESENTATION_DOCUMENT_NAME"] = $ICfiles;
        $data["PE_UPLOAD_DD_REC_DOCUMENT_NAME"] = $DDfiles;
        $data["PE_UPLOAD_BEAT_UP_DOCUMENT_NAME"] = $BEATFiles;
        $data["PE_UPLOAD_BLACK_HAT_DOCUMENT_NAME"] = $HATfiles;

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
        // Attach files to the message
        $apiInstance = $api->requestFiles();

        if (!empty($data["PE_UPLOAD_DD_REC"])) {
            foreach ($data["PE_UPLOAD_DD_REC"] as $index => $fileInfo) {
                if ($uploadDDRec == "No") {
                    $file = $apiInstance->getRequestFilesById($requestId, $fileInfo["file"]);
                    $documentPath = $file->getPathname();
                    // Attach each document with the correct filename from the array
                    if (!empty($data["PE_UPLOAD_DD_REC_DOCUMENT_NAME"][$index])) {
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($data["PE_UPLOAD_DD_REC_DOCUMENT_NAME"][$index]));
                    }
                }
            }
        }

        if (!empty($data["PE_UPLOAD_BEAT_UP"])) {
            foreach ($data["PE_UPLOAD_BEAT_UP"] as $index => $fileInfo) {
                if ($uploadBeatUp == "No") {
                    $file = $apiInstance->getRequestFilesById($requestId, $fileInfo["file"]);
                    $documentPath = $file->getPathname();
                    // Attach each document with the correct filename from the array
                    if (!empty($data["PE_UPLOAD_BEAT_UP_DOCUMENT_NAME"][$index])) {
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($data["PE_UPLOAD_BEAT_UP_DOCUMENT_NAME"][$index]));
                    }
                }
            }
        }

        if (!empty($data["PE_UPLOAD_IC_PRESENTATION"])) {
            foreach ($data["PE_UPLOAD_IC_PRESENTATION"] as $index => $fileInfo) {
                $file = $apiInstance->getRequestFilesById($requestId, $fileInfo["file"]);
                $documentPath = $file->getPathname();
                // Attach each document with the correct filename from the array
                if (!empty($data["PE_UPLOAD_IC_PRESENTATION_DOCUMENT_NAME"][$index])) {
                    $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($data["PE_UPLOAD_IC_PRESENTATION_DOCUMENT_NAME"][$index]));
                }
            }
        }

        if (!empty($data["PE_UPLOAD_BLACK_HAT"])) {
            foreach ($data["PE_UPLOAD_BLACK_HAT"] as $index => $fileInfo) {
                if ($uploadBlackHat == "No") {
                    $file = $apiInstance->getRequestFilesById($requestId, $fileInfo["file"]);
                    $documentPath = $file->getPathname();
                    // Attach each document with the correct filename from the array
                    if (!empty($data["PE_UPLOAD_BLACK_HAT_DOCUMENT_NAME"][$index])) {
                        $message->attach(Swift_Attachment::fromPath($documentPath)->setFilename($data["PE_UPLOAD_BLACK_HAT_DOCUMENT_NAME"][$index]));
                    }
                }
            }
        }
        //Add mandates table
        $mandateTable = "<br>";
        $mandateTable .= "<p>Currency: " . $data["PE_CURRENCY"]. "</p><br>";
        $mandateTable .= "<table width=\"100%\" border='1' style='border: 1px solid #FFF; width:100%; border-collapse: collapse'>";
        $mandateTable .= "<tr>";
        $mandateTable .= "<th style=\"width:25%; padding:2px 10px; background: #F2F2F2;\">Mandate</th>";
        $mandateTable .= "<th style=\"width:50%; padding:2px 10px; background: #F2F2F2;\">IC Approved Amount (full amount, not millions)</th>";
        $mandateTable .= "<th style=\"width:25%; padding:2px 10px; background: #F2F2F2;\">% of Deal</th>";
        $mandateTable .= "</tr>";

        foreach ($data["_parent"]["PE_MANDATES"] as $mandate) {
            $mandateTable .= "<tr>";
            $mandateTable .= "<td style=\"width:25%; padding:2px 10px; border-color: #0022; border-bottom: 1px solid #0022;\">" . $mandate["PE_MANDATE_NAME"] . "</td>";
            $mandateTable .= "<td style=\"width:50%; padding:2px 10px; border-color: #0022; border-bottom: 1px solid #0022;\">" . ($mandate["PE_MANDATE_AMOUNT"] == null ? 0.00 : number_format($mandate["PE_MANDATE_AMOUNT"], 2, '.', ',')) . "</td>";
            $mandateTable .= "<td style=\"width:25%; padding:2px 10px; border-color: #0022; border-bottom: 1px solid #0022;\">" . $mandate["PE_MANDATE_PERCENTAGE_DEAL_FORMATTED"] . "%</td>";
            $mandateTable .= "</tr>";
        }

        $mandateTable .= "<tr>";
        $mandateTable .= "<td style=\"width:25%; padding:2px 10px; border-color: #0022; border-bottom: 1px solid #0022;\"><b>TOTAL</b></td>";
        $mandateTable .= "<td style=\"width:50%; padding:2px 10px; border-color: #0022; border-bottom: 1px solid #0022;\">" . ($data["PE_MANDATE_TOTAL_AMOUNT"] == null ? 0.00 : number_format($data["PE_MANDATE_TOTAL_AMOUNT"], 2, '.', ',')) . "</td>";
        $mandateTable .= "<td style=\"width:25%; padding:2px 10px; border-color: #0022; border-bottom: 1px solid #0022;\"></td>";
        $mandateTable .= "</tr>";
        $mandateTable .= "</table><br>";

        $countApprover = 0; //REMOVE AFTER SMTP IS CONFIGURED CORRECTLY
        $aApprovers = [];

        $buttonsHtml = "<table width='100%'>";
        $buttonsHtml .= "<tr width='50%' style='text-align:center;'>";
        $buttonsHtml .= "<td>";
        $buttonsHtml .= "<a style='color: #ffffff;background-color: #711426;border-color: #711426;border: 1px solid #711426 !important;font-weight: 400;text-transform:capitalize;text-decoration: none;padding: 5px;border-radius: 4px;' href='" . $environmentBaseUrl . "webentry/request/" . $requestId . "/abeNode?addComments=0'>Approve</a>";
        $buttonsHtml .= "</td>";
        $buttonsHtml .= "<td width='50%'>";
        $buttonsHtml .= "<a style='color: #ffffff;background-color: #711426;border-color: #711426;border: 1px solid #711426 !important;font-weight: 400;text-transform:capitalize;text-decoration: none;padding: 5px;border-radius: 4px;' href='" . $environmentBaseUrl . "webentry/request/" . $requestId . "/abeNode?addComments=1'>Add Comments and Approve</a>";
        $buttonsHtml .= "</td>";
        $buttonsHtml .= "</tr>";
        $buttonsHtml .= "</table>";
        $emailBodyWithButtons = $emailBody . $mandateTable . $buttonsHtml;
        //Set Body
        $message->setBody($emailBodyWithButtons, 'text/html');
        $message->setTo($currentApproverEmail, $currentApproverName);
        if (!$mailer->send($message)) {
            $statusSend = [
                "SEND_STATUS" => "ERROR",
                "SEND_MESSAGE" => "Error..." . $mail->ErrorInfo
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
            $arrayNote['NODE_TASK_ID'] = "IC_01";
            $notificationLogcollectionId = getCollectionId("PE_NOTIFICATION_LOG", $apiUrl);
            $url = $apiHost . "/collections/" . $notificationLogcollectionId . "/records";
            $createRecord = callApiUrlGuzzle($url, "POST", ['data' => $arrayNote]);
            //Record on DB
            $queryInsert = "INSERT INTO SEND_NOTIFICATION_LOG ( CASE_NUMBER, NODE_TASK_ID, EMAIL_FROM, TO_SEND, SUBJECT, ATTACH, SEND, DETAIL )
                                                VALUES (" . $caseNumberId . ",
                                                        'IC_01',
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
        $countApprover++; //REMOVE AFTER SMTP IS CONFIGURED CORRECTLY
    }
}
//$dataReturn = $data;
$dataReturn['SEND_DETAIL'] = $statusSend;
return $dataReturn;