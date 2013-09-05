<?php

class Model_Product extends Jam_Model implements Sellable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->associations(array(
				'variations' => Jam::association('hasmany'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'price' => Jam::field('float'),
			))
			->validator('name', 'price', 'currency', array(
				'present' => TRUE
			))
			->validator('price', array('numeric' => TRUE));
	}

	public function price(Model_Purchase_Item $item)
	{
		return $this->price;
	}

	public function currency(Model_Purchase_Item $item)
	{
		return $this->currency;
	}
}