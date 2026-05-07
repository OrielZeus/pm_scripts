<?php
$levelNumber = ((int)($data['level_number'] ?? 0)) + 1;
$approverArray = $data['approver_array'] ?? [];
if ($approverArray == null || !is_array($approverArray)) {
    $approverArray = [];
}

$emailErrorMessage = '';
$subjectHeader = '';
$approverLevel = $levelNumber - 1;
$approvalInput = $data['approval_input'] ?? '';
$rfpNumber = (string)($data['number'] ?? '');

if ($approvalInput == 1) {
    $approvalInput = 'APPROVED';
}
if ($approvalInput == 2) {
    $approvalInput = 'REQUEST FOR MORE INFORMATION';
    $emailErrorMessage = 'The approver has requested for more information for the submitted RFP, please refer to the remarks of the approver.';
    $subjectHeader = 'Request For More Information for RFP - ' . $rfpNumber;
}
if ($approvalInput == 3) {
    $approvalInput = 'REJECTED';
}

$approverArray[] = [
    'approver_level' => $approverLevel,
    'approval_date' => (string)($data['approval_date'] ?? ''),
    'approver_name' => (string)($data['approver_name'] ?? ''),
    'approver_remarks' => (string)($data['remarks_input'] ?? ''),
    'approval_input' => $approvalInput,
];

$idKey = 'L' . $levelNumber . 'managerId';
$nameKey = 'L' . $levelNumber . 'managerName';
$emailKey = 'L' . $levelNumber . 'managerEmail';

return [
    'approver_array' => $approverArray,
    'approver_id' => (string)($data[$idKey] ?? ''),
    'approver_name' => (string)($data[$nameKey] ?? ''),
    'approver_email' => (string)($data[$emailKey] ?? ''),
    'level_number' => $levelNumber,
    'approval_input' => '',
    'remarks_input' => '',
    'email_error_message' => $emailErrorMessage,
    'subject_header' => $subjectHeader,
];
