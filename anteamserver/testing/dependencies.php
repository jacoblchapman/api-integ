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

	function __construct($id, $shippingMethod, $shippingAddress, $shippingName, $billingName, $items) {
		$this->id = $id;
		$this->status = 'processing';
		$this->shippingMethod = $shippingMethod;
		$this->shippingAddress = $shippingAddress;
		$this->shippingName = $shippingName;
		$this->billingName = $billingName;
		$this->items = $items;
	}

	function get_shipping_method() {
		return $this->shippingMethod;
	}

	function get_status() {
		return $this->status;
	}

	function get_shipping_address_1() {
		$address_lines = explode(',', $this->shippingAddress);
		return isset($address_lines[0]) ? trim($address_lines[0]) : '';
	}

	function get_shipping_address_2() {
		$address_lines = explode(',', $this->shippingAddress);
		return isset($address_lines[1]) ? trim($address_lines[1]) : '';
	}

	function get_shipping_city() {
		$address_lines = explode(',', $this->shippingAddress);
		return isset($address_lines[2]) ? trim($address_lines[2]) : '';
	}

	function get_shipping_postcode() {
		$address_lines = explode(',', $this->shippingAddress);
		return isset($address_lines[3]) ? trim($address_lines[3]) : '';
	}

	function get_items() {
		return $this->items;
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

$testProduct = new Product("test", 1);
$testItem = new Item($testProduct, 3);
$testItem2 = new Item($testProduct, 8);
$testOrder = new Order(1, 'free shipping', '1 Great Ash, Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred Moore', array($testItem));
$testOrder2 = new Order(2, 'Anteam shipping', '23 Garden Road, Bromley, BR1 3LU', 'Freddie Moore', 'F Moore', array($testItem2));
$test_interface = new TestInterface("kg");
$test_interface->add_order($testOrder);
$test_interface->add_order($testOrder2);

foreach($test_interface->orders as $order) {
	echo(getOrderWeight($order) . ' ');
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

