<?php 

// API Token
$pmHost = getenv('API_HOST');
$pmToken = getenv('API_TOKEN');
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);

$email_error_message = "";
$subject_header = "";
$rfp_number = $data['number'];
$no_error = 1;
$string_input = strtoupper($data['request_name']);
$search_string = "RFP V2 - INTERNAL";
$ITEM_record_list = $data['ITEM_recordList'];
if($ITEM_record_list == null && $string_input != $search_string ){
	$no_error = 0;
	$subject_header = "RFP ERROR: ".$rfp_number;
	$email_error_message = "Please add transactions under the record list to prevent encountering the error upon posting to ORACLE.";
}

$campus_input = strtoupper($data['campusChoiceCode']);
$original_dept_code = strtoupper($data['requestor_department_code']);
$dept_code_input = strtoupper($data['deprt_code_selected']);
if($dept_code_input != null && $dept_code_input != ""){
	$dept_input = $dept_code_input;
}
else{
	$dept_input = $original_dept_code;
}
$amount_requested = floatval(strtoupper($data['amountRequested']));
$transaction_currency = strtoupper($data['transactionCurrency']);
$supplier = strtoupper($data['payee']['NAME']);
$internal_transaction_type = $data['transactionTypeCode'];
$transaction_type = $data['transactionType'];

$is_internal = false;
$is_lider_project = false;
$lider_department_code = "3-14-45-265";
$office_of_the_vice_president_finance = "4-02-06-021";

if($string_input == $search_string && $dept_input == $office_of_the_vice_president_finance){ // finance
    $is_internal = true;
}
$dept_from_form = $data['department_selected'];
if($dept_input == $lider_department_code){
	$is_lider_project = true;
}
# ----------------------------------------- Initialization ------------------------------------------------------ #
$approval_matrix_collection = 40;


// sample input value based on collection comment out to get screen input
// $campus_input = "MC";
// $dept_input = "4-02-06-021";
// $amount_requested = 3239020;
// $transaction_currency = "PHP";
// $supplier = "ALLAN BRAVO BORRA";
// $internal_transaction_type = "MMP";
// end here (need to adjust for the requested amount that has decimal and in boundaries ex 500000.75)

// $code = $data['conditions'];

/*
userDept = ""; // code
campusChoiceCode = ""; // MC, LC, BGC, MKC
approver_level = ""; // 1, 2, 3, 4
RUL_USER = ""; // user_id in PM4

*/

if($transaction_currency == "USD"){
	$current_date =  date("Y-m-d");
	$url_link = "https://api.apilayer.com/exchangerates_data/convert?to=PHP&from=USD&amount=".$amount_requested."&date=".$current_date;
	$api_header = array(
	"apikey: hOWKoQLwBSZAulgxnSyIoHdauM8zLets"
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $api_header);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url_link);
	$result = curl_exec($ch);

	curl_close($ch);
	$transaction_details = json_decode($result,true);

	$amount_requested = $transaction_details['result'];
	$converted_amount = $amount_requested;
}

if($is_internal == true){
    // if($transaction_currency == "USD"){
    //     $url = $pmHost.'/collections/'.$approval_matrix_collection.'/records?include=data&pmql=((UPPER(data.userDept)="'.$dept_input.'") and (UPPER(data.campusChoiceCode) like "%'.$campus_input.'%") and (data.min_usd_amount <='.$amount_requested.' ) and data.transactionTypeCode = "'.$internal_transaction_type.'")';
    // }
    // else{
        $url = $pmHost.'/collections/'.$approval_matrix_collection.'/records?include=data&pmql=((UPPER(data.userDept)="'.$dept_input.'") and (UPPER(data.campusChoiceCode) like "%'.$campus_input.'%") and (cast(data.min_amount as number) <='.$amount_requested.' ) and data.transactionTypeCode = "'.$internal_transaction_type.'")';
    // }
}
else if($is_lider_project == true){
    $url = $pmHost.'/collections/'.$approval_matrix_collection.'/records?include=data&pmql=((UPPER(data.userDept)="'.$dept_input.'") and (UPPER(data.campusChoiceCode) like "%'.$campus_input.'%") and (cast(data.min_amount as number) <='.$amount_requested.' ) and data.userDeptName = "'.$dept_from_form.'")';
}
else{
    // if($transaction_currency == "USD"){
    //     $url = $pmHost.'/collections/'.$approval_matrix_collection.'/records?include=data&pmql=((UPPER(data.userDept)="'.$dept_input.'") and (UPPER(data.campusChoiceCode) like "%'.$campus_input.'%") and (data.min_usd_amount <='.$amount_requested.' ) )';
    // }
    // else{
        $url = $pmHost.'/collections/'.$approval_matrix_collection.'/records?include=data&pmql=((UPPER(data.userDept)="'.$dept_input.'") and (UPPER(data.campusChoiceCode) like "%'.$campus_input.'%") and (cast(data.min_amount as number) <='.$amount_requested.') )';
    // }
}


