<?php defined('SYSPATH') OR die('No direct access allowed.'); 

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Exception_Frozen extends Kohana_Exception {
	
	function __construct($message = 'This purchase has been paid, no modification is allowed')
	{
		parent::__construct($message, $fields);
	}
}