<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Processor_Emp {

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

	public function perchase_params(Model_Purchase $purchase)
	{
		foreach ($variable as $key => $value) 
		{
			# code...
		}
	}
}