<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'payment'	=> array(
		'Emp' => array(
			'api' => array(
				'api_key' => 'aaaaaa',
				'gateway' => 'https://my.emerchantpay.com/',
				'client_id' => '00000',
			),
			'threatmatrix' => array(
				'org_id' => 'lygdph9h',
				'client_id' => '00000',
			),
		),
		'Paypal' => array(
			'email' => 'dev@example.com',
			'app_id' => 'APP-00000000000000000',
			'username' => 'dev.example.com',
			'password' => '0000000000',
			'signature' => '00000000000000000000000000000000000000000000000000000000',
			'environment' => 'sandbox.',
		),
	)
);