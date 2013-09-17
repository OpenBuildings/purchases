<?php

/**
 * @group model.collection.purchase
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Collection_PurchaseTest extends Testcase_Purchases {

	/**
	 * @covers Model_Collection_Purchase::attempt_first
	 */
	public function test_attempt_first()
	{
		$purchase = Jam::all('purchase')->where('currency', '=', 'GBP')->attempt_first(1);

		$this->assertInstanceOf('Model_Purchase', $purchase);

		$purchase = Jam::all('purchase')->where('currency', '=', 'USD')->attempt_first(1);

		$this->assertNull($purchase);

	}
}