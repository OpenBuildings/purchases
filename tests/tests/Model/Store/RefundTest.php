<?php

use OpenBuildings\Monetary\Monetary;
use Omnipay\Omnipay;

/**
 * @group model
 * @group model.store_refund
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Store_RefundTest extends Testcase_Purchases {

	/**
	 * @covers Model_Store_Refund::validate
	 */
	public function test_validate()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$this->assertTrue($refund->check());
		$refund->items[0]->amount = 1000;

		$this->assertFalse($refund->check());
		$this->assertArrayHasKey('items', $refund->errors()->messages());
	}

	/**
	 * @covers Model_Store_Refund::amount
	 */
	public function test_amount()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$this->assertEquals(200, $refund->amount()->amount());

		$refund->amount = 10;

		$this->assertEquals(10, $refund->amount()->amount());
		$this->assertEquals('EUR', $refund->amount()->currency());

		$refund = $store_purchase->refunds->create(array());

		$this->assertEquals(400.0, $refund->amount()->amount());
		$this->assertEquals('EUR', $refund->amount()->currency());
	}

	/**
	 * @covers Model_Store_Refund::store_purchase_insist
	 * @expectedException Kohana_Exception
	 */
	public function test_store_purchase_insist()
	{
		$store_purchase = Jam::find_insist('store_purchase', 1);
		$refund = $store_purchase->refunds->build(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			),
		));

		$this->assertInstanceOf(
			'Model_Store_Purchase',
			$refund->store_purchase_insist()
		);

		$refund->store_purchase = NULL;
		$refund->store_purchase_insist();
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Model_Store_Refund::purchase_insist
	 */
	public function test_purchase_insist()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$this->assertInstanceOf('Model_Purchase', $refund->purchase_insist());

		$refund->store_purchase->purchase = NULL;
		$refund->purchase_insist();
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Model_Store_Refund::payment_insist
	 */
	public function test_payment_insist()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$this->assertInstanceOf('Model_Payment', $refund->payment_insist());

		$refund->store_purchase->purchase->payment = NULL;
		$refund->payment_insist();
	}

	/**
	 * @covers Model_Store_Refund::currency
	 */
	public function test_currency()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('currency'), array('store_purchase'));
		$store_purchase
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$store_refund = Jam::build('store_refund', array('store_purchase' => $store_purchase));

		$this->assertEquals('GBP', $store_refund->currency());
		$this->assertEquals('EUR', $store_refund->currency());
	}

	/**
	 * @covers Model_Store_Refund::display_currency
	 */
	public function test_display_currency()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('display_currency'), array('store_purchase'));
		$store_purchase
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$store_refund = Jam::build('store_refund', array('store_purchase' => $store_purchase));

		$this->assertEquals('GBP', $store_refund->display_currency());
		$this->assertEquals('EUR', $store_refund->display_currency());
	}

	/**
	 * @covers Model_Store_Refund::monetary
	 */
	public function test_monetary()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$store_purchase = $this->getMock('Model_Store_Purchase', array('monetary'), array('store_purchase'));
		$store_purchase
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$store_refund = Jam::build('store_refund', array('store_purchase' => $store_purchase));

		$this->assertSame($monetary, $store_refund->monetary());
	}

	/**
	 * @covers Model_Store_Refund::has_purchase_item
	 */
	public function test_has_purchase_item()
	{
		$item = $this->getMock('Model_Store_Refund_Item', array('is_full_amount'), array('store_refund_item'));
		$item
			->expects($this->exactly(2))
				->method('is_full_amount')
				->will($this->onConsecutiveCalls(TRUE, FALSE));

		$item->purchase_item_id = 1;

		$purchase_item1 = Jam::find('purchase_item', 1);
		$purchase_item2 = Jam::find('purchase_item', 2);

		$refund = Jam::build('store_refund', array(
			'items' => array($item)
		));

		$this->assertFalse($refund->has_purchase_item($purchase_item2));
		$this->assertTrue($refund->has_purchase_item($purchase_item1));
		$this->assertFalse($refund->has_purchase_item($purchase_item1));
	}

	/**
	 * @covers Model_Store_Refund::execute
	 */
	public function test_execute()
	{
		$gateway = Omnipay::create('Dummy');
		$refund_params = array();

		$store_refund = $this->getMock('Model_Store_Refund', array(
			'check_insist',
			'payment_insist',
		), array(
			'store_refund'
		));

		$payment_mock = $this->getMock('stdClass', array(
			'refund'
		));

		$payment_mock->status = Model_Payment::PAID;

		$payment_mock
			->expects($this->once())
			->method('refund')
			->with($gateway, $store_refund, $refund_params);

		$store_refund
			->expects($this->once())
			->method('check_insist');

		$store_refund
			->expects($this->once())
			->method('payment_insist')
			->will($this->returnValue($payment_mock));

		$this->assertSame($store_refund, $store_refund->execute($gateway, $refund_params));
	}

	/**
	 * @covers Model_Store_Refund::add_purchase_item_refund
	 */
	public function test_add_purchase_item_refund()
	{
		$store_purchase = Jam::find_insist('store_purchase', 1);
		$refund = $store_purchase->refunds->build(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			),
		));

		$this->assertSame(0, $store_purchase->items_count('refund'));

		$refund->add_purchase_item_refund();

		$store_purchase->revert();

		$this->assertSame(1, $store_purchase->items_count('refund'));
		$this->assertEquals(
			$refund->amount()->multiply_by(-1),
			$store_purchase->total_price('refund')
		);
	}
}
