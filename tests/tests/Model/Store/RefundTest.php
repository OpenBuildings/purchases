<?php

use OpenBuildings\Monetary\Monetary;

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
	 * @covers Model_Store_Refund::total_amount
	 */
	public function test_total_amount()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$this->assertEquals(200, $refund->total_amount()->amount());

		$refund->items[0]->amount = 10;

		$this->assertEquals(10, $refund->total_amount()->amount());
		$this->assertEquals('EUR', $refund->total_amount()->currency());

		$refund = $store_purchase->refunds->create(array());

		$this->assertEquals(400.0, $refund->total_amount()->amount());
		$this->assertEquals('EUR', $refund->total_amount()->currency());
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
}