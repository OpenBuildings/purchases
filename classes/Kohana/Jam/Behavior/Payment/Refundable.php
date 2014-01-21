<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Jam behavior for payments which could refund.
 * It would subscribe to the model.after_refund event of the payment
 * and it would create a new purchase_item_refund in the store purchases.
 *
 * @package    Openbuildings\Purchases
 * @author     Haralan Dobrev <hkdobrev@gmail.com>
 * @copyright  2014 OpenBuildings, Inc.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Payment_Refundable extends Jam_Behavior {

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind(
				'model.after_refund',
				'Jam_Behavior_Payment_Refundable::add_purchase_item_refund'
			);
	}

	public static function add_purchase_item_refund(Model_Payment $payment, Jam_Event_Data $data, Model_Store_Refund $refund)
	{
		$refund->add_purchase_item_refund();
	}
}
