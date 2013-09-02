<?php

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
				'amount' => Jam::field('decimal'),
			))
			->validator('store_refund', 'purchase_item', array('present' => TRUE))
			->validator('amount', array('numeric' => array('greater_than' => 0)));
	}

	public function validate()
	{
		if ($this->amount AND $this->amount > $this->purchase_item->price()) 
		{
			$this->errors()->add('amount', 'numeric_greater_than', array(':greater_than' => $this->purchase_item->price()));
		}
	}

	public function amount()
	{
		return ($this->amount !== NULL) ? $this->amount : $this->purchase_item->price();
	}
}