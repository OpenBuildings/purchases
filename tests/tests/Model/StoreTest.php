<?php

/**
 * Functest_TestsTest 
 *
 * @group model.purchase
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_StoreTest extends Testcase_Purchases {

	/**
	 * @covers Model_Store::purchases_total_price
	 */
	public function test_purchases_total_price()
	{
		$price1 = new Jam_Price(10, 'GBP');
		$price2 = new Jam_Price(32, 'GBP');

		$types = array('product');

		$purchase1 = $this->getMock('Model_Purchase', array('total_price'), array('purchase'));
		$purchase1
			->expects($this->once())
			->method('total_price')
			->with($this->equalTo($types))
			->will($this->returnValue($price1));

		$purchase2 = $this->getMock('Model_Purchase', array('total_price'), array('purchase'));
		$purchase2
			->expects($this->once())
			->method('total_price')
			->with($this->equalTo($types))
			->will($this->returnValue($price2));

		$store = Jam::build('store', array('purchases' => array($purchase1,	$purchase2)));

		$this->assertEquals(new Jam_Price(42, 'GBP'), $store->purchases_total_price($types));
	}
}