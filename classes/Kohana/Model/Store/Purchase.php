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
				'freezable' => Jam::behavior('freezable', array('associations' => 'items', 'parent' => 'purchase')),
			))
			->associations(array(
				'purchase' => Jam::association('belongsto', array(
					'inverse_of' => 'store_purchases'
				)),
				'items' => Jam::association('hasmany', array(
					'inverse_of' => 'store_purchase', 
					'foreign_model' => 'purchase_item', 
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
				'refunds' => Jam::association('hasmany', array(
					'inverse_of' => 'store_purchase', 
					'foreign_model' => 'store_refund', 
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
				'store' => Jam::association('belongsto', array(
					'inverse_of' => 'store_purchases',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
			))
			->validator('purchase', 'store', array('present' => TRUE));
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
		$items = $this->items->as_array();

		if ($types) 
		{
			$items = $this->meta()->events()->trigger('model.filter_items', $this, array($items, (array) $types));
		}

		return $items;
	}

	public function items_count($types = NULL)
	{
		return count($this->items($types));
	}

	public function items_quantity($types = NULL)
	{
		$quantities = array_map(function($item) {
			return $item->quantity;
		}, $this->items($types));

		return $quantities ? array_sum($quantities) : 0;
	}

	public function update_items()
	{
		$this->meta()->events()->trigger('model.update_items', $this);

		return $this;
	}

	public function replace_items($items, $types = NULL)
	{
		$original = $this->items($types);

		$array = Jam_Array_Model::factory()
			->model('purchase_item')
			->load_fields($original)
			->set($items);

		$removed_ids = array_values(array_diff($array->original_ids(), $array->ids()));

		$this->items
			->remove($removed_ids)
			->add($items);

		return $this;
	}

	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));
		
		return Jam_Price::sum($prices, $this->currency(), $this->monetary());
	}

	public function currency()
	{
		return $this->get_insist('purchase')->currency();
	}

	public function paid_at()
	{
		return $this->get_insist('purchase')->paid_at();	
	}

	public function monetary()
	{
		return $this->get_insist('purchase')->monetary();
	}
}