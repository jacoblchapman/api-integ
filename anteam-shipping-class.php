<?php

require_once( plugin_dir_path( __FILE__ ) . '../woocommerce/woocommerce.php' );
require_once( plugin_dir_path( __FILE__ ) . 'anteam-utilities.php' );

class WC_Anteam_Shipping_Method extends WC_Shipping_Method {

    public function __construct() {
        $this->id                 = 'anteam_shipping';
        $this->method_title       = __('Anteam Shipping', 'anteam_shipping');
        $this->method_description = __('Anteam shipping method used to ship parcels automatically', 'anteam_shipping');
        $this->title              = 'Anteam Shipping';
        $this->last_order = null;


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

    // calls every time the shipping address updates at checkout - calls 3 times for some reason?
    public function calculate_shipping($package = array()) {

        // check shipping option settings checkbox enabled
        if($this->get_option('enabled')=='yes') {

            // get total weight for basket contents
            $total_weight = 0;
            foreach ($package['contents'] as $item) {
                $product_weight = floatval(get_post_meta($item['product_id'], '_weight', true));
                $total_weight += $product_weight * $item['quantity'];
            }            
            // prepare json for request to Anteam server
            $data = array();
            $d = [
                // id of 1 is a placeholder - not relevant
                'id' => 1,
                'postcode' => $package['destination']['postcode'],
                'weight' => total_weight,
                'unit' => get_option('woocommerce_weight_unit'),
            ];
            array_push($data, $d);
            
            
            // prepare headers and send request
            $url = "https://api.anteam.co.uk/profiles/check_address/";
            $handle = curl_init($url);
            $encodedData = json_encode($data);
            $authToken = $this->get_auth_token();
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
            curl_setopt($handle, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: TOKEN ' . $authToken,
            ]);

            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($handle);

            $httpStatus = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    
            // ADD ERROR HANDLING FOR RESULT
            curl_close($handle);
                            
            // error handling from API
            if($httpStatus==200) {
                // Decode the response as a JSON object
                $json = json_decode($result);
                
                // load the order
                $orderChecked = $json[0];
                if($orderChecked->id == 1) {
                    if($orderChecked->accepted) {
                        // if postcode is valid, add shipping rate
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

        // check if pickup address has changed
        $old_address = $this->get_option('pickup_address');

        parent::process_admin_options();

        $new_address = $this->get_option('pickup_address');

    }
    
    // getters
    
    public function get_pickup_address() {
        return $this->get_option('pickup_address');
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
}





