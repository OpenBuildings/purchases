<?php defined('SYSPATH') OR die('No direct script access.');

use OpenBuildings\PayPal\Payment;
use OpenBuildings\PayPal\Payment_Adaptive_Simple;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment_Paypal_Chained extends Model_Payment {

	const PAYMENT_ID_KEY = 'payKey';

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->table('payments');

		$paypal_mode = Kohana::$config->load('purchases.processor.paypal.config.mode');

		$environment = ($paypal_mode === 'live')
			? Payment::ENVIRONMENT_LIVE
			: Payment::ENVIRONMENT_SANDBOX;

		Payment::environment($environment);
	}

	/**
	 * Convert a Model_Purchase to a OpenBuildings\PayPal\Payment object.
	 * You could provide some params as a second argument:
	 *  * fees_payer - Defaults to each receiver.
	 *  * success_url - Required.
	 *  * cancel_url - Required.
	 *
	 * @param  Model_Purchase $purchase
	 * @param  array          $params
	 * @return OpenBuildings\PayPal\Payment
	 */
	public static function convert_purchase(Model_Purchase $purchase, array $params = array())
	{
		if ( ! Jam::meta('store')->behavior('paypal_adaptive'))
			throw new Exception('Model_Store must have Paypal_Adaptive behavior attached to perform AdaptivePayments');

		$currency = $purchase->display_currency() ?: $purchase->currency();

		$receivers = Model_Payment_Paypal_Chained::receivers($purchase, $currency);

		if (empty($params['fees_payer']))
		{
			$params['fees_payer'] = Payment_Adaptive_Simple::FEES_PAYER_EACHRECEIVER;
		}

		$auth = Kohana::$config->load('purchases.processor.paypal.adaptive.auth');

		return Payment::instance('Adaptive_Chained')
			->config('fees_payer', $params['fees_payer'])
			->config('currency', $currency)
			->order(array(
				'total_price' => $purchase
					->total_price(array('is_payable' => TRUE))
					->as_string($currency),
				'receivers' => $receivers,
			))
			->return_url($params['success_url'])
			->cancel_url($params['cancel_url']);
	}

	public static function config_auth(OpenBuildings\PayPal\Payment $payment)
	{
		$auth = Kohana::$config->load('purchases.processor.paypal.adaptive.auth');

		return $payment
			->config('username', $auth['username'])
			->config('password', $auth['password'])
			->config('signature', $auth['signature'])
			->config('app_id', $auth['app_id']);
	}

	public static function receivers(Model_Purchase $purchase, $currency)
	{
		$receivers = array();

		foreach ($purchase->store_purchases->as_array() as $store_purchase)
		{
			$paypal_email = $store_purchase->store->{Jam_Behavior_Paypal_Adaptive::PAYPAL_EMAIL_FIELD};

			if ( ! $paypal_email)
				continue;

			$receivers []= array(
				'email' => $paypal_email,
				'amount' => $store_purchase->total_price(array(
					'is_payable' => TRUE
				))->as_string($currency)
			);
		}

		return $receivers;
	}

	/**
	 * Calcualte the transaciton fee of paypal based on the amount
	 * @param  Jam_Price $amount
	 * @return Jam_Price
	 */
	public function transaction_fee(Jam_Price $amount)
	{
		return Model_Payment_Paypal::transaction_fee_amount($amount);
	}

	/**
	 * Create an adaptive chained payment using the PAY API operation.
	 * https://developer.paypal.com/webapps/developer/docs/classic/api/adaptive-payments/Pay_API_Operation/
	 * Generate an authorization url where the user can go and approve the purchase through paypal.com interface.
	 *
	 * @param  array  $params must provide success_url and cancel_url
	 * @return Model_Payment_Paypal_Chained $this
	 */
	public function authorize_processor(array $params = array())
	{
		$payment = Model_Payment_Paypal_Chained::convert_purchase($this->purchase, $params);
		$payment = Model_Payment_Paypal_Chained::config_auth($payment);

		$response = $payment->do_payment();

		$this->set(array(
			'payment_id' => $response[self::PAYMENT_ID_KEY],
			'raw_response' => $response,
			'status' => Model_Payment::PENDING
		));

		$this->_authorize_url = Payment_Adaptive_Simple::approve_url(
			$this->payment_id,
			empty($params['mobile']) ? FALSE : TRUE
		);

		return $this;
	}

	/**
	 * Check if the purchase has completed successfully after the user has
	 * approved the transaction and returned on the success URL.
	 *
	 * @param  array  $params
	 * @return Model_Payment_Paypal_Chained $this
	 */
	public function execute_processor(array $params = array())
	{
		if ( ! $this->payment_id)
			throw new Exception('No payment_id set in the payment before executing');

		$payment = Payment::instance('Adaptive_PaymentDetails');
		$payment_details = Model_Payment_Paypal_Chained::config_auth($payment)
			->get_payment_details(array(
				self::PAYMENT_ID_KEY => $this->payment_id
			));

		return $this
			->set(array(
				'raw_response' => $payment_details,
				'status' => $payment_details['status'] === 'COMPLETED' ? Model_Payment::PAID : $this->status
			))
			->save();
	}
}