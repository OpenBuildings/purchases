<?php

/**
 * @group model
 * @group model.payment_emp
 */
class Model_Payment_EmpTest extends Testcase_Purchases {

	public $payment_params = array(
		'card_holder_name' => 'TEST HOLDER',
		'card_number'      => '4111111111111111',
		'exp_month'        => '10',
		'exp_year'         => '19',
		'cvv'              => '123',
		'order_reference'  => '521c7556ccdd8',
		'order_currency'   => 'GBP',
		'payment_method'   => 'creditcard',
	);

	/**
	 * @covers Model_Payment_Emp::convert_purchase
	 */
	public function test_convert_purchase()
	{
		$this->env->backup_and_set(array(
			'Request::$client_ip' => '1.1.1.1',
		));

		$purchase = Jam::find('purchase', 1);

		$promo = $purchase->store_purchases[0]->items->create(array(
			'quantity' => 1,
			'price' => -10,
			'type' => 'promotion',
			'is_discount' => TRUE,
			'is_payable' => TRUE,
		));

		$params = Model_Payment_Emp::convert_purchase($purchase);

		$expected = array(
			'payment_method' => 'creditcard',
			'order_reference' => 'CNV7IC',
			'order_currency' => 'EUR',
			'customer_email' => 'user@example.com',

			'customer_first_name' => 'name1',
			'customer_last_name' => 'name2',
			'customer_address' => 'Street 1',
			'customer_address2' => 'House 1',
			'customer_city' => 'London',
			'customer_country' => 'GB',
			'customer_postcode' => 'ZIP',
			'customer_phone' => 'phone123',

			'test_transaction' => '1',
			'ip_address' => '1.1.1.1',
			'credit_card_trans_type' => 'sale',

			'item_1_predefined' => '0',
			'item_1_digital' => '0',
			'item_1_code' => '1',
			'item_1_qty' => '1',
			'item_1_discount' => '0',
			'item_1_name' => 'chair',
			'item_1_unit_price_EUR' => '200.00',

			'item_2_predefined' => '0',
			'item_2_digital' => '0',
			'item_2_code' => '2',
			'item_2_qty' => '1',
			'item_2_discount' => '0',
			'item_2_name' => 'red..',
			'item_2_unit_price_EUR' => '200.00',

			'item_3_predefined' => '0',
			'item_3_digital' => '0',
			'item_3_code' => $promo->id(),
			'item_3_qty' => '1',
			'item_3_discount' => '1',
			'item_3_name' => 'promotion',
			'item_3_unit_price_EUR' => '-10.00',
		);

		$this->assertEquals($expected, $params);
	}

	/**
	 * @covers Model_Payment_Emp::find_item_id
	 */
	public function test_find_item_id()
	{
		$response = Jam::find('payment_emp', 1)->raw_response;
		$this->assertEquals('5657022', Model_Payment_Emp::find_item_id($response['cart'], 1));
		$this->assertEquals('5657032', Model_Payment_Emp::find_item_id($response['cart'], 2));
		$this->assertEquals(NULL, Model_Payment_Emp::find_item_id($response['cart'], 4));
	}

	/**
	 * @covers Model_Payment_Emp::convert_refund
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

		$params = Model_Payment_Emp::convert_refund($refund);

		$expected = array(
			'order_id' => '5580812',
			'trans_id' => '11111',
			'reason' => 'Faulty Product',
			'test_transaction' => '1',
			'item_1_id' => '5657022',
			'item_2_id' => '5657032',
			'item_2_amount' => '20.00',
		);

		$this->assertEquals($expected, $params);


		$refund = $store_purchase->refunds->create(array(
			'reason' => 'Full Rrefund',
		));

		$params = Model_Payment_Emp::convert_refund($refund);

		$expected = array(
			'order_id' => '5580812',
			'trans_id' => '11111',
			'reason' => 'Full Rrefund',
			'amount' => '400.00',
			'test_transaction' => '1',
		);

		$this->assertEquals($expected, $params);
	}

	/**
	 * @covers Model_Store_Refund::execute
	 * @covers Model_Payment_Emp::refund_processor
	 * @covers Model_Payment_Emp::execute_processor
	 */
	public function test_execute()
	{
		$this->env->backup_and_set(array(
			'Emp::$_api' => NULL,
			'Request::$client_ip' => '95.87.212.88',
			'purchases.processor.emp.threatmatrix' => array(
				'org_id' => getenv('EMP_TMX'), 
				'client_id' => getenv('EMP_CID')
			),
			'purchases.processor.emp.api' => array(
				'gateway_url' => 'https://my.emerchantpay.com', 
				'api_key' => getenv('EMP_KEY'), 
				'client_id' => getenv('EMP_CID'),
				'proxy' => getenv('EMP_PROXY')
			)
		));

		Openbuildings\Emp\Remote::get(Emp::threatmatrix()->tracking_url(), array(CURLOPT_PROXY => getenv('EMP_PROXY')));
		
		$purchase = Jam::find('purchase', 2);

		$purchase
			->freeze()
			->save();

		$purchase
			->build('payment', array('model' => 'payment_emp'))
				->execute($this->payment_params);
		
		$this->assertGreaterThan(0, $purchase->payment->payment_id);
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
		$monetary = new OpenBuildings\Monetary\Monetary('GBP', new OpenBuildings\Monetary\Source_Static());

		return array(
			array(new Jam_Price(10, 'EUR', $monetary), new Jam_Price(0.555, 'EUR', $monetary)),
			array(new Jam_Price(20, 'GBP', $monetary), new Jam_Price(0.854723, 'GBP', $monetary)),
			array(new Jam_Price(244, 'GBP', $monetary), new Jam_Price(8.358723, 'GBP', $monetary)),
		);
	}

	/**
	 * @dataProvider data_transaction_fee
	 * @covers Model_Payment_Emp::transaction_fee
	 */
	public function test_transaction_fee($payment_price, $expected)
	{
		$payment = Jam::build('payment_emp');
		$result = $payment->transaction_fee($payment_price);
		$this->assertEquals($expected, $result);
	}

}