<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment_Emp_Vbv extends Model_Payment {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->table('payments');
	}

	/**
	 * Use the current purchase to generate an "authorize url" where you can go and approve the purchase through paypal's interface.
	 * @param  array  $params must provide callback_url
	 * @return Model_Payment_Emp         self
	 */
	public function authorize_processor(array $params = array())
	{
		$currency = $this->purchase->display_currency() ?: $this->purchase->currency();

		$request_params = array(
			'refernece'        => $this->purchase->number,
			'amount'           => $this->purchase->total_price(array('is_payable' => TRUE))->as_string($currency),
			'currency'         => $currency,
			// 'test_transaction' => Kohana::$environment === Kohana::PRODUCTION ? '0' : '1',
		);

		$request_params = array_merge($request_params, $params);

		print_r($request_params);

		$response = Emp::api()
			->request(Openbuildings\Emp\Api::VBVMC3D_AUTH, $request_params);

		print_r($response);

		// $this->set(array(
		// 	'payment_id' => $payment->getId(), 
		// 	'raw_response' => $response, 
		// 	'status' => Model_Payment::PENDING
		// ));

		return $this;
	}

	/**
	 * Perform a payment of the current purchase, set the reponse in payment_id, raw_response and status.
	 * @param  array  $params 
	 * @return Model_Payment_Emp         self
	 */
	public function execute_processor(array $params = array())
	{
		$params = array_merge(Model_Payment_Emp::convert_purchase($this->purchase), $params);

		$response = Emp::api()
			->request(Openbuildings\Emp\Api::ORDER_SUBMIT, $params);

		Emp::clear_threatmatrix();

		$status = ($response['transaction_response'] == 'A') ? Model_Payment::PAID : NULL;

		$this->set(array(
			'payment_id' => $response['transaction_id'], 
			'raw_response' => $response['raw'], 
			'status' => $status
		));

		return $this;
	}

	/**
	 * Perform a refund based on a given refund object. Set the refund's raw_response and status accordingly
	 * @param  Model_Store_Refund $refund        
	 * @param  array              $custom_params 
	 * @return Model_Payment_Emp                            self
	 */
	public function refund_processor(Model_Store_Refund $refund, array $custom_params = array())
	{
		$params = Model_Payment_Emp::convert_refund($refund);

		$params = array_merge($params, $custom_params);

		$response = Emp::api()
			->request(Openbuildings\Emp\Api::ORDER_CREDIT, $params);

		$refund->raw_response = $response;
		$refund->status = Model_Store_Refund::REFUNDED;

		return $this;
	}
}