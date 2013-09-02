<?php defined('SYSPATH') OR die('No direct script access.');

use Openbuildings\Emp\Api;
use Openbuildings\Emp\Threatmatrix;

class Kohana_Processor_Emp implements Processor {

	const THREATMATRIX_SESSION_KEY = '_threatmatrix';

	protected static $_api;

	public static function api()
	{
		if ( ! Processor_Emp::$_api) 
		{
			$config = Kohana::$config->load('purchases.processor.emp.api');
			Processor_Emp::$_api = new Api($config['gateway_url'], $config['client_id'], $config['api_key']);

			if (Processor_Emp::is_threatmatrix_enabled()) 
			{
				Processor_Emp::$_api->threatmatrix(Processor_Emp::threatmatrix());
			}
		}
		return Processor_Emp::$_api;
	}

	public static function is_threatmatrix_enabled()
	{
		return (bool) Kohana::$config->load('purchases.processor.emp.threatmatrix');
	}

	public static function clear_threatmatrix()
	{
		if (Processor_Emp::is_threatmatrix_enabled()) 
		{
			Session::instance()->delete(Processor_Emp::THREATMATRIX_SESSION_KEY);
		}
	}

	public static function threatmatrix()
	{
		if (Processor_Emp::is_threatmatrix_enabled())
		{
			$threatmatrix = Session::instance()->get(Processor_Emp::THREATMATRIX_SESSION_KEY);

			if ( ! $threatmatrix)
			{
				$config = Kohana::$config->load('purchases.processor.emp.threatmatrix');

				$threatmatrix = new Threatmatrix($config['org_id'], $config['client_id']);
				Session::instance()->set(Processor_Emp::THREATMATRIX_SESSION_KEY, $threatmatrix);
			}

			return $threatmatrix;
		}
	}

	public static function params_for(Model_Purchase $purchase)
	{
		$params = array(
			'payment_method'         => 'creditcard',
			'order_reference'        => $purchase->number,
			'order_currency'         => $purchase->currency,
			'customer_email'         => $purchase->creator->email,
			'ip_address'             => Request::$client_ip,
			'credit_card_trans_type' => 'sale',
			'test_transaction'       => Kohana::$environment === Kohana::PRODUCTION ? '0' : '1',
		);

		foreach ($purchase->items(array('is_payable' => TRUE)) as $i => $item) 
		{
			$index = $i+1;

			$params = array_merge($params, array(
				"item_{$index}_predefined"                      => '0',
				"item_{$index}_digital"                         => '0',
				"item_{$index}_code"                            => $item->id(),
				"item_{$index}_qty"                             => $item->quantity,
				"item_{$index}_discount"                        => $item->is_discount ? '1' : '0',
				"item_{$index}_name"                            => $item->reference ? URL::title($item->reference->name(), ' ', TRUE) : $item->type,
				"item_{$index}_unit_price_".$purchase->currency => number_format($item->price(), 2, '.', ''),
			));
		}

		return $params;
	}

	public static function find_item_id(array $cart, $id)
	{
		$items = isset($cart['item'][0]) ? $cart['item'] : array($cart['item']);
		foreach ($items as $item) 
		{
			if ($item['code'] == $id) 
				return $item['id'];
		}
	}

	public static function params_for_refund(Model_Store_Refund $refund)
	{
		$payment = $refund->payment_insist();

		$params = array(
			'order_id' => $payment->raw_response['order_id'],
			'trans_id' => $payment->payment_id,
			'reason'   => $refund->reason,
		);

		if (count($refund->items)) 
		{
			foreach ($refund->items as $i => $item) 
			{
				$index = $i+1;
				$item_params = array(
					"item_{$index}_id" => Processor_Emp::find_item_id($payment->raw_response['cart'], $item->purchase_item->id()),
				);

				if ($item->amount)
				{
					$item_params["item_{$index}_amount"] = number_format($item->amount, 2, '.', '');
				}

				$params = array_merge($params, $item_params);
			}
		}
		else
		{
			$params['amount'] = number_format($refund->total_amount(), 2, '.', '');
		}

		return $params;
	}

	public static function refund(Model_Store_Refund $refund)
	{
		$params = Processor_Emp::params_for_refund($refund);

		$response = Processor_Emp::api()
			->request(Api::ORDER_CREDIT, $params);

		$refund->raw_response = $response;
		$refund->status = Model_Store_Refund::REFUNDED;

		return $this;
	}

	protected $_params = array();
	protected $_next_url;

	function __construct(array $params, $next_url = NULL) 
	{
		$this->_params = $params;
		$this->_next_url = $next_url;
	}
	
	public function params()
	{
		return $this->_params;
	}

	public function next_url()
	{
		return $this->_next_url;
	}

	public function execute(Model_Purchase $purchase)
	{
		$response = Processor_Emp::api()
			->request(Api::ORDER_SUBMIT, array_merge($this->params(), Processor_Emp::params_for($purchase)));

		Processor_Emp::clear_threatmatrix();

		$status = ($response['transaction_response'] == 'A') ? Model_Payment::PAID : NULL;

		return array(
			'method' => 'emp', 
			'payment_id' => $response['transaction_id'], 
			'raw_response' => $response['raw'], 
			'status' => $status
		);
	}

	public static function complete(Model_Payment $payment, array $params)
	{
		// do nothing;
	}

}