<?php

class Model_Payment_Paypal_Chained extends Kohana_Model_Payment_Paypal_Chained {

	/**
	 * Override default receiver list for refunds in tests.
	 * We should have API access to the account to perform the refund.
	 */
	public static function store_refund_receivers(Model_Store_Refund $store_refund, $currency)
	{
		return array(
			array(
				'email' => 'adel-dev@clippings.com',
				'amount' => $store_refund->total_amount()->as_string($currency)
			)
		);
	}

	public static function receivers(Model_Purchase $purchase, $currency)
	{
		$receivers = parent::receivers($purchase, $currency);

		$receivers[0]['amount'] = number_format(( (float) $receivers[0]['amount']) / 2, 2);

		foreach ($receivers as $index => $receiver)
		{
			$receivers[$index]['primary'] = FALSE;
		}

		$receivers []= array(
			'email' => 'adel-dev@clippings.com',
			'amount' => $purchase->total_price(array('is_payable' => TRUE))
				->as_string($currency),
			'primary' => TRUE
		);

		return $receivers;
	}
}
