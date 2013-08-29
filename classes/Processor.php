<?php defined('SYSPATH') OR die('No direct script access.');

interface Processor {

	public static function complete(Model_Payment $payment, array $params);
	
	public function execute(Model_Purchase $purchase);

	public function next_url();

}