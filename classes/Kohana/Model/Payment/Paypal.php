<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment_Paypal extends Model_Payment {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->table('payments');
	}

	public static function convert_purchase(Model_Purchase $purchase, array $params = array())
	{
		$payer = new PayPal\Api\Payer();
		$payer
			->setPaymentMethod('paypal');

		$amount = new PayPal\Api\Amount();
		$amount
			->setCurrency($purchase->currency)
			->setTotal(number_format($purchase->total_price(array('is_payable' => TRUE)), 2, '.', ''));

		$item_list = new PayPal\Api\ItemList();
		$items = array();
		foreach ($purchase->store_purchases as $store_purchase) 
		{
			$item = new PayPal\Api\Item();
			$item
				->setQuantity(1)
				->setName('Products From '.URL::title($store_purchase->store->name()))
				->setPrice(number_format($store_purchase->total_price(array('is_payable' => TRUE)), 2, '.', ''))
				->setCurrency($purchase->currency);

			$items[] = $item;
		}

		$item_list->setItems($items);

		$transaction = new PayPal\Api\Transaction();
		$transaction
			->setAmount($amount)
			->setItemList($item_list)
			->setDescription('Products from clippings');

		$redirectUrls = new PayPal\Api\RedirectUrls();
		$redirectUrls
			->setReturnUrl($params['success_url'])
			->setCancelUrl($params['cancel_url']);

		$payment = new PayPal\Api\Payment();
		$payment
			->setIntent('sale')
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));

		return $payment;
	}

	public static function convert_refund(Model_Store_Refund $refund)
	{
		$amount = new PayPal\Api\Amount();
		$amount
			->setCurrency($refund->purchase_insist()->currency)
			->setTotal(number_format($refund->total_amount(), 2, '.', ''));

		$paypal_refund = new PayPal\Api\Refund();
		$paypal_refund
			->setAmount($amount);

		return $paypal_refund;
	}

	public function authorize(array $params = array())
	{
		$payment = Model_Payment_Paypal::convert_purchase($this->purchase, $params);

		$payment->create(Paypal::api());

		foreach ($payment->getLinks() as $link)
		{
			if ($link->getRel() == 'approval_url') 
			{
				$this->_authorize_url = $link->getHref();
				break;
			}
		}

		$this->set(array(
			'method' => 'paypal', 
			'payment_id' => $payment->getId(), 
			'raw_response' => $payment->toArray(), 
			'status' => Model_Payment::PENDING
		));

		return $this;
	}

	public function execute(array $params = array())
	{
		$paypal_payement = PayPal\Api\Payment::get($this->payment_id, Paypal::api());
		
		$execution = new PayPal\Api\PaymentExecution();
		$execution
			->setPayerId($params['payer_id']);

		$response = $paypal_payement->execute($execution, Paypal::api());

		$transactions = $response->getTransactions();
		$resources = $transactions[0]->getRelatedResources();
		$sale = $resources[0]->getSale();

		$this->set(array(
			'raw_response' => $sale->toArray(),
			'payment_id' => $sale->getId(),
			'status' => ($sale->getState() == 'completed') ? Model_Payment::PAID : $sale->getState(),
		));

		return $this;
	}

	public function refund(Model_Store_Refund $refund, array $custom_params = array())
	{
		$paypal_refund = Model_Payment_Paypal::convert_refund($refund);

		$sale = PayPal\Api\Sale::get($this->payment_id, Paypal::api());
		$paypal_payement = PayPal\Api\Payment::get($sale->getParentPayment(), Paypal::api());

		$response = $sale->refund($paypal_refund, Paypal::api(TRUE));

		$refund->raw_response = $response->toArray();
		$refund->status = ($response->getState() == 'completed') ? Model_Store_Refund::REFUNDED : $response->getState();

		return $this;
	}
}