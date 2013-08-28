<?php

class Kohana_Model_Store_Purchase extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'tokenable' => Jam::behavior('tokenable', array('uppercase' => TRUE, 'field' => 'number')),
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'purchase' => Jam::association('belongsto', array('inverse_of' => 'store_purchases')),
				'items' => Jam::association('hasmany', array('inverse_of' => 'store_purchase', 'foreign_model' => 'purchase_item')),
				'store' => Jam::association('belongsto'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
			))
			->validator('purchase', 'store', array('present' => TRUE));
	}

	public function purchase_insist()
	{
		if ( ! $this->purchase) 
			throw new Kohana_Exception('This Store Purchase does not have a Purchase');

		return $this->purchase;
	}


	public function find_same_item(Model_Purchase_Item $new_item)
	{
		foreach ($this->items as $item) 
		{
			if ($item->is_same($new_item))
			{
				return $item;
			}
		}
	}

	public function add_or_update_item(Model_Purchase_Item $new_item)
	{
		if ($item = $this->find_same_item($new_item)) 
		{
			$item->quantity += $new_item->quantity;
		}
		else
		{
			$this->items->add($new_item);
		}
		return $this;
	}

	public function items($types = NULL)
	{
		$items = array();

		$is_payable = NULL;
		
		if (is_array($types) AND isset($types['is_payable'])) 
		{
			$is_payable = $types['is_payable'];
			unset($types['is_payable']);
		}

		foreach ($this->items->as_array() as $item) 
		{
			if ($types !== NULL AND ! (in_array($item->type, (array) $types)))
				continue;

			if ($is_payable !== NULL AND $item->is_payable !== $is_payable)
				continue;

			$items []= $item;
		}

		return $items;
	}

	public function items_count($types = NULL)
	{
		return count($this->items($types));
	}

	public function freeze_item_prices()
	{
		foreach ($this->items->as_array() as $item) 
		{
			$item->freeze_price();
		}

		return $this;
	}

	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));

		return array_sum($prices);
	}

}