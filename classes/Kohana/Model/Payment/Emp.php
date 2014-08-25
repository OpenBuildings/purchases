<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment_Emp extends Model_Payment {

	/**
	 * Convert a Model_Purcahse object to an array of parameteres, suited for Emp Payment processor
	 * Uses discount items, payable items, billing address as well as customer email and ip_address
	 * If environment != Kohana::PRODUCTION, adds a "test_transaction" => 1
	 *
	 * @param  Model_Purchase $purchase
	 * @return array
	 */
	public static function convert_purchase(Model_Purchase $purchase)
	{
		$currency = $purchase->display_currency() ?: $purchase->currency();

		$params = array(
			'payment_method'         => 'creditcard',
			'order_reference'        => $purchase->number,
			'order_currency'         => $currency,
			'ip_address'             => Request::$client_ip,
			'credit_card_trans_type' => 'sale',
		);

		if ($purchase->creator)
		{
			$params['customer_email'] = $purchase->creator->email;
		}

		foreach ($purchase->items(array('is_payable' => TRUE)) as $i => $item)
		{
			$index = $i+1;

			$name = str_pad($item->reference ? URL::title($item->reference->name(), ' ', TRUE) : $item->type(), 5, '.');

			$params = array_merge($params, array(
				"item_{$index}_predefined"            => '0',
				"item_{$index}_digital"               => '0',
				"item_{$index}_code"                  => $item->id(),
				"item_{$index}_qty"                   => $item->quantity,
				"item_{$index}_discount"              => $item->is_discount ? '1' : '0',
				"item_{$index}_name"                  => $name,
				"item_{$index}_unit_price_".$currency => $item->price()->as_string($currency),
			));
		}

		if (($billing = $purchase->billing_address))
		{
			$params = array_merge($params, array_filter(array(
				'customer_first_name' => $billing->first_name,
				'customer_last_name'  => $billing->last_name,
				'customer_address'    => $billing->line1,
				'customer_address2'   => $billing->line2,
				'customer_city'       => $billing->city ? $billing->city->name() : NULL,
				'customer_country'    => $billing->country ? $billing->country->short_name : NULL,
				'customer_postcode'   => $billing->zip,
				'customer_email'      => $billing->email,
				'customer_phone'      => $billing->phone,
			)));
		}

		return $params;
	}

	/**
	 * Find the item_id from a cart array, returned from emp
	 * @param  array  $cart
	 * @param  string $id
	 * @return string
	 */
	public static function find_item_id(array $cart, $id)
	{
		$items = isset($cart['item'][0]) ? $cart['item'] : array($cart['item']);
		foreach ($items as $item)
		{
			if ($item['code'] == $id)
				return $item['id'];
		}
	}

	/**
	 * Convert a Model_Store_Refund object to an array of parameteres, suited for Emp Payment processor. Uses purchase's payment object and its raw_response to extract the needed ids.
	 * @param  Model_Store_Refund $refund
	 * @return array
	 */
	public static function convert_refund(Model_Store_Refund $refund)
	{
		$payment = $refund->payment_insist();
		$currency = $refund->display_currency() ?: $refund->currency();

		$params = array(
			'order_id'         => $payment->raw_response['order_id'],
			'trans_id'         => $payment->payment_id,
			'reason'           => $refund->reason,
		);

		$is_full_refund = $refund->amount()->is(Jam_Price::EQUAL_TO,
			$refund->purchase_insist()->total_price(array('is_payable' => TRUE)));

		if (count($refund->items) AND ! $is_full_refund)
		{
			foreach ($refund->items as $i => $item)
			{
				$index = $i+1;
				$item_params = array(
					"item_{$index}_id" => Model_Payment_Emp::find_item_id($payment->raw_response['cart'], $item->purchase_item->id()),
				);

				$item_params["item_{$index}_amount"] = $item->amount()->as_string($currency);

				$params = array_merge($params, $item_params);
			}
		}
		else
		{
			$params['amount'] = $refund->amount()->as_string($currency);
		}

		return $params;
	}

	/**
	 * Convert multiple Model_Store_Refund objects to an array of parameteres, suited for Emp Payment processor. Uses purchase's payment object and its raw_response to extract the needed ids.
	 * @param  array $refunds
	 * @return array
	 */
	public static function convert_multiple_refunds(array $refunds)
	{
		$payment = $refunds[0]->payment_insist();
		$currency = $refunds[0]->display_currency() ?: $refund->currency();
		$monetary = $refunds[0]->monetary();
		$amounts = array();

		$params = array(
			'order_id'         => $payment->raw_response['order_id'],
			'trans_id'         => $payment->payment_id,
			'reason'           => $refunds[0]->reason,
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

		$params['amount'] = Jam_Price::sum($amounts, $currency, $monetary)->as_string($currency);

		return $params;
	}

	/**
	 * Calculate the transaction_fee of Emp Payment processor based on the given amount
	 * @param  Jam_Price $amount
	 * @return Jam_Price
	 */
	public function transaction_fee(Jam_Price $amount)
	{
		return $amount
				->multiply_by(0.0335)
				->add(new Jam_Price(0.22, 'EUR'));
	}

	/**
	 * Perform a payment of the current purchase, set the reponse in payment_id, raw_response and status.
	 * @param  array  $params
	 * @return Model_Payment_Emp         self
	 */
	public function execute_processor(array $params = array())
	{
		$params = array_merge($params, Model_Payment_Emp::convert_purchase($this->purchase));

		try
		{
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

	/**
	 * Perform a refund based on a given refund object. Set the refund's raw_response and status accordingly
	 * @param  Model_Store_Refund $refund
	 * @param  array              $custom_params
	 * @return Model_Payment_Emp                            self
	 */
	public function refund_processor(Model_Store_Refund $refund, array $custom_params = array())
	{
		$params = Model_Payment_Emp::convert_refund($refund);

		try
		{
			$response = Emp::api()
				->request(Openbuildings\Emp\Api::ORDER_CREDIT, $params);
		}
		catch (Openbuildings\Emp\Exception $exception)
		{
			throw new Exception_Payment('Payment gateway error: :error', array(':error' => $exception->getMessage()), 0, $exception);
		}

		$refund->raw_response = $response;
		$refund->transaction_status = ($response['transaction_response'] == 'A') ? Model_Store_Refund::TRANSACTION_REFUNDED : NULL;

		return $this;
	}

	/**
	 * Perform a refund based on multiple store refund objects. Set the refund's raw_response and status accordingly
	 * @param  Model_Store_Refund[]	$refunds
	 * @param  array                $custom_params
	 * @return Model_Payment_Emp    self
	 */
	public function multiple_refunds_processor(array $refunds, array $custom_params = array())
	{
		$params = Model_Payment_Emp::convert_multiple_refunds($refunds);

		try
		{
			$response = Emp::api()
				->request(Openbuildings\Emp\Api::ORDER_CREDIT, $params);
		}
		catch (Openbuildings\Emp\Exception $exception)
		{
			throw new Exception_Payment('Payment gateway error: :error', array(':error' => $exception->getMessage()), 0, $exception);
		}

		foreach ($refunds as $refund)
		{
			$refund->raw_response = $response;
			$refund->transaction_status = ($response['transaction_response'] == 'A') ? Model_Store_Refund::TRANSACTION_REFUNDED : NULL;
		}

		return $this;
	}
}
