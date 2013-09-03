<?php defined('SYSPATH') OR die('No direct script access.');

interface Processor {

	public static function refund(Model_Store_Refund $refund, array $custom_params = array());

	public static function complete(Model_Payment $payment, array $custom_params = array());
	
	public function execute(Model_Purchase $purchase, array $custom_params = array());

	public function next_url();

}