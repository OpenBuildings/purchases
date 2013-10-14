<?php

/**
 * @group paypal
 */
class PaypalTest extends Testcase_Purchases_Spiderling {

	/**
	 * @covers Paypal::api
	 */
	public function test_api()
	{
		$this->env->backup_and_set(array(
			'Paypal::$_api' => NULL,
			'purchases.processor.paypal.oauth' => array(
				'client_id' => getenv('PHP_PAYPAL_CLIENT_ID'), 
				'secret' => getenv('PHP_PAYPAL_SECRET')
			),
		));

		$api = Paypal::api();
		$this->assertInstanceOf('PayPal\Rest\ApiContext', $api);
		$this->assertNotNull($api->getrequestId());

		$this->assertSame($api, Paypal::api());
		$reloaded_api = Paypal::api(TRUE);

		$this->assertNotSame($api, $reloaded_api);
		$this->assertInstanceOf('PayPal\Rest\ApiContext', $reloaded_api);
		$this->assertNotNull($reloaded_api->getrequestId());
		$this->assertNotEquals($api->getrequestId(), $reloaded_api->getrequestId());
	}
}