<?php

use OpenBuildings\Monetary\Monetary;

/**
 * @group model
 * @group model.store_refund_item
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Store_Refund_ItemTest extends Testcase_Purchases {

	/**
	 * @covers Model_Store_Refund_Item::validate
	 * @covers Model_Store_Refund_Item::initialize
	 */
	public function test_validate()
	{
		$store_purchase = Jam::find('test_purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$item = $refund->items[0];

		$this->assertTrue($item->check());

		$item->amount = 400;
		$this->assertFalse($item->check());
		$this->assertArrayHasKey('amount', $item->errors()->messages());
	}

	/**
	 * @covers Model_Store_Refund_Item::amount
	 */
	public function test_amount()
	{
		$store_purchase = Jam::find('test_purchase', 1)->store_purchases[0];
		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0])
			)
		));

		$item = $refund->items[0];

		$this->assertEquals($store_purchase->items[0]->price(), $item->amount());

		$item->amount = 5;
		$this->assertEquals(5, $item->amount());

	}


}