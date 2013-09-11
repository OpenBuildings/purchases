<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Store_Purchase extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
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
				'refunds' => Jam::association('hasmany', array('inverse_of' => 'store_purchase', 'foreign_model' => 'store_refund')),
				'store' => Jam::association('belongsto'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
			))
			->validator('purchase', 'store', array('present' => TRUE));
	}

	public static function extract_assoc(array $array)
	{
		$assoc = array();

		foreach ($array as $key => $value) 
		{
			if ( ! is_numeric($key))
			{
				$assoc[$key] = $value;
			}
		}

		return $assoc;
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

		$current_flags = NULL;
		
		if (is_array($types)) 
		{
			$current_flags = Model_Store_Purchase::extract_assoc($types);
			$types = array_diff_key($types, $current_flags);
		}

		foreach ($this->items->as_array() as $item) 
		{
			if ($types AND ! (in_array($item->type, (array) $types)))
				continue;

			if ($current_flags AND ! $item->matches_filters($current_flags))
				continue;

			$items []= $item;
		}

		return $items;
	}

	public function items_count($types = NULL)
	{
		return count($this->items($types));
	}

	public function update_items()
	{
		$this->meta()->events()->trigger('model.update_items', $this);

		return $this;
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
		
		return Jam_Price::sum($prices, $this->currency(), $this->monetary());
	}

	public function currency()
	{
		return $this->get_insist('purchase')->currency;
	}

	public function monetary()
	{
		return $this->get_insist('purchase')->monetary();
	}
}