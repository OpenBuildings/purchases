<?php

/**
 * Field for prices, money, amounts, costs an others:
 *  - set it like a string
 *  - validate it like a float
 *  - insert it (into the database) like a decimal
 *
 * @package    Openbuildings\Purchases
 * @author     Haralan Dobrev <hdobrev@despark>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Field_Decimal extends Jam_Field_String {

	public $default = NULL;

	public $allow_null = TRUE;

	public $convert_empty = TRUE;

	public $precision = 2;

	/**
	 * Casts to a string, preserving NULLs along the way.
	 *
	 * @param   mixed   $value
	 * @return  string
	 */
	public function set(Jam_Validated $model, $value, $is_changed)
	{
		list($value, $return) = $this->_default($model, $value);

		return $value;
	}

	protected function _default(Jam_Validated $model, $value)
	{
		$return = FALSE;

		$value = $this->run_filters($model, $value);

		// Convert empty values to NULL, if needed
		if ($this->convert_empty AND empty($value) AND $value !== 0 AND $value !== 0.0)
		{
			$value  = $this->empty_value;
			$return = TRUE;
		}

		// Allow NULL values to pass through untouched by the field
		if ($this->allow_null AND $value === NULL)
		{
			$value  = NULL;
			$return = TRUE;
		}

		return array($value, $return);
	}

	/**
	 * Called just before saving.
	 *
	 * If $in_db, it is expected to return a value suitable for insertion
	 * into the database.
	 *
	 * @param   Jam_Model  $model
	 * @param   mixed        $value
	 * @param   bool         $loaded
	 * @return  mixed
	 */
	public function convert(Jam_Validated $model, $value, $is_loaded)
	{
		if ($value === NULL)
			return NULL;

		$value = (float) $value;
		if (is_numeric($this->precision))
		{
			$value = round($value, $this->precision);
		}
		
		return $value;
	}
}