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
	}

	/**
	 * @covers Model_Purchase_Item_Product::get_price
	 */
	public function test_get_price()
	{
		$mock = $this->getMock('stdClass', array(
			'price_for_purchase_item'
		));

		$purchase_item = $this->getMock('Model_Purchase_Item_Product', array(
			'get_reference_paranoid'
		), array(
			'purchase_item_product'
		));

		$purchase_item
			->expects($this->once())
			->method('get_reference_paranoid')
			->will($this->returnValue($mock));

		$mock
			->expects($this->once())
			->method('price_for_purchase_item')
			->with($purchase_item)
			->will($this->returnValue(10.25));

		$this->assertSame(10.25, $purchase_item->get_price());
	}
}
