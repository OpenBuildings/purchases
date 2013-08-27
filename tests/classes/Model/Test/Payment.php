<?php

class Model_Test_Payment extends Model_Payment {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);

		parent::initialize($meta);

		$meta->association('purchase')->foreign_model = 'test_purchase';
		$meta->association('purchase')->foreign_key = 'test_purchase_id';
	}
}