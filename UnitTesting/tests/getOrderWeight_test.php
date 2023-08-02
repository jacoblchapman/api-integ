<?php
require_once '../dependencies.php';
require_once '../anteam-utilities.php';

use PHPUnit\Framework\TestCase;

class getOrderWeight_test extends TestCase {
	public $order;
	
	protected function setUp(): void {
		$testProduct = new Product("test", 1);
		$testItem = new Item($testProduct, 3);
		$testItem2 = new Item($testProduct, 8);
		$this->order = new Order(1, 'free shipping', '1 Great Ash, 			Lubbock Road, Bromley, BR1 3LU', 'Freddie Moore', 'Fred 			Moore', array($testItem), '12345 678 901');

// $testOrder2 = new Order(2, 'Anteam shipping', '23 Garden Road, Bromley, BR1 3LU', 'Freddie Moore', 'F Moore', array($testItem2));
	}
	public function testOne() {
		$res = getOrderWeight($this->order);
		$this->assertEquals($res, 3);
	}
}
?>
