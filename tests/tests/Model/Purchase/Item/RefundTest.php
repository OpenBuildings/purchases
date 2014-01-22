<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @group model
 * @group model.purchase_item_refund
 */
class Model_Purchase_Item_RefundTest extends Testcase_Purchases {

	/**
	 * @covers Model_Purchase_Item_Refund::initialize
	 */
	public function test_initialize()
	{
		$meta = Jam::meta('purchase_item_refund');
		$this->assertSame('purchase_items', $meta->table());
		$this->assertFalse($meta->field('is_payable')->default);
		$this->assertTrue($meta->field('is_discount')->default);
	}

	/**
	 * @covers Model_Purchase_Item_Refund::get_price
	 */
	public function test_get_price()
	{
		$mock = $this->getMock('stdClass', array(
			'total_amount'
		));

		$purchase_item = $this->getMock('Model_Purchase_Item_Refund', array(
			'get_reference_paranoid'
		), array(
			'purchase_item_refund'
		));

		$purchase_item
			->expects($this->once())
			->method('get_reference_paranoid')
			->will($this->returnValue($mock));

		$refund_amount = new Jam_Price(10.25, 'GBP');
		$purchase_item_refund_price = $refund_amount->multiply_by(-1);
		$mock
			->expects($this->once())
			->method('total_amount')
			->will($this->returnValue($refund_amount));

		$this->assertEquals($purchase_item_refund_price, $purchase_item->get_price());
	}
}
