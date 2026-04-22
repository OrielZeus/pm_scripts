<?php 

/*  
 *  Author : Ryan Albaladejo, Keanu Dominado, Elizabeth Tan
 */


require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


### ------------------------------------------- API Token ------------------------------------------------ ###

$pmHost = "https://dlsu.cloud.processmaker.net/"; 
$pmHost = getenv('API_HOST');
$host_link = getenv('HTTP_HOST'); // this one not working
$pmToken = getenv('API_TOKEN');
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);
$client = new \GuzzleHttp\Client(['base_uri'=>$pmHost, 'verify' => false]);
$collection_record = "/api/1.0/collections/9/records/";

$host_link = str_replace("api/1.0", "", $pmHost);


### ------------------------------------- Input Values Received ------------------------------------------- ###
$date = date(DateTime::ISO8601);                                            // ISO8601 FORMAT - UTC
### --------------------------- DEFAULT ---------------------------- ###           
$business_unit = "DLSU_BU";
date_default_timezone_set('Asia/Manila');
date_default_timezone_set("Asia/Hong_Kong");
$MNL_DATE = date('Y-m-d\TH:i:sO');                                          // ISO8601 FORMAT - MANILA DATETIME
$supplier_site = null;                                                      // supplier site not tracked in BPMS
$invoice_date_received = null;
$payment_terms = "7 days"; 
$type = "ITEM";
// Request ID (for the new autofill)
$requestId = $data['_request']['id'];

// checking for prepayment transactions other than cash advance - unliquidated cash advance
$string_input = strtoupper($data['request_name']);
$search_string = "Request for Payment - PREPAYMENT";

### --------------------------- FORM INPUTS ---------------------------- ###           
$TRANSACTION_CODE = $data['transactionTypeCode'];
$invoice_date = $data['requestDate'];
$invoice_amount = $data['INVOICE_AMOUNT'];  
$invoice_number = $data['number'];
$currency = $data['transactionCurrency'];
$supplier = $data['SUPPLIER'];
if(strpos($supplier, "&#39;" )){
    $supplier = str_replace("&#39;","'", $supplier);
}
$supplier_id = $data['SUPPLIER_ID'];
$invoice_type = $data['invoiceType'];
$description = str_replace("  ", " ", (str_replace("\t", ' ', (str_replace("\n", ' ', $data['description'])))));       // Description                  --->    Transaction Type changed to Description
$description = str_replace("\"", "-", $description);
$liability_distribution = $data['LIABILITY_DISTRIBUTION'];
$campus_choice = $data['chargecode'];
$new_actvity_date = null;
if(isset($data['activityDate'])){
  // $new_actvity_date = date('Y-m-d\TH:i:sO', strtotime($data['activityDate']));          
  $new_actvity_date = $data['activityDate'].substr($invoice_date, 10);
}
$url_attachments = $host_link."requests/" . $requestId . "/files";
$additional_information = $host_link."requests/" . $requestId;
$payload_record_list = $data["ITEM_recordList"];
if($payload_record_list == null){
  $payload_record_list = [];
}
$line_item = [];
// cash advance
// $item_array =
//  "SUMMARY": {
//             "AMOUNT": 8500,
//             "REMARKS": "Cash advance payment for PAPSAS INTERACTIVE YOUTH FORUM 2023",
//             "CURRENCY": "PHP",
//             "ACTIVITY_DATE": "2023-03-09",
//             "LIQUIDATION_DATE": "2023-03-15"
//         }
$item_details = [];

// Line Item for (Parehas)
// $item_summary = array("REMARKS" => "remarks", "TOTAL_AMOUNT" => 10000);
// $record = array("ITEMS" => $item_details, "SUMMARY" => $item_summary);

// // Line Item for CA (Cash Advance)
// $item_summary = array("REMARKS" => "remarks", "AMOUNT" => 10000, "CURRENCY" => 10000, "ACTIVITY_DATE" => 10000, "LIQUIDATION_DATE" => 10000);
// $record = array("SUMMARY" => $item_summary);

// // Line Item for PRP (Prepayment)
// $item_detail = array("DATE" => "2023-04-03", "ITEM"=> "0", "AMOUNT"=> "25000", "INVOICE"=> "N/A", "EXPENSES"=> "EXPENSES", "DISTRIBUTION_COMBINATION" => "distribution_conbi");
// array_push($item_details, $item_detail);

$array_length = 0;
// $record = strpos($invoice_number, "LI");

