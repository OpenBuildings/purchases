<?php

class Model_Test_Store_Refund_Item extends Model_Store_Refund_Item {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);

		parent::initialize($meta);

		$meta->association('store_refund')->foreign_model = 'test_store_refund';
		$meta->association('store_refund')->foreign_key = 'test_store_refund_id';
		$meta->association('purchase_item')->foreign_model = 'test_purchase_item';
		$meta->association('purchase_item')->foreign_key = 'test_purchase_item_id';
	}
}