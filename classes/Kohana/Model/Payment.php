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
			->validator('purchase', 'model', array('present' => TRUE));
	}

	/**
	 * Calculate a transaction fee, based on the price provided. By default returns NULL
	 * @param  Jam_Price $price 
	 * @return Jam_Price           
	 */
	public function transaction_fee(Jam_Price $price)
	{
		return NULL;
	}

	/**
	 * This would return the url needed to authorize the purchase by the user, if the payment method requires it
	 * @return string 
	 */
	public function authorize_url()
	{
		return $this->_authorize_url;
	}

	/**
	 * Execute authorize_processor and model.before_authorize and model.after_authorize. Save the model after authorize_processor()
	 * @param  array  $params pass this to authorize_processor()
	 * @return Model_Payment  self
	 */
	public function authorize(array $params = array())
	{
		$this->meta()->events()->trigger('model.before_authorize', $this, array($params));
		$this->meta()->events()->trigger('model.before_first_operation', $this, array($params));

		$this->authorize_processor($params);
		$this->purchase->payment = $this;
		$this->purchase->save();

		$this->meta()->events()->trigger('model.after_first_operation', $this, array($params));
		$this->meta()->events()->trigger('model.after_authorize', $this, array($params));

		return $this;
	}

	/**
	 * Execute execute_processor and model.before_execute and model.after_execute. Save the model after execute_processor()
	 * @param  array  $params pass this to execute_processor()
	 * @return Model_Payment  self
	 */
	public function execute(array $params = array())
	{
		$this->meta()->events()->trigger('model.before_execute', $this, array($params));
		$first_operation = ! $this->loaded();

		if ($first_operation)
		{
			$this->meta()->events()->trigger('model.before_first_operation', $this, array($params));
		}

		$this->execute_processor($params);
		$this->purchase->payment = $this;
		$this->purchase->save();

		if ($first_operation)
		{
			$this->meta()->events()->trigger('model.after_first_operation', $this, array($params));
		}
		$this->meta()->events()->trigger('model.after_execute', $this, array($params));

		return $this;
	}

	/**
	 * Execute refund_processor and model.before_refund and model.after_refund. Save the refund model after refund_processor()
	 * @param  Model_Store_Refund  $refund pass this to refund_processor()
	 * @param  array  $custom_params pass this to refund_processor()
	 * @return Model_Payment  self
	 */
	public function refund(Model_Store_Refund $refund, array $custom_params = array())
	{
		$this->meta()->events()->trigger('model.before_refund', $this, array($refund, $custom_params));

		$this->refund_processor($refund, $custom_params);
		$refund->save();

		$this->meta()->events()->trigger('model.after_refund', $this, array($refund, $custom_params));

		return $this;
	}

	/**
	 * Extend this in the child models.
	 * @param  array  $params 
	 * @throws Kohana_Exception If method not implemented
	 */
	public function authorize_processor(array $params = array())
	{
		throw new Kohana_Exception('This payment does not support authorize');
	}

	/**
	 * Extend this in the child models.
	 * @param  array  $params 
	 * @throws Kohana_Exception If method not implemented
	 */
	public function execute_processor(array $params = array())
	{
		throw new Kohana_Exception('This payment does not support execute');
	}

	/**
	 * Extend this in the child models.
	 * @param  array  $params 
	 * @throws Kohana_Exception If method not implemented
	 */
	public function refund_processor(Model_Store_Refund $refund, array $params = array())
	{
		throw new Kohana_Exception('This payment does not support refunds');
	}
}