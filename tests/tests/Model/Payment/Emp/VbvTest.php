<?php

/**
 * @group model
 * @group model.payment_emp_vbv
 */
class Model_Payment_Emp_VbvTest extends Testcase_Purchases {

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
	 * @covers Model_Payment_Emp::authorize_processor
	 */
	public function test_authorize()
	{
		$this->env->backup_and_set(array(
			'Emp::$_api' => NULL,
			'Request::$client_ip' => '95.87.212.88',
			'Request::$user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			'purchases.processor.emp.threatmatrix' => array(
				'org_id' => getenv('EMP_TMX'), 
				'client_id' => getenv('EMP_CID'),
			),
			'purchases.processor.emp.api' => array(
				'gateway_url' => 'https://my.emerchantpay.com', 
				'api_key' => getenv('EMP_KEY'), 
				'client_id' => getenv('EMP_CID'),
				'proxy' => getenv('EMP_PROXY'),
			),
			'purchases.processor.emp.3d_secure' => TRUE,
		));

		$vbv_params = Jam::build('emp_form', $this->payment_params)->vbv_params();
		$vbv_params['callback_url'] = 'http://bouncer.example.com';

		$purchase = Jam::find('purchase', 2);

		$purchase
			->build('payment', array('model' => 'payment_emp_vbv'))
				->authorize($vbv_params);	
	}
}