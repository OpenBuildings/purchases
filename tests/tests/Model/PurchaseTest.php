<?php

use OpenBuildings\Monetary\Monetary;

/**
 * @group model.purchase
 */
class Model_PurchaseTest extends Testcase_Purchases {

	public function test_initialize()
	{
		$meta = Jam::meta('purchase');
		$this->assertSame('number', $meta->name_key());
	}

	/**
	 * @covers Model_Purchase::find_or_build_store_purchase
	 */
	public function test_find_or_build_store_purchase()
	{
		$purchase = Jam::find('purchase', 1);
		$store1 = Jam::find('store', 1);
		$store2 = Jam::find('store', 2);

		$store_purchase = $purchase->find_or_build_store_purchase($store1);
		$this->assertSame($purchase->store_purchases[0], $store_purchase);
		$this->assertTrue($store_purchase->loaded());

		$expected_store = $purchase->store_purchases[0]->store;

		$store_purchase = $purchase->find_or_build_store_purchase($expected_store);
		$this->assertSame($purchase->store_purchases[0], $store_purchase);
		$this->assertSame($purchase->store_purchases[0]->store, $store_purchase->store);

		$store_purchase = $purchase->find_or_build_store_purchase($store2);
		$this->assertSame($purchase->store_purchases[1], $store_purchase);
		$this->assertFalse($store_purchase->loaded());
		$this->assertEquals($store2->id(), $store_purchase->store->id());
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
		$this->assertEquals(7.4867110878191, $monetary->convert(10, 'USD', 'EUR'));
	}

	/**
	 * @covers Model_Purchase::currency
	 */
	public function test_currency()
	{
		$purchase = Jam::build('purchase', array('currency' => 'EUR'));

		$this->assertEquals('EUR', $purchase->currency());

		$purchase->currency = 'GBP';

		$this->assertEquals('GBP', $purchase->currency());
	}

	/**
	 * @covers Model_Purchase::display_currency
	 */
	public function test_display_currency()
	{
		$purchase = Jam::build('purchase', array('currency' => 'EUR'));

		$this->assertEquals('EUR', $purchase->display_currency());

		$purchase->currency = 'GBP';

		$this->assertEquals('GBP', $purchase->display_currency());
	}

	/**
	 * @covers Model_Purchase::is_paid
	 */
	public function test_is_paid()
	{
		$purchase = Jam::build('purchase');

		$this->assertFalse($purchase->is_paid());

		$purchase->payment = Jam::build('payment');

		$this->assertFalse($purchase->is_paid());

		$purchase->payment->status = Model_Payment::PAID;

		$this->assertTrue($purchase->is_paid());
	}

	/**
	 * @covers Model_Purchase::paid_at
	 */
	public function test_paid_at()
	{
		$purchase = $this->getMock('Model_Purchase', array('is_paid'), array('purchase'));
		$purchase->payment = Jam::build('payment', array('created_at' => '2013-02-02 10:00:00'));

		$purchase
			->expects($this->exactly(2))
			->method('is_paid')
			->will($this->onConsecutiveCalls(FALSE, TRUE));

		$this->assertNull($purchase->paid_at());
		$this->assertEquals('2013-02-02 10:00:00', $purchase->paid_at());
	}


