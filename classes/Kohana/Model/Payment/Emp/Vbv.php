<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment_Emp_Vbv extends Model_Payment_Emp {

	public static $auth_result_codes = array(
		'Y' => 'Authentication Successful',
		'A' => 'Attempts Processing Performed',
		'N' => 'Authentication Failed',
		'U' => 'Authentication Could Not Be Performed',
	);

	/**
	 * Use the current purchase to generate an "authorize url" where you can go and approve the purchase through paypal's interface.
	 * @param  array  $params must provide callback_url
	 * @return Model_Payment_Emp         self
	 */
	public function authorize_processor(array $params = array())
	{
		$currency = $this->purchase->display_currency() ?: $this->purchase->currency();

		$purchase_params = array(
			'refernece'        => $this->purchase->number,
			'amount'           => $this->purchase->total_price(array('is_payable' => TRUE))->as_string($currency),
			'currency'         => $currency,
		);

		$request_params = array_merge($params, $purchase_params);

		try
		{
			$response = Emp::api()
				->request(Openbuildings\Emp\Api::VBVMC3D_AUTH, $request_params);
		}
		catch (Openbuildings\Emp\Exception $exception)
		{
			throw new Exception_Payment('Payment gateway error: :error', array(':error' => $exception->getMessage()), 0, $exception);
		}

		if (Arr::path($response, 'raw.enrollmentstatus') !== 'Y')
			throw new Exception_Payment('Credit card not enrolled in VBV/3D Secure');

		$this->_authorize_url = Arr::path($response, 'raw.bouncerURL');

		$this->set(array(
			'payment_id' => Arr::path($response, 'raw.requestid'),
			'raw_response' => Arr::get($response, 'raw'),
			'status' => Model_Payment::PENDING,
		));

		return $this;
	}

	/**
	 * Perform a payment of the current purchase, set the reponse in payment_id, raw_response and status.
	 * @param  array  $params
	 * @return Model_Payment_Emp         self
	 */
	public function execute_processor(array $params = array())
	{
		$auth_result_params = array(
			'reference' => $this->purchase->number,
			'requestid' => $this->payment_id,
		);

		try
		{
			$auth_response = Emp::api()
				->request(Openbuildings\Emp\Api::VBVMC3D_RESULT, $auth_result_params);

			$result_code = Arr::path($auth_response, 'raw.authenticationstatus');

			if ($result_code !== 'Y')
				throw new Exception_Payment(
					Arr::get(self::$auth_result_codes, $result_code, 'Authentication not complete'),
					array(),
					0,
					NULL,
					$auth_response['raw']
				);

			$vbv_auth_params = Arr::extract($auth_response['raw'], array('eci', 'xid', 'cavv'));

			$params = array_merge($params, $vbv_auth_params, Model_Payment_Emp::convert_purchase($this->purchase));

			$response = Emp::api()
				->request(Openbuildings\Emp\Api::ORDER_SUBMIT, $params);
		}
		catch (Openbuildings\Emp\Exception $exception)
		{
			throw new Exception_Payment('Payment gateway error: :error', array(':error' => $exception->getMessage()), 0, $exception);
		}

		Emp::clear_threatmatrix();

		$status = ($response['transaction_response'] == 'A') ? Model_Payment::PAID : NULL;

		$this->set(array(
			'payment_id' => $response['transaction_id'],
			'raw_response' => $response['raw'],
			'status' => $status
		));

		return $this;
	}
}
