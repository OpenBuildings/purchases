<?php

class Kohana_Model_Store extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'store_purchases' => Jam::association('hasmany', array(
					'inverse_of' => 'store'
				)),
				'purchases' => Jam::association('manytomany', array(
					'join_table' => 'store_purchases',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
			))
			->validator('name', array(
				'present' => TRUE
			));
	}
}