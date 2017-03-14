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
		$this->assertTrue($meta->field('is_payable')->default);
		$this->assertTrue($meta->field('is_discount')->default);
	}

	/**
	 * @covers Model_Purchase_Item_Refund::get_price
	 */
	public function test_get_price()
	{
		$mock = $this->getMockBuilder('stdClass')
			->setMethods(array('amount'))
			->getMock();

		$purchase_item = $this->getMockBuilder('Model_Purchase_Item_Refund')
			->setMethods(array('get_reference_paranoid'))
			->setConstructorArgs(array('purchase_item_refund'))
			->getMock();

		$purchase_item
			->expects($this->exactly(2))
			->method('get_reference_paranoid')
			->will($this->onConsecutiveCalls($mock, NULL));

		$refund_amount = new Jam_Price(10.25, 'GBP');
		$purchase_item_refund_price = $refund_amount->multiply_by(-1);
		$mock
			->expects($this->once())
			->method('amount')
			->will($this->returnValue($refund_amount));

		$this->assertEquals($purchase_item_refund_price, $purchase_item->get_price());
		$this->assertEquals(new Jam_Price(0, 'GBP'), $purchase_item->get_price());
	}
}
