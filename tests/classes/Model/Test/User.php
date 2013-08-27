<?php

class Model_Test_User extends Model_User {

	public static function initialize(Jam_Meta $meta)
	{
		$meta->db(Kohana::TESTING);
		
		parent::initialize($meta);

		foreach ($meta->associations() as $association) 
		{
			$association->foreign_model = 'test_'.$association->foreign_model;
		}
	}
}