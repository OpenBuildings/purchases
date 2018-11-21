<?php defined('SYSPATH') OR die('No direct script access.');

use Clippings\Freezable\FreezableTrait;
use Clippings\Freezable\FreezableInterface;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 *
 * @property Jam_Field_Primary     $id
 * @property Jam_Field_Boolean     $is_frozen
 * @property Jam_Field_Timestamp   $created_id
 * @property Jam_Field_Timestamp   $updated_at
 * @property Model_Purchase        $purchase
 * @property Model_Purchase_Item[] $items
 * @property Model_Brand_Refund[]  $refunds
 * @property Model_Brand           $brand
 */
class Kohana_Model_Brand_Purchase extends Jam_Model implements Purchasable, FreezableInterface {

	use FreezableTrait;

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->name_key('number')
			->behaviors(array(
				'tokenable' => Jam::behavior('tokenable', array('uppercase' => TRUE, 'field' => 'number')),
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'purchase' => Jam::association('belongsto', array(
					'inverse_of' => 'brand_purchases'
				)),
				'items' => Jam::association('hasmany', array(
					'inverse_of' => 'brand_purchase',
					'foreign_model' => 'purchase_item',
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
				'refunds' => Jam::association('hasmany', array(
					'inverse_of' => 'brand_purchase',
					'foreign_model' => 'brand_refund',
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
				'brand' => Jam::association('belongsto', array(
					'inverse_of' => 'brand_purchases',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'is_frozen' => Jam::field('boolean'),
				'created_at'      => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at'      => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('purchase', 'brand', array('present' => TRUE));
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
	 * @return Model_Brand_Purchase self
	 */
	public function add_or_update_item(Model_Purchase_Item $new_item)
	{
		if (($index = $this->search_same_item($new_item)) !== NULL)
		{
			$this->items[$index]->quantity = $this->items[$index]->quantity + $new_item->quantity;
		}
		else
		{
			$this->items->add($new_item);
		}

		$this->items = $this->items;

		return $this;
	}

	public function remove_item(Model_Purchase_Item $item)
	{
		$index = $this->search_same_item($item);

		if (null !== $index)
		{
			unset($this->items[$index]);
		}

		return $this;
	}

	/**
	 * Return items, filtered, trigger model.filter_items to allow adding custom filters
	 * @trigger model.filter_items
	 * @param  array|string $types
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
	 * @param  array|string $types
	 * @return integer
	 */
	public function items_count($types = NULL)
	{  // @codeCoverageIgnore
		return count($this->items($types));
	}

	/**
	 * Return the sum of the quantities of all the items, filtered.
	 * @param  array|string $types
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
	 * @return Model_Brand_Purchase self
	 */
	public function update_items()
	{
		$this->meta()->events()->trigger('model.update_items', $this);

		return $this;
	}

	/**
	 * Replace purchase items, filtered. Removes old items
	 * @param  array $items array of Model_Purchase_Item
	 * @param  array|string $types
	 * @return Model_Brand_Purchase        self
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
	 * Sum the total price of the filtered items.
	 *
	 * @param  array|string $types
	 * @return Jam_Price
	 */
	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));

		return Jam_Price::sum($prices, $this->currency(), $this->monetary(), $this->display_currency());
	}

	/**
	 * Return the currency of all of the price fields in the purchase
	 *
	 * @return string
	 */
	public function currency()
	{
		return $this->get_insist('purchase')->currency();
	}

	/**
	 * The currency used in "humanizing" any of the price fields in the purchase
	 *
	 * @return string
	 */
	public function display_currency()
	{
		return $this->get_insist('purchase')->display_currency();
	}

	/**
	 * Return TRUE if there is a payment and its status is "paid"
	 *
	 * @return boolean
	 */
	public function is_paid()
	{
		return $this->get_insist('purchase')->is_paid();
	}

	/**
	 * Return the date when the associated payment has been created
	 *
	 * @return string|NULL
	 */
	public function paid_at()
	{
		return $this->get_insist('purchase')->paid_at();
	}

	/**
	 * Return Monetary::instance() if not frozen.
	 * Freezable field.
	 *
	 * @return OpenBuildings\Monetary\Monetary
	 */
	public function monetary()
	{
		return $this->get_insist('purchase')->monetary();
	}

	/**
	 * Return the ratio of this brand_purchase as part of the whole purchase
	 * @param  string|array $types filter
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

	public function brand()
	{
		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() {
			return $this->brand;
		});
	}

	public function freeze()
	{
		$this->performFreeze();
		$this->setFrozen(true);
		return $this;
	}

	public function unfreeze()
	{
		$this->performUnfreeze();
		$this->setFrozen(false);
		return $this;
	}

	public function isFrozen()
	{
		return $this->is_frozen;
	}

	protected function setFrozen($frozen)
	{
		$this->is_frozen = (bool) $frozen;

		return $this;
	}

	public function performFreeze()
	{
		foreach ($this->items->as_array() as $item)
		{
			$item->freeze();
		}

		return $this;
	}

	public function performUnfreeze()
	{
		foreach ($this->items->as_array() as $item)
		{
			$item->unfreeze();
		}

		return $this;
	}
}
