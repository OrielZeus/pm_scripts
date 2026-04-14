<?php 
/*****************************************
* Create an agremeent in adobe sign
*
* by Diego Tapia
*****************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Filesystem\Filesystem;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverterCommand;
use Xthiago\PDFVersionConverter\Converter\GhostscriptConverter;
use Smalot\PdfParser\Parser;
require 'vendor/autoload.php';

//Initialize variables
$apiInstanceFiles = $api->requestFiles();

// Get markets configuration
$apiInstanceCollections = $api->collections();
$collections = json_decode(json_encode($apiInstanceCollections->getCollections(null,"ID", "desc", "1000")->getData()));
$marketsCollection = $collections[array_search("CQP_FORTE_CARGO_REINSURER", array_column($collections, "name")) ];
$markets = array_column(json_decode(json_encode($apiInstanceCollections->getRecords($marketsCollection->id, null)->getData())), "data");

// Set initial variables for adobe creation
$response = [
    "CQP_ADOBE_SUCCESS" => true,
    "CQP_ADOBE_MESSAGE" => "",
    "CQP_SUBMIT_ADOBE" => "WAIT",
    "CQP_ADOBE_DECLINED_COMMENT" => null,
    "CQP_STATUS" => null  
];

$HEIGHT_PDF_CELL = 5;
$FONT_PDF_CELL = 6;
$MARGIN_PDF = 10;
$signPdf = null;
$agreementID = "";
$expirationDate = "";
$requestDocumentsArray = [];
$agreementCreationResponse = "";
$collections = null;
$variableMatchList = [];

// set default values for  variables in pdf
$data["CQP_CURRENT_DATE"] = date("d/m/Y");
$data["CQP_DEF_VALUE_ADOBE"] = "Test Value";
$data["CQP_INCEPTION_DATE_FORMAT"] = date("d/m/Y", strtotime($data["CQP_INCEPTION_DATE"]));
$data["CQP_EXPIRATION_DATE_FORMAT"] = date("d/m/Y", strtotime($data["CQP_EXPIRATION_DATE"]));
$data["CQP_ADOBE_START_DATE"] = date("d/m/Y", time());
$installmentList = "";

foreach ($data["CQP_INSTALLMENTS_DATE"] as $indexInst => $installment) {
    $installmentList .= "Installment " . ($indexInst + 1) . ":   " 
    . date("d/m/Y", strtotime($installment["CQP_INSTALLMENTS_DATE_INPUT"])) . ($indexInst != (count($data["CQP_INSTALLMENTS_DATE"]) -1) ? " \n" : "");
}

$data["CQP_INSTALLMENT_LIST"] = $installmentList;

// get Adobe Client
if (!createAdobeclient()) return $response;

// Get Request documents
$result = $apiInstanceFiles->getRequestFiles($data["_request"]["id"]);

//Get Generated Documents Type (PM Relation)
$hasSlip = false;
$hasReplaceSlip = false;
$slipData = [];
$replaceSlipData = [];

if (!empty($data["CQP_ADOBE_WORKFLOW_LIST"])) {
    foreach ($data['CQP_ADOBE_WORKFLOW_LIST'] as $documentsWorkflow) {
        if (!$documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["UPLOAD_FILE"] && $documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["DOC_ID"] !== "SIGNED-SLIP") {
            $requestDocumentsArray[] = [
                "CQP_ADOBE_DOCUMENT_LABEL" => $documentsWorkflow['CQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL'],
                "CQP_PM_DOCUMENT_ID" => null,
                "CQP_PM_DOCUMENT_NAME" => null,
                "CQP_DOCUMENT_GENERATED" => false,
                "CQP_DOCUMENT_LIB_ID" => $documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["DOC_ID"],
                "CQP_SEND_FILE_MAIL" => $documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"],
                "CQP_EDITABLE_SEND_FILE_MAIL" => !$documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"]
            ];
        } else {
            if (isset($result["data"])) {
                foreach ($result["data"] as $requestFile) {
                    if ($requestFile["custom_properties"]["data_name"] == "CQP_REPLACE_SLIP") {
                        $hasReplaceSlip = true;
                        $replaceSlipData = $requestFile;
                    } elseif (($requestFile["custom_properties"]["data_name"] == "CQP_SLIP" && $data["CQP_RENEWAL_CARGO"] != "YES") || ($requestFile["custom_properties"]["data_name"] == "CQP_NEW_FILE_UPLOAD" && $data["CQP_RENEWAL_CARGO"] == "YES")) {
                        $hasSlip = true;
                        $slipData = $requestFile;
                    } 
                    
                    if ($requestFile['id'] === $documentsWorkflow['CQP_ADOBE_WORKFLOW_DOCUMENTS_UPLOAD'] && $documentsWorkflow['CQP_ADOBE_DOCUMENTS_OPTIONS']['DOC_ID'] !== "SIGNED-SLIP") {
                        $requestDocumentsArray[] = [
                            "CQP_ADOBE_DOCUMENT_LABEL" => $documentsWorkflow['CQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL'],
                            "CQP_PM_DOCUMENT_ID" => $documentsWorkflow['CQP_ADOBE_WORKFLOW_DOCUMENTS_UPLOAD'],
                            "CQP_PM_DOCUMENT_NAME" => $requestFile["file_name"],
                            "CQP_DOCUMENT_GENERATED" => false,
                            "CQP_DOCUMENT_LIB_ID" => $documentsWorkflow["DOC_ID"],
                            "CQP_SEND_FILE_MAIL" => $documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"],
                            "CQP_EDITABLE_SEND_FILE_MAIL" => !$documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"]
                        ];
                    }
                }
            }
        }

        // Recover slip file from request data
        if ($documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["DOC_ID"] == "SIGNED-SLIP") {
            if ($hasReplaceSlip) {
                $requestDocumentsArray[] = [
                    "CQP_ADOBE_DOCUMENT_LABEL" => $documentsWorkflow['CQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL'],
                    "CQP_PM_DOCUMENT_ID" => $replaceSlipData["id"],
                    "CQP_PM_DOCUMENT_NAME" => $replaceSlipData["file_name"],
                    "CQP_DOCUMENT_GENERATED" => false,
                    "CQP_DOCUMENT_LIB_ID" => null,
                    "CQP_SEND_FILE_MAIL" => $documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"],
                    "CQP_EDITABLE_SEND_FILE_MAIL" => !$documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"],
                    "CQP_ADD_PAGE" => true
                ];
            } elseif($hasSlip) {
                $requestDocumentsArray[] = [
                    "CQP_ADOBE_DOCUMENT_LABEL" => $documentsWorkflow['CQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL'],
                    "CQP_PM_DOCUMENT_ID" => $slipData["id"],
                    "CQP_PM_DOCUMENT_NAME" => $slipData["file_name"],
                    "CQP_DOCUMENT_GENERATED" => false,
                    "CQP_DOCUMENT_LIB_ID" => null,
                    "CQP_SEND_FILE_MAIL" => $documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"],
                    "CQP_EDITABLE_SEND_FILE_MAIL" => !$documentsWorkflow["CQP_ADOBE_DOCUMENTS_OPTIONS"]["REQUIRED"],
                    "CQP_ADD_PAGE" => true
                ];
            }
        }
    }
    
    //Create Agreement
    if (count($requestDocumentsArray) > 0) {
        $apiInstance = $api->requestFiles();
        $agreementCreationResponse = createAgreement($requestDocumentsArray, $data, $apiInstanceFiles, $data["CQP_ADOBE_WORKFLOW_DOCUMENTS"]["CQP_RECIPIENTS"] ,$currentUserEmail);
        
        if ($agreementCreationResponse["success"]) {
            $response["CQP_AGREEMENT_ID"] = $agreementCreationResponse["agreementID"];
            $response["CQP_EXPIRATION_DATE_ADOBE"] = $agreementCreationResponse["expirationDate"];
            $response["CQP_FILE_LIST"] = $agreementCreationResponse["fileList"];
            $response["CQP_ADOBE_COMPLETE"] = "NO";
        } else {
            setResponse( $agreementCreationResponse["errorMessage"]);
        }
    } else {
        setResponse( "Request documents could not be obtained, please contact your system administrator.");
    }
}

$response["variableMatchList"] = $variableMatchList;

return $response;

/**
 * Creates the client to call the Adobe APIs
 * 
 * @return boolean $response
 *
 * by Diego Tapia
 */ 
