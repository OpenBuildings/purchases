<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Store_Refund extends Jam_Model {

	const REFUNDED = 'refunded';

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'store_purchase' => Jam::association('belongsto', array('inverse_of' => 'refunds')),
				'items' => Jam::association('hasmany', array('inverse_of' => 'store_refund', 'foreign_model' => 'store_refund_item')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'status' => Jam::field('string'),
				'raw_response' => Jam::field('serialized', array('method' => 'json')),
				'reason' => Jam::field('string'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('store_purchase', array('present' => TRUE));
	}

	public function validate()
	{
		if ($this->total_amount() > $this->store_purchase->total_price(array('is_payable' => TRUE))) 
		{
			$this->errors()->add('items', 'amount_more_than_store_purchase_price');
		}
	}

	public function total_amount()
	{
		if ( ! count($this->items))
		{
			return $this->store_purchase->total_price(array('is_payable' => TRUE));
		}
		else
		{
			$amounts = array_map(function($item) { return $item->amount(); }, $this->items->as_array());
			$currency = $this->purchase_insist()->currency;

			return Jam_Price::sum($currency, $amounts);
		}
	}

	public function execute()
	{
		$this->check_insist();

		$payment = $this->payment_insist();

		if ($payment->status !== Model_Payment::PAID)
			throw new Kohana_Exception('Payment must be payed in order to perform a refund');

		$payment->refund($this);

		return $this;
	}

	public function purchase_insist()
	{
		return $this
			->get_insist('store_purchase')
				->get_insist('purchase');
	}

	public function payment_insist()
	{
		return $this
			->purchase_insist()
				->get_insist('payment');
	}
}