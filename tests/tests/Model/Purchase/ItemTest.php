<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

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

		$item = Jam::build('purchase_item', array(
			'price' => 10,
			'brand_purchase' => 1,
			'model' => 'purchase_item_product',
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

		$new_item = Jam::build('purchase_item_product', array(
			'reference' => Jam::find('product', 1),
			'quantity' => 3,
		));

		$this->assertTrue($item->is_same($new_item));

		$new_item->model = 'purchase_item_shipping';

		$this->assertFalse($item->is_same($new_item));
	}

	/**
	 * @covers Model_Purchase_Item::group_by_brand_purchase
	 */
	public function test_group_by_brand_purchase()
	{
		$items = array(
			Jam::build('purchase_item', array('id' => 10, 'brand_purchase_id' => 1)),
			Jam::build('purchase_item', array('id' => 20, 'brand_purchase_id' => 1)),
			Jam::build('purchase_item', array('id' => 30, 'brand_purchase_id' => 12)),
			array('id' => 40, 'brand_purchase_id' => 13),
			array('id' => 50, 'brand_purchase_id' => 13),
			1,
			array('id' => 2),
		);

		$groups = Model_Purchase_Item::group_by_brand_purchase($items);

		$this->assertEquals(array('1', '12', '13'), array_keys($groups));

		$this->assertEquals(array(10, 20, 1, 2), $this->ids($groups['1']));
		$this->assertEquals(array(30), $this->ids($groups['12']));
		$this->assertEquals(array(40, 50), $this->ids($groups['13']));
	}

	public function data_compute_price()
	{
		$monetary = new Monetary('GBP', new Source_Static);

		return array(
			array(new Jam_Price(10, 'EUR'), 'GBP', $monetary, 'EUR', new Jam_Price(8.3965, 'GBP', $monetary, 'EUR')),
			array(new Jam_Price(10, 'GBP'), 'GBP', $monetary, 'GBP', new Jam_Price(10, 'GBP', $monetary, 'GBP')),
			array(new Jam_Price(5, 'USD'), 'EUR', $monetary, 'EUR', new Jam_Price(3.7545993842457, 'EUR', $monetary, 'EUR')),
		);
	}

	/**
	 * @dataProvider data_compute_price
	 * @covers Model_Purchase_Item::compute_price
	 */
	public function test_compute_price($price, $currency, $monetary, $display_currency, $expected)
	{
		$item = $this->getMock('Model_Purchase_Item_Product', array('currency', 'monetary', 'display_currency'), array('purchase_item_product'));

		$item
			->expects($this->once())
			->method('currency')
			->will($this->returnValue($currency));

		$item
			->expects($this->once())
			->method('monetary')
			->will($this->returnValue($monetary));

		$item
			->expects($this->once())
			->method('display_currency')
			->will($this->returnValue($display_currency));

		$item->reference = $this->getMock('Model_Product', array('price_for_purchase_item'), array('product'));

		$item->reference->expects($this->once())
			->method('price_for_purchase_item')
			->with($this->identicalTo($item))
			->will($this->returnValue($price));

		$this->assertEquals($expected, $item->compute_price());
	}

	/**
	 * @covers Model_Purchase_Item::monetary
	 */
	public function test_monetary()
	{
		$monetary = new Monetary;

		$brand_purchase = $this->getMock('Model_Brand_Purchase', array('monetary'), array('brand_purchase'));
		$brand_purchase
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$purchase_item = Jam::build('purchase_item', array('brand_purchase' => $brand_purchase));

		$this->assertSame($monetary, $purchase_item->monetary());
	}

	/**
	 * @covers Model_Purchase_Item::currency
	 */
	public function test_currency()
	{
		$brand_purchase = $this->getMock('Model_Brand_Purchase', array('currency'), array('brand_purchase'));
		$brand_purchase
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$purchase_item = Jam::build('purchase_item', array('brand_purchase' => $brand_purchase));

		$this->assertEquals('GBP', $purchase_item->currency());
		$this->assertEquals('EUR', $purchase_item->currency());
	}

	/**
	 * @covers Model_Purchase_Item::display_currency
	 */
	public function test_display_currency()
	{
		$brand_purchase = $this->getMock('Model_Brand_Purchase', array('display_currency'), array('brand_purchase'));
		$brand_purchase
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$purchase_item = Jam::build('purchase_item', array('brand_purchase' => $brand_purchase));

		$this->assertEquals('GBP', $purchase_item->display_currency());
		$this->assertEquals('EUR', $purchase_item->display_currency());
	}

	/**
	 * @covers Model_Purchase_Item::price
	 */
	public function test_price()
	{
		$item = $this->getMock('Model_Purchase_Item', array('compute_price'), array('purchase_item'));
		$item->brand_purchase = Jam::find('brand_purchase', 1);
		$price = new Jam_Price(10, 'USD');

		$item->expects($this->exactly(2))
			->method('compute_price')
			->will($this->returnValue($price));

		$this->assertSame($price, $item->price());

		$item->freeze();

		$item->price = new Jam_Price(100, 'EUR');

		$this->assertSame($item->price, $item->price());
	}

	/**
	 * @covers Model_Purchase_Item::get_reference_paranoid
	 */
	public function test_get_reference_paranoid()
	{
		$purchase_item = Jam::find('purchase_item', 1);

		$this->assertInstanceOf('Model_Product', $purchase_item->reference);
		$this->assertInstanceOf('Model_Product', $purchase_item->get_reference_paranoid());

		Jam::find('product', 1)->delete();

		$purchase_item = Jam::find('purchase_item', 1);

		$this->assertNull($purchase_item->reference);

		$purchase_item = Jam::find('purchase_item', 1);

		$this->assertInstanceOf('Model_Product', $purchase_item->get_reference_paranoid());
	}

	/**
	 * @covers Model_Purchase_Item::is_refunded
	 */
	public function test_is_refunded()
	{
		$refund = $this->getMock('Model_Brand_Refund', array('has_purchase_item'), array('brand_refund'));

		$item = Jam::build('purchase_item', array(
			'brand_purchase' => array(
				'refunds' => array($refund),
			)
		));

		$refund->expects($this->exactly(2))
			->method('has_purchase_item')
			->with($this->identicalTo($item))
			->will($this->onConsecutiveCalls(FALSE, TRUE));

		$this->assertFalse($item->is_refunded());
		$this->assertTrue($item->is_refunded());
	}

	/**
	 * @covers Model_Purchase_Item::total_price
	 */
	public function test_total_price()
	{
		$item = $this->getMock('Model_Purchase_Item', array('price'), array('purchase_item'));

		// Price should be rounded for payment processors
		$price = new Jam_Price(65.795, 'USD');

		$item->expects($this->exactly(3))
			->method('price')
			->will($this->returnValue($price));

		$item->quantity = 1;
		$this->assertEquals(65.8, $item->total_price()->amount());

		$item->quantity = 2;
		$this->assertEquals(131.6, $item->total_price()->amount());

		$item->quantity = 3;
		$this->assertEquals(197.4, $item->total_price()->amount());
	}

	/**
	 * @covers Model_Purchase_Item::type
	 */
	public function test_type()
	{
		$item = Jam::build('purchase_item_product');
		$this->assertSame('product', $item->type());

		$item->model = '';
		$this->assertNull($item->type());

		$item->model = FALSE;
		$this->assertNull($item->type());

		$item->model = NULL;
		$this->assertNull($item->type());

		$item->model = 'purchase_dsfs';
		$this->assertSame('purchase_dsfs', $item->type());

		$item->model = 'purchase_item_shipping';
		$this->assertSame('shipping', $item->type());
	}

	/**
	 * @covers Model_Purchase_Item::compute_price
	 */
	public function test_compute_price_expects_jam_price()
	{
		$item = $this->getMock('Model_Purchase_Item', array(
			'get_price'
		), array(
			'purchase_item'
		));

		$item
			->expects($this->once())
			->method('get_price')
			->will($this->returnValue(10.00));

		$product = $this->getMock('Model_Product', array(
			'__toString',
		), array(
			'product'
		));

		$product
			->expects($this->once())
			->method('__toString')
			->will($this->returnValue('abc'));

		$item->reference = $product;

		$this->setExpectedException('Kohana_Exception', 'Compute price expects the reference abc to return a Jam_Price');
		$item->compute_price();
	}

	/**
	 * @covers Model_Purchase_Item::get_price
	 */
	public function test_get_price_throws_exception()
	{
		$this->setExpectedException('BadMethodCallException', 'You must implement get_price()');
		Jam::build('purchase_item')->get_price();
	}

	public function data_refunded_amount()
	{
		return array(
			array(
				array(),
				new Jam_Price(0, 'GBP'),
			),
			array(
				array(
					array('amount' => new Jam_Price(0, 'GBP')),
				),
				new Jam_Price(0, 'GBP'),
			),
			array(
				array(
					array('amount' => new Jam_Price(50, 'GBP')),
				),
				new Jam_Price(50, 'GBP'),
			),
			array(
				array(
					array('amount' => new Jam_Price(150, 'GBP')),
					array('amount' => new Jam_Price(200, 'GBP')),
					array('amount' => new Jam_Price(33, 'GBP')),
				),
				new Jam_Price(383, 'GBP'),
			),
			array(
				array(
					array('amount' => new Jam_Price(-150, 'GBP')),
					array('amount' => new Jam_Price(200, 'GBP')),
					array('amount' => new Jam_Price(33, 'GBP')),
				),
				new Jam_Price(83, 'GBP'),
			),
		);
	}

	/**
	 * @dataProvider data_refunded_amount
	 * @covers Model_Purchase_Item::refunded_amount
	 */
	public function test_refunded_amount($refund_items, $expected_refunded_amount)
	{
		$purchase_item = $this->getMock('Model_Purchase_Item_Product', array(
			'currency',
			'monetary',
			'display_currency',
		), array(
			'purchase_item_product',
		));

		$purchase_item
			->expects($this->once())
			->method('currency')
			->will($this->returnValue('GBP'));

		$purchase_item->refund_items = $refund_items;

		$this->assertEquals(
			$expected_refunded_amount,
			$purchase_item->refunded_amount()
		);
	}
}
