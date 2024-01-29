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
require_once( plugin_dir_path( __FILE__ ) . 'anteam-woo-api.php' );
require_once( plugin_dir_path( __FILE__ ) . 'anteam-api.php' );


function anteam_scripts() {

    wp_register_script( 'anteam_browser_print', plugin_dir_url( __FILE__ ).'js/BrowserPrint-3.1.250.min.js', array( 'jquery' ), '1.0', true );
    
    wp_enqueue_script( 'anteam_browser_print' );

    wp_register_script( 'anteam_js', plugin_dir_url( __FILE__ ).'js/anteam.js', array( 'jquery' ), '1.0', true );
    
    wp_enqueue_script( 'anteam_js' );
    
}
    
add_action( 'admin_enqueue_scripts', 'anteam_scripts' );

// custom order property
function add_anteam_denied_field($order_id) {
    update_post_meta($order_id, 'anteam_denied', 'TBD');
}
add_action('woocommerce_new_order', 'add_anteam_denied_field');

// Instantiate and add the custom shipping method
function anteam_add_shipping_method($methods) {
    $methods['anteam_shipping'] = 'WC_Anteam_Shipping_Method';

    // global $Anteam_shipping_instance;
    // $Anteam_shipping_instance = new WC_Anteam_Shipping_method();
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

    $Anteam_shipping_instance = new WC_Anteam_Shipping_method();

    $Anteam_shipping_instance->calculate_shipping();
}

function custom_order_query($query, $query_vars){
    if ( ! empty( $query_vars['anteam_denied'] ) ) {
        $query['meta_query'][] = array(
            'key' => 'anteam_denied',
            'compare' => 'EXISTS',
            );
    }
    return $query;
}

add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'custom_order_query', 10, 2 );

function controller($anteam_api, $woo_api, $Anteam_shipping_instance) {
    // "Button" press logic
    if (isset($_GET['action']) ) {
        $action = $_GET['action'];
        
        if ($action == 'clear') {
            clear_packing();
        }
        if (isset($_GET['order_id'])) {
            $order_id = $_GET['order_id'];

            $order = wc_get_order($order_id);
            if ($order) {
                if ($action === 'approve') {
                    echo '<p>approve</p>';
                    $anteam_api->orderApproved($order, $Anteam_shipping_instance, $woo_api);
                } elseif ($action === 'deny') {
                    echo '<p>deny</p>';
                    $anteam_api->orderDenied($order);
                } elseif ($action === 'undo') {
                    echo '<p>undo</p>';
                    $woo_api->reset_order($order);
                }
            }
        }
    }
}

function get_orders($anteam_api, $woo_api, $display_list, $initial_date, $final_date) {



    $orders = array();
    if ($display_list === 'approved') {
      $orders_all = $woo_api->fetchOrders(array(
              'status' => array(
                'wc-pending',
              		'wc-on-hold',
              		'wc-completed',
              		'wc-cancelled',
              		'wc-refunded',
              		'wc-failed'),
                      'limit' => -1,
                      'orderby' => 'date', 
                      'order' => 'DESC',
                'date_created'=> $initial_date .'...'. $final_date 
              )
        );

        foreach ($orders_all as $order) {
            if($woo_api->isApproved($order)) {
                    array_push($orders, $order);
            }
        }
    } elseif ($display_list === 'denied'){

        $orders_all = $woo_api->fetchOrders(array(
            'status' => array(
                    'wc-pending',
                    'wc-processing',
                    'wc-on-hold',
                    'wc-completed',
                    'wc-cancelled',
                    'wc-refunded',
                    'wc-failed'),
                    'limit' => -1,
                    'orderby' => 'date', 
                    'order' => 'DESC',
              'date_created'=> $initial_date .'...'. $final_date 
            )
      );

      foreach ($orders_all as $order) { 
        if($woo_api->isDenied($order)) {
            array_push($orders, $order);
        }
      }

    } else {
        $orders_all = $woo_api->fetchOrders(

        array('status' => 'wc-processing',
              'limit' => -1,
              'orderby' => 'date', 
              'order' => 'DESC',
              'date_created'=> $initial_date .'...'. $final_date 
              )

      );

      foreach ($orders_all as $order) { 
         if($woo_api->isUnknown($order)) {
            array_push($orders, $order);
         }
      }

    }
    $orders = $anteam_api->checkOrders($orders);
    return $orders;

}


function styles(){
    echo '<style type="text/css">
    .table-title {
        margin-top: 20px;
    }
    .order-approval-table {
        margin-top: 20px;
    }
    .table-title {
        margin-bottom: 20px;
    }
    </style>';
}

