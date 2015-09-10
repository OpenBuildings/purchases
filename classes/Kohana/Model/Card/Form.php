<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * This form is used to ease creating html form for credit cards
 *
 * @package    Openbuildings\Purchases
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Card_Form extends Jam_Validated {

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->fields(array(
				'name' => Jam::field('string', array(
					'filters' => array('trim')
				)),
				'number' => Jam::field('string', array(
					'filters' => array('Model_Card_Form::process_credit_card')
				)),
				'expiryMonth' => Jam::field('string'),
				'expiryYear' => Jam::field('string'),
				'cvv' => Jam::field('string', array('filters' => array('trim'))),
			))
			->validator(
				'name',
				'number',
				'expiryMonth',
				'expiryYear',
				'cvv',
				array(
					'present' => TRUE,
				)
			)
			->validator('name',	array(
				'length' => array(
					'minimum' => 3,
					'maximum' => 40,
				),
				'format' => array(
					'regex' => "/^[\p{L}\p{M}\.\-\' ]+$/u",
				)
			))
			->validator('number', array(
				'length' => array(
					'maximum' => 20,
				),
				'format' => array(
					'credit_card' => TRUE,
				)
			))
			->validator('expiryMonth', array(
				'format' => array(
					'regex' => '/\d{2}/',
				)
			))
			->validator('expiryYear',	array(
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
		if ($this->expiryMonth AND $this->expiryYear)
		{
			$year = 2000 + (int) $this->expiryYear;
			if ( $year < (int) date('Y'))
			{
				$this->errors()->add('expiryYear', 'card_expired');
			}
			elseif ( (int) $this->expiryMonth < (int) date('n')
			 AND $year === (int) date('Y'))
			{
				$this->errors()->add('expiryMonth', 'card_expired');
			}
		}
	}
}
