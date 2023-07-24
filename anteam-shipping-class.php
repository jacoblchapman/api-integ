<?php

require_once( plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php' );
require_once( plugin_dir_path( __FILE__ ) . 'anteam-utilities.php' );

class WC_Anteam_Shipping_Method extends WC_Shipping_Method {

    public function __construct() {
        $this->id                 = 'anteam_shipping';
        $this->method_title       = __('Anteam Shipping', 'anteam_shipping');
        $this->method_description = __('Anteam shipping method used to ship parcels automatically', 'anteam_shipping');
        $this->title              = 'Anteam Shipping';
        $this->lat = 0;
        $this->lon = 0;


        $this->availability = 'including';
        $this->countries    = array(
            'GB', 
        );
        
        $this->init();
    }

    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }


    // plugin settings fields
    public function init_form_fields() {
        
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable', 'anteam_shipping'),
                'type'    => 'checkbox',
                'label'   => 'Enable Anteam Shipping Option at Checkout',
                'description'   => __('When this checkbox is enabled, users will be able to choose Anteam as a shipping method at checkout. All orders shown in the orders table will be ones where the customer chose Anteam shipping. If this box is disabled, all orders in the orders table will be orders eligible to ship with Anteam.', 'anteam_shipping'),
                'default' => 'no',
            ),
        'auth_token' => array(
                'title'   => __('Authorisation Token', 'anteam_shipping'),
                'type'    => 'text',
                'label'   => __('Enter authorisation token', 'anteam_shipping'),
                'default' => '',
            ),
        'pickup_address' => array(
                'title'   => __('Pickup Address', 'anteam_shipping'),
                'type'    => 'text',
                'label'   => __('Enter pickup address', 'anteam_shipping'),
                'default' => WC()->countries->get_base_address() . ', ' . get_option('woocommerce_store_city') . ', ' . get_option('woocommerce_store_postcode'),
            ),
        'pickup_latitude' => array(
                'title'   => __('Pickup Address Latitude', 'anteam_shipping'),
                'type'    => 'decimal',
                'label'   => __('Enter pickup address longitude', 'anteam_shipping'),
                'default' => '',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                ),
                'description' => __('Current Longitude: ' . $this->get_pickup_latitude(), 'anteam_shipping'),
            ),
        'pickup_longitude' => array(
                'title'   => __('Pickup Address Longitude', 'anteam_shipping'),
                'type'    => 'decimal',
                'label'   => __('Enter pickup address longitude', 'anteam_shipping'),
                'default' => '',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                ),
                'description' => __('Current Longitude: ' . $this->get_pickup_longitude(), 'anteam_shipping'),
            ),
        'contact_name' => array(
                'title'   => __('Contact Name', 'anteam_shipping'),
                'type'    => 'text',
                'label'   => __('Enter contact name', 'anteam_shipping'),
                'default' => 'smith',
            ),
        'contact_number' => array(
                'title'   => __('Contact Number', 'anteam_shipping'),
                'type'    => 'text',
                'label'   => __('Enter contact number', 'anteam_shipping'),
                'default' => '00000000000',
            ),
        );
    }

    // it calls every time a basket updates
    // but it calls the func 3 times?
    public function calculate_shipping($package = array()) {
        error_log("calc shipping called");
        // get total weight for basket contents
        $total_weight = 0;
        foreach ($package['contents'] as $item) {
            $product_weight = floatval(get_post_meta($item['product_id'], '_weight', true));
            $total_weight += $product_weight * $item['quantity'];
        }
        error_log("Debug0");
        // only offer shipping if option is enabled, weight <= 15kg and shipping address is within 8 miles
        if($total_weight <= (15 * getWeightMultiplier())) {
            error_log("Debug1");
            $latlon = fetchLatLon($package['destination']['address'] . ',' . $package['destination']['address_2'] . ',' . $package['destination']['city']);
            error_log("Debug2");
            if($latlon != null) {
    
                $distance = haversine($this->get_pickup_latitude(), $this->get_pickup_longitude(), $latlon[0], $latlon[1]);
                error_log('LOOK HERE ->' . $distance);
                
                
                if($this->get_option('enabled')=='yes') {
                        if($distance <= 12.87) {
                            $rate = array(
                                'id'        => $this->id,
                                'label'     => $this->title,
                                'cost'      => 3,
                                'calc_tax'  => 'per_item',
                            );
                            $this->add_rate($rate);
                        }
                }
            }
        }
    }
    
    // hook to run every time settings are updated
    public function process_admin_options() {

        $old_address = $this->get_option('pickup_address');

        parent::process_admin_options();

        $new_address = $this->get_option('pickup_address');

        // if pickup address changed , alter all orders that have the Anteam shipping option to now use free shipping (as cannot calculate if still within radius, or do we recalculate for all orders??)
        if ($new_address !== $old_address) {
            $orders = wc_get_orders(array(
                'status' => 'processing',
                'limit' => -1,
                ));
                
            foreach ($orders as $order) {
            
            // select only orders with Anteam shipping method
            $shipping_method = $order->get_shipping_method();
            if ($shipping_method == 'Anteam Shipping') {
                $free_shipping_method_id = 'free_shipping:1';
                $shipping_items = $order->get_items('shipping');
                
                update_post_meta($order->get_id(), 'anteam_denied', 'true');
            
                foreach ($shipping_items as $shipping_item) {
                    $shipping_item->set_method_title($free_shipping_method_id);
                    $shipping_item->save();
                }
            
                $order->save();
            }
            }
        }

        $latlon = fetchLatLon($this->get_pickup_address());

        update_option('wc_anteam_shipping_pickup_latitude', $latlon[0]);
        update_option('wc_anteam_shipping_pickup_longitude', $latlon[1]);

    }
    
    // getters
     public function get_pickup_address() {
        return $this->get_option('pickup_address');
    }
    
    public function get_pickup_latitude() {
        return get_option('wc_anteam_shipping_pickup_latitude');
    }
    
    public function get_pickup_longitude() {
        return get_option('wc_anteam_shipping_pickup_longitude');
    }
    public function get_owner_name() {
        return $this->get_option('contact_name');
    }
    
    public function get_owner_phone() {
        return $this->get_option('contact_number');
    }
    
    public function get_auth_token() {
        return $this->get_option('auth_token');
    }
    
    public function get_enabled() {
        return ($this->get_option('enabled') == 'yes');
    }
    
    public function get_this_lat() {
        return ($this->lat);
    }
}





