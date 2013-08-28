<?php

class Kohana_Model_Payment extends Jam_Model {

	const PAID = 'paid';
	
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
				'method' => Jam::field('string'),
				'raw_response' => Jam::field('serialized', array('method' => 'json')),
				'status' => Jam::field('text'),
			))
			->validator('purchase', 'method', array('present' => TRUE));
			->validator('method', array('choice' => array('in' => array('emp', 'paypal'))));
	}
}