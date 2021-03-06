<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Purchase_Item_Product extends Model_Purchase_Item {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->fields(array(
				'is_payable' => Jam::field('boolean', array(
					'default' => TRUE
				))
			));
	}

	public function get_price()
	{
		$reference = $this->get_reference_paranoid();
		return $reference ? $reference->price_for_purchase_item($this) : new Jam_Price(0, 'GBP');
	}
}
