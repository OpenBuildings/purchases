<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Paypal_AdaptiveTest extends Testcase_Purchases {

	/**
	 * @covers Jam_Behavior_Paypal_Adaptive::initialize
	 */
	public function test_initialize()
	{
		$store = Jam::find('store', 2);

		$this->assertInstanceOf('Jam_Field_String', Jam::meta('store')->field('paypal_email'));

		$this->assertTrue($store->check());

		$store->paypal_email = 'abc';
		$this->assertFalse($store->check());

		$store->paypal_email = 'example@example.com';
		$this->assertTrue($store->check());
	}
}
