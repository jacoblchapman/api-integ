<?php
require_once '../dependencies.php';
require_once '../anteam-utilities.php';

use PHPUnit\Framework\TestCase;

class OrderApproved_test extends TestCase {
	public $order;
	
	protected function setUp(): void {
		global $GlobalTestInstance;
		$GlobalTestInstance = new TestingInterface(array(),'kg');
		
		global $Anteam_shipping_instance;
		$Anteam_shipping_instance = new WC_Anteam_Shipping_Method();
	}
	
	public function testExtraSmall() {
		$testProduct = new Product("test", 0.4);
		$testItem = new Item($testProduct, 1);
		$this->order = new Order(1, 'free shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');		
		$this->assertTrue(orderApproved($this->order));
		
	}
	
	public function testSmall() {
		$testProduct = new Product("test", 1.9);
		$testItem = new Item($testProduct, 1);
		$this->order = new Order(1, 'free shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');		
		$this->assertTrue(orderApproved($this->order));
		
	}
	
	public function testMedium() {
		$testProduct = new Product("test", 4.9);
		$testItem = new Item($testProduct, 1);
		$this->order = new Order(1, 'free shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');		
		$this->assertTrue(orderApproved($this->order));
		
	}
	public function testLarge() {
		$testProduct = new Product("test", 9.9);
		$testItem = new Item($testProduct, 1);
		$this->order = new Order(1, 'free shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');		
		$this->assertTrue(orderApproved($this->order));
		
	}
	public function testExtraLarge() {
		$testProduct = new Product("test", 14.9);
		$testItem = new Item($testProduct, 1);
		$this->order = new Order(1, 'free shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');		
		$this->assertTrue(orderApproved($this->order));
		
	}
}
