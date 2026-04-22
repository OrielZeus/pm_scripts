<?php 
/*  
 *  Author : Ryan Albaladejo
 */

require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$auth_token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjZjZWQ3YWQ3OGNiNDNkYTlhMWQ3ODAzOGM3Njg4MDI5OWY2NWYyMGY3MDI0NTFlMmFlMTY4ODY3OTg0MGVmNDRjZjQ1NjgzZjAxNzM5MGYxIn0.eyJhdWQiOiI0IiwianRpIjoiNmNlZDdhZDc4Y2I0M2RhOWExZDc4MDM4Yzc2ODgwMjk5ZjY1ZjIwZjcwMjQ1MWUyYWUxNjg4Njc5ODQwZWY0NGNmNDU2ODNmMDE3MzkwZjEiLCJpYXQiOjE2MDQ5MjEwOTksIm5iZiI6MTYwNDkyMTA5OSwiZXhwIjoxNjM2NDU3MDk5LCJzdWIiOiI0NiIsInNjb3BlcyI6W119.bxe_9bVhYRGknkXiPR0ejBcOZuGGCCFsopIIDUeHp3Kd_MD6BM39pt6v9es6SYBAEMezUFsDin4fIKgcbQAb2S1VcD4H7ROqFbuTgy5KLzWOBHvJHW-Qq8oTlljEzfbKn-zuuPGU5VmQXi3f6JozLV4UgovXSTyLyaWxokRGocrQ2L1t3vPVszBu6_9GYQYny9sHDhKkHnnRluKi2s1BASB32ImhbtLuNTX0Jwsvr854g-xcphqAxP9QzeWasBka4wwQMKQZk0XvxN86nMsnL7ph8642Ujdl3KeowoHbBKeT-ETihbHCuHy9Gwt96UIXcXkNEV5sZzsHwcmG4l_S0Oe9YzRFl6wFKWmbP9fADC6Jn9RT4-4dGc4uUDuUT7pDUg1_zXHiPQZKOZwE5jjKSQmRCBY2qpSc8COqrTuOAmNdmj2LVDMMPjFH2Zua5i6OnK3SPG420UI0TERrPVq3MvGJO4Ga-9VBRr0DeQrCPyRXa1H4XIeHOgSzGFu6BEpu2sI5LiFQZ8EXInt9l5iZfP3vvy6p-h9SERT1BodxCbp2DjVerTJ0vXWCno-JCcfwHwc44dfdd4CSoR9jlVN51-bLMGC02FKpfziOmVBgqMNKoZ4CZ4lCs5FTwYIa5yNgCKv_rkGIA7iQ78hPHM9WO6z1wAteGRA1IP5vWKDOK4I"; 
$base_uri = "https://dlsu.dev.cloud.processmaker.net/"; 
$client = new \GuzzleHttp\Client(['base_uri'=>$base_uri, 'verify' => false]);

$date = date(DateTime::ISO8601);                                            // ISO8601 FORMAT - UTC

date_default_timezone_set('Asia/Manila');
$MNL_DATE = date('Y-m-d\TH:i:sO');                                          // ISO8601 FORMAT - MANILA DATETIME

$TRANSACTION_CODE = $data['transactionTypeCode'];           


$values['BUSINESS_UNIT'] = $data['businessUnit'];                           // Business Unit                --->    Default value is 'DLSU_BU'
$source['SOURCE'] = "External";                                             // Source                       --->    Default value is External 
$values['INVOICE_NUMBER'] = $data['number'];                                // RFP Number                   --->    RFP Request ID

if($TRANSACTION_CODE == "CA"){
    $values['INVOICE_AMOUNT'] = $data['ECA_amount'];   
} else {
    $values['INVOICE_AMOUNT'] = $data['INVOICE_AMOUNT'];                        // Invoice Amount               --->    INVOICE_AMOUNT 
}

