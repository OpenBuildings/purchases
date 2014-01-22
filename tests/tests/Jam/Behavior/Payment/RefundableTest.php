<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Unit tests for Jam_Behavior_Payment_Refundable
 *
 * @package    Openbuildings\Purchases
 * @author     Haralan Dobrev <hkdobrev@gmail.com>
 * @copyright  2014 OpenBuildings, Inc.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Jam_Behavior_Payment_RefundableTest extends Testcase_Purchases {

	public function test_add_purchase_item_refund()
	{
		$refund = $this->getMock('Model_Store_Refund', array(
			'add_purchase_item_refund',
		), array(
			'store_refund',
		));

		$refund
			->expects($this->once())
			->method('add_purchase_item_refund');

		Jam_Behavior_Payment_Refundable::add_purchase_item_refund(
			Jam::build('payment'),
			new Jam_Event_Data(array()),
			$refund
		);
	}
}
