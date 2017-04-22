<?php

class Array_Util_Test_Dummy {

}

/**
 * @group array_util
 */
class Array_UtilTest extends Testcase_Purchases {

	public function data_group_by()
	{
		return array(
			array(
				array('alpha', 'beta', 'gamma', 'getto', 'atton'),
				function($item){ return substr($item, 0, 1); },
				FALSE,
				array('a' => array('alpha', 'atton'), 'b' => array('beta'), 'g' => array('gamma', 'getto'))
			),
			array(
				array(10 => 'test1', 100 => 'test2', 90 => 'test3', 21 => '2222', 24 =>'3333'),
				function($item){ return substr($item, 0, 1); },
				FALSE,
				array('t' => array('test1', 'test2', 'test3'), '2' => array('2222'), '3' => array('3333')),
			),
			array(
				array(10 => 'test1', 100 => 'test2', 23 => 'test3', 321 =>'2222', 90 => '3333'),
				function($item){ return substr($item, 0, 1); },
				TRUE,
				array('t' => array(10 => 'test1', 100 => 'test2', 23 => 'test3'), '2' => array(321 => '2222'), '3' => array(90 => '3333')),
			),
		);
	}

	/**
	 * @dataProvider data_group_by
	 * @covers Array_Util::group_by
	 */
	public function test_group_by($array, $callback, $preserve_keys, $result)
	{
		$this->assertEquals($result, Array_Util::group_by($array, $callback, $preserve_keys));
	}

	public function data_not_instance_of()
	{
		return array(
			array(array(new stdClass, new stdClass), 'stdClass', FALSE),
			array(array(new stdClass, new stdClass, 'test' => 'test'), 'stdClass', 'test'),
			array(array(1, 2, 3), 'stdClass', 0),
			array(array(new Array_Util_Test_Dummy, new stdClass), 'Array_Util_Test_Dummy', 1),
		);
	}

	/**
	 * @dataProvider data_not_instance_of
	 * @covers Array_Util::not_instance_of
	 */
	public function test_not_instance_of($array, $class, $expected)
	{
		$this->assertSame($expected, Array_Util::not_instance_of($array, $class));
	}

	public function data_validate_instance_of()
	{
		return array(
			array(array(new stdClass, new stdClass), 'stdClass', FALSE),
			array(array(new stdClass, new stdClass, 'test' => 'test'), 'stdClass', 'The array must be of Model_Purchase_Item object, item [test] was "string"'),
			array(array(1, 2, 3), 'stdClass', 'The array must be of Model_Purchase_Item object, item [0] was "integer"'),
			array(array(new Array_Util_Test_Dummy, new stdClass), 'Array_Util_Test_Dummy', 'The array must be of Model_Purchase_Item object, item [1] was "stdClass"'),
		);
	}

	/**
	 * @dataProvider data_validate_instance_of
	 * @covers Array_Util::validate_instance_of
	 */
	public function test_validate_instance_of($array, $class, $expected_exception_message)
	{
		if ($expected_exception_message)
		{
			$this->expectException(\Kohana_Exception::class);
			$this->expectExceptionMessage($expected_exception_message);
		}

		$this->assertNull(Array_Util::validate_instance_of($array, $class));
	}
}
