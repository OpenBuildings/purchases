<?php defined('SYSPATH') OR die('No direct script access.');

use OpenBuildings\Monetary\Monetary;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Purchase extends Jam_Model {

	protected $_monetary;

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'tokenable' => Jam::behavior('tokenable', array('uppercase' => TRUE, 'field' => 'number')),
				'freezable' => Jam::behavior('freezable', array('fields' => 'monetary', 'associations' => 'store_purchases')),
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'store_purchases' => Jam::association('hasmany', array(
					'inverse_of' => 'purchase', 
					'foreign_model' => 'store_purchase',
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
				'stores' => Jam::association('manytomany', array(
					'join_table' => 'store_purchases', 
					'readonly' => TRUE,
				)),
				'creator' => Jam::association('creator'),
				'billing_address' => Jam::association('belongsto', array(
					'foreign_model' => 'address',
					'dependent' => Jam_Association::DELETE,
				)),
				'payment' => Jam::association('hasone', array('inverse_of' => 'purchase')),
			))
			->fields(array(
				'id'              => Jam::field('primary'),
				'currency'        => Jam::field('string'),
				'monetary'        => Jam::field('serialized'),
				'created_at'      => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at'      => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('currency', array('currency' => TRUE));
	}

	public function find_or_build_store_purchase(Model_Store $store)
	{
		$store_purchases = $this->store_purchases->as_array('store_id');

		if (isset($store_purchases[$store->id()]))
		{
			$store_purchase = $store_purchases[$store->id()];
		}
		else
		{
			$store_purchase = $this->store_purchases->build(array('store' => $store));
		}

		return $store_purchase;
	}

	public function add_item($store, Model_Purchase_Item $new_item)
	{
		$this
			->find_or_build_store_purchase($store)
				->add_or_update_item($new_item);

		$this->meta()->events()->trigger('model.add_item', $this, array($new_item));

		return $this;
	}

	public function monetary()
	{
		return $this->monetary ? $this->monetary : Monetary::instance();
	}

	public function currency()
	{
		return $this->currency;
	}

	public function display_currency()
	{
		return $this->currency;
	}

	public function items($types = NULL)
	{
		$items = array();

		foreach ($this->store_purchases->as_array() as $store_purchase) 
		{
			$items = array_merge($items, $store_purchase->items($types));
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
		foreach ($this->store_purchases->as_array() as $store_purchase) 
		{
			$store_purchase->update_items();
		}

		return $this;
	}

	public function replace_items($items, $types = NULL)
	{
		$grouped = Model_Purchase_Item::group_by_store_purchase($items);
		$current = $this->store_purchases->as_array('id');

		$replaced = array_intersect_key($grouped, $current);
		$removed = array_diff_key($current, $grouped);

		foreach ($replaced as $index => $items) 
		{
			$current[$index]->replace_items($items, $types);
		}

		$this->store_purchases->remove(array_values($removed));

		return $this;
	}

	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));
		
		return Jam_Price::sum($prices, $this->currency(), $this->monetary());
	}

	public function is_paid()
	{
		return ($this->payment AND $this->payment->status === Model_Payment::PAID);
	}

	public function paid_at()
	{
		return $this->is_paid() ? $this->payment->created_at : NULL;
	}

	public function recheck()
	{
		$this->store_purchases = array_map(function($item){
			return $item->set('items', $item->items);
		}, $this->store_purchases->as_array());

		return $this->check();
	}
}