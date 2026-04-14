<?php 
/*  
 *  Get Service Error and display error messages on the screen if an error occurs for Handling error process
 *  By Natalia Mendez
 */
;
$processName  = $data["processName"]  ?? "ProcessMaker Process";
$conectorName = $data["conectorName"] ?? "Web Service";
$resErrors    = $data["resErrorHandling"] ?? [];
$messageError = $data["messageError"] ?? null;

$count = isset($data['countError']) ? ((int)$data['countError'] + 1) : 1;

// Recovers previous errors
$messages = $data['resError'] ?? [];
$seen = array_unique($messages);

// Error
if (!empty($messageError) && !in_array($messageError, $seen, true)) {
    $seen[] = $messageError;
    $messages[] = $messageError;
}

// Request Errors
if (is_array($resErrors)) {
    foreach ($resErrors as $error) {
        if (!empty($error['message']) && !in_array($error['message'], $seen, true)) {
            $seen[] = $error['message'];
            $messages[] = $error['message'];
        }
    }
}

$lastError = !empty($messages)
    ? end($messages)
    : ($messageError ?: 'Unknown error');

if ($count === 3) { //Message if there were 3 request errors or more
    $subject = "{$processName} - Failed Request";
    $body =
        "{$conectorName} failed 3 times.\n\n" .
        "Errors:\n" .
        implode("\n", $messages);
} else { // Email for request error
    $subject = "{$processName} - Web Service Failure (Attempt {$count})";
    $body =
        "Error in {$conectorName}:\n\n" .
        $lastError;
}

return [
    "resError"      => $messages,
    "countError"    => $count,
    "actual_status" => $count === 3 ? "FAILED" : ($data["actual_status"] ?? "FAILED"),
    "body"          => $body,
    "subject"       => $subject
];