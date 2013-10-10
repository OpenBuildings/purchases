<?php

use OpenBuildings\Monetary\Monetary;

/**
 * @group model
 * @group model.purchase_item
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Purchase_ItemTest extends Testcase_Purchases {

	/**
	 * @covers Model_Purchase_Item::validate
	 */
	public function test_validate()
	{
		$item = Jam::find('purchase_item', 1);
		$item->reference = Jam::find('user', 1);

		$this->assertFalse($item->check());
		$this->assertArrayHasKey('reference', $item->errors()->messages());

		$item = Jam::build('purchase_item', array(
			'price' => 10, 
			'store_purchase' => 1,
			'type' => 'product', 
			'quantity' => 1,
			'is_payable' => TRUE
		));

		$item->price = -100;
		$this->assertFalse($item->check());
		$this->assertArrayHasKey('price', $item->errors()->messages());

		$item->price = 10;
		$this->assertTrue($item->check());

		$item->is_discount = TRUE;
		$this->assertFalse($item->check());
		$this->assertArrayHasKey('price', $item->errors()->messages());

		$item->price = -100;
		$this->assertTrue($item->check());
	}

	/**
	 * @covers Model_Purchase_Item::is_same
	 */
	public function test_is_same()
	{
		$item = Jam::find('purchase_item', 1);

		$new_item = Jam::build('purchase_item', array(
			'reference' => Jam::find('product', 1),
			'quantity' => 3,
			'type' => 'product',
		));

		$this->assertTrue($item->is_same($new_item));

		$new_item->type = 'shipping';

		$this->assertFalse($item->is_same($new_item));
	}

	/**
	 * @covers Model_Purchase_Item::group_by_store_purchase
	 */
	public function test_group_by_store_purchase()
	{
		$items = array(
			Jam::build('purchase_item', array('id' => 10, 'store_purchase_id' => 1)),
			Jam::build('purchase_item', array('id' => 20, 'store_purchase_id' => 1)),
			Jam::build('purchase_item', array('id' => 30, 'store_purchase_id' => 12)),
			array('id' => 40, 'store_purchase_id' => 13),
			array('id' => 50, 'store_purchase_id' => 13),
			1,
			array('id' => 2),
		);

		$groups = Model_Purchase_Item::group_by_store_purchase($items);

		$this->assertEquals(array('1', '12', '13'), array_keys($groups));

		$this->assertEquals(array(10, 20, 1, 2), $this->ids($groups['1']));
		$this->assertEquals(array(30), $this->ids($groups['12']));
		$this->assertEquals(array(40, 50), $this->ids($groups['13']));
	}

	/**
	 * @covers Model_Purchase_Item::compute_price
	 */
	public function test_compute_price()
	{
		$item = Jam::find('purchase_item', 1);
		$item->reference = $this->getMock('Model_Product', array('price_for_purchase_item'), array('product'));

		$price1 = new Jam_Price(10, 'EUR');
		$price2 = new Jam_Price(10, 'USD');

		$item->reference->expects($this->exactly(2))
			->method('price_for_purchase_item')
			->with($this->identicalTo($item))
			->will($this->onConsecutiveCalls($price1, $price2));

		$this->assertEquals(new Jam_Price(10, 'EUR', $item->monetary()), $item->compute_price(), 'Should be EUR -> EUR conversion');
		$this->assertEquals(new Jam_Price(7.4867110878191, 'EUR', $item->monetary()), $item->compute_price(), 'Should be EUR -> USD conversion');
	}

	/**
	 * @covers Model_Purchase_Item::monetary
	 */
	public function test_monetary()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$store_purchase = $this->getMock('Model_Store_Purchase', array('monetary'), array('store_purchase'));
		$store_purchase
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$purchase_item = Jam::build('purchase_item', array('store_purchase' => $store_purchase));

		$this->assertSame($monetary, $purchase_item->monetary());
	}

	/**
	 * @covers Model_Purchase_Item::currency
	 */
	public function test_currency()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('currency'), array('store_purchase'));
		$store_purchase
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$purchase_item = Jam::build('purchase_item', array('store_purchase' => $store_purchase));

		$this->assertEquals('GBP', $purchase_item->currency());
		$this->assertEquals('EUR', $purchase_item->currency());
	}

	/**
	 * @covers Model_Purchase_Item::display_currency
	 */
	public function test_display_currency()
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array('display_currency'), array('store_purchase'));
		$store_purchase
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$purchase_item = Jam::build('purchase_item', array('store_purchase' => $store_purchase));

		$this->assertEquals('GBP', $purchase_item->display_currency());
		$this->assertEquals('EUR', $purchase_item->display_currency());
	}

	/**
	 * @covers Model_Purchase_Item::price
	 */
	public function test_price()
	{
		$item = $this->getMock('Model_Purchase_Item', array('compute_price'), array('purchase_item'));
		$item->store_purchase = Jam::find('store_purchase', 1);
		$price = new Jam_Price(10, 'USD');

		$item->expects($this->once())
			->method('compute_price')
			->will($this->returnValue($price));

		$this->assertSame($price, $item->price());
		
		$item->price = new Jam_Price(100, 'EUR');

		$this->assertSame($item->price, $item->price());
	}

	/**
	 * @covers Model_Purchase_Item::total_price
	 */
	public function test_total_price()
	{
		$item = $this->getMock('Model_Purchase_Item', array('price'), array('purchase_item'));

		$price = new Jam_Price(10, 'USD');

		$item->expects($this->exactly(2))
			->method('price')
			->will($this->returnValue($price));

		$item->quantity = 2;
		$this->assertEquals(20, $item->total_price()->amount());

		$item->quantity = 3;
		$this->assertEquals(30, $item->total_price()->amount());
	}
}