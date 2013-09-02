<?php

use OpenBuildings\Monetary\Monetary;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Purchase_Item extends Jam_Model {

	const PRODUCT = 'product';
	
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
				'store_purchase' => Jam::association('belongsto', array('inverse_of' => 'items')),
				'reference' => Jam::association('belongsto', array(
					'foreign_key' => 'reference_id',
					'polymorphic' => 'reference_model',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'type' => Jam::field('string'),
				'quantity' => Jam::field('integer'),
				'price' => Jam::field('decimal'),
				'is_payable' => Jam::field('boolean'),
				'is_discount' => Jam::field('boolean'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at' => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('type', 'quantity', array('present' => TRUE))
			->validator('price', array('numeric' => array('greater_than_or_equal_to' => 0), 'unless' => 'is_discount'))
			->validator('price', array('numeric' => array('less_than_or_equal_to' => 0), 'if' => 'is_discount'))
			->validator('quantity', array('numeric' => array('only_integer' => TRUE, 'greater_than' => 0)));
	}

	public function validate()
	{
		if ($this->reference AND ! ($this->reference instanceof Sellable))
		{
			$this->errors()->add('reference', 'item_not_sellable');
		}

		if ($this->is_discount AND $this->price() >= 0)
		{
			$this->errors()->add('price', 'numeric_less_than_or_equal_to', array(':less_than_or_equal_to' => 0));
		}

		if ( ! $this->is_discount AND $this->price() <= 0)
		{
			$this->errors()->add('price', 'numeric_greater_than_or_equal_to', array(':greater_than_or_equal_to' => 0));
		}
	}

	public function matches_flags(array $flags)
	{
		foreach ($flags as $name => $value) 
		{
			if ($this->{$name} !== $value) 
				return FALSE;
		}

		return TRUE;
	}

	public function is_same(Model_Purchase_Item $item)
	{
		return ($item->reference_id 
			AND $this->reference_id == $item->reference_id 
			AND $this->reference_model == $item->reference_model 
			AND $this->type == $item->type);
	}

	public function purchase_insist()
	{
		return $this->get_insist('store_purchase')->get_insist('purchase');
	}

	public function monetary()
	{
		return $this->purchase_insist()->monetary();
	}

	public function compute_price()
	{
		$currency = $this->reference->currency($this);
		$price = $this->reference->price($this);

		if ( ! $currency) 
		{
			return $price;
		}
		else
		{
			$purchase_currancy = $this->purchase_insist()->currency;

			return $this
				->monetary()
					->convert($price, $currency, $purchase_currancy);
		}
	}

	public function price()
	{
		return ($this->price === NULL) ? $this->compute_price() : $this->price;
	}

	public function freeze_price()
	{
		$this->price = (float) $this->price();
	}

	public function total_price()
	{
		return $this->price() * $this->quantity;
	}

	public function total_price_in($currency, $types = NULL)
	{
		return $this->purchase_insist()->price_in($currency, $this->total_price($types));
	}
}
