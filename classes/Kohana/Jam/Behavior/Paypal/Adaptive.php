<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Behavior to attach to stores if you use AdaptivePayments PayPal API.
 * It would attach a `paypal_email` field to the model.
 *
 * @package   Openbuildings\Purchases
 * @author    Haralan Dobrev <hkdobrev@gmail.com>
 * @copyright 2013 OpenBuildings, Inc.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Paypal_Adaptive extends Jam_Behavior {

	const PAYPAL_EMAIL_FIELD = 'paypal_email';

	public $_field_options = array();

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->field(Jam_Behavior_Paypal_Adaptive::PAYPAL_EMAIL_FIELD, Jam::field('string', $this->_field_options))
			->validator(Jam_Behavior_Paypal_Adaptive::PAYPAL_EMAIL_FIELD, array(
				'format' => array(
					'email' => TRUE
				)
			));
	}
}
