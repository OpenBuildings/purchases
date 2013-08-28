<?php

use OpenBuildings\Monetary\Monetary;

class Kohana_Model_Purchase extends Jam_Model {

	protected $_monetary;

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'tokenable' => Jam::behavior('tokenable', array('uppercase' => TRUE, 'field' => 'number')),
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'store_purchases' => Jam::association('hasmany', array(
					'inverse_of' => 'purchase', 
					'delete_on_remove' => Jam_Association::DELETE,
				)),
				'creator' => Jam::association('creator'),
				'payment' => Jam::association('hasone', array('inverse_of' => 'purchase')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'currency' => Jam::field('string'),
				'monetary_source' => Jam::field('serialized'),
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
		if ($this->monetary_source) 
		{
			if ( ! $this->_monetary) 
			{
				$this->_monetary = new Monetary($this->currency, $this->monetary_source);
			}
			return $this->_monetary;
		}

		return Monetary::instance();
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

	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));

		return array_sum($prices);
	}

	public function freeze_item_prices()
	{
		foreach ($this->store_purchases->as_array() as $store_purchase) 
		{
			$store_purchase->freeze_item_prices();
		}

		return $this;
	}

	public function freeze_monetary()
	{
		$this->monetary_source = Monetary::instance()->source();
		
		return $this;
	}

	public function pay(Processor $processor)
	{
		$this->payment = $processor->execute($this);
	}

}