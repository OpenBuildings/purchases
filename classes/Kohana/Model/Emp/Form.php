<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This form is used to ease creating html form for credit cards, that use emp paymnet processor
 *
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Emp_Form extends Jam_Validated {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->fields(array(
				'card_holder_name' => Jam::field('string', array(
					'filters' => array('trim')
				)),
				'card_number' => Jam::field('string', array(
					'filters' => array('Model_Emp_Form::process_credit_card')
				)),
				'exp_month' => Jam::field('string'),
				'exp_year' => Jam::field('string'),
				'cvv' => Jam::field('string', array('filters' => array('trim'))),
			))
			->validator(
				'card_holder_name',
				'card_number',
				'exp_month',
				'exp_year',
				'cvv',
				array(
					'present' => TRUE,
				)
			)
			->validator('card_holder_name',	array(
				'length' => array(
					'minimum' => 3,
					'maximum' => 40,
				)
			))
			->validator('card_number', array(
				'length' => array(
					'maximum' => 20,
				),
				'format' => array(
					'credit_card' => TRUE,
				)
			))
			->validator('exp_month', array(
				'format' => array(
					'regex' => '/\d{2}/',
				)
			))
			->validator('exp_year',	array(
				'format' => array(
					'regex' => '/\d{2}/',
				)
			))
			->validator('cvv',array(
				'format' => array(
					'regex' => '/\d{2,4}/',
				)
			));
	}

	public static function process_credit_card($card)
	{
		return preg_replace('/\s|\-/', '', $card);
	}

	public function vbv_params($callback_url)
	{
		return array(
			'cardnumber' => $this->card_number,
			'expdate' => $this->exp_month.$this->exp_year,
			'callback_url' => $callback_url,
			'browser_useragent' => Request::$user_agent,
		);
	}

	public static function months()
	{
		return array('01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', '06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', '11' => '11', '12' => '12');
	}

	public static function years($years_in_the_furutre = 24)
	{
		$years = range(date('y'), date('y', strtotime('+'.$years_in_the_furutre.' years')));
		$labels = range(date('Y'), date('Y', strtotime('+'.$years_in_the_furutre.' years')));

		return array_combine($years, $labels);
	}

	public function validate()
	{
		if ($this->exp_month AND $this->exp_year)
		{
			$year = 2000 + (int) $this->exp_year;
			if ( $year < (int) date('Y'))
			{
				$this->errors()->add('exp_year', 'card_expired');
			}
			elseif ( (int) $this->exp_month < (int) date('n')
			 AND $year === (int) date('Y'))
			{
				$this->errors()->add('exp_month', 'card_expired');
			}
		}
	}
}
