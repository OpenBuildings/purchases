<?php

/**
 * @group exception
 * @group exception.payment
 */
class Exception_PaymentTest extends Testcase_Purchases {

	/**
	 * @covers Exception_Payment::__construct
	 * @covers Exception_Payment::data
	 */
	public function test_construct()
	{
		$previous = new Exception('Test');
		$exception = new Exception_Payment('Test :param', array(':param' => 'new'), 5, $previous, array('data' => 'data'));

		$this->assertEquals('Test new', $exception->getMessage());
		$this->assertEquals(5, $exception->getCode());
		$this->assertEquals($previous, $exception->getPrevious());
		$this->assertEquals(array('data' => 'data'), $exception->data());
	}
}
