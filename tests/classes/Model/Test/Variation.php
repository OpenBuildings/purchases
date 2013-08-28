<?php

class Model_Test_Variation extends Jam_Model implements Sellable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->db(Kohana::TESTING)

			->associations(array(
				'product' => Jam::association('belongsto', array('foreign_model' => 'test_product')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'price' => Jam::field('float'),
			))
			->validator('name', 'price', 'product', array(
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
		return $this->product->currency;
	}
}