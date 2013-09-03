<?php

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
	 * @covers Model_Store_Purchase::find_same_item
	 */
	public function test_find_same_item()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];

		$first_item = $store_purchase->items[0];
		$second_item = $store_purchase->items[1];

		$new_item = Jam::build('purchase_item', array(
			'reference' => Jam::find('product', 1),
			'quantity' => 3,
			'type' => 'product',
		));

		$found_item = $store_purchase->find_same_item($new_item);
		
		$this->assertSame($first_item, $found_item);

		$new_item->type = 'shipping';
		$found_item = $store_purchase->find_same_item($new_item);
		$this->assertNull($found_item);

		$new_item->type = 'product';
		$new_item->reference = Jam::find('variation', 1);

		$found_item = $store_purchase->find_same_item($new_item);
		$this->assertSame($second_item, $found_item);
	}

	/**
	 * @covers Model_Store_Purchase::add_or_update_item
	 */
	public function test_add_or_update_item()
	{
		$store_purchase = Jam::find('purchase', 1)->store_purchases[0];

		$existing_item_product = Jam::build('purchase_item', array(
			'reference' => Jam::find('product', 1),
			'quantity' => 3,
			'type' => 'product',
		));

		$existing_item_variation = Jam::build('purchase_item', array(
			'reference' => Jam::find('variation', 1),
			'quantity' => 2,
			'type' => 'product',
		));

		$new_item_product = Jam::build('purchase_item', array(
			'reference' => Jam::find('product', 3),
			'quantity' => 5,
			'type' => 'product',
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
	 * @covers Model_Store_Purchase::items_count
	 */
	public function test_items()
	{
		$store_purchase = Jam::find('store_purchase', 1);
		$store_purchase->items->build(array(
			'quantity' => 1,
			'price' => 10,
			'type' => 'shipping',
			'is_payable' => TRUE,
		));

		$store_purchase->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'type' => 'promotion',
			'is_discount' => TRUE,
		));

		$this->assertCount(4, $store_purchase->items());

		$product_items = $store_purchase->items('product');
		$this->assertCount(2, $product_items);
		$this->assertEquals(2, $store_purchase->items_count('product'));
		$this->assertSame($store_purchase->items[0], $product_items[0]);
		$this->assertSame($store_purchase->items[1], $product_items[1]);

		$shipping_items = $store_purchase->items('shipping');
		$this->assertCount(1, $shipping_items);
		$this->assertEquals(1, $store_purchase->items_count('shipping'));
		$this->assertSame($store_purchase->items[2], $shipping_items[0]);

		$mixed_items = $store_purchase->items(array('shipping', 'promotion'));
		$this->assertCount(2, $mixed_items);
		$this->assertEquals(2, $store_purchase->items_count(array('shipping', 'promotion')));
		$this->assertSame($store_purchase->items[2], $mixed_items[0]);
		$this->assertSame($store_purchase->items[3], $mixed_items[1]);

		$payable_items = $store_purchase->items(array('is_payable' => TRUE));
		$this->assertCount(3, $payable_items);
		$this->assertEquals(3, $store_purchase->items_count(array('is_payable' => TRUE)));
		$this->assertSame($store_purchase->items[0], $payable_items[0]);
		$this->assertSame($store_purchase->items[1], $payable_items[1]);
		$this->assertSame($store_purchase->items[2], $payable_items[2]);		

		$not_payable_items = $store_purchase->items(array('is_payable' => FALSE));
		$this->assertCount(1, $not_payable_items);
		$this->assertEquals(1, $store_purchase->items_count(array('is_payable' => FALSE)));
		$this->assertSame($store_purchase->items[3], $not_payable_items[0]);

		$discount_items = $store_purchase->items(array('is_discount' => TRUE));
		$this->assertCount(1, $discount_items);
		$this->assertEquals(1, $store_purchase->items_count(array('is_discount' => TRUE)));
		$this->assertSame($store_purchase->items[3], $discount_items[0]);
	}

	/**
	 * @covers Model_Store_Purchase::freeze_item_prices
	 */
	public function test_freeze_item_prices()
	{
		$store_purchase = Jam::build('store_purchase');

		$item1 = $this->getMock('Model_Purchase_Item', array('freeze_price'), array('purchase_item'));

		$item1->expects($this->once())
			->method('freeze_price')
			->will($this->returnValue(5));

		$item2 = $this->getMock('Model_Purchase_Item', array('freeze_price'), array('purchase_item'));

		$item2->expects($this->once())
			->method('freeze_price')
			->will($this->returnValue(10));

		$store_purchase->items = array(
			$item1,
			$item2,
		);

		$store_purchase->freeze_item_prices();
	}

	/**
	 * @covers Model_Store_Purchase::total_price
	 */
	public function test_total_price()
	{
		$store_purchase = Jam::build('store_purchase');

		$item1 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item1->type = 'product';
		$item1->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue(5));

		$item2 = $this->getMock('Model_Purchase_Item', array('total_price'), array('purchase_item'));
		$item2->type = 'shipping';
		$item2->expects($this->exactly(3))
			->method('total_price')
			->will($this->returnValue(10));

		$store_purchase->items = array(
			$item1,
			$item2,
		);

		$this->assertEquals(15, $store_purchase->total_price());
		$this->assertEquals(5, $store_purchase->total_price('product'));
		$this->assertEquals(10, $store_purchase->total_price('shipping'));
		$this->assertEquals(15, $store_purchase->total_price(array('shipping', 'product')));
	}

	/**
	 * @covers Model_Store_Purchase::total_price_in
	 */
	public function test_total_price_in()
	{
		$store_purchase = Jam::find('store_purchase', 1);

		$total_price_in_usd = $store_purchase
			->total_price_in('USD', array('product'));

		$this->assertEquals(400, $store_purchase->total_price(array('product')));
		$this->assertEquals(534.2, $total_price_in_usd);
	}
}