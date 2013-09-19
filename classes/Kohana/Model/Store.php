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

	public function purcahses_total_price($types)
	{
		$prices = array_map(function($purchase) use ($types) {
			return $purchase->total_price($types);
		}, $this->purcahses->as_array());

		return Jam_Price::sum($prices, $currency);
	}
}