$values['INVOICE_CURRENCY'] = $data['transactionCurrency'];                 // Invoice Currency             --->    transactionCurrency
//$values['AMOUNT'] = $data['INVOICE_AMOUNT'];                              // Amount                       --->     
$values['INVOICE_DATE'] = $data['requestDate'];                             // Invoice Date                 --->    Request Date in ISO8601 format from Form     
$values['SUPPLIER'] = $data['SUPPLIER'];                                    // Supplier                     --->    Payee Name
$values['SUPPLIER_ID'] = $data['SUPPLIER_ID'];                              // Supplier ID                  --->    Payee Type == 'Company'              
$values['SUPPLIER_SITE'] = $data['SUPPLIER_SITE'];                          // Supplier Site                --->    Default value is 'MAIN-PURCH'
$values['INVOICE_TYPE'] = $data['invoiceType'];                             // Invoice Type                 --->    Standard / Prepayment
$values['DESCRIPTION'] = $data['transactionType'];                          // Description                  --->    Transaction Type
$values['INVOICE_RECEIVED_DATE'] = null;                                    // Invoice Received Date        --->    Date Uploaded into Collection (ISO8601)     
$values['PAYMENT_TERMS'] = $data['paymentTerms'];                           // Payment Terms                --->    Default value is "7 days"
$values['TYPE'] = "ITEM";                                                   // Type                         --->    Default value is 'Item'      
$values['LINE'] = json_decode($data['LINE_ITEM']);                          // Line                         --->    LINE_ITEM
$values['LIABILITY_DISTRIBUTION'] = $data['LIABILITY_DISTRIBUTION'];

$CAMPUS_CHOICE = $data['campusChoice'];

if($CAMPUS_CHOICE == "Manila"){
    $values['CAMPUS_CHOICE'] = "11";
} else if($CAMPUS_CHOICE == "Manila"){
    $values['CAMPUS_CHOICE'] = "21";
} else if($CAMPUS_CHOICE == "Manila"){
    $values['CAMPUS_CHOICE'] = "31";
} else if($CAMPUS_CHOICE == "Manila"){
    $values['CAMPUS_CHOICE'] = "41";
}

$values['URL_ATTACHMENTS'] = "https://dlsu.dev.cloud.processmaker.net/requests/" . $data['requestID'] . "/files";

if($TRANSACTION_CODE == "CA" || $TRANSACTION_CODE == "PCO" || $TRANSACTION_CODE == "PCC"){
    $values['DISTRIBUTION_COMBINATION'] = $data['chargecode'];   
} else {
    $values['DISTRIBUTION_COMBINATION'] = null;
}

$EXPENSE_TYPE = $data['expenseTypeCode'];                                   // Additional Information       --->    APPROVER DETAILS
$PAYMENT_TYPE = $data['paymentTypeCode'];

$values['ADDITIONAL_INFORMATION'] = "https://dlsu.dev.cloud.processmaker.net/requests/" . $data['requestID'];
// if ($EXPENSE_TYPE == "CAD" || $EXPENSE_TYPE == "CAN" || $PAYMENT_TYPE == "GOVT" || $PAYMENT_TYPE == "COMMS"){
//     $values['ADDITIONAL_INFORMATION'] = "<Approved by: (1)". $data['AL1_name'] . "-" . $data['AL1_approveDate'] . "-" . $data['AL1_remarks'] . "><Link: https://dlsu.dev.cloud.processmaker.net/requests/" . $data['requestID'] . "/files>";
// } else if ($EXPENSE_TYPE == "FAD" || $PAYMENT_TYPE == "UT"){
//     $values['ADDITIONAL_INFORMATION'] = "<Approved by: (1)". $data['AL1_name'] . "-" . $data['AL1_approveDate'] . "-" . $data['AL1_remarks'] . "; (2)" . $data['AL2_name'] . "-" . $data['AL2_approveDate'] . "-" . $data['AL2_remarks'] . "><Link: https://dlsu.dev.cloud.processmaker.net/requests/" . $data['requestID'] . "/files>";
// } else {
//     $values['ADDITIONAL_INFORMATION'] = "<Approved by: (1)". $data['AL1_name'] . "-" . $data['AL1_approveDate'] . "-" . $data['AL1_remarks'] . "; (2)" . $data['AL2_name'] . "-" . $data['AL2_approveDate'] . "-" . $data['AL2_remarks'] . "; (3)" . $data['AL3_name'] . "-" . $data['AL3_approveDate'] . "-" . $data['AL3_remarks'] . "; (4)" . $data['AL4_name'] . "-" . $data['AL4_approveDate'] . "-" . $data['AL4_remarks'] . "><Link: https://dlsu.dev.cloud.processmaker.net/requests/" . $data['requestID'] . "/files>";
// }

$values['MNL_DATE'] = $MNL_DATE;                                            // MNL Date                     --->    Invoice Received Date in PH Time (ISO8601)
     
$payload = json_encode(['data'=>$values]);

$request = new Request(
 "POST",
 "/api/1.0/collections/53/records/",                                        // <-- RFP Collection ID
    [
 "Accept" => "application/json, text/plain, */*",
 "Authorization" => "Bearer $auth_token",
 "Content-Type" => "application/json; charset=utf-8"
    ],
 $payload
);

$response = $client->send($request);
 return $payload;