<?php

use Omnipay\Omnipay;

/**
 * @group model.payment
 */
class Model_PaymentTest extends Testcase_Purchases {

	public $payment_params = array(
		'card' => array(
			'name'			=> 'TEST HOLDER',
			'number'		=> '4242424242424242',
			'expiryMonth'	=> '10',
			'expiryYear'	=> '19',
			'cvv'			=> '123',
		),
	);

	/**
	 * @covers Model_Payment::purchase
	 */
	public function test_purchase()
	{
		$gateway = Omnipay::create('Dummy');
		$params = array('test', 'test2');
		$payment = $this->getMock('Model_Payment', array('execute_purchase'), array('payment'));
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));
		$payment->purchase = $purchase;

		$payment
			->expects($this->once())
			->method('execute_purchase')
			->with(
				$this->identicalTo($gateway),
				$this->equalTo($params)
			);

		$payment->status = Model_Payment::PAID;

		$purchase
			->expects($this->once())
			->method('save');

		$payment->purchase($gateway, $params);

		$this->assertTrue($payment->before_purchase_called);
		$this->assertTrue($payment->after_purchase_called);
		$this->assertTrue($payment->pay_called);
	}

	/**
	 * @covers Model_Payment::purchase
	 */
	public function test_purchase_not_successful()
	{
		$gateway = Omnipay::create('Dummy');
		$params = array('test', 'test2');
		$payment = $this->getMock('Model_Payment', array('execute_purchase'), array('payment'));
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));
		$payment->purchase = $purchase;

		$payment
			->expects($this->once())
			->method('execute_purchase')
			->with(
				$this->identicalTo($gateway),
				$this->equalTo($params)
			);

		$purchase
			->expects($this->once())
			->method('save');

		$payment->purchase($gateway, $params);

		$this->assertTrue($payment->before_purchase_called);
		$this->assertTrue($payment->after_purchase_called);
		$this->assertNull($payment->pay_called);
	}

	/**
	 * @covers Model_Payment::complete_purchase
	 */
	public function test_complete_purchase()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));

		$purchase
			->expects($this->once())
			->method('save');

		$payment = $purchase->build('payment', array('method' => 'Dummy', 'status' => Model_Payment::PENDING));

		$payment->complete_purchase($gateway, $this->payment_params);

		$this->assertTrue($payment->before_complete_purchase_called);
		$this->assertTrue($payment->after_complete_purchase_called);
		$this->assertTrue($payment->pay_called);
	}

	/**
	 * @covers Model_Payment::complete_purchase
	 */
	public function test_complete_purchase_not_successfull()
	{
		// Omnipay Dummy Gateway logic for returning a non successful response
		$this->payment_params['card']['number'] = 4111111111111111;

		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = $this->getMock('Model_Purchase', array('save'), array('purchase'));

		$purchase
			->expects($this->once())
			->method('save');

		$payment = $purchase->build('payment', array('method' => 'Dummy', 'status' => Model_Payment::PENDING));

		$payment->complete_purchase($gateway, $this->payment_params);

		$this->assertTrue($payment->before_complete_purchase_called);
		$this->assertTrue($payment->after_complete_purchase_called);
		$this->assertNull($payment->pay_called);
	}

	/**
	 * @covers Model_Payment::complete_purchase
	 * @expectedException Exception_Payment
	 * @expectedExceptionMessage You must initiate a purchase before completing it
	 */
	public function test_complete_purchase_not_pending()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = $this->getMock('Model_Purchase', NULL, array('purchase'));

		$payment = $purchase->build('payment', array('method' => 'Dummy'));

		$payment->complete_purchase($gateway, $this->payment_params);
	}

	/**
	 * @covers Model_Payment::execute_purchase
	 */
	public function test_execute_purchase()
	{
		$gateway = Omnipay::create('Dummy');
		$purchase = Jam::find('purchase', 2);

		$purchase
			->freeze()
			->save();

		$purchase
			->build('payment', array('method' => 'Dummy'))
				->execute_purchase($gateway, $this->payment_params);

		$this->assertGreaterThan(0, $purchase->payment->payment_id);
		$this->assertNotNull($purchase->payment->raw_response);
		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);
	}

	/**
	 * @covers Model_Payment::execute_purchase
	 */
	public function test_execute_purchase_not_successful()
	{
		// Omnipay Dummy Gateway logic for returning a non successful response
		$this->payment_params['card']['number'] = 4111111111111111;

		$gateway = Omnipay::create('Dummy');
		$purchase = Jam::find('purchase', 2);

		$purchase
			->freeze()
			->save();

		$purchase
			->build('payment', array('method' => 'Dummy'))
				->execute_purchase($gateway, $this->payment_params);

		$this->assertGreaterThan(0, $purchase->payment->payment_id);
		$this->assertNotNull($purchase->payment->raw_response);
		$this->assertEmpty($purchase->payment->status);
	}

	/**
	 * @covers Model_Payment::execute_purchase
	 */
	public function test_execute_purchase_redirect()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = Jam::find('purchase', 2);

		$purchase
			->freeze()
			->save();

		$purchase
			->build('payment', array('method' => 'Dummy'))
				->execute_purchase($gateway, $this->payment_params);

		$this->assertGreaterThan(0, $purchase->payment->payment_id);
		$this->assertNotNull($purchase->payment->raw_response);
		$this->assertEquals(Model_Payment::PENDING, $purchase->payment->status);
	}

	/**
	 * @covers Model_Payment::execute_complete_purchase
	 */
	public function test_execute_complete_purchase()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = Jam::find('purchase', 2);

		$purchase
			->freeze()
			->save();

		$purchase
			->build('payment', array('method' => 'Dummy', 'status' => Model_Payment::PENDING))
				->execute_complete_purchase($gateway, $this->payment_params);

		$this->assertGreaterThan(0, $purchase->payment->payment_id);
		$this->assertNotNull($purchase->payment->raw_response);
		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);
	}

	/**
	 * @covers Model_Payment::execute_complete_purchase
	 */
	public function test_execute_complete_purchase_not_successful()
	{
		// Omnipay Dummy Gateway logic for returning a non successful response
		$this->payment_params['card']['number'] = 4111111111111111;

		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = Jam::find('purchase', 2);

		$purchase
			->freeze()
			->save();

		$purchase
			->build('payment', array('method' => 'Dummy', 'status' => Model_Payment::PENDING))
				->execute_complete_purchase($gateway, $this->payment_params);

		$this->assertGreaterThan(0, $purchase->payment->payment_id);
		$this->assertNotNull($purchase->payment->raw_response);
		$this->assertNotEquals(Model_Payment::PAID, $purchase->payment->status);
	}

	/**
	 * @covers Model_Payment::convert_purchase
	 */
	public function test_convert_purchase()
	{
		$this->env->backup_and_set(array(
			'Request::$client_ip' => '1.1.1.1',
		));

		$purchase = Jam::find('purchase', 1);

		$promo = Jam::build('purchase_item_promotion', array(
			'quantity' => 1,
			'price' => -10,
			'is_discount' => TRUE,
			'is_payable' => TRUE,
			'is_frozen' => TRUE,
		));

		$purchase->brand_purchases[0]->items []= $promo;

		$params = $purchase
			->build('payment', array('method' => 'Dummy'))
				->convert_purchase();

		$expected = array(
			'transactionReference' => 'CNV7IC',
			'currency' => 'EUR',
			'clientIp' => '1.1.1.1',
			'items' => array(
				array(
					"name"			=> 1,
					"description"	=> 'chair',
					"quantity"		=> 1,
					"price"			=> '200.00',
				),
				array(
					"name"			=> 2,
					"description"	=> 'red..',
					"quantity"		=> 2,
					"price"			=> '200.00',
				),
				array(
					"name"			=> $promo->id(),
					"description"	=> 'promotion',
					"quantity"		=> 1,
					"price"			=> '-10.00',
				),
			),
			'amount' => '590.00',
		);

		$this->assertEquals($expected, $params);

		$params = $purchase
			->build('payment', array('method' => 'Dummy'))
				->convert_purchase(TRUE);

		$expected['card'] = array(
			'email' => 'user@example.com',
			'firstName' => 'name1',
			'lastName' => 'name2',
			'address1' => 'Street 1',
			'address2' => 'House 1',
			'city' => 'London',
			'country' => 'GB',
			'postcode' => 'ZIP',
			'phone' => 'phone123',
		);

		$this->assertEquals($expected, $params);

		$purchase->creator = NULL;

		$params = $purchase
			->build('payment', array('method' => 'Dummy'))
				->convert_purchase(TRUE);

		$expected['card'] = array(
			'firstName' => 'name1',
			'lastName' => 'name2',
			'address1' => 'Street 1',
			'address2' => 'House 1',
			'city' => 'London',
			'country' => 'GB',
			'postcode' => 'ZIP',
			'phone' => 'phone123',
		);
	}

	/**
	 * @covers Model_Payment::refund
	 */
	public function test_refund()
	{
		$gateway = Omnipay::create('Dummy');
		$params = array('test', 'test2');
		$refund = $this->getMock('Model_Brand_Refund', array(
			'save',
		), array(
			'brand_refund',
		));

		$payment = $this->getMock('Model_Payment', array(
			'execute_refund',
		), array(
			'payment',
		));

		$brand_purchase = $this->getMock('Model_Brand_Purchase', array(
			'save',
			'freeze',
		), array(
			'brand_purchase'
		));

		$payment
			->expects($this->once())
			->method('execute_refund')
			->with(
				$this->identicalTo($gateway),
				$this->identicalTo($refund),
				$this->equalTo($params)
			);

		$refund
			->expects($this->once())
			->method('save')
			->will($this->returnValue($refund));

		$brand_purchase
			->expects($this->once())
			->method('save')
			->will($this->returnValue($brand_purchase));

		$brand_purchase
			->expects($this->once())
			->method('freeze')
			->will($this->returnValue($brand_purchase));

		$brand_purchase->purchase = Jam::build('purchase');

		$refund->brand_purchase = $brand_purchase;
		$refund->transaction_status = Model_Brand_Refund::TRANSACTION_REFUNDED;

		$payment->refund($gateway, $refund, $params);

		$this->assertTrue($payment->before_refund_called);
		$this->assertTrue($payment->after_refund_called);
	}

	/**
	 * @covers Model_Payment::execute_refund
	 */
	public function test_execute_refund()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = Jam::find('purchase', 1);
		$brand_purchase = $purchase->brand_purchases[0];
		$payment = $purchase->payment;

		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Faulty Product',
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0]),
				array('purchase_item' => $brand_purchase->items[1], 'amount' => 20),
			),
		));
		$payment->execute_refund($gateway, $refund);

		$this->assertNotNull($refund->raw_response);
		$this->assertEquals(Model_Brand_Refund::TRANSACTION_REFUNDED, $refund->transaction_status);
	}

	public function test_execute_refund_not_successful()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = Jam::find('purchase', 1);
		$brand_purchase = $purchase->brand_purchases[0];
		$payment = $purchase->payment;

		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Faulty Product Fail',
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0]),
				array('purchase_item' => $brand_purchase->items[1], 'amount' => 20),
			),
		));
		$payment->execute_refund($gateway, $refund);

		$this->assertNotNull($refund->raw_response);
		$this->assertEquals(NULL, $refund->transaction_status);
	}

	/**
	 * @covers Model_Payment::convert_refund
	 */
	public function test_convert_refund()
	{
		$purchase = Jam::find('purchase', 1);
		$brand_purchase = $purchase->brand_purchases[0];
		$payment = $purchase->payment;

		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Faulty Product',
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0]),
				array('purchase_item' => $brand_purchase->items[1], 'amount' => 20),
			)
		));

		$params = $payment->convert_refund($refund);

		$expected = array(
			'transactionReference' => '11111',
			'reason' => 'Faulty Product',
			'items' => array(
				array(
					'name' => $brand_purchase->items[0]->id(),
					'price' => '200.00',
				),
				array(
					'name' => $brand_purchase->items[1]->id(),
					'price' => '20.00',
				),
			),
			'amount' => '220.00',
			'currency' => 'EUR',
		);

		$this->assertEquals($expected, $params);


		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Full Refund',
		));

		$params = $payment->convert_refund($refund);

		$expected = array(
			'transactionReference' => '11111',
			'reason' => 'Full Refund',
			'amount' => '600.00',
			'currency' => 'EUR',
		);

		$this->assertEquals($expected, $params);

		// Testing full brand purchase refund that also is full purchase refund
		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Full Brand And Purchase Refund',
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0]),
				array('purchase_item' => $brand_purchase->items[1]),
			)
		));

		$params = $payment->convert_refund($refund);

		$expected = array(
			'transactionReference' => '11111',
			'reason' => 'Full Brand And Purchase Refund',
			'amount' => '600.00',
			'currency' => 'EUR',
		);

		$this->assertEquals($expected, $params);

		// Testing full brand purchase refund, but not full purchase refund
		$purchase = Jam::find('purchase', 4);
		$brand_purchase = $purchase->brand_purchases[0];

		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Full Brand But Not Purchase Refund',
			'items' => array(
				array('purchase_item' => $brand_purchase->items[0]),
			)
		));

		$params = $payment->convert_refund($refund);

		$expected = array(
			'transactionReference' => '22222',
			'reason' => 'Full Brand But Not Purchase Refund',
			'items' => array(
				array(
					'name' => $brand_purchase->items[0]->id(),
					'price' => '290.40',
				),
			),
			'amount' => '290.40',
			'currency' => 'GBP',
		);

		$this->assertEquals($expected, $params);
	}

	/**
	 * @covers Model_Payment::full_refund
	 */
	public function test_full_refund()
	{
		$gateway = Omnipay::create('Dummy');
		$params = array('test', 'test2');
		$refund = $this->getMock('Model_Brand_Refund', array(
			'save',
		), array(
			'brand_refund',
		));

		$refund2 = $this->getMock('Model_Brand_Refund', array(
			'save',
		), array(
			'brand_refund',
		));

		$payment = $this->getMock('Model_Payment', array(
			'execute_multiple_refunds',
		), array(
			'payment',
		));

		$brand_purchase = $this->getMock('Model_Brand_Purchase', array(
			'save',
			'freeze',
		), array(
			'brand_purchase'
		));

		$brand_purchase2 = $this->getMock('Model_Brand_Purchase', array(
			'save',
			'freeze',
		), array(
			'brand_purchase'
		));

		$payment
			->expects($this->once())
			->method('execute_multiple_refunds')
			->with(
				$this->identicalTo($gateway),
				$this->identicalTo(array($refund, $refund2)),
				$this->equalTo($params)
			);

		$refund
			->expects($this->once())
			->method('save')
			->will($this->returnValue($refund));

		$refund2
			->expects($this->once())
			->method('save')
			->will($this->returnValue($refund2));

		$brand_purchase
			->expects($this->once())
			->method('save')
			->will($this->returnValue($brand_purchase));

		$brand_purchase
			->expects($this->once())
			->method('freeze')
			->will($this->returnValue($brand_purchase));

		$brand_purchase2
			->expects($this->once())
			->method('save')
			->will($this->returnValue($brand_purchase2));

		$brand_purchase2
			->expects($this->once())
			->method('freeze')
			->will($this->returnValue($brand_purchase2));

		$brand_purchase->purchase = Jam::build('purchase');
		$brand_purchase2->purchase = $brand_purchase->purchase;

		$refund->brand_purchase = $brand_purchase;
		$refund->transaction_status = Model_Brand_Refund::TRANSACTION_REFUNDED;

		$refund2->brand_purchase = $brand_purchase2;
		$refund2->transaction_status = Model_Brand_Refund::TRANSACTION_REFUNDED;

		$payment->full_refund($gateway, array($refund, $refund2), $params);

		$this->assertTrue($payment->before_full_refund_called);
		$this->assertTrue($payment->after_full_refund_called);
	}

	/**
	 * @covers Model_Payment::execute_multiple_refunds
	 */
	public function test_execute_multiple_refunds()
	{
		$gateway = Omnipay::create('\Test\Omnipay\Dummy\ExtendedGateway');
		$purchase = Jam::find('purchase', 4);
		$brand_purchase = $purchase->brand_purchases[0];
		$brand_purchase2 = $purchase->brand_purchases[1];

		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Faulty Product',
		));

		$refund2 = $brand_purchase2->refunds->create(array(
			'reason' => 'Faulty Product',
		));

		$purchase->payment->execute_multiple_refunds($gateway, array($refund, $refund2));

		$this->assertNotNull($refund->raw_response);
		$this->assertEquals(Model_Brand_Refund::TRANSACTION_REFUNDED, $refund->transaction_status);
		$this->assertNotNull($refund2->raw_response);
		$this->assertEquals(Model_Brand_Refund::TRANSACTION_REFUNDED, $refund2->transaction_status);
	}

	/**
	 * @covers Model_Payment::convert_multiple_refunds
	 */
	public function test_convert_multiple_refunds()
	{
		$purchase = Jam::find('purchase', 4);
		$brand_purchase = $purchase->brand_purchases[0];
		$brand_purchase2 = $purchase->brand_purchases[1];

		$refund = $brand_purchase->refunds->create(array(
			'reason' => 'Faulty Product',
		));

		$refund2 = $brand_purchase2->refunds->create(array(
			'reason' => 'Faulty Product',
		));

		$params = $purchase->payment->convert_multiple_refunds(array($refund, $refund2));

		$expected = array(
			'transactionReference' => '22222',
			'reason' => 'Faulty Product',
			'amount' => '440.40',
			'currency' => 'GBP',
		);

		$this->assertEquals($expected, $params);
	}
}
