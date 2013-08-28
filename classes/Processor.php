<?php defined('SYSPATH') OR die('No direct script access.');

interface Processor {

	public function execute(Model_Purchase $purchase);

	public function next_url();
}