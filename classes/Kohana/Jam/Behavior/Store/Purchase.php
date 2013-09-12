<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Jam_Behavior_Store_Purchase extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.filter_items', array($this, 'filter_items'));
	}

	public static function extract_types(array $array)
	{
		$types = array();

		foreach ($array as $key => $value) 
		{
			if (is_numeric($key))
			{
				$types []= $value;
			}
		}

		return $types;
	}

	public function filter_items(Model_Store_Purchase $store_purchase, Jam_Event_Data $data, array $items, array $filter)
	{
		$types = Jam_Behavior_Store_Purchase::extract_types($filter);

		$items = is_array($data->return) ? $data->return : $items;
		$filtered = array();

		foreach ($items as $item)
		{
			if (($types AND ! in_array($item->type, $types))
				OR (array_key_exists('is_payable', $filter) AND $item->is_payable !== $filter['is_payable'])
				OR (array_key_exists('is_discount', $filter) AND $item->is_discount !== $filter['is_discount']))
			{
				continue;
			}

			$filtered [] = $item;
		}

		$data->return = $filtered;
	}
}
