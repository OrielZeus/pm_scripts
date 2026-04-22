<?php 
/*  
 *  All $config variables are assigned and created as request data.
 * by Telmo Chiri
 */

$dataReturn = [];

if (is_array($config) && !empty($config)) {
    $dataReturn = $config;
}

return $dataReturn;