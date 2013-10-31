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
	 * @covers Model_Payment_Paypal_Vbv::execute_processor
	 * @covers Model_Payment_Paypal_Vbv::authorize_processor
	 * @covers Model_Payment_Paypal_Vbv::authorize_url
	 * @covers Model_Payment_Paypal_Vbv::refund_processor
	 */
	public function test_execute()
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
		));

		$form = Jam::build('emp_form', $this->payment_params);
		$vbv_params = $form->vbv_params('http://example.com/checkout');

		$purchase = Jam::find('purchase', 2);
		
		// Set the price to 1 to automatically authorize
		$purchase->store_purchases[0]->items[0]->price = 1;

		$purchase
			->build('payment', array('model' => 'payment_emp_vbv'))
				->authorize($vbv_params);

		$purchase
			->payment
				->execute($form->as_array());

		$this->assertEquals(Model_Payment::PAID, $purchase->payment->status);

		$this->assertNotEquals('', $purchase->payment->payment_id);

		$store_purchase = $purchase->store_purchases[0];

		$refund = $store_purchase->refunds->create(array(
			'items' => array(
				array('purchase_item' => $store_purchase->items[0], 'amount' => 1)
			)
		));

		$refund
			->execute();

		$this->assertEquals(Model_Store_Refund::REFUNDED, $refund->status);
	}
}