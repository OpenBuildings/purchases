<?php

use OpenBuildings\Monetary\Monetary;

/**
 * Functest_TestsTest 
 *
 * @group model.purchase
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_PurchaseTest extends Testcase_Purchases {

	/**
	 * @covers Model_Purchase::find_or_build_store_purchase
	 */
	public function test_find_or_build_store_purchase()
	{
		$purchase = Jam::find('purchase', 1);

		$store_purchase = $purchase->find_or_build_store_purchase(1);
		$this->assertSame($purchase->store_purchases[0], $store_purchase);
		$this->assertTrue($store_purchase->loaded());

		$expected_store = $purchase->store_purchases[0]->store;

		$store_purchase = $purchase->find_or_build_store_purchase($expected_store);
		$this->assertSame($purchase->store_purchases[0], $store_purchase);
		$this->assertSame($purchase->store_purchases[0]->store, $store_purchase->store);

		$store_purchase = $purchase->find_or_build_store_purchase(2);
		$this->assertSame($purchase->store_purchases[1], $store_purchase);
		$this->assertFalse($store_purchase->loaded());
		$this->assertEquals(2, $store_purchase->store->id());
	}

	/**
	 * @covers Model_Purchase::monetary
	 */
	public function test_monetary()
	{
		$purchase = Jam::build('purchase');

		$this->assertSame(Monetary::instance(), $purchase->monetary());
		
		$purchase = Jam::find('purchase', 1);

		$monetary = $purchase->monetary();

		$this->assertInstanceOf('Openbuildings\Monetary\Monetary', $monetary);
		$this->assertNotSame(Monetary::instance(), $monetary);
		$this->assertSame($monetary, $purchase->monetary());
		$this->assertEquals(7.4878322725571, $monetary->convert(10, 'USD', 'EUR'));
	}

	/**
	 * @covers Model_Purchase::items
	 * @covers Model_Purchase::items_count
	 */
	public function test_items()
	{
		$purchase = Jam::find('purchase', 1);
		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'type' => 'shipping',
		));

		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'type' => 'promotion',
		));

		$this->assertCount(4, $purchase->items());

		$product_items = $purchase->items('product');
		$this->assertCount(2, $product_items);
		$this->assertEquals(2, $purchase->items_count('product'));
		$this->assertSame($purchase->store_purchases[0]->items[0], $product_items[0]);
		$this->assertSame($purchase->store_purchases[0]->items[1], $product_items[1]);

		$shipping_items = $purchase->items('shipping');
		$this->assertCount(1, $shipping_items);
		$this->assertEquals(1, $purchase->items_count('shipping'));
		$this->assertSame($purchase->store_purchases[0]->items[2], $shipping_items[0]);

		$mixed_items = $purchase->items(array('shipping', 'promotion'));
		$this->assertCount(2, $mixed_items);
		$this->assertEquals(2, $purchase->items_count(array('shipping', 'promotion')));
		$this->assertSame($purchase->store_purchases[0]->items[2], $mixed_items[0]);
		$this->assertSame($purchase->store_purchases[0]->items[3], $mixed_items[1]);
	}

	/**
	 * @covers Model_Purchase::freeze_item_prices
	 */
	public function test_freeze_item_prices()
	{
		$purchase = Jam::build('purchase');

		$item1 = $this->getMock('Model_Store_Purchase', array('freeze_item_prices'), array('store_purchase'));

		$item1->expects($this->once())
			->method('freeze_item_prices');

		$item2 = $this->getMock('Model_Store_Purchase', array('freeze_item_prices'), array('store_purchase'));

		$item2->expects($this->once())
			->method('freeze_item_prices');

		$purchase->store_purchases = array(
			$item1,
			$item2,
		);

		$purchase->freeze_item_prices();
	}	

	/**
	 * @covers Model_Purchase::freeze_monetary
	 */
	public function test_freeze_monetary()
	{
		$purchase = Jam::build('purchase');

		$this->assertSame(Monetary::instance(), $purchase->monetary());
		
		$purchase->freeze_monetary();

		$monetary = $purchase->monetary();

		$this->assertInstanceOf('Openbuildings\Monetary\Monetary', $monetary);
		$this->assertNotSame(Monetary::instance(), $monetary);
		$this->assertSame($monetary, $purchase->monetary());
		$this->assertSame($monetary->source(), Monetary::instance()->source());
	}

	/**
	 * @covers Model_Purchase::freeze
	 */
	public function test_freeze()
	{
		$purchase = $this->getMock('Model_Purchase', array('freeze_item_prices', 'freeze_monetary'), array('purchase'));

		$purchase->expects($this->once())
			->method('freeze_item_prices')
			->will($this->returnValue($purchase));

		$purchase->expects($this->once())
			->method('freeze_monetary')
			->will($this->returnValue($purchase));

		$this->assertFalse($purchase->is_frozen);
		
		$purchase->freeze();

		$this->assertTrue($purchase->is_frozen);
	}

	/**
	 * @covers Model_Purchase::add_item
	 */
	public function test_add_item()
	{
		$purchase = Jam::find('purchase', 1);
		$this->assertCount(2, $purchase->store_purchases[0]->items);

		$existing_item = Jam::build('purchase_item', array(
			'reference_id' => 1,
			'reference_model' => 'product',
			'quantity' => 3,
			'type' => 'product',
		));

		$purchase->add_item(1, $existing_item);

		$this->assertCount(2, $purchase->store_purchases[0]->items);
		$this->assertEquals(4, $purchase->store_purchases[0]->items[0]->quantity);
	}

	/**
	 * @covers Model_Purchase::total_price
	 */
	public function test_total_price()
	{
		$purchase = Jam::build('purchase', array('store_purchases' => array(
			Jam::build('store_purchase')
		)));

		$price1 = new Jam_Price(5, 'EUR');
		$price2 = new Jam_Price(10, 'EUR');

		$item1 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item1->type = 'product';
		$item1->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price1));

		$item2 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item2->type = 'shipping';
		$item2->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price2));

		$purchase->store_purchases[0]->items = array(
			$item1,
			$item2,
		);

		$this->assertEquals(15, $purchase->total_price()->amount());
		$this->assertEquals(5, $purchase->total_price('product')->amount());
		$this->assertEquals(10, $purchase->total_price('shipping')->amount());
		$this->assertEquals(15, $purchase->total_price(array('shipping', 'product'))->amount());
	}
}