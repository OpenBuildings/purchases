<?php

class Model_Test_Store extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->db(Kohana::TESTING)
			->associations(array(
				'products' => Jam::association('hasmany', array('foreign_model' => 'test_products')),
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