function page_header($display_list) {
    echo '<h1 class = "table-title">Anteam Order Approval - ';
    if($display_list == "approved"){
        echo "Approved";
    } elseif ($display_list == "denied"){
        echo "Denied";
    } else {
        echo "New";
    }
    echo ' Orders';
    echo '</h1>';
}

function page_controls($display_list, $initial_date, $final_date) {

    echo '<ul class="subsubsub">';

    echo '<li class="">';
    echo '<a href="?page=anteam-orders&start_date=' . $initial_date . '&end_date=' . $final_date . '"> New';
    echo '<span"></span>';
    echo '</a> |';
    echo '</li> ';

    echo '<li class="">';
    echo '<a href="?page=anteam-orders&list=approved&start_date=' . $initial_date . '&end_date=' . $final_date . '"> Approved';
    echo '<span></span>';
    echo '</a> |';
    echo '</li> ';

    echo '<li class="section-nav-tab" role="none">';
    echo '<a href="?page=anteam-orders&list=denied&start_date=' . $initial_date . '&end_date=' . $final_date . '"> Denied';
    echo '<span></span>';
    echo '</a>';
    echo '</li>';

    echo '</ul>';
    
    echo '</br>';
    echo '</br>';


    echo '<div >';

    

    echo '
    <script type="text/javascript">
    function anteam_filter() 
    {
        
        window.location.replace("?page=anteam-orders&list='.$display_list.'&start_date="+jQuery( "input#datepickerstart" ).val() +"&end_date="+jQuery( "input#datepickerend" ).val());
        
        return true;
    };
    
    function anteam_filter_today() 
    {
        
        window.location.replace("?page=anteam-orders&list='.$display_list.'&start_date=' . date("Y-m-d") . '&end_date=' . date("Y-m-d", strtotime("+1 day")) . '");
        
        return true;
    };

</script>';

    echo '<span class="form-row my-field-class validate-required" id="datepickerstart_field" data-priority=""><label for="datepickerstart" class="">Start Date&nbsp;</label><span class="woocommerce-input-wrapper"><input type="date" class="input-text " name="order_pickup_date" id="datepickerstart" placeholder="Select Date" value="' . $initial_date . '"></span></span>
    <span class="form-row my-field-class validate-required" id="datepickerend_field" data-priority=""><label for="datepickerend" class="">End Date&nbsp;</label><span class="woocommerce-input-wrapper"><input type="date" class="input-text " name="order_pickup_date" id="datepickerend" placeholder="Select Date" value="' . $final_date . '"></span></span>
    <input type="submit" name="filter_action" id="order-query-submit" class="button" value="Filter" onclick="anteam_filter()">
    <input type="submit" name="filter_today_action" id="order-query-submit_today" class="button" value="Filter Today" onclick="anteam_filter_today()">
    | <input type="submit" name="print_action" id="order-query-submit_print" class="button" value="Print Table" onclick="popup()">';
    echo '</div>';





}


function page_table_head($display_list="default", $controls=TRUE) {

    echo '<thead>';
    echo '<tr>';
    echo '<th>Order ID</th>';
    if(($display_list == 'default') || $display_list == 'denied'){
        echo '<th>Order Date</th>';
    } else {
        echo '<th>Approved Date</th>';
    }
    
    echo '<th>Customer Name</th>';
    echo '<th>Order Total (£)</th>';
    echo '<th>Weight (' . get_option('woocommerce_weight_unit') . ')</th>';
    echo '<th>Address</th>';
    echo '<th>Shipping Method</th>';
    if($controls){
        echo '<th>View</th>';
        echo '<th>meta</th>';
        echo '<th>Approve</th>';
        echo '<th>Deny</th>';
    }
    if($display_list == 'approved'){
        echo '<th>Label</th>';
    }
    echo '</tr>';
    echo '</thead>';
}

function page_table_data($content){
    echo '<td>' . $content . '</td>';
}

function get_page_href($the_url, $title) {
   return '<a href="' . $the_url . '">' . $title . '</a>';
}

