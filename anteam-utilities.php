<?php

// return a multiplier to sync up weight units acros the programme by translating everything into kg
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


// fetch all orders to be displayed in table, in accordance to current setting enabled
function fetchOrders() {
    
    // fetch all store orders
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
            
            // if shipping method is Anteam shipping, add it to ret array
            if ($shipping_method == 'Anteam Shipping') {
                array_push($retOrders, $order);
            }
        }
    } else {
        $Anteam_shipping_instance = new WC_Anteam_Shipping_Method();

        $authToken = $Anteam_shipping_instance->get_auth_token();
        // check all orders against valid postcode list
        $checkOrders = checkAddresses($orders, $authToken);
        foreach ($orders as $order) {
            
            // ensure order hasn't already been rejected from table
            if(get_post_meta($order->get_id(), 'anteam_denied', true) == 'false') {
                
                // ensure order is under 15kg    
                if(getOrderWeight($order) < (15 * getWeightMultiplier())) {


                    
                    foreach ($checkOrders as $orderChecked) {
                        if($orderChecked->id == $order->get_id()) {
                            if($orderChecked->accepted) {
                                // add to ret array
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
    if($order->get_status() == 'processing') {
        

        $Anteam_shipping_instance = new WC_Anteam_Shipping_Method();
        
    
        // Prepare additional data for POST request
        date_default_timezone_set('Europe/London');
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');
        $total_weight = getOrderWeight($order);
        $multiplier = getWeightMultiplier();
        
        // get package size
         if($total_weight < (0.5*$multiplier)) {
            $size = "extra_small";
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
            error_log("Error , line 104 : anteam_utilities.php");
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
        $httpStatus = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        
        // error handling from API
        if($httpStatus==201) {
            // Order has been accepted, update status to completed
            $order->update_status('completed');
        } else {
            error_log("Error, line 155 : Anteam utilities.php , Result : " . $result . " Status : " . $httpStatus);
            echo '<script type="text/javascript">alert("Error : Order No. ' . $order->get_id() . ' not fulfilled succesfully, please contact Anteam support");</script>';
            
        } 
        
        curl_close($handle);
    }
        
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
    
    // returns weight in whatever is shop's basic unit
    return $total_weight;
    
}

// check orders against Anteam valid postcode list
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
    $httpStatus = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    if($httpStatus==200) {
        // Decode the response as a JSON object
        $json = json_decode($result);
        // accepted
        return $json;
    } else {
        return array();
    }
}