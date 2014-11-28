<?php

class Kohana_Model_Brand extends Jam_Model {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'brand_purchases' => Jam::association('hasmany', array(
					'inverse_of' => 'brand',
					'foreign_model' => 'brand_purchase',
					'delete_on_remove' => Jam_Association::DELETE,
				)),
				'purchases' => Jam::association('manytomany', array(
					'foreign_model' => 'purchase',
					'join_table' => 'brand_purchases',
				)),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
			))
			->validator('name', array(
				'present' => TRUE
			));
	}

	public function purchases_total_price($types)
	{
		$prices = array_map(function($purchase) use ($types) {
			return $purchase->total_price($types);
		}, $this->purchases->as_array());

		return Jam_Price::sum($prices, $this->currency);
	}
}
