<?php

use OpenBuildings\Monetary\Monetary;
use Omnipay\Omnipay;

/**
 * @group model
 * @group model.brand_refund
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Brand_RefundTest extends Testcase_Purchases {

	/**
	 * @covers Model_Brand_Refund::validate
	 */
	public function test_validate()
	{
		$brand_purchase = Jam::find('purchase', 1)->brand_purchases[0];
		$refund = $brand_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0])
			)
		));

		$this->assertTrue($refund->check());
		$refund->items[0]->amount = 1000;

		$this->assertFalse($refund->check());
		$this->assertArrayHasKey('items', $refund->errors()->messages());
	}

	/**
	 * @covers Model_Brand_Refund::amount
	 */
	public function test_amount()
	{
		$brand_purchase = Jam::find('purchase', 1)->brand_purchases[0];
		$refund = $brand_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0])
			)
		));

		$this->assertEquals(200, $refund->amount()->amount());

		$refund->amount = 10;

		$this->assertEquals(10, $refund->amount()->amount());
		$this->assertEquals('EUR', $refund->amount()->currency());

		$refund = $brand_purchase->refunds->create(array());

		$this->assertEquals(600.0, $refund->amount()->amount());
		$this->assertEquals('EUR', $refund->amount()->currency());
	}

	/**
	 * @covers Model_Brand_Refund::brand_purchase_insist
	 * @expectedException Kohana_Exception
	 */
	public function test_brand_purchase_insist()
	{
		$brand_purchase = Jam::find_insist('brand_purchase', 1);
		$refund = $brand_purchase->refunds->build(array(
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0])
			),
		));

		$this->assertInstanceOf(
			'Model_Brand_Purchase',
			$refund->brand_purchase_insist()
		);

		$refund->brand_purchase = NULL;
		$refund->brand_purchase_insist();
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Model_Brand_Refund::purchase_insist
	 */
	public function test_purchase_insist()
	{
		$brand_purchase = Jam::find('purchase', 1)->brand_purchases[0];
		$refund = $brand_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0])
			)
		));

		$this->assertInstanceOf('Model_Purchase', $refund->purchase_insist());

		$refund->brand_purchase->purchase = NULL;
		$refund->purchase_insist();
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Model_Brand_Refund::payment_insist
	 */
	public function test_payment_insist()
	{
		$brand_purchase = Jam::find('purchase', 1)->brand_purchases[0];
		$refund = $brand_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0])
			)
		));

		$this->assertInstanceOf('Model_Payment', $refund->payment_insist());

		$refund->brand_purchase->purchase->payment = NULL;
		$refund->payment_insist();
	}

	/**
	 * @covers Model_Brand_Refund::currency
	 */
	public function test_currency()
	{
		$brand_purchase = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('currency'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();
		$brand_purchase
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$brand_refund = Jam::build('brand_refund', array('brand_purchase' => $brand_purchase));

		$this->assertEquals('GBP', $brand_refund->currency());
		$this->assertEquals('EUR', $brand_refund->currency());
	}

	/**
	 * @covers Model_Brand_Refund::display_currency
	 */
	public function test_display_currency()
	{
		$brand_purchase = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('display_currency'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();
		$brand_purchase
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$brand_refund = Jam::build('brand_refund', array('brand_purchase' => $brand_purchase));

		$this->assertEquals('GBP', $brand_refund->display_currency());
		$this->assertEquals('EUR', $brand_refund->display_currency());
	}

	/**
	 * @covers Model_Brand_Refund::monetary
	 */
	public function test_monetary()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$brand_purchase = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('monetary'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();
		$brand_purchase
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$brand_refund = Jam::build('brand_refund', array('brand_purchase' => $brand_purchase));

		$this->assertSame($monetary, $brand_refund->monetary());
	}

	/**
	 * @covers Model_Brand_Refund::has_purchase_item
	 */
	public function test_has_purchase_item()
	{
		$item = $this->getMockBuilder('Model_Brand_Refund_Item')
			->setMethods(array('is_full_amount'))
			->setConstructorArgs(array('brand_refund_item'))
			->getMock();
		$item
			->expects($this->exactly(2))
				->method('is_full_amount')
				->will($this->onConsecutiveCalls(TRUE, FALSE));

		$item->purchase_item_id = 1;

		$purchase_item1 = Jam::find('purchase_item', 1);
		$purchase_item2 = Jam::find('purchase_item', 2);

		$refund = Jam::build('brand_refund', array(
			'items' => array($item)
		));

		$this->assertFalse($refund->has_purchase_item($purchase_item2));
		$this->assertTrue($refund->has_purchase_item($purchase_item1));
		$this->assertFalse($refund->has_purchase_item($purchase_item1));
	}

	/**
	 * @covers Model_Brand_Refund::execute
	 */
	public function test_execute()
	{
		$gateway = Omnipay::create('Dummy');
		$refund_params = array();

		$brand_refund = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array(
				'check',
				'payment_insist',
			))
			->setConstructorArgs(array('brand_refund'))
			->getMock();

		$payment_mock = $this->getMockBuilder('stdClass')
			->setMethods(array(
				'refund'
			))
			->getMock();

		$payment_mock->status = Model_Payment::PAID;

		$payment_mock
			->expects($this->once())
			->method('refund')
			->with($gateway, $brand_refund, $refund_params);

		$brand_refund
			->expects($this->once())
			->method('check');

		$brand_refund
			->expects($this->once())
			->method('payment_insist')
			->will($this->returnValue($payment_mock));

		$this->assertSame($brand_refund, $brand_refund->execute($gateway, $refund_params));
	}

	/**
	 * @covers Model_Brand_Refund::add_purchase_item_refund
	 */
	public function test_add_purchase_item_refund()
	{
		$brand_purchase = Jam::find_insist('brand_purchase', 1);
		$refund = $brand_purchase->refunds->build(array(
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0])
			),
		));

		$this->assertSame(0, $brand_purchase->items_count('refund'));

		$refund->add_purchase_item_refund();

		$brand_purchase->revert();

		$this->assertSame(1, $brand_purchase->items_count('refund'));
		$this->assertEquals(
			$refund->amount()->multiply_by(-1),
			$brand_purchase->total_price('refund')
		);
	}
}