	/**
	 * @covers Model_Purchase::items
	 */
	public function test_items()
	{
		$purchase = Jam::find('purchase', 1);
		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
		));

		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
		));

		$this->assertCount(4, $purchase->items());

		$product_items = $purchase->items('product');
		$this->assertCount(2, $product_items);
		$this->assertSame($purchase->store_purchases[0]->items[0], $product_items[0]);
		$this->assertSame($purchase->store_purchases[0]->items[1], $product_items[1]);

		$shipping_items = $purchase->items('shipping');
		$this->assertCount(1, $shipping_items);
		$this->assertSame($purchase->store_purchases[0]->items[2], $shipping_items[0]);

		$mixed_items = $purchase->items(array('shipping', 'promotion'));
		$this->assertCount(2, $mixed_items);
		$this->assertSame($purchase->store_purchases[0]->items[2], $mixed_items[0]);
		$this->assertSame($purchase->store_purchases[0]->items[3], $mixed_items[1]);

		$excluded_items = $purchase->items(array('not' => 'promotion'));
		$this->assertCount(3, $excluded_items);
		$this->assertSame($purchase->store_purchases[0]->items[0], $excluded_items[0]);
		$this->assertSame($purchase->store_purchases[0]->items[1], $excluded_items[1]);
		$this->assertSame($purchase->store_purchases[0]->items[2], $excluded_items[2]);

		$excluded_items = $purchase->items(array('not' => array('promotion', 'shipping')));
		$this->assertCount(2, $excluded_items);
		$this->assertSame($purchase->store_purchases[0]->items[0], $excluded_items[0]);
		$this->assertSame($purchase->store_purchases[0]->items[1], $excluded_items[1]);
	}

	/**
	 * @covers Model_Purchase::items_count
	 */
	public function test_items_count()
	{
		$purchase = Jam::find('purchase', 1);
		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
		));

		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
		));

		$this->assertEquals(2, $purchase->items_count('product'));
		$this->assertEquals(1, $purchase->items_count('shipping'));
		$this->assertEquals(2, $purchase->items_count(array('shipping', 'promotion')));
		$this->assertEquals(3, $purchase->items_count(array('not' => 'promotion')));
		$this->assertEquals(2, $purchase->items_count(array('not' => array('promotion', 'shipping'))));
	}

	/**
	 * @covers Model_Purchase::add_item
	 */
	public function test_add_item()
	{
		$purchase = Jam::find('purchase', 1);
		$store = Jam::find('store', 1);
		$this->assertCount(2, $purchase->store_purchases[0]->items);

		$existing_item = Jam::build('purchase_item', array(
			'reference_id' => 1,
			'reference_model' => 'product',
			'quantity' => 3,
			'model' => 'purchase_item_product',
		));

		$purchase->add_item($store, $existing_item);

		$this->assertSame($existing_item, $purchase->item_added);

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
		$item1->model = 'purchase_item_product';
		$item1->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price1));

		$item2 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item2->model = 'purchase_item_shipping';
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

	/**
	 * @covers Model_Purchase::update_items
	 */
	public function test_triger_update_items()
	{
		$purchase = Jam::build('purchase');

		$item1 = $this->getMock('Model_Store_Purchase', array('update_items'), array('store_purchase'));

		$item1->expects($this->once())
			->method('update_items');

		$item2 = $this->getMock('Model_Store_Purchase', array('update_items'), array('store_purchase'));

		$item2->expects($this->once())
			->method('update_items');

		$purchase->store_purchases = array(
			$item1,
			$item2,
		);

		$purchase->update_items();
	}


	/**
	 * @covers Model_Purchase::replace_items
	 */
	public function test_replace_items()
	{
		$purchase = Jam::build('purchase');

		$store_purchase1 = $this->getMock('Model_Store_Purchase', array(
			'replace_items'
		), array(
			'store_purchase'
		));

		$store_purchase1->id = 10;
		$store_purchase2 = $this->getMock('Model_Store_Purchase', array(
			'replace_items'
		), array(
			'store_purchase'
		));

		$store_purchase2->id = 20;
		$store_purchase3 = Jam::build('store_purchase', array('id' => 30));

		$item1 = Jam::build('purchase_item', array(
			'id' => 10,
			'store_purchase' => $store_purchase1
		));
		$item2 = Jam::build('purchase_item', array(
			'id' => 20,
			'store_purchase' => $store_purchase1
		));
		$item3 = Jam::build('purchase_item', array(
			'id' => 30,
			'store_purchase' => $store_purchase2
		));

		$store_purchase1
			->expects($this->once())
			->method('replace_items')
			->with($this->equalTo(array(
				$item1, $item2
			)), $this->equalTo('product'));

		$store_purchase2
			->expects($this->once())
			->method('replace_items')
			->with($this->equalTo(array($item3)), $this->equalTo('product'));

		$purchase->store_purchases = array(
			$store_purchase1,
			$store_purchase2,
			$store_purchase3,
		);

		$purchase->replace_items(array($item1, $item2, $item3), 'product');

		$this->assertFalse($purchase->store_purchases->has($store_purchase3));
	}

	/**
	 * @covers Model_Purchase::items_quantity
	 */
	public function test_items_quantity()
	{
		$purchase = Jam::build('purchase', array(
			'store_purchases' => array(
				array(
					'store' => 1,
					'items' => array(
						array('model' => 'purchase_item_product', 'quantity' => 2),
						array('model' => 'purchase_item_shipping', 'quantity' => 1),
					)
				),
				array(
					'store' => 1,
					'items' => array(
						array('model' => 'purchase_item_product', 'quantity' => 3),
					)
				),
			)
		));

		$this->assertEquals(6, $purchase->items_quantity());
		$this->assertEquals(5, $purchase->items_quantity('product'));
		$this->assertEquals(1, $purchase->items_quantity('shipping'));
		$this->assertEquals(6, $purchase->items_quantity(array('product', 'shipping')));
	}

	/**
	 * @covers Model_Purchase::recheck
	 */
	public function test_recheck()
	{
		$purchase = Jam::find('purchase', 1);

		$this->assertTrue($purchase->check());

		$purchase->store_purchases[0]->items[0]->price = -10;

		$this->assertFalse($purchase->recheck());
	}
}