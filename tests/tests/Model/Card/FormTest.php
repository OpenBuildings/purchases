<?php

/**
 * @group model.card_form
 */
class Model_Card_FormTest extends Testcase_Purchases {

	/**
	 * @covers Model_Card_Form::months
	 */
	public function test_months()
	{
		$this->assertCount(12, Model_Card_Form::months());
	}

	/**
	 * @covers Model_Card_Form::years
	 */
	public function test_years()
	{
		$years = Model_Card_Form::years();
		$this->assertCount(25, $years);
		$this->assertEquals(date('Y'), reset($years));
		$this->assertEquals(date('y'), key($years));

		$years = Model_Card_Form::years(12);
		$this->assertCount(13, $years);
		$this->assertEquals(date('Y'), reset($years));
		$this->assertEquals(date('y'), key($years));
	}

	public function data_process_credit_card()
	{
		return array(
			array('4111111111111111', '4111111111111111'),
			array('4111 1111 1111 1111', '4111111111111111'),
			array('4111	1111	1111	1111', '4111111111111111'),
			array('4111-1111-1111-1111', '4111111111111111'),
			array('4111-1111-1111-1111', '4111111111111111'),
		);
	}

	/**
	 * @dataProvider data_process_credit_card
	 * @covers Model_Card_Form::process_credit_card
	 */
	public function test_process_credit_card($cc, $expected)
	{
		$this->assertEquals($expected, Model_Card_Form::process_credit_card($cc));
	}

	/**
	 * @covers Model_Card_Form::as_array
	 */
	public function test_as_array()
	{
		$card_form = Jam::build('card_form');
		$card_form->name = 'Test User';
		$card_form->cardNumber = 'qwerhjgf43534fgn3453';

		$result = $card_form->as_array();

		$this->assertFalse(array_key_exists('cardNumber', $result));
		$this->assertTrue(array_key_exists('number', $result));
		$this->assertEquals($card_form->cardNumber, $result['number']);
	}

	public function test_filters()
	{
		$form = Jam::build('card_form', array(
			'name' => ' Mr. Thompson  	',
			'cardNumber' => '4111 1111 1111 1111',
			'expiryMonth' => '01',
			'expiryYear' => '25',
			'cvv' => ' 123 	',
		));

		$expected = array(
			'name' => 'Mr. Thompson',
			'number' => '4111111111111111',
			'expiryMonth' => '01',
			'expiryYear' => '25',
			'cvv' => '123',
		);

		$this->assertEquals($expected, $form->as_array());
	}

	public function data_vbv_params()
	{
		return array(
			array(
				array(
					'cardnumber' => 'qwerhjgf43534fgn3453',
					'expdate' => 'ZXCVBQWERTY',
					'callback_url' => 'ABCDE',
					'browser_useragent' => 'teeeestiing'
				),
				'qwerh jgf4 353 4fgn3453',
				'QWERTY',
				'ZXCVB',
				'ABCDE',
				'teeeestiing'
			),
			array(
				array(
					'cardnumber' => '',
					'expdate' => '2013',
					'callback_url' => 'example.com',
					'browser_useragent' => 'test user agent'
				),
				'   ',
				NULL,
				'2013',
				'example.com',
				'test user agent'
			),
		);
	}

	/**
	 * @dataProvider data_vbv_params
	 * @covers Model_Card_Form::vbv_params
	 * @backupGlobals
	 */
	public function test_vbv_params($expected_params, $number, $expiryMonth, $expiryYear, $callback_url, $user_agent)
	{
		$card_form = Jam::build('card_form');
		$card_form->cardNumber = $number;
		$card_form->expiryMonth = $expiryMonth;
		$card_form->expiryYear = $expiryYear;

		Request::$user_agent = $user_agent;
		$this->assertSame($expected_params, $card_form->vbv_params($callback_url));
	}

	public function data_validate()
	{
		$past_year = date('y', strtotime('-1 year'));
		$long_ago_past_year = date('y', strtotime('-5 year'));
		$future_year = date('y', strtotime('+1 year'));
		$distant_future_year = date('y', strtotime('+5 year'));

		$current_year = date('y');
		$current_month = date('m');

		$month_in_the_future = '12';
		$year_in_the_furutre = $current_year;

		$month_in_the_past = '01';
		$year_in_the_past = $current_year;
		$past_error_key = 'expiryMonth';

		if ($month_in_the_future == $current_month)
		{
			$month_in_the_future = '01';
			$year_in_the_furutre = $future_year;
			$past_error_key = 'expiryYear';
		}
		elseif ($month_in_the_past == $current_month)
		{
			$month_in_the_past = '12';
			$year_in_the_past = $past_year;
			$past_error_key = 'expiryYear';
		}


		return array(
			array(
				NULL,
				NULL,
				array(),
			),
			array(
				FALSE,
				FALSE,
				array(),
			),
			array(
				'05',
				'00',
				array(
					'expiryYear' => array(
						'card_expired' => array(),
					),
				),
			),
			array(
				'05',
				$past_year,
				array(
					'expiryYear' => array(
						'card_expired' => array(),
					),
				),
			),
			array(
				'05',
				$long_ago_past_year,
				array(
					'expiryYear' => array(
						'card_expired' => array(),
					),
				),
			),
			array(
				'05',
				$future_year,
				array(),
			),
			array(
				'05',
				$distant_future_year,
				array(),
			),
			array(
				$current_month,
				$current_year,
				array(),
			),
			array(
				$month_in_the_future,
				$year_in_the_furutre,
				array(),
			),
			array(
				$month_in_the_future,
				$year_in_the_furutre,
				array(),
			),
			array(
				$month_in_the_past,
				$year_in_the_past,
				array(
					$past_error_key => array(
						'card_expired' => array()
					),
				),
			),
		);
	}

	/**
	 * The data provider for the test is time sensitive.
	 * You cannot cover the case of an error with `expiryMonth` during January.
	 *
	 * @dataProvider data_validate
	 * @covers Model_Card_Form::validate
	 */
	public function test_validate($expiryMonth, $expiryYear, $expected_errors)
	{
		$card_form = Jam::build('card_form', array(
			'expiryMonth' => $expiryMonth,
			'expiryYear' => $expiryYear,
		));

		$card_form->validate();
		$this->assertSame($expected_errors, $card_form->errors()->as_array());
	}
}