function createAdobeclient() {
    global $adobeClient;

    try {
        $adobeClientGet = new Client(
            [
                'base_uri' => getenv("ADOBE_URL"),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer "  . getenv("CQP_INTEGRATION_KEY_ADOBE")
                ],
            ]
        );

        $urls = $adobeClientGet->request('GET', '/api/rest/v6/baseUris');
        $response = json_decode($urls->getBody()->getContents());
        
        $adobeClient = new Client(
            [
                'base_uri' => $response->apiAccessPoint,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => "Bearer "  . getenv("CQP_INTEGRATION_KEY_ADOBE")
                ],
            ]
        );

        return true;
    } catch (\Exception $e) {
        setResponse("Issue in Adobe Client creation, please contact your system administrator");
        return false;
    }

}


/**
 * Call Adobe API
 * 
 * @param string $method
 * @param string $url
 * @param array $data
 * @return array $response
 *
 * by Diego Tapia
 */ 
function callAdobeClient($method, $url, $data = null, $type = "json") {
    global $adobeClient;

        if ($method == "POST" || $method == "PUT") {
            $response = $adobeClient->request($method, '/api/rest/v6/' . $url, [
                $type => $data
            ]);
        } else {
            $response = $adobeClient->request($method, "/api/rest/v6/" . $url);
        }

        $response = json_decode($response->getBody()->getContents());
    try {
        return $response;
    } catch (\Exception $e) {
        setResponse("Issue in API " . $url . ", please contact your system administrator");
        return false;
    }

}


