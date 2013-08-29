<?php defined('SYSPATH') OR die('No direct access allowed.'); 

/**
 * Resource_Jam_Exception_Sluggable class
 * Jam Sluggable Exception
 *
 * @package    Despark/jam
 * @author     Yasen Yanev
 * @copyright  (c) 2012 Despark Ltd.
 * @license    http://creativecommons.org/licenses/by-sa/3.0/legalcode
 */
class Kohana_Exception_Frozen extends Kohana_Exception {
	
	function __construct($message = 'This purchase has been paid, no modification is allowed')
	{
		parent::__construct($message, $fields);
	}
}