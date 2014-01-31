<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Store_Refund extends Jam_Model {

	const TRANSACTION_REFUNDED = 'refunded';

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
				'creator' => Jam::association('creator', array('required' => FALSE)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'amount' => Jam::field('price'),
				'transaction_status' => Jam::field('string'),
				'raw_response' => Jam::field('serialized', array('method' => 'json')),
				'reason' => Jam::field('string'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('store_purchase', array('present' => TRUE));
	}

	/**
	 * Do not allow refunding more than the given price
	 */
	public function validate()
	{
		$refund_amount = $this->amount();
		$store_purchase = $this->store_purchase_insist();
		$previously_refunded_amount = $store_purchase->total_price('refund');
		$store_purchase_not_refunded_amount = $store_purchase->total_price(array(
			'is_payable' => TRUE,
			'not' => 'refund',
		));

		if ($refund_amount->add($previously_refunded_amount)
			->is(Jam_Price::GREATER_THAN, $store_purchase_not_refunded_amount))
		{
			$this->errors()->add('items', 'amount_more_than_store_purchase_price');
		}
	}

	public function has_purchase_item(Model_Purchase_Item $purchase_item)
	{
		foreach ($this->items->as_array() as $item) 
		{
			if ($item->purchase_item_id == $purchase_item->id() AND $item->is_full_amount()) 
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Total amount to be refunded
	 * @return Jam_Price
	 */
	public function amount()
	{
		if ( ! $this->amount)
		{
			if ( ! count($this->items))
			{
				$this->amount = $this->store_purchase->total_price(array('is_payable' => TRUE));
			}
			else
			{
				$amounts = array_map(function($item) {
					return $item->amount();
				}, $this->items->as_array());

				$this->amount = Jam_Price::sum($amounts, $this->currency(), $this->monetary(), $this->display_currency());
			}
		}

		return $this->amount;
	}

	public function currency()
	{
		return $this->store_purchase_insist()->currency();
	}

	public function display_currency()
	{
		return $this->store_purchase_insist()->display_currency();
	}

	public function monetary()
	{
		return $this->store_purchase_insist()->monetary();
	}

	/**
	 * Call payment->refund
	 * @throws Kohana_Exception If payment is not "paid"
	 * @return Model_Store_refund self
	 */
	public function execute()
	{
		$this->check_insist();

		$payment = $this->payment_insist();

		if ($payment->status !== Model_Payment::PAID)
			throw new Kohana_Exception('Payment must be payed in order to perform a refund');

		$payment->refund($this);

		return $this;
	}

	public function store_purchase_insist()
	{
		return $this->get_insist('store_purchase');
	}

	public function purchase_insist()
	{
		return $this
			->store_purchase_insist()
				->get_insist('purchase');
	}

	public function payment_insist()
	{
		return $this
			->purchase_insist()
				->get_insist('payment');
	}

	/**
	 * @return Model_Purchase_Item_Refund the item added
	 */
	public function add_purchase_item_refund()
	{
		$store_purchase = $this->store_purchase_insist();

		$purchase_item_refund = Jam::build('purchase_item_refund', array(
			'reference' => $this,
			'store_purchase' => $store_purchase,
		));

		$store_purchase->purchase->is_just_frozen = TRUE;
		$store_purchase->items->add($purchase_item_refund);
		$store_purchase->freeze()->save();
		$store_purchase->purchase->is_just_frozen = FALSE;

		return $purchase_item_refund;
	}
}