/**
 * Set script response in case of error
 * 
 * @param string $message
 *
 * by Diego Tapia
 */ 
function setResponse($message) {
    global $response;
    $response["CQP_ADOBE_SUCCESS"] = false;
    $response["CQP_ADOBE_MESSAGE"] = $message;
}

/**
 * Validate and create Adobe agreement request
 * 
 * @param array $requestDocuments 
 * @param array $requestData 
 * @param object $apiInstance
 * @param string $userEmail
 * @param string $message
 * @return array $creationResponse
 *
 * by Diego Tapia
 */ 
function createAgreement($requestDocuments, $requestData, $apiInstance, $recipients, $userEmail) {
    global $variableMatchList;

    //Get Workflow characteristics
    $workflowUid = $requestData["CQP_ADOBE_WORKFLOW_SELECTED"];
    $workflowCharacteristics = callAdobeClient("GET", "workflows/" . $workflowUid);

    $creationResponse = [
        "success" => true,
        "errorMessage" => "",
        "agreementID" => "",
        "expirationDate" => ""
    ];

    if (!empty($workflowCharacteristics->name)) {
    
        //Form fileInfos array
        $documentsList = [];
        $documentError = "";
        
        //Form recipients array
        $recipientsList = [];
        $notSigner = [];
        
        foreach ($recipients as $key => $recipient) {
            $tempSigner = [
                "memberInfos" => [
                    0 => [
                        "email" => $recipient["edit"] ? $recipient["newValue"] : $recipient["defaultValue"],
                        "name" => $recipient["name"],
                        "company" => "Company test",
                        "title" => "Signer",
                        "securityOption" => [
                            "authenticationMethod" => "NONE"
                        ]
                    ]
                ],
                "role" => $recipient["role"],
                "label" => $recipient["label"],
                "order" => $key + 1
            ];

            if ($recipient["role"] == "APPROVER") {
                $notSigner[] = $key + 1;
            } 

            $recipientsList[] = $tempSigner;
        };

        $matchedData = [];
        
        foreach ($requestDocuments as &$requestFile) {
            
            if (isset($requestFile["CQP_DOCUMENT_LIB_ID"]) && $requestFile["CQP_DOCUMENT_LIB_ID"] != null) {
                // Get required Variables an map from the fonfigured variables in request
                $variablesTemplate = mapVariables($requestFile["CQP_DOCUMENT_LIB_ID"]);
                $matchedData = $matchedData + $variablesTemplate;

                $documentsList[] = [
                    "libraryDocumentId" => $requestFile["CQP_DOCUMENT_LIB_ID"],
                    "label" => $requestFile['CQP_ADOBE_DOCUMENT_LABEL']
                ];
            } else {
                //Get document path
                $file = $apiInstance->getRequestFilesById($requestData['_request']['id'], $requestFile['CQP_PM_DOCUMENT_ID']);
                $documentPath = $file->getPathname();

                if (count($recipientsList) > 0 && $requestFile["CQP_ADD_PAGE"] === true) {
                    // Add Addintional page with sign fields
                    $documentPathSign = addSignPage($documentPath, $recipientsList, "temp-" . $requestFile["CQP_PM_DOCUMENT_ID"], $notSigner);
                } else {
                    $documentPathSign = $documentPath;
                }

                //Create new document in Adobe Sign
                $adobeDocumentId = createTransientDocument($requestFile['CQP_PM_DOCUMENT_ID'], $documentPathSign);
                
                if ($adobeDocumentId["success"]) {
                    $requestFile["CQP_TRANSIENT_ID"] = $adobeDocumentId["transientDocumentID"];
                    $requestFile["CQP_SIGN_FILE"] = "";
                    $documentsList[] = [
                        "transientDocumentId" => $adobeDocumentId["transientDocumentID"],
                        "label" => $requestFile['CQP_ADOBE_DOCUMENT_LABEL']
                    ];
                } else {
                    $creationResponse["errorMessage"] = $adobeDocumentId["errorMessage"] . " FILE: " . $requestFile['CQP_PM_DOCUMENT_NAME'];
                    break;
                }
            }
        }
        
        // Validate Variables for workflow
        foreach ($workflowCharacteristics->mergeFieldsInfo as $indexField => $field) {
            $exist = false;

            foreach ($matchedData as $currentMatch) {
                if ($currentMatch["fieldName"] == $field->fieldName) {
                    $exist = true;
                }

            }
            
            if (!$exist) {
                $variableMatchList[] = $field->fieldName;
                $matchedData[] = [
                    'fieldName' => $field->fieldName,
                    'defaultValue' => "tempVal"
                ];
            }
        };
        
        if ($documentError == "") {

            //Form ccs array
            $ccsList = [];
            if (!empty($workflowCharacteristics->ccsListInfo)) {
                foreach ($workflowCharacteristics->ccsListInfo as $key=>$cc) {
                    if (!empty($cc->defaultValues[0])) {
                        $ccsList[] = [
                            "email" => $cc->defaultValues[0],
                            "label" => $cc->label
                        ];
                    }
                }
            }

            //Define agreement due date
            $agreementDueDate = "";
            $agreementDueDateToShow = "";

            if (!empty($workflowCharacteristics->expirationInfo)) {
                //Obtain the days to wait
                $daysToWait = $workflowCharacteristics->expirationInfo->defaultValue;

                if ($daysToWait > 0) {
                    $currentDate = date("Y-m-d");
                    $agreementDueDateToShow = date("Y-m-d",strtotime($currentDate."+ " . $daysToWait . " days"));

                    //Change format date to adobe format
                    $agreementDueDate = $agreementDueDateToShow . "T00:00:00Z";
                } 
            }

            //If there was not any error create the agreement
            if (count($documentsList) > 0 && count($recipientsList) > 0) {
                $postFields = [
                    'fileInfos' => $documentsList, 
                    'message' => $workflowCharacteristics->messageInfo->defaultValue,
                    'name' => $workflowCharacteristics->agreementNameInfo->defaultValue . " - #" . $requestData["_request"]["id"],
                    'participantSetsInfo' => $recipientsList,
                    'signatureType'=> 'ESIGN', 
                    'state'=> 'IN_PROCESS',
                    'status' => 'IN_PROCESS',
                    'workflowId' => $workflowUid,
                    'mergeFieldInfo' => $matchedData
                ];
                
                if (count($ccsList) > 0) {
                    $postFields["ccs"] = $ccsList;
                }

                if ($agreementDueDate != "") {
                    $postFields["expirationTime"] = $agreementDueDate;
                }
                //print_r($postFields);die();
                
                $responseCreation = callAdobeClient("POST", "agreements", $postFields);
                
                if (!empty($responseCreation->code)) {
                    $creationResponse = [
                        "success" => false,
                        "errorMessage" => $responseCreation->message,
                        "agreementID" => "",
                        "expirationDate" => ""
                    ];
                } else {

                    $creationResponse = [
                        "success" => true,
                        "errorMessage" => "",
                        "agreementID" => $responseCreation->id,
                        "expirationDate" => $agreementDueDateToShow,
                        "fileList" => $requestDocuments
                    ];
                }
            } else {
                $creationResponse["errorMessage"] = "There was an error trying to get the files or recipients, please contact your system administrator.";
            }
        }
    } else {
        $creationResponse["errorMessage"] = "Workflow configuration could not be obtained, please contact your system administrator.";
    }

    $creationResponse["success"] = $creationResponse["errorMessage"] == "" ? true : false;
    return $creationResponse;
}

