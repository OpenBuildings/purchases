<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Address extends Jam_Model {

	public $required = FALSE;

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->name_key('line1')
			->behaviors(array(
				'location_parent' => Jam::behavior('location_parent', array('parents' => array('city' => 'country'))),
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'purchase' => Jam::association('hasone'),
				'city' => Jam::association('autocreate', array('foreign_model' => 'location')),
				'country' => Jam::association('belongsto', array('foreign_model' => 'location')),
				'purchase' => Jam::association('hasone'),
			))
			->fields(array(
				'id'         => Jam::field('primary'),
				'first_name' => Jam::field('string'),
				'last_name'  => Jam::field('string'),
				'email'      => Jam::field('string'),
				'phone'      => Jam::field('string'),
				'zip'        => Jam::field('string'),
				'line1'      => Jam::field('string'),
				'line2'      => Jam::field('string'),
				'state'      => Jam::field('string'),
				'fax'        => Jam::field('string'),
				'fields_required'   => Jam::field('boolean', array('in_db' => FALSE)),
			))
			->validator(
				'first_name',
				'last_name',
				'city',
				'country',
				'email',
				'line1',
				'zip',
				'phone',
				array('if' => 'fields_required', 'present' => TRUE)
			)
			->validator('email', array('format' => array('email' => TRUE)));
	}
}