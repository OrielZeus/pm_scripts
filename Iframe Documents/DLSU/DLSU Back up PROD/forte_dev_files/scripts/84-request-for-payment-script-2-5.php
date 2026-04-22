<?php 
/*  
 *  Author : Ryan Albaladejo
 */

require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$auth_token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxMCIsImp0aSI6IjNhNmNhYjlmMzYxODNjMWFjZWYwYTIwMTUwZDg3NGMxYzIzY2E5Zjc4YmExNmZiYjBlMGRkYzRkZGE4OTBiNzliYWYyZmYyYjc0ODFmMmI3IiwiaWF0IjoxNjQ4NzE0MTkyLCJuYmYiOjE2NDg3MTQxOTIsImV4cCI6MTY4MDI1MDE5Miwic3ViIjoiMSIsInNjb3BlcyI6W119.Nwz6vRLKJx5xXNc0y0IgHlnWwQk0lGZ_NL6NUkdLErS0ldN12Xd35EN23gIvo43yQuWRSRte2FlAsfdC3ctnWNweii-2vSyGERGtxWjg3tQS_38-R7pKIgYgtypuO5x_vMkn1BhfZXwf0upV4G7Nik8AycAvwnS9K2yiuUhyLSk4S4e5UHipT_EhXVj_5mJGojUEPdgKsPXqtMXjX1hRhY2p6wDT3gd06GjojcgDq5CR62GP6aj1sYSe0QmWRfrfV7M3Ip0fkic02Tvd1F8Evdtk7yxo4C7C75CBkD9-ttHOEfOJP_ep2k6Qih5OJ2rc_Yo4Wfe42w8GicjAs8gZmdfDAP27Si405x2ItlM8UNhZ_ZoY-lIOFfyATZa_qBJaHi00iDM5BACdYy0s4v40PQFZLg_lkYvDy3Lawhy0UwjvcPu5lsnSdlIqJ14taDxwB13-uhiqjjvrkIj6075dxtKasXnOP4EHwdc_bFH67yMebxXWdnp2SXhx9blrn4VAU5UEKQ8BOXLrdpHXzEprsxVKoxa7gPe-YVlGgzpi0O53cqP4YKjmQ0Ka67oQrlnCz743Qb-xqoGA6gvmRnimG87URjCzJVWN5R9_7Samk019YvpygtAwTM80d7lMYO6c-PU8iqwi2Y19fIrFqm89fKGjO8SiytslUmyLnZUR4D8"; 
$base_uri = "https://dlsu.cloud.processmaker.net/"; 
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
} else if($TRANSACTION_CODE == "CONTRACTOR"){
    $values['INVOICE_AMOUNT'] = $data['CONTRACTOR_amount'];   
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
$values['DESCRIPTION'] = $data['description'];                              // Description                  --->    Transaction Type changed to Description
$values['INVOICE_RECEIVED_DATE'] = null;                                    // Invoice Received Date        --->    Date Uploaded into Collection (ISO8601)     
$values['PAYMENT_TERMS'] = $data['paymentTerms'];                           // Payment Terms                --->    Default value is "7 days"
$values['TYPE'] = "ITEM";                                                   // Type                         --->    Default value is 'Item'      
$values['LINE'] = json_decode($data['LINE_ITEM']);                          // Line                         --->    LINE_ITEM
$values['LIABILITY_DISTRIBUTION'] = $data['LIABILITY_DISTRIBUTION'];

$CAMPUS_CHOICE = $data['campusChoice'];

if($CAMPUS_CHOICE == "Manila"){
    $values['CAMPUS_CHOICE'] = "11";
} else if($CAMPUS_CHOICE == "Laguna"){
    $values['CAMPUS_CHOICE'] = "21";
} else if($CAMPUS_CHOICE == "BGC"){
    $values['CAMPUS_CHOICE'] = "31";
} else if($CAMPUS_CHOICE == "Makati"){
    $values['CAMPUS_CHOICE'] = "12";
}

$values['URL_ATTACHMENTS'] = "https://dlsu.cloud.processmaker.net/requests/" . $data['requestID'] . "/files";

if($TRANSACTION_CODE == "CA" || $TRANSACTION_CODE == "PCO" || $TRANSACTION_CODE == "PCC" || $TRANSACTION_CODE == "CONTRACTOR"){
    $values['DISTRIBUTION_COMBINATION'] = $data['chargecode'];   
} else {
    $values['DISTRIBUTION_COMBINATION'] = null;
}

$EXPENSE_TYPE = $data['expenseTypeCode'];                                   // Additional Information       --->    APPROVER DETAILS
$PAYMENT_TYPE = $data['paymentTypeCode'];

$values['ADDITIONAL_INFORMATION'] = "https://dlsu.cloud.processmaker.net/requests/" . $data['requestID'];
// if ($EXPENSE_TYPE == "CAD" || $EXPENSE_TYPE == "CAN" || $PAYMENT_TYPE == "GOVT" || $PAYMENT_TYPE == "COMMS"){
//     $values['ADDITIONAL_INFORMATION'] = "<Approved by: (1)". $data['AL1_name'] . "-" . $data['AL1_approveDate'] . "-" . $data['AL1_remarks'] . "><Link: https://dlsu-dev.processmaker.net/requests/" . $data['requestID'] . "/files>";
// } else if ($EXPENSE_TYPE == "FAD" || $PAYMENT_TYPE == "UT"){
//     $values['ADDITIONAL_INFORMATION'] = "<Approved by: (1)". $data['AL1_name'] . "-" . $data['AL1_approveDate'] . "-" . $data['AL1_remarks'] . "; (2)" . $data['AL2_name'] . "-" . $data['AL2_approveDate'] . "-" . $data['AL2_remarks'] . "><Link: https://dlsu-dev.processmaker.net/requests/" . $data['requestID'] . "/files>";
// } else {
//     $values['ADDITIONAL_INFORMATION'] = "<Approved by: (1)". $data['AL1_name'] . "-" . $data['AL1_approveDate'] . "-" . $data['AL1_remarks'] . "; (2)" . $data['AL2_name'] . "-" . $data['AL2_approveDate'] . "-" . $data['AL2_remarks'] . "; (3)" . $data['AL3_name'] . "-" . $data['AL3_approveDate'] . "-" . $data['AL3_remarks'] . "; (4)" . $data['AL4_name'] . "-" . $data['AL4_approveDate'] . "-" . $data['AL4_remarks'] . "><Link: https://dlsu-dev.processmaker.net/requests/" . $data['requestID'] . "/files>";
// }

$values['MNL_DATE'] = $MNL_DATE;                                            // MNL Date                     --->    Invoice Received Date in PH Time (ISO8601)
     
$payload = json_encode(['data'=>$values]);

$request = new Request(
 "POST",
 "/api/1.0/collections/9/records/",                                        // <-- RFP Collection ID
    [
 "Accept" => "application/json, text/plain, */*",
 "Authorization" => "Bearer $auth_token",
 "Content-Type" => "application/json; charset=utf-8"
    ],
 $payload
);

$response = $client->send($request);
 return $payload;