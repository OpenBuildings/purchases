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
	 * @covers Model_Purchase_Item::compute_price
	 */
	public function test_compute_price()
	{
		$item = Jam::find('purchase_item', 1);
		$item->reference = $this->getMock('Model_Product', array('price'), array('product'));

		$price1 = new Jam_Price(10, 'EUR');
		$price2 = new Jam_Price(10, 'USD');

		$item->reference->expects($this->at(0))
			->method('price')
			->with($this->identicalTo($item))
			->will($this->returnValue($price1));

		$item->reference->expects($this->at(1))
			->method('price')
			->with($this->identicalTo($item))
			->will($this->returnValue($price2));

		$this->assertEquals(new Jam_Price(10, 'EUR', $item->monetary()), $item->compute_price(), 'Should be EUR -> EUR conversion');
		$this->assertEquals(new Jam_Price(7.4878322725571, 'EUR', $item->monetary()), $item->compute_price(), 'Should be EUR -> USD conversion');
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Model_Purchase_Item::purchase_insist
	 */
	public function test_purchase_insist()
	{
		$item = Jam::find('purchase_item', 1);
		$this->assertInstanceOf('Model_Purchase', $item->purchase_insist());

		$item->store_purchase->purchase = NULL;
		$item->purchase_insist();
	}

	/**
	 * @covers Model_Purchase_Item::monetary
	 */
	public function test_monetary()
	{
		$item = Jam::find('purchase_item', 1);

		$this->assertInstanceOf('Openbuildings\Monetary\Monetary', $item->monetary());
		$this->assertSame($item->store_purchase->purchase->monetary(), $item->monetary());
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
	 * @covers Model_Purchase_Item::freeze_price
	 */
	public function test_freeze_price()
	{
		$item = $this->getMock('Model_Purchase_Item', array('compute_price'), array('purchase_item'));

		$price = new Jam_Price(10, 'USD');

		$item->expects($this->once())
			->method('compute_price')
			->will($this->returnValue($price));

		$this->assertNull($item->price);

		$item->freeze_price();

		$this->assertEquals($price, $item->price);
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