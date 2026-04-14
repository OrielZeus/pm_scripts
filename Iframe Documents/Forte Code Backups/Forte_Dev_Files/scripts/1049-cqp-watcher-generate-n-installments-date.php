<?php 
/*  
 * CQP - Watcher - Generate N Installments Date
 * By Natalia Mendez
 */

//Get Data from Form
$nInstallments = $data["CQP_N_INSTALLMENTS"] ?? null;

// Generate default N Dates for Installments Date
$summaryDates = generateSummaryInstallmentsDate($nInstallments);

//Return Array Created
return $summaryDates;

/*
* Generates an array of Inputs for Installments Dates with his respective Installment Number
*
* @param (int) $numInstallments        // Total number of Installments Date to generate
* @return (array) $claimsArray    // Array of generated claims with Installments number
*
* by Natalia Mendez
*/
function generateSummaryInstallmentsDate($numInstallments) {

    $claimsArray = [];

    // Loop to generate claims data
    for ($i = 0; $i < $numInstallments; $i++) {
        // Installments number
        $installmentNumber = $i + 1;

        // Create associative array for each claim
        $claim = [
            "CQP_INSTALLMENTS_NUMBER" => $installmentNumber,
            "form_html_viewer" => null
        ];

        $claimsArray[] = $claim;
    }

    return $claimsArray;
}