<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Store_Refund_Item extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'store_refund' => Jam::association('belongsto', array(
					'inverse_of' => 'items'
				)),
				'purchase_item' => Jam::association('belongsto', array(
					'inverse_of' => 'refund_items',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'amount' => Jam::field('price'),
			))
			->validator('store_refund', 'purchase_item', array('present' => TRUE));
	}

	public function validate()
	{
		if ($this->amount AND round($this->amount->amount(), 2) > abs(round($this->purchase_item_price()->amount(), 2)))
		{
			$this->errors()
				->add('amount', 'numeric_less_than_or_equal_to', array(
					':less_than_or_equal_to' => $this->purchase_item_price()
				));
		}
	}

	public function currency()
	{
		return $this->get_insist('store_refund')->currency();
	}

	public function display_currency()
	{
		return $this->get_insist('store_refund')->display_currency();
	}

	public function monetary()
	{
		return $this->get_insist('store_refund')->monetary();
	}

	public function is_full_amount()
	{
		return ($this->amount === NULL OR $this->amount->is(Jam_Price::EQUAL_TO, $this->purchase_item_price()));
	}

	public function purchase_item_price()
	{
		return $this->get_insist('purchase_item')->total_price();
	}

	public function amount()
	{
		return ($this->amount !== NULL) ? $this->amount : $this->purchase_item_price();
	}
}
