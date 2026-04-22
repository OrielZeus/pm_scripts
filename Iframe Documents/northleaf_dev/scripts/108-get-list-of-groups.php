<?php
/**********************************
 * Get List of Groups
 *
 * by Telmo Chiri
 *********************************/
require_once("/Northleaf_PHP_Library.php");
$apiHost = getenv('API_HOST');

$urlGetGroups = $apiHost."/groups?status=ACTIVE&order_direction=asc&per_page=1000";
$responseGetGroups = callApiUrlGuzzle($urlGetGroups, "GET", []);

return $responseGetGroups['data'];