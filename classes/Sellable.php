<?php defined('SYSPATH') OR die('No direct script access.');

interface Sellable {

	public function price(Model_Purchase_Item $purchase_item = NULL);

	public function currency();
	
}