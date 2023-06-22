<?php

/*
Plugin Name: Anteam Shipping
Version: 1.0
Author: Anteam
Description: Automatically ship products from your store using Anteam
*/

// Include the WooCommerce plugin files (relative)
require_once( plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php' );

// Add a new submenu page
function add_order_approval_submenu_page() {
    add_submenu_page(
        'woocommerce',
        'Anteam Orders',
        'Anteam Orders',
        'manage_options',
        'anteam-orders',
        'load_orders_page'
    );
}
add_action('admin_menu', 'add_order_approval_submenu_page');

// Generate table
function load_orders_page() {
    
    // "Button" press logic
    if (isset($_GET['action']) && isset($_GET['order_id'])) {
        $action = $_GET['action'];
        $order_id = $_GET['order_id'];

        $order = wc_get_order($order_id);
        if ($order) {
            if ($action === 'approve') {
                orderApproved($order);
            }
            elseif ($action === 'deny') {
                orderDenied($order);
            }
        } else {
            // error ? do something (maybe not necessary)
        }
    }
    
    // Get all processing orders
    $orders = wc_get_orders(array(
        'status' => 'processing',
    ));

    // Output table
    echo '<h1>Anteam Order Approval</h1>';

    echo '<style type="text/css">
    .order-approval-table {
        margin-top: 20px;
    }
    </style>';

    echo '<table class="wp-list-table widefat striped order-approval-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Order ID</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Order Total</th>';
    echo '<th>Approve</th>';
    echo '<th>Deny</th>';
    echo '<th>View</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($orders as $order) {
        // Load table data for order
        $order_id = $order->get_id();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_total = $order->get_total();
        $view_order_url = get_edit_post_link($order_id);

        // Show order
        echo '<tr>';
        echo '<td>' . $order_id . '</td>';
        echo '<td>' . $customer_name . '</td>';
        echo '<td>' . $order_total . '</td>';
        echo '<td><a href="?page=anteam-orders&action=approve&order_id=' . $order_id . '">Approve</a></td>';
        echo '<td><a href="?page=anteam-orders&action=deny&order_id=' . $order_id . '">Deny</a></td>';
        echo '<td><a href="' . $view_order_url . '">View Order</a></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}

// Approval and Denied functions
function orderApproved($order)
{
    // Placeholder
    $order->update_status('completed');
    // keep status as procesing?
    // pass to Anteam app
    // HANDLE
    // when delivered - update status?
}
function orderDenied($order)
{
    // Placeholder
    $order->update_status('cancelled');
    // update shipping option ?
} 

// Next steps :

// Add custom shipping method and pull orders based off that
// Look at how to implement 8 mile radius : Google API, Shipping zones?
// Implement logic for if order is rejected through Anteam panel
// Look at sending data over to app
// Look at handling logic after sent to app (e.g. order delivered , request unsuccesful, not enough credit..)
// do we need custom statuses??
















