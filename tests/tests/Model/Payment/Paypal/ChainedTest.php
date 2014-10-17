<?php

use OpenBuildings\Monetary\Monetary;
use OpenBuildings\Monetary\Source_Static;
use OpenBuildings\PayPal\Payment_Adaptive_Simple;

/**
 * @group model
 * @group model.payment
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
	 * @covers Kohana_Model_Payment_Paypal_Chained::convert_purchase
	 * @covers Kohana_Model_Payment_Paypal_Chained::receivers
	 * @covers Kohana_Model_Payment_Paypal_Chained::store_refund_receivers
	 */
	public function test_convert_purchase()
	{
		$purchase = Jam::find('purchase', 1);

		$promo = $purchase->store_purchases[0]->items->create(array(
			'quantity' => 1,
			'price' => -10,
			'model' => 'purchase_item_promotion',
			'is_discount' => TRUE,
			'is_payable' => TRUE,
		));

		$product = Jam::create('product', array('price' => 100, 'name' => 'product 1', 'currency' => 'GBP', 'store' => Jam::find('store', 2)));
		$item = Jam::build('purchase_item', array('reference' => $product, 'model' => 'purchase_item_product', 'is_payable' => TRUE));
		$purchase->add_item(Jam::find('store', 2), $item);

		$payment = Model_Payment_Paypal_Chained::convert_purchase($purchase, array(
			'success_url' => 'http://example.com/success',
			'cancel_url' => 'http://example.com/cancel',
			'description' => 'test transaction description'
		));

		$this->assertInstanceOf('OpenBuildings\PayPal\Payment_Adaptive_Chained', $payment);

		$this->assertSame('EUR', $payment->config('currency'));
		$this->assertSame(Payment_Adaptive_Simple::FEES_PAYER_EACHRECEIVER, $payment->config('fees_payer'));
		$this->assertSame(array(
			'total_price' => '509.26',
			'receivers' => array(
				array(
					'email' => 'teststore@clippings.com',
					'amount' => '195.00',
					'primary' => FALSE,
				),
				array(
					'email' => 'test-store@clippings.com',
					'amount' => '59.63',
					'primary' => FALSE,
				),
				array(
					'email' => 'adel-dev@clippings.com',
					'amount' => '509.26',
					'primary' => TRUE,
				),
			)
		), $payment->order());
		$this->assertSame('http://example.com/success', $payment->return_url());
		$this->assertSame('http://example.com/cancel', $payment->cancel_url());
	}

	/**
	 * @covers Kohana_Model_Payment_Paypal_Chained::execute_processor
	 * @covers Kohana_Model_Payment_Paypal_Chained::authorize_processor
	 * @covers Kohana_Model_Payment_Paypal_Chained::authorize_url
	 * @covers Kohana_Model_Payment_Paypal_Chained::config_auth
	 * @covers Kohana_Model_Payment_Paypal_Chained::refund_processor
	 * @covers Kohana_Model_Payment_Paypal_Chained::store_refund_receivers
	 * @driver selenium
	 */
	public function test_execute()
	{
		$this->markTestSkipped('Tests are failing on Travis for no obvious reasone and they would with mocked requests soon');
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
			->wait(8000)
			->next_wait_time(8000)
			->click_on('#loadLogin')
			->wait(10000)
			->next_wait_time(4000)
			->assertHasCss('input', array('value' => 'Log In'))
			->wait(5000)
			->fill_in('login_email', 'buyer@openbuildings.com')
			->fill_in('login_password', '12345678')
			->click_on('input', array('value' => 'Log In'))
			->wait(5000)
			->assertHasCss('h2', array('text' => 'Review your information'))
			->wait(5000)
			->click_on('input', array('value' => 'Pay'))
			->wait(5000)
			->assertHasCss('h3', array('text' => 'You made a payment of'))
			->assertHasCss('h3 p', array('text' => $amount_string));

		$purchase->payment->execute();

		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);

		$pay_key = $purchase->payment->payment_id;
		$this->assertStringStartsWith('AP-', $pay_key);

		$refund = $purchase->store_purchases[0]->refunds->create(array(
			'items' => array(
				array(
					'purchase_item' => $purchase->store_purchases[0]->items[0],
					'amount' => 100
				)
			)
		));

		$refund
			->execute();

		$this->assertEquals(Model_Store_Refund::TRANSACTION_REFUNDED, $refund->transaction_status);
	}

	/**
	 * @covers Kohana_Model_Payment_Paypal_Chained::convert_receivers_amount
	 */
	public function test_convert_receivers_amount()
	{
		$monetary = new Monetary('GBP', new Source_Static);

		$receivers = array(
			array(
				'amount' => new Jam_Price(10, 'GBP', $monetary),
			),
			array(
				'amount' => new Jam_Price(100, 'USD', $monetary),
			),
			array(
				'amount' => new Jam_Price(15, 'EUR', $monetary),
			),
		);

		$result = Kohana_Model_Payment_Paypal_Chained::convert_receivers_amount($receivers, 'EUR');
		$this->assertSame(array(
			array(
				'amount' => '11.91',
			),
			array(
				'amount' => '75.09',
			),
			array(
				'amount' => '15.00',
			),
		), $result);
	}

	public function data_store_purchase_receiver()
	{
		return array(
			array(
				array(
					'store' => array(
						'paypal_email' => 'abc@example.com',
					),
				),
				50.00,
				1,
				array(
					'email' => 'abc@example.com',
					'amount' => 50.00,
				),
			),
			array(
				array(
					'store' => array(
						'paypal_email' => FALSE,
					),
				),
				50.00,
				0,
				NULL,
			),
			array(
				array(
					'store' => array(
						'paypal_email' => 'paypal@example.com',
					),
				),
				'150.00',
				1,
				array(
					'email' => 'paypal@example.com',
					'amount' => '150.00',
				),
			),
			array(
				array(
					'store' => array(
						'paypal_email' => NULL,
					),
				),
				'150.00',
				0,
				NULL,
			),
			array(
				array(
					'store' => Jam::build('store'),
				),
				'150.00',
				0,
				NULL,
			),
		);
	}

	/**
	 * @dataProvider data_store_purchase_receiver
	 * @covers Kohana_Model_Payment_Paypal_Chained::store_purchase_receiver
	 */
	public function test_store_purchase_receiver($store_purchase_data, $total_price, $call_total_price, $expected_receiver_data)
	{
		$store_purchase = $this->getMock('Model_Store_Purchase', array(
			'total_price',
		), array(
			'store_purchase'
		));

		if ($call_total_price)
		{
			$store_purchase
				->expects($this->exactly($call_total_price))
				->method('total_price')
				->with(array(
					'is_payable' => TRUE
				))
				->will($this->returnValue($total_price));
		}

		$store_purchase->set($store_purchase_data);

		$this->assertSame(
			$expected_receiver_data,
			Kohana_Model_Payment_Paypal_Chained::store_purchase_receiver(
				$store_purchase
			)
		);
	}

	public function data_store_refund_receivers()
	{
		$monetary = new Monetary('GBP', new Source_Static);

		return array(
			array(
				array(
					'store' => array(
						'paypal_email' => 'abc@example.com',
					),
				),
				new Jam_Price(15.00, 'EUR', $monetary),
				1,
				array(
					array(
						'email' => 'abc@example.com',
						'amount' => '15.00',
					),
				),
			),
			array(
				array(
					'store' => array(
						'paypal_email' => FALSE,
					),
				),
				new Jam_Price(15.00, 'EUR', $monetary),
				0,
				array(),
			),
			array(
				array(
					'store' => array(
						'paypal_email' => 'abc@example.com',
					),
				),
				new Jam_Price(10.00, 'GBP', $monetary),
				1,
				array(
					array(
						'email' => 'abc@example.com',
						'amount' => '11.91',
					),
				),
			),
			array(
				array(
					'store' => Jam::build('store'),
				),
				new Jam_Price(15.00, 'EUR', $monetary),
				0,
				array(),
			),
		);
	}

	/**
	 * @dataProvider data_store_refund_receivers
	 * @covers Kohana_Model_Payment_Paypal_Chained::store_refund_receivers
	 */
	public function test_store_refund_receivers($store_purchase_data, $total_price, $call_total_price, $expected_store_refund_receivers)
	{
		$store_refund = $this->getMock('Model_Store_Refund', array(
			'get_insist'
		), array(
			'store_refund'
		));

		$store_purchase = $this->getMock('Model_Store_Purchase', array(
			'total_price',
		), array(
			'store_purchase'
		));

		$store_purchase->set($store_purchase_data);

		if ($call_total_price)
		{
			$store_purchase
				->expects($this->exactly($call_total_price))
				->method('total_price')
				->with(array(
					'is_payable' => TRUE
				))
				->will($this->returnValue($total_price));
		}

		$store_refund
			->expects($this->once())
			->method('get_insist')
			->with('store_purchase')
			->will($this->returnValue($store_purchase));

		$result = Kohana_Model_Payment_Paypal_Chained::store_refund_receivers($store_refund, 'EUR');
		$this->assertSame($expected_store_refund_receivers, $result);
	}

	public function data_transaction_fee()
	{
		$monetary = new Monetary('GBP', new Source_Static);

		return array(
			array(new Jam_Price(10, 'EUR', $monetary), new Jam_Price(0.69, 'EUR', $monetary)),
			array(new Jam_Price(20, 'GBP', $monetary), new Jam_Price(0.9738775, 'GBP', $monetary)),
			array(new Jam_Price(4000, 'GBP', $monetary), new Jam_Price(116.2938775, 'GBP', $monetary)),
		);
	}

	/**
	 * @dataProvider data_transaction_fee
	 * @covers Model_Payment_Paypal_Chained::transaction_fee
	 */
	public function test_transaction_fee($payment_price, $expected)
	{
		$payment = Jam::build('payment_paypal_chained');
		$result = $payment->transaction_fee($payment_price);
		$this->assertEquals($expected, $result);
	}
}
