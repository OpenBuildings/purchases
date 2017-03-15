<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @group model
 * @group model.purchase_item_product
 */
class Model_Purchase_Item_ProductTest extends Testcase_Purchases {

	/**
	 * @covers Model_Purchase_Item_Product::initialize
	 */
	public function test_initialize()
	{
		$meta = Jam::meta('purchase_item_product');
		$this->assertSame('purchase_items', $meta->table());
		$this->assertTrue($meta->field('is_payable')->default);
	}

	/**
	 * @covers Model_Purchase_Item_Product::get_price
	 */
	public function test_get_price()
	{
		$mock = $this->getMockBuilder('stdClass')
			->setMethods(array('price_for_purchase_item'))
			->getMock();

		$purchase_item = $this->getMockBuilder('Model_Purchase_Item_Product')
			->setMethods(array('get_reference_paranoid'))
			->setConstructorArgs(array('purchase_item_product'))
			->getMock();

		$purchase_item
			->expects($this->exactly(2))
			->method('get_reference_paranoid')
			->will($this->onConsecutiveCalls($mock, NULL));

		$mock
			->expects($this->once())
			->method('price_for_purchase_item')
			->with($purchase_item)
			->will($this->returnValue(new Jam_Price(10.25, 'GBP')));

		$this->assertEquals(new Jam_Price(10.25, 'GBP'), $purchase_item->get_price());
		$this->assertEquals(new Jam_Price(0, 'GBP'), $purchase_item->get_price());
	}
}
