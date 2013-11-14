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
}