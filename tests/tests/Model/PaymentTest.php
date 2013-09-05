<?php

/**
 * Functest_TestsTest 
 *
 * @group model.payment
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_PaymentTest extends Testcase_Purchases {

	/**
	 * @covers Model_Payment::authorize
	 * @covers Model_Payment::execute
	 */
	public function test_public_methods()
	{
		$payment = Jam::build('payment');

		$payment2 = clone $payment;
		$payment2
			->authorize()
			->execute();

		$this->assertEquals($payment, $payment2);

		$this->assertNull($payment2->authorize_url());
	}

	/**
	 * @covers Model_Payment::refund
	 * @expectedException Kohana_Exception
	 */
	public function test_refund()
	{
		$payment = Jam::build('payment');
		$payment->refund(Jam::build('store_refund'));
	}
}