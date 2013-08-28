<?php

/**
 * Functest_TestsTest 
 *
 * @group processor
 * @group processor.emp
 * 
 * @package Functest
 * @author Ivan Kerin
 * @copyright  (c) 2011-2013 Despark Ltd.
 */
class Processor_EmpTest extends Testcase_Purchases {

	/**
	 * @covers Processor_Emp::is_threatmatrix_enabled
	 */
	public function test_is_threatmatrix_enabled()
	{
		$this->assertFalse(Processor_Emp::is_threatmatrix_enabled());

		$this->env->backup_and_set(array(
			'purchases.processor.emp.threatmatrix' => array('org_id' => '1')
		));

		$this->assertTrue(Processor_Emp::is_threatmatrix_enabled());		
	}

	/**
	 * @covers Processor_Emp::threatmatrix
	 * @covers Processor_Emp::clrear_threatmatrix
	 */
	public function test_threatmatrix()
	{
		$this->assertNull(Processor_Emp::threatmatrix());

		$this->env->backup_and_set(array(
			'purchases.processor.emp.threatmatrix' => array('org_id' => 'TESTORG', 'client_id' => 'TESTCLIENT')
		));

		$threatmatrix = Processor_Emp::threatmatrix();

		$this->assertInstanceOf('Openbuildings\Emp\Threatmatrix', $threatmatrix);
		$this->assertEquals('TESTORG', $threatmatrix->org_id());

		$this->assertSame($threatmatrix, Processor_Emp::threatmatrix());

		Processor_Emp::clear_threatmatrix();

		$this->assertInstanceOf('Openbuildings\Emp\Threatmatrix', Processor_Emp::threatmatrix());
		$this->assertNotSame($threatmatrix, Processor_Emp::threatmatrix());		
	}

	/**
	 * @covers Processor_Emp::api
	 */
	public function test_api()
	{
		$this->env->backup_and_set(array(
			'Processor_Emp::$_api' => NULL,
			'purchases.processor.emp.threatmatrix' => array('org_id' => 'TESTORG', 'client_id' => 'TESTCLIENT'),
			'purchases.processor.emp.api' => array('gateway_url' => 'http://example.com', 'api_key' => 'TESTAPI', 'client_id' => 'TESTCLIENT')
		));

		$api = Processor_Emp::api();

		$this->assertInstanceOf('Openbuildings\Emp\Api', $api);
		$this->assertEquals('TESTAPI', $api->api_key());
		$this->assertEquals('http://example.com', $api->gateway_url());
		$this->assertEquals('TESTCLIENT', $api->client_id());
		$this->assertEquals(Processor_Emp::threatmatrix(), $api->threatmatrix());
	}

	/**
	 * @covers Processor_Emp::params_for
	 */
	public function test_params_for()
	{
		$this->env->backup_and_set(array(
			'Request::$client_ip' => '1.1.1.1',
		));


		$purchase = Jam::find('test_purchase', 1);

		$purchase->store_purchases[0]->items->build(array(
			'quantity' => 1,
			'price' => -10,
			'type' => 'promotion',
			'is_discount' => TRUE,
			'is_payable' => TRUE,
		));

		$params = Processor_Emp::params_for($purchase);

		$expected = array(
			'payment_method' => 'creditcard',
			'order_reference' => 'CNV7IC',
			'order_currency' => 'EUR',
			'customer_email' => 'admin@example.com',
			'test_transaction' => '1',
			'ip_address' => '1.1.1.1',
			'credit_card_trans_type' => 'sale',

			'item_1_predefined' => '0',
			'item_1_digital' => '0',
			'item_1_code' => 'Model_Test_Product(1)',
			'item_1_qty' => '1',
			'item_1_discount' => '0',
			'item_1_name' => 'chair',
			'item_1_unit_price_EUR' => '200.00',

			'item_2_predefined' => '0',
			'item_2_digital' => '0',
			'item_2_code' => 'Model_Test_Variation(1)',
			'item_2_qty' => '1',
			'item_2_discount' => '0',
			'item_2_name' => 'red',
			'item_2_unit_price_EUR' => '200.00',

			'item_3_predefined' => '0',
			'item_3_digital' => '0',
			'item_3_code' => 'promotion',
			'item_3_qty' => '1',
			'item_3_discount' => '1',
			'item_3_name' => 'promotion',
			'item_3_unit_price_EUR' => '-10.00',
		);

		$this->assertEquals($expected, $params);
	}

	/**
	 * @covers Processor_Emp::construct
	 */
	public function test_construct()
	{
		$params = array(
			'card_holder_name' => 'TEST HOLDER',
			'card_number'      => '4111111111111111',
			'exp_month'        => '10',
			'exp_year'         => '19',
			'cvv'              => '123',
		);

		$next_url = 'http://example.com/complete';

		$processor = new Processor_Emp($params, $next_url);

		$this->assertEquals($params, $processor->params());
		$this->assertEquals($next_url, $processor->next_url());
	}

	public function test_execute()
	{
		$this->env->backup_and_set(array(
			'Processor_Emp::$_api' => NULL,
			'Request::$client_ip' => '95.87.212.88',
			'purchases.processor.emp.threatmatrix' => array(
				'org_id' => getenv('PHP_THREATMATRIX_ORG_ID'), 
				'client_id' => getenv('PHP_EMP_CLIENT_ID')
			),
			'purchases.processor.emp.api' => array(
				'gateway_url' => 'https://my.emerchantpay.com', 
				'api_key' => getenv('PHP_EMP_API_KEY'), 
				'client_id' => getenv('PHP_EMP_CLIENT_ID')
			)
		));

		Request::factory(Processor_Emp::threatmatrix()->tracking_url())->execute();

		$purchase = Jam::find('test_purchase', 2);

		$purchase
			->freeze_item_prices()
			->freeze_monetary()
			->save();

		$params = array(
			'card_holder_name' => 'TEST HOLDER',
			'card_number'      => '4111111111111111',
			'exp_month'        => '10',
			'exp_year'         => '19',
			'cvv'              => '123',
			'order_reference'  => '521c7556ccdd8',
			'order_currency'   => 'GBP',
			'payment_method'   => 'creditcard',
		);

		$next_url = 'http://example.com/complete';

		$processor = new Processor_Emp($params, $next_url);	

		$purchase->pay($processor);

		$this->assertNotNull($purchase->payment);
		$this->assertEquals('emp', $purchase->payment->method);
		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);
	}
}