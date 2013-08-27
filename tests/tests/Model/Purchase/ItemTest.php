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
	 * @covers Monedel_Purchase_Item::is_same
	 */
	public function test_is_same()
	{
		$item = Jam::find('test_purchase_item', 1);

		$new_item = Jam::build('test_purchase_item', array(
			'reference' => Jam::find('test_product', 1),
			'quantity' => 3,
			'type' => 'product',
		));

		$this->assertTrue($item->is_same($new_item));

		$new_item->type = 'shipping';

		$this->assertFalse($item->is_same($new_item));
	}

	/**
	 * @covers Monedel_Purchase_Item::compute_price
	 */
	public function test_compute_price()
	{
		$item = Jam::find('test_purchase_item', 1);
		$item->reference = $this->getMock('Model_Test_Product', array('price', 'currency'), array('test_product'));

		$item->reference->expects($this->exactly(2))
			->method('price')
			->will($this->returnValue(10));

		$item->reference->expects($this->at(1))
			->method('currency')
			->will($this->returnValue('EUR'));

		$item->reference->expects($this->at(3))
			->method('currency')
			->will($this->returnValue('USD'));

		$this->assertSame(10.0, $item->compute_price(), 'Should be EUR -> EUR conversion');
		$this->assertSame(7.4878322725571, $item->compute_price(), 'Should be EUR -> USD conversion');
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Monedel_Purchase_Item::store_purchase_insist
	 */
	public function test_store_purchase_insist()
	{
		$item = Jam::find('test_purchase_item', 1);
		$this->assertInstanceOf('Model_Store_Purchase', $item->store_purchase_insist());

		$item->store_purchase = NULL;
		$item->store_purchase_insist();
	}

	/**
	 * @expectedException Kohana_Exception
	 * @covers Monedel_Purchase_Item::purchase_insist
	 */
	public function test_purchase_insist()
	{
		$item = Jam::find('test_purchase_item', 1);
		$this->assertInstanceOf('Model_Purchase', $item->purchase_insist());

		$item->store_purchase->purchase = NULL;
		$item->purchase_insist();
	}

	/**
	 * @covers Monedel_Purchase_Item::monetary
	 */
	public function test_monetary()
	{
		$item = Jam::find('test_purchase_item', 1);

		$this->assertInstanceOf('Openbuildings\Monetary\Monetary', $item->monetary());
		$this->assertSame($item->store_purchase->purchase->monetary(), $item->monetary());
	}

	/**
	 * @covers Monedel_Purchase_Item::price
	 */
	public function test_price()
	{
		$item = $this->getMock('Model_Test_Purchase_Item', array('compute_price'), array('test_purchase_item'));

		$item->expects($this->once())
			->method('compute_price')
			->will($this->returnValue(15.90));

		$this->assertEquals(15.90, $item->price());
		
		$item->price = 100.20;

		$this->assertEquals(100.20, $item->price());
	}

	/**
	 * @covers Model_Purcahse_Item::freeze_price
	 */
	public function test_freeze_price()
	{
		$item = $this->getMock('Model_Test_Purchase_Item', array('compute_price'), array('test_purchase_item'));

		$item->expects($this->once())
			->method('compute_price')
			->will($this->returnValue(15.90));

		$this->assertNull($item->price);

		$item->freeze_price();

		$this->assertEquals(15.90, $item->price);
	}

	/**
	 * @covers Model_Purcahse_Item::total_price
	 */
	public function test_total_price()
	{
		$item = $this->getMock('Model_Test_Purchase_Item', array('price'), array('test_purchase_item'));

		$item->expects($this->exactly(2))
			->method('price')
			->will($this->returnValue(15.90));

		$item->quantity = 2;
		$this->assertEquals(15.90*2, $item->total_price());

		$item->quantity = 3;
		$this->assertEquals(15.90*3, $item->total_price());
	}
}