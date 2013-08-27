<?php defined('SYSPATH') OR die('No direct script access.');

interface Sellable {

	public function price();

	public function currency();
	
}