<?php

use OpenBuildings\Monetary\Monetary;

/**
 * @group model.purchase
 */
class Model_PurchaseTest extends Testcase_Purchases {

	/**
	 * @covers Model_Purchase::initialize
	 */
	public function test_initialize()
	{
		$meta = Jam::meta('purchase');
		$this->assertSame('number', $meta->name_key());
	}

	/**
	 * @coversNothing
	 */
	public function test_implements_purchasable()
	{
		$this->assertInstanceOf('Purchasable', Jam::build('purchase'));
	}

	/**
	 * @covers Model_Purchase::find_or_build_brand_purchase
	 */
	public function test_find_or_build_brand_purchase()
	{
		$purchase = Jam::find('purchase', 1);
		$brand1 = Jam::find('brand', 1);
		$brand2 = Jam::find('brand', 2);

		$brand_purchase = $purchase->find_or_build_brand_purchase($brand1);
		$this->assertSame($purchase->brand_purchases[0], $brand_purchase);
		$this->assertTrue($brand_purchase->loaded());

		$expected_brand = $purchase->brand_purchases[0]->brand;

		$brand_purchase = $purchase->find_or_build_brand_purchase($expected_brand);
		$this->assertSame($purchase->brand_purchases[0], $brand_purchase);
		$this->assertSame($purchase->brand_purchases[0]->brand, $brand_purchase->brand);

		$brand_purchase = $purchase->find_or_build_brand_purchase($brand2);
		$this->assertSame($purchase->brand_purchases[1], $brand_purchase);
		$this->assertFalse($brand_purchase->loaded());
		$this->assertEquals($brand2->id(), $brand_purchase->brand->id());
	}

