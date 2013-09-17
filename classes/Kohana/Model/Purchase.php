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
				)),
				'creator' => Jam::association('creator'),
				'payment' => Jam::association('hasone', array('inverse_of' => 'purchase')),
			))
			->fields(array(
				'id'              => Jam::field('primary'),
				'currency'        => Jam::field('string'),
				'monetary'        => Jam::field('serialized'),
				'created_at'      => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at'      => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			));
	}

	public function find_or_build_store_purchase($store)
	{
		if (($store_offset = $this->store_purchases->search($store)) === NULL)
		{
			$store_purchase = $this->store_purchases->build(array('store' => $store));
		}
		else
		{
			$store_purchase = $this->store_purchases[$store_offset];
		}

		return $store_purchase;
	}

	public function add_item($store, Model_Purchase_Item $new_item)
	{
		$this
			->find_or_build_store_purchase($store)
				->add_or_update_item($new_item);

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

	public function update_items()
	{
		foreach ($this->store_purchases->as_array() as $store_purchase) 
		{
			$store_purchase->update_items();
		}

		return $this;
	}

	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));
		
		return Jam_Price::sum($prices, $this->currency(), $this->monetary());
	}
}