<?php

class Model_Test_Purchase_Item extends Model_Purchase_Item {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);
		
		parent::initialize($meta);

		$meta->association('store_purchase')->foreign_model = 'test_store_purchase';
		$meta->association('store_purchase')->foreign_key = 'test_store_purchase_id';
	}
}