	/**
	 * @covers Model_Purchase::find_brand_purchase
	 */
	public function test_find_brand_purchase()
	{
		$purchase = Jam::find('purchase', 1);
		$brand1 = Jam::find('brand', 1);
		$brand2 = Jam::find('brand', 2);

		$brand_purchase = $purchase->find_brand_purchase($brand1);
		$this->assertSame($purchase->brand_purchases[0], $brand_purchase);
		$this->assertTrue($brand_purchase->loaded());

		$expected_brand = $purchase->brand_purchases[0]->brand;

		$brand_purchase = $purchase->find_brand_purchase($expected_brand);
		$this->assertSame($purchase->brand_purchases[0], $brand_purchase);
		$this->assertSame($purchase->brand_purchases[0]->brand, $brand_purchase->brand);

		$brand_purchase = $purchase->find_brand_purchase($brand2);
		$this->assertNull($brand_purchase);
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
		$purchase = $this->getMockBuilder('Model_Purchase')
			->setMethods(array('is_paid'))
			->setConstructorArgs(array('purchase'))
			->getMock();
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
		$purchase->brand_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
		));

		$purchase->brand_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
		));

		$this->assertCount(4, $purchase->items());

		$product_items = $purchase->items('product');
		$this->assertCount(2, $product_items);
		$this->assertSame($purchase->brand_purchases[0]->items[0], $product_items[0]);
		$this->assertSame($purchase->brand_purchases[0]->items[1], $product_items[1]);

		$shipping_items = $purchase->items('shipping');
		$this->assertCount(1, $shipping_items);
		$this->assertSame($purchase->brand_purchases[0]->items[2], $shipping_items[0]);

		$mixed_items = $purchase->items(array('shipping', 'promotion'));
		$this->assertCount(2, $mixed_items);
		$this->assertSame($purchase->brand_purchases[0]->items[2], $mixed_items[0]);
		$this->assertSame($purchase->brand_purchases[0]->items[3], $mixed_items[1]);

		$excluded_items = $purchase->items(array('not' => 'promotion'));
		$this->assertCount(3, $excluded_items);
		$this->assertSame($purchase->brand_purchases[0]->items[0], $excluded_items[0]);
		$this->assertSame($purchase->brand_purchases[0]->items[1], $excluded_items[1]);
		$this->assertSame($purchase->brand_purchases[0]->items[2], $excluded_items[2]);

		$excluded_items = $purchase->items(array('not' => array('promotion', 'shipping')));
		$this->assertCount(2, $excluded_items);
		$this->assertSame($purchase->brand_purchases[0]->items[0], $excluded_items[0]);
		$this->assertSame($purchase->brand_purchases[0]->items[1], $excluded_items[1]);
	}

	/**
	 * @covers Model_Purchase::items_count
	 */
	public function test_items_count()
	{
		$purchase = Jam::find('purchase', 1);
		$purchase->brand_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
		));

		$purchase->brand_purchases[0]->items->build(array(
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
		$brand = Jam::find('brand', 1);
		$this->assertCount(2, $purchase->brand_purchases[0]->items);

		$existing_item = Jam::build('purchase_item', array(
			'reference_id' => 1,
			'reference_model' => 'product',
			'quantity' => 3,
			'model' => 'purchase_item_product',
		));

		$purchase->add_item($brand, $existing_item);

		$this->assertSame($existing_item, $purchase->item_added);

		$this->assertCount(2, $purchase->brand_purchases[0]->items);
		$this->assertEquals(4, $purchase->brand_purchases[0]->items[0]->quantity);
	}

	/**
	 * @covers Model_Purchase::remove_item
	 */
	public function test_remove_item()
	{
		$purchase = Jam::find('purchase', 1)->unfreeze();
		$this->assertCount(2, $purchase->items('product'));

		$purchase_item = Arr::get($purchase->items('product'), 0);
		$purchase->remove_item($purchase_item->brand_purchase->brand, $purchase_item);
		$this->assertCount(1, $purchase->items('product'));

		$purchase_item = Arr::get($purchase->items('product'), 0);
		$purchase->remove_item($purchase_item->brand_purchase->brand, $purchase_item);
		$this->assertCount(0, $purchase->items('product'));
		$this->assertCount(0, $purchase->brand_purchases);
	}

	/**
	 * @covers Model_Purchase::total_price
	 */
	public function test_total_price()
	{
		$purchase = Jam::build('purchase', array('brand_purchases' => array(
			Jam::build('brand_purchase')
		)));

		$price1 = new Jam_Price(5, 'EUR');
		$price2 = new Jam_Price(10, 'EUR');

		$item1 = $this->getMockBuilder('Model_Purchase_Item')
			->setMethods(array('total_price'))
			->setConstructorArgs(array('purchase_item'))
			->getMock();
		$item1->model = 'purchase_item_product';
		$item1->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price1));

		$item2 = $this->getMockBuilder('Model_Purchase_Item')
			->setMethods(array('total_price'))
			->setConstructorArgs(array('purchase_item'))
			->getMock();
		$item2->model = 'purchase_item_shipping';
		$item2->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price2));

		$purchase->brand_purchases[0]->items = array(
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

		$item1 = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('update_items'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();

		$item1->expects($this->once())
			->method('update_items');

		$item2 = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('update_items'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();

		$item2->expects($this->once())
			->method('update_items');

		$purchase->brand_purchases = array(
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

		$brand_purchase1 = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('replace_items'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();

		$brand_purchase1->id = 10;
		$brand_purchase2 = $this->getMockBuilder('Model_Brand_Purchase')
			->setMethods(array('replace_items'))
			->setConstructorArgs(array('brand_purchase'))
			->getMock();

		$brand_purchase2->id = 20;
		$brand_purchase3 = Jam::build('brand_purchase', array('id' => 30));

		$item1 = Jam::build('purchase_item', array(
			'id' => 10,
			'brand_purchase' => $brand_purchase1
		));
		$item2 = Jam::build('purchase_item', array(
			'id' => 20,
			'brand_purchase' => $brand_purchase1
		));
		$item3 = Jam::build('purchase_item', array(
			'id' => 30,
			'brand_purchase' => $brand_purchase2
		));

		$brand_purchase1
			->expects($this->once())
			->method('replace_items')
			->with($this->equalTo(array(
				$item1, $item2
			)), $this->equalTo('product'));

		$brand_purchase2
			->expects($this->once())
			->method('replace_items')
			->with($this->equalTo(array($item3)), $this->equalTo('product'));

		$purchase->brand_purchases = array(
			$brand_purchase1,
			$brand_purchase2,
			$brand_purchase3,
		);

		$purchase->replace_items(array($item1, $item2, $item3), 'product');

		$this->assertFalse($purchase->brand_purchases->has($brand_purchase3));
	}

	/**
	 * @covers Model_Purchase::items_quantity
	 */
	public function test_items_quantity()
	{
		$purchase = Jam::build('purchase', array(
			'brand_purchases' => array(
				array(
					'brand' => 1,
					'items' => array(
						array('model' => 'purchase_item_product', 'quantity' => 2),
						array('model' => 'purchase_item_shipping', 'quantity' => 1),
					)
				),
				array(
					'brand' => 1,
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

		$purchase->brand_purchases[0]->items[0]->price = -10;

		$this->assertFalse($purchase->recheck());
	}
}
