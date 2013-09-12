<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Store_Purchase extends Kohana_Jam_Behavior_Store_Purchase {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.update_items', array($this, 'update_items'));
	}

	public function update_items(Model_Store_Purchase $store_purchase, Jam_Event_Data $data)
	{
		$store_purchase->items_updated = TRUE;
	}
}
