<?php

class Model_Payment_Paypal_Chained extends Kohana_Model_Payment_Paypal_Chained {

	public static function receivers(Model_Purchase $purchase, $currency)
	{
		$receivers = parent::receivers($purchase, $currency);

		$receivers []= array(
			'email' => 'adel-dev@clippings.com',
			'amount' => $purchase->total_price(array('is_payable' => TRUE))
				->as_string($currency),
			'primary' => TRUE
		);

		return $receivers;
	}

	public static function store_purchase_receiver(Model_Store_Purchase $store_purchase)
	{
		$receiver = parent::store_purchase_receiver($store_purchase);

		if ($receiver)
		{
			$receiver['amount'] = $receiver['amount']->multiply_by(1 / 2);
			$receiver['primary'] = FALSE;
		}

		return $receiver;
	}

	/**
	 * Override default receiver list for refunds in tests.
	 * We should have API access to the account to perform the refund.
	 */
	public static function store_refund_receivers(Model_Store_Refund $store_refund, $currency)
	{
		return array(
			array(
				'email' => 'adel-dev@clippings.com',
				'amount' => $store_refund->total_amount()->as_string($currency),
				'primary' => TRUE
			)
		);
	}
}
