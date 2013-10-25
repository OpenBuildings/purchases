<?php

class Model_Variation extends Jam_Model implements Sellable {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'product' => Jam::association('belongsto'),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'price' => Jam::field('price'),
			))
			->validator('name', 'price', 'product', array(
				'present' => TRUE
			))
			->validator('price', array('numeric' => TRUE));
	}

	public function price_for_purchase_item(Model_Purchase_Item $item)
	{
		return $this->price;
	}

	public function currency()
	{
		return $this->product->currency;
	}
}