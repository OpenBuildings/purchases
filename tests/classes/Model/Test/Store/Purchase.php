<?php

class Model_Test_Store_Purchase extends Model_Store_Purchase {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);

		parent::initialize($meta);

		$meta->association('purchase')->foreign_model = 'test_purchase';
		$meta->association('purchase')->foreign_key = 'test_purchase_id';
		$meta->association('store')->foreign_model = 'test_store';
		$meta->association('store')->foreign_key = 'test_store_id';
		$meta->association('items')->foreign_model = 'test_purchase_item';
		$meta->association('refunds')->foreign_model = 'test_store_refund';
	}
}