<?php
require_once '../dependencies.php';
require_once '../anteam-utilities.php';

use PHPUnit\Framework\TestCase;

class fetchOrders_test extends TestCase {
	protected function setUp(): void {
		global $Anteam_shipping_instance;
		$Anteam_shipping_instance = new WC_Anteam_Shipping_Method();
	}
	public function testOne() {
		global $Anteam_shipping_instance;
		$Anteam_shipping_instance->set_enabled(true);
		
		$testProduct = new Product("test", 1);
		$testItem = new Item($testProduct, 3);
		$order1 = new Order(1, 'Anteam Shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');
		
		$order2 = new Order(2, 'Free Shipping', '1 Great Ash, Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred Moore', array($testItem), '12345 678 901');
		
		$orders = array($order1, $order2);
		
		global $GlobalTestInstance;
		// unit can be any
		$GlobalTestInstance = new TestingInterface($orders, 'kg');
		$this->assertEquals(fetchOrders(),array($order1));
	}
	public function testTwo() {
	
		global $Anteam_shipping_instance;
		$Anteam_shipping_instance->set_enabled(false);
		
		$testProduct = new Product("test", 1);
		$testItem = new Item($testProduct, 3);
		$order1 = new Order(1, 'Anteam Shipping', '1 Great Ash, 			Lubbock Road, Bromley, NG11 4RU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');
		
		$order2 = new Order(2, 'Free Shipping', '1 Great Ash, 		Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');
		
		$orders = array($order1, $order2);
		
		global $GlobalTestInstance;
		// unit can be any
		$GlobalTestInstance = new TestingInterface($orders, 'kg');
		$this->assertEquals(fetchOrders(),array($order1));
	}
	
}
?>
