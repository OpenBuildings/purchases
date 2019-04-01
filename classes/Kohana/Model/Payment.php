<?php defined('SYSPATH') OR die('No direct script access.');

use Omnipay\Common\GatewayInterface;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment extends Jam_Model {

	const PAID = 'paid';
	const PENDING = 'pending';

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->table('payments')
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
				'payment_refundable' => Jam::behavior('payment_refundable'),
			))
			->associations(array(
				'purchase' => Jam::association('belongsto', array('inverse_of' => 'payment')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'method' => Jam::field('string'),
				'payment_id' => Jam::field('string'),
				'raw_response' => Jam::field('serialized', array('method' => 'json')),
				'status' => Jam::field('string'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at' => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('purchase', 'method', array('present' => TRUE));
	}

	/**
	 * Calculate a transaction fee, based on the price provided. By default returns NULL
	 * @param  Jam_Price $price
	 * @return Jam_Price
	 */
	public function transaction_fee(Jam_Price $price)
	{
		return NULL;
	}

	/**
	 * Executes the purchase and handles events before and after the execution
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function purchase(GatewayInterface $gateway, array $params = array())
	{
		$this->meta()->events()->trigger('model.before_purchase', $this, array($params));

		$response = $this->execute_purchase($gateway, $params);

		$this->purchase->payment = $this;
		$this->purchase->save();

		$this->meta()->events()->trigger('model.after_purchase', $this, array($params));

		if ($this->status === Model_Payment::PAID)
		{
			$this->meta()->events()->trigger('model.pay', $this, array($params));
		}

		return $response;
	}

	/**
	 * Executes the purchase with the provided payment gateway
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function execute_purchase(GatewayInterface $gateway, array $params = array())
	{
		$include_card = (isset($params['card']) && isset($params['card']['number']));
		$params = Arr::merge($params, $this->convert_purchase($include_card));

		$response = $gateway->purchase($params)->send();

		$this->payment_id = $response->getTransactionReference();
		$this->raw_response = $response->getData();

		if ($response->isRedirect())
		{
			$this->status = Model_Payment::PENDING;
		}
		else if ($response->isSuccessful())
		{
			$this->status = Model_Payment::PAID;
		}

		return $response;
	}

	/**
	 * Completes an off site purchase and handles events before and after the completion
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function complete_purchase(GatewayInterface $gateway, array $params = array())
	{
		if ($this->status !== Model_Payment::PENDING)
		{
			throw new Exception_Payment('You must initiate a purchase before completing it');
		}

		$this->meta()->events()->trigger('model.before_complete_purchase', $this, array($params));

		$response = $this->execute_complete_purchase($gateway, $params);

		$this->purchase->payment = $this;
		$this->purchase->save();

		$this->meta()->events()->trigger('model.after_complete_purchase', $this, array($params));

		if ($this->status === Model_Payment::PAID)
		{
			$this->meta()->events()->trigger('model.pay', $this, array($params));
		}

		return $response;
	}

	/**
	 * Completes an off site purchase with the provided payment gateway
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function execute_complete_purchase(GatewayInterface $gateway, array $params = array())
	{
		$include_card = (isset($params['card']) && isset($params['card']['number']));
		$params = Arr::merge($params, $this->convert_purchase($include_card));

		$response = $gateway->completePurchase($params)->send();

		$this->payment_id = $response->getTransactionReference();
		$this->raw_response = $response->getData();

		if ($response->isSuccessful())
		{
			$this->status = Model_Payment::PAID;
		}

		return $response;
	}

	/**
	 * Convert a Model_Purchase object to an array of parameteres, suited for Omnipay
	 *
	 * @return array
	 */
	public function convert_purchase($include_card = FALSE)
	{
		$currency = $this->purchase->display_currency() ?: $this->purchase->currency();

		$params = array(
			'transactionReference' => $this->payment_id ?: $this->purchase->number,
			'currency' => $currency,
			'clientIp' => Request::$client_ip
		);

		if ($include_card)
		{
			$params['card'] = array();

			if ($this->purchase->creator)
			{
				$params['card']['email'] = $this->purchase->creator->email;
			}

			if (($billing = $this->purchase->billing_address))
			{
				$params['card'] = array_merge($params['card'], array_filter(array(
					'firstName'	=> $billing->first_name,
					'lastName'	=> $billing->last_name,
					'address1'	=> $billing->line1,
					'address2'	=> $billing->line2,
					'city'		=> $billing->city ? $billing->city->name() : NULL,
					'country'	=> $billing->country ? $billing->country->short_name : NULL,
					'postcode'	=> $billing->zip,
					'email'		=> $billing->email,
					'phone'		=> $billing->phone,
				)));
			}
		}

		$params['items'] = array_map(function ($item) use ($currency) {
			$name = str_pad($item->reference ? URL::title($item->reference->name(), ' ', TRUE) : $item->type(), 5, '.');

			return array(
				"name"			=> $item->id(),
				"description"	=> $name,
				"quantity"		=> $item->quantity,
				"price"			=> $item->price()->as_string($currency),
			);
		}, $this->purchase->items(array('is_payable' => TRUE)));

		$params['amount'] = $this->purchase->total_price(array('is_payable' => TRUE))->as_string($currency);

		return $params;
	}

	/**
	 * Executes the refund and handles events before and after the execution
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	Model_Brand_Refund							$refund
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function refund(GatewayInterface $gateway, Model_Brand_Refund $refund, array $params = array())
	{
		$this->meta()->events()->trigger('model.before_refund', $this, array($refund, $params));

		$response = $this->execute_refund($gateway, $refund, $params);

		$refund->save();

		$this->meta()->events()->trigger('model.after_refund', $this, array($refund, $params));

		return $response;
	}

	/**
	 * Executes the refund with the provided payment gateway
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	Model_Brand_Refund							$refund
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function execute_refund(GatewayInterface $gateway, Model_Brand_Refund $refund, array $params = array())
	{
		$params = Arr::merge($this->convert_refund($refund), $params);

		$response = $gateway->refund($params)->send();

		$refund->raw_response = $response->getData();
		$refund->transaction_status = ($response->isSuccessful()) ? Model_Brand_Refund::TRANSACTION_REFUNDED : NULL;

		return $response;

	}

	/**
	 * Convert a Model_Brand_Refund object to an array of parameteres, suited for Omnipay
	 * @param  Model_Brand_Refund $refund
	 * @return array
	 */
	public function convert_refund(Model_Brand_Refund $refund)
	{
		$payment = $refund->payment_insist();
		$currency = $refund->display_currency() ?: $refund->currency();

		$params = array(
			'transactionReference'	=> $payment->payment_id,
			'currency'				=> $currency,
		);

		$is_full_refund = $refund->amount()->is(Jam_Price::EQUAL_TO,
			$refund->purchase_insist()->total_price(array('is_payable' => TRUE)));

		if (count($refund->items) AND ! $is_full_refund)
		{
			$params['items'] = array_map(function ($item) use ($currency) {
				return array(
					"name"			=> $item->purchase_item->id(),
					"price"			=> $item->amount()->as_string($currency),
				);
			}, $refund->items->as_array());
		}

		$params['amount'] = $refund->amount()->as_string($currency);

		return $params;
	}

	/**
	 * Executes multiple refunds and handles events before and after the execution
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	Model_Brand_Refund[]						$refunds
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function full_refund(GatewayInterface $gateway, array $refunds, array $params = array())
	{
		$this->meta()->events()->trigger('model.before_full_refund', $this, array($refunds, $params));

		$response = $this->execute_multiple_refunds($gateway, $refunds, $params);

		foreach ($refunds as $refund)
		{
			$refund->save();
		}

		$this->meta()->events()->trigger('model.after_full_refund', $this, array($refunds, $params));

		return $response;
	}

	/**
	 * Executes multiple refunds with the provided payment gateway
	 * @param	\Omnipay\Common\GatewayInterface			$gateway Omnipay payment gateway
	 * @param	Model_Brand_Refund[]						$refunds
	 * @param	array										$params pass this to the gateway
	 * @return	\Omnipay\Common\Message\ResponseInterface	$response payment gateway response
	 */
	public function execute_multiple_refunds(GatewayInterface $gateway, array $refunds, array $params = array())
	{
		$params = Arr::merge($this->convert_multiple_refunds($refunds), $params);

		$response = $gateway->refund($params)->send();

		$raw_response = $response->getData();
		$status = ($response->isSuccessful()) ? Model_Brand_Refund::TRANSACTION_REFUNDED : NULL;

		foreach ($refunds as $refund)
		{
			$refund->raw_response = $raw_response;
			$refund->transaction_status = $status;
		}

		return $response;
	}

	/**
	 * Convert multiple Model_Brand_Refund objects to an array of parameteres, suited for Omnipay
	 * @param  array $refunds
	 * @return array
	 */
	public static function convert_multiple_refunds(array $refunds)
	{
		$payment = $refunds[0]->payment_insist();
		$currency = $refunds[0]->display_currency() ?: $refund->currency();
		$amounts = array();

		$params = array(
			'transactionReference'	=> $payment->payment_id,
			'currency'				=> $currency,
		);

		foreach ($refunds as $refund)
		{
			if (count($refund->items))
			{
				throw new Exception_Payment('Multiple refunds do not support refund items');
			}
			else
			{
				$amounts[] = $refund->amount();
			}
		}

		$params['amount'] = Jam_Price::sum($amounts, $currency)->as_string($currency);

		return $params;
	}
}