$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);
$Test = json_decode($resp);
$Test_count = 0;
if($Test != null){
	$Test_count = count($Test -> data);
}

$report_data = [];
$values['supplier'] = $supplier;
// not used

$approver_name = strtoupper($Test -> data[0] -> data -> approver_name) ;
$until_level_number = 0;

$minus_1 = 0;
if($Test_count >= 0){	
	for($x = 0; $x < $Test_count ; $x++){
		$approver_level = $Test -> data[$x] -> data -> approver_level;
		$manager_id = $Test -> data[$x] -> data -> RUL_USER;
		$min_amount = $Test -> data[$x] -> data -> min_amount;
		$approver_name = strtoupper($Test -> data[$x] -> data -> approver_name);

		$variable_id = 'L'.($x+1).'managerId';
		$variable_name = 'L'.($x+1).'managerName';
		$variable_email = 'L'.($x+1).'managerEmail';
		
		if($approver_level == $x+1 && floatval($min_amount) <= $amount_requested){
			${$variable_id} = $manager_id;
			
			$url = $pmHost . '/users/' . ${$variable_id};
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $pmHeaders);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$resp = curl_exec($curl);
			curl_close($curl);
			$getUserResponse = json_decode($resp); 
			${$variable_email} = $getUserResponse->email;
			// we can use the one indicated in the hierarchy level
			${$variable_name} = $getUserResponse->fullname;
			$until_level_number = $x+1;

			$values[$variable_id]= ${$variable_id};
			$values[$variable_email]=  ${$variable_email};
			$values[$variable_name]=  ${$variable_name};
			
			$minus_1 += 1;
		}
	}
}

$values['url_link'] = $url;
$values['until_level_number']=  $until_level_number;
$values['level_number']=  1;
$values['approver_id'] = $L1managerId;
$values['approver_name'] = $L1managerName;
$values['approver_email'] = $L1managerEmail;
$values['requestor_department_code'] = $dept_input;
$values['converted_amount'] = $converted_amount;
$values['no_error'] = $no_error;
$values['transactionType'] = $transaction_type;
$values['email_error_message'] = $email_error_message;
$values['subject_header'] = $subject_header;
$report_data = $values;

if($L1managerId == ""){
	$no_error = 0;
	$subject_header = "RFP ERROR: ".$rfp_number;
	$email_error_message = "No approver was found for the selected campus and department. Please contact support for the issue";
}

/*
	"supplier" => $supplier,
	"url" => $url,
	"Test_count" => $Test_count,
    "until_level_number" => $until_level_number,
    'L1_MANAGER_ID' => $L1managerId,
    'L1_MANAGER_NAME' => $L1managerName,
    'L1_MANAGER_EMAIL' => $L1managerEmail,
    'L2_MANAGER_ID' => $L2managerId,
    'L2_MANAGER_NAME' => $L2managerName,
    'L2_MANAGER_EMAIL' => $L2managerEmail,
    'L3_MANAGER_ID' => $L3managerId,
    'L3_MANAGER_NAME' => $L3managerName,
    'L3_MANAGER_EMAIL' => $L3managerEmail,
    'L4_MANAGER_ID' => $L4managerId,
    'L4_MANAGER_NAME' => $L4managerName,
    'L4_MANAGER_EMAIL' => $L4managerEmail
*/
return $report_data;