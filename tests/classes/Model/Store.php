<?php

class Model_Store extends Kohana_Model_Store {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta->behaviors(array(
			'paypal_adaptive' => Jam::behavior('paypal_adaptive'),
		));
	}
}