if(strpos($invoice_number, "LI") != false){
// if(str_contains($invoice_number, "LI") != false){
  // liquidation
  $array_length = count($payload_record_list);
  for($x = 0; $x < $array_length; $x++){
    $row_number = $x+1;
    $record_list_date = $payload_record_list[$x]["date"];
    $expense_name = $payload_record_list[$x]["expenses"];
    $receipt_no = $payload_record_list[$x]["invoice"];
    $item_amount = round($payload_record_list[$x]["amount"], 2);
    $distirbution_combination = $payload_record_list[$x]["glAccount"];
    $item_detail = array("DATE" => $record_list_date, "ITEM"=> $row_number, "AMOUNT"=> $item_amount, "INVOICE"=> $receipt_no, "EXPENSES"=> $expense_name, "DISTRIBUTION_COMBINATION" => $distirbution_combination);
    array_push($item_details, $item_detail);

  }
  $date_of_liquidation = $data['LCA_liquidationDate'];
  $cash_advance_rfp_no = $data['LCA_cashAdvanceRequestNo'];
  $liquidation_amount = $data['INVOICE_AMOUNT'];
  $reimbursement_amount = $data['LCA_amountForReimbursement'];
  $item_summary = array("DATE_OF_LIQUIDATION" => $date_of_liquidation, "CASH_ADVANCE_REQUEST_NO" => $cash_advance_rfp_no,  "TOTAL_AMOUNT_LIQUIDATED" => $liquidation_amount,  "AMOUNT_FOR_REIMBURSEMENT" => $reimbursement_amount);
  $record = array("ITEMS" => $item_details, "SUMMARY" => $item_summary);
}
else if (strpos($invoice_number, "PCR") != false){
// else if (str_contains($invoice_number, "PCR") != false){
  // petty cash replenishment
  $array_length = count($payload_record_list);
  for($x = 0; $x < $array_length; $x++){
    $row_number = $x+1;
    $record_list_date = $payload_record_list[$x]["date"];
    $expense_name = $payload_record_list[$x]["expenses"];
    $receipt_no = $payload_record_list[$x]["invoice"];
    $item_amount = round($payload_record_list[$x]["amount"], 2);
    $distirbution_combination = $payload_record_list[$x]["glAccount"];
    $item_detail = array("DATE" => $record_list_date, "ITEM"=> $row_number, "AMOUNT"=> $item_amount, "INVOICE"=> $receipt_no, "EXPENSES"=> $expense_name, "DISTRIBUTION_COMBINATION" => $distirbution_combination);
    array_push($item_details, $item_detail);

  }
  $petty_cash_on_hand = $data['INVOICE_AMOUNT'];
  $petty_cash_fund = $data['PCR_fund'];
  $cash_overage = $data['PCR_overage'];
  $item_summary = array("CASH_OVERAGE" => $cash_overage, "PETTY_CASH_FUND" => $petty_cash_fund, "PETTY_CASH_ON_HAND" => $petty_cash_on_hand);
  $record = array("ITEMS" => $item_details, "SUMMARY" => $item_summary);
}
else if (strpos($invoice_number, "CA") != false || strpos($invoice_number, "CRCA") != false || strpos($invoice_number, "CTRA") != false){
// else if (str_contains($invoice_number, "CA") != false){
  // prepayment = cash advance
  $activity_date = $data['ECA_activityDate'];
  $currency = $data['transactionCurrency'];
  $liquidation_date = $data['ECA_liquidationDate'];
  $item_remarks = $data['ITEM_remarks'];
  $item_amount = $data['ECA_amount'];
  $invoice_amount = $item_amount;
  $item_summary = array("AMOUNT" => $item_amount, "REMARKS" => $item_remarks, "CURRENCY" => $currency, "ACTIVITY_DATE" => $activity_date, "LIQUIDATION_DATE" => $liquidation_date);
  $record = array("SUMMARY" => $item_summary);
}
// else if (strpos($request_name, "PREPAYMNENT") != false){
else if (strpos($invoice_number, "UNL") != false){
  // for prepayment = unliquidated cash advance
  $activity_date = $data['ECA_activityDate'];
  $currency = $data['transactionCurrency'];
  $item_remarks = $data['ITEM_remarks'];
  $item_amount = $data['ECA_amount'];
  $invoice_amount = $item_amount;
  $item_summary = array("AMOUNT" => $item_amount, "REMARKS" => $item_remarks, "CURRENCY" => $currency, "ACTIVITY_DATE" => $activity_date);
  $record = array("SUMMARY" => $item_summary);
}
else{
  // OTHERS MES HON BANK DON CON RE RRI STI AED(Advances to Employees)
  // $line_item = $record["ITEMS"][0]["DATE"]; // call individual variables
  $array_length = count($payload_record_list);
  for($x = 0; $x < $array_length; $x++){
    $row_number = $x+1;
    $record_list_date = $payload_record_list[$x]["date"];
    $expense_name = $payload_record_list[$x]["expenses"];
    $receipt_no = $payload_record_list[$x]["invoice"];
    $item_amount = round($payload_record_list[$x]["amount"],2);
    $distirbution_combination = $payload_record_list[$x]["glAccount"];
    $item_detail = array("DATE" => $record_list_date, "ITEM"=> $row_number, "AMOUNT"=> $item_amount, "INVOICE"=> $receipt_no, "EXPENSES"=> $expense_name, "DISTRIBUTION_COMBINATION" => $distirbution_combination);
    array_push($item_details, $item_detail);

  }
  $item_summary = array("REMARKS" => $description, "TOTAL_AMOUNT" => $invoice_amount);
  $record = array("ITEMS" => $item_details, "SUMMARY" => $item_summary);
}