/**
 * Create Transient Document
 *
 * @param string $documentName
 * @param string $documentPath
 * @return array $successResponse
 *
 * by Diego Tapia
 */
function createTransientDocument($documentName, $documentPath) {
    $mimeType = mime_content_type($documentPath);
    $file = file_get_contents($documentPath);
    
    $postFields =  [
        [
            'name' => 'File-Name',
            'contents' => $documentName
        ],
        [
            'name' => 'File',
            'contents' => $file,
        ],
        [
            'name' => 'Mime-Type',
            'contents' => $mimeType
        ]
    ];

    $responsCreateFIle = callAdobeClient("POST", "transientDocuments", $postFields, "multipart");

    if (!empty($responsCreateFIle->transientDocumentId)) {
        $successResponse = [
            "success" => true,
            "errorMessage" => "",
            "transientDocumentID" => $responsCreateFIle->transientDocumentId
        ];
    } else {
        $successResponse = [
            "success" => false,
            "errorMessage" => $responsCreateFIle->message,
            "transientDocumentID" => ""
        ];
    }

    return $successResponse;
}


/**
 * Map Adobe template variables with PM Variables/values
 *
 * @param string $templateID
 * @return array $variables
 *
 * by Diego Tapia
 */
function mapVariables ($templateID) {
    global $collections, $api, $data, $variableMatchList;
    $getMappingFields = [];
    $fieldsAdobe = callAdobeClient("GET", "libraryDocuments/" . $templateID . "/formFields");
    
    if ($collections == null) {
        $apiInstance = $api->collections();
        $collections = json_decode(json_encode($apiInstance->getCollections(null,"ID", "desc", "1000")->getData()));
    }
    
    $idCollectionMatch = getCollectionId("CQP_FORTE_CARGO_MATCH_ADOBE_VARIABLE");
    $records = $apiInstance->getRecords($idCollectionMatch, 'data.CQP_TEMPLATE_ID="CARGO-FIELDS-MAP"')->getData();
    
    foreach ($records as &$configurations) {
        $confMatch = $configurations["data"]["CQP_TEMPLATE_VARIABLES"];

        foreach ($data["CQP_MARKETS"] as $indexMarket => $market) {
            $confMatch[] = (object) [
                'CQP_VAR_ADOBE' => 'Signing' . $market["CQP_ADOBE_ALIAS"],
                'CQP_VAR_PM' => 'CQP_MARKETS.' . $indexMarket . '.CQP_FORTE_SHARE',
                'CQP_VAR_MASK' => $market["CQP_MASK_ADOBE_FORTE_SHARE"]
            ];
        }
        
        foreach ($fieldsAdobe->fields as $indexField => $field) {
            $indexFIeld = array_search ($field->name, array_column($confMatch, "CQP_VAR_ADOBE"));
            
            if ($indexFIeld !== false) {
                $value = getFieldValue($confMatch[$indexFIeld]->CQP_VAR_PM);
                
                switch ($confMatch[$indexFIeld]->CQP_VAR_MASK) {
                    case "PERCENTAGE":
                        $value = number_format($value, 2, '.', '') . " %";
                        break;
                }

                $getMappingFields[] = [
                    'fieldName' => $field->name,
                    'defaultValue' => $value
                ];
            } else {
                $variableMatchList[] = $field->name;
            }
        }
    }
    
    return $getMappingFields;
}


