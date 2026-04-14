<?php 
/*   
 * Capture and save errors
 * by Helen Callisaya
 * Modified by Mateo Rada
 * Modified by Natalia Mendez
 */

function processForteErrors(array $data): array
{
    $errors = $data['_request']['errors'] ?? [];
    
    if (empty($errors) || !is_array($errors)) {
        $data['resErrorHandling'] = [];
        $data['FORTE_ERROR'] = ['data' => []];
        $data['FORTE_ERROR_MESSAGE'] = '';
        return $data;
    }
    // Get errors without duplicates 
    $uniqueErrors = [];

    foreach ($errors as $error) {

        $message   = trim($error['message'] ?? '');
        $elementId = $error['element_id'] ?? '';

        // Composite key to avoid actual duplicates
        $key = $elementId . '|' . $message;

        if ($message !== '' && !isset($uniqueErrors[$key])) {
            $uniqueErrors[$key] = $error;
        }
    }

    // Reindex array
    $uniqueErrors = array_values($uniqueErrors);

    if (empty($uniqueErrors)) {
        $data['resErrorHandling'] = [];
        $data['FORTE_ERROR'] = ['data' => []];
        $data['FORTE_ERROR_MESSAGE'] = '';
        return $data;
    }

    // Get last error
    $lastError = end($uniqueErrors);

    $structuredError = [
        'FORTE_ERROR_LOG'           => $lastError['message'] ?? '',
        'FORTE_ERROR_DATE'          => $lastError['created_at'] ?? '',
        'FORTE_ERROR_BODY'          => $lastError['body'] ?? '',
        'FORTE_ERROR_REQUEST_ID'    => $data['_request']['id'] ?? '',
        'FORTE_ERROR_ELEMENT_ID'    => $lastError['element_id'] ?? '',
        'FORTE_ERROR_ELEMENT_NAME'  => $lastError['element_name'] ?? '',
        'FORTE_ERROR_PROCESS_ID'    => $data['_request']['process_id'] ?? '',
        'FORTE_ERROR_PROCESS_NAME'  => $data['_request']['name'] ?? ''
    ];

    // Save the errors
    $data['resErrorHandling']   = $uniqueErrors;
    $data['FORTE_ERROR']        = ['data' => $structuredError];
    $data['FORTE_ERROR_MESSAGE'] = $structuredError['FORTE_ERROR_LOG'];

    return $data;
}

// Run main function for errors
$data = processForteErrors($data);
return $data;