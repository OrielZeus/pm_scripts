<?php 
use Carbon\Carbon;


$dataFile = $data['field'];
$errorsVendor = [];

// Deleted by Ana Castillo requested by the client 2024-01-02
/*if ($dataFile['IN_INVOICE_VENDOR'] != "" && $dataFile['IN_INVOICE_VENDOR'] != null) {
    $temp = strtoupper($dataFile['IN_INVOICE_VENDOR']);
    $dataFile['IN_INVOICE_VENDOR'] = $errorsVendor[$temp] != null ? $errorsVendor[$temp] : $dataFile['IN_INVOICE_VENDOR'];
}*/
if ($dataFile['IN_INVOICE_DATE'] != "" && $dataFile['IN_INVOICE_DATE'] != null) {
    $temp = getFormattedDate($dataFile['IN_INVOICE_DATE']);
    $dataFile['IN_INVOICE_DATE'] = $temp;
}
if ($dataFile['IN_INVOICE_NUMBER'] != "" && $dataFile['IN_INVOICE_NUMBER'] != null) {
    $temp = strtoupper($dataFile['IN_INVOICE_NUMBER']);
    $dataFile['IN_INVOICE_NUMBER'] = $errorsVendor[$temp] != null ? $errorsVendor[$temp] : $dataFile['IN_INVOICE_NUMBER'];
}
if ($dataFile['IN_INVOICE_TAX_TOTAL'] != "" && $dataFile['IN_INVOICE_TAX_TOTAL'] != null) {
    //$cleaned_string = preg_replace(['/[\$,]/', '/^0+/'], '', $dataFile['IN_INVOICE_TAX_TOTAL']);
    //$cleaned_string = number_format((float)preg_replace('/[^\d.]/', '', $dataFile['IN_INVOICE_TAX_TOTAL']), 2);
    $dataFile['IN_INVOICE_TAX_TOTAL'] =  $dataFile['IN_INVOICE_TAX_TOTAL'];
}
if ($dataFile['IN_INVOICE_TOTAL'] != "" && $dataFile['IN_INVOICE_TOTAL'] != null) {
    //$cleaned_string = preg_replace(['/[\$,]/', '/^0+/'], '',  $dataFile['IN_INVOICE_TOTAL']);
    //$cleaned_string =  number_format((float)preg_replace('/[^\d.]/', '', $dataFile['IN_INVOICE_TOTAL']), 2);
    $dataFile['IN_INVOICE_TOTAL'] = $dataFile['IN_INVOICE_TOTAL'];
}

return $dataFile;

function getFormattedDate($date)
{
    try {
        $carbonDate = Carbon::parse($date)->format('Y-m-d');
        return $carbonDate;
    } catch (Carbon\Exceptions\InvalidTimeZoneException | Exception $e) {
        return "";
    }
}