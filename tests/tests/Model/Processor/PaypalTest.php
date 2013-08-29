<?php

/**
 * Functest_TestsTest 
 *
 * @group processor
 * @group processor.paypal
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Processor_PaypalTest extends Testcase_Purchases_Spiderling {
	
	/**
	 * @covers Processor_Paypal::api
	 */
	public function test_api()
	{
		$this->env->backup_and_set(array(
			'Processor_Paypal::$_api' => NULL,
			'purchases.processor.paypal.oauth' => array(
				'client_id' => getenv('PHP_PAYPAL_CLIENT_ID'), 
				'secret' => getenv('PHP_PAYPAL_SECRET')
			),
		));

		$api = Processor_Paypal::api();
		$this->assertNotNull($api->getrequestId());
	}

	/**
	 * @covers Processor_Paypal::execute
	 * @driver phantomjs
	 */
	public function test_execute()
	{
		$this->env->backup_and_set(array(
			'Processor_Paypal::$_api' => NULL,
			'purchases.processor.paypal.oauth' => array(
				'client_id' => getenv('PHP_PAYPAL_CLIENT_ID'), 
				'secret' => getenv('PHP_PAYPAL_SECRET')
			),
		));

		$purchase = Jam::find('test_purchase', 2);
		$purchase
			->store_purchases[0]
				->items
					->build(array(
						'quantity' => 1, 
						'price' => -10, 
						'type' => 'promotion', 
						'is_discount' => TRUE,
						'is_payable' => TRUE,
					));

		$processor = new Processor_Paypal('http://example.com?result=success', 'http://example.com?result=cancel');

		$purchase
			->pay($processor)
			->save();

		$this->assertNotNull($purchase->payment);
		$this->assertEquals('paypal', $purchase->payment->method);
		$this->assertNotEquals('', $purchase->payment->payment_id);
		
		$this
			->visit($processor->next_url())
			->fill_in('Email', 'buyer@openbuildings.com')
			->fill_in('PayPal password', '12345678')
			->click_button('Log In');

		$this
			->next_wait_time(5000)
			->assertHasCss('h2', array('text' => 'Review your information'))
			->assertHasCss('span.grandTotal', array('text' => $purchase->total_price(array('is_payable' => TRUE))))
			->click_button('Continue');

		$this
			->assertHasNoCss('h2', array('text' => 'Review your information'));

		$query = parse_url($this->current_url(), PHP_URL_QUERY);	
		parse_str($query, $query);

		$this->assertEquals('success', $query['result']);

		$purchase
			->payment
				->complete(array('payer_id' => $query['PayerID']))
				->save();

		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);
	}
}