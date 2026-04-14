<?php 
/*  
 *  Get Service Error and display error messages on the screen if an error occurs
 *  By Natalia Mendez
 */

$errorComplete = $data['_request']['errors'];

$uniqueErrors = [];
$seenMessages = [];

if (!empty($errorComplete) && is_array($errorComplete)) {
    foreach ($errorComplete as $error) {
        if (!empty($error['message'])) {
            
            if (in_array($error['message'], $seenMessages, true)) { // Avoids the duplicated messages
                continue;
            }

            $seenMessages[] = $error['message'];
            $uniqueErrors[] = $error;
        }
    }
}

$data["resErrorHandling"] = $uniqueErrors; // Assigne the errors without duplicates

return $data;