/**
* Get Request variable value.
*
* @param string $fieldReference
* @return string $value
*/
function getFieldValue($fieldReference) {
    global $data;
    $requestData = $data;
    $fieldPath = explode('.', $fieldReference);

    foreach ($fieldPath as $key) {
        switch (\gettype($requestData)) {
            case 'array':
                if (!isset($requestData[$key])) {
                    return '';
                }

                $requestData = $requestData[$key];

                break;

            case 'object':
                if (!isset($requestData->{$key})) {
                    return '';
                }

                $requestData = $requestData->{$key};

                break;

            default:
                return $requestData;
        }
    }

    return $requestData;
}


/*
* Get collection ID using the name
*
* @param string $name
* @return int $collectionId
*
* by Diego Tapia
*/
function getCollectionId ($name) {
    global $collections;
    $collection = $collections[array_search ($name, array_column($collections, "name"))];

    if ($collection == null || $collection === false) {
        return false;
    } else {
        return $collection->id;
    }
}


/**
 * Add Signature Page
 *
 * @param string $pathFile
 * @param array $recipients
 * @param string $tempFileName
 * @param array $notSigner
 * @return int $fileID
 *
 * by Diego Tapia
 */
function addSignPage ($pathFile, $recipients, $tempFileName, $notSigner = []) {
    global $HEIGHT_PDF_CELL, $FONT_PDF_CELL, $MARGIN_PDF, $data, $markets, $signPdf;
    
    // Create Sign Page
    $command = new GhostscriptConverterCommand();
    $filesystem = new Filesystem();
    $converter = new GhostscriptConverter($command, $filesystem);

    $copy = '/tmp/copy_' . uniqid() . '.pdf';
    file_put_contents($copy, file_get_contents($pathFile));
    $converter->convert($copy, '1.4');

    $sizePdf = new Fpdi();
    $pageCount = $sizePdf->setSourceFile($copy);
    $templateId = $sizePdf->importPage($pageCount);
    $size = $sizePdf->getTemplateSize($templateId);

    $signPageFile = '/tmp/sign_page_' . uniqid() . '.pdf';
    $signPdf = new Fpdi();  
    $signPdf->SetAutoPageBreak(true, $MARGIN_PDF);
    $signPdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $signPdf->SetMargins($MARGIN_PDF, $MARGIN_PDF, $MARGIN_PDF);

    // Set Approver participant
    $approver = count($notSigner) > 0 ? $notSigner[0] : 1;
    $viewMode = count($notSigner) > 0 ? "" : ":readonly";

    // Fill Sign Page
    $signPdf->SetFont('Arial', 'B', $FONT_PDF_CELL);
    $signPdf->Cell(0, 10, 'CONFIRMACION DE COLOCACION DE REASEGURO', 0, 1, 'C');
    $signPdf->Cell(0, 10, 'REINSURANCE PLACEMENT CONFIRMATION', 0, 1, 'C');
    $content = [];

    $content[] = [
        [
            "vaue" => "FORTE ID:",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "{{ForteID_es_:signer" . $approver . $viewMode  . "}}",
            "width" => "0.5",
            "bold" => false,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "REFERENCIA BROKER/BROKER REFERENCE:",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "N/A",
            "width" => "0.5",
            "bold" => false,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "FECHA/DATE:",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "{{FechaDate_es_:signer" . $approver . $viewMode  . "}}",
            "width" => "0.5",
            "bold" => false,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "RAMO/BRANCH:",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "TRANSPORTE MARITIMO/" . $data["CQP_TYPE"],
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "REASEGURADO/REASSURED:",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "{{                                             ReaseguradoReassured_es_:signer" . $approver . $viewMode  . "                                             }}",
            "width" => "0.5",
            "bold" => false,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "ASEGURADO ORIGINAL/ORIGINAL ASSURED:  ",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "{{                                                 AseguradoAssured_es_:signer" . $approver . $viewMode  . "                                                }}",
            "width" => "0.5",
            "bold" => false,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "INTERMEDIARIO/INTERMEDIARY:",
            "width" => "0.5",
            "bold" => true,
            "border" => 1
        ],
        [
            "vaue" => "{{                                           IntermediarioIntermediary_es_:signer" . $approver . $viewMode  . "                                           }}",
            "width" => "0.5",
            "bold" => false,
            "border" => 1
        ]
    ];

    $content[] = [
        [
            "vaue" => "VIGENCIA/PERIOD:",
            "width" => "0.5",
            "bold" => true,
            "border" => "LTR"
        ],
        [
            "vaue" => "Desde/From ",
            "width" => "0.2",
            "bold" => true,
            "border" => "LT"
        ],
        [
            "vaue" => "{{  DesdeFrom_es_:signer" . $approver . $viewMode  . "  }}",
            "width" => "0.3",
            "bold" => false,
            "border" => "TR"
        ]
    ];

    $content[] = [
        [
            "vaue" => "",
            "width" => "0.5",
            "bold" => false,
            "border" => "LBR"
        ],
        [
            "vaue" => "Hasta/To",
            "width" => "0.2",
            "bold" => true,
            "border" => "LB"
        ],
        [
            "vaue" => "{{    HastaTo_es_:signer" . $approver . $viewMode  . "    }}",
            "width" => "0.3",
            "bold" => false,
            "border" => "BR"
        ]
    ];

    // Add the sign members to the page
    $signer = 1;
    $arrayMarketsFile = [];
    
    for ($i = 1; $i <= count($recipients); $i++) {;
        if (!in_array($signer, $notSigner)) {
            foreach ($markets as $marketSigners) {
                // match configuration from Markets collection to Adobe Workflow recipient
                $indexMarketValues = array_search($recipients[$i - 1]["label"], array_column($marketSigners->CQP_SIGNERS_LIST, "CQP_WORKFLOW_TITLE"));

                if ($indexMarketValues !== false) {
                    $marketAdded = array_search($marketSigners->CQP_ADOBE_LABEL, array_column($arrayMarketsFile, "reinsurer"));

                    // Check if is the second signer in market
                    if ($marketAdded !== false) {
                        
                        if ($marketSigners->CQP_SIGNERS_LIST[$indexMarketValues]->CQP_STAMP) {
                            $arrayMarketsFile[$marketAdded]["content"][4][0] = [
                                "vaue" => "{{Stamp1_es_:signer" . $signer . ":stampimage}}",
                                "width" => "1",
                                "bold" => false,
                                "border" => "LTR",
                                "align" => "C"
                            ]; 

                            $arrayMarketsFile[$marketAdded]["content"][5][0] =  [
                                "vaue" => "",
                                "width" => "1",
                                "bold" => false,
                                "border" => "LR",
                                "heigth" => 38
                            ]; 
                        }

                        $arrayMarketsFile[$marketAdded]["content"][7][1] = [
                            "vaue" => "{{Sig_es_:signer" . $signer . ":signature}}",
                            "width" => "0.5",
                            "bold" => false,
                            "border" => "LR",
                            "font" => 18,
                            "heigth" => 12,
                            "align" => "R"
                        ]; 

                        $arrayMarketsFile[$marketAdded]["content"][8][1] = [
                            "vaue" => "{{N_es_:signer" . $signer . ":fullname:align(center)}}",
                            "width" => "0.5",
                            "bold" => true,
                            "border" => "LR",
                            "align" => "C"
                        ]; 

                        if ($marketSigners->CQP_SIGNERS_LIST[$indexMarketValues]->CQP_TITLE) {
                            $arrayMarketsFile[$marketAdded]["content"][9][1] = [
                                "vaue" => "{{Ttl_es_:signer" . $signer . ":title:align(center)}}",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LR",
                                "align" => "C"
                            ]; 
                        }

                        $arrayMarketsFile[$marketAdded]["content"][10][1] = [
                            "vaue" => "{{Dte_es_:signer" . $signer . ":date:format(dd/mm/yyyy):align(center)}}",
                            "width" => "0.5",
                            "bold" => false,
                            "border" => "LBR",
                            "align" => "C"
                        ]; 
                    } else {
                        $contentMarket = [];

                        $contentMarket[] = [
                            [
                                "addLine" => true
                            ]
                        ];
                        
                        $contentMarket[] = [
                            [
                                "vaue" => "REASEGURADOR/REINSURER:",
                                "width" => "0.5",
                                "bold" => true,
                                "border" => 1
                            ],
                            [
                                "vaue" => $marketSigners->CQP_REINSURER_NAME,
                                "width" => "0.5",
                                "bold" => true,
                                "border" => 1
                            ]
                        ];
                        
                        $contentMarket[] = [
                            [
                                "vaue" => "PARTICIPACION/PARTICIPATION:",
                                "width" => "0.5",
                                "bold" => true,
                                "border" => "LTR"
                            ],
                            [
                                "vaue" => "{{Signing" . $marketSigners->CQP_ADOBE_ALIAS . "_es_:signer" . $approver . $viewMode  . "}} Del/of 100.00% limites/limits",
                                "width" => "0.5",
                                "bold" => true,
                                "border" => "LTR"
                            ]
                        ];
                        
                        $contentMarket[] = [
                            [
                                "vaue" => "",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LBR"
                            ],
                            [
                                "vaue" => "Lineas Inalterados/Lines to Stand",
                                "width" => "0.5",
                                "bold" => true,
                                "border" => "LBR"
                            ]
                        ];
                        
                        if ($marketSigners->CQP_SIGNERS_LIST[$indexMarketValues]->CQP_STAMP) {
                            $contentMarket[] = [
                                [
                                    "vaue" => "{{Stamp1_es_:signer" . $signer . ":stampimage}}",
                                    "width" => "1",
                                    "bold" => false,
                                    "border" => "LTR",
                                    "align" => "C"
                                ]
                            ];
                            
                            $contentMarket[] = [
                                [
                                    "vaue" => "",
                                    "width" => "1",
                                    "bold" => false,
                                    "border" => "LR",
                                    "heigth" => 38
                                ]
                            ];
                        } else {
                            $contentMarket[] = [
                                [
                                    "vaue" => "",
                                    "width" => "1",
                                    "bold" => false,
                                    "border" => "LTR"
                                ]
                            ];
                            
                            $contentMarket[] = [
                                [
                                    "vaue" => "",
                                    "width" => "1",
                                    "bold" => false,
                                    "border" => "LTR"
                                ]
                            ];
                        }
                        
                        $contentMarket[] = [
                            [
                                "vaue" => $marketSigners->CQP_REINSURER_NAME,
                                "width" => "1",
                                "bold" => false,
                                "border" => "LBR",
                                "align" => "C"
                            ]
                        ];
                        
                        $contentMarket[] = [
                            [
                                "vaue" => "{{Sig_es_:signer" . $signer . ":signature}}",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LTR",
                                "font" => 18,
                                "heigth" => 12,
                                "align" => "R"
                            ],
                            [
                                "vaue" => "",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LTR",
                                "align" => "C"
                            ]
                        ];
                        
                        $contentMarket[] = [
                            [
                                "vaue" => "{{N_es_:signer" . $signer . ":fullname:align(center)}}",
                                "width" => "0.5",
                                "bold" => true,
                                "border" => "LR",
                                "align" => "C"
                            ],
                            [
                                "vaue" => "",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LR",
                                "align" => "C"
                            ]
                        ];

                        if ($marketSigners->CQP_SIGNERS_LIST[$indexMarketValues]->CQP_TITLE) {
                            $contentMarket[] = [
                                [
                                    "vaue" => "{{Ttl_es_:signer" . $signer . ":title:align(center)}}",
                                    "width" => "0.5",
                                    "bold" => false,
                                    "border" => "LR",
                                    "align" => "C"
                                ],
                                [
                                    "vaue" => "",
                                    "width" => "0.5",
                                    "bold" => false,
                                    "border" => "LR",
                                    "align" => "C"
                                ]
                            ];
                        } else {
                            $contentMarket[] = [
                                [
                                    "vaue" => "",
                                    "width" => "0.5",
                                    "bold" => false,
                                    "border" => "LR",
                                    "align" => "C"
                                ],
                                [
                                    "vaue" => "",
                                    "width" => "0.5",
                                    "bold" => false,
                                    "border" => "LR",
                                    "align" => "C"
                                ]
                            ];
                        }

                        $contentMarket[] = [
                            [
                                "vaue" => "{{Dte_es_:signer" . $signer . ":date:format(dd/mm/yyyy):align(center)}}",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LBR",
                                "align" => "C"
                            ],
                            [
                                "vaue" => "",
                                "width" => "0.5",
                                "bold" => false,
                                "border" => "LBR",
                                "align" => "C"
                            ]
                        ]; 

                        $arrayMarketsFile[] = [
                            "reinsurer" => $marketSigners->CQP_ADOBE_LABEL,
                            "content" => $contentMarket
                        ];
                    }
                }
            }
        }
        
        $signer++;
    }

    // Add Signning section in pdf content
    $AddPageAfter = 2;
    $countLines = 0;
    $arrayMarketsFile = array_column($arrayMarketsFile, "content");

    foreach ($arrayMarketsFile as $indexMarketFile => $arrayMarket) {
        foreach ($arrayMarket as $newContent) {
            $content[] = $newContent;
        }

        $count++;

        if ($count == $AddPageAfter && $indexMarketFile != (count($arrayMarketsFile) - 1)) {
            $AddPageAfter = 3;
            $count = 0;

            $content[] = [
                [
                    "addPage" => true
                ]
            ];
        }
    }
    
    foreach ($content as $rowContent) {
        addTableLine($rowContent);
    }
    
    $signPdf->SetCompression(true);
    $signPdf->Output('F', $signPageFile);

    // Merge original pdf with new sign page
    $finalFile = '/tmp/' . $tempFileName;

    $cmd = "gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite ".
        "-dCompatibilityLevel=1.4 " .
        "-sOutputFile=$finalFile $pathFile $signPageFile";

    shell_exec($cmd);

    return $finalFile;
}


