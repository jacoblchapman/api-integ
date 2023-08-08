<?php
require_once '../dependencies.php';
require_once '../anteam-utilities.php';

use PHPUnit\Framework\TestCase;

class getOrderWeight_test extends TestCase {
	public function testKG() {
		global $GlobalTestInstance;
		
		$GlobalTestInstance = new TestingInterface(array(), 'kg');
		$res = getWeightMultiplier();
		$this->assertequals(1, $res);
	}
	
	public function testG() {
		global $GlobalTestInstance;
		
		$GlobalTestInstance = new TestingInterface(array(), 'g');
		$res = getWeightMultiplier();
		$this->assertequals(1000, $res);
	}
	
	public function testOZ() {
		global $GlobalTestInstance;
		
		$GlobalTestInstance = new TestingInterface(array(), 'oz');
		$res = getWeightMultiplier();
		$this->assertequals(35.273962, $res);
	}
	
	public function testLB() {
		global $GlobalTestInstance;
		
		$GlobalTestInstance = new TestingInterface(array(), 'lbs');
		$res = getWeightMultiplier();
		$this->assertequals(2.20462262, $res);
	}
}
?>
