<?php defined('SYSPATH') OR die('No direct script access.');

use OpenBuildings\Monetary\Monetary;
use Clippings\Freezable\FreezableTrait;
use Clippings\Freezable\FreezableInterface;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Purchase extends Jam_Model implements Purchasable, FreezableInterface {

	use FreezableTrait {
		freeze as parentFreeze;
		unfreeze as parentUnfreeze;
	}

	protected $_monetary;

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
				'brand_purchases' => Jam::association('hasmany', array(
					'inverse_of' => 'purchase',
					'foreign_model' => 'brand_purchase',
					'delete_on_remove' => Jam_Association::DELETE,
					'dependent' => Jam_Association::DELETE,
				)),
				'brands' => Jam::association('manytomany', array(
					'join_table' => 'brand_purchases',
					'readonly' => TRUE,
				)),
				'creator' => Jam::association('creator', array('required' => FALSE)),
				'billing_address' => Jam::association('belongsto', array(
					'foreign_model' => 'address',
					'dependent' => Jam_Association::DELETE,
				)),
				'payment' => Jam::association('hasone', array('inverse_of' => 'purchase', 'dependent' => Jam_Association::DELETE)),
			))
			->fields(array(
				'id'              => Jam::field('primary'),
				'currency'        => Jam::field('string'),
				'monetary'        => Jam::field('serialized'),
				'is_frozen'          => Jam::field('boolean'),
				'created_at'      => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at'      => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('currency', array('currency' => TRUE));
	}

	/**
	 * Iterate through the existing brand_purchases and return the one that is linked to this brand.
	 * If none exist build one and return it
	 * @param  Model_Brand $brand
	 * @return Model_Brand_Purchase
	 */
	public function find_or_build_brand_purchase(Model_Brand $brand)
	{
		$brand_purchases = $this->brand_purchases->as_array('brand_id');

		if (isset($brand_purchases[$brand->id()]))
		{
			$brand_purchase = $brand_purchases[$brand->id()];
		}
		else
		{
			$brand_purchase = $this->brand_purchases->build(array('brand' => $brand));
		}

		return $brand_purchase;
	}

	/**
	 * Add item to the brand_purchase that matches the brand given, if it exists, update the quantity, if the brand_purchase does not exist, build it.
	 *
	 * @param Model_Brand              $brand
	 * @param Model_Purchase_Item $new_item
	 * @trigger model.add_item event passing $new_item
	 */
	public function add_item($brand, Model_Purchase_Item $new_item)
	{
		$this
			->find_or_build_brand_purchase($brand)
				->add_or_update_item($new_item);

		$this->meta()->events()->trigger('model.add_item', $this, array($new_item));

		return $this;
	}

	/**
	 * Return Monetary::instance() if not frozen.
	 * Freezable field.
	 *
	 * @return OpenBuildings\Monetary\Monetary
	 */
	public function monetary()
	{
		return $this->isFrozen() ? $this->monetary : Monetary::instance();
	}

	/**
	 * Return the currency for all the field in the purchase
	 *
	 * @return string
	 */
	public function currency()
	{
		return $this->currency;
	}

	/**
	 * The currency used in "humanizing" any of the price fields in the purchase
	 *
	 * @return string
	 */
	public function display_currency()
	{
		return $this->currency;
	}

	/**
	 * Return purchase_items, aggregated from all the brand_purchases. Can pass filters.
	 *
	 * @param  array $types filters
	 * @return array        Model_Purchase_Items
	 */
	public function items($types = NULL)
	{
		$items = array();

		foreach ($this->brand_purchases->as_array() as $brand_purchase)
		{
			$items = array_merge($items, $brand_purchase->items($types));
		}

		return $items;
	}

	/**
	 * Return the sum purchase items count from all brand_purchases
	 *
	 * @param  array $types filters
	 * @return integer
	 */
	public function items_count($types = NULL)
	{
		return count($this->items($types));
	}

	/**
	 * Return the sum of the quantities of all the purchase_items
	 * @param  array $types filters
	 * @return integer
	 */
	public function items_quantity($types = NULL)
	{
		$quantities = array_map(function($item) {
			return $item->quantity;
		}, $this->items($types));

		return $quantities ? array_sum($quantities) : 0;
	}

	/**
	 * Run update items on all the brand_purchases
	 *
	 * @return Model_Purchase $this
	 */
	public function update_items()
	{
		foreach ($this->brand_purchases->as_array() as $brand_purchase)
		{
			$brand_purchase->update_items();
		}

		return $this;
	}

	/**
	 * Replace the purchase items from a given type, removing old items
	 *
	 * @param  array $items array of new items
	 * @param  array $types filters
	 * @return Model_Purchase $this
	 */
	public function replace_items($items, $types = NULL)
	{
		$grouped = Model_Purchase_Item::group_by_brand_purchase($items);
		$current = $this->brand_purchases->as_array('id');

		$replaced = array_intersect_key($grouped, $current);
		$removed = array_diff_key($current, $grouped);

		foreach ($replaced as $index => $items)
		{
			$current[$index]->replace_items($items, $types);
		}

		$this->brand_purchases->remove(array_values($removed));

		return $this;
	}

	/**
	 * Return the sum of all the prices from the purchase items
	 *
	 * @param  array $types filters
	 * @return Jam_Price
	 */
	public function total_price($types = NULL)
	{
		$prices = array_map(function($item) { return $item->total_price(); }, $this->items($types));

		return Jam_Price::sum($prices, $this->currency(), $this->monetary(), $this->display_currency());
	}

	/**
	 * Return TRUE if there is a payment and its status is "paid"
	 *
	 * @return boolean
	 */
	public function is_paid()
	{
		return ($this->payment AND $this->payment->status === Model_Payment::PAID);
	}

	/**
	 * Return the date when the payment has been created
	 *
	 * @return string|NULL
	 */
	public function paid_at()
	{
		return $this->is_paid() ? $this->payment->created_at : NULL;
	}

	public function recheck()
	{
		$this->brand_purchases = array_map(function($item){
			return $item->set('items', $item->items);
		}, $this->brand_purchases->as_array());

		return $this->check();
	}

	public function freeze()
	{
		$this->parentFreeze();

		$this->freezeCollection();

		return $this;
	}

	public function unfreeze()
	{
		$this->parentUnfreeze();

		$this->unfreezeCollection();

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
		$this->monetary = $this->monetary();
	}

	public function performUnfreeze()
	{
		$this->monetary = NULL;
	}

	public function freezeCollection()
	{
		foreach ($this->brand_purchases as $brand_purchase)
		{
			$brand_purchase->freeze();
		}
	}

	public function unfreezeCollection()
	{
		foreach ($this->brand_purchases as $brand_purchase)
		{
			$brand_purchase->unfreeze();
		}
	}
}
