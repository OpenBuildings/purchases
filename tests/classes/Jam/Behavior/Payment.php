<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Payment extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()

			->bind('model.before_purchase', array($this, 'before_purchase'))
			->bind('model.after_purchase', array($this, 'after_purchase'))

			->bind('model.before_complete_purchase', array($this, 'before_complete_purchase'))
			->bind('model.after_complete_purchase', array($this, 'after_complete_purchase'))

			->bind('model.pay', array($this, 'pay'))

			->bind('model.before_refund', array($this, 'before_refund'))
			->bind('model.after_refund', array($this, 'after_refund'))

			->bind('model.before_full_refund', array($this, 'before_full_refund'))
			->bind('model.after_full_refund', array($this, 'after_full_refund'));

	}

	public function before_purchase(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_purchase_called = TRUE;
	}

	public function after_purchase(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_purchase_called = TRUE;
	}

	public function before_complete_purchase(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->before_complete_purchase_called = TRUE;
	}

	public function after_complete_purchase(Model_Payment $payment, Jam_Event_Data $data)
	{
		$payment->after_complete_purchase_called = TRUE;
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