/**
 * Add a row in pdf in table format
 *
 * @param array $cellsTable
 *
 * by Diego Tapia
 */
function addTableLine($cellsTable) {
    global $HEIGHT_PDF_CELL, $FONT_PDF_CELL, $MARGIN_PDF, $signPdf;

    // Set page space
    $x = $signPdf->GetX();
    $y = $signPdf->GetY();
    $usableWidth = $signPdf->GetPageWidth() - ($MARGIN_PDF * 2) - 2;
    $yValue = [0];

    // Add cells in table
    $count = count($cellsTable);
    $tempCellWidth = 0;

    foreach ($cellsTable as $indexCell => $cells) {
        if ($cells["addLine"]) {
            $signPdf->Ln($HEIGHT_PDF_CELL);
        } elseif($cells["addPage"]) {
            $signPdf->AddPage();
        } else {
            $cellWidth = $usableWidth * $cells["width"];
            $align = isset($cells["align"]) ? $cells["align"] : "L";
            $font = isset($cells["font"]) ? $cells["font"] : $FONT_PDF_CELL;
            $signPdf->SetFont('Arial', ($cells["bold"] ? "B" : "") , $font);

            if (isset($cells["heigth"])) {
                $signPdf->cell($cellWidth, $cells["heigth"], $cells["vaue"], $cells["border"], 1, $align);
            } else {
                $signPdf->MultiCell($cellWidth, $HEIGHT_PDF_CELL, $cells["vaue"], $cells["border"], $align);
            }

            $tempCellWidth += $cellWidth;

            if ($indexCell != $count -1) {
                $signPdf->SetXY($x + $tempCellWidth, $y); 
            }
        }

        $yValue[] = $signPdf->GetY();
    }

    // Adjust table page Y axis
    $signPdf->SetY(max($yValue));
}