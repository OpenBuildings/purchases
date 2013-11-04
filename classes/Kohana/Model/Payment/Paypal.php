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

	/**
	 * Convert a Model_Purchase to a PayPal\Api\Payment object.
	 * Discount prices are not supported, so that each store_purchase is combined and added as a separate item.
	 *
	 * @param  Model_Purchase $purchase
	 * @param  array          $params
	 * @return PayPal\Api\Payment
	 */
	public static function convert_purchase(Model_Purchase $purchase, array $params = array())
	{
		$currency = $purchase->display_currency() ?: $purchase->currency();

		$payer = new PayPal\Api\Payer();
		$payer
			->setPaymentMethod('paypal');

		$amount = new PayPal\Api\Amount();
		$amount
			->setCurrency($currency)
			->setTotal($purchase->total_price(array('is_payable' => TRUE))->as_string($currency));

		$item_list = new PayPal\Api\ItemList();
		$items = array();
		foreach ($purchase->store_purchases as $store_purchase)
		{
			$item = new PayPal\Api\Item();
			$item
				->setQuantity(1)
				->setName('Products From '.URL::title($store_purchase->store->name()))
				->setPrice($store_purchase->total_price(array('is_payable' => TRUE))->as_string($currency))
				->setCurrency($currency);

			$items[] = $item;
		}

		$item_list->setItems($items);

		$transaction = new PayPal\Api\Transaction();
		$transaction
			->setAmount($amount)
			->setItemList($item_list);

		if ( ! empty($params['description']))
		{
			$transaction->setDescription($params['description']);
		}

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

	/**
	 * Convert Model_Store_Refund to a PayPal\Api\Refund
	 * Do not convert individual refund items as the refund does not support that.
	 *
	 * @param  Model_Store_Refund $refund
	 * @return PayPal\Api\Refund
	 */
	public static function convert_refund(Model_Store_Refund $refund)
	{
		$amount = new PayPal\Api\Amount();
		$amount
			->setCurrency($refund->purchase_insist()->currency)
			->setTotal($refund->total_amount()->as_string());

		$paypal_refund = new PayPal\Api\Refund();
		$paypal_refund
			->setAmount($amount);

		return $paypal_refund;
	}

	/**
	 * Calculate transaction percent based on the price
	 * @param  Jam_Price $total
	 * @return float
	 */
	public static function transaction_fee_percent(Jam_Price $total)
	{
		$amount = $total->in('EUR');

		if ($amount <= 2500.00)
		{
			$percent = 0.034;
		}
		elseif ($amount <= 10000.00)
		{
			$percent = 0.029;
		}
		elseif ($amount <= 50000.00)
		{
			$percent = 0.027;
		}
		elseif ($amount <= 100000.00)
		{
			$percent = 0.024;
		}
		else
		{
			$percent = 0.019;
		}

		return $percent;
	}

	public static function transaction_fee_amount(Jam_Price $amount)
	{
		$percent = Model_Payment_Paypal::transaction_fee_percent($amount);

		return $amount
			->multiply_by($percent)
			->add(new Jam_Price(0.35, 'EUR'));
	}

	/**
	 * Calcualte the transaciton fee of paypal based on the amount
	 * @param  Jam_Price $amount
	 * @return Jam_Price
	 */
	public function transaction_fee(Jam_Price $amount)
	{
		return Model_Payment_Paypal::transaction_fee_amount($amount);
	}

	/**
	 * Use the current purchase to generate an "authorize url" where you can go and approve the purchase through paypal's interface.
	 * @param  array  $params must provide success_url and cancel_url
	 * @return Model_Payment_Paypal         self
	 */
	public function authorize_processor(array $params = array())
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
			'payment_id' => $payment->getId(), 
			'raw_response' => $payment->toArray(), 
			'status' => Model_Payment::PENDING
		));

		return $this;
	}

	/**
	 * Finalize the purchase after the user has approved the payment request.
	 * Provide payer_id in params to check the payment request.
	 * @param  array  $params must provide payer_id from the approval redirect
	 * @return Model_Payment_Paypal         self
	 */
	public function execute_processor(array $params = array())
	{
		$paypal_payment = PayPal\Api\Payment::get($this->payment_id, Paypal::api());
		
		$execution = new PayPal\Api\PaymentExecution();
		$execution
			->setPayerId($params['payer_id']);

		$response = $paypal_payment->execute($execution, Paypal::api());

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

	/**
	 * Refund amount of the purchases, specified in the Model_Store_Refund object
	 * @param  Model_Store_Refund $refund
	 * @param  array              $custom_params
	 * @return Model_Store_Refund                            self
	 */
	public function refund_processor(Model_Store_Refund $refund, array $custom_params = array())
	{
		$paypal_refund = Model_Payment_Paypal::convert_refund($refund);

		$sale = PayPal\Api\Sale::get($this->payment_id, Paypal::api());
		PayPal\Api\Payment::get($sale->getParentPayment(), Paypal::api());

		$response = $sale->refund($paypal_refund, Paypal::api(TRUE));

		$refund->raw_response = $response->toArray();
		$refund->status = ($response->getState() == 'completed') ? Model_Store_Refund::REFUNDED : $response->getState();

		return $this;
	}
}