function page_table_body($display_list, $orders, $woo_api, $controls=TRUE){

    echo '<tbody>';
    foreach ($orders as $order) {
        // Load table data for order
        $order_id = $order->get_id();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_total = $order->get_total();
        if($display_list == "approved"){
            $order_date = wc_format_datetime($order->get_date_completed());
        } else {
            $order_date = wc_format_datetime($order->get_date_created());
        }
        $view_order_url = get_edit_post_link($order_id);
        $order_weight = $woo_api->getOrderWeight($order);
        $order_address = $order->get_shipping_address_1() . $order->get_shipping_address_2() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode();
        $shipping_method = $order->get_shipping_method();


        // Show order
        echo '<tr>';
        page_table_data($order_id);    
        page_table_data($order_date);
        page_table_data($customer_name);
        page_table_data($order_total);
        page_table_data($order_weight);
        page_table_data($order_address);
        page_table_data($shipping_method);

        if($controls){
            
            page_table_data(get_page_href($view_order_url, 'View Order'));

            page_table_data($woo_api->getMeta($order));
            
            if($display_list == "default"){
                page_table_data(get_page_href('?page=anteam-orders&list=' . $display_list . '&action=approve&order_id=' . $order_id, 'Approve'));
                page_table_data(get_page_href('?page=anteam-orders&list=' . $display_list . '&action=deny&order_id=' . $order_id, 'Deny'));
            } else {

                if($woo_api->isApproved($order)) {
                    page_table_data('Approved');
                } else {
                    page_table_data(get_page_href('?page=anteam-orders&&list=' . $display_list . 'action=approve&order_id=' . $order_id, 'Approve'));
                }

                if ($woo_api->isDenied($order)) {
                    page_table_data('Denied');
                } else {
                    page_table_data(get_page_href('?page=anteam-orders&&list=' . $display_list . 'action=deny&order_id=' . $order_id, 'Deny'));
                }

                if($woo_api->isApproved($order)) {
                    page_table_data('<a href="#" onclick="printzpl(\''.$customer_name.'\',\''.$order->get_shipping_address_1().'\',\''.$order->get_shipping_address_2().'\',\''.$order->get_shipping_city() . ', ' . $order->get_shipping_postcode().'\')">Print</a>');
                }  

                


            }
        }
        echo '</tr>';
    }

    echo '</tbody>';
}


function zplscript(){
    echo '
    
    <script>
   
  </script>
    ';
}


function print_list($display_list, $orders, $woo_api){

    echo '
    <script type="text/javascript">
    function popup() 
    {
        var mywindow = window.open(\'\', \'List\', \'height=400,width=600\');
        mywindow.document.write(\'<html><head><title>List</title>\');
        mywindow.document.write(\'</head><body >\');
        mywindow.document.write(\'<table>';

        // data
        echo page_table_head($display_list, FALSE);
        echo page_table_body($display_list, $orders, $woo_api, FALSE);


    
        echo '</table>\');
        mywindow.document.write(\'</body></html>\');

        mywindow.print();
        mywindow.close();

        return true;
    }

</script>

';

}



// Generate table
function load_orders_page() {
    styles();

    $Anteam_shipping_instance = new WC_Anteam_Shipping_method();

    $woo_api = new AnteamWooApi();    
    $anteam_api = new AnteamApi();
    $anteam_api->auth_token = $Anteam_shipping_instance->get_auth_token();

    controller($anteam_api, $woo_api, $Anteam_shipping_instance);

    $display_list = "default";
    if (isset($_GET['list'])) {
      $display_list = $_GET['list'];
    }

    $initial_date = date("Y-m-d", strtotime("-1 week"));
    if (isset($_GET['start_date']) ) {
        $initial_date = $_GET['start_date'];
    }

    $final_date = date("Y-m-d", strtotime("+1 day"));
    if (isset($_GET['end_date']) ) {
        $final_date = $_GET['end_date'];
    }

    $orders = get_orders($anteam_api, $woo_api, $display_list, $initial_date, $final_date);

    page_header($display_list);

    // action buttons
    // echo '<div style="display: inline-flex; gap: 10px;">';

    // echo '<form action="' . esc_url(plugins_url('print-orders.html', __FILE__)) . '" method="post" target="_blank">
    //     <input type="submit" name="printButton" value="Print orders">
    // </form>';

    // $last_action = $Anteam_shipping_instance->last_order;
    // echo '<form action="?page=anteam-orders&action=undo&order_id=' . $last_action . '" method="post">
    //     <input type="submit" name="undoButton" value="Undo">
    // </form>';

    // echo '<form action="?page=anteam-orders&action=clear&order_id=0" method="post">
    //     <input type="submit" name="clearButton" value="Clear Packing List">
    // </form>';

    // echo '</div>';


    page_controls($display_list, $initial_date, $final_date);
    print_list($display_list, $orders, $woo_api);
    zplscript();


    echo '<table class="wp-list-table widefat striped order-approval-table">';

    page_table_head($display_list);
    page_table_body($display_list, $orders, $woo_api);

    echo '</table>';
}
