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
}