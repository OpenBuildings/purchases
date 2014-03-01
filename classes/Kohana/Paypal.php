<?php defined('SYSPATH') OR die('No direct script access.');

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Paypal {

	protected static $_api;

	public static function api($reload = FALSE)
	{
		if ( ! Paypal::$_api OR $reload)
		{
			$oauth = Kohana::$config->load('purchases.processor.paypal.oauth');
			Paypal::$_api = new ApiContext(new OAuthTokenCredential($oauth['client_id'], $oauth['secret']));

			$config = Kohana::$config->load('purchases.processor.paypal.config');
			Paypal::$_api->setConfig($config);
		}
		return Paypal::$_api;
	}
}