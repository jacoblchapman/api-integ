<?php

/*
Plugin Name: Anteam Shipping
Version: 1.0
Author: Anteam
Description: Automatically ship products from your store using Anteam
Requires PHP: 8.0
Requires at least: 6.1.3
*/

// Include the WooCommerce plugin files and shipping class files
require_once( plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php' );
require_once( plugin_dir_path( __FILE__ ) . 'anteam-shipping-class.php' );
require_once( plugin_dir_path( __FILE__ ) . 'anteam-utilities.php' );

// custom order property
function add_anteam_denied_field($order_id) {
    update_post_meta($order_id, 'anteam_denied', 'false');
}
add_action('woocommerce_new_order', 'add_anteam_denied_field');

// Instantiate and add the custom shipping method
function anteam_add_shipping_method($methods) {
    $methods['anteam_shipping'] = 'WC_Anteam_Shipping_Method';

    global $Anteam_shipping_instance;
    $Anteam_shipping_instance = new WC_Anteam_Shipping_method();
    return $methods;
}
add_filter('woocommerce_shipping_methods', 'anteam_add_shipping_method');


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



// Recalculate shipping whenever shipping address is changed - using WC action hook
add_action('woocommerce_shipping_address_changed', 'anteam_calculate_shipping_on_address_change', 10, 2);
function anteam_calculate_shipping_on_address_change($customer_id, $address) {
    // Get the shipping instance
    global $Anteam_shipping_instance;

    $Anteam_shipping_instance->calculate_shipping();
}

// Generate table
function load_orders_page() {

    global $Anteam_shipping_instance;
    // "Button" press logic
    if (isset($_GET['action']) && isset($_GET['order_id'])) {
        $action = $_GET['action'];
        $order_id = $_GET['order_id'];
        
        if ($action == 'clear') {
            clear_packing();
        }
        $order = wc_get_order($order_id);
        if ($order) {
            if ($action === 'approve') {
                orderApproved($order);
            }
            elseif ($action === 'deny') {
                orderDenied($order);
            } elseif ($action === 'undo') {
                reset_order($order);
            }
        }
    }
    
    // load orders according to selected setting
    $orders = fetchOrders();
    
    // Output table
    echo '<style type="text/css">
    .table-title {
        margin-top: 20px;
    }
    </style>';
    
    echo '<h1 class = "table-title">Anteam Order Approval</h1>';


    echo '<style type="text/css">
    .order-approval-table {
        margin-top: 20px;
    }
    .table-title {
        margin-bottom: 20px;
    }
    </style>';
    
    // action buttons
    echo '<div style="display: inline-flex; gap: 10px;">';

    echo '<form action="' . esc_url(plugins_url('print-orders.html', __FILE__)) . '" method="post" target="_blank">
        <input type="submit" name="printButton" value="Print orders">
    </form>';

    $last_action = $Anteam_shipping_instance->last_order;
    echo '<form action="?page=anteam-orders&action=undo&order_id=' . $last_action . '" method="post">
        <input type="submit" name="undoButton" value="Undo">
    </form>';

    echo '<form action="?page=anteam-orders&action=clear&order_id=0" method="post">
        <input type="submit" name="clearButton" value="Clear Packing List">
    </form>';

    echo '</div>';




    echo '<table class="wp-list-table widefat striped order-approval-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Order ID</th>';
    echo '<th>Customer Name</th>';
    echo '<th>Order Total (£)</th>';
    echo '<th>Weight (' . get_option('woocommerce_weight_unit') . ')</th>';
    echo '<th>Address</th>';
    echo '<th>Shipping Method</th>';
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
            $order_weight = getOrderWeight($order);
            $order_address = $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
            $shipping_method = $order->get_shipping_method();
    
                // Show order
                echo '<tr>';
                echo '<td>' . $order_id . '</td>';
                echo '<td>' . $customer_name . '</td>';
                echo '<td>' . $order_total . '</td>';
                echo '<td>' . $order_weight . '</td>';
                echo '<td>' . $order_address . '</td>';
                echo '<td>' . $shipping_method . '</td>';
                echo '<td><a href="?page=anteam-orders&action=approve&order_id=' . $order_id . '">Approve</a></td>';
                echo '<td><a href="?page=anteam-orders&action=deny&order_id=' . $order_id . '">Deny</a></td>';
                echo '<td><a href="' . $view_order_url . '">View Order</a></td>';
                echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
}
