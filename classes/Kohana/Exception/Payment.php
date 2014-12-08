<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Exception_Payment extends Kohana_Exception {

	protected $data;

	public function __construct($message = "", array $variables = NULL, $code = 0, Exception $previous = NULL, $data = array())
	{
		if ($data AND ! is_array($data))
		{
			$data = json_decode($data, TRUE);
		}

		parent::__construct($message, $variables, $code, $previous, $data);

		if (is_array($data))
		{
			$this->data = $data;
		}
	}

	public function data()
	{
		return $this->data;
	}
}
