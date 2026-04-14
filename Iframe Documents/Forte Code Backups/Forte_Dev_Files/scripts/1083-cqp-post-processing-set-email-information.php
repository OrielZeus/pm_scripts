<?php 
/*  
 *  CQP - Postprocessing Set email Information
 *  By Natalia Mendez
 *  Modified by Adriana Centellas
 *  Modified by Diego Tapia
 * Modified by Natalia Mendez
 */

require_once("/CQP_Generic_Functions.php");
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;

//Global Variables
$apiHost = getEnv("API_HOST");
$apiToken = getEnv("API_TOKEN");
$apiSql = getEnv("API_SQL");
$apiUrl = $apiHost . $apiSql;

$languageSelected = $data["CQP_LANGUAGE_OPTION"];

$data["CQP_MARKETS_DETAIL_EMAIL"] = convertDashLinesToHtmlBullets($data["CQP_MARKETS_DETAIL_EMAIL"]);

sendNotification($data, 'BRK', $languageSelected, $api);

return [
    "CQP_CONFIRM_SUBMIT_TAKEN" => "NO", 
    "CQP_CONFIRM_SUBMIT_NOT_TAKEN" => "NO",
    
    "resErrorHandling"   => "", // Clear error variable to avoid previous error to be shown afterwards
    "FORTE_ERROR"       => ['data' => ["FORTE_ERROR_LOG" => ""]],
    "FORTE_ERROR_MESSAGE" => ""
    ];

/*
* Convert dash-prefixed text lines into an HTML bullet list
* styled to match email template typography.
*
* @param (string) $text //1-10
* @return (string) HTML list
*
* by Adriana Centellas
*/
function convertDashLinesToHtmlBullets($text)
{
    $lines = preg_split('/\r\n|\r|\n/', (string)$text);

    $html = '<ul style="margin:0;padding-left:20px;color:#556271;font-family:Arial, sans-serif;">';

    foreach ($lines as $line) {

        $line = trim($line);
        if ($line === '') {
            continue;
        }

        // remove leading dash
        $line = preg_replace('/^\-\s*/', '', $line);

        $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');

        $html .= '<li style="color:#556271;margin-bottom:2px;">'.$line.'</li>';
    }

    $html .= '</ul>';

    return $html;
}