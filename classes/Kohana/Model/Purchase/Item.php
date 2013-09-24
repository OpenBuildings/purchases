<?php defined('SYSPATH') OR die('No direct script access.');

use OpenBuildings\Monetary\Monetary;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Purchase_Item extends Jam_Model {

	const PRODUCT = 'product';

	const FILTER_PREFIX = 'matches_filter_';
	
	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
				'freezable' => Jam::behavior('freezable', array('fields' => 'price', 'parent' => 'store_purchase')),
			))
			->associations(array(
				'store_purchase' => Jam::association('belongsto', array('inverse_of' => 'items')),
				'reference' => Jam::association('belongsto', array(
					'foreign_key' => 'reference_id',
					'polymorphic' => 'reference_model',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'type' => Jam::field('string'),
				'quantity' => Jam::field('integer', array('default' => 1)),
				'price' => Jam::field('price'),
				'is_payable' => Jam::field('boolean'),
				'is_discount' => Jam::field('boolean'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at' => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('type', 'quantity', array('present' => TRUE))
			->validator('price', array('price' => array('greater_than_or_equal_to' => 0), 'unless' => 'is_discount'))
			->validator('price', array('price' => array('less_than_or_equal_to' => 0), 'if' => 'is_discount'))
			->validator('quantity', array('numeric' => array('only_integer' => TRUE, 'greater_than' => 0)));
	}

	public static function group_by_store_purchase(array $items)
	{
		$items = Jam_Array_Model::factory()
			->model('purchase_item')
			->load_fields($items)
			->set($items);

		return Array_Util::group_by($items, function($item) {
			return $item->store_purchase_id;
		});
	}

	public function validate()
	{
		if ($this->reference AND ! ($this->reference instanceof Sellable))
		{
			$this->errors()->add('reference', 'item_not_sellable');
		}

		if ($this->is_discount AND $this->price()->is(Jam_Price::GREATER_THAN_OR_EQUAL_TO, 0))
		{
			$this->errors()->add('price', 'numeric_less_than_or_equal_to', array(':less_than_or_equal_to' => 0));
		}

		if ( ! $this->is_discount AND $this->price()->is(Jam_Price::LESS_THAN_OR_EQUAL_TO, 0))
		{
			$this->errors()->add('price', 'numeric_greater_than_or_equal_to', array(':greater_than_or_equal_to' => 0));
		}
	}

	public function is_same(Model_Purchase_Item $item)
	{
		return ($item->reference_id 
			AND $this->reference_id == $item->reference_id 
			AND $this->reference_model == $item->reference_model 
			AND $this->type == $item->type);
	}

	public function monetary()
	{
		return $this->get_insist('store_purchase')->monetary();
	}

	public function currency()
	{
		return $this->get_insist('store_purchase')->currency();
	}

	public function compute_price()
	{
		$price = $this->get_insist('reference')->price_for_purchase_item($this);

		if ( ! ($price instanceof Jam_Price))
			throw new Kohana_Exception('Compute price expects the reference :reference to return a Jam_Price', array(':reference' => (string) $this->reference));

		return $price
			->monetary($this->monetary())
			->convert_to($this->currency());
	}

	public function price()
	{
		return ($this->price === NULL) ? $this->compute_price() : $this->price;
	}

	public function total_price()
	{
		return $this
			->price()
				->multiply_by($this->quantity);
	}
}
