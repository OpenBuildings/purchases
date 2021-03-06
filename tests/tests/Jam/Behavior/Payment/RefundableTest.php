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
		$refund = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array('add_purchase_item_refund'))
			->setConstructorArgs(array('brand_refund'))
			->getMock();

		$refund
			->expects($this->once())
			->method('add_purchase_item_refund');

		$refund->transaction_status = Model_Brand_Refund::TRANSACTION_REFUNDED;

		Jam_Behavior_Payment_Refundable::add_purchase_item_refund(
			Jam::build('payment'),
			new Jam_Event_Data(array()),
			$refund
		);

		$refund->transaction_status = 'abc';

		Jam_Behavior_Payment_Refundable::add_purchase_item_refund(
			Jam::build('payment'),
			new Jam_Event_Data(array()),
			$refund
		);
	}

	public function test_add_multiple_purchase_item_refunds()
	{
		$refund = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array('add_purchase_item_refund'))
			->setConstructorArgs(array('brand_refund'))
			->getMock();

		$refund2 = $this->getMockBuilder('Model_Brand_Refund')
			->setMethods(array('add_purchase_item_refund'))
			->setConstructorArgs(array('brand_refund'))
			->getMock();

		$refund
			->expects($this->once())
			->method('add_purchase_item_refund');

		$refund2
			->expects($this->once())
			->method('add_purchase_item_refund');

		$refund->transaction_status = Model_Brand_Refund::TRANSACTION_REFUNDED;
		$refund2->transaction_status = Model_Brand_Refund::TRANSACTION_REFUNDED;

		Jam_Behavior_Payment_Refundable::add_multiple_purchase_item_refunds(
			Jam::build('payment'),
			new Jam_Event_Data(array()),
			array($refund, $refund2)
		);

		$refund->transaction_status = 'abc';
		$refund2->transaction_status = 'abc';

		Jam_Behavior_Payment_Refundable::add_multiple_purchase_item_refunds(
			Jam::build('payment'),
			new Jam_Event_Data(array()),
			array($refund, $refund2)
		);
	}
}
