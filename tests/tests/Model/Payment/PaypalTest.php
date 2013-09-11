<?php

/**
 * Functest_TestsTest 
 *
 * @group model.payment_paypal
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Model_Payment_PaypalTest extends Testcase_Purchases_Spiderling {

	/**
	 * @covers Model_Payment_Paypal::convert_purchase
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

		$paypal_payment = Model_Payment_Paypal::convert_purchase($purchase, array('success_url' => 'http://example.com/success', 'cancel_url' => 'http://example.com/cancel'));

		$this->assertInstanceOf('Paypal\Api\Payment', $paypal_payment);

		$expected = array(
			'intent' => 'sale',
			'payer' => array(
				'payment_method' => 'paypal',
			),
			'redirect_urls' => array(
				'return_url' => 'http://example.com/success',
				'cancel_url' => 'http://example.com/cancel',
			),
			'transactions' => array(
				array(
					'amount' => array(
						'currency' => 'EUR',
						'total' => 506.40,
					),
					'item_list' => array(
						'items' => array(
							array(
								'quantity' => 1,
								'name' => 'Products From example-store',
								'price' => '390.00',
								'currency' => 'EUR',
							),
							array(
								'quantity' => 1,
								'name' => 'Products From empty-store',
								'price' => '116.40',
								'currency' => 'EUR',
							),
						)
					),
					'description' => 'Products from clippings',
				)
			)
		);

		$this->assertEquals($expected, $paypal_payment->toArray());
	}

	/**
	 * @covers Model_Payment_Paypal::convert_refund
	 */
		public function test_convert_refund()
	{
		$purchase = Jam::find('purchase', 1);
		$store_purchase = $purchase->store_purchases[0];

		$refund = $store_purchase->refunds->create(array(
			'reason' => 'Faulty Product',
			'items' => array(
				array('purchase_item' => $store_purchase->items[0]),
				array('purchase_item' => $store_purchase->items[1], 'amount' => 20),
			)
		));
		$paypal_refund = Model_Payment_Paypal::convert_refund($refund);
		$this->assertInstanceOf('Paypal\Api\Refund', $paypal_refund);

		$expected = array(
			'amount' => array(
				'currency' => 'EUR',
				'total' => '220.00',
			)
		);

		$this->assertEquals($expected, $paypal_refund->toArray());

		$refund = $store_purchase->refunds->create(array(
			'reason' => 'Full Rrefund',
		));

		$paypal_refund = Model_Payment_Paypal::convert_refund($refund);

		$expected = array(
			'amount' => array(
				'currency' => 'EUR',
				'total' => '400.00',
			)
		);

		$this->assertEquals($expected, $paypal_refund->toArray());
	}

	/**
	 * @covers Model_Payment_Paypal::execute
	 * @covers Model_Payment_Paypal::authorize
	 * @covers Model_Payment_Paypal::authorize_url
	 * @covers Model_Payment_Paypal::refund
	 * @driver phantomjs
	 */
	public function test_execute()
	{
		$this->env->backup_and_set(array(
			'Paypal::$_api' => NULL,
			'purchases.processor.paypal.oauth' => array(
				'client_id' => getenv('PHP_PAYPAL_CLIENT_ID'), 
				'secret' => getenv('PHP_PAYPAL_SECRET')
			),
		));

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
			->build('payment', array('model' => 'payment_paypal'))
				->authorize(array('success_url' => 'http://example.com?result=success', 'cancel_url' => 'http://example.com?result=cancel'))
				->save();

		$this->assertInstanceOf('Model_Payment_Paypal', $purchase->payment);
		$this->assertEquals('paypal', $purchase->payment->method);
		$this->assertNotEquals('', $purchase->payment->payment_id);
		
		$this
			->visit($purchase->payment->authorize_url())
			->fill_in('Email', 'buyer@openbuildings.com')
			->fill_in('PayPal password', '12345678')
			->click_button('Log In');

		$this
			->next_wait_time(5000)
			->assertHasCss('h2', array('text' => 'Review your information'))
			->assertHasCss('span.grandTotal', array('text' => $purchase->total_price(array('is_payable' => TRUE))))
			->click_button('Continue');

		$this
			->next_wait_time(5000)
			->assertHasNoCss('h2', array('text' => 'Review your information'));

		$query = parse_url($this->current_url(), PHP_URL_QUERY);	
		parse_str($query, $query);
		
		$this->assertEquals('success', $query['result']);

		$purchase
			->payment
				->execute(array('payer_id' => $query['PayerID']))
				->save();

		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);

		$store_purchase = $purchase->store_purchases[0];

		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0], 'amount' => 100)
			)
		));

		$refund
			->execute()
			->save();

		$this->assertEquals(Model_Store_Refund::REFUNDED, $refund->status);
	}
}