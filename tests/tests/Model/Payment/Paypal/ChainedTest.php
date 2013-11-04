<?php

use OpenBuildings\PayPal\Payment_Adaptive;

/**
 * @group model.payment_paypal_chained
 */
class Model_Payment_Paypal_ChainedTest extends Testcase_Purchases_Spiderling {

	public function setUp()
	{
		parent::setUp();

		$this->env->backup_and_set(array(
			'Paypal::$_api' => NULL,
			'purchases.processor.paypal.adaptive.auth' => array(
				'username' => getenv('PHP_PAYPAL_ADAPTIVE_USERNAME'), 
				'password' => getenv('PHP_PAYPAL_ADAPTIVE_PASSWORD'), 
				'signature' => getenv('PHP_PAYPAL_ADAPTIVE_SIGNATURE'), 
				'app_id' => getenv('PHP_PAYPAL_ADAPTIVE_APP_ID'), 
			),
		));
	}

	/**
	 * @covers Model_Payment_Paypal_Chained::convert_purchase
	 * @covers Model_Payment_Paypal_Chained::receivers
	 */
	public function test_convert_purchase()
	{
		$purchase = Jam::find('purchase', 1);

		$promo = $purchase->store_purchases[0]->items->create(array(
			'quantity' => 1,
			'price' => -10,
			'type' => 'promotion',
			'is_discount' => TRUE,
			'is_payable' => TRUE,
		));

		$product = Jam::create('product', array('price' => 100, 'name' => 'product 1', 'currency' => 'GBP', 'store' => Jam::find('store', 2)));
		$item = Jam::build('purchase_item', array('reference' => $product, 'type' => 'product', 'is_payable' => TRUE));
		$purchase->add_item(Jam::find('store', 2), $item);

		$payment = Model_Payment_Paypal_Chained::convert_purchase($purchase, array(
			'success_url' => 'http://example.com/success',
			'cancel_url' => 'http://example.com/cancel',
			'description' => 'test transaction description'
		));

		$this->assertInstanceOf('OpenBuildings\PayPal\Payment_Adaptive_Chained', $payment);

		$this->assertSame('EUR', $payment->config('currency'));
		$this->assertSame(Payment_Adaptive::FEES_PAYER_EACHRECEIVER, $payment->config('fees_payer'));
		$this->assertSame(array(
			'total_price' => '509.26',
			'receivers' => array(
				array(
					'email' => 'teststore@clippings.com',
					'amount' => '390.00'
				),
				array(
					'email' => 'test-store@clippings.com',
					'amount' => '119.26'
				),
			)
		), $payment->order());
		$this->assertSame('http://example.com/success', $payment->return_url());
		$this->assertSame('http://example.com/cancel', $payment->cancel_url());
	}

	/**
	 * @covers Model_Payment_Paypal_Chained::execute_processor
	 * @covers Model_Payment_Paypal_Chained::authorize_processor
	 * @covers Model_Payment_Paypal_Chained::authorize_url
	 * @covers Model_Payment_Paypal_Chained::refund_processor
	 * @covers Model_Payment_Paypal_Chained::config_auth
	 * @driver phantomjs
	 */
	public function test_execute()
	{
		$purchase = Jam::find('purchase', 2);
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

		$purchase
			->build('payment', array('model' => 'payment_paypal_chained'))
				->authorize(array(
					'success_url' => 'http://example.com?result=success',
					'cancel_url' => 'http://example.com?result=cancel'
				));

		$this->assertInstanceOf('Model_Payment_Paypal_Chained', $purchase->payment);
		$this->assertNotEquals('', $purchase->payment->payment_id);
		
		$amount_string = $purchase->total_price(array('is_payable' => TRUE))->humanize().' GBP';

		$this
			->visit($purchase->payment->authorize_url())
			->assertHasCss('h3', array('text' => 'Your payment summary'))
			->assertHasCss('.items .amount', array('text' => $amount_string))
			->next_wait_time(4000)
			->click_on('#loadLogin')
			->next_wait_time(4000)
			->assertHasCss('input', array('value' => 'Log In'))
			->fill_in('login_email', 'buyer@openbuildings.com')
			->fill_in('login_password', '12345678')
			->wait(3000)
			->click_on('input', array('value' => 'Log In'))
			->wait(3000)
			->assertHasCss('h2', array('text' => 'Review your information'))
			->wait(3000)
			->click_on('input', array('value' => 'Pay'))
			->wait(3000)
			->screenshot('paypal-paid.jpg')
			->assertHasCss('h3', array('text' => 'You made a payment of'))
			->assertHasCss('h3 p', array('text' => $amount_string));

		$purchase->payment->execute();

		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);
	}
}