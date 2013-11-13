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

	public $purchase;
	public $store_purchase;
	public $refund;

	public function setUp()
	{
		parent::setUp();

		$this->purchase = Jam::find('purchase', 1);
		$this->store_purchase = $this->purchase->store_purchases[0];
		$this->refund = $this->store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $this->store_purchase->items[0])
			)
		));
	}

	/**
	 * @covers Model_Store_Refund_Item::validate
	 */
	public function test_validate()
	{
		$item = $this->refund->items[0];

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
		$item = $this->refund->items[0];

		$this->assertEquals($this->store_purchase->items[0]->price(), $item->amount());

		$item->amount = 5;
		$this->assertEquals(new Jam_Price(5, 'EUR', $item->monetary(), 'EUR'), $item->amount());
	}

	/**
	 * @covers Model_Store_Refund_Item::currency
	 */
	public function test_currency()
	{
		$store_refund = $this->getMock('Model_Store_Refund', array('currency'), array('store_refund'));
		$store_refund
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$item = Jam::build('store_refund_item', array('store_refund' => $store_refund));

		$this->assertEquals('GBP', $item->currency());
		$this->assertEquals('EUR', $item->currency());
	}

	/**
	 * @covers Model_Store_Refund_Item::display_currency
	 */
	public function test_display_currency()
	{
		$store_refund = $this->getMock('Model_Store_Refund', array('display_currency'), array('store_refund'));
		$store_refund
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$item = Jam::build('store_refund_item', array('store_refund' => $store_refund));

		$this->assertEquals('GBP', $item->display_currency());
		$this->assertEquals('EUR', $item->display_currency());
	}

	/**
	 * @covers Model_Store_Refund_Item::is_full_amount
	 */
	public function test_is_full_amount()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$purchase_item = $this->getMock('Model_Purchase_Item', array('price'), array('purchase_item'));
		$purchase_item
			->expects($this->exactly(2))
				->method('price')
				->will($this->returnValue(new Jam_Price(10, 'GBP')));

		$item = $this->getMock('Model_Store_Refund_Item', array('currency', 'display_currency', 'monetary'), array('store_refund_item'));
		$item
			->expects($this->any())
				->method('currency')
				->will($this->returnValue('GBP'));

		$item
			->expects($this->any())
				->method('display_currency')
				->will($this->returnValue('GBP'));

		$item
			->expects($this->any())
				->method('monetary')
				->will($this->returnValue($monetary));

		$item->purchase_item = $purchase_item;

		$this->assertTrue($item->is_full_amount());

		$item->amount = 5;

		$this->assertFalse($item->is_full_amount());

		$item->amount = 10;

		$this->assertTrue($item->is_full_amount());
	}

	/**
	 * @covers Model_Store_Refund_Item::monetary
	 */
	public function test_monetary()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$store_refund = $this->getMock('Model_Store_Refund', array('monetary'), array('store_refund'));
		$store_refund
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$item = Jam::build('store_refund_item', array('store_refund' => $store_refund));

		$this->assertSame($monetary, $item->monetary());
	}
}