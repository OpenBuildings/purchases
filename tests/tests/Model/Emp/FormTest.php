<?php

/**
 * @group model.emp_form
 */
class Model_Emp_FormTest extends Testcase_Purchases {

	/**
	 * @covers Model_Emp_Form::months
	 */
	public function test_months()
	{
		$this->assertCount(12, Model_Emp_Form::months());
	}

	/**
	 * @covers Model_Emp_Form::years
	 */
	public function test_years()
	{
		$years = Model_Emp_Form::years();
		$this->assertCount(25, $years);
		$this->assertEquals(date('Y'), reset($years));
		$this->assertEquals(date('y'), key($years));

		$years = Model_Emp_Form::years(12);
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
	 * @covers Model_Emp_Form::process_credit_card
	 */
	public function test_process_credit_card($cc, $expected)
	{
		$this->assertEquals($expected, Model_Emp_Form::process_credit_card($cc));
	}

	public function test_filters()
	{
		$form = Jam::build('emp_form', array(
			'card_holder_name' => ' Mr. Thompson  	',
			'card_number' => '4111 1111 1111 1111',
			'exp_month' => '01',
			'exp_year' => '25',
			'cvv' => ' 123 	',
		));

		$expected = array(
			'card_holder_name' => 'Mr. Thompson',
			'card_number' => '4111111111111111',
			'exp_month' => '01',
			'exp_year' => '25',
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
					'expdate' => 'QWERTYZXCVB',
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
	 * @covers Model_Emp_Form::vbv_params
	 * @backupGlobals
	 */
	public function test_vbv_params($expected_params, $card_number, $exp_month, $exp_year, $callback_url, $user_agent)
	{
		$emp_form = Jam::build('emp_form');
		$emp_form->card_number = $card_number;
		$emp_form->exp_month = $exp_month;
		$emp_form->exp_year = $exp_year;

		Request::$user_agent = $user_agent;
		$this->assertSame($expected_params, $emp_form->vbv_params($callback_url));
	}
}