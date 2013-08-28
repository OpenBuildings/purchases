<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Processor {

	public function tracking_html()
	{
		
	}

	public function redirect($back)
	{
		
	}

	public function complete(array $params)
	{
		
	}

	public function execute(array $params)
	{
		if ( ! $this->redirect()) 
		{
			$this->complete($params);
		}
	}
}