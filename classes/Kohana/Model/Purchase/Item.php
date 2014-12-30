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
class Kohana_Model_Purchase_Item extends Jam_Model implements FreezableInterface {

	use FreezableTrait;

	const STI_PREFIX = 'purchase_item_';

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			// Useful for STI
			->table('purchase_items')
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'brand_purchase' => Jam::association('belongsto', array('inverse_of' => 'items')),
				'reference' => Jam::association('belongsto', array(
					'foreign_key' => 'reference_id',
					'polymorphic' => 'reference_model',
				)),
				'refund_items' => Jam::association('hasmany', array(
					'foreign_model' => 'brand_refund_item',
					'foreign_key' => 'purchase_item_id',
					'inverse_of' => 'purchase_item',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'model' => Jam::field('polymorphic'),
				'quantity' => Jam::field('integer', array('default' => 1)),
				'price' => Jam::field('price'),
				'is_payable' => Jam::field('boolean'),
				'is_discount' => Jam::field('boolean'),
				'is_frozen' => Jam::field('boolean'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at' => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('model', 'quantity', array('present' => TRUE))
			->validator('price', array('price' => array('greater_than_or_equal_to' => 0), 'unless' => 'is_discount'))
			->validator('price', array('price' => array('less_than_or_equal_to' => 0), 'if' => 'is_discount'))
			->validator('quantity', array('numeric' => array('only_integer' => TRUE, 'greater_than' => 0)));
	}

	/**
	 * Return items, grouped by brand_purchase_id
	 * @param  array  $items array of Purchase_Items
	 * @return array
	 */
	public static function group_by_brand_purchase(array $items)
	{
		$items = Jam_Array_Model::factory()
			->model('purchase_item')
			->load_fields($items)
			->set($items);

		return Array_Util::group_by($items, function($item) {
			return $item->brand_purchase_id;
		});
	}

	/**
	 * Validate if reference is instanceof Sellable and that price is > 0 (or < 0 for discount items)
	 */
	public function validate()
	{
		if ($this->is_discount AND ! $this->price()->is(Jam_Price::LESS_THAN_OR_EQUAL_TO, 0))
		{
			$this->errors()->add('price', 'numeric_less_than_or_equal_to', array(':less_than_or_equal_to' => 0));
		}

		if ( ! $this->is_discount AND ! $this->price()->is(Jam_Price::GREATER_THAN_OR_EQUAL_TO, 0))
		{
			$this->errors()->add('price', 'numeric_greater_than_or_equal_to', array(':greater_than_or_equal_to' => 0));
		}
	}

	/**
	 * Check if the purchase item is the same as another purchase item,
	 * creterias are reference id, model and purchase item type.
	 *
	 * @param  Model_Purchase_Item $item
	 * @return boolean
	 */
	public function is_same(Model_Purchase_Item $item)
	{
		return ($item->reference_id
			AND $this->reference_id == $item->reference_id
			AND $this->reference_model == $item->reference_model
			AND $this->model == $item->model);
	}

	/**
	 * Return the monetary for this purchase item, get it from parent brand_purchase
	 * @return OpenBuildings\Monetary\Montary
	 */
	public function monetary()
	{
		return $this->get_insist('brand_purchase')->monetary();
	}

	/**
	 * Return the currency for this purchase item, get it from parent brand_purchase
	 * @return string
	 */
	public function currency()
	{
		return $this->get_insist('brand_purchase')->currency();
	}

	/**
	 * Return the display_currency for this purchase item, get it from parent brand_purchase
	 * @return string
	 */
	public function display_currency()
	{
		return $this->get_insist('brand_purchase')->display_currency();
	}

	/**
	 * Compute the price of the reference, converted to this purchase item currency and Monetary
	 * @return Jam_Price
	 */
	public function compute_price()
	{
		$price = $this->get_price();

		if ( ! ($price instanceof Jam_Price))
			throw new Kohana_Exception('Compute price expects the reference :reference to return a Jam_Price', array(
				':reference' => (string) $this->reference
			));

		$price->monetary($this->monetary());
		$price->display_currency($this->display_currency());

		return $price->convert_to($this->currency());
	}

	/**
	 * Get the reference even if it was deleted
	 * @return Jam_Model
	 */
	public function get_reference_paranoid()
	{
		$self = $this;

		return Jam_Behavior_Paranoid::with_filter(Jam_Behavior_Paranoid::ALL, function() use ($self) {
			return $self->reference;
		});
	}

	/**
	 * Freezable implementation, return compute_price or price field
	 * @return Jam_Price
	 */
	public function price()
	{
		return $this->isFrozen() ? $this->price : $this->compute_price();
	}

	/**
	 * Check if item has been refunded
	 * @return boolean
	 */
	public function is_refunded()
	{
		foreach ($this->get_insist('brand_purchase')->refunds->as_array() as $refund)
		{
			if ($refund->has_purchase_item($this))
			{
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Return price() multiplied by quantity field
	 * @return Jam_Price
	 */
	public function total_price()
	{
		$item_price = $this->price();

		// Round before multiplying.
		// Payment processors work only with 2 digits of precision.
		$item_price->amount(round($item_price->amount(), 2));

		return $item_price->multiply_by($this->quantity);
	}

	public function type()
	{
		if ( ! $this->model)
			return NULL;

		return str_replace(static::STI_PREFIX, '', $this->model);
	}

	/**
	 * Get the price of the item.
	 *
	 * @return Jam_Price
	 */
	public function get_price()
	{
		throw new BadMethodCallException('You must implement get_price()');
	}

	public function refunded_amount()
	{
		$amounts = array_map(function($refund_item) {
			return $refund_item->amount();
		}, $this->refund_items->as_array());

		return Jam_Price::sum(
			$amounts,
			$this->currency(),
			$this->monetary(),
			$this->display_currency()
		);
	}

	public function isFrozen()
	{
		return $this->is_frozen;
	}

	public function setFrozen($frozen)
	{
		$this->is_frozen = (bool) $frozen;

		return $this;
	}

	public function performFreeze()
	{
		$this->price = $this->compute_price();
	}

	public function performUnfreeze()
	{
		$this->price = NULL;
	}
}
