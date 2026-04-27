<?php
$currentLevel = (int)($data['level_number'] ?? 0);
$nextLevel = $currentLevel + 1;

$approverArray = $data['approver_array'] ?? [];
if (!is_array($approverArray)) {
    $approverArray = [];
}

$approvalInputRaw = (int)($data['approval_input'] ?? 0);
$approvalLabel = 'PENDING';
if ($approvalInputRaw === 1) {
    $approvalLabel = 'APPROVED';
} elseif ($approvalInputRaw === 2) {
    $approvalLabel = 'REQUEST FOR MORE INFORMATION';
} elseif ($approvalInputRaw === 3) {
    $approvalLabel = 'REJECTED';
}

$approverArray[] = [
    'approver_level' => $currentLevel,
    'approval_date' => $data['approval_date'] ?? null,
    'approver_name' => $data['approver_name'] ?? null,
    'approver_remarks' => $data['remarks_input'] ?? null,
    'approval_input' => $approvalLabel,
];

$nextIdKey = 'L' . $nextLevel . 'managerId';
$nextNameKey = 'L' . $nextLevel . 'managerName';
$nextEmailKey = 'L' . $nextLevel . 'managerEmail';

$nextApproverId = $data[$nextIdKey] ?? null;
$nextApproverName = $data[$nextNameKey] ?? null;
$nextApproverEmail = $data[$nextEmailKey] ?? null;

$values = [];
$values['approver_array'] = $approverArray;
$values['approver_id'] = $nextApproverId;
$values['approver_name'] = $nextApproverName;
$values['approver_email'] = $nextApproverEmail;
$values['level_number'] = $nextLevel;
$values['approval_input'] = '';
$values['remarks_input'] = '';
$values['has_next_approver'] = !empty($nextApproverId);

if ($approvalInputRaw === 2) {
    $values['subject_header'] = 'Request For More Information for RFP - ' . ($data['number'] ?? '');
    $values['email_error_message'] = 'The approver requested more information. Please review remarks and update the request.';
} else {
    $values['subject_header'] = '';
    $values['email_error_message'] = '';
}

return $values;
