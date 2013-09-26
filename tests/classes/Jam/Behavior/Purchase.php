<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Purchase extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.add_item', array($this, 'add_item'));
	}

	public function add_item(Model_Purchase $purchase, Jam_Event_Data $data, Model_Purchase_Item $item)
	{
		$purchase->item_added = $item;
	}
}
