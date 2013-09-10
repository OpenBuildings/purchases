<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Purchase extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.update_items', array($this, 'update_items'));
	}

	public function update_items(Model_Purchase $purchase, Jam_Event_Data $data)
	{
		$purchase->items_updated = TRUE;
	}
}
