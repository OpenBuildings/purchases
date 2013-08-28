<?php defined('SYSPATH') OR die('No direct script access.');

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