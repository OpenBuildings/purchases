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
		$payment = $this->getMock('Model_Payment', array('authorize_processor'), array('payment'));
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));
		$payment->purchase = $purchase;

		$payment
			->expects($this->once())
			->method('authorize_processor')
			->with($this->equalTo($params));

		$purchase
			->expects($this->once())
			->method('save');

		$payment->authorize($params);

		$this->assertTrue($payment->before_first_operation_called);
		$this->assertTrue($payment->before_authorize_called);
		$this->assertTrue($payment->after_authorize_called);
	}

	/**
	 * @covers Model_Payment::execute
	 */
	public function test_execute()
	{
		$params = array('test', 'test2');
		$payment = $this->getMock('Model_Payment', array('execute_processor', 'loaded'), array('payment'));
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));
		$payment->purchase = $purchase;

		$payment
			->expects($this->once())
			->method('execute_processor')
			->with($this->equalTo($params));

		$payment
			->expects($this->once())
			->method('loaded')
			->will($this->returnValue(FALSE));

		$payment->status = Model_Payment::PAID;

		$purchase
			->expects($this->once())
			->method('save');

		$payment->execute($params);

		$this->assertTrue($payment->before_first_operation_called);
		$this->assertTrue($payment->before_execute_called);
		$this->assertTrue($payment->after_execute_called);
		$this->assertTrue($payment->pay_called);
	}

	/**
	 * @covers Model_Payment::execute
	 */
	public function test_execute_not_call_before_first_operation_called()
	{
		$params = array('test', 'test2');
		$payment = $this->getMock('Model_Payment', array('execute_processor', 'loaded'), array('payment'));
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));
		$payment->purchase = $purchase;

		$payment
			->expects($this->once())
			->method('execute_processor')
			->with($this->equalTo($params));

		$payment
			->expects($this->once())
			->method('loaded')
			->will($this->returnValue(TRUE));

		$purchase
			->expects($this->once())
			->method('save');

		$payment->execute($params);

		$this->assertNull($payment->before_first_operation_called);
		$this->assertTrue($payment->before_execute_called);
		$this->assertTrue($payment->after_execute_called);
		$this->assertNull($payment->pay_called);
	}

	/**
	 * @covers Model_Payment::refund
	 */
	public function test_refund()
	{
		$params = array('test', 'test2');
		$refund = $this->getMock('Model_Store_Refund', array(
			'save',
		), array(
			'store_refund',
		));

		$payment = $this->getMock('Model_Payment', array(
			'refund_processor',
		), array(
			'payment',
		));

		$store_purchase = $this->getMock('Model_Store_Purchase', array(
			'save',
			'freeze',
		), array(
			'store_purchase'
		));

		$payment
			->expects($this->once())
			->method('refund_processor')
			->with($this->identicalTo($refund), $this->equalTo($params));

		$refund
			->expects($this->once())
			->method('save')
			->will($this->returnValue($refund));

		$store_purchase
			->expects($this->once())
			->method('save')
			->will($this->returnValue($store_purchase));

		$store_purchase
			->expects($this->once())
			->method('freeze')
			->will($this->returnValue($store_purchase));

		$store_purchase->purchase = Jam::build('purchase');

		$refund->store_purchase = $store_purchase;
		$refund->transaction_status = Model_Store_Refund::TRANSACTION_REFUNDED;

		$payment->refund($refund, $params);

		$this->assertTrue($payment->before_refund_called);
		$this->assertTrue($payment->after_refund_called);
	}

	/**
	 * @covers Model_Payment::full_refund
	 */
	public function test_full_refund()
	{
		$params = array('test', 'test2');
		$refund = $this->getMock('Model_Store_Refund', array(
			'save',
		), array(
			'store_refund',
		));

		$refund2 = $this->getMock('Model_Store_Refund', array(
			'save',
		), array(
			'store_refund',
		));

		$payment = $this->getMock('Model_Payment', array(
			'multiple_refunds_processor',
		), array(
			'payment',
		));

		$store_purchase = $this->getMock('Model_Store_Purchase', array(
			'save',
			'freeze',
		), array(
			'store_purchase'
		));

		$store_purchase2 = $this->getMock('Model_Store_Purchase', array(
			'save',
			'freeze',
		), array(
			'store_purchase'
		));

		$payment
			->expects($this->once())
			->method('multiple_refunds_processor')
			->with($this->identicalTo(array($refund, $refund2)), $this->equalTo($params));

		$refund
			->expects($this->once())
			->method('save')
			->will($this->returnValue($refund));

		$refund2
			->expects($this->once())
			->method('save')
			->will($this->returnValue($refund2));

		$store_purchase
			->expects($this->once())
			->method('save')
			->will($this->returnValue($store_purchase));

		$store_purchase
			->expects($this->once())
			->method('freeze')
			->will($this->returnValue($store_purchase));

		$store_purchase2
			->expects($this->once())
			->method('save')
			->will($this->returnValue($store_purchase2));

		$store_purchase2
			->expects($this->once())
			->method('freeze')
			->will($this->returnValue($store_purchase2));

		$store_purchase->purchase = Jam::build('purchase');
		$store_purchase2->purchase = $store_purchase->purchase;

		$refund->store_purchase = $store_purchase;
		$refund->transaction_status = Model_Store_Refund::TRANSACTION_REFUNDED;

		$refund2->store_purchase = $store_purchase2;
		$refund2->transaction_status = Model_Store_Refund::TRANSACTION_REFUNDED;

		$payment->full_refund(array($refund, $refund2), $params);

		$this->assertTrue($payment->before_full_refund_called);
		$this->assertTrue($payment->after_full_refund_called);
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

	/**
	 * @covers Model_Payment::multiple_refunds_processor
	 * @expectedException Kohana_Exception
	 * @expectedExceptionMessage This payment does not support multiple refunds
	 */
	public function test_multiple_refunds_processor()
	{
		$payment = Jam::build('payment');
		$refund = Jam::build('store_refund');
		$payment->multiple_refunds_processor(array($refund));
	}
}
