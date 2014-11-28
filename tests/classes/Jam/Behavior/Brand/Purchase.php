<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Brand_Purchase extends Kohana_Jam_Behavior_Brand_Purchase {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.update_items', array($this, 'update_items'));
	}

	public function update_items(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data)
	{
		$brand_purchase->items_updated = TRUE;
	}
}
