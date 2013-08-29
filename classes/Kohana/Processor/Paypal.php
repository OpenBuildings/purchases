<?php defined('SYSPATH') OR die('No direct script access.');

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\ShippingAddress;

class Kohana_Processor_Paypal implements Processor {

	protected static $_api;

	public function next_url()
	{
		return $this->_next_url;
	}

	function __construct($success_url, $cancel_url) 
	{
		$this->_success_url = $success_url;
		$this->_cancel_url = $cancel_url;
	}

	public function cancel_url()
	{
		return $this->_cancel_url;
	}

	public function success_url()
	{
		return $this->_success_url;
	}

	public static function api()
	{
		if ( ! Processor_Paypal::$_api) 
		{
			$oauth = Kohana::$config->load('purchases.processor.paypal.oauth');
			Processor_Paypal::$_api = new ApiContext(new OAuthTokenCredential($oauth['client_id'], $oauth['secret']));

			$config = Kohana::$config->load('purchases.processor.paypal.config');
			Processor_Paypal::$_api->setConfig($config);
		}
		return Processor_Paypal::$_api;
	}

	public static function complete(Model_Payment $payment, array $params)
	{
		$paypal_payement = Payment::get($payment->payment_id, Processor_Paypal::api());
		
		$execution = new PaymentExecution();
		$execution
			->setPayerId($params['payer_id']);

		$response = $paypal_payement->execute($execution, Processor_Paypal::api());

		$transactions = $response->getTransactions();
		$resources = $transactions[0]->getRelatedResources();
		$payment->raw_response = $resources[0]->getSale()->toArray();
		$payment->payment_id = $resources[0]->getSale()->getId();
		$payment->status = Model_Payment::PAID;
	}

	public function execute(Model_Purchase $purchase)
	{
		$payer = new Payer();
		$payer
			->setPaymentMethod('paypal');

		$amount = new Amount();
		$amount
			->setCurrency($purchase->currency)
			->setTotal(number_format($purchase->total_price(array('is_payable' => TRUE)), 2, '.', ''));

		$item_list = new ItemList();
		$items = array();
		foreach ($purchase->store_purchases as $store_purchase) 
		{
			$item = new Item();
			$item
				->setQuantity(1)
				->setName('Products From '.URL::title($store_purchase->store->name()))
				->setPrice(number_format($store_purchase->total_price(array('is_payable' => TRUE)), 2, '.', ''))
				->setCurrency($purchase->currency);

			$items[] = $item;
		}

		$item_list->setItems($items);

		$transaction = new Transaction();
		$transaction
			->setAmount($amount)
			->setItemList($item_list)
			->setDescription('Products from clippings');

		$redirectUrls = new RedirectUrls();
		$redirectUrls
			->setReturnUrl($this->success_url())
			->setCancelUrl($this->cancel_url());

		$payment = new Payment();
		$payment
			->setIntent('sale')
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));

		try 
		{
			$payment->create(Processor_Paypal::api());
		} catch (Exception $e) 
		{
			print_r($e->getData());
			die();
		}

		foreach ($payment->getLinks() as $link)
		{
			if ($link->getRel() == 'approval_url') 
			{
				$this->_next_url = $link->getHref();
				break;
			}
		}

		return array('method' => 'paypal', 'payment_id' => $payment->getId(), 'raw_response' => $payment->toArray(), 'status' => 'pending');
	}
}