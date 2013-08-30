<?php

class Model_Test_Store_Refund extends Model_Store_Refund {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);

		parent::initialize($meta);

		$meta->association('store_purchase')->foreign_model = 'test_store_purchase';
		$meta->association('store_purchase')->foreign_key = 'test_store_purchase_id';
		$meta->association('items')->foreign_model = 'test_store_refund_item';
	}
}