<?php defined('SYSPATH') OR die('No direct script access.');

use Openbuildings\Emp\Api;
use Openbuildings\Emp\Threatmatrix;

class Kohana_Processor_Paypal implements Processor {

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
				"item_{$index}_code"                            => $item->reference ? (string) $item->reference : $item->type,
				"item_{$index}_qty"                             => $item->quantity,
				"item_{$index}_discount"                        => $item->is_discount ? '1' : '0',
				"item_{$index}_name"                            => $item->reference ? URL::title($item->reference->name(), ' ', TRUE) : $item->type,
				"item_{$index}_unit_price_".$purchase->currency => number_format($item->price(), 2, '.', ''),
			));
		}

		return $params;
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
		$response = $this->api()
			->request(Api::ORDER_SUBMIT, array_merge($this->params(), Processor_Emp::params_for($purchase)));

		Processor_Emp::clear_threatmatrix();

		$status = ($response['transaction_response'] == 'A') ? Model_Payment::PAID : NULL;

		return Jam::build('payment', array('method' => 'emp', 'raw_response' => $response['raw'], 'status' => $status));
	}
}