<?php 

/**
* This script makes a request to the Google Maps API to get the coordinates from an address string. It requires the address string to be clean
* meaning it can't have special characters (eg. #,-,/) or tildes. Refer to https://developers.google.com/maps/documentation/geocoding/requests-geocoding?hl=es-419
* and https://developers.google.com/maps/documentation/embed/embedding-map?hl=es-419#place_mode for more information
*
* by Mateo Rada Arias
*/

$address = $data["ADDRESS"];
$region = $data["COUNTRY_CODE"];
$key = getenv("GOOGLE_MAPS_API_TOKEN"); 

// Format and clean the address
$formattedAddress = cleanseString($address);
$formattedAddress = str_replace(" ", "+", $formattedAddress);

// Build the URL
$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$formattedAddress}&sensor=false&region={$region}&key={$key}";


// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute request
$response = curl_exec($ch);
curl_close($ch);

// Decode JSON
$res = json_decode($response, true);

if (isset($res['results'][0]['geometry']['location'])) {
    $lat = $res['results'][0]['geometry']['location']['lat'];
    $lng = $res['results'][0]['geometry']['location']['lng'];
    //return $lat . "," . $lng;
    //return convertToDMS($lat) . " " . convertToDMS($lng);
    return [
        "DISPLAY_COORDS" => convertToDMS($lat,true) . " " . convertToDMS($lng,false),
        "REAL_COORDS" => $lat . "," . $lng
    ];
} else {
    return $res; // or handle error
}

/**
*  This function cleans the string from special characters like #,/ or -
*  @param $input Address string
*  @return $normalized Address without special characters
*/
function cleanseString($input) {
    // 1. Convert accented characters to their non-accented equivalents
    $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $input);

    // 2. Remove commas and special characters except letters, numbers, and spaces
    $normalized = preg_replace('/[^A-Za-z0-9\s]/', '', $normalized);

    // 3. Replace multiple spaces with a single space
    $normalized = preg_replace('/\s+/', ' ', $normalized);

    // 4. Trim spaces at start and end
    return trim($normalized);
}

/**
 *  This function converts a decimal degree value into
 *  degrees, minutes, and seconds (DMS) format with the
 *  appropriate hemisphere indicator (N/S for latitude,
 *  E/W for longitude).
 *
 *  @param float $decimal   Coordinate in decimal degrees
 *  @param bool  $isLatitude Flag to indicate if the value is latitude (true) or longitude (false)
 *  @return string Formatted coordinate in DMS notation
 */

function convertToDMS($decimal, $isLatitude = true) {
    $direction = '';
    if ($isLatitude) {
        $direction = $decimal < 0 ? 'S' : 'N';
    } else {
        $direction = $decimal < 0 ? 'W' : 'E';
    }

    $decimal = abs($decimal);
    $degrees = floor($decimal);
    $minutesDecimal = ($decimal - $degrees) * 60;
    $minutes = floor($minutesDecimal);
    $seconds = round(($minutesDecimal - $minutes) * 60);

    return sprintf("%d°%d'%d\"%s", $degrees, $minutes, $seconds, $direction);
}