<?php defined('SYSPATH') OR die('No direct script access.');

interface Kohana_Sellable {

	public function price_for_purchase_item(Model_Purchase_Item $item);
}
