<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Payment extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()

			->bind('model.before_first_operation', array($this, 'before_first_operation'))
			->bind('model.after_first_operation', array($this, 'after_first_operation'))

			->bind('model.before_authorize', array($this, 'before_authorize'))
			->bind('model.after_authorize', array($this, 'after_authorize'))

			->bind('model.before_execute', array($this, 'before_execute'))
			->bind('model.after_execute', array($this, 'after_execute'))

			->bind('model.pay', array($this, 'pay'))

			->bind('model.before_refund', array($this, 'before_refund'))
			->bind('model.after_refund', array($this, 'after_refund'))

			->bind('model.before_full_refund', array($this, 'before_full_refund'))
			->bind('model.after_full_refund', array($this, 'after_full_refund'));

	}

	public function before_first_operation(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_first_operation_called = TRUE;
	}

	public function after_first_operation(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_first_operation_called = TRUE;
	}

	public function before_authorize(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_authorize_called = TRUE;
	}

	public function after_authorize(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_authorize_called = TRUE;
	}

	public function before_execute(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_execute_called = TRUE;
	}

	public function after_execute(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_execute_called = TRUE;
	}

	public function pay(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->pay_called = TRUE;
	}

	public function before_refund(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_refund_called = TRUE;
	}

	public function after_refund(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_refund_called = TRUE;
	}

	public function before_full_refund(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_full_refund_called = TRUE;
	}

	public function after_full_refund(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_full_refund_called = TRUE;
	}
}
