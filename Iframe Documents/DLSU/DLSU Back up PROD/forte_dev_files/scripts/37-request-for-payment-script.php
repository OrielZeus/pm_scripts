<?php 
/*  
 *  Author : Ryan Albaladejo
 */

require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$auth_token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxMCIsImp0aSI6IjFjOGRhMDI0YzFmNmMxMDg3NzU4MTFiNTljZGM2YjQ5ZDZiMzdjODI0ZDEzMjVlNzcwYWUwNjI5NDgyYmQ1Y2EwZjk3Y2ZiNmI0NzRlYmRhIiwiaWF0IjoxNjgwMTY0MzQ5LCJuYmYiOjE2ODAxNjQzNDksImV4cCI6MTcxMTc4Njc0OSwic3ViIjoiNTY2Iiwic2NvcGVzIjpbXX0.BYcoYUC4UjWNYT4Uhm56Z73QlUftgygjTVwvpb_7vCaaZsW-GBd4XrhDnPUVD3dCSgenFeTuGkL3jETkat0zRLp5X8eigNKZl_AUdQAeYRUwU3gUSw-Z0lb-eqsjBg_rmHv5191DKwZKbcNi58SDfTB3Dg7ImGt8ywKtYAjdAIQmlFBFmk32KQfhgCh5JoRfT3IG4U-Ojni478s7uJt4qE4cUFxfhhJP2HA0OeryQswkp1DwR8XR1OhY4WTPK343yMNnA-lZqWxUKs6cgMh7n2BxRFMmJ7iru51bZnljR6d2bXbhY73i2JMjHqDKwFYA-O4QygDSvbLGS9Haz8I8FX7OwuSUgsWlBx2U1i99w_4i3nA8iG6R5uTRaZRazUCvSZqn7Q399Bxd4azaqrvZ8ZvnF3ACXVr7SXwNdSyFKKixDMHWn-qjuY5HlMk1UbWRR-i4lnKEO0rXZRwEqj0ps93bxa-_we3LHKvzrU5aUmBeuFEaR9x--6xemqUotIOSYwAMDQE85PfOgFBQ3SDV_MHfAoUvMw5cA6PPMxHRBsnYvIub5sjajG3NuH2LvzkNUFxNkQW0vhmO8gt9uRSR0PpbtybozJSdMbZnDcptjyJVV8q3WPsvZpae9xpx7vq6So7jpUmMjAHGSKRE0pTxifzlEuQML-oP11ztiGy_49g"; 

$auth_token = getenv('API_TOKEN');
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
$values['RCV_TRANSACTION_ID'] = null;                                       // RCV TRANSACTION ID NEW FIELD FROM FUSION UPDATE 06-16-2023     
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

if($TRANSACTION_CODE == "CA" ||  $TRANSACTION_CODE == "UNL" || $TRANSACTION_CODE == "CONTRACTOR"){
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