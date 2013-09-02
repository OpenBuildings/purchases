<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Emp_Form extends Jam_Validated {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->fields(array(
				'card_holder_name' => Jam::field('string'),
				'card_number' => Jam::field('string'),
				'exp_month' => Jam::field('string'),
				'exp_year' => Jam::field('string'),
				'cvv' => Jam::field('string'),
			))
			->validator('card_holder_name', 'card_number', 'exp_month', 'exp_year', 'cvv', array('present' => TRUE))
			->validator('card_holder_name', array('length' => array('minimum' => 3, 'maximum' => 40)))
			->validator('card_number', array('length' => array('maximum' => 20), 'format' => array('credit_card' => TRUE)))
			->validator('exp_month', array('format' => array('regex' => '/\d{2}/')))
			->validator('exp_year', array('format' => array('regex' => '/\d{2}/')))
			->validator('cvv', array('format' => array('regex' => '/\d{2,4}/')));
	}
}