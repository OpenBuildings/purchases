<?php

class Kohana_Model_Store extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
			))
			->validator('name', array(
				'present' => TRUE
			));
	}
}