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

	// Jam::build('test_purchase', array(
	// 	'payment' => array(
	// 		'method' => 'emp',
	// 		'status' => 'paid',
	// 		'raw_response' => array('asd'),
	// 	),
	// 	'store_purchases' => array(
	// 		array(
	// 			'store' => 1,
	// 			'items' => array(
	// 				array(
	// 					'price' => 200,
	// 					'type' => 'product',
	// 					'quantity' => 1,
	// 					'reference' => array('test_product' => 1),
	// 				),
	// 				array(
	// 					'price' => 200,
	// 					'type' => 'product',
	// 					'quantity' => 1,
	// 					'reference' => array('test_variation' => 1),
	// 				),
	// 			),
	// 		)
	// 	)
	// ));

	/**
	 * @covers Model_Purchase::monetary
	 */
	public function test_monetary()
	{
		$purchase = Jam::build('test_purchase');

		$this->assertSame(Monetary::instance(), $purchase->monetary());
		
		$purchase = Jam::find('test_purchase', 1);

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
		$purchase = Jam::find('test_purchase', 1);
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
		$purchase = Jam::build('test_purchase');

		$item1 = $this->getMock('Model_Test_Store_Purchase', array('freeze_item_prices'), array('test_store_purchase'));

		$item1->expects($this->once())
			->method('freeze_item_prices')
			->will($this->returnValue(5));

		$item2 = $this->getMock('Model_Test_Store_Purchase', array('freeze_item_prices'), array('test_store_purchase'));

		$item2->expects($this->once())
			->method('freeze_item_prices')
			->will($this->returnValue(10));

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
		$purchase = Jam::build('test_purchase');

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
		$purchase = $this->getMock('Model_Test_Purchase', array('freeze_item_prices', 'freeze_monetary'), array('test_purchase'));

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
		$purchase = Jam::find('test_purchase', 1);
 		$this->assertCount(2, $purchase->store_purchases[0]->items);

 		$existing_item = Jam::build('test_purchase_item', array(
 			'reference_id' => 1,
 			'reference_model' => 'test_product',
 			'quantity' => 3,
 			'type' => 'product',
 		));

 		$purchase->add_item(1, $existing_item);

 		$this->assertCount(2, $purchase->store_purchases[0]->items);
 		$this->assertEquals(4, $purchase->store_purchases[0]->items[0]->quantity);
	}

	/**
	 * @covers Model_Purchase::price_in
	 */
	public function test_price_in()
	{
		$purchase = $this->getMock('Model_Test_Purchase', array('monetary'), array('test_purchase'));
		$purchase->currency = 'EUR';
		$monetary = $this->getMock('Openbuildings\Monetary\Monetary');

		$purchase
			->expects($this->once())
			->method('monetary')
			->will($this->returnValue($monetary));

		$monetary
			->expects($this->once())
			->method('convert')
			->with($this->equalTo(50), $this->equalTo('EUR'), $this->equalTo('USD'))
			->will($this->returnValue(100));

		$purchase->price_in('USD', 50);
	}

	/**
	 * @covers Model_Purchase::total_price_in
	 */
	public function test_total_price_in()
	{
		$purchase = Jam::find('test_purchase', 1);

		$total_price_in_usd = $purchase
			->total_price_in('USD', array('product'));

		$this->assertEquals(400, $purchase->total_price(array('product')));
		$this->assertEquals(534.2, $total_price_in_usd);
	}


}