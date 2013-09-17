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
				'store_refund' => Jam::association('belongsto', array('inverse_of' => 'items')),
				'purchase_item' => Jam::association('belongsto', array('inverse_of' => 'refund')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'amount' => Jam::field('price'),
			))
			->validator('store_refund', 'purchase_item', array('present' => TRUE))
			->validator('amount', array('price' => array('greater_than' => 0)));
	}

	public function validate()
	{
		if ($this->amount AND $this->amount->amount() > $this->get_insist('purchase_item')->price()->amount()) 
		{
			$this->errors()->add('amount', 'numeric_greater_than', array(':greater_than' => $this->get_insist('purchase_item')->price()));
		}
	}

	public function currency()
	{
		return $this->get_insist('store_refund')->currency();
	}

	public function monetary()
	{
		return $this->get_insist('store_refund')->monetary();
	}

	public function amount()
	{
		return ($this->amount !== NULL) ? $this->amount : $this->get_insist('purchase_item')->price();
	}
}