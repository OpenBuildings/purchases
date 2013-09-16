<?php defined('SYSPATH') OR die('No direct script access.');

use Openbuildings\Emp\Api;
use Openbuildings\Emp\Threatmatrix;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Emp {

	const THREATMATRIX_SESSION_KEY = '_threatmatrix';

	protected static $_api;

	public static function api()
	{
		if ( ! Emp::$_api) 
		{
			$config = Kohana::$config->load('purchases.processor.emp.api');
			Emp::$_api = new Api($config['gateway_url'], $config['client_id'], $config['api_key']);

			if (isset($config['proxy'])) 
			{
				Emp::$_api->proxy($config['proxy']);
			}

			if (Emp::is_threatmatrix_enabled()) 
			{
				Emp::$_api->threatmatrix(Emp::threatmatrix());
			}
		}
		return Emp::$_api;
	}

	public static function is_threatmatrix_enabled()
	{
		return (bool) Kohana::$config->load('purchases.processor.emp.threatmatrix');
	}

	public static function clear_threatmatrix()
	{
		if (Emp::is_threatmatrix_enabled()) 
		{
			Session::instance()->delete(Emp::THREATMATRIX_SESSION_KEY);
		}
	}

	public static function threatmatrix()
	{
		if (Emp::is_threatmatrix_enabled())
		{
			$threatmatrix = Session::instance()->get(Emp::THREATMATRIX_SESSION_KEY);

			if ( ! $threatmatrix)
			{
				$config = Kohana::$config->load('purchases.processor.emp.threatmatrix');

				$threatmatrix = new Threatmatrix($config['org_id'], $config['client_id']);
				Session::instance()->set(Emp::THREATMATRIX_SESSION_KEY, $threatmatrix);
			}

			return $threatmatrix;
		}
	}
}