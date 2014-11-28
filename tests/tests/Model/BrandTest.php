<?php

/**
 * @group model.brand
 */
class Model_BrandTest extends Testcase_Purchases {

	/**
	 * @covers Model_Brand::purchases_total_price
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

		$brand = Jam::build('brand', array('currency' => 'GBP', 'purchases' => array($purchase1,	$purchase2)));

		$this->assertEquals(new Jam_Price(42, 'GBP'), $brand->purchases_total_price($types));
	}
}
