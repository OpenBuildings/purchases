<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Tests timestamp fields.
 *
 * @package Jam
 * @group   jam
 * @group   jam.field
 * @group   jam.field.decimal
 */
class Jam_Field_TimestampTest extends PHPUnit_Framework_TestCase {

	public function data_set()
	{
		return array(
			array(array(), '10', '10'),
			array(array(), '10.21', '10.21'),
			array(array(), '', NULL),
			array(array(), NULL, NULL),
		);
	}

	/**
	 * @dataProvider data_set
	 * @covers Jam_Field_Decimal::set
	 * @covers Jam_Field_Decimal::_default
	 */
	public function test_set($options, $value, $expected)
	{
		$object = Jam::build('purchase_item');
		$field = new Jam_Field_Decimal($options);
		$result = $field->set($object, $value, TRUE);
		$this->assertSame($expected, $result);
	}

	public function data_convert()
	{
		return array(
			array(array(), '10', 10.00),
			array(array(), '10.123', 10.12),
			array(array(), NULL, NULL),
		);
	}

	/**
	 * @dataProvider data_convert
	 * @covers Jam_Field_Decimal::convert
	 */
	public function test_convert($options, $value, $expected)
	{
		$object = Jam::build('purchase_item');
		$field = new Jam_Field_Decimal($options);
		$result = $field->convert($object, $value, TRUE);
		$this->assertSame($expected, $result);
	}


}