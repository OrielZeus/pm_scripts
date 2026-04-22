<?php
/******************************** 
 * Resend Emails - Preprocessing
 *
 * by Ronald Rodriguez
 *******************************/
require_once("/Northleaf_PHP_Library.php");

$emailType = '';

//Set initial values
$dataReturn = array();

//Format date for emails
$data["OFF_LAST_DAY_EMPLOYMENT_FORMAT"] = convertDate($data["OFF_LAST_DAY_EMPLOYMENT"]);
$dataReturn["OFF_LAST_DAY_EMPLOYMENT_FORMAT"] = convertDate($data["OFF_LAST_DAY_EMPLOYMENT"]);

//Send email notifications
$taskCode = "HR.02";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_HR02'] = $notificationSent;

$taskCode = "EA.02";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_EA02'] = $notificationSent;

$taskCode = "EA.03";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_EA03'] = $notificationSent;

$taskCode = "CF.01";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_CF01'] = $notificationSent;

if ($data['OFF_OFFICE_LOCATION']['OFFICE_DESCRIPTION'] == 'Toronto' || $data['OFF_OFFICE_LOCATION']['OFFICE_DESCRIPTION'] == 'London') {
    $taskCode = "IT.01";
    $notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
    $dataReturn['OFF_SENT_DETAILS_IT01'] = $notificationSent;
}

$taskCode = "IT.02";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_IT02'] = $notificationSent;

$taskCode = "CO.01";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_CO01'] = $notificationSent;

$taskCode = "CO.02";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_CO02'] = $notificationSent;

if ($data['OFF_OFFICE_LOCATION']['OFFICE_DESCRIPTION'] == 'Toronto') {
    $taskCode = "CO.03";
    $notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
    $dataReturn['OFF_SENT_DETAILS_CO03'] = $notificationSent;
}

$taskCode = "CO.04";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_CO04'] = $notificationSent;

$taskCode = "CM.01";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_CM01'] = $notificationSent;

$taskCode = "MK.01";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_MK01'] = $notificationSent;

$taskCode = "OS.01";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_OS01'] = $notificationSent;

$taskCode = "OS.02";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_OS02'] = $notificationSent;

$taskCode = "OS.03";
$notificationSent = sendNotificationOffboarding($data, $taskCode, $emailType, $api);
$dataReturn['OFF_SENT_DETAILS_OS03'] = $notificationSent;

return $dataReturn;

/**
 * Convert a date string into a human-readable format with the day suffix.
 *
 * @param string $date - The date string to convert.
 * @return string - The formatted date with the appropriate day suffix.
 *
 * by Adriana Centellas
 */
function convertDate($date) {
    // Convert the string to a DateTime object
    $date_obj = new DateTime($date);

    // Get the day and determine the correct suffix (st, nd, rd, th)
    $day = $date_obj->format('j');
    $suffix = ($day % 10 == 1 && $day != 11) ? 'st' : 
              (($day % 10 == 2 && $day != 12) ? 'nd' : 
              (($day % 10 == 3 && $day != 13) ? 'rd' : 'th'));

    // Set locale to English for month formatting
    setlocale(LC_TIME, 'en_US.UTF-8');
    // Format month and year in English
    $month = strftime('%B', $date_obj->getTimestamp());
    $year = $date_obj->format('Y');

    // Return the final formatted date with the correct order
    return "$month $day$suffix, $year";
}