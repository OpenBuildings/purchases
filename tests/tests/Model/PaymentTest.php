<?php

/**
 * @group model.payment
 */
class Model_PaymentTest extends Testcase_Purchases {

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
}