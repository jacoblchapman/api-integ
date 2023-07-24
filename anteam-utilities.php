<?php

// fetch orders according to setting

function getWeightMultiplier() {
    $unit = get_option('woocommerce_weight_unit');
    if($unit == 'kg') {
        return 1;
    } else if($unit == 'g') {
        return 1000;
    } else if($unit == 'lbs') {
        return 2.20462262;
    } else {
        return 35.273962;
    }
}

function fetchOrders() {
    // do we need a limit?
    // fetch all orders marked as processing
    $orders = wc_get_orders(array(
    'status' => 'processing',
    'limit' => -1,
    ));
    
    
    // create array to store valid orders
    $retOrders = array();
    $Anteam_shipping_instance = new WC_Anteam_Shipping_Method();
    
    // filter based on setting
    if($Anteam_shipping_instance->get_enabled() == true) {
        foreach ($orders as $order) {
            
            // select only orders with Anteam shipping method
            $shipping_method = $order->get_shipping_method();
            if ($shipping_method == 'Anteam Shipping') {
                array_push($retOrders, $order);
            }
        }
    } else {
        $Anteam_shipping_instance = new WC_Anteam_Shipping_Method();

        $authToken = $Anteam_shipping_instance->get_auth_token();

        $checkOrders = checkAddresses($orders, $authToken);
    
        foreach ($orders as $order) {
            if(get_post_meta($order->get_id(), 'anteam_denied', true) == 'false') {
                if(getOrderWeight($order) < (15 * getWeightMultiplier())) {
                    // $address = $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
                    // $latlon = fetchLatLon($address);
                    // if($latlon == null) {
                    //     error_log('ERROR WITH ORDER '. $order->get_id() . ' address :' . $order->get_shipping_address_1());
                    // } else {
                    //     $distance = haversine($Anteam_shipping_instance->get_pickup_latitude(), $Anteam_shipping_instance->get_pickup_longitude(), $latlon[0], $latlon[1]);
                    //     if($distance <= 12.87) {
                    //         error_log('adding order');
                    //         array_push($retOrders, $order);
                    //     }
                    // }


                    
                    foreach ($checkOrders as $orderChecked) {
                        if($orderChecked->id == $order->get_id()) {
                            if($orderChecked->accepted) {
                                error_log('adding order' . $order->get_id());
                                array_push($retOrders, $order);
                                break;
                            }
                        }
                    } 
                }
            }
        }
    }
    
    return $retOrders;
}

// Approval and Denied functions
function orderApproved($order)
{
    
    $Anteam_shipping_instance = new WC_Anteam_Shipping_Method();
    

    // Prepare additional data for POST request
    date_default_timezone_set('Europe/London');
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    $total_weight = getOrderWeight($order);
    $multiplier = getWeightMultiplier();
    
     if($total_weight < (0.5*$multiplier)) {
        $size = extra_small;
    } else if ($total_weight < (2*$multiplier)) {
        $size = "small";
    } else if ($total_weight < (5*$multiplier)) {
       $size = "medium";
    } else if ($total_weight < (10*$multiplier)) {
        $size = "large";
    } else if ($total_weight < (15*$multiplier)) {
        $size = "extra_large";
    } else {
        $size = "inv";
    }
    
    $handle = curl_init('https://api.anteam.co.uk/api/requests/woo_create/');
    $authToken = $Anteam_shipping_instance->get_auth_token();
    
    // JSON data
    $data = [
            'pickup' => [
            'address' => $Anteam_shipping_instance->get_pickup_address(),
            'lat' => 0,
            'lon' => 0,
            'instructions' => '',
            'contactName' => $Anteam_shipping_instance->get_owner_name(),
            'contactNumber' => $Anteam_shipping_instance->get_owner_phone(),
            'business' => true,
        ],
        'dropoff' => [
            'address' => $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode(), 
            'lat' => 0,
            'lon' => 0,
            'instructions' => '',
            'contactName' => $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name(),
            'contactNumber' => $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone(),
            'business' => null,
        ],
        'pickup_start_time' => $currentDate . 'T' . $currentTime,
        'dropoff_start_time' => $currentDate . 'T' . $currentTime,
        'size' => $size,
    ];

    // Prepare JSON and send request
    $encodedData = json_encode($data);

    curl_setopt($handle, CURLOPT_POST, 1);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
    curl_setopt($handle, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: TOKEN '. $authToken,
    ]);

    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($handle);
    // ADD ERROR HANDLING FOR RESULT
    curl_close($handle);
    
    // Order has been accepted, update status to completed
    $order->update_status('completed');
}

function orderDenied($order)
{
    
    // set shipping method to free shipping, business can handle as they like
    $free_shipping_method_id = 'free_shipping:1';
    $shipping_items = $order->get_items('shipping');
    
    update_post_meta($order->get_id(), 'anteam_denied', 'true');

    foreach ($shipping_items as $shipping_item) {
        $shipping_item->set_method_title($free_shipping_method_id);
        $shipping_item->save();
    }

    $order->save();
  
} 

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

function fetchLatLon($address) {
    
$API_KEY = "AIzaSyCyuj2O2NfgYm-wze8w1S8O6-6NYMgZXNo";
error_log($address);
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

function checkAddresses($orders, $authToken) {

    $data = array();
    foreach ($orders as $order) {

        $d = [
            'id' => $order->get_id(),
            'postcode' => $order->get_shipping_postcode(),
        ];

        array_push($data, $d);

    }
    
    $url = "https://api.anteam.co.uk/profiles/check_address/";

    $handle = curl_init($url);


    $encodedData = json_encode($data);

    curl_setopt($handle, CURLOPT_POST, 1);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
    curl_setopt($handle, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: TOKEN '. $authToken,
    ]);

    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($handle);
    // ADD ERROR HANDLING FOR RESULT
    curl_close($handle);

    // Decode the response as a JSON object
    $json = json_decode($result);

    // accepted
    return $json;
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