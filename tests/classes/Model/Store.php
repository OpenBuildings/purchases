<?php

class Model_Store extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'products' => Jam::association('hasmany'),
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