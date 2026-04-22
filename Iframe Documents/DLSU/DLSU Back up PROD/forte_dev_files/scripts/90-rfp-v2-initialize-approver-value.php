<?php 
$level_number = $data['level_number']+1;
// create variable for the previous approver and remarks
$approver_array = $data['approver_array'];
if($approver_array == null){
    $approver_array = [];
}

$email_error_message = "";
$subject_header = "";
$approver_level = $level_number-1;
$approver_id = $data['approver_id'];
$approver_name = $data['approver_name'];
$approver_email = $data['approver_email'];
$approval_date = $data['approval_date'];
$approver_remarks = $data['remarks_input'];
$approval_input = $data['approval_input'];
$rfp_number = $data['number'];

if($approval_input == 1){
    $approval_input = "APPROVED";
}
if($approval_input == 2){
    $approval_input = "REQUEST FOR MORE INFORMATION";
    $email_error_message = "The approver has requested for more information for the submitted RFP, please refer to the remarks of the approver.";
    $subject_header = "Request For More Information for RFP - ".$rfp_number;
}
if($approval_input == 3){
    $approval_input = "REJECTED";
}

$record_array = array("approver_level"=> $approver_level, "approval_date"=> $approval_date,"approver_name" => $approver_name, "approver_remarks" => $approver_remarks, "approval_input" => $approval_input);
array_push($approver_array, $record_array);


$variable_id = 'L'.($level_number).'managerId';
$variable_name = 'L'.($level_number).'managerName';
$variable_email = 'L'.($level_number).'managerEmail';

$approver_id = $data[$variable_id];
$approver_name = $data[$variable_name];
$approver_email = $data[$variable_email];

$L1managerId = "missing";
$variable_id = "L1managerId";

$values['approver_array'] = $approver_array;
$values['approver_id'] = $approver_id;
$values['approver_name'] = $approver_name;
$values['approver_email'] = $approver_email;
$values['level_number'] = $level_number;
$values['approval_input'] = "";
$values['remarks_input'] = "";
$values['email_error_message'] = $email_error_message;
$values['subject_header'] = $subject_header;


$report_data = $values;

return $report_data;