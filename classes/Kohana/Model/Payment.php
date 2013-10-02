<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Payment extends Jam_Model {

	const PAID = 'paid';
	const PENDING = 'pending';

	public $_authorize_url;
	
	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'paranoid' => Jam::behavior('paranoid'),
			))
			->associations(array(
				'purchase' => Jam::association('belongsto', array('inverse_of' => 'payment')),
			))
			->fields(array(
				'id' => Jam::field('primary'),
				'model' => Jam::field('polymorphic'),
				'payment_id' => Jam::field('string'),
				'raw_response' => Jam::field('serialized', array('method' => 'json')),
				'status' => Jam::field('string'),
				'created_at' => Jam::field('timestamp', array('auto_now_create' => TRUE, 'format' => 'Y-m-d H:i:s')),
				'updated_at' => Jam::field('timestamp', array('auto_now_update' => TRUE, 'format' => 'Y-m-d H:i:s')),
			))
			->validator('purchase', 'model', array('present' => TRUE))
			->validator('method', array('choice' => array('in' => array('emp', 'paypal'))));
	}

	public function transaction_fee(Jam_Price $price)
	{
		return NULL;
	}

	public function authorize_url()
	{
		return $this->_authorize_url;
	}

	public function authorize(array $params = array())
	{
		$this->meta()->events()->trigger('model.before_authorize', $this, array($params));

		$this->authorize_processor($params);
		$this->save();

		$this->meta()->events()->trigger('model.after_authorize', $this, array($params));

		return $this;
	}

	public function execute(array $params = array())
	{
		$this->meta()->events()->trigger('model.before_execute', $this, array($params));

		$this->execute_processor($params);
		$this->save();

		$this->meta()->events()->trigger('model.after_execute', $this, array($params));

		return $this;
	}

	public function refund(Model_Store_Refund $refund, array $custom_params = array())
	{
		$this->meta()->events()->trigger('model.before_refund', $this, array($refund, $custom_params));

		$this->refund_processor($refund, $custom_params);
		$refund->save();

		$this->meta()->events()->trigger('model.after_refund', $this, array($refund, $custom_params));

		return $this;
	}

	public function authorize_processor(array $params = array())
	{
		throw new Kohana_Exception('This payment does not support authorize');
	}

	public function execute_processor(array $params = array())
	{
		throw new Kohana_Exception('This payment does not support execute');
	}

	public function refund_processor(Model_Store_Refund $refund, array $params = array())
	{
		throw new Kohana_Exception('This payment does not support refunds');
	}
}