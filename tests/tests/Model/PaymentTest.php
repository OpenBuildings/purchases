<?php

/**
 * @group model.payment
 */
class Model_PaymentTest extends Testcase_Purchases {

	/**
	 * @covers Model_Payment::authorize
	 */
	public function test_authorize()
	{
		$params = array('test', 'test2');
		$payment = $this->getMock('Model_Payment', array('authorize_processor', 'save'), array('payment'));

		$payment
			->expects($this->once())
			->method('authorize_processor')
			->with($this->equalTo($params));

		$payment
			->expects($this->once())
			->method('save');

		$payment->authorize($params);

		$this->assertTrue($payment->before_authorize_called);
		$this->assertTrue($payment->after_authorize_called);
	}

	/**
	 * @covers Model_Payment::execute
	 */
	public function test_execute()
	{
		$params = array('test', 'test2');
		$payment = $this->getMock('Model_Payment', array('execute_processor', 'save'), array('payment'));

		$payment
			->expects($this->once())
			->method('execute_processor')
			->with($this->equalTo($params));

		$payment
			->expects($this->once())
			->method('save');

		$payment->execute($params);

		$this->assertTrue($payment->before_execute_called);
		$this->assertTrue($payment->after_execute_called);
	}

	/**
	 * @covers Model_Payment::refund
	 */
	public function test_refund()
	{
		$params = array('test', 'test2');
		$refund = $this->getMock('Model_Store_Refund', array('save'), array('store_refund'));
		$payment = $this->getMock('Model_Payment', array('refund_processor'), array('payment'));

		$payment
			->expects($this->once())
			->method('refund_processor')
			->with($this->identicalTo($refund), $this->equalTo($params));

		$refund
			->expects($this->once())
			->method('save');

		$payment->refund($refund, $params);

		$this->assertTrue($payment->before_refund_called);
		$this->assertTrue($payment->after_refund_called);
	}

	/**
	 * @covers Model_Payment::transaction_fee
	 */
	public function test_transaction_fee()
	{
		$payment = Jam::build('payment');
		$result = $payment->transaction_fee(new Jam_Price(10, 'GBP'));
		$this->assertNull($result);
	}

	/**
	 * @covers Model_Payment::execute_processor
	 * @expectedException Kohana_Exception
	 * @expectedExceptionMessage This payment does not support execute
	 */
	public function test_execute_processor()
	{
		$payment = Jam::build('payment');
		$payment->execute_processor();
	}

	/**
	 * @covers Model_Payment::authorize_processor
	 * @expectedException Kohana_Exception
	 * @expectedExceptionMessage This payment does not support authorize
	 */
	public function test_authorize_processor()
	{
		$payment = Jam::build('payment');
		$payment->authorize_processor();
	}

	/**
	 * @covers Model_Payment::refund_processor
	 * @expectedException Kohana_Exception
	 * @expectedExceptionMessage This payment does not support refund
	 */
	public function test_refund_processor()
	{
		$payment = Jam::build('payment');
		$refund = Jam::build('store_refund');
		$payment->refund_processor($refund);
	}
}