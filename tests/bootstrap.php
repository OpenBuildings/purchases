<?php

spl_autoload_register(function($class)
{
	$file = __DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.str_replace('_', '/', $class).'.php';

	if (is_file($file))
	{
		require_once $file;
	}
});

$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->addPsr4('Test\\Omnipay\\Dummy\\', __DIR__.'/src');


Kohana::modules(array(
	'database'         => MODPATH.'database',
	'auth'             => MODPATH.'auth',
	'jam'              => __DIR__.'/../modules/jam',
	'jam-auth'         => __DIR__.'/../modules/jam-auth',
	'jam-monetary'     => __DIR__.'/../modules/jam-monetary',
	'jam-closuretable' => __DIR__.'/../modules/jam-closuretable',
	'jam-locations'    => __DIR__.'/../modules/jam-locations',
	'purchases'        => __DIR__.'/..',
));

Kohana::$config
	->load('database')
		->set('default', array(
			'type'       => 'MySQL',
			'connection' => array(
				'hostname'   => 'localhost',
				'database'   => 'OpenBuildings/purchases',
				'username'   => 'root',
				'password'   => '',
				'persistent' => TRUE,
			),
			'table_prefix' => '',
			'charset'      => 'utf8',
			'caching'      => FALSE,
		));

Kohana::$config
	->load('auth')
		->set('session_key', 'auth_user')
		->set('hash_key', '11111');

Kohana::$environment = Kohana::TESTING;
