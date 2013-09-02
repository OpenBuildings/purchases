<?php

class Kohana_Model_Store_Refund extends Jam_Model {

	const REFUNDED = 'refunded';

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

			return array_sum($amounts);
		}
	}

	public function execute()
	{
		$this->check_insist();

		$payment = $this->payment_insist();

		if ($payment->status !== Model_Payment::PAID)
			throw new Kohana_Exception('Payment must be payed in order to perform a refund');

		switch ($payment->method) 
		{
			case 'emp':
				Processor_Emp::refund($this);
			break;
			
			case 'paypal':
				Processor_Paypal::refund($this);
			break;
		}

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

	public function total_amount_in($currency)
	{
		return $this->purchase_insist()->price_in($currency, $this->total_amount());
	}
}