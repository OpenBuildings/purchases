<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;

/**
 * @group model
 * @group model.payment
 * @group model.payment_paypal
 */
class Model_Payment_PaypalTest extends Testcase_Purchases_Spiderling {

	/**
	 * @covers Model_Payment_Paypal::convert_purchase
	 */
	public function test_convert_purchase()
	{
		$purchase = Jam::find('purchase', 1);

		$purchase->store_purchases[0]->items->create(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
			'is_discount' => TRUE,
			'is_payable' => TRUE,
		));

		$product = Jam::create('product', array('price' => 100, 'name' => 'product 1', 'currency' => 'GBP', 'store' => Jam::find('store', 2)));
		$item = Jam::build('purchase_item', array('reference' => $product, 'model' => 'purchase_item_product', 'is_payable' => TRUE));
		$purchase->add_item(Jam::find('store', 2), $item);

		$paypal_payment = Model_Payment_Paypal::convert_purchase($purchase, array(
			'success_url' => 'http://example.com/success',
			'cancel_url' => 'http://example.com/cancel',
			'description' => 'test transaction description'
		));

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
						'total' => 509.26,
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
								'price' => '119.26',
								'currency' => 'EUR',
							),
						)
					),
					'description' => 'test transaction description',
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
	 * @covers Model_Payment_Paypal::execute_processor
	 * @covers Model_Payment_Paypal::authorize_processor
	 * @covers Model_Payment_Paypal::authorize_url
	 * @covers Model_Payment_Paypal::refund_processor
	 * @driver selenium
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
						'model' => 'purchase_item_promotion',
						'is_discount' => TRUE,
						'is_payable' => TRUE,
					));

		$purchase
			->build('payment', array('model' => 'payment_paypal'))
				->authorize(array('success_url' => 'http://example.com?result=success', 'cancel_url' => 'http://example.com?result=cancel'));

		$this->assertInstanceOf('Model_Payment_Paypal', $purchase->payment);
		$this->assertNotEquals('', $purchase->payment->payment_id);
		
		$this
			->visit($purchase->payment->authorize_url())
			->wait(5000)
			->assertHasCss('h3', array('text' => 'Your order summary'))
			->next_wait_time(5000)
			->click_on('div.panel', array('text' => 'Log in to your account to complete the purchase'))
			->next_wait_time(5000)
			->fill_in('login_email', 'buyer@openbuildings.com')
			->fill_in('PayPal password', '12345678')
			->wait(5000)
			->click_button('Log In')
			->wait(5000)
			->assertHasCss('h2', array('text' => 'Review your information'))
			->assertHasCss('span.grandTotal', array('text' => $purchase->total_price(array('is_payable' => TRUE))))
			->click_button('Continue')
			->wait(5000)
			->assertHasNoCss('h2', array('text' => 'Review your information'));

		$query = parse_url($this->current_url(), PHP_URL_QUERY);	
		parse_str($query, $query);
		
		$this->assertEquals('success', $query['result']);

		$purchase
			->payment
				->execute(array('payer_id' => $query['PayerID']));

		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);

		$store_purchase = $purchase->store_purchases[0];

		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0], 'amount' => 100)
			)
		));

		$refund
			->execute();

		$this->assertEquals(Model_Store_Refund::REFUNDED, $refund->status);
	}

	public function data_transaction_fee()
	{
		$monetary = new Monetary('GBP', new Source_Static());

		return array(
			array(new Jam_Price(10, 'EUR', $monetary), new Jam_Price(0.69, 'EUR', $monetary)),
			array(new Jam_Price(20, 'GBP', $monetary), new Jam_Price(0.9738775, 'GBP', $monetary)),
			array(new Jam_Price(4000, 'GBP', $monetary), new Jam_Price(116.2938775, 'GBP', $monetary)),
		);
	}

	/**
	 * @dataProvider data_transaction_fee
	 * @covers Model_Payment_Paypal::transaction_fee
	 */
	public function test_transaction_fee($payment_price, $expected)
	{
		$payment = Jam::build('payment_paypal');
		$result = $payment->transaction_fee($payment_price);
		$this->assertEquals($expected, $result);
	}

	public function data_transaction_fee_percent()
	{
		$monetary = new Monetary('GBP', new Source_Static());

		return array(
			array(new Jam_Price(100, 'EUR', $monetary), 0.034),
			array(new Jam_Price(3000, 'EUR', $monetary), 0.029),
			array(new Jam_Price(9000, 'GBP', $monetary), 0.027),
			array(new Jam_Price(59000, 'EUR', $monetary), 0.024),
			array(new Jam_Price(90000, 'GBP', $monetary), 0.019),
			array(new Jam_Price(190000, 'GBP', $monetary), 0.019),
		);
	}

	/**
	 * @dataProvider data_transaction_fee_percent
	 * @covers Model_Payment_Paypal::transaction_fee_percent
	 */
	public function test_transaction_fee_percent($payment_price, $expected)
	{
		$result = Model_Payment_Paypal::transaction_fee_percent($payment_price);
		$this->assertEquals($expected, $result);
	}
}
