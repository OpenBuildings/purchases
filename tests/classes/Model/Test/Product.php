<?php

class Model_Test_Product extends Jam_Model implements Sellable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->db(Kohana::TESTING)
			->associations(array(
				'variations' => Jam::association('hasmany', array('foreign_model' => 'test_variation')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'price' => Jam::field('float'),
			))
			->validator('type', 'price', 'quantity', array(
				'present' => TRUE
			))
			->validator('price', array('numeric' => TRUE));
	}

	public function price()
	{
		return $this->price;
	}

	public function currency()
	{
		return $this->currency;
	}
}