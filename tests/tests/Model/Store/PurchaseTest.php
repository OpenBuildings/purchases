<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model
 * @group model.store_purchase
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Store_PurchaseTest extends Testcase_Purchases {

	/**
	 * @covers Model_Store_Purchase::initialize
	 */
	public function test_initialize()
	{
		$meta = Jam::meta('store_purchase');
		$this->assertSame('number', $meta->name_key());
	}

	/**
	 * @coversNothing
	 */
	public function test_implements_purchasable()
	{
		$this->assertInstanceOf('Purchasable', Jam::build('store_purchase'));
	}

	/**
	 * @covers Model_Store_Purchase::search_same_item
	 */
	public function test_search_same_item()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];

		$first_item = $store_purchase->items[0];
		$second_item = $store_purchase->items[1];

		$new_item = Jam::build('purchase_item_product', array(
			'reference' => Jam::find('product', 1),
			'quantity' => 3,
		));

		$found_index = $store_purchase->search_same_item($new_item);

		$this->assertSame($first_item, $store_purchase->items[$found_index]);

		$new_item->model = 'purchase_item_shipping';
		$found_index = $store_purchase->search_same_item($new_item);
		$this->assertNull($found_index);

		$new_item->model = 'purchase_item_product';
		$new_item->reference = Jam::find('variation', 1);

		$found_index = $store_purchase->search_same_item($new_item);
		$this->assertSame($second_item, $store_purchase->items[$found_index]);
	}

	/**
	 * @covers Model_Store_Purchase::add_or_update_item
	 */
	public function test_add_or_update_item()
	{
		$purchase = Jam::find('purchase', 1)->unfreeze();
		$store_purchase = $purchase->store_purchases[0];

		$existing_item_product = Jam::build('purchase_item', array(
			'reference' => Jam::find('product', 1),
			'quantity' => 3,
			'model' => 'purchase_item_product',
		));

		$existing_item_variation = Jam::build('purchase_item', array(
			'reference' => Jam::find('variation', 1),
			'quantity' => 2,
			'model' => 'purchase_item_product',
		));

		$new_item_product = Jam::build('purchase_item', array(
			'reference' => Jam::find('product', 3),
			'quantity' => 5,
			'model' => 'purchase_item_product',
		));

		$store_purchase->add_or_update_item($existing_item_product);

		$this->assertCount(2, $store_purchase->items);
		$this->assertEquals(4, $store_purchase->items[0]->quantity);

		$store_purchase->add_or_update_item($existing_item_variation);

		$this->assertCount(2, $store_purchase->items);
		$this->assertEquals(3, $store_purchase->items[1]->quantity);

		$store_purchase->add_or_update_item($new_item_product);

		$this->assertCount(3, $store_purchase->items);
		$this->assertEquals(5, $store_purchase->items[2]->quantity);

		$store_purchase->save();

		$this->assertCount(3, $store_purchase->items);
		$this->assertEquals(5, $store_purchase->items[2]->quantity);
	}

	/**
	 * @covers Model_Store_Purchase::items
	 * @covers Jam_Behavior_Store_Purchase::filter_items
	 * @covers Model_Store_Purchase::items_count
	 */
	public function test_items()
	{
		$store_purchase = Jam::find('store_purchase', 1);
		$store_purchase->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
			'is_payable' => TRUE,
		));

		$store_purchase->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
			'is_discount' => TRUE,
		));

		$this->assertCount(4, $store_purchase->items());

		$product_items = $store_purchase->items('product');
		$this->assertCount(2, $product_items);
		$this->assertSame($store_purchase->items[0], $product_items[0]);
		$this->assertSame($store_purchase->items[1], $product_items[1]);

		$shipping_items = $store_purchase->items('shipping');
		$this->assertCount(1, $shipping_items);
		$this->assertSame($store_purchase->items[2], $shipping_items[0]);

		$mixed_items = $store_purchase->items(array('shipping', 'promotion'));
		$this->assertCount(2, $mixed_items);
		$this->assertSame($store_purchase->items[2], $mixed_items[0]);
		$this->assertSame($store_purchase->items[3], $mixed_items[1]);

		$payable_items = $store_purchase->items(array('is_payable' => TRUE));
		$this->assertCount(3, $payable_items);
		$this->assertSame($store_purchase->items[0], $payable_items[0]);
		$this->assertSame($store_purchase->items[1], $payable_items[1]);
		$this->assertSame($store_purchase->items[2], $payable_items[2]);

		$not_payable_items = $store_purchase->items(array('is_payable' => FALSE));
		$this->assertCount(1, $not_payable_items);
		$this->assertSame($store_purchase->items[3], $not_payable_items[0]);

		$discount_items = $store_purchase->items(array('is_discount' => TRUE));
		$this->assertCount(1, $discount_items);
		$this->assertSame($store_purchase->items[3], $discount_items[0]);
	}

	/**
	 * @covers Model_Store_Purchase::total_price
	 */
	public function test_total_price()
	{
		$monetary = new Monetary('GBP', new Source_Static);

		$store_purchase = $this->getMock('Model_Store_Purchase', array('currency', 'monetary', 'display_currency'), array('store_purchase'));

		$store_purchase
			->expects($this->exactly(4))
			->method('currency')
			->will($this->returnValue('EUR'));

		$store_purchase
			->expects($this->exactly(4))
			->method('monetary')
			->will($this->returnValue($monetary));

		$store_purchase
			->expects($this->exactly(4))
			->method('display_currency')
			->will($this->returnValue('GBP'));

		$price1 = new Jam_Price(5, 'EUR');
		$price2 = new Jam_Price(10, 'EUR');

		$item1 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item1->model = 'purchase_item_product';
		$item1
			->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price1));

		$item2 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item2->model = 'purchase_item_shipping';
		$item2
			->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue($price2));

		$store_purchase->items = array(
			$item1,
			$item2,
		);

		$this->assertEquals(new Jam_Price(15, 'EUR', $monetary, 'GBP'), $store_purchase->total_price());
		$this->assertEquals(new Jam_Price(5, 'EUR', $monetary, 'GBP'), $store_purchase->total_price('product'));
		$this->assertEquals(new Jam_Price(10, 'EUR', $monetary, 'GBP'), $store_purchase->total_price('shipping'));
		$this->assertEquals(new Jam_Price(15, 'EUR', $monetary, 'GBP'), $store_purchase->total_price(array('shipping', 'product')));
	}

	/**
	 * @covers Model_Store_Purchase::items_count
	 */
	public function test_items_count()
	{
		$store_purchase = Jam::find('store_purchase', 1);
		$store_purchase->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
			'is_payable' => TRUE,
		));

		$store_purchase->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
			'is_discount' => TRUE,
		));

		$this->assertCount(4, $store_purchase->items());

		$this->assertEquals(2, $store_purchase->items_count('product'));

		$this->assertEquals(1, $store_purchase->items_count('shipping'));

		$this->assertEquals(2, $store_purchase->items_count(array('shipping', 'promotion')));

		$this->assertEquals(3, $store_purchase->items_count(array('is_payable' => TRUE)));

		$this->assertEquals(1, $store_purchase->items_count(array('is_payable' => FALSE)));

		$this->assertEquals(1, $store_purchase->items_count(array('is_discount' => TRUE)));
	}

	/**
	 * @covers Model_Store_Purchase::items_quantity
	 */
	public function test_items_quantity()
	{
		$store_purchase = Jam::build('store_purchase', array(
			'items' => array(
				array('model' => 'purchase_item_product', 'quantity' => 2),
				array('model' => 'purchase_item_product', 'quantity' => 3),
				array('model' => 'purchase_item_shipping', 'quantity' => 1),
			)
		));

		$this->assertEquals(6, $store_purchase->items_quantity());
		$this->assertEquals(5, $store_purchase->items_quantity('product'));
		$this->assertEquals(1, $store_purchase->items_quantity('shipping'));
		$this->assertEquals(6, $store_purchase->items_quantity(array('product', 'shipping')));
	}

	/**
	 * @covers Model_Store_Purchase::update_items
	 */
	public function test_triger_update_items()
	{
		$store_purchase = Jam::find('store_purchase', 2)->update_items();

		$this->assertTrue($store_purchase->items_updated);
	}

	/**
	 * @covers Model_Store_Purchase::replace_items
	 * @covers Model_Purchase::replace_items
	 */
	public function test_replace_items()
	{
		$store_purchase = Jam::find('store_purchase', 2);

		$store_purchase->items->build(array(
			'id' => 23,
			'quantity' => 1,
			'price' => 20,
			'model' => 'purchase_item_shipping',
			'is_payable' => TRUE,
		));

		$new_item = array(
			'id' => 100,
			'quantity' => 1,
			'price' => 10,
			'model' => 'purchase_item_shipping',
			'is_payable' => TRUE,
		);

		$update_item = array(
			'id' => 3,
			'quantity' => 3,
		);

		$products = $store_purchase->items('product');
		$shipping = $store_purchase->items('shipping');

		$store_purchase->replace_items(array($new_item), 'shipping');

		$this->assertEquals($products, $store_purchase->items('product'));

		$new_shipping = $store_purchase->items('shipping');
		$this->assertNotEquals($shipping, $new_shipping);
		$this->assertEquals(array($new_item['id']), $this->ids($new_shipping));
		$this->assertEquals($new_item['price'], $new_shipping[0]->price->amount());

		$store_purchase->replace_items(array(), 'shipping');
		$cleared_shipping = $store_purchase->items('shipping');

		$this->assertEquals(array(), $this->ids($cleared_shipping));

		$existing_item = $store_purchase->items[0]->as_array();

		$products = $store_purchase->replace_items(array($update_item), 'product');

		$expected = array_merge($existing_item, $update_item);

		$this->assertEquals($expected, $store_purchase->items[0]->as_array());
	}

	/**
	 * @covers Model_Store_Purchase::is_paid
	 */
	public function test_is_paid()
	{
		$purchase = $this->getMock('Model_Purchase', array('is_paid'), array('purchase'));
		$purchase
			->expects($this->exactly(2))
				->method('is_paid')
				->will($this->onConsecutiveCalls(TRUE, FALSE));

		$store_purchase = Jam::build('store_purchase', array('purchase' => $purchase));

		$this->assertTrue($store_purchase->is_paid());
		$this->assertFalse($store_purchase->is_paid());
	}

	/**
	 * @covers Model_Store_Purchase::paid_at
	 */
	public function test_paid_at()
	{
		$purchase = $this->getMock('Model_Purchase', array('paid_at'), array('purchase'));
		$purchase
			->expects($this->exactly(2))
				->method('paid_at')
				->will($this->onConsecutiveCalls('2013-05-02 10:00:00', '2013-02-02 10:00:00'));

		$store_purchase = Jam::build('store_purchase', array('purchase' => $purchase));

		$this->assertEquals('2013-05-02 10:00:00', $store_purchase->paid_at());
		$this->assertEquals('2013-02-02 10:00:00', $store_purchase->paid_at());
	}

	/**
	 * @covers Model_Store_Purchase::currency
	 */
	public function test_currency()
	{
		$purchase = $this->getMock('Model_Purchase', array('currency'), array('purchase'));
		$purchase
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$store_purchase = Jam::build('store_purchase', array('purchase' => $purchase));

		$this->assertEquals('GBP', $store_purchase->currency());
		$this->assertEquals('EUR', $store_purchase->currency());
	}

	/**
	 * @covers Model_Store_Purchase::display_currency
	 */
	public function test_display_currency()
	{
		$purchase = $this->getMock('Model_Purchase', array('display_currency'), array('purchase'));
		$purchase
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$store_purchase = Jam::build('store_purchase', array('purchase' => $purchase));

		$this->assertEquals('GBP', $store_purchase->display_currency());
		$this->assertEquals('EUR', $store_purchase->display_currency());
	}

	/**
	 * @covers Model_Store_Purchase::monetary
	 */
	public function test_monetary()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$purchase = $this->getMock('Model_Purchase', array('monetary'), array('purchase'));
		$purchase
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$store_purchase = Jam::build('store_purchase', array('purchase' => $purchase));

		$this->assertSame($monetary, $store_purchase->monetary());
	}

	public function data_total_price_ratio()
	{
		return array(
			array(new Jam_Price(5, 'EUR'), new Jam_Price(5, 'EUR'), 1.0),
			array(new Jam_Price(10, 'EUR'), new Jam_Price(50, 'EUR'), 0.2),
			array(new Jam_Price(40, 'EUR'), new Jam_Price(1250, 'EUR'), 0.032),
		);
	}

	/**
	 * @covers Model_Store_Purchase::total_price_ratio
	 * @dataProvider data_total_price_ratio
	 */
	public function test_total_price_ratio($store_purchase_price, $purchase_price, $expected_ratio)
	{
		$filters = array('some_filters' => TRUE);

		$purchase = $this->getMock('Model_Purchase', array('total_price'), array('purchase'));

		$purchase
			->expects($this->once())
			->method('total_price')
			->with($this->identicalTo($filters))
			->will($this->returnValue($purchase_price));

		$store_purchase = $this->getMock('Model_Store_Purchase', array('total_price'), array('store_purchase'));

		$store_purchase
			->expects($this->once())
			->method('total_price')
			->with($this->identicalTo($filters))
			->will($this->returnValue($store_purchase_price));

		$store_purchase->purchase = $purchase;

		$this->assertSame($expected_ratio, $store_purchase->total_price_ratio($filters));
	}

	/**
	 * @covers Model_Store_Purchase::store
	 */
	public function test_store()
	{
		$store_purchase = Jam::find('store_purchase', 3);
		$this->assertNull($store_purchase->store);

		$store_purchase = Jam::find('store_purchase', 3);
		$this->assertInstanceOf('Model_Store', $store_purchase->store());

		$store_purchase->store = NULL;
		$this->assertNull($store_purchase->store());
	}
}
