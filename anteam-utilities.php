<?php
function getOrderWeight($order) {
    
    // Get the items in the order
    $items = $order->get_items();
    $total_weight = 0;
    $size = '';
    
    // Loop through each item and calculate the total weight
    foreach ($items as $item) {
        $product = $item->get_product();
    
        if ($product && $product->get_weight()) {
            $weight = floatval($product->get_weight());
            $quantity = intval($item->get_quantity());
            $item_weight = $weight * $quantity;
            $total_weight += $item_weight;
        }
        
    }
    
    return $total_weight;
    
}


// Can pass in postcode or full address, whichever is needed
// Would JSON just look like this?
// {
//     "allowed" = true,
// }
function allowOrder($pickup_address, $dropoff_address) {
    $url = "https://anteam.co.uk/placeholder?pickup-address=" . $pickup_address . "&dropoff-address=" . $dropoff_address; #placeholder url
    # could store pickup lat / lon to save on an API request?
    
    $response = file_get_contents($url);
    $json = json_decode($response, true);
    
    if($json->allowed == true) {
        return true
    } else {
        return false
    }
    
}


// OLD METHOD TO CHECK IF ORDER IS ALLOWED
// Pass in full address to google maps API, returns latitude and longitude co-ords
// Use haversine on lat1 and lon1 (pickup address latitude longitude) and lat2 and lon2 (co-ords returned by maps API)

function fetchLatLon($address) {
$API_KEY = "AIzaSyC4yPb-81eKJDgyQh2IIR8Secx6rvuIdYs";

$address = urlencode($address);

$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&key=$API_KEY";

// Send the request and get the response
$response = file_get_contents($url);

// Decode the response as a JSON object
$json = json_decode($response);

if($json->status != 'OK') {
    return null;
} else {
    
    // Extract the latitude and longitude coordinates
    $latitude = $json->results[0]->geometry->location->lat;
    $longitude = $json->results[0]->geometry->location->lng;

    return array($latitude, $longitude);
}

}

function haversine($lat1, $lon1, $lat2, $lon2) {
    // Earth's radius in kilometers
    $radius = 6371;

    // Convert degrees to radians
    $lat1Rad = deg2rad($lat1);
    $lon1Rad = deg2rad($lon1);
    $lat2Rad = deg2rad($lat2);
    $lon2Rad = deg2rad($lon2);

    // Calculate differences between latitudes and longitudes
    $latDiff = $lat2Rad - $lat1Rad;
    $lonDiff = $lon2Rad - $lon1Rad;

    // Haversine formula
    $a = sin($latDiff/2) * sin($latDiff/2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($lonDiff/2) * sin($lonDiff/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $radius * $c;
    return $distance;
}