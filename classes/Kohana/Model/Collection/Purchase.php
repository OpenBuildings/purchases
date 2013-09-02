<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Collection_Purchase extends Jam_Query_Builder_Collection {

	public function attempt_first($attempts, $interval = 2)
	{
		while (($attempts >= 0)	AND ! ($first = $this->first())) 
		{
			sleep($interval);
			$attempts--;
			$this->result($this->execute());
		}

		return $first;
	}
}