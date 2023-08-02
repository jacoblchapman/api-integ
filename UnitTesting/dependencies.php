<?php

class Product {

    public $name;

    public $weight;

   

    function __construct($name, $weight) {

        $this->name = $name;

        $this->weight = $weight;

    }

    function get_weight() {

        return $this->weight;

    }

}

 

class Item {

    public $product;

    public $quantity;

   

    function __construct($product, $quantity) {

        $this->product = $product;

        $this->quantity = $quantity;

    }

   

    function get_product() {

        return $this->product;

    }

    function get_quantity() {

        return $this->quantity;

    }

}

 

class Order {

    public $id;

    public $status;

    public $shippingMethod;

    public $shippingAddress;

    public $shippingName;

    public $billingName;

    public $items;

 

    function __construct($id, $shippingMethod, $shippingAddress, $shippingName, $billingName, $items, $phone) {

        $this->id = $id;

        $this->status = 'processing';

        $this->shippingMethod = $shippingMethod;

        $this->shippingAddress = $shippingAddress;

        $this->shippingName = $shippingName;

        $this->billingName = $billingName;

        $this->items = $items;
        
        $this->shippingPhone = $phone;
        
        $this->billingPhone = $phone;

    }

 

    function get_shipping_method() {

        return $this->shippingMethod;

    }
    function get_id() {
    	return $this->id;
    }

 

    function get_status() {

        return $this->status;

    }

 

    function get_shipping_address_1() {

        $address_lines = explode(',', $this->shippingAddress);

        return $address_lines[0];

    }

 

    function get_shipping_address_2() {

        $address_lines = explode(',', $this->shippingAddress);

        return $address_lines[1];

    }

 

    function get_shipping_city() {

        $address_lines = explode(',', $this->shippingAddress);

        return $address_lines[2];

    }

 

    function get_shipping_postcode() {

        $address_lines = explode(',', $this->shippingAddress);

        return $address_lines[3];
    }

 

    function get_items() {

        return $this->items;

    }
    
    function get_shipping_first_name() {
    	$names = explode(' ', $this->shippingName);

        return $names[0];
    
    }
    function get_shipping_phone() {
    	return $this->shippingPhone;
    
    }
    function get_billing_phone() {
    	return $this->billingPhone;
    
    }

   

    function update_status($newStatus) {

        $this->status = $newStatus;

    }

}

 

class TestInterface {

        public $orders;

        public $weight_unit;

       

        function __construct($weight_unit) {

            $this->weight_unit = $weight_unit;

            $this->orders = array();

        }

        function add_order($order) {

            array_push($this->orders, $order);

        }

}

class WC_Anteam_Shipping_Method {
    private $enabled;
    private $auth_token;
    private $pickup_address;
    private $contact_name;
    private $contact_number;

    public function __construct() {
        $this->enabled = 'yes';
        $this->auth_token = '1418873350d912c488fc889b2429df834a9307b3';
        $this->pickup_address = "123 Fake Street, Line Two, City, Postcode";
        $this->contact_name = "FirstName SecondName";
        $this->contact_number = "12345 678 901";
    }

    // Getters
    public function get_enabled() {
        return $this->enabled;
    }
    public function set_enabled($val) {
    	$this->enabled = $val;
    }	

    public function get_auth_token() {
        return $this->auth_token;
    }

    public function get_pickup_address() {
        return $this->pickup_address;
    }

    public function get_owner_name() {
        return $this->contact_name;
    }

    public function get_owner_phone() {
        return $this->contact_number;
    }
}

class TestingInterface {
	public $orders;
	public $unit;
	
	function __construct($orders, $unit) {
		$this->orders = $orders;
		$this->unit = $unit;
	
	} 	
}

function get_option($option) {
	global $GlobalTestInstance;
	
	if($option == 'woocommerce_weight_unit') {
		return $GlobalTestInstance->unit;
	}
}


function wc_get_orders($params) {
	// has to take a parameter - doesn't use any w/ this implementation
	global $GlobalTestInstance;
	return $GlobalTestInstance->orders;
}

function get_post_meta($id, $property, $single) {
	// params not use - implement later
	return false;

}
    
