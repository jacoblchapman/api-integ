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
    global $Anteam_shipping_instance;
    
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

        $authToken = $Anteam_shipping_instance->get_auth_token();

        $checkOrders = checkAddresses($orders, $authToken);
    
        foreach ($orders as $order) {
            if(get_post_meta($order->get_id(), 'anteam_denied', true) != 'true') {
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
    
    return $retOrders;
}

// Approval and Denied functions
function orderApproved($order)
{
    if($order->get_status() == 'processing') {
        
        global $Anteam_shipping_instance;
        
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
            $size = "extra_large";
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
        curl_close($handle);

        // error handling from API
        if($httpStatus==201) {
            // Order has been accepted, update status to completed
            $order->update_status('completed');
            $customer_id = json_decode($result)->customer_id;

            $Anteam_shipping_instance->last_order = $order->get_id();
            writeOrder($order, $customer_id);
            return true;
        } else {
            error_log("Error, line 155 : Anteam utilities.php , Result : " . $result . " Status : " . $httpStatus);
            echo '<script type="text/javascript">alert("Error : Order No. ' . $order->get_id() . ' not fulfilled succesfully, please contact Anteam support");</script>';  
            return false;
        } 
    }
}

function orderDenied($order)
{
    global $Anteam_shipping_instance;
    $Anteam_shipping_instance->last_order = $order->get_id();

    update_post_meta($order->get_id(), 'anteam_denied', 'true');
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


function checkAddresses($orders, $authToken) {

    $data = array();
    foreach ($orders as $order) {
        $d = [
            'id' => $order->get_id(),
            'postcode' => $order->get_shipping_postcode(),
            'weight' => getOrderWeight($order),
            'unit' => get_option('woocommerce_weight_unit'),
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

function writeOrder($order, $customer_id) {
    $name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
    $address = $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
    $id = $order->get_id();

    $content = '
    <div class="divider"></div>
    <p><strong>Name: </strong>' . $name . '</p> 
    <p><strong>Shipping Address: </strong>' . $address . '</p>
    <p><strong>Order ID: </strong>' . $id . '</p>
    <p><strong>Customer ID: </strong>' . $customer_id . '</p>
    
    <table border="1">
        <tr>
            <th>Item</th>
            <th>SKU</th>
            <th>Description</th>
            <th>Quantity</th>
        </tr>';

    // Loop through each item in the order
    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $item_name = $product ? $product->get_name() : $item->get_name();
        $item_sku = $product ? $product->get_sku() : '';
        $item_desc = $product ? $product->get_description() : '';
        $item_quantity = $item->get_quantity();

        // Append item details to the table
        $content .= '
        <tr>
            <td>' . $item_name . '</td>
            <td>' . $item_sku . '</td>
            <td>' . $item_desc . '</td>
            <td>' . $item_quantity . '</td>
        </tr>';
    }

    $content .= '
    </table>
    ';
    
    $file_path = __DIR__ . '/print-orders.html';
    
    file_put_contents($file_path, $content, FILE_APPEND);
}

function reset_order($order) {
    update_post_meta($order->get_id(), 'anteam_denied', 'false');
    $order->update_status('processing');
}

function clear_packing() {
    $content = '<!DOCTYPE html>
<html>
<head>
    <title>Order Details</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
        .divider {
            border: 1px solid black;
            width: 95%;
            margin: 20px auto;
        }
    </style>
</head>
<body>';
    
    $file_path = __DIR__ . '/print-orders.html';
    
    file_put_contents($file_path, $content);
    
    
}