// update invoice_date $MNL_DATE = date('Y-m-d\TH:i:sO');
if(strtotime($MNL_DATE) > strtotime($invoice_date) && date("n", strtotime($MNL_DATE)) != date("n", strtotime($invoice_date))){
    $description = $description." ORIG_DATE: ".$invoice_date;
    $invoice_date = $MNL_DATE;
}

// value that is being passed
$line_item = $record;

### ---------------------------------------------------------- PAYLOAD  ------------------------------------------------------------ ###
$values['MNL_DATE'] = $MNL_DATE;                                            // MNL Date                     --->    Invoice Received Date in PH Time (ISO8601)
$values['BUSINESS_UNIT'] = $business_unit;                                  // Business Unit                --->    Default value is 'DLSU_BU'
$values['INVOICE_NUMBER'] = $invoice_number;                                // RFP Number                   --->    RFP Request ID
$values['INVOICE_AMOUNT'] = $invoice_amount;                                // Invoice Amount               --->    INVOICE_AMOUNT 
$values['INVOICE_CURRENCY'] = $currency;                                    // Invoice Currency             --->    transactionCurrency
$values['INVOICE_DATE'] = $invoice_date;                                    // Invoice Date                 --->    Request Date in ISO8601 format from Form     
$values['SUPPLIER'] = $supplier;                                            // Supplier                     --->    Payee Name
$values['SUPPLIER_ID'] = $supplier_id;                                      // Supplier ID                  --->    Payee Type == 'Company'              
$values['SUPPLIER_SITE'] = $supplier_site;                                  // Supplier Site                --->    Default value is 'MAIN-PURCH'
$values['INVOICE_TYPE'] = $invoice_type;                                    // Invoice Type                 --->    Standard / Prepayment
$values['DESCRIPTION'] =  $description;                                     // Description                  --->    Transaction Type
$values['INVOICE_RECEIVED_DATE'] = $invoice_date_received;                  // Invoice Received Date        --->    Date Uploaded into Collection (ISO8601)     
$values['PAYMENT_TERMS'] =  $payment_terms;                                 // Payment Terms                --->    Default value is "7 days"
$values['TYPE'] = $type;                                                    // Type                         --->    Default value is 'Item'      
$values['LINE'] = $line_item;                                               // Line                         --->    LINE_ITEM
$values['LIABILITY_DISTRIBUTION'] = $liability_distribution;
$values['CAMPUS_CHOICE'] = $campus_choice;
$values['URL_ATTACHMENTS'] = $url_attachments;
$values['ADDITIONAL_INFORMATION'] = $additional_information;
$values['ACTIVITY_DATE'] = $new_actvity_date;
   
if($TRANSACTION_CODE == "CA" || $TRANSACTION_CODE == "PCO" || $TRANSACTION_CODE == "PCC" || $TRANSACTION_CODE == "CTRA" || $TRANSACTION_CODE == "CRCA"){
    $values['DISTRIBUTION_COMBINATION'] = $data['distribution_combination'];   
} else {
    $values['DISTRIBUTION_COMBINATION'] = null;
}

$payload = json_encode(['data'=>$values]);

$request = new Request(
 "POST",
 $collection_record,                                        // <-- RFP Collection ID
    [
 "Accept" => "application/json, text/plain, */*",
 "Authorization" => "Bearer $pmToken",
 "Content-Type" => "application/json; charset=utf-8"
    ],
 $payload
);

$response = $client->send($request);

return json_decode($payload);