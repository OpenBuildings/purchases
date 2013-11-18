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
				'created_at'      => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at'      => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('purchase', 'store', array('present' => TRUE));
	}

	/**
	 * Search for the same item in items, (using "is_same()" method, and return its index, or NULL if not found)
	 * @param  Model_Purchase_Item $new_item 
	 * @return integer                        
	 */
	public function search_same_item(Model_Purchase_Item $new_item)
	{
		foreach ($this->items as $index => $item) 
		{
			if ($item->is_same($new_item))
			{
				return $index;
			}
		}
	}

	/**
	 * Add the item to items or update an existing one (checked using "search_same_item()")
	 * @param Model_Purchase_Item $new_item 
	 * @return Model_Store_Purchase self
	 */
	public function add_or_update_item(Model_Purchase_Item $new_item)
	{
		if (($index = $this->search_same_item($new_item)) !== NULL) 
		{
			$this->items[$index]->quantity += $new_item->quantity;
		}
		else
		{
			$this->items->add($new_item);
		}
		return $this;
	}

	/**
	 * Return items, filtered, trigger model.filter_items to allow adding custom filters
	 * @trigger model.filter_items
	 * @param  array $types 
	 * @return array        
	 */
	public function items($types = NULL)
	{
		$items = $this->items->as_array();

		if ($types) 
		{
			$items = $this->meta()->events()->trigger('model.filter_items', $this, array($items, (array) $types));
		}

		return $items;
	}

	/**
	 * Return the count of items, filtered
	 * @param  array $types
	 * @return integer
	 */
	public function items_count($types = NULL)
	{  // @codeCoverageIgnore
		return count($this->items($types));
	}

	/**
	 * Return the sum of the quantities of all the items, filtered.
	 * @param  array $types 
	 * @return integer        
	 */
	public function items_quantity($types = NULL) // @codeCoverageIgnore
	{
		$quantities = array_map(function($item) {
			return $item->quantity;
		}, $this->items($types));

		return $quantities ? array_sum($quantities) : 0;
	}

	/**
	 * Trigger model.update_items
	 * @trigger model.update_items
	 * @return Model_Store_Purchase self
	 */
	public function update_items()
	{
		$this->meta()->events()->trigger('model.update_items', $this);

		return $this;
	}

	/**
	 * Replace purchase items, filtered. Removes old items
	 * @param  array $items arrat of Model_Purchase_Item
	 * @param  array $types 
	 * @return Model_Store_Purchase        self
	 */
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

	/**
	 * Sum the total price of the items. filtered.
	 * @param  array $types 
	 * @return Jam_Price        
	 */
	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));
		
		return Jam_Price::sum($prices, $this->currency(), $this->monetary(), $this->display_currency());
	}

	public function currency()
	{
		return $this->get_insist('purchase')->currency();
	}

	public function display_currency()
	{
		return $this->get_insist('purchase')->display_currency();
	}

	public function paid_at()
	{
		return $this->get_insist('purchase')->paid_at();	
	}

	public function monetary()
	{
		return $this->get_insist('purchase')->monetary();
	}

	/**
	 * Return the ratio of this store_purchase as part of the whole purchase
	 * @param  string|arrat $types filter
	 * @return integer
	 */
	public function total_price_ratio($types)
	{
		$price = $this->total_price($types);
		$total_price = $this->get_insist('purchase')->total_price($types);

		if ( ! $price OR ! $total_price OR ! $price->amount() OR ! $total_price->amount())
			return NULL;

		return $price->amount() / $total_price->amount();
	}

	public function store()
	{
		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() {
			return $this->store;
		});
	}
}