<?php

use OpenBuildings\Monetary\Monetary;

class Kohana_Model_Purchase_Item extends Jam_Model {

	const PRODUCT = 'product';
	
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
			))
			->validator('type', 'quantity', array('present' => TRUE))
			->validator('price', array('numeric' => TRUE))
			->validator('quantity', array('numeric' => array('only_integer' => TRUE, 'greater_than' => 0)));
	}

	public function validate()
	{
		if ($this->reference AND ! ($this->reference instanceof Sellable))
		{
			$this->errors()->add('reference', 'item_not_sellable');
		}

		if ( ! $this->price())
		{
			$this->errors()->add('price', 'null_price');
		}


	}

	public function is_same(Model_Purchase_Item $item)
	{
		return ($item->reference_id 
			AND $this->reference_id == $item->reference_id 
			AND $this->reference_model == $item->reference_model 
			AND $this->type == $item->type);
	}

	public function store_purchase_insist()
	{
		if ( ! $this->store_purchase) 
			throw new Kohana_Exception('This Purchase Item does not have a Store Purchase');

		return $this->store_purchase;
	}

	public function purchase_insist()
	{
		return $this->store_purchase_insist()->purchase_insist();
	}

	public function monetary()
	{
		return $this->purchase_insist()->monetary();
	}

	public function compute_price()
	{
		$current_currency = $this->purchase_insist()->currency;

		return $this->monetary()
					->convert($this->reference->price(), $this->reference->currency(), $current_currency);
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
}
