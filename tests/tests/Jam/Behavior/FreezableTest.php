<?php

/**
 * @group jam
 * @group jam.behavior
 * @group jam.behavior.freezable
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Jam_Behavior_FreezableTest extends Testcase_Purchases {

	/**
	 * @covers Jam_Behavior_Freezable::call_associations_method
	 */
	public function test_call_associations_method()
	{
		$purchase = Jam::find('purchase', 1);

		$store_purchase1 = $this->getMock('Model_Store_Purchase', array('test_method'), array('store_purchase'));
		$store_purchase1
			->expects($this->once())
			->method('test_method');

		$store_purchase2 = $this->getMock('Model_Store_Purchase', array('test_method'), array('store_purchase'));
		$store_purchase2
			->expects($this->once())
			->method('test_method');

		$purchase->store_purchases = array(
			$store_purchase1,
			$store_purchase2,
		);

		$behaviors = $purchase->meta()->behaviors();

		$behaviors['freezable']->call_associations_method($purchase, 'test_method');
	}

	/**
	 * @covers Jam_Behavior_Freezable::model_call_freeze
	 */
	public function test_model_call_freeze()
	{
		$purchase = $this->getMock('Model_Purchase', array('monetary'), array('purchase'));
		$monetary = new OpenBuildings\Monetary\Monetary('GBP', new OpenBuildings\Monetary\Source_Static());

		$purchase
			->expects($this->once())
			->method('monetary')
				->will($this->returnValue($monetary));

		$store_purchase1 = $this->getMock('Model_Store_Purchase', array('freeze'), array('store_purchase'));
		$store_purchase1
			->expects($this->once())
			->method('freeze');

		$store_purchase2 = $this->getMock('Model_Store_Purchase', array('freeze'), array('store_purchase'));
		$store_purchase2
			->expects($this->once())
			->method('freeze');

		$purchase->store_purchases = array(
			$store_purchase1,
			$store_purchase2,
		);

		$result = $purchase->freeze();
		$this->assertSame($purchase, $result);

		$this->assertSame($monetary, $purchase->monetary);
		$this->assertTrue($purchase->is_frozen());
	}

	/**
	 * @covers Jam_Behavior_Freezable::model_after_check
	 * @covers Jam_Behavior_Freezable::model_after_save
	 * @covers Jam_Behavior_Freezable::model_call_is_just_frozen
	 */
	public function test_model_after_check()
	{
		$purchase = Jam::find('purchase', 2);

		$this->assertTrue($purchase->check());

		$purchase
			->freeze()
			->save();

		$purchase->store_purchases->build(array(
			'store' => 1,
			'items' => array(
				array(
					'quantity' => 1,
					'price' => 10,
					'type' => 'shipping',
					'reference_model' => 'variation',
					'reference' => 2,
					'is_payable' => TRUE,
				)
			)
		));

		$purchase->store_purchases[0]->items[0]->price = 122;

		$this->assertFalse($purchase->check());
		
		$purchase->unfreeze();

		$this->assertTrue($purchase->check());
	}

	/**
	 * @covers Jam_Behavior_Freezable::model_call_unfreeze
	 */
	public function test_model_call_unfreeze()
	{
		$purchase = Jam::build('purchase');

		$store_purchase1 = $this->getMock('Model_Store_Purchase', array('unfreeze'), array('store_purchase'));
		$store_purchase1
			->expects($this->once())
			->method('unfreeze');

		$store_purchase2 = $this->getMock('Model_Store_Purchase', array('unfreeze'), array('store_purchase'));
		$store_purchase2
			->expects($this->once())
			->method('unfreeze');

		$purchase->store_purchases = array(
			$store_purchase1,
			$store_purchase2,
		);

		$result = $purchase->unfreeze();
		$this->assertSame($purchase, $result);

		$this->assertNull($purchase->monetary);
		$this->assertFalse($purchase->is_frozen());
	}

	/**
	 * @covers Jam_Behavior_Freezable::model_call_is_frozen
	 */
	public function test_model_call_is_frozen()
	{
		$purchase = Jam::find('purchase', 2);

		$purchase->freeze();

		$this->assertTrue($purchase->is_frozen());
		$this->assertTrue($purchase->store_purchases[0]->is_frozen());
		$this->assertTrue($purchase->store_purchases[0]->items[0]->is_frozen());

		$purchase->unfreeze();

		$this->assertFalse($purchase->is_frozen());
		$this->assertFalse($purchase->store_purchases[0]->is_frozen());
		$this->assertFalse($purchase->store_purchases[0]->items[0]->is_frozen());
	}

	/**
	 * @covers Jam_Behavior_Freezable::model_call_is_just_frozen
	 */
	public function test_model_call_is_just_frozen()
	{
		$purchase = Jam::find('purchase', 2);

		$this->assertFalse($purchase->is_just_frozen());
		$this->assertFalse($purchase->store_purchases[0]->is_just_frozen());
		$this->assertFalse($purchase->store_purchases[0]->items[0]->is_just_frozen());

		$purchase->freeze();

		$this->assertTrue($purchase->is_just_frozen());
		$this->assertTrue($purchase->store_purchases[0]->is_just_frozen());
		$this->assertTrue($purchase->store_purchases[0]->items[0]->is_just_frozen());
	
		$purchase->save();			

		$this->assertFalse($purchase->is_just_frozen());
		$this->assertFalse($purchase->store_purchases[0]->is_just_frozen());
		$this->assertFalse($purchase->store_purchases[0]->items[0]->is_just_frozen());
	}
}