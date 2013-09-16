<?php defined('SYSPATH') OR die('No direct script access.');

return array(
	'processor'	=> array(
		'emp' => array(
			// 'api' => array(
			// 	'gateway_url' => 'https://my.emerchantpay.com/',
			// 	'client_id' => '00000',
			// 	'api_key' => 'aaaaaa',
			// 	'proxy' => 'aaaaaa',
			// ),
			// 'threatmatrix' => array(
			// 	'org_id' => 'lygdph9h',
			// 	'client_id' => '00000',
			// ),
		),
		'paypal' => array(
			'oauth' => array(
				// 'client_id' => 'APP-00000000000000000',
				// 'secret' => 'dev.example.com',
			),
			'config' => array(
				'mode' => 'sandbox',
				'http.ConnectionTimeOut' => 30,
				'log.LogEnabled' => TRUE,
				'log.FileName' => APPPATH.'logs'.DIRECTORY_SEPARATOR.'paypal.log',
				'log.LogLevel' => 'FINE',
			),
		),
	)
);