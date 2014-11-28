<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Brand_Purchase extends Jam_Behavior {

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.filter_items', array($this, 'filter_items'));
	}

	/**
	 * Extract values with numeric keys so that array('product', 'shipping, 'is_payable' => TRUE) will return array('product', 'shipping')
	 * @param  array  $array
	 * @return array
	 */
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

	/**
	 * filter out items from $items that do not match the given filters.
	 *
	 * - is_payable => TRUE|FALSE
	 * - is_discount => TRUE|FALSE
	 * - not => array() - do not match items with this types
	 * - array() - all other non-associative keys are considered type filters, allowing items only with these types
	 *
	 * @param  Model_Brand_Purchase $brand_purchase
	 * @param  Jam_Event_Data       $data
	 * @param  array                $items
	 * @param  array                $filter
	 */
	public function filter_items(Model_Brand_Purchase $brand_purchase, Jam_Event_Data $data, array $items, array $filter)
	{
		$types = Jam_Behavior_Brand_Purchase::extract_types($filter);

		$items = is_array($data->return) ? $data->return : $items;
		$filtered = array();

		foreach ($items as $item)
		{
			if (($types AND ! in_array($item->type(), $types))
				OR (array_key_exists('not', $filter) AND in_array($item->type(), (array) $filter['not']))
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
