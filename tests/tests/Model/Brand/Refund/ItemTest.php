<?php

use OpenBuildings\Monetary\Monetary;

/**
 * @group model
 * @group model.brand_refund_item
 *
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Brand_Refund_ItemTest extends Testcase_Purchases {

	public $purchase;
	public $brand_purchase;
	public $refund;

	public function setUp()
	{
		parent::setUp();

		$this->purchase = Jam::find('purchase', 1);
		$this->brand_purchase = $this->purchase->brand_purchases[0];
		$this->refund = $this->brand_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $this->brand_purchase->items[0])
			)
		));
	}

	/**
	 * @covers Model_Brand_Refund_Item::validate
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
	 * @covers Model_Brand_Refund_Item::amount
	 */
	public function test_amount()
	{
		$item = $this->refund->items[0];

		$this->assertEquals($this->brand_purchase->items[0]->price(), $item->amount());

		$item->amount = 5;
		$this->assertEquals(new Jam_Price(5, 'EUR', $item->monetary(), 'EUR'), $item->amount());
	}

	/**
	 * @covers Model_Brand_Refund_Item::currency
	 */
	public function test_currency()
	{
		$brand_refund = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array('currency'))
			->setConstructorArgs(array('brand_refund'))
			->getMock();
		$brand_refund
			->expects($this->exactly(2))
				->method('currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$item = Jam::build('brand_refund_item', array('brand_refund' => $brand_refund));

		$this->assertEquals('GBP', $item->currency());
		$this->assertEquals('EUR', $item->currency());
	}

	/**
	 * @covers Model_Brand_Refund_Item::purchase_item_price
	 */
	public function test_purchase_item_price()
	{
		$purchase_item = Jam::find('purchase_item', 1);

		$item = Jam::build('brand_refund_item', array('purchase_item' => $purchase_item));

		$this->assertEquals($purchase_item->price(), $item->purchase_item_price());
	}

	/**
	 * @covers Model_Brand_Refund_Item::display_currency
	 */
	public function test_display_currency()
	{
		$brand_refund = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array('display_currency'))
			->setConstructorArgs(array('brand_refund'))
			->getMock();
		$brand_refund
			->expects($this->exactly(2))
				->method('display_currency')
				->will($this->onConsecutiveCalls('GBP', 'EUR'));

		$item = Jam::build('brand_refund_item', array('brand_refund' => $brand_refund));

		$this->assertEquals('GBP', $item->display_currency());
		$this->assertEquals('EUR', $item->display_currency());
	}

	/**
	 * @covers Model_Brand_Refund_Item::is_full_amount
	 */
	public function test_is_full_amount()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$item = $this->getMockBuilder('Model_Brand_Refund_Item')
			->setMethods(array('currency', 'display_currency', 'monetary', 'purchase_item_price'))
			->setConstructorArgs(array('brand_refund_item'))
			->getMock();

		$item
			->expects($this->exactly(2))
				->method('purchase_item_price')
				->will($this->returnValue(new Jam_Price(10, 'GBP')));

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

		$this->assertTrue($item->is_full_amount());

		$item->amount = 5;

		$this->assertFalse($item->is_full_amount());

		$item->amount = 10;

		$this->assertTrue($item->is_full_amount());
	}

	/**
	 * @covers Model_Brand_Refund_Item::monetary
	 */
	public function test_monetary()
	{
		$monetary = new OpenBuildings\Monetary\Monetary;

		$brand_refund = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array('monetary'))
			->setConstructorArgs(array('brand_refund'))
			->getMock();
		$brand_refund
			->expects($this->once())
				->method('monetary')
				->will($this->returnValue($monetary));

		$item = Jam::build('brand_refund_item', array('brand_refund' => $brand_refund));

		$this->assertSame($monetary, $item->monetary());
	}
}
