<?php

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment extends Jam_Model {

	const PAID = 'paid';
	const PENDING = 'pending';

	protected $_processor;
	
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
				'purchase' => Jam::association('belongsto', array('inverse_of' => 'payment')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'payment_id' => Jam::field('string'),
				'method' => Jam::field('string'),
				'raw_response' => Jam::field('serialized', array('method' => 'json')),
				'status' => Jam::field('text'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at' => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('purchase', 'method', array('present' => TRUE))
			->validator('method', array('choice' => array('in' => array('emp', 'paypal'))));
	}

	public function complete(array $params = array())
	{
		switch ($this->method) 
		{
			case 'emp':
				Processor_Emp::complete($this, $params);
			break;

			case 'paypal':
				Processor_Paypal::complete($this, $params);
			break;
		}

		return $this;
	}
}