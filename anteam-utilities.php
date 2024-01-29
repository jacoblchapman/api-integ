<?php

// return a multiplier to sync up weight units acros the programme by translating everything into kg



// // fetch all orders to be displayed in table, in accordance to current setting enabled
// function fetchOrders($arr = array('status' => 'wc-processing','limit' => -1,'orderby' => 'date', 'order' => 'DESC',)) {

//     // print_r($arr);

//     // fetch all store orders
//     $orders = wc_get_orders($arr);
    

//     // create array to store valid orders
//     $retOrders = array();
    
//     $Anteam_shipping_instance = new WC_Anteam_Shipping_method();

//     // filter based on setting
//     if($Anteam_shipping_instance->get_enabled() == true) {
//         foreach ($orders as $order) {
//             // select only orders with Anteam shipping method
//             $shipping_method = $order->get_shipping_method();

//             // if shipping method is Anteam shipping, add it to ret array
//             if ($shipping_method == 'Anteam Shipping') {
//                 array_push($retOrders, $order);
//             }
//         }
//     } else {
//         $authToken = $Anteam_shipping_instance->get_auth_token();
//         $checkOrders = checkAddresses($orders, $authToken);
//         foreach ($orders as $order) {            
//             // if(isUnknown($order)) {
//                 foreach ($checkOrders as $orderChecked) {
//                     if($orderChecked->id == $order->get_id()) {
//                         if($orderChecked->accepted) {
//                             array_push($retOrders, $order);
//                             break;
//                         }
//                     }
//                 }
//             // }
//         }
//     }

//     return $retOrders;
// }








//
// function writeOrder($order, $customer_id) {
//     $name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
//     $address = $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
//     $id = $order->get_id();
//
//     $content = '
//     <div class="divider"></div>
//     <p><strong>Name: </strong>' . $name . '</p>
//     <p><strong>Shipping Address: </strong>' . $address . '</p>
//     <p><strong>Order ID: </strong>' . $id . '</p>
//     <p><strong>Customer ID: </strong>' . $customer_id . '</p>
//
//     <table border="1">
//         <tr>
//             <th>Item</th>
//             <th>SKU</th>
//             <th>Description</th>
//             <th>Quantity</th>
//         </tr>';
//
//     // <oop through each item in the order
//     foreach ($order->get_items() as $item_id => $item) {
//         $product = $item->get_product();
//         $item_name = $product ? $product->get_name() : $item->get_name();
//         $item_sku = $product ? $product->get_sku() : '';
//         $item_desc = $product ? $product->get_description() : '';
//         $item_quantity = $item->get_quantity();
//
//         // Append item details to the table
//         $content .= '
//         <tr>
//             <td>' . $item_name . '</td>
//             <td>' . $item_sku . '</td>
//             <td>' . $item_desc . '</td>
//             <td>' . $item_quantity . '</td>
//         </tr>';
//     }
//
//     $content .= '
//     </table>
//     ';
//
//     $file_path = __DIR__ . '/print-orders.html';
//
//     file_put_contents($file_path, $content, FILE_APPEND);
// }

// resets all properties of an order


// reset the contents of print-orders.html
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
