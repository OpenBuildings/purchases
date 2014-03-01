<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Model_Purchase_Item_Shipping extends Model_Purchase_Item {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta->table('purchase_items');
	}

	public function get_price()
	{
		$reference = $this->get_reference_paranoid();
		return $reference ? $reference->price_for_purchase_item($this) : new Jam_Price(0, 'GBP');
	}
}
