<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\shipping
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Array_Util {

	public static function group_by($array, Closure $callback, $preserve_keys = FALSE)
	{
		$grouped = array();

		foreach ($array as $i => $item) 
		{
			$item_group = call_user_func($callback, $item, $i);

			if ( ! isset($grouped[$item_group]))
			{
				$grouped[$item_group] = array();
			}

			if ($preserve_keys)
			{
				$grouped[$item_group][$i] = $item;
			}
			else
			{
				$grouped[$item_group][] = $item;	
			}
		}

		return $grouped;
	}

	public static function not_instance_of(array $items, $class)
	{
		foreach ($items as $index => $item) 
		{
			if ( ! ($item instanceof $class))
				return $index;
		}

		return FALSE;
	}

	public static function validate_instance_of(array $items, $class)
	{
		if (($index = Array_Util::not_instance_of($items, $class)) !== FALSE) 
			throw new Kohana_Exception('The array must be of Model_Purchase_Item object, item [:index] was ":type"', array(
				':type' => is_object($items[$index]) ? get_class($items[$index]) : gettype($items[$index]),
				':index' => $index,
			));
	}
}
